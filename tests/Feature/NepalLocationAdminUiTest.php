<?php

namespace Tests\Feature;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\User;
use Tests\TestCase;

class NepalLocationAdminUiTest extends TestCase
{
    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    /**
     * Test admin can view Nepal locations management page with executive header, 4 KPI cards and directory tabs.
     */
    public function test_nepal_locations_manage_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('nepal_locations.manage'));

        $response->assertStatus(200);
        $response->assertSee('Nepal Administrative Locations &amp; Governance', false);
        $response->assertSee('Provinces (प्रदेश)', false);
        $response->assertSee('Districts (जिल्ला)', false);
        $response->assertSee('Local Governments (तह)', false);
        $response->assertSee('Total Wards (वडा तह)', false);
        $response->assertSee('Local Governments (स्थानीय तह)', false);
        $response->assertSee('Federal Structure Overview', false);
        $response->assertSee('editLocationModal');
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('nepal_locations.manage'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot access location management.
     */
    public function test_student_cannot_access_manage_page()
    {
        $student = $this->getStudent();
        $response = $this->actingAs($student)->get(route('nepal_locations.manage'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test super admin can toggle location status.
     */
    public function test_super_admin_can_toggle_location_status()
    {
        $admin = $this->getSuperAdmin();
        $local = NepalLocalLevel::first();

        $this->assertNotNull($local);
        $oldStatus = (bool) $local->is_active;

        $response = $this->actingAs($admin)->post(route('nepal_locations.toggle', [
            'type' => 'local_level',
            'id' => $local->id,
        ]));

        $response->assertSessionHas('flash_success');

        $local->refresh();
        $this->assertEquals(!$oldStatus, (bool) $local->is_active);

        // Revert back
        $this->actingAs($admin)->post(route('nepal_locations.toggle', [
            'type' => 'local_level',
            'id' => $local->id,
        ]));
    }

    /**
     * Test super admin can update location details.
     */
    public function test_super_admin_can_update_location_details()
    {
        $admin = $this->getSuperAdmin();
        $local = NepalLocalLevel::first();

        $this->assertNotNull($local);
        $originalName = $local->name;
        $originalNameNe = $local->name_ne;

        $response = $this->actingAs($admin)->put(route('nepal_locations.update', [
            'type' => 'local_level',
            'id' => $local->id,
        ]), [
            'name' => $originalName . ' Verified',
            'name_ne' => $originalNameNe,
            'code' => $local->code,
            'total_wards' => $local->total_wards,
        ]);

        $response->assertSessionHas('flash_success');

        $local->refresh();
        $this->assertEquals($originalName . ' Verified', $local->name);

        // Revert back
        $local->update(['name' => $originalName]);
    }
}
