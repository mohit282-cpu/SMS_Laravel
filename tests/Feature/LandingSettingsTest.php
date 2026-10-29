<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LandingSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first();
    }

    public function test_super_admin_can_access_landing_settings_dashboard()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('landing_settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Landing Page & Admissions');
    }

    public function test_super_admin_can_access_inquiries_page()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('landing_settings.inquiries'));

        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_entrance_results_management()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('landing_settings.entrance_results'));

        $response->assertStatus(200);
    }

    public function test_non_super_admin_cannot_access_landing_settings()
    {
        $student = $this->getStudent();

        $response = $this->actingAs($student)->get(route('landing_settings.index'));

        // SuperAdmin middleware aborts or redirects non-super-admins
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));
    }
}
