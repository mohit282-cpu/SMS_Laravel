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
     * Test calendar and event management pages load correctly against the actual schema (year_bs, not year_start_bs).
     */
    public function test_event_calendar_pages_load_against_actual_schema()
    {
        $admin = $this->getAdmin();

        $resIndex = $this->actingAs($admin)->get(route('events.index'));
        $resIndex->assertStatus(200);

        $resCreate = $this->actingAs($admin)->get(route('events.create'));
        $resCreate->assertStatus(200);

        $event = Event::first();
        if ($event) {
            $resEdit = $this->actingAs($admin)->get(route('events.edit', $event->id));
            $resEdit->assertStatus(200);
        }
    }

    /**
     * Test date range overlap filtering includes overlapping multi-day and boundary events.
     */
    public function test_event_date_range_overlap_filtering()
    {
        $admin = $this->getAdmin();
        $bsService = app(\App\Services\BikramSambatService::class);

        // Requested filter interval: 2083-08-10 BS to 2083-08-20 BS
        // E1: Starts before, ends inside (2083-08-01 to 2083-08-12) -> Overlaps
        $conv1S = $bsService->bsToAd('2083-08-01');
        $conv1E = $bsService->bsToAd('2083-08-12');
        $e1 = Event::create([
            'title' => 'Test Event E1 Starts Before Ends Inside ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv1S['ad_date'],
            'end_date' => $conv1E['ad_date'],
            'start_date_bs' => '2083-08-01',
            'end_date_bs' => '2083-08-12',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // E2: Starts inside, ends after (2083-08-18 to 2083-08-25) -> Overlaps
        $conv2S = $bsService->bsToAd('2083-08-18');
        $conv2E = $bsService->bsToAd('2083-08-25');
        $e2 = Event::create([
            'title' => 'Test Event E2 Starts Inside Ends After ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv2S['ad_date'],
            'end_date' => $conv2E['ad_date'],
            'start_date_bs' => '2083-08-18',
            'end_date_bs' => '2083-08-25',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // E3: Fully contains requested range (2083-08-05 to 2083-08-28) -> Overlaps
        $conv3S = $bsService->bsToAd('2083-08-05');
        $conv3E = $bsService->bsToAd('2083-08-28');
        $e3 = Event::create([
            'title' => 'Test Event E3 Fully Contains ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv3S['ad_date'],
            'end_date' => $conv3E['ad_date'],
            'start_date_bs' => '2083-08-05',
            'end_date_bs' => '2083-08-28',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // E4: Boundary single-day (2083-08-10 to 2083-08-10) -> Overlaps
        $conv4 = $bsService->bsToAd('2083-08-10');
        $e4 = Event::create([
            'title' => 'Test Event E4 Boundary Single Day ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv4['ad_date'],
            'end_date' => $conv4['ad_date'],
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-10',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // E5: Outside before (2083-08-01 to 2083-08-08) -> Outside
        $conv5S = $bsService->bsToAd('2083-08-01');
        $conv5E = $bsService->bsToAd('2083-08-08');
        $e5 = Event::create([
            'title' => 'Test Event E5 Outside Before ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv5S['ad_date'],
            'end_date' => $conv5E['ad_date'],
            'start_date_bs' => '2083-08-01',
            'end_date_bs' => '2083-08-08',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // E6: Outside after (2083-08-22 to 2083-08-28) -> Outside
        $conv6S = $bsService->bsToAd('2083-08-22');
        $conv6E = $bsService->bsToAd('2083-08-28');
        $e6 = Event::create([
            'title' => 'Test Event E6 Outside After ' . uniqid(),
            'category' => 'school_event',
            'start_date' => $conv6S['ad_date'],
            'end_date' => $conv6E['ad_date'],
            'start_date_bs' => '2083-08-22',
            'end_date_bs' => '2083-08-28',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        // Filter for range 2083-08-10 to 2083-08-20
        $response = $this->actingAs($admin)->get(route('events.index', [
            'date_from_bs' => '2083-08-10',
            'date_to_bs' => '2083-08-20',
        ]));
        $response->assertStatus(200);

        $response->assertSee($e1->title);
        $response->assertSee($e2->title);
        $response->assertSee($e3->title);
        $response->assertSee($e4->title);
        $response->assertDontSee($e5->title);
        $response->assertDontSee($e6->title);
    }

    /**
     * Test invalid or reversed BS filter date ranges return useful validation feedback.
     */
    public function test_event_date_range_filter_rejects_reversed_or_invalid_dates()
    {
        $admin = $this->getAdmin();

        // 1. Reversed range (to < from)
        $resReversed = $this->actingAs($admin)->get(route('events.index', [
            'date_from_bs' => '2083-08-20',
            'date_to_bs' => '2083-08-10',
        ]));
        $resReversed->assertSessionHas('flash_danger');

        // 2. Invalid BS date in filter
        $resInvalid = $this->actingAs($admin)->get(route('events.index', [
            'date_from_bs' => '2083-14-99',
        ]));
        $resInvalid->assertSessionHas('flash_danger');
    }

    /**
     * Test class and section relationship validation.
     */
    public function test_event_class_and_section_validation()
    {
        $admin = $this->getAdmin();
        $classes = MyClass::all();
        $this->assertGreaterThanOrEqual(2, $classes->count());

        $classA = $classes[0];
        $classB = $classes[1];

        // Find section belonging to Class B
        $secB = Section::where('my_class_id', $classB->id)->first();
        if (!$secB) {
            $secB = Section::create(['name' => 'Sec B Test', 'my_class_id' => $classB->id, 'active' => 1]);
        }

        // Attempt to create event with Class A and Section B (mismatched)
        $resMismatch = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Mismatched Section',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'target_audience' => 'classes',
            'my_class_id' => $classA->id,
            'section_id' => $secB->id, // Belongs to Class B!
        ]);
        $resMismatch->assertSessionHas('flash_danger');

        // When target audience is NOT classes (e.g. 'all'), class/section IDs must be cleared to null
        $resAll = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Test Event Non-Class Audience Clears Class',
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'target_audience' => 'all',
            'my_class_id' => $classA->id,
            'section_id' => $secB->id,
        ]);
        $resAll->assertSessionHas('flash_success');

        $createdEvent = Event::where('title', 'Test Event Non-Class Audience Clears Class')->first();
        $this->assertNotNull($createdEvent);
        $this->assertNull($createdEvent->my_class_id);
        $this->assertNull($createdEvent->section_id);
    }

    /**
     * Test stored XSS strings in event title, description, and location are rendered safely.
     */
    public function test_xss_payload_in_event_content_is_stored_safely_and_escaped()
    {
        $admin = $this->getAdmin();
        $xssTitle = 'Test Event XSS <script>alert("pwned")</script><img src=x onerror=alert(1)>';
        $xssDesc = '<b onmouseover="alert(\'hover\')">Bold Danger</b><iframe src="javascript:alert(2)"></iframe>';

        $response = $this->actingAs($admin)->post(route('events.store'), [
            'title' => $xssTitle,
            'description' => $xssDesc,
            'category' => 'school_event',
            'start_date_bs' => '2083-08-10',
            'target_audience' => 'all',
            'location' => '<script>alert("loc")</script>',
        ]);
        $response->assertSessionHas('flash_success');

        $event = Event::where('title', $xssTitle)->first();
        $this->assertNotNull($event);

        // Web view: Blade escaping must escape raw HTML tags
        $viewRes = $this->actingAs($admin)->get(route('events.show', $event->id));
        $viewRes->assertStatus(200);
        $viewRes->assertSee(e($xssTitle), false);
        $viewRes->assertDontSee('<script>alert("pwned")</script>', false);

        // JSON calendar feed: Returns string in JSON (which nepali_bs_calendar.js safely binds with textContent)
        $feedRes = $this->actingAs($admin)->getJson(route('calendar.events'));
        $feedRes->assertStatus(200);
        $feedData = $feedRes->json();
        $matching = array_filter($feedData, fn($e) => isset($e['title']) && $e['title'] === $xssTitle);
        $this->assertNotEmpty($matching);
    }

    /**
     * Test centralized notification recipient resolution adheres to audience scoping.
     */
    public function test_event_push_notification_recipient_scoping()
    {
        $admin = $this->getAdmin();
        $teacher = $this->getTeacher();
        $student = $this->getStudent();
        $parent = $this->getParent();
        $notificationService = app(\App\Services\EventNotificationService::class);

        // 1. Staff-only event: only teachers and admins, no students or parents
        $staffEvent = Event::create([
            'title' => 'Test Event Teachers Only Meeting ' . uniqid(),
            'category' => 'staff_meeting',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-20',
            'start_date_bs' => '2083-08-04',
            'end_date_bs' => '2083-08-04',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'teachers',
            'created_by' => $admin->id,
        ]);

        $staffRecipients = $notificationService->getEligibleUserIds($staffEvent);
        if ($teacher) {
            $this->assertContains($teacher->id, $staffRecipients);
        }
        if ($student) {
            $this->assertNotContains($student->id, $staffRecipients);
        }
        if ($parent) {
            $this->assertNotContains($parent->id, $staffRecipients);
        }

        // 2. Draft event: zero recipients
        $draftEvent = Event::create([
            'title' => 'Test Event Unpublished Draft ' . uniqid(),
            'category' => 'school_event',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-20',
            'start_date_bs' => '2083-08-04',
            'end_date_bs' => '2083-08-04',
            'is_all_day' => true,
            'is_published' => false, // UNPUBLISHED
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $draftRecipients = $notificationService->getEligibleUserIds($draftEvent);
        $this->assertEmpty($draftRecipients);

        // 3. No duplicates in recipients
        $allEvent = Event::create([
            'title' => 'Test Event School Wide Assembly ' . uniqid(),
            'category' => 'school_event',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-20',
            'start_date_bs' => '2083-08-04',
            'end_date_bs' => '2083-08-04',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $allRecipients = $notificationService->getEligibleUserIds($allEvent);
        $uniqueRecipients = array_unique($allRecipients);
        $this->assertEquals(count($uniqueRecipients), count($allRecipients), 'Notification recipients array must not contain duplicates.');
    }
}
