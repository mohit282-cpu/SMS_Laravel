<?php

namespace App\Services;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;

class NepalLocationService
{
    public const OFFICIAL_TOTAL_PROVINCES = 7;
    public const OFFICIAL_TOTAL_DISTRICTS = 77;
    public const OFFICIAL_TOTAL_LOCAL_LEVELS = 753;
    public const OFFICIAL_TOTAL_WARDS = 6743;
    public const OFFICIAL_TYPE_COUNTS = [
        'metropolitan' => 6,
        'sub_metropolitan' => 11,
        'municipality' => 276,
        'rural_municipality' => 460,
    ];

    /**
     * Validate that Province -> District -> Local Government -> Ward relationships are consistent and active.
     */
    public function validateHierarchy(?int $provinceId, ?int $districtId, ?int $localLevelId, ?int $wardNo = null, bool $requireActive = true): ?string
    {
        if ($provinceId !== null) {
            $province = NepalProvince::find($provinceId);
            if (!$province) {
                return __('Selected province does not exist.');
            }
            if ($requireActive && !$province->is_active) {
                return __('Selected province is inactive.');
            }
        }

        if ($districtId !== null) {
            $district = NepalDistrict::find($districtId);
            if (!$district) {
                return __('Selected district does not exist.');
            }
            if ($requireActive && !$district->is_active) {
                return __('Selected district is inactive.');
            }
            if ($provinceId !== null && (int) $district->province_id !== (int) $provinceId) {
                return __('Selected district does not belong to the selected province.');
            }
        }

        $localLevel = null;
        if ($localLevelId !== null) {
            $localLevel = NepalLocalLevel::find($localLevelId);
            if (!$localLevel) {
                return __('Selected local government does not exist.');
            }
            if ($requireActive && !$localLevel->is_active) {
                return __('Selected local government is inactive.');
            }
            if ($districtId !== null && (int) $localLevel->district_id !== (int) $districtId) {
                return __('Selected local government does not belong to the selected district.');
            }
        }

        if ($wardNo !== null) {
            if (!$localLevel) {
                return __('Please select a local government before choosing a ward number.');
            }
            if ($localLevel->total_wards < 1 || $wardNo < 1 || $wardNo > $localLevel->total_wards) {
                return __('Ward number must be between 1 and :max for the selected local government.', [
                    'max' => $localLevel->total_wards,
                ]);
            }
            $hasExplicitWards = \App\Models\NepalWard::where('local_level_id', $localLevel->id)->exists();
            if ($hasExplicitWards) {
                $ward = \App\Models\NepalWard::where('local_level_id', $localLevel->id)
                    ->where('ward_number', $wardNo)
                    ->first();
                if (!$ward) {
                    return __('Selected ward does not belong to the selected local government.');
                }
                if ($requireActive && !$ward->is_active) {
                    return __('Selected ward is inactive.');
                }
            }
        }

        return null;
    }

    /**
     * Check whether the local database currently holds the complete official 7/77/753 Nepal dataset.
     */
    public function getDatasetCompletenessReport(): array
    {
        $provinces = NepalProvince::where('is_active', true)->count();
        $districts = NepalDistrict::where('is_active', true)->count();
        $localLevels = NepalLocalLevel::where('is_active', true)->count();
        $totalWards = (int) NepalLocalLevel::where('is_active', true)->sum('total_wards');

        $byType = [];
        foreach (self::OFFICIAL_TYPE_COUNTS as $type => $expected) {
            $actual = NepalLocalLevel::where('is_active', true)->where('level_type', $type)->count();
            $byType[$type] = [
                'expected' => $expected,
                'actual' => $actual,
                'complete' => ($actual === $expected),
            ];
        }

        return [
            'provinces' => ['expected' => self::OFFICIAL_TOTAL_PROVINCES, 'actual' => $provinces],
            'districts' => ['expected' => self::OFFICIAL_TOTAL_DISTRICTS, 'actual' => $districts],
            'local_levels' => ['expected' => self::OFFICIAL_TOTAL_LOCAL_LEVELS, 'actual' => $localLevels],
            'total_wards' => ['expected' => self::OFFICIAL_TOTAL_WARDS, 'actual' => $totalWards],
            'by_type' => $byType,
            'is_complete_official_dataset' => (
                $provinces === self::OFFICIAL_TOTAL_PROVINCES &&
                $districts === self::OFFICIAL_TOTAL_DISTRICTS &&
                $localLevels === self::OFFICIAL_TOTAL_LOCAL_LEVELS &&
                $totalWards === self::OFFICIAL_TOTAL_WARDS &&
                $byType['metropolitan']['complete'] &&
                $byType['sub_metropolitan']['complete'] &&
                $byType['municipality']['complete'] &&
                $byType['rural_municipality']['complete']
            ),
        ];
    }
}

