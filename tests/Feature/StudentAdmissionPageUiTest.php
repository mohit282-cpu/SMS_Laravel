<?php

namespace Tests\Feature;

use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\NepalDistrict;
use App\Models\NepalProvince;
use App\Models\Section;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAdmissionPageUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User ' . uniqid(),
            'email' => 'admin_' . uniqid() . '@sms.test',
            'username' => 'adm_' . uniqid(),
            'user_type' => 'admin',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    protected function createTeacher(): User
    {
        return User::create([
            'name' => 'Teacher User ' . uniqid(),
            'email' => 'teacher_' . uniqid() . '@sms.test',
            'username' => 'tea_' . uniqid(),
            'user_type' => 'teacher',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_student_admission()
    {
        $this->get(route('students.create'))->assertRedirect('/login');
    }

    public function test_unauthorized_teacher_cannot_access_student_admission()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher)->get(route('students.create'))->assertStatus(302);
    }

    public function test_admin_can_view_student_admission_page()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('students.create'));

        $response->assertStatus(200);
        $response->assertSee('Admit New Student');
        $response->assertSee('Personal Data');
        $response->assertSee('Academic & Class');
        $response->assertSee('Nepal Address');
        $response->assertSee('Parent / Guardian');
        $response->assertSee('Complete Student Admission');
    }

    public function test_admin_can_successfully_admit_a_new_student()
    {
        $admin = $this->createAdmin();

        $classType = ClassType::first() ?: ClassType::create(['name' => 'Secondary', 'code' => 'S']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Grade 10 ' . uniqid(), 'class_type_id' => $classType->id]);
        $section = Section::where('my_class_id', $class->id)->first()
            ?: Section::create(['name' => 'A', 'my_class_id' => $class->id, 'active' => 1]);

        $province = NepalProvince::first();
        $district = NepalDistrict::first();

        $uniqueName = 'Aarav Adhikari ' . rand(100, 999);
        $payload = [
            'name' => $uniqueName,
            'gender' => 'Male',
            'year_admitted' => (string)date('Y'),
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'address' => 'Shanti Chowk, Ward 4, Itahari',
            'nal_id' => 1,
            'province_id' => $province ? $province->id : null,
            'district_id' => $district ? $district->id : null,
            'parent_name' => 'Bikash Adhikari',
            'parent_phone' => '9841' . rand(100000, 999999),
            'parent_relationship' => 'Father',
        ];

        $response = $this->actingAs($admin)->post(route('students.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('users', [
            'name' => $uniqueName,
            'user_type' => 'student',
        ]);

        $this->assertDatabaseHas('student_records', [
            'my_class_id' => $class->id,
            'section_id' => $section->id,
        ]);
    }
}
