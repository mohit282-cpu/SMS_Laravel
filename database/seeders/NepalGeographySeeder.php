<?php

namespace Database\Seeders;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class NepalGeographySeeder extends Seeder
{
    public function run()
    {
        $datasetPath = database_path('data/nepal_locations.json');

        if (!File::exists($datasetPath)) {
            throw new \RuntimeException("Nepal locations dataset not found at {$datasetPath}");
        }

        $payload = json_decode(File::get($datasetPath), true);
        if (!is_array($payload) || empty($payload['provinces'])) {
            throw new \RuntimeException('Invalid nepal_locations.json structure.');
        }

        $source = $payload['metadata']['source'] ?? 'Government of Nepal, MoFAGA & Department of Postal Services';
        $verifiedDate = $payload['metadata']['verified_date'] ?? null;

        foreach ($payload['provinces'] as $pData) {
            $province = NepalProvince::updateOrCreate(
                ['code' => $pData['code']],
                [
                    'country_code' => 'NP',
                    'name' => $pData['name'],
                    'name_ne' => $pData['name_ne'],
                    'is_active' => true,
                    'effective_date' => $verifiedDate,
                    'data_source' => $source,
                ]
            );

            foreach (($pData['districts'] ?? []) as $dData) {
                $district = NepalDistrict::updateOrCreate(
                    ['province_id' => $province->id, 'name' => $dData['name']],
                    [
                        'name_ne' => $dData['name_ne'],
                        'code' => $dData['code'] ?? null,
                        'is_active' => true,
                        'effective_date' => $verifiedDate,
                        'data_source' => $source,
                    ]
                );

                foreach (($dData['local_levels'] ?? []) as $lData) {
                    NepalLocalLevel::updateOrCreate(
                        ['district_id' => $district->id, 'name' => $lData['name']],
                        [
                            'name_ne' => $lData['name_ne'] ?? null,
                            'code' => $lData['code'] ?? null,
                            'level_type' => $lData['level_type'] ?? 'municipality',
                            'total_wards' => (int) ($lData['total_wards'] ?? 1),
                            'is_active' => true,
                            'effective_date' => $verifiedDate,
                            'data_source' => $source,
                        ]
                    );
                }
            }
        }

        app(\App\Services\NepalLocationService::class)->clearCache();
    }
}
