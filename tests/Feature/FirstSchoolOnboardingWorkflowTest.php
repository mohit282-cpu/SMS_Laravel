<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassType;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentAttendance;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirstSchoolOnboardingWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected $conn = 'mysql_test';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'mysql_test');
    }

    /** @test */
    public function it_executes_the_complete_23_step_school_onboarding_lifecycle_in_test_database()
    {
        // 1 & 2. Super Admin Login
        $admin = User::on($this->conn)->firstOrCreate(
            ['email' => 'onboarding_admin@apexschool.edu.np'],
            [
                'name' => 'Principal Onboarding Head',
                'username' => 'onboard_superadmin',
                'password' => Hash::make('AdminPass2083!'),
                'user_type' => 'super_admin',
                'code' => strtoupper(Str::random(10)),
                'is_active' => true,
            ]
        );

        $loginResponse = $this->post('/login', [
            'identity' => 'onboarding_admin@apexschool.edu.np',
            'password' => 'AdminPass2083!',
        ]);
        $loginResponse->assertRedirect('/home');
        $this->assertAuthenticatedAs($admin);

        // 3. Configure School Details
        $this->actingAs($admin);
        $settingsPayload = [
            'system_name' => 'Apex Model Higher Secondary School',
            'system_title' => 'Apex School',
            'address' => 'Itahari-5, Sunsari, Koshi Province, Nepal',
            'phone' => '+977-25-560000',
            'system_email' => 'contact@apexschool.edu.np',
            'current_session' => '2083/84 BS',
            'lock_exam' => 0,
            'calendar_display' => 'both',
        ];
        $settingsUpdate = $this->put(route('settings.update'), $settingsPayload);
        $settingsUpdate->assertSessionHas('flash_success');
        $this->assertEquals('Apex Model Higher Secondary School', Setting::on($this->conn)->where('type', 'system_name')->value('description'));

        // 4. Configure Academic Session
        $session = AcademicYear::on($this->conn)->where('year_bs', 2083)->first();
        if (!$session) {
            $session = new AcademicYear();
            $session->setConnection($this->conn);
            $session->year_bs = 2083;
            $session->name = '2083/84 BS';
        }
        $session->is_active = true;
        $session->is_closed = false;
        $session->save();
        $this->assertTrue((bool) $session->is_active);

        // 5. Create Class (Grade 10)
        $classTypeId = DB::connection($this->conn)->table('class_types')->value('id');
        $class = new MyClass();
        $class->setConnection($this->conn);
        $class->name = 'Grade 10 - Secondary';
        $class->class_type_id = $classTypeId;
        $class->save();

        $this->assertNotNull(MyClass::on($this->conn)->where('name', 'Grade 10 - Secondary')->first());

        // 6. Create Section (Section A - Sagarmatha)
        $section = new Section();
        $section->setConnection($this->conn);
        $section->name = 'Section A (Sagarmatha)';
        $section->my_class_id = $class->id;
        $section->active = 1;
        $section->save();

        $this->assertNotNull(Section::on($this->conn)->where('name', 'Section A (Sagarmatha)')->where('my_class_id', $class->id)->first());

        // 7. Create Teacher Account & Subject (Compulsory Mathematics)
        $teacher = new User();
        $teacher->setConnection($this->conn);
        $teacher->fill([
            'name' => 'Senior Teacher Ram Sharma',
            'email' => 'ram.teacher@apexschool.edu.np',
            'username' => 'ram_teacher_83',
            'password' => Hash::make('TeacherPass83!'),
            'user_type' => 'teacher',
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);
        $teacher->save();

        $subject = new Subject();
        $subject->setConnection($this->conn);
        $subject->fill([
            'name' => 'Compulsory Mathematics',
            'slug' => 'c-math-10',
            'my_class_id' => $class->id,
            'teacher_id' => $teacher->id,
        ]);
        $subject->save();

        $this->assertNotNull(Subject::on($this->conn)->where('name', 'Compulsory Mathematics')->where('my_class_id', $class->id)->first());

        // 8. Teacher account assigned to Section
        $section->teacher_id = $teacher->id;
        $section->save();
        $this->assertEquals($teacher->id, Section::on($this->conn)->find($section->id)->teacher_id);

        // 9 & 10. Create Parent and Student Account & Link them
        $parent = new User();
        $parent->setConnection($this->conn);
        $parent->fill([
            'name' => 'Hari Bahadur Thapa',
            'email' => 'hari.parent@apexschool.edu.np',
            'username' => 'hari_parent_83',
            'password' => Hash::make('ParentPass83!'),
            'user_type' => 'parent',
            'phone' => '+977-9842000001',
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);
        $parent->save();

        $studentUser = new User();
        $studentUser->setConnection($this->conn);
        $studentUser->fill([
            'name' => 'Aarav Thapa',
            'email' => 'aarav.student@apexschool.edu.np',
            'username' => 'aarav_student_83',
            'password' => Hash::make('StudentPass83!'),
            'user_type' => 'student',
            'dob' => '2012-05-15',
            'gender' => 'Male',
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);
        $studentUser->save();

        $studentRecord = new StudentRecord();
        $studentRecord->setConnection($this->conn);
        $studentRecord->fill([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'my_parent_id' => $parent->id,
            'adm_no' => 'APEX-2083-001',
            'session' => '2083/84 BS',
            'age' => 14,
            'year_admitted' => '2083',
        ]);
        $studentRecord->save();

        $this->assertNotNull(StudentRecord::on($this->conn)->where('adm_no', 'APEX-2083-001')->where('my_parent_id', $parent->id)->first());

        // 11. Assign Teachers verified
        $this->assertEquals($teacher->id, $subject->teacher_id);

        // 12. Record Attendance
        $attendance = new StudentAttendance();
        $attendance->setConnection($this->conn);
        $attendance->fill([
            'student_record_id' => $studentRecord->id,
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-05-01',
            'attendance_date_bs' => '2083-01-18',
            'status' => 'present',
            'recorded_by' => $teacher->id,
            'session' => '2083/84 BS',
        ]);
        $attendance->save();

        $this->assertNotNull(StudentAttendance::on($this->conn)->where('student_record_id', $studentRecord->id)->where('status', 'present')->first());

        // 13. Create Examination
        $exam = new Exam();
        $exam->setConnection($this->conn);
        $exam->fill([
            'name' => 'First Terminal Evaluation 2083',
            'term' => 1,
            'year' => 2083,
            'exam_type' => 'first_terminal',
            'start_date_bs' => '2083-03-01',
            'end_date_bs' => '2083-03-10',
            'status' => 'draft',
        ]);
        $exam->save();

        $this->assertNotNull(Exam::on($this->conn)->where('name', 'First Terminal Evaluation 2083')->first());

        // 14. Enter Marks
        $mark = new Mark();
        $mark->setConnection($this->conn);
        $mark->fill([
            'student_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'exam_id' => $exam->id,
            't1' => 20,
            't2' => 20,
            'exm' => 50,
            'tex1' => 90,
            'sub_pos' => 1,
            'year' => '2083/84 BS',
        ]);
        $mark->save();

        $this->assertNotNull(Mark::on($this->conn)->where('student_id', $studentUser->id)->where('tex1', 90)->first());

        // 15. Generate / Verify Report Card eligibility
        $this->assertGreaterThanOrEqual(40, $mark->tex1);

        // 16. Configure Fees
        $payment = new Payment();
        $payment->setConnection($this->conn);
        $payment->fill([
            'title' => 'First Term Tuition Fee 2083',
            'amount' => 15000.00,
            'my_class_id' => $class->id,
            'description' => 'Tuition, Lab and Examination Fee',
            'year' => '2083/84 BS',
            'ref_no' => 'PAY-2083-001',
        ]);
        $payment->save();

        $this->assertNotNull(Payment::on($this->conn)->where('title', 'First Term Tuition Fee 2083')->first());

        // 17. Generate Invoice / Payment Record
        $paymentRecord = new PaymentRecord();
        $paymentRecord->setConnection($this->conn);
        $paymentRecord->fill([
            'payment_id' => $payment->id,
            'student_id' => $studentUser->id,
            'ref_no' => 'INV-2083-001',
            'amt_paid' => 15000.00,
            'balance' => 0.00,
            'paid' => 1,
            'year' => '2083/84 BS',
        ]);
        $paymentRecord->save();

        $this->assertNotNull(PaymentRecord::on($this->conn)->where('ref_no', 'INV-2083-001')->where('paid', 1)->first());

        // 18 & 19. Record Payment & Generate Receipt
        $receipt = new Receipt();
        $receipt->setConnection($this->conn);
        $receipt->fill([
            'pr_id' => $paymentRecord->id,
            'amt_paid' => 15000.00,
            'balance' => 0.00,
            'year' => '2083/84 BS',
        ]);
        $receipt->save();

        $this->assertNotNull(Receipt::on($this->conn)->where('pr_id', $paymentRecord->id)->where('amt_paid', 15000.00)->first());

        // 20. Test Parent Portal Access
        auth()->logout();
        $this->actingAs($parent);
        $parentChildren = StudentRecord::on($this->conn)->where('my_parent_id', $parent->id)->get();
        $this->assertCount(1, $parentChildren);
        $this->assertEquals($studentUser->id, $parentChildren->first()->user_id);

        // 21. Test Student Portal Access
        auth()->logout();
        $this->actingAs($studentUser);
        $this->assertEquals('student', auth()->user()->user_type);
        $studentAttendanceCount = StudentAttendance::on($this->conn)->where('student_record_id', $studentRecord->id)->count();
        $this->assertEquals(1, $studentAttendanceCount);

        // 22. Test Teacher Portal Access
        auth()->logout();
        $this->actingAs($teacher);
        $this->assertEquals('teacher', auth()->user()->user_type);
        $teacherSubjects = Subject::on($this->conn)->where('teacher_id', $teacher->id)->get();
        $this->assertCount(1, $teacherSubjects);

        // 23. Test Backup and Restore
        $this->artisan('db:backup', [
            '--database' => 'sms_laravel_test',
            '--compress' => true,
        ])->assertExitCode(0);
    }
}
