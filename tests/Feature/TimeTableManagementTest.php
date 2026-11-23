<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Models\TimeTableRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimeTableManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' TT User',
            'email' => $role . '_' . uniqid() . '@sms.test',
            'username' => $role . '_' . uniqid(),
            'user_type' => $role,
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_timetables()
    {
        $response = $this->get('/timetables');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_view_timetables_index_with_all_tabs_and_metrics()
    {
        $admin = $this->createUser('super_admin');

        $response = $this->actingAs($admin)->get('/timetables');

        $response->assertStatus(200);
        $response->assertSee('Manage TimeTables');
        $response->assertSee('Total Timetables');
        $response->assertSee('Class Schedules');
        $response->assertSee('Exam Routines');
        $response->assertSee('All Timetables');
        $response->assertSee('Create Timetable');
        $response->assertSee('Filter By Class');
    }

    public function test_admin_can_create_new_timetable_record()
    {
        $admin = $this->createUser('admin');
        $myClass = MyClass::first() ?: MyClass::factory()->create();

        $name = 'Routine ' . uniqid();
        $response = $this->actingAs($admin)->post(route('ttr.store'), [
            'name' => $name,
            'my_class_id' => $myClass->id,
            'exam_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('time_table_records', [
            'name' => $name,
            'my_class_id' => $myClass->id,
        ]);
    }

    public function test_teacher_can_view_timetables_page_for_assigned_classes()
    {
        $teacher = $this->createUser('teacher');
        $myClass = MyClass::first() ?: MyClass::factory()->create();
        $section = Section::first() ?: Section::factory()->create(['my_class_id' => $myClass->id]);
        $section->update(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get('/timetables');

        $response->assertStatus(200);
        $response->assertSee('Manage TimeTables');
        $response->assertSee('All Timetables');
    }

    public function test_student_visiting_timetables_is_redirected_to_my_timetable()
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)->get('/timetables');

        $response->assertRedirect(route('tt.my_timetable'));
    }

    public function test_parent_visiting_timetables_is_redirected_to_my_timetable()
    {
        $parent = $this->createUser('parent');

        $response = $this->actingAs($parent)->get('/timetables');

        $response->assertRedirect(route('tt.my_timetable'));
    }
}
