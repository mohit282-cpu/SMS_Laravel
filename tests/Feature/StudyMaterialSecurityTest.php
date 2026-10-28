<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudyMaterial;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudyMaterialSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('user_type', 'admin')->first()
            ?: User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'admin']);
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first()
            ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudentWithRecord(): array
    {
        $sr = StudentRecord::with('user')->first();
        if ($sr && $sr->user) {
            return [$sr->user, $sr];
        }

        $myClass = MyClass::first() ?: MyClass::factory()->create();
        $section = Section::first() ?: Section::factory()->create(['my_class_id' => $myClass->id]);

        $student = User::factory()->create(['user_type' => 'student']);
        $sr = StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'session' => '2018-2019',
        ]);

        return [$student, $sr];
    }

    /**
     * Test uploading a file with dangerous script extension is rejected.
     */
    public function test_uploading_dangerous_extension_is_rejected()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $class = MyClass::first() ?: MyClass::factory()->create();

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
            $class1 = MyClass::factory()->create();
            $class2 = MyClass::factory()->create();
        } else {
            $class1 = $classes[0];
            $class2 = $classes[1];
        }

        $sectionFromClass2 = Section::where('my_class_id', $class2->id)->first()
            ?: Section::factory()->create(['my_class_id' => $class2->id]);

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
        [$student, $sr] = $this->getStudentWithRecord();

        // Find a different class
        $otherClass = MyClass::where('id', '!=', $sr->my_class_id)->first();
        if (!$otherClass) {
            $otherClass = MyClass::factory()->create();
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
        [$student, $sr] = $this->getStudentWithRecord();

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
     * Test authorized student can download material for their class and section.
     */
    public function test_authorized_student_can_download_own_class_material()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        [$student, $sr] = $this->getStudentWithRecord();

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

    /**
     * Regression Test: Two sections within the same class.
     * Teacher and Student of Section A cannot access material restricted to Section B.
     */
    public function test_section_a_teacher_and_student_cannot_access_section_b_material()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();

        $myClass = MyClass::first() ?: MyClass::factory()->create();

        // Create Section A and Teacher A
        $teacherA = User::factory()->create(['user_type' => 'teacher']);
        $sectionA = Section::create([
            'name' => 'Section Alpha',
            'my_class_id' => $myClass->id,
            'teacher_id' => $teacherA->id,
        ]);

        // Create Section B and Teacher B
        $teacherB = User::factory()->create(['user_type' => 'teacher']);
        $sectionB = Section::create([
            'name' => 'Section Beta',
            'my_class_id' => $myClass->id,
            'teacher_id' => $teacherB->id,
        ]);

        // Create Student A in Section A
        $studentA = User::factory()->create(['user_type' => 'student']);
        StudentRecord::create([
            'user_id' => $studentA->id,
            'my_class_id' => $myClass->id,
            'section_id' => $sectionA->id,
            'session' => '2018-2019',
        ]);

        // Upload material restricted strictly to Section B
        $filePath = 'study_materials/sec_b_' . uniqid() . '.pdf';
        Storage::disk('local')->put($filePath, 'Section B Confidential Notes');

        $materialB = StudyMaterial::create([
            'title' => 'Section B Only Notes',
            'file_path' => $filePath,
            'file_name' => 'sec_b.pdf',
            'file_size' => 500,
            'file_type' => 'pdf',
            'my_class_id' => $myClass->id,
            'section_id' => $sectionB->id,
            'teacher_id' => $teacherB->id,
        ]);

        // 1. Teacher A (assigned only to Section A in this class) must be DENIED access to Section B material
        $responseTeacherA = $this->actingAs($teacherA)->get(route('study_materials.download', $materialB->id));
        $responseTeacherA->assertStatus(403);

        // 2. Student A (enrolled in Section A) must be DENIED access to Section B material
        $responseStudentA = $this->actingAs($studentA)->get(route('study_materials.download', $materialB->id));
        $responseStudentA->assertStatus(403);

        // 3. Teacher B (assigned to Section B) MUST be allowed access
        $responseTeacherB = $this->actingAs($teacherB)->get(route('study_materials.download', $materialB->id));
        $responseTeacherB->assertStatus(200);

        // 4. Admin MUST be allowed access
        $responseAdmin = $this->actingAs($admin)->get(route('study_materials.download', $materialB->id));
        $responseAdmin->assertStatus(200);
    }

    /**
     * Test class-wide material (section_id IS NULL) is accessible to teachers and students of both sections.
     */
    public function test_class_wide_material_is_accessible_to_both_sections()
    {
        Storage::fake('local');
        $admin = $this->getAdmin();
        $myClass = MyClass::first() ?: MyClass::factory()->create();

        $teacherA = User::factory()->create(['user_type' => 'teacher']);
        $sectionA = Section::create([
            'name' => 'Section Alpha Wide',
            'my_class_id' => $myClass->id,
            'teacher_id' => $teacherA->id,
        ]);

        $studentA = User::factory()->create(['user_type' => 'student']);
        StudentRecord::create([
            'user_id' => $studentA->id,
            'my_class_id' => $myClass->id,
            'section_id' => $sectionA->id,
            'session' => '2018-2019',
        ]);

        $filePath = 'study_materials/class_wide_' . uniqid() . '.pdf';
        Storage::disk('local')->put($filePath, 'Class Wide Material Content');

        $classWideMaterial = StudyMaterial::create([
            'title' => 'Class Wide Syllabus',
            'file_path' => $filePath,
            'file_name' => 'class_wide.pdf',
            'file_size' => 600,
            'file_type' => 'pdf',
            'my_class_id' => $myClass->id,
            'section_id' => null, // Class-wide
            'teacher_id' => $admin->id,
        ]);

        // Teacher A should be able to download class-wide material
        $responseTeacher = $this->actingAs($teacherA)->get(route('study_materials.download', $classWideMaterial->id));
        $responseTeacher->assertStatus(200);

        // Student A should be able to download class-wide material
        $responseStudent = $this->actingAs($studentA)->get(route('study_materials.download', $classWideMaterial->id));
        $responseStudent->assertStatus(200);
    }
}
