<?php

namespace App\Http\Controllers;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Models\NepalWard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NepalLocationController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            $items = Cache::remember('nepal_loc_provinces', 3600, function () {
                return NepalProvince::where('is_active', true)
                    ->orderBy('id')
                    ->get(['id', 'name', 'name_ne', 'code']);
            });
            return response()->json(['data' => $items]);
        }

        $items = NepalProvince::where('is_active', true)
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ne', 'like', "%{$q}%");
            })
            ->orderBy('id')
            ->get(['id', 'name', 'name_ne', 'code']);

        return response()->json(['data' => $items]);
    }

    public function districts(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');
        $q = trim((string) $request->query('q', ''));

        if ($q === '' && $provinceId) {
            $cacheKey = 'nepal_loc_districts_p_' . (int) $provinceId;
            $items = Cache::remember($cacheKey, 3600, function () use ($provinceId) {
                return NepalDistrict::where('is_active', true)
                    ->where('province_id', (int) $provinceId)
                    ->orderBy('name')
                    ->get(['id', 'province_id', 'name', 'name_ne', 'code']);
            });
            return response()->json(['data' => $items]);
        }

        $items = NepalDistrict::where('is_active', true)
            ->when($provinceId, function ($query) use ($provinceId) {
                $query->where('province_id', (int) $provinceId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('name_ne', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'province_id', 'name', 'name_ne', 'code']);

        return response()->json(['data' => $items]);
    }

    public function localLevels(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');
        $q = trim((string) $request->query('q', ''));

        if ($q === '' && $districtId) {
            $cacheKey = 'nepal_loc_locals_d_' . (int) $districtId;
            $items = Cache::remember($cacheKey, 3600, function () use ($districtId) {
                return NepalLocalLevel::where('is_active', true)
                    ->where('district_id', (int) $districtId)
                    ->orderBy('name')
                    ->get(['id', 'district_id', 'name', 'name_ne', 'level_type', 'total_wards', 'code']);
            });
            return response()->json(['data' => $items]);
        }

        $items = NepalLocalLevel::where('is_active', true)
            ->when($districtId, function ($query) use ($districtId) {
                $query->where('district_id', (int) $districtId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('name_ne', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'district_id', 'name', 'name_ne', 'level_type', 'total_wards', 'code']);

        return response()->json(['data' => $items]);
    }

    public function wards(Request $request): JsonResponse
    {
        $localLevelId = (int) $request->query('local_level_id', 0);
        $localLevel = NepalLocalLevel::find($localLevelId);

        if (!$localLevel) {
            return response()->json(['data' => []]);
        }

        $wards = NepalWard::where('local_level_id', $localLevel->id)
            ->where('is_active', true)
            ->orderBy('ward_number')
            ->get(['id', 'ward_number', 'name', 'name_ne']);

        if ($wards->isEmpty() && $localLevel->total_wards > 0) {
            $generated = [];
            for ($i = 1; $i <= $localLevel->total_wards; $i++) {
                $generated[] = [
                    'ward_number' => $i,
                    'name' => 'Ward ' . $i,
                    'name_ne' => 'वडा नं. ' . \App\Helpers\NepaliHelper::toDevanagari((string) $i),
                ];
            }
            return response()->json(['data' => $generated]);
        }

        return response()->json(['data' => $wards]);
    }

    public function apiProvinceDistricts(int $province): JsonResponse
    {
        $prov = NepalProvince::where('is_active', true)->find($province);
        if (!$prov) {
            return response()->json(['message' => __('Province not found.')], 404);
        }

        $items = Cache::remember('nepal_loc_districts_p_' . $prov->id, 3600, function () use ($prov) {
            return NepalDistrict::where('is_active', true)
                ->where('province_id', $prov->id)
                ->orderBy('name')
                ->get(['id', 'province_id', 'name', 'name_ne', 'code']);
        });

        return response()->json(['data' => $items]);
    }

    public function apiDistrictLocalGovernments(int $district): JsonResponse
    {
        $dist = NepalDistrict::where('is_active', true)->find($district);
        if (!$dist) {
            return response()->json(['message' => __('District not found.')], 404);
        }

        $items = Cache::remember('nepal_loc_locals_d_' . $dist->id, 3600, function () use ($dist) {
            return NepalLocalLevel::where('is_active', true)
                ->where('district_id', $dist->id)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name', 'name_ne', 'level_type', 'total_wards', 'code']);
        });

        return response()->json(['data' => $items]);
    }

    public function apiShowLocalGovernment(int $id): JsonResponse
    {
        $local = NepalLocalLevel::with(['district:id,province_id,name,name_ne,code', 'district.province:id,name,name_ne,code'])
            ->find($id);

        if (!$local) {
            return response()->json(['message' => __('Local government not found.')], 404);
        }

        return response()->json(['data' => $local]);
    }
}

