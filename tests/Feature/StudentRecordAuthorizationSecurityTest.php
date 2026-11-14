<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StudentRecordAuthorizationSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $superAdmin;
    protected $classA;
    protected $classB;
    protected $sectionA;
    protected $sectionB;
    protected $studentUser1;
    protected $studentRecord1;
    protected $studentUser2;
    protected $studentRecord2;
    protected $parentUser1;
    protected $parentUser2;
    protected $teacherAssigned;
    protected $teacherUnassigned;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'admin')->first()
            ?: User::factory()->create(['user_type' => 'admin']);

        $this->superAdmin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $ct = ClassType::first() ?: ClassType::create(['name' => 'Sec', 'code' => 'S']);
        $this->classA = MyClass::create(['name' => 'Class Auth A ' . mt_rand(100, 999), 'class_type_id' => $ct->id]);
        $this->classB = MyClass::create(['name' => 'Class Auth B ' . mt_rand(100, 999), 'class_type_id' => $ct->id]);

        $this->sectionA = Section::create(['name' => 'A', 'my_class_id' => $this->classA->id, 'active' => 1]);
        $this->sectionB = Section::create(['name' => 'B', 'my_class_id' => $this->classB->id, 'active' => 1]);

        // Parents
        $this->parentUser1 = User::factory()->create(['user_type' => 'parent']);
        $this->parentUser2 = User::factory()->create(['user_type' => 'parent']);

        // Student 1 (in Class A, child of Parent 1)
        $this->studentUser1 = User::factory()->create(['user_type' => 'student']);
        $this->studentRecord1 = StudentRecord::create([
            'user_id' => $this->studentUser1->id,
            'my_class_id' => $this->classA->id,
            'section_id' => $this->sectionA->id,
            'my_parent_id' => $this->parentUser1->id,
            'adm_no' => 'AUTH-ST-001-' . mt_rand(1000, 9999),
            'year_admitted' => '2026',
            'session' => Qs::getCurrentSession(),
        ]);

        // Student 2 (in Class B, child of Parent 2)
        $this->studentUser2 = User::factory()->create(['user_type' => 'student']);
        $this->studentRecord2 = StudentRecord::create([
            'user_id' => $this->studentUser2->id,
            'my_class_id' => $this->classB->id,
            'section_id' => $this->sectionB->id,
            'my_parent_id' => $this->parentUser2->id,
            'adm_no' => 'AUTH-ST-002-' . mt_rand(1000, 9999),
            'year_admitted' => '2026',
            'session' => Qs::getCurrentSession(),
        ]);

        // Teacher Assigned to Class A
        $this->teacherAssigned = User::factory()->create(['user_type' => 'teacher']);
        Subject::create([
            'name' => 'Mathematics ' . mt_rand(100, 999),
            'slug' => 'math-' . mt_rand(100, 999),
            'my_class_id' => $this->classA->id,
            'teacher_id' => $this->teacherAssigned->id,
        ]);

        // Teacher Unassigned (teaches nothing in Class A)
        $this->teacherUnassigned = User::factory()->create(['user_type' => 'teacher']);
    }

    public function test_student_cannot_view_another_student_profile(): void
    {
        $hashId = Qs::hash($this->studentRecord2->id);

        $response = $this->actingAs($this->studentUser1)
            ->get(route('students.show', $hashId));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_student_can_view_own_profile(): void
    {
        $hashId = Qs::hash($this->studentRecord1->id);

        $response = $this->actingAs($this->studentUser1)
            ->get(route('students.show', $hashId));

        $response->assertStatus(200);
        $response->assertSee($this->studentUser1->name);
    }

    public function test_parent_cannot_view_unlinked_student_profile(): void
    {
        // Parent 1 tries to view Student 2 (child of Parent 2)
        $hashId = Qs::hash($this->studentRecord2->id);

        $response = $this->actingAs($this->parentUser1)
            ->get(route('students.show', $hashId));

        $response->assertStatus(403);
    }

    public function test_parent_can_view_linked_child_profile(): void
    {
        $hashId = Qs::hash($this->studentRecord1->id);

        $response = $this->actingAs($this->parentUser1)
            ->get(route('students.show', $hashId));

        $response->assertStatus(200);
        $response->assertSee($this->studentUser1->name);
    }

    public function test_unassigned_teacher_cannot_view_student_profile_or_class_list(): void
    {
        // Teacher not assigned to Class A tries to list Class A
        $listRes = $this->actingAs($this->teacherUnassigned)
            ->get(route('students.list', $this->classA->id));

        $listRes->assertStatus(403);

        // Teacher not assigned to Class A tries to view Student 1's profile
        $hashId = Qs::hash($this->studentRecord1->id);
        $showRes = $this->actingAs($this->teacherUnassigned)
            ->get(route('students.show', $hashId));

        $showRes->assertStatus(403);
    }

    public function test_assigned_teacher_can_view_student_profile_and_class_list(): void
    {
        $listRes = $this->actingAs($this->teacherAssigned)
            ->get(route('students.list', $this->classA->id));

        $listRes->assertStatus(200);
        $listRes->assertSee($this->studentUser1->name);

        $hashId = Qs::hash($this->studentRecord1->id);
        $showRes = $this->actingAs($this->teacherAssigned)
            ->get(route('students.show', $hashId));

        $showRes->assertStatus(200);
        $showRes->assertSee($this->studentUser1->name);
    }

    public function test_admin_and_super_admin_can_view_any_student_profile(): void
    {
        $hashId = Qs::hash($this->studentRecord1->id);

        $this->actingAs($this->admin)
            ->get(route('students.show', $hashId))
            ->assertStatus(200);

        $this->actingAs($this->superAdmin)
            ->get(route('students.show', $hashId))
            ->assertStatus(200);
    }
}

