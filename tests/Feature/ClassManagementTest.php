<?php

namespace Tests\Feature;

use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $classType;
    protected $myClass;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->myClass = MyClass::with('class_type')->first();
        if (!$this->myClass) {
            $this->classType = ClassType::first() ?: ClassType::create(['name' => 'Primary Tier', 'code' => 'PT']);
            $this->myClass = MyClass::create(['name' => 'Standard 1', 'class_type_id' => $this->classType->id]);
        } else {
            $this->classType = $this->myClass->class_type ?: (ClassType::first() ?: ClassType::create(['name' => 'Primary Tier', 'code' => 'PT']));
        }

        $this->section = Section::where('my_class_id', $this->myClass->id)->first()
            ?: Section::create(['name' => 'A', 'my_class_id' => $this->myClass->id, 'active' => 1]);
    }

    public function test_super_admin_can_access_classes_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('classes.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Classes');
        $response->assertSee('Total Classes');
        $response->assertSee('Total Sections');
        $response->assertSee('Enrolled Students');
        $response->assertSee('Class Levels');
        $response->assertSee('Create New Class');
        $response->assertSee($this->myClass->name);
        $response->assertSee($this->classType->name);
    }

    public function test_admin_can_store_new_class_and_default_section_is_provisioned()
    {
        $unique = mt_rand(1000, 9999);
        $className = 'Class Test ' . $unique;

        $response = $this->actingAs($this->admin)->post(route('classes.store'), [
            'name' => $className,
            'class_type_id' => $this->classType->id,
        ]);

        $response->assertStatus(200);

        $createdClass = MyClass::where('name', $className)->first();
        $this->assertNotNull($createdClass, 'Class was not stored in database');
        $this->assertEquals($this->classType->id, $createdClass->class_type_id);

        // Verify default section "A" was automatically provisioned
        $defaultSection = Section::where('my_class_id', $createdClass->id)->where('name', 'A')->first();
        $this->assertNotNull($defaultSection, 'Default section A was not created');
    }

    public function test_admin_can_access_class_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('classes.edit', $this->myClass->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Class');
        $response->assertSee($this->myClass->name);
        $response->assertSee($this->classType->name);
    }

    public function test_admin_can_update_class_name()
    {
        $updatedName = 'Renamed Class ' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->admin)->put(route('classes.update', $this->myClass->id), [
            'name' => $updatedName,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('my_classes', [
            'id' => $this->myClass->id,
            'name' => $updatedName,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_classes()
    {
        $response = $this->get(route('classes.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_classes_management()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('classes.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
