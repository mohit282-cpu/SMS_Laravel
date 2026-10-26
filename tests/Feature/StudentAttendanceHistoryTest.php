<?php

namespace Tests\Feature;

use App\Models\StudentRecord;
use App\Models\StudentAttendance;
use App\User;
use Tests\TestCase;

class StudentAttendanceHistoryTest extends TestCase
{
    /**
     * Unauthenticated user cannot view student attendance history.
     */
    public function test_guest_is_redirected_to_login()
    {
        $sr = StudentRecord::first();
        $this->assertNotNull($sr);

        $response = $this->get(route('attendance.student_history', $sr->id));
        $response->assertRedirect(route('login'));
    }

    /**
     * Super admin can view any student attendance history.
     */
    public function test_admin_can_view_student_attendance_history()
    {
        $admin = User::where('user_type', 'super_admin')->first();
        $sr = StudentRecord::with('user')->first();
        $this->assertNotNull($sr);

        $response = $this->actingAs($admin)->get(route('attendance.student_history', $sr->id));
        $response->assertStatus(200);
        $response->assertSee($sr->user->name);
        $response->assertSee(__('Attendance Summary & Analytics'));
    }

    /**
     * Student can view their own attendance history.
     */
    public function test_student_can_view_own_attendance_history()
    {
        $sr = StudentRecord::with('user')->first();
        $studentUser = $sr->user;

        $response = $this->actingAs($studentUser)->get(route('attendance.student_history', $sr->id));
        $response->assertStatus(200);
        $response->assertSee($studentUser->name);
    }

    /**
     * Student cannot view another student's attendance history.
     */
    public function test_student_cannot_view_other_student_attendance_history()
    {
        $students = StudentRecord::with('user')->take(2)->get();
        if ($students->count() < 2) {
            $this->markTestSkipped('Need at least 2 students for cross-student test.');
        }

        $student1 = $students[0];
        $student2 = $students[1];

        $response = $this->actingAs($student1->user)->get(route('attendance.student_history', $student2->id));
        $response->assertStatus(403);
    }

    /**
     * Linked parent can view their child's attendance history.
     */
    public function test_parent_can_view_linked_child_attendance_history()
    {
        $parent = User::where('user_type', 'parent')->first();
        $sr = StudentRecord::with('user')->first();
        $sr->update(['my_parent_id' => $parent->id]);

        $response = $this->actingAs($parent)->get(route('attendance.student_history', $sr->id));
        $response->assertStatus(200);
        $response->assertSee($sr->user->name);
    }
}
