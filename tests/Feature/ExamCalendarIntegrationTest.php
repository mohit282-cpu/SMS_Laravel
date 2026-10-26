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
}
