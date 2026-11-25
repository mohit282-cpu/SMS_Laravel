<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\User;
use Tests\TestCase;

class AttendanceSuiteUiTest extends TestCase
{
    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    /**
     * Test 1: Student Daily Attendance page loads with executive header, KPIs (when class selected) and PWA sync widget.
     */
    public function test_student_daily_attendance_page_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();
        $class = MyClass::first();

        $response = $this->actingAs($admin)->get(route('attendance.index', [
            'my_class_id' => $class->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('Student Daily Attendance (Bikram Sambat)'));
        $response->assertSee('pwa-attendance-offline-widget');
        $response->assertSee(__('Total Students'));
        $response->assertSee(__('Present'));
        $response->assertSee(__('Absent'));
        $response->assertSee(__('Late / Leave / Half'));
    }

    /**
     * Test 2: Monthly Class Attendance Sheet loads with executive header and 4 KPI cards.
     */
    public function test_monthly_class_attendance_sheet_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();
        $class = MyClass::first();

        $response = $this->actingAs($admin)->get(route('attendance.monthly', [
            'my_class_id' => $class->id,
            'year_bs' => 2083,
            'month_bs' => 7,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('Monthly Class Attendance Sheet (BS)'));
        $response->assertSee(__('Enrolled Students'));
        $response->assertSee(__('Days in Month'));
        $response->assertSee(__('Total Present Days'));
        $response->assertSee(__('Class Average'));
        $response->assertSee('Kartik');
        $response->assertSee(__('Attendance Sheet'));
    }

    /**
     * Test 3: Class-wise Attendance Comparison Report loads with executive header and 4 KPI cards.
     */
    public function test_class_wise_attendance_report_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('attendance.class_report'));

        $response->assertStatus(200);
        $response->assertSee(__('Class-wise Attendance Comparison Report'));
        $response->assertSee(__('Total Enrolled'));
        $response->assertSee(__('Recorded Today'));
        $response->assertSee(__('Present Students'));
        $response->assertSee(__('School Turnout'));
        $response->assertSee(__('Attendance Overview by Class'));
    }

    /**
     * Test 4: Daily Staff Attendance loads with executive header and 4 KPI cards.
     */
    public function test_daily_staff_attendance_page_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('staff_attendance.index'));

        $response->assertStatus(200);
        $response->assertSee(__('Daily Staff Attendance (Bikram Sambat)'));
        $response->assertSee(__('Total Staff'));
        $response->assertSee(__('Present'));
        $response->assertSee(__('Absent'));
        $response->assertSee(__('Late / Leave / Half'));
        $response->assertSee(__('Staff Members List'));
    }

    /**
     * Test 5: Monthly Staff Attendance Summary loads with executive header and 4 KPI cards.
     */
    public function test_monthly_staff_attendance_summary_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('staff_attendance.monthly', [
            'year_bs' => 2083,
            'month_bs' => 7,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('Monthly Staff Attendance Summary (BS)'));
        $response->assertSee(__('Total Staff'));
        $response->assertSee(__('Days in Month'));
        $response->assertSee(__('Total Present Days'));
        $response->assertSee(__('Average Turnout'));
        $response->assertSee(__('Staff Attendance Sheet'));
    }

    /**
     * Test 6: Guest is redirected to login on all attendance routes.
     */
    public function test_guest_is_redirected_from_all_attendance_pages()
    {
        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->get(route('attendance.monthly'))->assertRedirect(route('login'));
        $this->get(route('attendance.class_report'))->assertRedirect(route('login'));
        $this->get(route('staff_attendance.index'))->assertRedirect(route('login'));
        $this->get(route('staff_attendance.monthly'))->assertRedirect(route('login'));
    }
}
