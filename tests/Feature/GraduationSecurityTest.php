<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GraduationSecurityTest extends TestCase
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

    protected function getStudentUser()
    {
        return User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);
    }

    protected function getParentUser()
    {
        return User::where('user_type', 'parent')->first()
            ?: User::factory()->create(['user_type' => 'parent']);
    }

    public function test_guest_cannot_access_graduated_or_change_status()
    {
        $response = $this->get(route('students.graduated'));
        $response->assertRedirect(route('login'));

        $responsePut = $this->put(route('st.not_graduated', 999999));
        $responsePut->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_cannot_access_graduated_list()
    {
        $teacher = $this->getTeacher();
        $student = $this->getStudentUser();
        $parent = $this->getParentUser();

        $this->actingAs($teacher)->get(route('students.graduated'))->assertRedirect(route('login'));
        $this->actingAs($student)->get(route('students.graduated'))->assertRedirect(route('login'));
        $this->actingAs($parent)->get(route('students.graduated'))->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_cannot_change_graduation_status()
    {
        $teacher = $this->getTeacher();
        $student = $this->getStudentUser();
        $parent = $this->getParentUser();

        $sr = StudentRecord::first();
        $this->assertNotNull($sr);
        $sr->update(['grad' => 1, 'grad_date' => '2025/2026']);

        $hashedId = Qs::hash($sr->id);

        $this->actingAs($teacher)->put(route('st.not_graduated', $hashedId))->assertRedirect(route('login'));
        $this->actingAs($student)->put(route('st.not_graduated', $hashedId))->assertRedirect(route('login'));
        $this->actingAs($parent)->put(route('st.not_graduated', $hashedId))->assertRedirect(route('login'));

        $this->assertEquals(1, $sr->fresh()->grad);
    }

    public function test_authorized_admin_can_view_graduated_list_and_restore_student()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('students.graduated'));
        $response->assertStatus(200);

        $sr = StudentRecord::first();
        $this->assertNotNull($sr);
        $sr->update(['grad' => 1, 'grad_date' => '2025/2026']);

        $hashedId = Qs::hash($sr->id);

        $responsePut = $this->actingAs($admin)->put(route('st.not_graduated', $hashedId));
        $responsePut->assertSessionHas('flash_success');

        $fresh = $sr->fresh();
        $this->assertEquals(0, $fresh->grad);
        $this->assertNull($fresh->grad_date);
    }

    public function test_non_existent_student_record_returns_error_safely()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->put(route('st.not_graduated', 'invalid-hash-9999'));
        $response->assertSessionHas('flash_danger');
    }
}
