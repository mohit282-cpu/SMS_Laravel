<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudyMaterial;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudyMaterialSecurityTest extends TestCase
{
    protected function getAdmin(): User
    {
        return User::where('user_type', 'admin')->first() ?: User::factory()->create(['user_type' => 'admin']);
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first() ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first() ?: User::factory()->create(['user_type' => 'student']);
    }

    /**
     * Test uploading a file with dangerous script extension is rejected.
     */
    public function test_uploading_dangerous_extension_is_rejected()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $class = MyClass::first();

        $file = UploadedFile::fake()->create('exploit.php', 100, 'text/x-php');

        $response = $this->actingAs($admin)->postJson(route('study_materials.store'), [
            'title' => 'Lecture Notes',
            'my_class_id' => $class->id,
            'material_file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test uploading with mismatched class and section is rejected.
     */
    public function test_mismatched_class_and_section_is_rejected()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $classes = MyClass::take(2)->get();
        if ($classes->count() < 2) {
            $this->markTestSkipped('Need at least 2 classes');
        }

        $class1 = $classes[0];
        $class2 = $classes[1];
        $sectionFromClass2 = Section::where('my_class_id', $class2->id)->first();
        if (!$sectionFromClass2) {
            $this->markTestSkipped('Need section in class 2');
        }

        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)->postJson(route('study_materials.store'), [
            'title' => 'Math Notes',
            'my_class_id' => $class1->id,
            'section_id' => $sectionFromClass2->id,
            'material_file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test student cannot download study material for another class.
     */
    public function test_student_cannot_download_unauthorized_material()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $student = $this->getStudent();
        $sr = StudentRecord::where('user_id', $student->id)->first();

        // Find a different class
        $otherClass = MyClass::where('id', '!=', $sr->my_class_id)->first();
        if (!$otherClass) {
            $this->markTestSkipped('Need another class');
        }

        $filePath = 'study_materials/test_unauth_' . uniqid() . '.pdf';
        Storage::disk('local')->put($filePath, 'Secret Content');

        $material = StudyMaterial::create([
            'title' => 'Other Class Notes',
            'file_path' => $filePath,
            'file_name' => 'test_unauth.pdf',
            'file_size' => 1234,
            'file_type' => 'pdf',
            'my_class_id' => $otherClass->id,
            'teacher_id' => $admin->id,
        ]);

        $response = $this->actingAs($student)->get(route('study_materials.download', $material->id));
        $response->assertStatus(403);
    }

    /**
     * Test unauthorized student cannot delete study materials.
     */
    public function test_student_cannot_delete_study_material()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $student = $this->getStudent();
        $sr = StudentRecord::where('user_id', $student->id)->first();

        $filePath = 'study_materials/test_del_' . uniqid() . '.pdf';
        Storage::disk('local')->put($filePath, 'Delete Me');

        $material = StudyMaterial::create([
            'title' => 'Delete Target',
            'file_path' => $filePath,
            'file_name' => 'test_del.pdf',
            'file_size' => 1234,
            'file_type' => 'pdf',
            'my_class_id' => $sr->my_class_id,
            'teacher_id' => $admin->id,
        ]);

        $response = $this->actingAs($student)->delete(route('study_materials.destroy', $material->id));
        $response->assertStatus(403);
    }

    /**
     * Test authorized student can download material for their class.
     */
    public function test_authorized_student_can_download_own_class_material()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $student = $this->getStudent();
        $sr = StudentRecord::where('user_id', $student->id)->first();

        $filePath = 'study_materials/test_auth_' . uniqid() . '.pdf';
        Storage::disk('local')->put($filePath, 'Authorized Content');

        $material = StudyMaterial::create([
            'title' => 'Own Class Notes',
            'file_path' => $filePath,
            'file_name' => 'test_auth.pdf',
            'file_size' => 1234,
            'file_type' => 'pdf',
            'my_class_id' => $sr->my_class_id,
            'section_id' => $sr->section_id,
            'teacher_id' => $admin->id,
        ]);

        $response = $this->actingAs($student)->get(route('study_materials.download', $material->id));
        $response->assertStatus(200);
    }
}
