<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\User;
use Tests\TestCase;

class ExamCalendarIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up test exams and events
        $exams = Exam::where('name', 'like', '%Test Exam%')->get();
        foreach ($exams as $ex) {
            Event::where('exam_id', $ex->id)->delete();
            $ex->delete();
        }
        Event::where('title', 'like', '%Test Exam%')->delete();
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
     * Test scheduling an examination synchronizes with calendar events.
     */
    public function test_scheduling_exam_creates_calendar_event()
    {
        $admin = $this->getAdmin();

        $examName = 'Test Exam First Terminal 2083 ' . uniqid();
        $response = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 1,
            'exam_type' => 'terminal',
            'start_date_bs' => '2083-08-15',
            'end_date_bs' => '2083-08-25',
            'status' => 'published',
            'description' => 'First Terminal Assessment 2083',
        ]);

        $response->assertSessionHas('flash_success');

        $exam = Exam::where('name', $examName)->first();
        $this->assertNotNull($exam);

        // Verify calendar event was automatically created and linked
        $event = Event::where('exam_id', $exam->id)->first();
        $this->assertNotNull($event, 'Calendar event should be synchronized for scheduled exam.');
        $this->assertEquals($examName, $event->title);
        $this->assertEquals('examination', $event->category);
        $this->assertEquals('2083-08-15', $event->start_date_bs);
        $this->assertEquals('2083-08-25', $event->end_date_bs);
        $this->assertTrue((bool) $event->is_published);
    }

    /**
     * Test duplicate exam schedule prevention within the same academic session.
     */
    public function test_prevents_duplicate_exam_schedules_in_session()
    {
        $admin = $this->getAdmin();

        $examName = 'Test Exam Midterm Unique ' . uniqid();
        $res1 = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 2,
            'exam_type' => 'terminal',
            'start_date_bs' => '2083-09-01',
            'end_date_bs' => '2083-09-10',
            'status' => 'scheduled',
        ]);
        $res1->assertSessionHas('flash_success');

        // Attempting to create duplicate exam in same session
        $res2 = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 2,
            'exam_type' => 'terminal',
            'start_date_bs' => '2083-09-01',
            'end_date_bs' => '2083-09-10',
            'status' => 'scheduled',
        ]);
        $res2->assertSessionHas('flash_danger');
    }

    /**
     * Test updating exam schedule synchronizes event without altering results or marks.
     */
    public function test_updating_exam_schedule_updates_calendar_event()
    {
        $admin = $this->getAdmin();

        $examName = 'Test Exam Schedule To Revise ' . uniqid();
        $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 3,
            'exam_type' => 'final',
            'start_date_bs' => '2083-11-01',
            'end_date_bs' => '2083-11-15',
            'status' => 'scheduled',
        ]);

        $exam = Exam::where('name', $examName)->first();
        $this->assertNotNull($exam);

        // Update schedule dates
        $revisedResponse = $this->actingAs($admin)->put(route('exams.update', $exam->id), [
            'name' => $examName . ' (Revised)',
            'term' => 3,
            'exam_type' => 'final',
            'start_date_bs' => '2083-11-05',
            'end_date_bs' => '2083-11-20',
            'status' => 'published',
            'description' => 'Revised exam dates due to local holiday.',
        ]);

        $revisedResponse->assertSessionHas('flash_success');

        $event = Event::where('exam_id', $exam->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals($examName . ' (Revised)', $event->title);
        $this->assertEquals('2083-11-05', $event->start_date_bs);
        $this->assertEquals('2083-11-20', $event->end_date_bs);
        $this->assertTrue((bool) $event->is_published);
    }

    /**
     * Test publishing and unpublishing updates calendar visibility.
     */
    public function test_exam_publish_and_unpublish_syncs_visibility()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudent();

        $examName = 'Test Exam Visibility Check ' . uniqid();
        $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 1,
            'exam_type' => 'terminal',
            'start_date_bs' => '2083-10-01',
            'end_date_bs' => '2083-10-10',
            'status' => 'draft',
        ]);

        $exam = Exam::where('name', $examName)->first();

        // Draft exam should NOT be visible to student
        $res1 = $this->actingAs($student)->getJson(route('calendar.events'));
        $titles1 = array_column($res1->json(), 'title');
        $this->assertNotContains($examName, $titles1);

        // Publish exam
        $this->actingAs($admin)->put(route('exams.publish', $exam->id));

        // Now visible to student
        $res2 = $this->actingAs($student)->getJson(route('calendar.events'));
        $titles2 = array_column($res2->json(), 'title');
        $this->assertContains($examName, $titles2);

        // Unpublish exam
        $this->actingAs($admin)->put(route('exams.unpublish', $exam->id));

        // Hidden from student again
        $res3 = $this->actingAs($student)->getJson(route('calendar.events'));
        $titles3 = array_column($res3->json(), 'title');
        $this->assertNotContains($examName, $titles3);
    }

    /**
     * Test safe deletion: deleting exam removes calendar entry safely.
     */
    public function test_deleting_exam_cleans_up_calendar_event()
    {
        $admin = $this->getAdmin();

        $examName = 'Test Exam Safe Delete ' . uniqid();
        $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 1,
            'start_date_bs' => '2083-12-01',
            'end_date_bs' => '2083-12-10',
            'status' => 'published',
        ]);

        $exam = Exam::where('name', $examName)->first();
        $this->assertNotNull($exam);

        $event = Event::where('exam_id', $exam->id)->first();
        $this->assertNotNull($event);

        // Delete exam
        $delResponse = $this->actingAs($admin)->delete(route('exams.destroy', $exam->id));
        $delResponse->assertSessionHas('flash_success');

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test exam creation and editing reject invalid BS dates.
     */
    public function test_exam_create_and_update_rejects_invalid_bs_dates()
    {
        $admin = $this->getAdmin();

        // 1. Create with invalid BS date (month 13 or impossible day)
        $resCreate = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => 'Invalid BS Exam ' . uniqid(),
            'term' => 1,
            'start_date_bs' => '2083-13-01',
            'end_date_bs' => '2083-13-05',
        ]);
        $resCreate->assertSessionHasErrors(['start_date_bs']);

        // 2. Create valid exam then attempt update with impossible day (e.g. Kartik 31 in 2083)
        $validName = 'Valid BS Exam ' . uniqid();
        $this->actingAs($admin)->post(route('exams.store'), [
            'name' => $validName,
            'term' => 1,
            'start_date_bs' => '2083-07-10',
            'end_date_bs' => '2083-07-20',
            'status' => 'scheduled',
        ]);
        $exam = Exam::where('name', $validName)->first();
        $this->assertNotNull($exam);

        $resUpdate = $this->actingAs($admin)->put(route('exams.update', $exam->id), [
            'name' => $validName,
            'term' => 1,
            'start_date_bs' => '2083-07-31', // Kartik 2083 has only 30 days
            'end_date_bs' => '2083-07-31',
        ]);
        $resUpdate->assertSessionHasErrors(['start_date_bs']);
    }

    /**
     * Test exam rejects chronologically reversed BS date ranges.
     */
    public function test_exam_rejects_chronologically_invalid_bs_dates()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => 'Reversed BS Exam ' . uniqid(),
            'term' => 1,
            'start_date_bs' => '2083-08-20',
            'end_date_bs' => '2083-08-10', // Earlier than start
        ]);
        $response->assertSessionHasErrors(['end_date_bs']);
    }

    /**
     * Test timetable exam calendar feed strictly hides draft exams and restricts to authorized classes.
     */
    public function test_timetable_exam_calendar_hides_draft_and_respects_class_scoping()
    {
        $admin = $this->getAdmin();
        $classes = MyClass::all();
        $this->assertGreaterThanOrEqual(2, $classes->count());

        $classA = $classes[0];
        $classB = $classes[1];

        $srA = \App\Models\StudentRecord::where('my_class_id', $classA->id)->first();
        if (!$srA) {
            $this->markTestSkipped('Need student in class A');
        }
        $studentA = $srA->user;

        // 1. Create a draft exam with timetable entry for Class A
        $draftExam = Exam::create([
            'name' => 'Draft Secret Exam ' . uniqid(),
            'term' => 1,
            'year' => \App\Helpers\Qs::getCurrentSession(),
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-25',
            'start_date_bs' => '2083-08-09',
            'end_date_bs' => '2083-08-09',
            'status' => 'draft', // DRAFT
        ]);

        $ttrA = TimeTableRecord::create([
            'name' => 'Draft TTR ' . uniqid(),
            'my_class_id' => $classA->id,
            'exam_id' => $draftExam->id,
            'year' => \App\Helpers\Qs::getCurrentSession(),
        ]);

        $sub = Subject::where('my_class_id', $classA->id)->first() ?? Subject::first();
        $ts = TimeSlot::first();
        $ttA = TimeTable::create([
            'ttr_id' => $ttrA->id,
            'ts_id' => $ts ? $ts->id : 1,
            'timestamp_from' => $ts ? $ts->timestamp_from : '1792984500',
            'timestamp_to' => $ts ? $ts->timestamp_to : '1792988100',
            'subject_id' => $sub->id,
            'exam_date' => '2026-11-25',
            'day' => 'Wednesday',
        ]);

        // Student A queries calendar: draft exam timetable MUST be hidden
        $resA = $this->actingAs($studentA)->getJson(route('calendar.events'));
        $dataA = $resA->json();
        $ttIds = array_column($dataA, 'id');
        $this->assertNotContains('tt_' . $ttA->id, $ttIds, 'Draft exam timetable must not be exposed to student.');

        // 2. Now publish the exam, and test cross-class access
        $draftExam->update(['status' => 'published']);

        // Student A should see it
        $resA2 = $this->actingAs($studentA)->getJson(route('calendar.events'));
        $ttIds2 = array_column($resA2->json(), 'id');
        $this->assertContains('tt_' . $ttA->id, $ttIds2, 'Published exam timetable for Class A should be visible to Class A student.');

        // Student in Class B should NOT see Class A timetable
        $srB = \App\Models\StudentRecord::where('my_class_id', $classB->id)->first();
        if ($srB) {
            $studentB = $srB->user;
            $resB = $this->actingAs($studentB)->getJson(route('calendar.events'));
            $ttIdsB = array_column($resB->json(), 'id');
            $this->assertNotContains('tt_' . $ttA->id, $ttIdsB, 'Class B student must not see Class A timetable entry.');
        }

        // Clean up
        $ttA->delete();
        $ttrA->delete();
        $draftExam->delete();
    }

    /**
     * Test unpublishing examination preserves marks and lifecycle history without destruction.
     */
    public function test_unpublish_exam_preserves_lifecycle_and_does_not_delete_marks()
    {
        $admin = $this->getAdmin();

        $exam = Exam::create([
            'name' => 'Exam With Marks Preserved ' . uniqid(),
            'term' => 1,
            'year' => \App\Helpers\Qs::getCurrentSession(),
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'start_date_bs' => '2083-07-15',
            'end_date_bs' => '2083-07-24',
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $admin->id,
        ]);

        $student = $this->getStudent();
        $sr = \App\Models\StudentRecord::where('user_id', $student->id)->first();
        $secId = $sr && $sr->section_id ? $sr->section_id : (\App\Models\Section::first()->id ?? 1);
        $classId = $sr && $sr->my_class_id ? $sr->my_class_id : (\App\Models\MyClass::first()->id ?? 1);
        $sub = Subject::where('my_class_id', $classId)->first() ?? Subject::first();
        $mark = \App\Models\Mark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $sub->id,
            'my_class_id' => $classId,
            'section_id' => $secId,
            'year' => $exam->year,
            'theory_mark' => 45.0,
            'practical_mark' => 20.0,
            'total_mark' => 65.0,
            'grade_name' => 'B+',
            'gpa_point' => 3.2,
        ]);

        // Unpublish the exam
        $unpubRes = $this->actingAs($admin)->put(route('exams.unpublish', $exam->id));
        $unpubRes->assertSessionHas('flash_success');

        $exam->refresh();
        $this->assertNull($exam->published_by);
        $this->assertNull($exam->published_at);
        $this->assertNotEquals('published', $exam->status);

        // Marks MUST be completely intact
        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
            'exam_id' => $exam->id,
            'total_mark' => 65.0,
        ]);

        // Clean up mark before exam teardown
        $mark->delete();
        $exam->delete();
    }

    /**
     * Test cannot delete examination when student marks are already recorded.
     */
    public function test_cannot_delete_exam_with_existing_marks()
    {
        $admin = $this->getAdmin();

        $exam = Exam::create([
            'name' => 'Exam Cannot Delete With Marks ' . uniqid(),
            'term' => 2,
            'year' => \App\Helpers\Qs::getCurrentSession(),
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-10',
            'start_date_bs' => '2083-08-15',
            'end_date_bs' => '2083-08-24',
            'status' => 'completed',
        ]);

        $student = $this->getStudent();
        $sr = \App\Models\StudentRecord::where('user_id', $student->id)->first();
        $secId = $sr && $sr->section_id ? $sr->section_id : (\App\Models\Section::first()->id ?? 1);
        $classId = $sr && $sr->my_class_id ? $sr->my_class_id : (\App\Models\MyClass::first()->id ?? 1);
        $sub = Subject::where('my_class_id', $classId)->first() ?? Subject::first();
        $mark = \App\Models\Mark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $sub->id,
            'my_class_id' => $classId,
            'section_id' => $secId,
            'year' => $exam->year,
            'theory_mark' => 50.0,
            'practical_mark' => 25.0,
            'total_mark' => 75.0,
            'grade_name' => 'A',
            'gpa_point' => 3.6,
        ]);

        // Attempt deletion: must be blocked by integrity check
        $resDelete = $this->actingAs($admin)->delete(route('exams.destroy', $exam->id));
        $resDelete->assertSessionHas('flash_danger');
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);

        // Clean up
        $mark->delete();
        $exam->delete();
    }
}
