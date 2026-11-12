<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Services\AuditLogService;
use App\Services\NepalLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NepalLocationAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function index(Request $request, NepalLocationService $locationService)
    {
        $provinceId = $request->query('province_id');
        $districtId = $request->query('district_id');
        $levelType = $request->query('level_type');
        $q = trim((string) $request->query('q', ''));

        $provinces = NepalProvince::orderBy('id')->get();
        $districts = NepalDistrict::with('province')
            ->when($provinceId, fn ($query) => $query->where('province_id', (int) $provinceId))
            ->orderBy('name')
            ->get();

        $localLevels = NepalLocalLevel::with(['district.province'])
            ->when($provinceId, function ($query) use ($provinceId) {
                $query->whereHas('district', fn ($d) => $d->where('province_id', (int) $provinceId));
            })
            ->when($districtId, fn ($query) => $query->where('district_id', (int) $districtId))
            ->when($levelType, fn ($query) => $query->where('level_type', $levelType))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('name_ne', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->appends($request->query());

        $report = $locationService->getDatasetCompletenessReport();

        return view('pages.nepal.locations.index', compact(
            'provinces',
            'districts',
            'localLevels',
            'report',
            'provinceId',
            'districtId',
            'levelType',
            'q'
        ));
    }

    public function toggleStatus(string $type, int $id)
    {
        if (!Qs::userIsSuperAdmin()) {
            abort(403);
        }

        $model = $this->resolveModel($type, $id);
        $oldActive = (bool) $model->is_active;
        $model->is_active = !$oldActive;
        $model->save();

        app(NepalLocationService::class)->clearCache();

        app(AuditLogService::class)->log(
            'nepal_location.toggle',
            'nepal_locations',
            "Toggled {$type} '{$model->name}' active status to " . ($model->is_active ? 'active' : 'inactive'),
            $model,
            ['is_active' => $oldActive],
            ['is_active' => $model->is_active],
            'success'
        );

        return back()->with('flash_success', __('Location status updated.'));
    }

    public function update(Request $request, string $type, int $id)
    {
        if (!Qs::userIsSuperAdmin()) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string|max:150',
            'name_ne' => 'required|string|max:180',
            'code' => 'nullable|string|max:30',
        ];
        if ($type === 'local_level') {
            $rules['total_wards'] = 'nullable|integer|min:1|max:50';
        }

        $validated = $request->validate($rules);
        $model = $this->resolveModel($type, $id);
        $oldValues = $model->only(array_keys($validated));

        $model->fill($validated);
        $model->save();

        app(NepalLocationService::class)->clearCache();

        app(AuditLogService::class)->log(
            'nepal_location.update',
            'nepal_locations',
            "Updated {$type} '{$model->name}'",
            $model,
            $oldValues,
            $validated,
            'success'
        );

        return back()->with('flash_success', __('Location details updated.'));
    }

    protected function resolveModel(string $type, int $id)
    {
        switch ($type) {
            case 'province':
                return NepalProvince::findOrFail($id);
            case 'district':
                return NepalDistrict::findOrFail($id);
            case 'local_level':
                return NepalLocalLevel::findOrFail($id);
            default:
                abort(404);
        }
    }
}

