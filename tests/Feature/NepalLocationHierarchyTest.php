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

    public function test_nepal_geography_seeder_populates_exact_official_7_77_753_counts()
    {
        $this->seed(NepalGeographySeeder::class);

        $report = app(NepalLocationService::class)->getDatasetCompletenessReport();

        $this->assertEquals(7, $report['provinces']['actual']);
        $this->assertEquals(77, $report['districts']['actual']);
        $this->assertEquals(753, $report['local_levels']['actual']);
        $this->assertEquals(6743, $report['total_wards']['actual']);
        $this->assertEquals(6, $report['by_type']['metropolitan']['actual']);
        $this->assertEquals(11, $report['by_type']['sub_metropolitan']['actual']);
        $this->assertEquals(276, $report['by_type']['municipality']['actual']);
        $this->assertEquals(460, $report['by_type']['rural_municipality']['actual']);
        $this->assertTrue($report['is_complete_official_dataset']);

        // Verify unique codes and unique (district_id, name) across all 753 local governments
        $allLocals = NepalLocalLevel::all();
        $this->assertEquals(753, $allLocals->pluck('code')->filter()->unique()->count());
        $this->assertEquals(753, $allLocals->map(fn ($l) => $l->district_id . '|' . $l->name)->unique()->count());

        // Independent official reference ward-count fixture checks
        $expectedWards = [
            'Kathmandu Metropolitan City' => 32,
            'Biratnagar Metropolitan City' => 19,
            'Pokhara Metropolitan City' => 33,
            'Lalitpur Metropolitan City' => 29,
            'Bharatpur Metropolitan City' => 29,
            'Birgunj Metropolitan City' => 32,
            'Dharan Sub-Metropolitan City' => 20,
            'Itahari Sub-Metropolitan City' => 20,
            'Budhanilakantha Municipality' => 13,
        ];
        foreach ($expectedWards as $name => $wards) {
            $local = NepalLocalLevel::where('name', $name)->firstOrFail();
            $this->assertEquals($wards, $local->total_wards, "Ward count mismatch for {$name}");
        }
    }

    public function test_importer_dry_run_makes_no_changes_and_rejects_fake_ward_counts()
    {
        $this->artisan('nepal:import-locations', [
            'file' => database_path('data/nepal_locations.json'),
            '--dry-run' => true,
            '--strict-official-counts' => true,
        ])->assertExitCode(0);

        $this->assertEquals(0, NepalProvince::count());

        $badPath = storage_path('app/test_bad_wards.json');
        try {
            File::put($badPath, json_encode([
                'provinces' => [[
                    'code' => 'P1',
                    'name' => 'Koshi Province',
                    'name_ne' => 'कोशी प्रदेश',
                    'districts' => [[
                        'name' => 'Morang',
                        'name_ne' => 'मोरङ',
                        'local_levels' => [[
                            'name' => 'Fake Palika',
                            'name_ne' => 'नक्कली पालिका',
                            'level_type' => 'municipality',
                            'total_wards' => 1, // Invalid < 5
                        ]],
                    ]],
                ]],
            ]));

            $this->artisan('nepal:import-locations', [
                'file' => $badPath,
            ])->assertExitCode(1);
        } finally {
            File::delete($badPath);
        }
    }

    public function test_inactive_locations_cannot_be_selected_and_ward_endpoint_validates_id()
    {
        $this->seed(NepalGeographySeeder::class);
        $user = $this->makeAuthUser();
        $service = app(NepalLocationService::class);

        $bagmati = NepalProvince::where('code', 'P3')->firstOrFail();
        $kathmandu = NepalDistrict::where('province_id', $bagmati->id)->where('name', 'Kathmandu')->firstOrFail();
        $kmc = NepalLocalLevel::where('district_id', $kathmandu->id)
            ->where('name', 'Kathmandu Metropolitan City')
            ->firstOrFail();

        // Ward endpoint returns exact 32 wards for Kathmandu Metropolitan City
        $res = $this->actingAs($user)->getJson(route('nepal_locations.wards', ['local_level_id' => $kmc->id]));
        $res->assertStatus(200);
        $this->assertCount(32, $res->json('data'));

        // Invalid & non-existent IDs
        $this->actingAs($user)->getJson(route('nepal_locations.wards', ['local_level_id' => 0]))->assertStatus(422);
        $this->actingAs($user)->getJson(route('nepal_locations.wards', ['local_level_id' => 999999]))->assertStatus(404);

        // Inactive local level rejected for new selection
        $kmc->is_active = false;
        $kmc->save();
        $this->assertNotNull($service->validateHierarchy($bagmati->id, $kathmandu->id, $kmc->id, 10, true));
    }

    public function test_rest_api_endpoints_return_expected_hierarchy_and_404_for_invalid_ids()
    {
        $this->seed(NepalGeographySeeder::class);

        $koshi = NepalProvince::where('code', 'P1')->firstOrFail();
        $morang = NepalDistrict::where('province_id', $koshi->id)->where('name', 'Morang')->firstOrFail();
        $biratnagar = NepalLocalLevel::where('district_id', $morang->id)
            ->where('name', 'Biratnagar Metropolitan City')
            ->firstOrFail();

        $this->getJson('/api/provinces')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Koshi Province', 'name_ne' => 'कोशी प्रदेश']);

        $this->getJson("/api/provinces/{$koshi->id}/districts")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Morang', 'name_ne' => 'मोरङ']);

        $this->getJson("/api/districts/{$morang->id}/local-governments")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Biratnagar Metropolitan City']);

        $this->getJson("/api/local-governments/{$biratnagar->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Biratnagar Metropolitan City']);

        $this->getJson('/api/provinces/999999/districts')->assertStatus(404);
        $this->getJson('/api/districts/999999/local-governments')->assertStatus(404);
        $this->getJson('/api/local-governments/999999')->assertStatus(404);
    }

    public function test_admin_location_management_directory_and_toggle_audit()
    {
        $this->seed(NepalGeographySeeder::class);
        $admin = $this->makeAuthUser();
        $local = NepalLocalLevel::firstOrFail();

        $this->actingAs($admin)
            ->get(route('nepal_locations.manage'))
            ->assertStatus(200)
            ->assertSee('Verified Complete (7/77/753)');

        $this->actingAs($admin)
            ->post(route('nepal_locations.toggle', ['type' => 'local_level', 'id' => $local->id]))
            ->assertRedirect();

        $this->assertFalse((bool) $local->fresh()->is_active);
    }

    public function test_incomplete_hierarchy_and_conflicting_legacy_fields_are_rejected_and_parent_inherits_nepal_location()
    {
        $this->seed(NepalGeographySeeder::class);
        $admin = $this->makeAuthUser();
        $service = app(NepalLocationService::class);

        $bagmati = NepalProvince::where('code', 'P3')->firstOrFail();
        $kathmandu = NepalDistrict::where('province_id', $bagmati->id)->where('name', 'Kathmandu')->firstOrFail();
        $kmc = NepalLocalLevel::where('district_id', $kathmandu->id)->where('name', 'Kathmandu Metropolitan City')->firstOrFail();

        // Incomplete combinations rejected
        $this->assertNotNull($service->validateHierarchy(null, $kathmandu->id, null, null, true));
        $this->assertNotNull($service->validateHierarchy($bagmati->id, null, $kmc->id, null, true));
        $this->assertNotNull($service->validateHierarchy($bagmati->id, $kathmandu->id, null, 1, true));

        // Non-admin role rejected on admin management page
        $studentUser = User::create([
            'name' => 'Student Role User',
            'email' => 'stu_role_' . uniqid() . '@sms.local',
            'username' => 'stu_role_' . uniqid(),
            'user_type' => 'student',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
        $this->actingAs($studentUser)->get(route('nepal_locations.manage'))->assertRedirect();
        $regularAdmin = User::create([
            'name' => 'Regular Admin User',
            'email' => 'reg_admin_' . uniqid() . '@sms.local',
            'username' => 'reg_admin_' . uniqid(),
            'user_type' => 'admin',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
        $this->actingAs($regularAdmin)
            ->post(route('nepal_locations.toggle', ['type' => 'local_level', 'id' => $kmc->id]))
            ->assertStatus(403);

        $myClass = \App\Models\MyClass::firstOrFail();
        $section = \App\Models\Section::where('my_class_id', $myClass->id)->firstOrFail();
        $nal = \App\Models\Nationality::firstOrFail();

        $unique = mt_rand(10000, 99999);
        $parentPhone = '9841' . $unique . '9';

        // Conflicting legacy + Nepal location rejected
        $this->actingAs($admin)->postJson(route('students.store'), [
            'name' => 'Conflict Student ' . $unique,
            'address' => 'Koteshwor, Kathmandu',
            'gender' => 'Male',
            'nal_id' => $nal->id,
            'state_id' => 1,
            'province_id' => $bagmati->id,
            'district_id' => $kathmandu->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year_admitted' => '2083',
        ])->assertStatus(422);

        // Valid Nepal location + auto-created parent inherits Nepal location
        $this->actingAs($admin)->postJson(route('students.store'), [
            'name' => 'Sita Gurung ' . $unique,
            'address' => 'Koteshwor, Kathmandu',
            'gender' => 'Female',
            'nal_id' => $nal->id,
            'province_id' => $bagmati->id,
            'district_id' => $kathmandu->id,
            'local_level_id' => $kmc->id,
            'ward_no' => 32,
            'tole' => 'Koteshwor Chowk',
            'street' => 'Ring Road',
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year_admitted' => '2083',
            'parent_name' => 'Hari Gurung ' . $unique,
            'parent_phone' => $parentPhone,
            'parent_relationship' => 'Father',
        ])->assertStatus(200);

        $parent = User::where('name', 'Hari Gurung ' . $unique)->firstOrFail();
        $this->assertEquals($bagmati->id, $parent->province_id);
        $this->assertEquals($kathmandu->id, $parent->district_id);
        $this->assertEquals($kmc->id, $parent->local_level_id);
        $this->assertEquals(32, $parent->ward_no);
        $this->assertEquals('Koteshwor Chowk', $parent->tole);
    }
}

