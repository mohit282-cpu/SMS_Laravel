<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompleteFeatureImplementationTest extends TestCase
{
    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getAdmin(): User
    {
        return User::where('user_type', 'admin')->first();
    }

    protected function getAccountant(): User
    {
        return User::where('user_type', 'accountant')->first();
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first();
    }

    protected function getStudent(): User
    {
        $sr = StudentRecord::where('grad', 0)->first();
        if ($sr && $sr->user) {
            return $sr->user;
        }

        return User::where('user_type', 'student')->first();
    }

    protected function getParent(): User
    {
        return User::where('user_type', 'parent')->first();
    }

    // ==========================================
    // 1. DELETION RESTRICTIONS & AUTHORIZATION
    // ==========================================

    public function test_deletion_restricted_to_super_admin()
    {
        $admin = $this->getAdmin();
        $class = MyClass::first();

        // Admin attempting to delete a class should receive 403 Forbidden
        $response = $this->actingAs($admin)->delete(route('classes.destroy', $class->id));
        $response->assertStatus(403);

        // Accountant attempting to delete a payment structure should receive 403 Forbidden
        $accountant = $this->getAccountant();
        $pay = Payment::first();
        if ($pay) {
            $responsePay = $this->actingAs($accountant)->delete(route('payments.destroy', $pay->id));
            $responsePay->assertStatus(403);
        }
    }

    public function test_head_super_admin_cannot_be_deleted()
    {
        $sa = $this->getSuperAdmin();
        $headSaId = 1;

        $response = $this->actingAs($sa)->delete(route('users.destroy', Qs::hash($headSaId)));
        $response->assertSessionHas('pop_error');
    }

    // ==========================================
    // 2. NOTICEBOARD & CALENDAR INTEGRATION
    // ==========================================

    public function test_notice_crud_and_role_visibility()
    {
        $admin = $this->getAdmin();
        $teacher = $this->getTeacher();
        $student = $this->getStudent();

        // 1. Admin creates a teacher-only notice
        $response = $this->actingAs($admin)->post(route('notices.store'), [
            'title' => 'Staff Meeting Notice',
            'content' => 'Mandatory meeting for teachers.',
            'notice_date' => now()->toDateString(),
            'role_visibility' => 'teacher',
            'is_published' => 1,
        ]);
        $response->assertRedirect(route('notices.index'));

        $teacherNotice = Notice::where('title', 'Staff Meeting Notice')->first();
        $this->assertNotNull($teacherNotice);

        // 2. Teacher can view this notice
        $resTeacher = $this->actingAs($teacher)->get(route('notices.show', $teacherNotice->id));
        $resTeacher->assertStatus(200);
        $resTeacher->assertSee('Staff Meeting Notice');

        // 3. Student cannot view this teacher-only notice
        $resStudent = $this->actingAs($student)->get(route('notices.show', $teacherNotice->id));
        $resStudent->assertStatus(404);

        // 4. Calendar events feed includes the notice
        $resEvents = $this->actingAs($teacher)->get(route('calendar.events'));
        $resEvents->assertStatus(200);
        $resEvents->assertJsonFragment([
            'type' => 'notice',
            'notice_id' => $teacherNotice->id,
        ]);
    }

    // ==========================================
    // 3. STUDY MATERIALS MODULE
    // ==========================================

    public function test_study_material_upload_and_download()
    {
        Storage::fake('local');
        $teacher = $this->getTeacher();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();
        $section->update(['teacher_id' => $teacher->id]);
        StudyMaterial::where('title', 'Unit 1 Lecture Notes')->delete();

        // 1. Teacher uploads a valid PDF study material
        $file = UploadedFile::fake()->create('lecture_notes.pdf', 500, 'application/pdf');
        $resUpload = $this->actingAs($teacher)->post(route('study_materials.store'), [
            'title' => 'Unit 1 Lecture Notes',
            'description' => 'Important formulas',
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'material_file' => $file,
        ]);
        $resUpload->assertRedirect(route('study_materials.index'));

        $material = StudyMaterial::where('title', 'Unit 1 Lecture Notes')->first();
        $this->assertNotNull($material);

        // 2. Rejection of prohibited file extensions
        $badFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');
        $resBad = $this->actingAs($teacher)->post(route('study_materials.store'), [
            'title' => 'Malicious file',
            'my_class_id' => $class->id,
            'material_file' => $badFile,
        ]);
        $resBad->assertSessionHasErrors('material_file');

        // 3. Student in this class can download the material
        $student = $this->getStudent();
        StudentRecord::where('user_id', $student->id)->update([
            'my_class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        $resDownload = $this->actingAs($student)->get(route('study_materials.download', $material->id));
        $resDownload->assertStatus(200);
    }

    // ==========================================
    // 4. TIMETABLE MANAGEMENT & CONFLICT DETECTION
    // ==========================================

    public function test_timetable_conflict_detection()
    {
        $sa = $this->getSuperAdmin();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();
        $teacher = $this->getTeacher();
        $subject = Subject::first();
        $subject->update(['teacher_id' => $teacher->id]);

        $ttr = TimeTableRecord::firstOrCreate(
            ['name' => 'Regular Class Timetable'],
            [
                'my_class_id' => $class->id,
                'year' => Qs::getCurrentSession(),
            ]
        );

        $ts = TimeSlot::firstOrCreate([
            'ttr_id' => $ttr->id,
            'hour_from' => 9,
            'min_from' => '00',
            'meridian_from' => 'AM',
            'hour_to' => 10,
            'min_to' => '00',
            'meridian_to' => 'AM',
            'time_from' => '09:00 AM',
            'time_to' => '10:00 AM',
            'timestamp_from' => strtotime('09:00 AM'),
            'timestamp_to' => strtotime('10:00 AM'),
            'full' => '09:00 AM - 10:00 AM',
        ]);

        // Add first slot entry
        TimeTable::where(['ttr_id' => $ttr->id, 'ts_id' => $ts->id, 'day' => 'Monday'])->delete();
        TimeTable::create([
            'ttr_id' => $ttr->id,
            'ts_id' => $ts->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'timestamp_from' => strtotime('Monday 09:00 AM'),
            'timestamp_to' => strtotime('Monday 10:00 AM'),
        ]);

        // 1. Duplicate class slot conflict: same class, same day, same time slot
        $resConflict = $this->actingAs($sa)->postJson(route('tt.store'), [
            'ttr_id' => $ttr->id,
            'ts_id' => $ts->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
        ]);
        $resConflict->assertStatus(422);
        $resConflict->assertJsonFragment([
            'msg' => 'This class already has a subject scheduled for this time slot.',
            'ok' => false,
        ]);
    }

    public function test_student_and_teacher_timetable_views()
    {
        $student = $this->getStudent();
        $class = MyClass::first();
        StudentRecord::where('user_id', $student->id)->update(['my_class_id' => $class->id]);

        $ttr = TimeTableRecord::firstOrCreate(
            ['name' => 'Class Timetable'],
            [
                'my_class_id' => $class->id,
                'year' => Qs::getCurrentSession(),
            ]
        );

        // Student accessing own class timetable redirects to ttr.show
        $resStudent = $this->actingAs($student)->get(route('tt.my_timetable'));
        $resStudent->assertStatus(302);
        $this->assertStringContainsString('timetables/records/show/', $resStudent->headers->get('Location'));

        // Teacher accessing teaching timetable
        $teacher = $this->getTeacher();
        $resTeacher = $this->actingAs($teacher)->get(route('tt.my_timetable'));
        $resTeacher->assertStatus(200);
        $resTeacher->assertSee('My Weekly Teaching Schedule');
    }

    // ==========================================
    // 5. FEES, PAYMENTS & PRIVACY
    // ==========================================

    public function test_payment_validation_and_parent_child_privacy()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();
        $parent = $this->getParent();

        $pr = PaymentRecord::first();
        if (!$pr) {
            $payment = Payment::firstOrCreate([
                'title' => 'Tuition Fee',
                'amount' => 5000,
                'year' => Qs::getCurrentSession(),
                'ref_no' => 'PAY-TEST-001',
            ]);
            $pr = PaymentRecord::create([
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'ref_no' => 'REC-TEST-001',
                'amt_paid' => 0,
                'balance' => 5000,
                'paid' => 0,
                'year' => Qs::getCurrentSession(),
            ]);
        }

        // 1. Invalid or negative payment amount is rejected
        $resInvalidPay = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => -500,
        ]);
        $resInvalidPay->assertStatus(422);

        // Zero payment amount is rejected
        $resZeroPay = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 0,
        ]);
        $resZeroPay->assertStatus(422);

        // 2. Student can access their own invoice
        $resInvoice = $this->actingAs($student)->get(route('payments.invoice', [Qs::hash($student->id), Qs::getCurrentSession()]));
        $resInvoice->assertStatus(200);

        // 3. Student cannot access another student's invoice
        $otherStudent = User::where('user_type', 'student')->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            $resOtherInvoice = $this->actingAs($student)->get(route('payments.invoice', [Qs::hash($otherStudent->id), Qs::getCurrentSession()]));
            $resOtherInvoice->assertStatus(403);
        }

        // 4. Linked parent can access child's invoice; unlinked parent receives 403
        StudentRecord::where('user_id', $student->id)->update(['my_parent_id' => $parent->id]);
        $resParentInvoice = $this->actingAs($parent)->get(route('payments.invoice', [Qs::hash($student->id), Qs::getCurrentSession()]));
        $resParentInvoice->assertStatus(200);

        if ($otherStudent) {
            StudentRecord::where('user_id', $otherStudent->id)->update(['my_parent_id' => null]);
            $resUnlinked = $this->actingAs($parent)->get(route('payments.invoice', [Qs::hash($otherStudent->id), Qs::getCurrentSession()]));
            $resUnlinked->assertStatus(403);
        }
    }

    // ==========================================
    // 6. CLASS & SECTION RELATIONSHIP VALIDATION
    // ==========================================

    public function test_class_section_relationship_validation()
    {
        $sa = $this->getSuperAdmin();
        $student = $this->getStudent();
        $sr = StudentRecord::where('user_id', $student->id)->first();

        $classes = MyClass::take(2)->get();
        if ($classes->count() >= 2) {
            $class1 = $classes[0];
            $class2 = $classes[1];

            $sectionFromClass2 = Section::where('my_class_id', $class2->id)->first();

            if ($sectionFromClass2) {
                // Attempting to assign section from Class 2 while class is Class 1
                $resInvalidSec = $this->actingAs($sa)->putJson(route('students.update', Qs::hash($sr->id)), [
                    'name' => $student->name,
                    'gender' => 'Male',
                    'address' => 'Kathmandu, Nepal',
                    'state_id' => 1,
                    'lga_id' => 1,
                    'nal_id' => 1,
                    'my_class_id' => $class1->id,
                    'section_id' => $sectionFromClass2->id,
                ]);
                $resInvalidSec->assertStatus(422);
            }
        }
    }
}
