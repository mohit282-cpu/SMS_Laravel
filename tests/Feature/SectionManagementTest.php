<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Section;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SectionManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $myClass;
    protected $defaultSection;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $classType = ClassType::first() ?: ClassType::create(['name' => 'Secondary Tier', 'code' => 'ST']);
        $this->myClass = MyClass::first() ?: MyClass::create(['name' => 'Class 9', 'class_type_id' => $classType->id]);

        $this->defaultSection = Section::where('my_class_id', $this->myClass->id)->where('active', 1)->first();
        if (!$this->defaultSection) {
            $this->defaultSection = Section::create([
                'name' => 'A',
                'my_class_id' => $this->myClass->id,
                'active' => 1,
            ]);
        }

        $this->teacher = User::where('user_type', 'teacher')->first()
            ?: User::create([
                'name' => 'Teacher ' . mt_rand(100, 999),
                'email' => 'teacher' . mt_rand(100, 999) . '@sms.test',
                'username' => 't_' . mt_rand(100, 999),
                'password' => bcrypt('password'),
                'user_type' => 'teacher',
                'code' => strtoupper(substr(md5(mt_rand()), 0, 10)),
            ]);
    }

    public function test_super_admin_can_access_sections_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('sections.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Class Sections');
        $response->assertSee('Total Sections');
        $response->assertSee('Academic Classes');
        $response->assertSee('Class Teachers');
        $response->assertSee('Enrolled Pupils');
        $response->assertSee('All Sections Overview');
        $response->assertSee('Create New Section');
        $response->assertSee($this->defaultSection->name);
        $response->assertSee($this->myClass->name);
    }

    public function test_admin_can_store_new_section()
    {
        $sectionName = 'Section ' . mt_rand(100, 999);

        $response = $this->actingAs($this->admin)->post(route('sections.store'), [
            'name' => $sectionName,
            'my_class_id' => $this->myClass->id,
            'teacher_id' => Qs::hash($this->teacher->id),
        ]);

        $response->assertStatus(200);

        $created = Section::where('name', $sectionName)->where('my_class_id', $this->myClass->id)->first();
        $this->assertNotNull($created, 'Section was not saved in database');
        $this->assertEquals($this->teacher->id, $created->teacher_id);
    }

    public function test_admin_can_access_section_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('sections.edit', $this->defaultSection->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Section');
        $response->assertSee($this->defaultSection->name);
        $response->assertSee($this->myClass->name);
    }

    public function test_admin_can_update_section_and_teacher()
    {
        $updatedName = 'Section ' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->admin)->put(route('sections.update', $this->defaultSection->id), [
            'name' => $updatedName,
            'teacher_id' => Qs::hash($this->teacher->id),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sections', [
            'id' => $this->defaultSection->id,
            'name' => $updatedName,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_cannot_delete_active_default_section()
    {
        $response = $this->actingAs($this->admin)->delete(route('sections.destroy', $this->defaultSection->id));

        $response->assertStatus(302);
        $response->assertSessionHas('pop_warning');
        $this->assertDatabaseHas('sections', [
            'id' => $this->defaultSection->id,
        ]);
    }

    public function test_can_delete_additional_non_default_section()
    {
        $additionalSection = Section::create([
            'name' => 'Temp Section ' . mt_rand(100, 999),
            'my_class_id' => $this->myClass->id,
            'active' => 0,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('sections.destroy', $additionalSection->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('sections', [
            'id' => $additionalSection->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_sections()
    {
        $response = $this->get(route('sections.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_sections_management()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('sections.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
