<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Setting;
use App\Models\StudentRecord;
use App\Services\NepaliDateService;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NepaliMonthlyBillingTest extends TestCase
{
    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);
    }

    protected function getAccountant(): User
    {
        return User::where('user_type', 'accountant')->first()
            ?: User::factory()->create(['user_type' => 'accountant']);
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first()
            ?: User::factory()->create(['user_type' => 'teacher']);
    }

    protected function getStudent(): User
    {
        $sr = StudentRecord::with('user')->first();
        if ($sr && $sr->user) {
            return $sr->user;
        }

        $user = User::factory()->create(['user_type' => 'student']);
        $myClass = MyClass::first() ?: MyClass::create(['name' => 'Class 1', 'class_type_id' => 1]);
        StudentRecord::create([
            'user_id' => $user->id,
            'my_class_id' => $myClass->id,
            'section_id' => 1,
            'adm_no' => 'ADM_' . rand(1000, 9999),
            'session' => Qs::getCurrentSession(),
        ]);
        return $user;
    }

    /**
     * Test authorized accountant can view the monthly fee billing dashboard.
     */
    public function test_accountant_can_view_monthly_billing_dashboard()
    {
        $accountant = $this->getAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.monthly_billing', [
            'bs_year' => 2083,
            'bs_month' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nepali 12-Month Billing Cycle');
        $response->assertSee('Baisakh');
    }

    /**
     * Test unauthorized users (student, teacher) cannot access monthly fee billing dashboard.
     */
    public function test_unauthorized_users_cannot_access_monthly_billing()
    {
        $student = $this->getStudent();
        $response = $this->actingAs($student)->get(route('payments.monthly_billing'));
        $response->assertStatus(403);

        $teacher = $this->getTeacher();
        $response2 = $this->actingAs($teacher)->get(route('payments.monthly_billing'));
        $response2->assertStatus(403);
    }

    /**
     * Test generating monthly fee invoices for an eligible class.
     */
    public function test_generate_monthly_invoices_for_bs_period()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();
        $classId = $student->student_record->my_class_id;

        $response = $this->actingAs($accountant)->post(route('payments.generate_monthly_invoices'), [
            'bs_year' => 2083,
            'bs_month' => 2, // Jestha
            'my_class_id' => $classId,
            'fee_category' => 'tuition',
            'amount' => 2500,
            'due_day' => 15,
        ]);

        $response->assertSessionHas('flash_success');

        // Verify Payment structure was created
        $payment = Payment::where('period_type', 'monthly')
            ->where('fee_category', 'tuition')
            ->where('bs_year', 2083)
            ->where('bs_month', 2)
            ->where('my_class_id', $classId)
            ->first();

        $this->assertNotNull($payment);
        $this->assertEquals(2500, $payment->amount);
        $this->assertEquals('2083-02-15', $payment->due_date_bs);
        $this->assertNotNull($payment->due_date); // Canonical AD due date

        // Verify PaymentRecord invoice was created for the student
        $pr = PaymentRecord::where('payment_id', $payment->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($pr);
        $this->assertEquals(0, $pr->paid);
        $this->assertEquals(2500, $pr->balance);
        $this->assertEquals(2083, $pr->bs_year);
        $this->assertEquals(2, $pr->bs_month);
    }

    /**
     * Test idempotency: re-running invoice generation for same BS period does not duplicate invoices.
     */
    public function test_idempotent_monthly_invoice_generation_prevents_duplicate_charges()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();
        $classId = $student->student_record->my_class_id;

        $payload = [
            'bs_year' => 2083,
            'bs_month' => 4, // Shrawan
            'my_class_id' => $classId,
            'fee_category' => 'tuition',
            'amount' => 3000,
            'due_day' => 15,
        ];

        // First execution
        $this->actingAs($accountant)->post(route('payments.generate_monthly_invoices'), $payload);

        $initialPaymentCount = Payment::where('bs_year', 2083)->where('bs_month', 4)->where('my_class_id', $classId)->count();
        $initialRecordCount = PaymentRecord::where('bs_year', 2083)->where('bs_month', 4)->count();

        $this->assertGreaterThan(0, $initialPaymentCount);
        $this->assertGreaterThan(0, $initialRecordCount);

        // Second execution with identical parameters (repeating the generation request)
        $secondResponse = $this->actingAs($accountant)->post(route('payments.generate_monthly_invoices'), $payload);
        $secondResponse->assertSessionHas('flash_success');

        // Verify counts remain identical - zero duplicates!
        $afterPaymentCount = Payment::where('bs_year', 2083)->where('bs_month', 4)->where('my_class_id', $classId)->count();
        $afterRecordCount = PaymentRecord::where('bs_year', 2083)->where('bs_month', 4)->count();

        $this->assertEquals($initialPaymentCount, $afterPaymentCount, 'Payment structures were duplicated!');
        $this->assertEquals($initialRecordCount, $afterRecordCount, 'Student invoices were duplicated!');
    }

    /**
     * Test monthly billing across all 12 BS months and multiple BS years.
     */
    public function test_monthly_billing_across_all_12_bs_months_and_multiple_years()
    {
        $service = app(NepaliDateService::class);
        $years = [2083, 2084];

        foreach ($years as $yr) {
            for ($m = 1; $m <= 12; $m++) {
                $range = $service->getBsMonthAdRange($yr, $m);
                $this->assertEquals($yr, $range['bs_year']);
                $this->assertEquals($m, $range['bs_month']);
                $this->assertNotEmpty($range['month_name_en']);
                $this->assertNotEmpty($range['month_name_ne']);
                $this->assertNotEmpty($range['start_ad']);
                $this->assertNotEmpty($range['end_ad']);
                $this->assertTrue($range['start_ad'] < $range['end_ad']);
            }
        }
    }

    /**
     * Test payment flow on monthly invoices: partial payment, exact balance calculation, and receipt generation.
     */
    public function test_partial_payment_and_balance_calculation_on_monthly_invoice()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Monthly Tuition Fee - Kartik 2083 BS',
            'amount' => 4000.00,
            'period_type' => 'monthly',
            'fee_category' => 'tuition',
            'bs_year' => 2083,
            'bs_month' => 7,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 4000.00,
            'paid' => 0,
            'bs_year' => 2083,
            'bs_month' => 7,
            'year' => $payment->year,
        ]);

        // 1. Partial payment of 1500.00
        $hashId = Qs::hash($pr->id);
        $response = $this->actingAs($accountant)->post(route('payments.pay_now', $hashId), [
            'amt_paid' => 1500.00,
        ]);

        $response->assertJson(['ok' => true]);

        $pr->refresh();
        $this->assertEquals(1500.00, (float)$pr->amt_paid);
        $this->assertEquals(2500.00, (float)$pr->balance);
        $this->assertEquals(0, $pr->paid);

        // Verify receipt created
        $receipt = $pr->receipt()->first();
        $this->assertNotNull($receipt);
        $this->assertEquals(1500.00, (float)$receipt->amt_paid);
        $this->assertEquals(2500.00, (float)$receipt->balance);

        // 2. Complete the remaining balance of 2500.00
        $response2 = $this->actingAs($accountant)->post(route('payments.pay_now', $hashId), [
            'amt_paid' => 2500.00,
        ]);
        $response2->assertJson(['ok' => true]);

        $pr->refresh();
        $this->assertEquals(4000.00, (float)$pr->amt_paid);
        $this->assertEquals(0.00, (float)$pr->balance);
        $this->assertEquals(1, $pr->paid);
    }

    /**
     * Test receipt print view displays BS month and dual dates.
     */
    public function test_receipt_print_view_displays_bs_period()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Monthly Tuition - Baisakh 2083',
            'amount' => 2000.00,
            'period_type' => 'monthly',
            'fee_category' => 'tuition',
            'bs_year' => 2083,
            'bs_month' => 1,
            'due_date' => '2026-04-28',
            'due_date_bs' => '2083-01-15',
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 2000.00,
            'balance' => 0.00,
            'paid' => 1,
            'bs_year' => 2083,
            'bs_month' => 1,
            'due_date' => '2026-04-28',
            'due_date_bs' => '2083-01-15',
            'year' => $payment->year,
        ]);

        $pr->receipt()->create([
            'amt_paid' => 2000.00,
            'balance' => 0.00,
            'year' => $payment->year,
        ]);

        $response = $this->actingAs($accountant)->get(route('payments.receipts', Qs::hash($pr->id)));
        $response->assertStatus(200);
        $response->assertSee('Baisakh 2083 BS');
        $response->assertSee('PAYMENT RECEIPT');
    }

    /**
     * Test annual BS fee collection reports.
     */
    public function test_annual_bs_fee_collection_reports()
    {
        $accountant = $this->getAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.reports', [
            'bs_year' => 2083,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Annual Nepali Calendar Fee Collection Reports');
        $response->assertSee('Baisakh');
        $response->assertSee('Chaitra');
        $response->assertSee('2083 BS');
    }

    /**
     * Test global settings and user profile calendar_display preferences.
     */
    public function test_calendar_display_preference_configuration()
    {
        $superAdmin = $this->getSuperAdmin();

        // 1. Super admin updates global calendar_display setting to 'bs'
        $response = $this->actingAs($superAdmin)->put(route('settings.update'), [
            'system_name' => Setting::where('type', 'system_name')->value('description') ?: 'Apex Model Secondary School',
            'current_session' => Setting::where('type', 'current_session')->value('description') ?: '2083/84 BS',
            'address' => Setting::where('type', 'address')->value('description') ?: 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari',
            'lock_exam' => 0,
            'calendar_display' => 'bs',
        ]);

        $response->assertSessionHas('flash_success');
        $this->assertEquals('bs', Setting::where('type', 'calendar_display')->value('description'));

        // 2. Individual user sets preference in profile to 'ad'
        $user = $this->getAccountant();
        $profileResponse = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'Sample User Address in Itahari',
            'calendar_display' => 'ad',
        ]);

        $profileResponse->assertSessionHas('flash_success');
        $user->refresh();
        $this->assertEquals('ad', $user->calendar_display);
    }
}
