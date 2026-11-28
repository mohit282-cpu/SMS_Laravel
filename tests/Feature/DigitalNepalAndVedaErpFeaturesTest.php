<?php

namespace Tests\Feature;

use App\Models\ComplaintFeedback;
use App\Models\Dorm;
use App\Models\Exam;
use App\Models\FinanceLedger;
use App\Models\FiscalYear;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\MyClass;
use App\Models\NotificationLog;
use App\Models\OnlineQuiz;
use App\Models\PortalMessage;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\StaffPayroll;
use App\Models\StudentCertificate;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Models\Survey;
use App\Models\TransportRoute;
use App\Models\VisitorLog;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalNepalAndVedaErpFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test User',
            'email' => $role . '_' . uniqid() . '@sms.test',
            'username' => $role . '_' . uniqid(),
            'user_type' => $role,
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    protected function makeStudent(int $classId, int $sectionId, ?int $parentId = null): StudentRecord
    {
        $u = $this->makeUser('student');

        return StudentRecord::create([
            'user_id' => $u->id,
            'my_class_id' => $classId,
            'section_id' => $sectionId,
            'my_parent_id' => $parentId,
            'adm_no' => 'ERP-' . strtoupper(Str::random(6)),
            'session' => \App\Helpers\Qs::getCurrentSession(),
            'year_admitted' => '2082',
        ]);
    }

    public function test_all_new_erp_routes_render_cleanly_for_super_admin()
    {
        $admin = $this->makeUser('super_admin');

        $routes = [
            'homework.index',
            'lesson_plans.index',
            'elearning.classes',
            'elearning.quizzes',
            'elearning.forum',
            'nepal_emis.index',
            'leaves.index',
            'wellness.index',
            'certificates.index',
            'documents.id_cards',
            'documents.admit_cards',
            'accounting.index',
            'payroll.index',
            'inventory.index',
            'transport.index',
            'visitors.index',
            'feedback.index',
            'hostel_canteen.index',
            'messages.index',
            'surveys.index',
            'communications.index',
        ];

        foreach ($routes as $name) {
            $resp = $this->actingAs($admin)->get(route($name));
            $this->assertEquals(200, $resp->getStatusCode(), "Route [{$name}] failed with HTTP {$resp->getStatusCode()}.");
        }
    }

    public function test_homework_submission_and_teacher_evaluation_workflow()
    {
        $class = MyClass::firstOrFail();
        $section = Section::where('my_class_id', $class->id)->firstOrFail();
        $subject = Subject::where('my_class_id', $class->id)->first();
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $sr = $this->makeStudent($class->id, $section->id, $parent->id);
        $studentUser = User::findOrFail($sr->user_id);

        $this->actingAs($teacher)->post(route('homework.store'), [
            'title' => 'Science Photosynthesis Sheet',
            'description' => 'Solve questions 1-5.',
            'homework_type' => 'assignment',
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject ? $subject->id : null,
            'assigned_date_bs' => '2083-08-12',
            'due_date_bs' => '2083-08-15',
            'max_marks' => 20,
        ])->assertRedirect(route('homework.index'));

        $hw = Homework::latest('id')->firstOrFail();

        $this->actingAs($studentUser)->post(route('homework.submit', $hw->id), [
            'submission_text' => 'Completed all five questions neatly.',
        ])->assertSessionHasNoErrors();

        $sub = HomeworkSubmission::where('homework_id', $hw->id)->where('student_record_id', $sr->id)->firstOrFail();

        // Parent cannot evaluate homework submissions
        $this->actingAs($parent)->post(route('homework.evaluate', $sub->id), [
            'status' => 'evaluated',
            'marks_obtained' => 20,
        ])->assertStatus(403);

        // Teacher evaluates cleanly
        $this->actingAs($teacher)->post(route('homework.evaluate', $sub->id), [
            'status' => 'evaluated',
            'marks_obtained' => 18.5,
            'teacher_remarks' => 'Great diagram labeling!',
        ])->assertSessionHasNoErrors();

        $this->assertEquals('18.50', $sub->fresh()->marks_obtained);
        $this->assertEquals('evaluated', $sub->fresh()->status);
    }

    public function test_online_quiz_autograding_and_lesson_plan_workflow()
    {
        $class = MyClass::firstOrFail();
        $section = Section::where('my_class_id', $class->id)->firstOrFail();
        $teacher = $this->makeUser('teacher');
        $sr = $this->makeStudent($class->id, $section->id);
        $studentUser = User::findOrFail($sr->user_id);

        $this->actingAs($teacher)->post(route('lesson_plans.store'), [
            'my_class_id' => $class->id,
            'unit_title' => 'Unit 1 Algebra',
            'lesson_topic' => 'Linear Equations',
            'planned_date_bs' => '2083-08-10',
        ])->assertSessionHasNoErrors();

        $this->actingAs($teacher)->post(route('elearning.quizzes.store'), [
            'title' => 'Algebra Quick Check',
            'my_class_id' => $class->id,
            'duration_minutes' => 15,
            'pass_marks' => 50,
            'is_published' => 1,
        ]);

        $quiz = OnlineQuiz::latest('id')->firstOrFail();
        $this->actingAs($teacher)->post(route('elearning.quizzes.questions.add', $quiz->id), [
            'question_text' => 'What is 2 + 2?',
            'option_a' => '3',
            'option_b' => '4',
            'option_c' => '5',
            'option_d' => '6',
            'correct_option' => 'b',
            'marks' => 5,
        ])->assertSessionHasNoErrors();

        $question = $quiz->questions()->firstOrFail();
        $this->actingAs($studentUser)->post(route('elearning.quizzes.attempt', $quiz->id), [
            'answers' => [$question->id => 'b'],
        ])->assertSessionHasNoErrors();

        $attempt = QuizAttempt::where('online_quiz_id', $quiz->id)->where('student_record_id', $sr->id)->firstOrFail();
        $this->assertEquals(5.0, (float) $attempt->score);
        $this->assertEquals(100.0, (float) $attempt->percentage);
        $this->assertTrue($attempt->passed);
    }

    public function test_leave_management_enforces_ownership_and_staff_review_boundaries()
    {
        $class = MyClass::firstOrFail();
        $section = Section::where('my_class_id', $class->id)->firstOrFail();
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $ownChild = $this->makeStudent($class->id, $section->id, $parent->id);
        $otherChild = $this->makeStudent($class->id, $section->id);

        // Parent cannot file leave for unrelated student
        $this->actingAs($parent)->post(route('leaves.store'), [
            'leave_scope' => 'student',
            'student_record_id' => $otherChild->id,
            'leave_type' => 'sick',
            'from_date_bs' => '2083-08-12',
            'to_date_bs' => '2083-08-13',
            'reason' => 'Unauthorized',
        ])->assertStatus(403);

        // Parent files leave for own child, teacher approves
        $this->actingAs($parent)->post(route('leaves.store'), [
            'leave_scope' => 'student',
            'student_record_id' => $ownChild->id,
            'leave_type' => 'sick',
            'from_date_bs' => '2083-08-12',
            'to_date_bs' => '2083-08-13',
            'reason' => 'Fever recovery',
        ])->assertSessionHasNoErrors();

        $studentLeave = LeaveRequest::where('student_record_id', $ownChild->id)->firstOrFail();
        $this->assertEquals(2, $studentLeave->total_days);

        $this->actingAs($teacher)->post(route('leaves.review', $studentLeave->id), [
            'status' => 'approved',
        ])->assertSessionHasNoErrors();
        $this->assertEquals('approved', $studentLeave->fresh()->status);

        // Teacher files staff leave; another teacher cannot approve staff leave, admin can
        $this->actingAs($teacher)->post(route('leaves.store'), [
            'leave_scope' => 'staff',
            'leave_type' => 'casual',
            'from_date_bs' => '2083-08-14',
            'to_date_bs' => '2083-08-14',
            'reason' => 'Family work',
        ])->assertSessionHasNoErrors();

        $staffLeave = LeaveRequest::where('leave_scope', 'staff')->where('user_id', $teacher->id)->firstOrFail();
        $this->actingAs($teacher)->post(route('leaves.review', $staffLeave->id), ['status' => 'approved'])->assertStatus(403);
        $this->actingAs($admin)->post(route('leaves.review', $staffLeave->id), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertEquals('approved', $staffLeave->fresh()->status);
    }

    public function test_accounting_payroll_ledger_posting_and_inventory_stock_guard()
    {
        $admin = $this->makeUser('super_admin');
        $teacher = $this->makeUser('teacher');

        FiscalYear::create([
            'name' => 'FY Test ' . uniqid(),
            'start_date_bs' => '2083-04-01',
            'end_date_bs' => '2084-03-31',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('accounting.ledger.store'), [
            'entry_type' => 'income',
            'voucher_type' => 'receipt',
            'category' => 'Admission Form Fee',
            'account_head' => 'cash',
            'amount' => 1500,
        ])->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertTrue(FinanceLedger::where('category', 'Admission Form Fee')->exists());

        $class = MyClass::firstOrFail();
        $section = Section::where('my_class_id', $class->id)->firstOrFail();
        $sr = $this->makeStudent($class->id, $section->id);
        $this->actingAs($admin)->post(route('accounting.concessions.store'), [
            'student_record_id' => $sr->id,
            'title' => 'Topper Scholarship',
            'concession_type' => 'percentage',
            'amount_or_percent' => 25,
        ])->assertStatus(302)->assertSessionHasNoErrors();

        // Generate payroll & mark paid -> auto-creates FinanceLedger expense
        $this->actingAs($admin)->post(route('payroll.store'), [
            'user_id' => $teacher->id,
            'salary_month_bs' => '2083-08',
            'basic_salary' => 40000,
            'allowances' => 5000,
            'provident_fund' => 2000,
            'tax_amount' => 1000,
            'deductions' => 0,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasNoErrors();

        $payroll = StaffPayroll::where('user_id', $teacher->id)->where('salary_month_bs', '2083-08')->firstOrFail();
        $this->assertEquals('42000.00', $payroll->net_salary);

        $this->actingAs($admin)->post(route('payroll.mark_paid', $payroll->id))->assertSessionHasNoErrors();
        $this->assertEquals('paid', $payroll->fresh()->payment_status);
        $this->assertTrue(FinanceLedger::where('reference_no', 'PAYROLL-' . $payroll->id)->exists());

        // Inventory purchase & issue stock guard
        $sku = 'SKU-' . strtoupper(Str::random(6));
        $this->actingAs($admin)->post(route('inventory.items.store'), [
            'item_code' => $sku,
            'name' => 'Whiteboard Markers',
            'unit' => 'box',
            'current_stock' => 5,
            'reorder_level' => 3,
            'unit_price' => 250,
        ])->assertSessionHasNoErrors();

        $item = InventoryItem::where('item_code', $sku)->firstOrFail();
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'inventory_item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'inventory_item_id' => $item->id,
            'transaction_type' => 'issue',
            'quantity' => 4,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(11, $item->fresh()->current_stock);

        // Insufficient stock blocked with 422
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'inventory_item_id' => $item->id,
            'transaction_type' => 'issue',
            'quantity' => 50,
        ])->assertStatus(422);
    }

    public function test_certificates_transport_visitors_feedback_and_communication_flows()
    {
        $admin = $this->makeUser('super_admin');
        $parent = $this->makeUser('parent');
        $class = MyClass::firstOrFail();
        $section = Section::where('my_class_id', $class->id)->firstOrFail();
        $sr = $this->makeStudent($class->id, $section->id, $parent->id);
        $dorm = Dorm::first() ?: Dorm::create(['name' => 'Test Dorm']);

        // Certificate issue & verify lookup
        $this->actingAs($admin)->post(route('certificates.store'), [
            'student_record_id' => $sr->id,
            'certificate_type' => 'character',
            'issued_date_bs' => '2083-08-12',
        ])->assertSessionHasNoErrors();

        $cert = StudentCertificate::where('student_record_id', $sr->id)->latest('id')->firstOrFail();
        $verifyResp = $this->actingAs($parent)->get(route('certificates.index', ['verify_code' => $cert->verification_code]));
        $verifyResp->assertStatus(200)->assertSee('VERIFIED CERTIFICATE')->assertSee($cert->certificate_number);

        // Transport route, assignment & bus attendance
        $this->actingAs($admin)->post(route('transport.routes.store'), [
            'name' => 'Ringroad North Bus',
            'vehicle_number' => 'Ba 1 Ka 9901',
            'monthly_fee' => 2000,
        ])->assertSessionHasNoErrors();
        $route = TransportRoute::latest('id')->firstOrFail();

        $this->actingAs($admin)->post(route('transport.assign.store'), [
            'student_record_id' => $sr->id,
            'transport_route_id' => $route->id,
            'pickup_point' => 'Chabahil Chowk',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('transport.attendance.store'), [
            'transport_route_id' => $route->id,
            'student_record_id' => $sr->id,
            'trip_type' => 'pickup',
            'status' => 'boarded',
        ])->assertSessionHasNoErrors();

        // Hostel allocation
        $this->actingAs($admin)->post(route('hostel_canteen.hostel.store'), [
            'student_record_id' => $sr->id,
            'dorm_id' => $dorm->id,
            'room_no' => 'R-201',
            'monthly_fee' => 5500,
        ])->assertSessionHasNoErrors();

        // Visitor check-in & check-out
        $this->actingAs($admin)->post(route('visitors.store'), [
            'visitor_name' => 'Hari Bahadur',
            'visitor_type' => 'parent',
            'student_record_id' => $sr->id,
            'purpose' => 'Fee Inquiry',
        ])->assertStatus(302)->assertSessionHasNoErrors();
        $visitor = VisitorLog::latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('visitors.checkout', $visitor->id))->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertNotNull($visitor->fresh()->check_out_time);

        // Printable Certificate, ID Cards & Admit Cards + Wellness Log + Groups + EMIS + E-Learning Online Class/Forum
        $this->actingAs($admin)->get(route('certificates.print', $cert->id))->assertStatus(200);
        $this->actingAs($admin)->get(route('documents.id_cards', ['mode' => 'student']))->assertStatus(200);
        $this->actingAs($admin)->get(route('documents.id_cards', ['mode' => 'staff']))->assertStatus(200);
        $this->actingAs($admin)->get(route('documents.admit_cards'))->assertStatus(200);

        $this->actingAs($admin)->post(route('wellness.logs.store'), [
            'student_record_id' => $sr->id,
            'category' => 'merit',
            'title' => 'Science Fair Winner',
            'points' => 10,
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('elearning.classes.store'), [
            'title' => 'Grade 10 Physics Live',
            'platform' => 'zoom',
            'meeting_url' => 'https://zoom.us/j/123456789',
            'my_class_id' => $class->id,
            'start_time' => '10:00 AM',
            'duration_minutes' => 45,
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('elearning.forum.store'), [
            'title' => 'Exam Preparation Tips',
            'body' => 'Share chapter notes here.',
            'my_class_id' => $class->id,
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('nepal_emis.profile.update'), [
            'name' => 'Apex Model Secondary School',
            'emis_code' => '270310001',
            'school_type' => 'institutional',
            'boarding_type' => 'both',
            'calendar_preference' => 'both',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        // Authorization boundaries for Accountant vs Student
        $accountant = $this->makeUser('accountant');
        $studentUser = User::findOrFail($sr->user_id);
        $this->actingAs($accountant)->get(route('accounting.index'))->assertStatus(200);
        $this->actingAs($accountant)->get(route('payroll.index'))->assertStatus(200);
        $this->actingAs($studentUser)->get(route('accounting.index'))->assertStatus(403);
        $this->actingAs($studentUser)->get(route('inventory.index'))->assertStatus(302);
        $this->actingAs($studentUser)->get(route('visitors.index'))->assertStatus(403);

        // Direct messaging & feedback & survey & broadcast
        $this->actingAs($parent)->post(route('messages.send'), [
            'recipient_id' => $admin->id,
            'subject' => 'Bus Pickup Query',
            'body' => 'Could pickup time be confirmed?',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(1, PortalMessage::where('recipient_id', $admin->id)->where('sender_id', $parent->id)->count());

        $this->actingAs($parent)->post(route('feedback.store'), [
            'entry_type' => 'feedback',
            'student_record_id' => $sr->id,
            'subject' => 'Appreciation for science lab',
            'description' => 'Very well organized.',
            'priority' => 'low',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('surveys.store'), [
            'title' => 'Parent Term Satisfaction',
            'target_role' => 'all',
            'question_1' => 'How satisfied are you?',
        ])->assertSessionHasNoErrors();
        $survey = Survey::latest('id')->firstOrFail();
        $this->actingAs($parent)->post(route('surveys.respond', $survey->id), [
            'rating' => 5,
            'answer_1' => 'Very satisfied',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('communications.broadcast'), [
            'channel' => 'in_app',
            'category' => 'notice',
            'target_role' => 'parent',
            'title' => 'Exam Schedule Update',
            'message' => 'Please review the updated exam routine.',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(NotificationLog::where('title', 'Exam Schedule Update')->exists());

        // Nepal EMIS JSON export
        $this->actingAs($admin)->get(route('nepal_emis.export_json'))
            ->assertStatus(200)
            ->assertJsonStructure(['generated_at', 'enrollment', 'staffing']);
    }
}
