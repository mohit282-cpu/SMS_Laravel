<?php

namespace Tests\Feature;

use App\Models\BloodGroup;
use App\Models\Nationality;
use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Models\UserType;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql_test']);
    }

    protected function makeUser(string $role): User
    {
        return User::on('mysql_test')->create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '_' . uniqid() . '@example.com',
            'username' => $role . '_' . uniqid(),
            'password' => bcrypt('password123'),
            'user_type' => $role,
            'code' => strtoupper(substr(md5(uniqid()), 0, 10)),
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_manage_users()
    {
        $response = $this->get('/users');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function unauthorized_student_cannot_access_manage_users()
    {
        $student = $this->makeUser('student');
        $response = $this->actingAs($student)->get('/users');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function super_admin_can_access_manage_users_page()
    {
        $superAdmin = $this->makeUser('super_admin');
        $response = $this->actingAs($superAdmin)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Manage System Users');
        $response->assertSee('All Staff & Users');
        $response->assertSee('Create New User');
        $response->assertSee('Create User Account');
    }

    /** @test */
    public function admin_can_access_manage_users_page()
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Manage System Users');
    }

    /** @test */
    public function super_admin_can_view_user_edit_page()
    {
        $superAdmin = $this->makeUser('super_admin');
        $teacher = $this->makeUser('teacher');

        $response = $this->actingAs($superAdmin)->get(route('users.edit', \App\Helpers\Qs::hash($teacher->id)));

        $response->assertStatus(200);
        $response->assertSee('Edit User Details');
        $response->assertSee($teacher->name);
        $response->assertSee('Update User Details');
    }
}
