<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\User;
use Tests\TestCase;

class EventManagementUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Event::where('title', 'like', '%Test UI Event%')->delete();
        parent::tearDown();
    }

    protected function getAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    /**
     * Test admin can load events index with executive header, 4 KPI cards and calendar.
     */
    public function test_events_index_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getAdmin();

        $activeYear = AcademicYear::where('is_active', true)->first();

        // Create a test published event and holiday
        $event = Event::create([
            'title' => 'Test UI Event Sports Day',
            'title_ne' => 'वार्षिक खेलकुद',
            'category' => 'sports_extracurricular',
            'start_date_bs' => '2083-05-10',
            'end_date_bs' => '2083-05-12',
            'start_date' => '2026-08-26',
            'end_date' => '2026-08-28',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $holiday = Event::create([
            'title' => 'Test UI Event Dashain Holiday',
            'title_ne' => 'दशैं बिदा',
            'category' => 'holiday',
            'start_date_bs' => '2083-07-01',
            'end_date_bs' => '2083-07-15',
            'start_date' => '2026-10-17',
            'end_date' => '2026-10-31',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => true,
            'is_closure' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('School Events &amp; Calendar (BS)', false);
        $response->assertSee('Total Events');
        $response->assertSee('Academic Holidays');
        $response->assertSee('Upcoming Events');
        $response->assertSee('Published Notices');
        $response->assertSee('events-bs-calendar');
        $response->assertSee('Bikram Sambat Calendar');
        $response->assertSee('All Events List');
        $response->assertSee('Quick Add Event');
        $response->assertSee('Test UI Event Sports Day');
        $response->assertSee('Test UI Event Dashain Holiday');
    }

    /**
     * Test guest user is redirected to login.
     */
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('events.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot see admin actions and unpublished draft events.
     */
    public function test_student_sees_published_events_without_admin_controls()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudent();

        $activeYear = AcademicYear::where('is_active', true)->first();

        // Draft event
        $draftEvent = Event::create([
            'title' => 'Test UI Event Draft Internal',
            'category' => 'meeting_administrative',
            'start_date_bs' => '2083-06-01',
            'end_date_bs' => '2083-06-01',
            'start_date' => '2026-09-17',
            'end_date' => '2026-09-17',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => false,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // Published event
        $publishedEvent = Event::create([
            'title' => 'Test UI Event Public Gala',
            'category' => 'cultural_celebration',
            'start_date_bs' => '2083-06-15',
            'end_date_bs' => '2083-06-15',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($student)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('Test UI Event Public Gala');
        $response->assertDontSee('Test UI Event Draft Internal');
        $response->assertDontSee('Quick Add Event');
        $response->assertDontSee(route('events.create'));
    }

    /**
     * Test filtering events by category.
     */
    public function test_events_filtering_by_category()
    {
        $admin = $this->getAdmin();
        $activeYear = AcademicYear::where('is_active', true)->first();

        Event::create([
            'title' => 'Test UI Event Exam Period',
            'category' => 'examination',
            'start_date_bs' => '2083-04-01',
            'end_date_bs' => '2083-04-10',
            'start_date' => '2026-07-16',
            'end_date' => '2026-07-25',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        Event::create([
            'title' => 'Test UI Event Cultural Dance',
            'category' => 'cultural_celebration',
            'start_date_bs' => '2083-04-15',
            'end_date_bs' => '2083-04-15',
            'start_date' => '2026-07-30',
            'end_date' => '2026-07-30',
            'session' => $activeYear ? $activeYear->code : '2083-84',
            'academic_year_id' => $activeYear ? $activeYear->id : null,
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('events.index', ['category' => 'examination']));

        $response->assertStatus(200);
        $response->assertSee('Test UI Event Exam Period');
        $response->assertDontSee('Test UI Event Cultural Dance');
    }
}
