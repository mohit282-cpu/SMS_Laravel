<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\PushSubscription;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventAuthorizationAndIntegrityTest extends TestCase
{
    protected $createdUserIds = [];
    protected $createdEventIds = [];
    protected $createdClassIds = [];
    protected $createdSectionIds = [];
    protected $createdSubjectIds = [];
    protected $createdExamIds = [];
    protected $createdTtrIds = [];
    protected $createdTtIds = [];
    protected $createdSubIds = [];

    protected $admin;
    protected $testClass;
    protected $sectionA;
    protected $sectionB;
    protected $teacherA;
    protected $teacherB;
    protected $teacherC;
    protected $studentA;
    protected $studentB;
    protected $parentA;
    protected $parentB;
    protected $parentAB;
    protected $accountant;

    protected $eventSchoolWide;
    protected $eventClassWide;
    protected $eventSectionA;
    protected $eventSectionB;
    protected $eventSectionAUnpublished;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first();

        // 1. Create a dedicated test class
        $classType = DB::table('class_types')->first();
        $this->testClass = MyClass::create([
            'name' => 'Test Class ' . uniqid(),
            'class_type_id' => $classType ? $classType->id : 1,
        ]);
        $this->createdClassIds[] = $this->testClass->id;

        // 2. Create Section A and Section B in the same class
        $this->sectionA = Section::create([
            'name' => 'Sec A ' . uniqid(),
            'my_class_id' => $this->testClass->id,
            'active' => 1,
        ]);
        $this->createdSectionIds[] = $this->sectionA->id;

        $this->sectionB = Section::create([
            'name' => 'Sec B ' . uniqid(),
            'my_class_id' => $this->testClass->id,
            'active' => 1,
        ]);
        $this->createdSectionIds[] = $this->sectionB->id;

        // 3. Teachers
        // Teacher A: assigned to Section A
        $this->teacherA = $this->createTestUser('teacher');
        $this->sectionA->update(['teacher_id' => $this->teacherA->id]);

        // Teacher B: assigned to Section B
        $this->teacherB = $this->createTestUser('teacher');
        $this->sectionB->update(['teacher_id' => $this->teacherB->id]);

        // Teacher C: subject teacher for this class, not assigned to Section A or B
        $this->teacherC = $this->createTestUser('teacher');
        $subject = Subject::create([
            'name' => 'Math ' . uniqid(),
            'slug' => 'math_' . uniqid(),
            'my_class_id' => $this->testClass->id,
            'teacher_id' => $this->teacherC->id,
        ]);
        $this->createdSubjectIds[] = $subject->id;

        // 4. Parents
        $this->parentA = $this->createTestUser('parent');
        $this->parentB = $this->createTestUser('parent');
        $this->parentAB = $this->createTestUser('parent'); // has children in both sections

        // 5. Students
        // Student A in Section A (child of Parent A)
        $this->studentA = $this->createTestUser('student');
        StudentRecord::create([
            'user_id' => $this->studentA->id,
            'my_parent_id' => $this->parentA->id,
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionA->id,
            'adm_no' => 'ADM_' . uniqid(),
            'session' => Qs::getCurrentSession(),
        ]);

        // Student B in Section B (child of Parent B)
        $this->studentB = $this->createTestUser('student');
        StudentRecord::create([
            'user_id' => $this->studentB->id,
            'my_parent_id' => $this->parentB->id,
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionB->id,
            'adm_no' => 'ADM_' . uniqid(),
            'session' => Qs::getCurrentSession(),
        ]);

        // Students for Parent AB: one in Section A and one in Section B
        $studentAB1 = $this->createTestUser('student');
        StudentRecord::create([
            'user_id' => $studentAB1->id,
            'my_parent_id' => $this->parentAB->id,
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionA->id,
            'adm_no' => 'ADM_' . uniqid(),
            'session' => Qs::getCurrentSession(),
        ]);

        $studentAB2 = $this->createTestUser('student');
        StudentRecord::create([
            'user_id' => $studentAB2->id,
            'my_parent_id' => $this->parentAB->id,
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionB->id,
            'adm_no' => 'ADM_' . uniqid(),
            'session' => Qs::getCurrentSession(),
        ]);

        // 6. Non-teaching staff
        $this->accountant = $this->createTestUser('accountant');

        // 7. Events
        $this->eventSchoolWide = $this->createTestEvent([
            'title' => 'Test School Wide Event ' . uniqid(),
            'target_audience' => 'all',
            'is_published' => true,
        ]);

        $this->eventClassWide = $this->createTestEvent([
            'title' => 'Test Class Wide Event ' . uniqid(),
            'target_audience' => 'classes',
            'my_class_id' => $this->testClass->id,
            'section_id' => null,
            'is_published' => true,
        ]);

        $this->eventSectionA = $this->createTestEvent([
            'title' => 'Test Section A Only Event ' . uniqid(),
            'target_audience' => 'classes',
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionA->id,
            'is_published' => true,
        ]);

        $this->eventSectionB = $this->createTestEvent([
            'title' => 'Test Section B Only Event ' . uniqid(),
            'target_audience' => 'class_section',
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionB->id,
            'is_published' => true,
        ]);

        $this->eventSectionAUnpublished = $this->createTestEvent([
            'title' => 'Test Section A Unpublished ' . uniqid(),
            'target_audience' => 'classes',
            'my_class_id' => $this->testClass->id,
            'section_id' => $this->sectionA->id,
            'is_published' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTtIds)) {
            TimeTable::whereIn('id', $this->createdTtIds)->delete();
        }
        if (!empty($this->createdTtrIds)) {
            TimeTableRecord::whereIn('id', $this->createdTtrIds)->delete();
        }
        if (!empty($this->createdExamIds)) {
            Exam::whereIn('id', $this->createdExamIds)->delete();
        }
        if (!empty($this->createdSubIds)) {
            PushSubscription::whereIn('id', $this->createdSubIds)->delete();
        }
        if (!empty($this->createdEventIds)) {
            Event::whereIn('id', $this->createdEventIds)->delete();
        }
        if (!empty($this->createdSubjectIds)) {
            Subject::whereIn('id', $this->createdSubjectIds)->delete();
        }
        if (!empty($this->createdUserIds)) {
            StudentRecord::whereIn('user_id', $this->createdUserIds)->orWhereIn('my_parent_id', $this->createdUserIds)->delete();
            User::whereIn('id', $this->createdUserIds)->delete();
        }
        if (!empty($this->createdSectionIds)) {
            Section::whereIn('id', $this->createdSectionIds)->delete();
        }
        if (!empty($this->createdClassIds)) {
            MyClass::whereIn('id', $this->createdClassIds)->delete();
        }

        parent::tearDown();
    }

    protected function createTestUser(string $type): User
    {
        $user = User::create([
            'name' => ucfirst($type) . ' Test ' . uniqid(),
            'username' => strtolower($type) . '_' . uniqid(),
            'email' => strtolower($type) . '_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper(substr(md5(uniqid()), 0, 7)),
        ]);
        $this->createdUserIds[] = $user->id;
        return $user;
    }

    protected function createTestEvent(array $attributes = []): Event
    {
        $defaults = [
            'title' => 'Test Event ' . uniqid(),
            'category' => 'school_event',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-20',
            'start_date_bs' => '2083-08-04',
            'end_date_bs' => '2083-08-04',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $this->admin->id,
            'session' => Qs::getCurrentSession(),
        ];

        $event = Event::create(array_merge($defaults, $attributes));
        $this->createdEventIds[] = $event->id;
        return $event;
    }

    /*
    |--------------------------------------------------------------------------
    | Priority 1: Class & Section-Level Event Authorization Tests
    |--------------------------------------------------------------------------
    */

    /**
     * A student in Section A cannot view an event exclusively for Section B.
     */
    public function test_student_in_section_a_cannot_view_section_b_event()
    {
        $visibleEventIds = Event::visibleTo($this->studentA)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Student A should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Student A should see class-wide events.');
        $this->assertContains($this->eventSectionA->id, $visibleEventIds, 'Student A should see Section A events.');
        $this->assertNotContains($this->eventSectionB->id, $visibleEventIds, 'Student A must NOT see Section B events.');
        $this->assertNotContains($this->eventSectionAUnpublished->id, $visibleEventIds, 'Student A must NOT see unpublished events.');
    }

    /**
     * A student in Section B cannot view an event exclusively for Section A.
     */
    public function test_student_in_section_b_cannot_view_section_a_event()
    {
        $visibleEventIds = Event::visibleTo($this->studentB)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Student B should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Student B should see class-wide events.');
        $this->assertContains($this->eventSectionB->id, $visibleEventIds, 'Student B should see Section B events.');
        $this->assertNotContains($this->eventSectionA->id, $visibleEventIds, 'Student B must NOT see Section A events.');
    }

    /**
     * A parent whose child is in Section A cannot view a Section B-only event within the same class.
     */
    public function test_parent_of_section_a_child_cannot_view_section_b_event()
    {
        $visibleEventIds = Event::visibleTo($this->parentA)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Parent A should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Parent A should see class-wide events.');
        $this->assertContains($this->eventSectionA->id, $visibleEventIds, 'Parent A should see Section A events.');
        $this->assertNotContains($this->eventSectionB->id, $visibleEventIds, 'Parent A must NOT see Section B events.');
        $this->assertNotContains($this->eventSectionAUnpublished->id, $visibleEventIds, 'Parent A must NOT see unpublished events.');
    }

    /**
     * A parent with children in Sections A and B can view events targeted to either of those sections.
     */
    public function test_parent_with_children_in_both_sections_can_view_both_section_events()
    {
        $visibleEventIds = Event::visibleTo($this->parentAB)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Parent AB should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Parent AB should see class-wide events.');
        $this->assertContains($this->eventSectionA->id, $visibleEventIds, 'Parent AB should see Section A events.');
        $this->assertContains($this->eventSectionB->id, $visibleEventIds, 'Parent AB should see Section B events.');
        $this->assertNotContains($this->eventSectionAUnpublished->id, $visibleEventIds, 'Parent AB must NOT see unpublished events.');
    }

    /**
     * A teacher assigned to Section A cannot view a Section B-only event.
     */
    public function test_teacher_assigned_to_section_a_cannot_view_section_b_event()
    {
        $visibleEventIds = Event::visibleTo($this->teacherA)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Teacher A should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Teacher A should see class-wide events.');
        $this->assertContains($this->eventSectionA->id, $visibleEventIds, 'Teacher A should see Section A events.');
        $this->assertNotContains($this->eventSectionB->id, $visibleEventIds, 'Teacher A must NOT see Section B events.');
        $this->assertNotContains($this->eventSectionAUnpublished->id, $visibleEventIds, 'Teacher A must NOT see unpublished events.');
    }

    /**
     * A teacher who teaches a subject in the class (but is not a section teacher) sees class-wide events but not section-specific events.
     */
    public function test_subject_teacher_sees_class_wide_events_but_not_unassigned_section_events()
    {
        $visibleEventIds = Event::visibleTo($this->teacherC)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds, 'Subject Teacher C should see school-wide events.');
        $this->assertContains($this->eventClassWide->id, $visibleEventIds, 'Subject Teacher C should see class-wide events.');
        $this->assertNotContains($this->eventSectionA->id, $visibleEventIds, 'Subject Teacher C must NOT see Section A events.');
        $this->assertNotContains($this->eventSectionB->id, $visibleEventIds, 'Subject Teacher C must NOT see Section B events.');
    }

    /**
     * Administrators retain access to all events, including unpublished drafts and all section events.
     */
    public function test_administrator_sees_all_events_including_unpublished()
    {
        $visibleEventIds = Event::visibleTo($this->admin)->pluck('id')->toArray();

        $this->assertContains($this->eventSchoolWide->id, $visibleEventIds);
        $this->assertContains($this->eventClassWide->id, $visibleEventIds);
        $this->assertContains($this->eventSectionA->id, $visibleEventIds);
        $this->assertContains($this->eventSectionB->id, $visibleEventIds);
        $this->assertContains($this->eventSectionAUnpublished->id, $visibleEventIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Priority 2: Push Notification Targeting Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Section-specific event notification targeting restricts students, parents, and teachers to that section.
     */
    public function test_section_specific_event_notification_targeting()
    {
        $notificationService = app(\App\Services\EventNotificationService::class);

        // Section A event recipient list
        $recipientsA = $notificationService->getEligibleUserIds($this->eventSectionA);

        // Section A users MUST be included
        $this->assertContains((int) $this->studentA->id, $recipientsA, 'Student A must be eligible for Section A event notice.');
        $this->assertContains((int) $this->parentA->id, $recipientsA, 'Parent A must be eligible for Section A event notice.');
        $this->assertContains((int) $this->parentAB->id, $recipientsA, 'Parent AB must be eligible for Section A event notice.');
        $this->assertContains((int) $this->teacherA->id, $recipientsA, 'Teacher A must be eligible for Section A event notice.');
        $this->assertContains((int) $this->admin->id, $recipientsA, 'Admin must be eligible for notifications.');

        // Section B and unrelated users MUST be strictly excluded
        $this->assertNotContains((int) $this->studentB->id, $recipientsA, 'Student B must NOT receive Section A event notice.');
        $this->assertNotContains((int) $this->parentB->id, $recipientsA, 'Parent B must NOT receive Section A event notice.');
        $this->assertNotContains((int) $this->teacherB->id, $recipientsA, 'Teacher B must NOT receive Section A event notice.');
        $this->assertNotContains((int) $this->teacherC->id, $recipientsA, 'Subject Teacher C must NOT receive Section A event notice.');
        $this->assertNotContains((int) $this->accountant->id, $recipientsA, 'Accountant must NOT receive Section A event notice.');

        // No duplicates
        $this->assertEquals(count($recipientsA), count(array_unique($recipientsA)), 'Recipient list must not contain duplicates.');
    }

    /**
     * Class-wide event notification targeting includes all sections and subject teachers of the class.
     */
    public function test_class_wide_event_notification_targeting()
    {
        $notificationService = app(\App\Services\EventNotificationService::class);

        $recipientsClass = $notificationService->getEligibleUserIds($this->eventClassWide);

        $this->assertContains((int) $this->studentA->id, $recipientsClass);
        $this->assertContains((int) $this->studentB->id, $recipientsClass);
        $this->assertContains((int) $this->parentA->id, $recipientsClass);
        $this->assertContains((int) $this->parentB->id, $recipientsClass);
        $this->assertContains((int) $this->parentAB->id, $recipientsClass);
        $this->assertContains((int) $this->teacherA->id, $recipientsClass);
        $this->assertContains((int) $this->teacherB->id, $recipientsClass);
        $this->assertContains((int) $this->teacherC->id, $recipientsClass);
        $this->assertContains((int) $this->admin->id, $recipientsClass);
        $this->assertNotContains((int) $this->accountant->id, $recipientsClass);
    }

    /**
     * Unpublished events return zero eligible notification recipients.
     */
    public function test_unpublished_event_dispatches_zero_notification_recipients()
    {
        $notificationService = app(\App\Services\EventNotificationService::class);
        $recipients = $notificationService->getEligibleUserIds($this->eventSectionAUnpublished);
        $this->assertEmpty($recipients, 'Unpublished events must yield empty recipient list.');
    }

    /**
     * Subscribed recipients list deduplicates multiple push subscription records.
     */
    public function test_subscribed_recipients_deduplication()
    {
        $notificationService = app(\App\Services\EventNotificationService::class);

        // Add 2 push subscription records for Student A (e.g. mobile + desktop)
        $sub1 = PushSubscription::create([
            'user_id' => $this->studentA->id,
            'endpoint' => 'https://push.example.com/test_endpoint_1_' . uniqid(),
            'endpoint_hash' => md5(uniqid()),
            'public_key' => 'key1',
            'auth_token' => 'token1',
        ]);
        $sub2 = PushSubscription::create([
            'user_id' => $this->studentA->id,
            'endpoint' => 'https://push.example.com/test_endpoint_2_' . uniqid(),
            'endpoint_hash' => md5(uniqid()),
            'public_key' => 'key2',
            'auth_token' => 'token2',
        ]);
        $this->createdSubIds[] = $sub1->id;
        $this->createdSubIds[] = $sub2->id;

        $subscribedUserIds = $notificationService->getSubscribedRecipientIds($this->eventSectionA);

        $studentCount = count(array_filter($subscribedUserIds, fn($id) => $id === (int) $this->studentA->id));
        $this->assertEquals(1, $studentCount, 'Student A ID must appear exactly once in subscribed recipients list despite multiple subscriptions.');
    }

    /*
    |--------------------------------------------------------------------------
    | Priority 3: Migration Safety & Orphan Detection Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Foreign-key migration throws actionable RuntimeException without altering data when orphaned records exist.
     */
    public function test_foreign_key_migration_detects_orphans_without_silent_nullification()
    {
        require_once database_path('migrations/2026_10_26_140000_add_foreign_keys_to_events_table.php');
        $migration = new \AddForeignKeysToEventsTable();

        // 1. Temporarily drop FK constraints to simulate a pre-migration state with legacy data
        $migration->down();

        try {
            // 2. Insert a test event with an invalid (orphaned) academic_year_id
            $orphanEvent = Event::create([
                'title' => 'Orphan Test Event ' . uniqid(),
                'category' => 'school_event',
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-20',
                'start_date_bs' => '2083-08-04',
                'end_date_bs' => '2083-08-04',
                'is_all_day' => true,
                'is_published' => true,
                'academic_year_id' => 999999, // Non-existent academic year
                'created_by' => $this->admin->id,
            ]);
            $this->createdEventIds[] = $orphanEvent->id;

            // 3. Attempting to run up() MUST fail safely due to the orphan
            try {
                $migration->up();
                $this->fail('Migration should have thrown RuntimeException due to orphaned academic_year_id.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('academic_year_id', $e->getMessage());
                $this->assertStringContainsString('unresolved orphaned references exist', $e->getMessage());
            }

            // CRITICAL CHECK: Verify the orphaned reference was NOT silently nullified or modified!
            $orphanEvent->refresh();
            $this->assertEquals(999999, $orphanEvent->academic_year_id, 'Orphaned reference must remain intact and not silently nullified.');

            // Clean up the orphan
            $orphanEvent->delete();

            // 4. Clean migration must succeed now
            $migration->up();
        } finally {
            // Ensure foreign keys are restored in case of any failure
            try {
                $migration->up();
            } catch (\Throwable $e) {
                // Ignore if already applied
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Priority 4: Examination Timetable Visibility Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Draft examination timetable entries are never exposed through the calendar feed.
     */
    public function test_timetable_draft_exams_are_never_disclosed_to_non_admins()
    {
        // Create draft examination
        $draftExam = Exam::create([
            'name' => 'Confidential Draft Exam ' . uniqid(),
            'term' => 1,
            'year' => Qs::getCurrentSession(),
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-25',
            'start_date_bs' => '2083-08-09',
            'end_date_bs' => '2083-08-09',
            'status' => 'draft',
        ]);
        $this->createdExamIds[] = $draftExam->id;

        $ttr = TimeTableRecord::create([
            'name' => 'TTR Draft ' . uniqid(),
            'my_class_id' => $this->testClass->id,
            'exam_id' => $draftExam->id,
            'year' => Qs::getCurrentSession(),
        ]);
        $this->createdTtrIds[] = $ttr->id;

        $sub = Subject::where('my_class_id', $this->testClass->id)->first();
        $ts = TimeSlot::first();

        $tt = TimeTable::create([
            'ttr_id' => $ttr->id,
            'ts_id' => $ts ? $ts->id : 1,
            'timestamp_from' => $ts ? $ts->timestamp_from : '1792984500',
            'timestamp_to' => $ts ? $ts->timestamp_to : '1792988100',
            'subject_id' => $sub->id,
            'exam_date' => '2026-11-25',
            'day' => 'Wednesday',
        ]);
        $this->createdTtIds[] = $tt->id;

        // Student A queries calendar: draft exam MUST NOT be exposed
        $resStudent = $this->actingAs($this->studentA)->getJson(route('calendar.events'));
        $resStudent->assertStatus(200);
        $studentIds = array_column($resStudent->json(), 'id');
        $this->assertNotContains('tt_' . $tt->id, $studentIds, 'Draft exam timetable must not appear for student.');

        // Parent A queries calendar: draft exam MUST NOT be exposed
        $resParent = $this->actingAs($this->parentA)->getJson(route('calendar.events'));
        $resParent->assertStatus(200);
        $parentIds = array_column($resParent->json(), 'id');
        $this->assertNotContains('tt_' . $tt->id, $parentIds, 'Draft exam timetable must not appear for parent.');

        // Teacher A queries calendar: draft exam MUST NOT be exposed
        $resTeacher = $this->actingAs($this->teacherA)->getJson(route('calendar.events'));
        $resTeacher->assertStatus(200);
        $teacherIds = array_column($resTeacher->json(), 'id');
        $this->assertNotContains('tt_' . $tt->id, $teacherIds, 'Draft exam timetable must not appear for teacher.');

        // Non-teaching staff queries calendar: draft exam MUST NOT be exposed
        $resAccountant = $this->actingAs($this->accountant)->getJson(route('calendar.events'));
        $resAccountant->assertStatus(200);
        $accountantIds = array_column($resAccountant->json(), 'id');
        $this->assertNotContains('tt_' . $tt->id, $accountantIds, 'Timetable entries must not appear for non-teaching staff.');

        // Super Admin queries calendar: draft exam IS visible for administration
        $resAdmin = $this->actingAs($this->admin)->getJson(route('calendar.events'));
        $resAdmin->assertStatus(200);
        $adminIds = array_column($resAdmin->json(), 'id');
        $this->assertContains('tt_' . $tt->id, $adminIds, 'Super Admin must see all examination timetables.');
    }

    /**
     * Published examination timetable entries are visible to students and parents of that class, but not other classes.
     */
    public function test_published_examination_timetable_cross_class_restriction()
    {
        // 1. Create published exam for Test Class
        $pubExam = Exam::create([
            'name' => 'Published Terminal Exam ' . uniqid(),
            'term' => 1,
            'year' => Qs::getCurrentSession(),
            'start_date' => '2026-11-28',
            'end_date' => '2026-11-28',
            'start_date_bs' => '2083-08-12',
            'end_date_bs' => '2083-08-12',
            'status' => 'published',
        ]);
        $this->createdExamIds[] = $pubExam->id;

        $ttr = TimeTableRecord::create([
            'name' => 'TTR Pub ' . uniqid(),
            'my_class_id' => $this->testClass->id,
            'exam_id' => $pubExam->id,
            'year' => Qs::getCurrentSession(),
        ]);
        $this->createdTtrIds[] = $ttr->id;

        $sub = Subject::where('my_class_id', $this->testClass->id)->first();
        $ts = TimeSlot::first();

        $tt = TimeTable::create([
            'ttr_id' => $ttr->id,
            'ts_id' => $ts ? $ts->id : 1,
            'timestamp_from' => $ts ? $ts->timestamp_from : '1792984500',
            'timestamp_to' => $ts ? $ts->timestamp_to : '1792988100',
            'subject_id' => $sub->id,
            'exam_date' => '2026-11-28',
            'day' => 'Saturday',
        ]);
        $this->createdTtIds[] = $tt->id;

        // Student A (enrolled in Test Class) CAN see the published timetable
        $resA = $this->actingAs($this->studentA)->getJson(route('calendar.events'));
        $this->assertContains('tt_' . $tt->id, array_column($resA->json(), 'id'));

        // Parent A (child in Test Class) CAN see the published timetable
        $resParent = $this->actingAs($this->parentA)->getJson(route('calendar.events'));
        $this->assertContains('tt_' . $tt->id, array_column($resParent->json(), 'id'));

        // Unrelated student in another class CANNOT see it
        $unrelatedClass = MyClass::where('id', '!=', $this->testClass->id)->first();
        if ($unrelatedClass) {
            $unrelatedSection = Section::where('my_class_id', $unrelatedClass->id)->first();
            $unrelatedStudent = $this->createTestUser('student');
            StudentRecord::create([
                'user_id' => $unrelatedStudent->id,
                'my_class_id' => $unrelatedClass->id,
                'section_id' => $unrelatedSection ? $unrelatedSection->id : $this->sectionA->id,
                'adm_no' => 'ADM_OTHER_' . uniqid(),
                'session' => Qs::getCurrentSession(),
            ]);

            $resOther = $this->actingAs($unrelatedStudent)->getJson(route('calendar.events'));
            $this->assertNotContains('tt_' . $tt->id, array_column($resOther->json(), 'id'), 'Student from another class must not see this timetable.');
        }
    }
}
