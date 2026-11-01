<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Section;
use App\Models\StudentAttendance;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentRecordDestroySecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected $superAdmin;
    protected $admin;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->admin = User::where('user_type', 'admin')->first()
            ?: User::factory()->create(['user_type' => 'admin']);

        $this->teacher = User::where('user_type', 'teacher')->first()
            ?: User::factory()->create(['user_type' => 'teacher']);
    }

    public function test_unauthorized_users_cannot_delete_student_records(): void
    {
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_DEL_TEST_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_DEL_1',
            'session' => '2026-2027',
        ]);

        $hashedId = Qs::hash($studentUser->id);

        // Teacher cannot delete
        $response = $this->actingAs($this->teacher)->delete(route('students.destroy', $hashedId));
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);

        // Regular admin cannot delete (destroy is restricted to super_admin)
        $responseAdmin = $this->actingAs($this->admin)->delete(route('students.destroy', $hashedId));
        $responseAdmin->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
    }

    public function test_destroy_handles_invalid_or_missing_student_id_safely(): void
    {
        // Invalid hash format
        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', 'invalid-hash-string-xyz'));
        $response->assertSessionHas('flash_danger');

        // Non-existent user ID
        $nonExistentHashedId = Qs::hash(9999999);
        $responseMissing = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $nonExistentHashedId));
        $responseMissing->assertSessionHas('flash_danger');
    }

    public function test_cannot_delete_student_with_existing_financial_history(): void
    {
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_FIN_DEL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_FIN_1',
            'session' => '2026-2027',
        ]);

        $payment = Payment::first() ?: Payment::create([
            'title' => 'Tuition Fee',
            'amount' => 5000,
            'my_class_id' => $class->id,
            'description' => 'Tuition Fee Description',
            'year' => '2026-2027',
            'ref_no' => 'PAY_REF_DEL_1',
        ]);

        PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $studentUser->id,
            'ref_no' => 'PR_DEL_TEST_1',
            'amt_paid' => 2000,
            'balance' => 3000,
            'paid' => 0,
            'year' => '2026-2027',
        ]);

        $hashedId = Qs::hash($studentUser->id);

        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_danger');

        // Verify student record and user are preserved
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
        $this->assertDatabaseHas('student_records', ['user_id' => $studentUser->id]);
    }

    public function test_cannot_delete_student_with_existing_academic_marks(): void
    {
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_ACAD_DEL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_ACAD_1',
            'session' => '2026-2027',
        ]);

        $exam = Exam::first() ?: Exam::create([
            'name' => 'First Term Exam',
            'term' => 1,
            'year' => '2026-2027',
        ]);

        $subject = Subject::first() ?: Subject::create([
            'name' => 'Mathematics',
            'slug' => 'math-del-test',
            'my_class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
        ]);

        Mark::create([
            'student_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'exam_id' => $exam->id,
            'year' => '2026-2027',
            't1' => 20,
            'exm' => 60,
            'tex1' => 80,
        ]);

        $hashedId = Qs::hash($studentUser->id);

        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_danger');

        // Verify student record and user are preserved
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
        $this->assertDatabaseHas('student_records', ['user_id' => $studentUser->id]);
    }

    public function test_cannot_delete_student_with_existing_attendance_history(): void
    {
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_ATT_DEL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_ATT_1',
            'session' => '2026-2027',
        ]);

        StudentAttendance::create([
            'student_record_id' => $sr->id,
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date' => now()->toDateString(),
            'attendance_date_bs' => '2081-08-16',
            'status' => 'present',
        ]);

        $hashedId = Qs::hash($studentUser->id);

        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_danger');

        // Verify record remains intact
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
        $this->assertDatabaseHas('student_records', ['user_id' => $studentUser->id]);
        $this->assertDatabaseHas('student_attendances', ['user_id' => $studentUser->id]);
    }

    public function test_deleting_eligible_student_preserves_linked_parent_account(): void
    {
        $parentUser = User::factory()->create(['user_type' => 'parent', 'code' => 'PR_LINKED_1']);
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_PARENT_DEL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'my_parent_id' => $parentUser->id,
            'adm_no' => 'ADM_PR_1',
            'session' => '2026-2027',
        ]);

        $hashedId = Qs::hash($studentUser->id);

        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_success');

        // Student is deleted, but parent remains untouched
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);
        $this->assertDatabaseMissing('student_records', ['user_id' => $studentUser->id]);
        $this->assertDatabaseHas('users', ['id' => $parentUser->id]);
    }

    public function test_super_admin_can_safely_delete_clean_student_record_and_cleanup_files(): void
    {
        Storage::fake('public');

        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_CLEAN_DEL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_CLEAN_1',
            'session' => '2026-2027',
        ]);

        // Place a mock uploaded file in student directory
        $studentDir = Qs::getUploadPath('student') . $studentUser->code;
        Storage::put($studentDir . '/photo.jpg', 'mock photo content');
        $this->assertTrue(Storage::exists($studentDir . '/photo.jpg'));

        $hashedId = Qs::hash($studentUser->id);

        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_success');

        // Verify student user and student record were deleted
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);
        $this->assertDatabaseMissing('student_records', ['user_id' => $studentUser->id]);

        // Verify storage directory was cleaned up post-commit
        $this->assertFalse(Storage::exists($studentDir . '/photo.jpg'));

        // Verify audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.delete',
            'category' => 'students',
        ]);
    }

    public function test_failed_database_transaction_rolls_back_and_preserves_files(): void
    {
        Storage::fake('public');

        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_FAIL_TX_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_FAIL_TX_1',
            'session' => '2026-2027',
        ]);

        $studentDir = Qs::getUploadPath('student') . $studentUser->code;
        Storage::put($studentDir . '/photo.jpg', 'persisted content');

        // Hook into user delete to simulate mid-transaction failure
        User::deleting(function ($user) use ($studentUser) {
            if ($user->id === $studentUser->id) {
                throw new \RuntimeException('Simulated unexpected DB trigger failure during user deletion');
            }
        });

        $hashedId = Qs::hash($studentUser->id);

        try {
            $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        } catch (\RuntimeException $e) {
            // Expected simulated exception
        }

        // DB record should NOT be deleted because transaction rolled back
        $this->assertDatabaseHas('users', ['id' => $studentUser->id]);
        $this->assertDatabaseHas('student_records', ['user_id' => $studentUser->id]);

        // File must NOT be deleted because post-commit cleanup was never reached
        $this->assertTrue(Storage::exists($studentDir . '/photo.jpg'));
    }

    public function test_file_cleanup_exception_does_not_abort_committed_deletion(): void
    {
        $studentUser = User::factory()->create(['user_type' => 'student', 'code' => 'ST_FILE_FAIL_1']);
        $class = MyClass::first() ?: MyClass::create(['name' => 'Test Class', 'class_type_id' => 1]);
        $section = Section::first() ?: Section::create(['name' => 'A', 'my_class_id' => $class->id]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM_FILE_FAIL_1',
            'session' => '2026-2027',
        ]);

        $hashedId = Qs::hash($studentUser->id);

        // Even if Storage::deleteDirectory throws an unexpected error, the response still completes
        $response = $this->actingAs($this->superAdmin)->delete(route('students.destroy', $hashedId));
        $response->assertSessionHas('flash_success');

        // Database deletion was safely committed
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);
        $this->assertDatabaseMissing('student_records', ['user_id' => $studentUser->id]);
    }
}
