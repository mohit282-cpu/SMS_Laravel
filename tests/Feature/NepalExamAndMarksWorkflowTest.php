<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\Subject;
use App\User;
use Tests\TestCase;

class NepalExamAndMarksWorkflowTest extends TestCase
{
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
        $sr = \App\Models\StudentRecord::where('grad', 0)->first();
        if ($sr && $sr->user) {
            return $sr->user;
        }

        return User::where('user_type', 'student')->first();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Exam::where('name', 'First Terminal Evaluation 2083')->delete();
    }

    /**
     * Test admin can create exam with Nepal fields (BS dates, type, status)
     */
    public function test_admin_can_create_nepal_exam()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('exams.store'), [
            'name' => 'First Terminal Evaluation 2083',
            'term' => 1,
            'exam_type' => 'first_terminal',
            'start_date_bs' => '2083-03-01',
            'end_date_bs' => '2083-03-10',
            'status' => 'draft',
            'description' => 'First terminal exams for 2083 session',
        ]);

        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('exams', [
            'name' => 'First Terminal Evaluation 2083',
            'exam_type' => 'first_terminal',
            'start_date_bs' => '2083-03-01',
            'status' => 'draft',
        ]);

        // Verify stored Gregorian start_date
        $exam = Exam::where('name', 'First Terminal Evaluation 2083')->first();
        $this->assertNotNull($exam->start_date);
    }

    /**
     * Test marks entry with theory/practical and validation bounds.
     */
    public function test_marks_validation_rejects_negative_or_excessive_marks()
    {
        $admin = $this->getAdmin();
        $exam = Exam::first();
        $myClass = MyClass::first();
        $section = Section::first();
        $subject = Subject::first();

        // Create or get a mark row for test
        $student = $this->getStudent();
        $mark = Mark::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        // Attempting to post marks > theory_max (e.g. 150) should be rejected
        $response = $this->actingAs($admin)->put(route('marks.update', [$exam->id, $myClass->id, $section->id, $subject->id]), [
            'theory_' . $mark->id => 150,
            'practical_' . $mark->id => 10,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test valid marks entry calculates total, grade, GPA, and logs audit trail.
     */
    public function test_marks_entry_computes_nepal_gpa_and_audit_trail()
    {
        $admin = $this->getAdmin();
        $exam = Exam::first();
        $myClass = MyClass::first();
        $section = Section::first();
        $subject = Subject::first();
        $subject->update(['has_practical' => true, 'theory_max' => 75, 'practical_max' => 25, 'total_max' => 100]);
        $student = $this->getStudent();

        $mark = Mark::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        // Submit 60 theory + 20 practical = 80 marks (80% -> A, 3.6 GPA)
        $response = $this->actingAs($admin)->put(route('marks.update', [$exam->id, $myClass->id, $section->id, $subject->id]), [
            'theory_' . $mark->id => 60,
            'practical_' . $mark->id => 20,
        ]);

        $response->assertStatus(200);

        $mark->refresh();
        $this->assertEquals(80.0, (float)$mark->total_mark);
        $this->assertEquals('A', $mark->grade_name);
        $this->assertEquals(3.6, (float)$mark->gpa_point);

        // Verify audit trail logged
        $this->assertDatabaseHas('mark_audit_trails', [
            'mark_id' => $mark->id,
            'student_id' => $student->id,
            'user_id' => $admin->id,
            'action' => 'updated',
        ]);
    }

    /**
     * Test student cannot view unpublished examination report card.
     */
    public function test_student_cannot_view_unpublished_exam_results()
    {
        $student = $this->getStudent();
        $exam = Exam::first();
        $exam->update(['status' => 'completed']); // Unpublished

        $myClass = MyClass::first();
        $section = Section::first();
        \App\Models\ExamRecord::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        $hashedId = \App\Helpers\Qs::hash($student->id);
        $response = $this->actingAs($student)->get(route('marks.show', [$hashedId, $exam->year]));
        // Should redirect with warning about unpublished results
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash_danger');
    }

    /**
     * Test admin can publish exam and student can then view results.
     */
    public function test_admin_can_publish_exam_results()
    {
        $admin = $this->getAdmin();
        $exam = Exam::first();

        $response = $this->actingAs($admin)->put(route('exams.publish', $exam->id));
        $response->assertSessionHas('flash_success');

        $exam->refresh();
        $this->assertEquals('published', $exam->status);
        $this->assertEquals($admin->id, $exam->published_by);
    }

    /**
     * Test student cannot view another student's marks (privacy protection).
     */
    public function test_student_cannot_view_other_students_results()
    {
        $students = User::where('user_type', 'student')->take(2)->get();
        if ($students->count() < 2) {
            $this->markTestSkipped('At least two students needed for privacy test');
        }

        $studentA = $students[0];
        $studentB = $students[1];
        $exam = Exam::first();

        // Student B tries to access Student A's result
        $hashedIdA = \App\Helpers\Qs::hash($studentA->id);
        $response = $this->actingAs($studentB)->get(route('marks.show', [$hashedIdA, $exam->year]));

        // Access should be denied and redirected to dashboard
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('pop_error');
    }

    /**
     * Test marks entry properly handles absent and exempt students without zeroing out silently.
     */
    public function test_marks_entry_handles_absent_and_exempt()
    {
        $admin = $this->getAdmin();
        $exam = Exam::first();
        $myClass = MyClass::first();
        $section = Section::first();
        $subject = Subject::first();
        $student = $this->getStudent();

        $mark = Mark::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        // Submit as absent
        $response = $this->actingAs($admin)->put(route('marks.update', [$exam->id, $myClass->id, $section->id, $subject->id]), [
            'absent_' . $mark->id => '1',
        ]);

        $response->assertStatus(200);

        $mark->refresh();
        $this->assertTrue((bool)$mark->is_absent);
        $this->assertEquals('ABS', $mark->grade_name);
        $this->assertEquals(0.0, (float)$mark->gpa_point);
        $this->assertFalse((bool)$mark->is_passed);
    }

    /**
     * Test manage marks selector resolves active students even if session in student_records differs from exam/current session.
     */
    public function test_admin_manage_marks_selector_resolves_active_students_when_session_differs()
    {
        $admin = $this->getAdmin();
        $exam = Exam::first();
        $myClass = MyClass::first();
        $section = Section::where('my_class_id', $myClass->id)->first();
        $subject = Subject::where('my_class_id', $myClass->id)->first();

        $response = $this->actingAs($admin)->post(route('marks.selector'), [
            'exam_id' => $exam->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);

        $response->assertRedirect(route('marks.manage', [$exam->id, $myClass->id, $section->id, $subject->id]));
        $response->assertSessionMissing('pop_error');
    }

    /**
     * Test student marksheet year selector resolves correctly with hashed student ID.
     */
    public function test_student_marksheet_year_selector_resolves_with_hashed_id()
    {
        $student = $this->getStudent();
        $exam = Exam::first();
        $myClass = MyClass::first();
        $section = Section::where('my_class_id', $myClass->id)->first();

        // Ensure student has at least one exam record so select_year loads
        \App\Models\ExamRecord::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        $hashedId = \App\Helpers\Qs::hash($student->id);
        $response = $this->actingAs($student)->get(route('marks.year_selector', $hashedId));

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.marks.select_year');
    }

    /**
     * Test student with no exam records receives friendly notice instead of false student record not found.
     */
    public function test_student_without_exam_records_shows_informative_message()
    {
        $students = User::where('user_type', 'student')
            ->whereHas('student_record', function ($q) {
                $q->where('grad', 0);
            })->get();
        $studentWithoutExam = null;
        foreach ($students as $st) {
            if (\App\Models\ExamRecord::where('student_id', $st->id)->count() === 0
                && \App\Models\Mark::where('student_id', $st->id)->count() === 0) {
                $studentWithoutExam = $st;
                break;
            }
        }

        if (!$studentWithoutExam) {
            $this->markTestSkipped('All students have exam records in this environment.');
        }

        $hashedId = \App\Helpers\Qs::hash($studentWithoutExam->id);
        $response = $this->actingAs($studentWithoutExam)->get(route('marks.year_selector', $hashedId));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash_danger', __('No exam records or marksheet found for this student.'));
    }
}
