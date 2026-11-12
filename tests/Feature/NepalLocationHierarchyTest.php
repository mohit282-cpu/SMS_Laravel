<?php

namespace Tests\Feature;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Models\NepalSettlement;
use App\Models\NepalWard;
use App\Services\NepalLocationService;
use App\User;
use Database\Seeders\NepalGeographySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NepalLocationHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeAuthUser(): User
    {
        return User::create([
            'name' => 'Geo Test User',
            'email' => 'geo_' . uniqid() . '@sms.local',
            'username' => 'geo_' . uniqid(),
            'user_type' => 'super_admin',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_cascading_endpoints_and_bilingual_search()
    {
        $this->seed(NepalGeographySeeder::class);
        $user = $this->makeAuthUser();

        $koshi = NepalProvince::where('code', 'P1')->firstOrFail();
        $morang = NepalDistrict::where('province_id', $koshi->id)->where('name', 'Morang')->firstOrFail();

        $biratnagar = NepalLocalLevel::create([
            'district_id' => $morang->id,
            'name' => 'Biratnagar Metropolitan City',
            'name_ne' => 'विराटनगर महानगरपालिका',
            'level_type' => 'metropolitan',
            'total_wards' => 19,
            'is_active' => true,
            'data_source' => 'Government of Nepal Official Dataset',
        ]);

        NepalWard::create([
            'local_level_id' => $biratnagar->id,
            'ward_number' => 1,
            'name' => 'Ward 1',
            'name_ne' => 'वडा नं. १',
        ]);

        NepalSettlement::create([
            'local_level_id' => $biratnagar->id,
            'ward_number' => 1,
            'name' => 'Tinpaini',
            'name_ne' => 'तिनपैनी',
            'settlement_type' => 'tole',
        ]);

        // Bilingual search on provinces
        $this->actingAs($user)
            ->getJson(route('nepal_locations.provinces', ['q' => 'कोशी']))
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Koshi Province']);

        // Cascading district filter by province
        $this->actingAs($user)
            ->getJson(route('nepal_locations.districts', ['province_id' => $koshi->id, 'q' => 'मोरङ']))
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Morang']);

        // Cascading local level filter by district
        $this->actingAs($user)
            ->getJson(route('nepal_locations.local_levels', ['district_id' => $morang->id, 'q' => 'विराटनगर']))
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Biratnagar Metropolitan City']);

        // Ward resolution
        $this->actingAs($user)
            ->getJson(route('nepal_locations.wards', ['local_level_id' => $biratnagar->id]))
            ->assertStatus(200)
            ->assertJsonFragment(['name_ne' => 'वडा नं. १']);
    }

    public function test_backend_hierarchy_validation_rejects_mismatched_relationships()
    {
        $this->seed(NepalGeographySeeder::class);
        $service = app(NepalLocationService::class);

        $koshi = NepalProvince::where('code', 'P1')->firstOrFail();
        $bagmati = NepalProvince::where('code', 'P3')->firstOrFail();
        $kathmandu = NepalDistrict::where('province_id', $bagmati->id)->where('name', 'Kathmandu')->firstOrFail();
        $morang = NepalDistrict::where('province_id', $koshi->id)->where('name', 'Morang')->firstOrFail();

        $kmc = NepalLocalLevel::create([
            'district_id' => $kathmandu->id,
            'name' => 'Kathmandu Metropolitan City',
            'name_ne' => 'काठमाडौँ महानगरपालिका',
            'level_type' => 'metropolitan',
            'total_wards' => 32,
        ]);

        // Valid hierarchy returns null
        $this->assertNull($service->validateHierarchy($bagmati->id, $kathmandu->id, $kmc->id, 10));

        // Mismatched province-district
        $this->assertNotNull($service->validateHierarchy($koshi->id, $kathmandu->id, $kmc->id, 10));

        // Mismatched district-local level
        $this->assertNotNull($service->validateHierarchy($bagmati->id, $morang->id, $kmc->id, 10));

        // Out-of-range ward
        $this->assertNotNull($service->validateHierarchy($bagmati->id, $kathmandu->id, $kmc->id, 99));
    }

    public function test_import_command_rejects_incomplete_dataset_under_strict_official_counts()
    {
        $tmpPath = storage_path('app/test_incomplete_nepal_locations.json');

        try {
            File::put($tmpPath, json_encode([
                'provinces' => [
                    [
                        'code' => 'P1',
                        'name' => 'Koshi Province',
                        'name_ne' => 'कोशी प्रदेश',
                        'districts' => [
                            [
                                'name' => 'Morang',
                                'name_ne' => 'मोरङ',
                                'local_levels' => [
                                    [
                                        'name' => 'Biratnagar Metropolitan City',
                                        'name_ne' => 'विराटनगर महानगरपालिका',
                                        'level_type' => 'metropolitan',
                                        'total_wards' => 19,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]));

            // Strict official count mode MUST reject incomplete dataset
            $this->artisan('nepal:import-locations', [
                'file' => $tmpPath,
                '--strict-official-counts' => true,
            ])->assertExitCode(1);

            // Non-strict mode imports cleanly with source attribution
            $this->artisan('nepal:import-locations', [
                'file' => $tmpPath,
                '--source' => 'MoFAGA Verified Test Slice',
            ])->assertExitCode(0);

            $report = app(NepalLocationService::class)->getDatasetCompletenessReport();
            $this->assertFalse($report['is_complete_official_dataset']);
        } finally {
            File::delete($tmpPath);
        }
    }
}

