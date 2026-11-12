<?php

namespace App\Http\Controllers;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Models\NepalWard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NepalLocationController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $items = NepalProvince::where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('name_ne', 'like', "%{$q}%");
                });
            })
            ->orderBy('id')
            ->get(['id', 'name', 'name_ne', 'code']);

        return response()->json(['data' => $items]);
    }

    public function districts(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');
        $q = trim((string) $request->query('q', ''));

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
}
