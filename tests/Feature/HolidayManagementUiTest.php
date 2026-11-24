<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\User;
use Tests\TestCase;

class HolidayManagementUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Holiday::where('title', 'like', '%Test Holiday%')->delete();
        parent::tearDown();
    }

    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    /**
     * Test admin can load holiday index page with executive header, 4 KPI cards and dual calendars.
     */
    public function test_holidays_index_loads_with_executive_header_kpis_and_calendars()
    {
        $admin = $this->getSuperAdmin();

        Holiday::create([
            'title' => 'Test Holiday Buddha Jayanti',
            'title_ne' => 'बुद्ध जयन्ती',
            'type' => 'public_holiday',
            'start_date_bs' => '2083-02-15',
            'end_date_bs' => '2083-02-15',
            'start_date' => '2026-05-29',
            'end_date' => '2026-05-29',
            'is_closure' => true,
            'description' => 'Celebration of Buddha Jayanti',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('holidays.index'));

        $response->assertStatus(200);
        $response->assertSee('School Holiday &amp; Calendar Management (BS &amp; AD)', false);
        $response->assertSee('Total Holidays');
        $response->assertSee('School Closures');
        $response->assertSee('Public Holidays');
        $response->assertSee('Upcoming Holidays');
        $response->assertSee('holiday-bs-calendar');
        $response->assertSee('school-holiday-calendar');
        $response->assertSee('Bikram Sambat Calendar (BS)');
        $response->assertSee('Gregorian Calendar (AD)');
        $response->assertSee('All Holidays &amp; Events', false);
        $response->assertSee('Test Holiday Buddha Jayanti');
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('holidays.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student is rejected by teamSA middleware.
     */
    public function test_student_cannot_access_holiday_management()
    {
        $student = $this->getStudent();
        $response = $this->actingAs($student)->get(route('holidays.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can create a holiday with BS dates.
     */
    public function test_admin_can_create_holiday_with_bs_dates()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->post(route('holidays.store'), [
            'title' => 'Test Holiday Winter Vacation',
            'title_ne' => 'जाडो बिदा',
            'type' => 'school_holiday',
            'start_date_bs' => '2083-09-15',
            'end_date_bs' => '2083-09-25',
            'is_closure' => '1',
            'description' => 'Winter break for all students',
        ]);

        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('holidays', [
            'title' => 'Test Holiday Winter Vacation',
            'type' => 'school_holiday',
            'start_date_bs' => '2083-09-15',
            'end_date_bs' => '2083-09-25',
            'is_closure' => 1,
        ]);
    }

    /**
     * Test super admin can delete a holiday.
     */
    public function test_super_admin_can_delete_holiday()
    {
        $admin = $this->getSuperAdmin();

        $holiday = Holiday::create([
            'title' => 'Test Holiday To Delete',
            'type' => 'custom_working_day',
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-05-01',
            'start_date' => '2026-08-17',
            'end_date' => '2026-08-17',
            'is_closure' => false,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('holidays.destroy', $holiday->id));
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    /**
     * Test calendar.events JSON feed includes holiday items.
     */
    public function test_calendar_events_json_feed_includes_holidays()
    {
        $admin = $this->getSuperAdmin();

        $holiday = Holiday::create([
            'title' => 'Test Holiday Maghe Sankranti',
            'title_ne' => 'माघे संक्रान्ति',
            'type' => 'public_holiday',
            'start_date_bs' => '2083-10-01',
            'end_date_bs' => '2083-10-01',
            'start_date' => '2027-01-15',
            'end_date' => '2027-01-15',
            'is_closure' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('calendar.events'));
        $response->assertStatus(200);

        $json = $response->json();
        $holidayIds = array_column($json, 'id');
        $this->assertContains('holiday_' . $holiday->id, $holidayIds);
    }
}
