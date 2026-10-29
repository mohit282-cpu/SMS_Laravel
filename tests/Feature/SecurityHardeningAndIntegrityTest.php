<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningAndIntegrityTest extends TestCase
{
    protected function getAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getTeacher()
    {
        return User::where('user_type', 'teacher')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    protected function getParent()
    {
        return User::where('user_type', 'parent')->first();
    }

    /**
     * 1. Teacher cannot access marks management or update marks for an unassigned class/subject.
     */
    public function test_unassigned_teacher_cannot_manage_or_update_marks()
    {
        $teacher = $this->getTeacher();
        $myClass = MyClass::first();
        $section = Section::first();
        $exam = Exam::first();

        // Create or find a subject not assigned to this teacher
        $otherTeacher = User::where('user_type', 'teacher')->where('id', '!=', $teacher->id)->first();
        if (!$otherTeacher) {
            $otherTeacher = User::create([
                'name' => 'Other Teacher',
                'username' => 'other_teacher_' . rand(100, 999),
                'email' => 'other_' . rand(100, 999) . '@test.com',
                'password' => bcrypt('password'),
                'user_type' => 'teacher',
                'code' => strtoupper(\Illuminate\Support\Str::random(10)),
            ]);
        }

        $unassignedSubject = Subject::create([
            'name' => 'Unassigned Subject ' . rand(100, 999),
            'slug' => 'unassigned-' . rand(100, 999),
            'my_class_id' => $myClass->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $student = $this->getStudent();
        Mark::firstOrCreate([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $unassignedSubject->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        // Attempt manage view -> expect 403 Forbidden
        $response = $this->actingAs($teacher)->get(route('marks.manage', [$exam->id, $myClass->id, $section->id, $unassignedSubject->id]));
        $response->assertStatus(403);

        // Attempt update -> expect 403 Forbidden
        $responseUpdate = $this->actingAs($teacher)->put(route('marks.update', [$exam->id, $myClass->id, $section->id, $unassignedSubject->id]), [
            'theory_1' => 50,
        ]);
        $responseUpdate->assertStatus(403);
    }

    /**
     * 2. Teacher cannot update comments or skills for unassigned section.
     */
    public function test_teacher_cannot_update_comments_for_unassigned_section()
    {
        $teacher = $this->getTeacher();
        $exam = Exam::first();
        $myClass = MyClass::first();

        $otherTeacher = User::where('user_type', 'teacher')->where('id', '!=', $teacher->id)->first();
        if (!$otherTeacher) {
            $otherTeacher = User::create([
                'name' => 'Other Teacher 2',
                'username' => 'other_teacher2_' . rand(100, 999),
                'email' => 'other2_' . rand(100, 999) . '@test.com',
                'password' => bcrypt('password'),
                'user_type' => 'teacher',
                'code' => strtoupper(\Illuminate\Support\Str::random(10)),
            ]);
        }

        // Create a section with a different teacher
        $otherSection = Section::create([
            'name' => 'Sec ' . rand(100, 999),
            'my_class_id' => $myClass->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $exr = ExamRecord::create([
            'exam_id' => $exam->id,
            'student_id' => $this->getStudent()->id,
            'my_class_id' => $myClass->id,
            'section_id' => $otherSection->id,
            'year' => $exam->year,
        ]);

        // Attempt comment update -> expect 403 Forbidden
        $response = $this->actingAs($teacher)->put(route('marks.comment_update', $exr->id), [
            't_comment' => 'Unauthorized comment',
        ]);
        $response->assertStatus(403);

        // Attempt skills update -> expect 403 Forbidden
        $responseSkills = $this->actingAs($teacher)->put(route('marks.skills_update', ['AF', $exr->id]), [
            'af' => [1, 2, 3],
        ]);
        $responseSkills->assertStatus(403);
    }

    /**
     * 3. Student cannot access another student's year_selector.
     */
    public function test_student_cannot_access_another_student_year_selector()
    {
        $student1 = $this->getStudent();

        $student2 = User::where('user_type', 'student')->where('id', '!=', $student1->id)->first();
        if (!$student2) {
            $student2 = User::create([
                'name' => 'Second Student',
                'username' => 'student2_' . rand(100, 999),
                'email' => 'student2_' . rand(100, 999) . '@test.com',
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(\Illuminate\Support\Str::random(10)),
            ]);
        }

        // Student 1 tries to access Student 2's selector
        $response = $this->actingAs($student1)->get(route('marks.year_selector', Qs::hash($student2->id)));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('pop_error');
    }

    /**
     * 4. Student and parent cannot view unpublished exam results.
     */
    public function test_student_and_parent_cannot_view_unpublished_exam_results()
    {
        $student = $this->getStudent();
        $exam = Exam::create([
            'name' => 'Unpublished Exam ' . rand(100, 999),
            'term' => 2,
            'year' => Qs::getCurrentSession(),
            'status' => 'draft',
        ]);

        $myClass = MyClass::first();
        $section = Section::first();
        $subject = Subject::first();

        Mark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
            'total_mark' => 90,
        ]);

        ExamRecord::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year' => $exam->year,
        ]);

        // Student tries to view print_view of unpublished exam
        $response = $this->actingAs($student)->get(route('marks.print', [Qs::hash($student->id), $exam->id, $exam->year]));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash_danger');
    }

    /**
     * 5. Payment pay_now rejects overpayments, zero, and negative amounts.
     */
    public function test_pay_now_rejects_overpayment_and_invalid_amounts()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Tuition Fee ' . rand(100, 999),
            'amount' => 5000,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . rand(1000, 9999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . rand(1000, 9999),
            'amt_paid' => 1000,
            'balance' => 4000,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        // Outstanding is 4000. Try paying 5000 (overpayment)
        $responseOver = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 5000,
        ]);
        $responseOver->assertStatus(422);
        $this->assertStringContainsString('exceeds the outstanding balance', $responseOver->json('msg'));

        // Try paying zero
        $responseZero = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 0,
        ]);
        $responseZero->assertStatus(422);

        // Try paying negative amount
        $responseNeg = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => -500,
        ]);
        $responseNeg->assertStatus(422);

        // Valid partial payment of 2000
        $responseValid = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 2000,
        ]);
        $responseValid->assertStatus(200);

        $pr->refresh();
        $this->assertEquals(3000.0, (float)$pr->amt_paid);
        $this->assertEquals(2000.0, (float)$pr->balance);
        $this->assertEquals(0, $pr->paid);

        // Pay exact balance of 2000
        $responseExact = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 2000,
        ]);
        $responseExact->assertStatus(200);

        $pr->refresh();
        $this->assertEquals(5000.0, (float)$pr->amt_paid);
        $this->assertEquals(0.0, (float)$pr->balance);
        $this->assertEquals(1, $pr->paid);

        // Attempt payment on fully paid invoice
        $responseAlreadyPaid = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 100,
        ]);
        $responseAlreadyPaid->assertStatus(422);
    }

    /**
     * 6. Payment pay_now records receipt with invoice's academic session year.
     */
    public function test_pay_now_preserves_invoice_academic_year()
    {
        $admin = $this->getAdmin();
        $student = $this->getStudent();
        $historicalYear = '2019-2020';

        $payment = Payment::create([
            'title' => 'Historical Fee ' . rand(100, 999),
            'amount' => 3000,
            'year' => $historicalYear,
            'ref_no' => 'PAY_' . rand(1000, 9999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . rand(1000, 9999),
            'amt_paid' => 0,
            'balance' => 3000,
            'paid' => 0,
            'year' => $historicalYear,
        ]);

        $response = $this->actingAs($admin)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1500,
        ]);
        $response->assertStatus(200);

        $receipt = Receipt::where('pr_id', $pr->id)->latest()->first();
        $this->assertNotNull($receipt);
        $this->assertEquals($historicalYear, $receipt->year);
    }

    /**
     * 7. Study material rejects mismatched class and section.
     */
    public function test_study_material_rejects_mismatched_class_and_section()
    {
        $admin = $this->getAdmin();
        Storage::fake('local');

        $classes = MyClass::all();
        $class1 = $classes->first();
        $class2 = $classes->last();

        $sectionOfClass2 = Section::firstOrCreate([
            'name' => 'Sec B',
            'my_class_id' => $class2->id,
        ]);

        $file = UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('study_materials.store'), [
            'title' => 'Mismatched Section Material',
            'my_class_id' => $class1->id,
            'section_id' => $sectionOfClass2->id,
            'material_file' => $file,
        ]);

        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('study_materials', ['title' => 'Mismatched Section Material']);
    }

    /**
     * 8. Study material rejects upload from unassigned teacher.
     */
    public function test_study_material_rejects_unassigned_teacher()
    {
        $teacher = $this->getTeacher();
        Storage::fake('local');

        // Create an unassigned class where teacher teaches nothing
        $classType = \App\Models\ClassType::first() ?? \App\Models\ClassType::create(['name' => 'General', 'code' => 'GEN']);
        $myClass = MyClass::create([
            'name' => 'Foreign Class ' . rand(100, 999),
            'class_type_id' => $classType->id,
        ]);

        $file = UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('study_materials.store'), [
            'title' => 'Teacher Unassigned Material',
            'my_class_id' => $myClass->id,
            'material_file' => $file,
        ]);

        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('study_materials', ['title' => 'Teacher Unassigned Material']);
    }

    /**
     * 9. Study material rejects executable and script uploads.
     */
    public function test_study_material_rejects_executable_uploads()
    {
        $admin = $this->getAdmin();
        Storage::fake('local');

        $myClass = MyClass::first();
        $file = UploadedFile::fake()->create('exploit.php', 100, 'text/x-php');

        $response = $this->actingAs($admin)->post(route('study_materials.store'), [
            'title' => 'Malicious File',
            'my_class_id' => $myClass->id,
            'material_file' => $file,
        ]);

        $this->assertDatabaseMissing('study_materials', ['title' => 'Malicious File']);
    }

    /**
     * 10. Notice rejects mismatched class and section.
     */
    public function test_notice_rejects_mismatched_class_and_section()
    {
        $admin = $this->getAdmin();

        $classes = MyClass::all();
        $class1 = $classes->first();
        $class2 = $classes->last();

        $sectionOfClass2 = Section::firstOrCreate([
            'name' => 'Sec B',
            'my_class_id' => $class2->id,
        ]);

        $response = $this->actingAs($admin)->post(route('notices.store'), [
            'title' => 'Mismatched Notice',
            'content' => 'Content here',
            'my_class_id' => $class1->id,
            'section_id' => $sectionOfClass2->id,
        ]);

        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('notices', ['title' => 'Mismatched Notice']);
    }

    /**
     * 11. Notice rejects invalid BS dates and expiry before notice date.
     */
    public function test_notice_rejects_invalid_bs_and_inconsistent_dates()
    {
        $admin = $this->getAdmin();

        // 1. Invalid BS date
        $responseInvalidBs = $this->actingAs($admin)->post(route('notices.store'), [
            'title' => 'Invalid BS Notice',
            'content' => 'Content here',
            'notice_date_bs' => '9999-99-99',
        ]);
        $responseInvalidBs->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('notices', ['title' => 'Invalid BS Notice']);

        // 2. Expiry date before notice date
        $responseExpiryBefore = $this->actingAs($admin)->post(route('notices.store'), [
            'title' => 'Inconsistent Dates Notice',
            'content' => 'Content here',
            'notice_date' => '2026-10-25',
            'expiry_date' => '2026-10-20',
        ]);
        $responseExpiryBefore->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('notices', ['title' => 'Inconsistent Dates Notice']);
    }

    /**
     * 12. Expired and unpublished notices are excluded from student visibility.
     */
    public function test_expired_and_unpublished_notices_hidden_from_students()
    {
        $student = $this->getStudent();

        // 1. Expired notice
        $expiredNotice = Notice::create([
            'title' => 'Expired Notice ' . rand(100, 999),
            'content' => 'Expired content',
            'notice_date' => '2026-01-01',
            'expiry_date' => '2026-01-05',
            'is_published' => true,
            'role_visibility' => 'all',
        ]);

        // 2. Unpublished notice
        $unpublishedNotice = Notice::create([
            'title' => 'Unpublished Notice ' . rand(100, 999),
            'content' => 'Draft content',
            'notice_date' => now()->toDateString(),
            'is_published' => false,
            'role_visibility' => 'all',
        ]);

        $visibleNotices = Notice::visibleTo($student)->pluck('id')->toArray();
        $this->assertNotContains($expiredNotice->id, $visibleNotices);
        $this->assertNotContains($unpublishedNotice->id, $visibleNotices);

        // Attempt direct access via show route
        $responseExp = $this->actingAs($student)->get(route('notices.show', $expiredNotice->id));
        $responseExp->assertStatus(404);

        $responseUnpub = $this->actingAs($student)->get(route('notices.show', $unpublishedNotice->id));
        $responseUnpub->assertStatus(404);
    }
}
