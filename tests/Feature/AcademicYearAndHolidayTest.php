<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Holiday;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearAndHolidayTest extends TestCase
{
    protected function getAdminUser()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    public function test_admin_can_view_academic_years_page()
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('academic_years.index'));
        $response->assertStatus(200);
        $response->assertSee('Academic');
    }

    public function test_admin_can_create_nepali_academic_year()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => '2083/84 BS',
            'year_bs' => 2083,
            'start_date_bs' => '2083-01-01',
            'end_date_bs' => '2083-12-30',
            'is_active' => '1',
            'description' => 'Nepali Academic Session 2083/84',
        ]);

        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('academic_years', [
            'name' => '2083/84 BS',
            'year_bs' => 2083,
            'start_date_bs' => '2083-01-01',
            'is_active' => 1,
        ]);

        // Verify start_date stored as valid Gregorian AD date (2083-01-01 BS = 2026-04-14 AD)
        $ay = AcademicYear::where('name', '2083/84 BS')->first();
        $this->assertNotNull($ay);
        $this->assertEquals('2026-04-14', $ay->start_date->format('Y-m-d'));
    }

    public function test_academic_year_validation_rejects_invalid_date_order()
    {
        $admin = $this->getAdminUser();

        // End date earlier than start date
        $response = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => 'Invalid Session',
            'year_bs' => 2083,
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-01-01',
        ]);

        $response->assertSessionHas('flash_danger');
    }

    public function test_admin_can_create_school_holiday()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->post(route('holidays.store'), [
            'title' => 'Dashain Festival',
            'title_ne' => 'दशैं पर्व',
            'type' => 'public_holiday',
            'start_date_bs' => '2083-07-01',
            'end_date_bs' => '2083-07-15',
            'is_closure' => '1',
            'description' => 'Ghatasthapana to Kojagrat Purnima',
        ]);

        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('holidays', [
            'title' => 'Dashain Festival',
            'type' => 'public_holiday',
            'is_closure' => 1,
        ]);
    }

    public function test_calendar_events_json_endpoint()
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('calendar.events'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'title', 'start', 'end', 'color']
        ]);
    }

    public function test_admin_can_edit_and_update_academic_year()
    {
        $admin = $this->getAdminUser();
        $ay = AcademicYear::where('is_closed', false)->first() ?? AcademicYear::first();
        if ($ay && $ay->is_closed) {
            $ay->update(['is_closed' => false]);
        }

        // View edit page
        $response = $this->actingAs($admin)->get(route('academic_years.edit', $ay->id));
        $response->assertStatus(200);
        $response->assertSee($ay->name);

        // Update academic year
        $response = $this->actingAs($admin)->put(route('academic_years.update', $ay->id), [
            'name' => '2083/84 BS Updated',
            'year_bs' => 2083,
            'start_date_bs' => '2083-01-01',
            'end_date_bs' => '2083-12-30',
            'is_active' => '1',
            'description' => 'Updated session notes',
        ]);

        $response->assertSessionHas('flash_success');

        $ay->refresh();
        $this->assertEquals('2083/84 BS Updated', $ay->name);
        $this->assertEquals('Updated session notes', $ay->description);
    }

    public function test_admin_can_activate_and_close_academic_year()
    {
        $admin = $this->getAdminUser();
        $ay = AcademicYear::where('is_closed', false)->first() ?? AcademicYear::first();
        if ($ay && $ay->is_closed) {
            $ay->update(['is_closed' => false]);
        }

        // Activate
        $response = $this->actingAs($admin)->put(route('academic_years.activate', $ay->id));
        $response->assertSessionHas('flash_success');
        $ay->refresh();
        $this->assertTrue((bool)$ay->is_active);

        // Close / archive
        $response = $this->actingAs($admin)->put(route('academic_years.close', $ay->id));
        $response->assertSessionHas('flash_success');
        $ay->refresh();
        $this->assertTrue((bool)$ay->is_closed);
        $this->assertFalse((bool)$ay->is_active);

        // Reopen
        $response = $this->actingAs($admin)->put(route('academic_years.reopen', $ay->id));
        $response->assertSessionHas('flash_success');
        $ay->refresh();
        $this->assertFalse((bool)$ay->is_closed);
    }
}
