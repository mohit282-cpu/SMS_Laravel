<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\TimeTableRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeTableSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin()
    {
        return User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);
    }

    protected function getTeacher()
    {
        return User::where('user_type', 'teacher')->first()
            ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudentWithRecord()
    {
        $sr = StudentRecord::with('user')->first();
        if ($sr && $sr->user) {
            return [$sr->user, $sr];
        }

        $student = User::factory()->create(['user_type' => 'student']);
        $myClass = MyClass::first() ?: MyClass::factory()->create();
        $section = Section::first() ?: Section::factory()->create(['my_class_id' => $myClass->id]);

        $sr = StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'session' => '2018-2019',
        ]);

        return [$student, $sr];
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('ttr.show', 1));
        $response->assertRedirect(route('login'));

        $responsePrint = $this->get(route('ttr.print', 1));
        $responsePrint->assertRedirect(route('login'));
    }

    public function test_non_existent_timetable_record_returns_404()
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get(route('ttr.show', 999999));
        $response->assertStatus(404);

        $responsePrint = $this->actingAs($admin)->get(route('ttr.print', 999999));
        $responsePrint->assertStatus(404);
    }

    public function test_student_cannot_view_or_print_other_class_timetable()
    {
        [$student, $sr] = $this->getStudentWithRecord();

        // Create or find a class different from student's class
        $otherClass = MyClass::where('id', '!=', $sr->my_class_id)->first();
        if (!$otherClass) {
            $otherClass = MyClass::create(['name' => 'Other Class Sec', 'class_type_id' => $sr->my_class->class_type_id ?? 1]);
        }

        $otherTtr = TimeTableRecord::create([
            'name' => 'Other Class Timetable',
            'my_class_id' => $otherClass->id,
            'year' => '2018-2019',
        ]);

        // Attempt direct access
        $responseShow = $this->actingAs($student)->get(route('ttr.show', $otherTtr->id));
        $responseShow->assertStatus(403);

        $responsePrint = $this->actingAs($student)->get(route('ttr.print', $otherTtr->id));
        $responsePrint->assertStatus(403);
    }

    public function test_student_can_view_and_print_own_class_timetable()
    {
        [$student, $sr] = $this->getStudentWithRecord();

        $ownTtr = TimeTableRecord::create([
            'name' => 'Own Class Routine',
            'my_class_id' => $sr->my_class_id,
            'year' => '2018-2019',
        ]);

        $responseShow = $this->actingAs($student)->get(route('ttr.show', $ownTtr->id));
        $responseShow->assertStatus(200);

        $responsePrint = $this->actingAs($student)->get(route('ttr.print', $ownTtr->id));
        $responsePrint->assertStatus(200);
    }

    public function test_parent_cannot_view_unlinked_class_timetable()
    {
        $parent = User::where('user_type', 'parent')->first()
            ?: User::factory()->create(['user_type' => 'parent']);

        [$student, $sr] = $this->getStudentWithRecord();

        // Make sure parent is NOT linked to this student
        $sr->update(['my_parent_id' => null]);

        $ttr = TimeTableRecord::create([
            'name' => 'Unlinked Parent Routine',
            'my_class_id' => $sr->my_class_id,
            'year' => '2018-2019',
        ]);

        $this->actingAs($parent)->get(route('ttr.show', $ttr->id))->assertStatus(403);
        $this->actingAs($parent)->get(route('ttr.print', $ttr->id))->assertStatus(403);
    }

    public function test_parent_can_view_linked_child_class_timetable()
    {
        $parent = User::where('user_type', 'parent')->first()
            ?: User::factory()->create(['user_type' => 'parent']);

        [$student, $sr] = $this->getStudentWithRecord();

        // Link parent to student
        $sr->update(['my_parent_id' => $parent->id]);

        $ttr = TimeTableRecord::create([
            'name' => 'Linked Child Routine',
            'my_class_id' => $sr->my_class_id,
            'year' => '2018-2019',
        ]);

        $this->actingAs($parent)->get(route('ttr.show', $ttr->id))->assertStatus(200);
        $this->actingAs($parent)->get(route('ttr.print', $ttr->id))->assertStatus(200);
    }

    public function test_non_admin_cannot_view_unpublished_exam_timetable()
    {
        [$student, $sr] = $this->getStudentWithRecord();

        $draftExam = Exam::create([
            'name' => 'Midterm Secret Draft ' . uniqid(),
            'term' => 1,
            'year' => '2018-2019',
            'status' => 'draft',
        ]);

        $examTtr = TimeTableRecord::create([
            'name' => 'Draft Exam Routine',
            'my_class_id' => $sr->my_class_id,
            'exam_id' => $draftExam->id,
            'year' => '2018-2019',
        ]);

        // Student tries to view draft exam timetable
        $this->actingAs($student)->get(route('ttr.show', $examTtr->id))->assertStatus(403);
        $this->actingAs($student)->get(route('ttr.print', $examTtr->id))->assertStatus(403);

        // Super Admin CAN view draft exam timetable
        $admin = $this->getAdmin();
        $this->actingAs($admin)->get(route('ttr.show', $examTtr->id))->assertStatus(200);
        $this->actingAs($admin)->get(route('ttr.print', $examTtr->id))->assertStatus(200);

        // When published, student can view
        $draftExam->update(['status' => 'published']);
        $this->actingAs($student)->get(route('ttr.show', $examTtr->id))->assertStatus(200);
    }

    public function test_timetable_index_redirects_student_and_parent()
    {
        [$student, $sr] = $this->getStudentWithRecord();
        $parent = User::where('user_type', 'parent')->first()
            ?: User::factory()->create(['user_type' => 'parent']);

        $this->actingAs($student)->get(route('tt.index'))->assertRedirect(route('tt.my_timetable'));
        $this->actingAs($parent)->get(route('tt.index'))->assertRedirect(route('tt.my_timetable'));
    }
}
