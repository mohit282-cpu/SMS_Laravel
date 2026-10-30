<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Exam;
use App\Models\StudentRecord;
use App\User;
use Tests\TestCase;

class MarksAuthorizationTest extends TestCase
{
    protected function getAdmin(): User
    {
        return User::where('user_type', 'admin')->first() ?: User::factory()->create(['user_type' => 'admin']);
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first() ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first() ?: User::factory()->create(['user_type' => 'student']);
    }

    protected function getOtherStudent(): User
    {
        $first = $this->getStudent();
        return User::where('user_type', 'student')->where('id', '!=', $first->id)->first()
            ?: User::factory()->create(['user_type' => 'student']);
    }

    /**
     * Test student is blocked from viewing another student's marks selector or show.
     */
    public function test_student_cannot_access_other_students_marks()
    {
        $student = $this->getStudent();
        $otherStudent = $this->getOtherStudent();

        $response = $this->actingAs($student)->get(route('marks.year_selector', Qs::hash($otherStudent->id)));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('pop_error');

        $responseShow = $this->actingAs($student)->get(route('marks.show', [Qs::hash($otherStudent->id), '2083/84 BS']));
        $responseShow->assertRedirect(route('dashboard'));
        $responseShow->assertSessionHas('pop_error');
    }

    /**
     * Test student cannot access administrative marks management or bulk entry endpoints.
     */
    public function test_student_cannot_access_administrative_marks_endpoints()
    {
        $student = $this->getStudent();

        $responseIndex = $this->actingAs($student)->get(route('marks.index'));
        $this->assertTrue(in_array($responseIndex->status(), [403, 302]));

        $responseBulk = $this->actingAs($student)->get(route('marks.bulk'));
        $this->assertTrue(in_array($responseBulk->status(), [403, 302]));

        $responseTabulation = $this->actingAs($student)->get(route('marks.tabulation'));
        $this->assertTrue(in_array($responseTabulation->status(), [403, 302]));
    }

    /**
     * Test unassigned teacher cannot access unassigned student's marks.
     */
    public function test_unassigned_teacher_cannot_access_unassigned_students_marks()
    {
        $student = $this->getStudent();

        // Create a teacher with no classes or sections assigned
        $unassignedTeacher = User::factory()->create([
            'user_type' => 'teacher',
            'username' => 'unassigned_' . uniqid(),
            'email' => 'unassigned_' . uniqid() . '@test.com',
        ]);

        $response = $this->actingAs($unassignedTeacher)->get(route('marks.year_selector', Qs::hash($student->id)));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('pop_error');
    }

    /**
     * Test unpublished exam results are blocked from students.
     */
    public function test_unpublished_exam_results_are_hidden_from_students()
    {
        $student = $this->getStudent();

        $unpublishedExam = Exam::create([
            'name' => 'Draft Terminal Exam ' . uniqid(),
            'term' => 1,
            'year' => '2083/84 BS',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($student)->get(route('marks.print', [
            Qs::hash($student->id),
            $unpublishedExam->id,
            '2083/84 BS',
        ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash_danger');
    }
}
