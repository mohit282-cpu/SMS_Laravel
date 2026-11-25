<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\StudyMaterial;
use App\User;
use Tests\TestCase;

class StudyMaterialUiTest extends TestCase
{
    protected function tearDown(): void
    {
        StudyMaterial::where('title', 'like', '%Test UI Material%')->delete();
        parent::tearDown();
    }

    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    public function test_study_materials_index_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();
        $class = MyClass::first();

        StudyMaterial::create([
            'title' => 'Test UI Material Physics Notes',
            'description' => 'Thermodynamics chapter notes.',
            'file_name' => 'dummy_physics.pdf',
            'file_path' => 'study_materials/dummy_physics.pdf',
            'file_type' => 'pdf',
            'file_size' => 102400,
            'my_class_id' => $class->id,
            'teacher_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('study_materials.index'));

        $response->assertStatus(200);
        $response->assertSee(__('Study Materials & E-Learning'));
        $response->assertSee(__('Total Materials'));
        $response->assertSee(__('Classes Covered'));
        $response->assertSee(__('Subjects Covered'));
        $response->assertSee(__('Repository Size'));
        $response->assertSee(__('All Study Materials'));
        $response->assertSee(__('Upload New Material'));
        $response->assertSee('Test UI Material Physics Notes');
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('study_materials.index'))->assertRedirect(route('login'));
    }

    public function test_student_sees_materials_without_upload_controls()
    {
        $student = $this->getStudent();

        $response = $this->actingAs($student)->get(route('study_materials.index'));

        $response->assertStatus(200);
        $response->assertSee(__('Study Materials & E-Learning'));
        $response->assertDontSee('tab-upload-material-link');
    }

    public function test_study_materials_filtering_by_keyword_and_class()
    {
        $admin = $this->getSuperAdmin();
        $classes = MyClass::take(2)->get();
        $classA = $classes[0];
        $classB = $classes[1] ?? $classes[0];

        StudyMaterial::create([
            'title' => 'Test UI Material Chemistry Lab',
            'description' => 'Titration guide.',
            'file_name' => 'dummy_chem.pdf',
            'file_path' => 'study_materials/dummy_chem.pdf',
            'file_type' => 'pdf',
            'file_size' => 51200,
            'my_class_id' => $classA->id,
            'teacher_id' => $admin->id,
        ]);

        StudyMaterial::create([
            'title' => 'Test UI Material History Essay',
            'description' => 'Unrelated subject.',
            'file_name' => 'dummy_hist.pdf',
            'file_path' => 'study_materials/dummy_hist.pdf',
            'file_type' => 'pdf',
            'file_size' => 51200,
            'my_class_id' => $classB->id,
            'teacher_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('study_materials.index', [
            'q' => 'Chemistry',
            'class_id' => $classA->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Test UI Material Chemistry Lab');
        $response->assertDontSee('Test UI Material History Essay');
    }
}
