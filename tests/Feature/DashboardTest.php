<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Dashboard User',
            'email' => $role . '_' . uniqid() . '@sms.test',
            'username' => $role . '_' . uniqid(),
            'user_type' => $role,
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_sees_executive_dashboard_and_quick_operations()
    {
        $superAdmin = $this->createUser('super_admin');

        $response = $this->actingAs($superAdmin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee('Quick Operations Hub');
        $response->assertSee('Total Students');
        $response->assertSee('Total Teachers');
        $response->assertSee('Classes');
        $response->assertSee('Sections');
        $response->assertSee('Library Books');
        $response->assertSee('Examinations');
        $response->assertSee('School Calendar (Bikram Sambat BS)');
        $response->assertSee('Upcoming Events & Holidays');
        $response->assertSee('Recent Notices & Circulars');
    }

    public function test_admin_sees_dashboard_with_administrative_features()
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee('Quick Operations Hub');
        $response->assertSee('Add Student');
        $response->assertSee('School Calendar (Bikram Sambat BS)');
    }

    public function test_teacher_sees_teacher_dashboard_and_shortcuts()
    {
        $teacher = $this->createUser('teacher');

        $response = $this->actingAs($teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Assigned Classes');
        $response->assertSee('Assigned Subjects');
        $response->assertSee('Study Materials');
        $response->assertSee('Direct Actions');
        $response->assertSee('Take Class Attendance');
        $response->assertSee('School Calendar (Bikram Sambat BS)');
    }

    public function test_accountant_sees_financial_dashboard()
    {
        $accountant = $this->createUser('accountant');

        $response = $this->actingAs($accountant)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Revenue');
        $response->assertSee('Fee Structures');
        $response->assertSee('Cleared Invoices');
        $response->assertSee('Pending Invoices');
        $response->assertSee('Financial Actions');
        $response->assertSee('Manage Fee Structures');
    }

    public function test_librarian_sees_library_dashboard()
    {
        $librarian = $this->createUser('librarian');

        $response = $this->actingAs($librarian)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Books Catalog');
        $response->assertSee('Active Loans');
        $response->assertSee('Pending Requests');
        $response->assertSee('Book Categories');
        $response->assertSee('Library Actions');
        $response->assertSee('Add New Book');
    }

    public function test_student_sees_student_dashboard_widgets()
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('My Class & Section');
        $response->assertSee('Active Book Loans');
        $response->assertSee('Invoices / Fees');
        $response->assertSee('Quick Student Links');
        $response->assertSee('School Calendar (Bikram Sambat BS)');
    }

    public function test_parent_sees_parent_portal()
    {
        $parent = $this->createUser('parent');

        $response = $this->actingAs($parent)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Parent Portal — My Children');
        $response->assertSee('School Calendar (Bikram Sambat BS)');
    }
}
