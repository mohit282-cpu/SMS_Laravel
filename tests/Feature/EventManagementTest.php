<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    protected function tearDown(): void
    {
        Event::where('title', 'like', '%Test Event%')->delete();
        parent::tearDown();
    }

    protected function getAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getTeacher()
    {
        return User::where('user_type', 'teacher')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    protected function getParent()
    {
        return User::where('user_type', 'parent')->first();
    }

    /**
     * Test authorized admin can create a school event with BS dates.
     */
    public function test_admin_can_create_event()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Annual Sports Meet',
            'title_ne' => 'वार्षिक खेलकुद',
            'category' => 'sports_extracurricular',
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-12',
            'is_all_day' => '1',
            'is_published' => '1',
            'target_audience' => 'all',
            'location' => 'Main Sports Ground',
            'description' => 'School-wide sports event.',
        ]);

        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('events', [
            'title' => 'Test Event Annual Sports Meet',
            'category' => 'sports_extracurricular',
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-12',
            'is_published' => 1,
            'target_audience' => 'all',
        ]);

        $event = Event::where('title', 'Test Event Annual Sports Meet')->first();
        $this->assertNotNull($event->start_date);
        $this->assertNotNull($event->end_date);
    }

    /**
     * Test unauthorized users (students, teachers) cannot create or modify events.
     */
    public function test_unauthorized_users_cannot_create_or_modify_events()
    {
        $student = $this->getStudent();
        $teacher = $this->getTeacher();

        // Student cannot create event (redirected by teamSA middleware)
        $resStudent = $this->actingAs($student)->post(route('events.store'), [
            'title' => 'Test Event Student Hack',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'target_audience' => 'all',
        ]);
        $resStudent->assertRedirect(route('login'));

        // Teacher cannot create school-wide event (teamSA protected)
        $resTeacher = $this->actingAs($teacher)->post(route('events.store'), [
            'title' => 'Test Event Teacher Hack',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'target_audience' => 'all',
        ]);
        $resTeacher->assertRedirect(route('login'));
    }

    /**
     * Test event publication and visibility rules.
     */
    public function test_draft_events_are_hidden_from_non_admins()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudent();

        // Create draft event
        $draft = Event::create([
            'title' => 'Test Event Secret Admin Meeting',
            'category' => 'staff_meeting',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_date_bs' => '2083-07-15',
            'end_date_bs' => '2083-07-15',
            'is_all_day' => true,
            'is_published' => false, // DRAFT
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // Create published event
        $pub = Event::create([
            'title' => 'Test Event Public Assembly',
            'category' => 'school_event',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'start_date_bs' => '2083-07-16',
            'end_date_bs' => '2083-07-16',
            'is_all_day' => true,
            'is_published' => true, // PUBLISHED
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // Student queries calendar feed
        $res = $this->actingAs($student)->getJson(route('calendar.events'));
        $res->assertStatus(200);

        $data = $res->json();
        $titles = array_column($data, 'title');

        $this->assertContains('Test Event Public Assembly', $titles);
        $this->assertNotContains('Test Event Secret Admin Meeting', $titles);

        // Admin queries calendar feed - sees both
        $resAdmin = $this->actingAs($admin)->getJson(route('calendar.events'));
        $adminData = $resAdmin->json();
        $adminTitles = array_column($adminData, 'title');

        $this->assertContains('Test Event Public Assembly', $adminTitles);
        $this->assertContains('Test Event Secret Admin Meeting', $adminTitles);
    }

    /**
     * Test class-specific targeting prevents leaking to unrelated students.
     */
    public function test_class_targeting_visibility()
    {
        $admin = $this->getAdmin();
        $classes = MyClass::all();
        $this->assertGreaterThanOrEqual(2, $classes->count());

        $classA = $classes[0];
        $classB = $classes[1];

        // Find student in Class A
        $srA = StudentRecord::where('my_class_id', $classA->id)->first();
        if (!$srA) {
            $this->markTestSkipped('Need student in class A');
        }
        $studentA = $srA->user;

        // Create event targeted ONLY to Class B
        $eventForClassB = Event::create([
            'title' => 'Test Event Only Class B Special Workshop',
            'category' => 'school_event',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'start_date_bs' => '2083-07-24',
            'end_date_bs' => '2083-07-24',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'classes',
            'my_class_id' => $classB->id,
            'created_by' => $admin->id,
        ]);

        // Student A queries calendar feed - MUST NOT see Class B event
        $res = $this->actingAs($studentA)->getJson(route('calendar.events'));
        $data = $res->json();
        $titles = array_column($data, 'title');

        $this->assertNotContains('Test Event Only Class B Special Workshop', $titles);
    }

    /**
     * Test invalid start/end dates and times validation.
     */
    public function test_rejects_invalid_date_and_time_ranges()
    {
        $admin = $this->getAdmin();

        // 1. Invalid BS date format
        $res1 = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Bad Date',
            'category' => 'school_event',
            'start_date_bs' => '2083/99/99', // invalid regex
            'target_audience' => 'all',
        ]);
        $res1->assertSessionHasErrors(['start_date_bs']);

        // 2. End date earlier than start date
        $res2 = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Backwards Date',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-05',
            'target_audience' => 'all',
        ]);
        $res2->assertSessionHas('flash_danger');

        // 3. Same day but end time earlier than start time
        $res3 = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Backwards Time',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-10',
            'start_time' => '14:00',
            'end_time' => '10:00',
            'target_audience' => 'all',
        ]);
        $res3->assertSessionHas('flash_danger');
    }

    /**
     * Test safe deletion guard: cannot delete event directly if linked to an exam.
     */
    public function test_cannot_delete_event_linked_to_exam()
    {
        $admin = $this->getAdmin();

        $event = Event::create([
            'title' => 'Test Event Linked to Exam',
            'category' => 'examination',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-15',
            'start_date_bs' => '2083-07-29',
            'end_date_bs' => '2083-07-29',
            'is_all_day' => true,
            'is_published' => true,
            'exam_id' => 9999, // Linked to exam
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('events.destroy', $event->id));
        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseHas('events', ['id' => $event->id]);

        $event->forceDelete();
    }
}
