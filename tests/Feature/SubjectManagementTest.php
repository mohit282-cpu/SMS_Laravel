<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $myClass;
    protected $teacher;
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $classType = ClassType::first() ?: ClassType::create(['name' => 'General Class Type', 'code' => 'GCT']);
        $this->myClass = MyClass::first() ?: MyClass::create(['name' => 'Class 8', 'class_type_id' => $classType->id]);

        $this->teacher = User::where('user_type', 'teacher')->first()
            ?: User::create([
                'name' => 'Teacher Sub ' . mt_rand(100, 999),
                'email' => 'teachersub' . mt_rand(100, 999) . '@sms.test',
                'username' => 'tsub_' . mt_rand(100, 999),
                'password' => bcrypt('password'),
                'user_type' => 'teacher',
                'code' => strtoupper(substr(md5(mt_rand()), 0, 10)),
            ]);

        $this->subject = Subject::first();
        if (!$this->subject) {
            $this->subject = Subject::create([
                'name' => 'Mathematics ' . mt_rand(100, 999),
                'slug' => 'MATH',
                'my_class_id' => $this->myClass->id,
                'teacher_id' => $this->teacher->id,
            ]);
        }
    }

    public function test_super_admin_can_access_subjects_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('subjects.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Subjects');
        $response->assertSee('Total Subjects');
        $response->assertSee('Classes Covered');
        $response->assertSee('Subject Teachers');
        $response->assertSee('Practical Courses');
        $response->assertSee('All Subjects Overview');
        $response->assertSee('Add New Subject');
        $response->assertSee($this->subject->name);
    }

    public function test_admin_can_store_new_subject()
    {
        $unique = mt_rand(1000, 9999);
        $subjectTitle = 'Social Studies ' . $unique;
        $slug = 'SOC' . $unique;

        $response = $this->actingAs($this->admin)->post(route('subjects.store'), [
            'name' => $subjectTitle,
            'slug' => $slug,
            'my_class_id' => $this->myClass->id,
            'teacher_id' => Qs::hash($this->teacher->id),
        ]);

        $response->assertStatus(200);

        $created = Subject::where('name', $subjectTitle)->first();
        $this->assertNotNull($created, 'Subject was not stored in database');
        $this->assertEquals($this->myClass->id, $created->my_class_id);
        $this->assertEquals($this->teacher->id, $created->teacher_id);
    }

    public function test_admin_can_access_subject_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('subjects.edit', $this->subject->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Subject');
        $response->assertSee($this->subject->name);
    }

    public function test_admin_can_update_subject_details()
    {
        $updatedName = 'Renamed Subject ' . mt_rand(1000, 9999);
        $updatedSlug = 'RS' . mt_rand(100, 999);

        $response = $this->actingAs($this->admin)->put(route('subjects.update', $this->subject->id), [
            'name' => $updatedName,
            'slug' => $updatedSlug,
            'my_class_id' => $this->myClass->id,
            'teacher_id' => Qs::hash($this->teacher->id),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subjects', [
            'id' => $this->subject->id,
            'name' => $updatedName,
            'slug' => $updatedSlug,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_super_admin_can_delete_subject()
    {
        $tempSubject = Subject::create([
            'name' => 'Temp Course ' . mt_rand(1000, 9999),
            'slug' => 'TMP',
            'my_class_id' => $this->myClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('subjects.destroy', $tempSubject->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('subjects', [
            'id' => $tempSubject->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_subjects()
    {
        $response = $this->get(route('subjects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_subjects_management()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('subjects.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
