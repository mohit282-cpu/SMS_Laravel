<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordManagementSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);
    }

    protected function getAdmin()
    {
        return User::where('user_type', 'admin')->first()
            ?: User::factory()->create(['user_type' => 'admin']);
    }

    protected function getTeacher()
    {
        return User::where('user_type', 'teacher')->first()
            ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudentUser()
    {
        $sr = StudentRecord::with('user')->first();
        if ($sr && $sr->user) {
            return $sr->user;
        }
        return User::factory()->create(['user_type' => 'student']);
    }

    public function test_get_requests_to_reset_pass_are_rejected()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudentUser();

        $hashedId = Qs::hash($student->id);

        // GET request to student reset must fail (POST only)
        $response = $this->actingAs($admin)->get('/students/reset_pass/' . $hashedId);
        $this->assertTrue(in_array($response->status(), [404, 405]));

        // GET request to user reset must fail (POST only)
        $responseUser = $this->actingAs($this->getSuperAdmin())->get('/users/reset_pass/' . $hashedId);
        $this->assertTrue(in_array($responseUser->status(), [404, 405]));
    }

    public function test_unauthorized_users_cannot_reset_passwords()
    {
        $teacher = $this->getTeacher();
        $student = $this->getStudentUser();
        $hashedId = Qs::hash($student->id);

        // Teacher cannot reset passwords
        $res = $this->actingAs($teacher)->post(route('st.reset_pass', $hashedId));
        $this->assertTrue(in_array($res->status(), [302, 403]));

        // Student cannot reset passwords
        $resStudent = $this->actingAs($student)->post(route('st.reset_pass', $hashedId));
        $this->assertTrue(in_array($resStudent->status(), [302, 403]));
    }

    public function test_admin_can_reset_student_password_with_strong_temporary_credentials()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudentUser();

        // Set an initial password
        $student->update(['password' => Hash::make('old_pass_12345')]);

        $hashedId = Qs::hash($student->id);

        $response = $this->actingAs($admin)->post(route('st.reset_pass', $hashedId));
        $response->assertSessionHas('flash_success');
        $response->assertSessionHas('temp_password');

        $tempPassword = session('temp_password');
        $this->assertNotEmpty($tempPassword);
        $this->assertNotEquals('student', $tempPassword);

        // Verify new password is set to tempPassword and NOT predictable 'student'
        $student->refresh();
        $this->assertTrue(Hash::check($tempPassword, $student->password));
        $this->assertFalse(Hash::check('student', $student->password));
    }

    public function test_super_admin_can_reset_user_password_with_strong_temporary_credentials()
    {
        $superAdmin = $this->getSuperAdmin();
        $teacher = $this->getTeacher();

        $hashedId = Qs::hash($teacher->id);

        $response = $this->actingAs($superAdmin)->post(route('users.reset_pass', $hashedId));
        $response->assertSessionHas('flash_success');
        $response->assertSessionHas('temp_password');

        $tempPassword = session('temp_password');
        $this->assertNotEmpty($tempPassword);
        $this->assertNotEquals('user', $tempPassword);

        // Verify password is NOT predictable 'user'
        $teacher->refresh();
        $this->assertTrue(Hash::check($tempPassword, $teacher->password));
        $this->assertFalse(Hash::check('user', $teacher->password));
    }

    public function test_head_super_admin_cannot_be_reset_by_reset_endpoint()
    {
        $superAdmin = $this->getSuperAdmin();

        // The first super_admin in Qs::headSA
        $headId = Qs::headSA(1) ? 1 : $superAdmin->id;
        $hashedId = Qs::hash($headId);

        $response = $this->actingAs($superAdmin)->post(route('users.reset_pass', $hashedId));
        $response->assertSessionHas('flash_danger');
    }
}
