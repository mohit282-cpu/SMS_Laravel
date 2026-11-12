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
    public const OFFICIAL_TYPE_COUNTS = [
        'metropolitan' => 6,
        'sub_metropolitan' => 11,
        'municipality' => 276,
        'rural_municipality' => 460,
    ];

    /**
     * Validate that Province -> District -> Local Government -> Ward relationships are consistent.
     */
    public function validateHierarchy(?int $provinceId, ?int $districtId, ?int $localLevelId, ?int $wardNo = null): ?string
    {
        if ($districtId !== null) {
            $district = NepalDistrict::find($districtId);
            if (!$district) {
                return __('Selected district does not exist.');
            }
            if ($provinceId !== null && (int) $district->province_id !== (int) $provinceId) {
                return __('Selected district does not belong to the selected province.');
            }
        }

        if ($localLevelId !== null) {
            $localLevel = NepalLocalLevel::find($localLevelId);
            if (!$localLevel) {
                return __('Selected local government does not exist.');
            }
            if ($districtId !== null && (int) $localLevel->district_id !== (int) $districtId) {
                return __('Selected local government does not belong to the selected district.');
            }
            if ($wardNo !== null && $localLevel->total_wards > 0 && ($wardNo < 1 || $wardNo > $localLevel->total_wards)) {
                return __('Ward number must be between 1 and :max for the selected local government.', [
                    'max' => $localLevel->total_wards,
                ]);
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
            'by_type' => $byType,
            'is_complete_official_dataset' => (
                $provinces === self::OFFICIAL_TOTAL_PROVINCES &&
                $districts === self::OFFICIAL_TOTAL_DISTRICTS &&
                $localLevels === self::OFFICIAL_TOTAL_LOCAL_LEVELS &&
                $byType['metropolitan']['complete'] &&
                $byType['sub_metropolitan']['complete'] &&
                $byType['municipality']['complete'] &&
                $byType['rural_municipality']['complete']
            ),
        ];
    }
}
