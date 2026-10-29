<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentHardeningTest extends TestCase
{
    protected function getAdmin(): User
    {
        return User::where('user_type', 'admin')->first() ?: User::factory()->create(['user_type' => 'admin']);
    }

    protected function getAccountant(): User
    {
        return User::where('user_type', 'accountant')->first() ?: User::factory()->create(['user_type' => 'accountant']);
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first() ?: User::factory()->create(['user_type' => 'student']);
    }

    protected function getOtherStudent(): User
    {
        $first = $this->getStudent();
        return User::where('user_type', 'student')->where('id', '!=', $first->id)->first()
            ?: User::factory()->create(['user_type' => 'student']);
    }

    /**
     * Test full payment correctly transitions invoice to cleared state.
     */
    public function test_full_payment_clears_invoice()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Lab Fee ' . rand(1000, 9999),
            'amount' => 1500,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid() . '_' . rand(100, 999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . uniqid() . '_' . rand(100, 999),
            'amt_paid' => 0,
            'balance' => 1500,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $response = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1500,
        ]);

        $response->assertStatus(200);
        $pr->refresh();
        $this->assertEquals(1500.0, (float)$pr->amt_paid);
        $this->assertEquals(0.0, (float)$pr->balance);
        $this->assertEquals(1, $pr->paid);

        $receipt = Receipt::where('pr_id', $pr->id)->first();
        $this->assertNotNull($receipt);
        $this->assertEquals(1500.0, (float)$receipt->amt_paid);
    }

    /**
     * Test partial payment correctly updates balance and leaves invoice uncleared.
     */
    public function test_partial_payment_updates_balance_accurately()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Sports Fee ' . rand(1000, 9999),
            'amount' => 2000,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid() . '_' . rand(100, 999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . uniqid() . '_' . rand(100, 999),
            'amt_paid' => 0,
            'balance' => 2000,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $response = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 750,
        ]);

        $response->assertStatus(200);
        $pr->refresh();
        $this->assertEquals(750.0, (float)$pr->amt_paid);
        $this->assertEquals(1250.0, (float)$pr->balance);
        $this->assertEquals(0, $pr->paid);
    }

    /**
     * Test overpayments and invalid numbers are strictly rejected.
     */
    public function test_overpayments_and_negative_amounts_are_rejected()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Library Fee ' . rand(1000, 9999),
            'amount' => 1000,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid() . '_' . rand(100, 999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . uniqid() . '_' . rand(100, 999),
            'amt_paid' => 200,
            'balance' => 800,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        // Attempt overpayment
        $resOver = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 801,
        ]);
        $resOver->assertStatus(422);

        // Attempt zero
        $resZero = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 0,
        ]);
        $resZero->assertStatus(422);

        // Attempt negative
        $resNeg = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => -100,
        ]);
        $resNeg->assertStatus(422);
    }

    /**
     * Test duplicate payment submission with idempotency key does not charge twice.
     */
    public function test_duplicate_submission_with_idempotency_key_prevents_duplicate_charge()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Tuition Installment ' . rand(1000, 9999),
            'amount' => 5000,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid() . '_' . rand(100, 999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . uniqid() . '_' . rand(100, 999),
            'amt_paid' => 0,
            'balance' => 5000,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'client_token_' . uniqid();

        // First request
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000,
            'idempotency_key' => $key,
        ]);
        $res1->assertStatus(200);

        // Second duplicate request with same idempotency key
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000,
            'idempotency_key' => $key,
        ]);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('duplicate'));

        // Assert only ONE receipt was created
        $count = Receipt::where('pr_id', $pr->id)->count();
        $this->assertEquals(1, $count);

        $pr->refresh();
        $this->assertEquals(1000.0, (float)$pr->amt_paid);
    }

    /**
     * Test missing payment record returns 404.
     */
    public function test_pay_now_missing_record_returns_404()
    {
        $accountant = $this->getAccountant();
        $res = $this->actingAs($accountant)->postJson(route('payments.pay_now', 99999999), [
            'amt_paid' => 100,
        ]);
        $res->assertStatus(404);
    }

    /**
     * Test unauthorized access: student cannot post payments.
     */
    public function test_student_cannot_execute_pay_now()
    {
        $student = $this->getStudent();
        $res = $this->actingAs($student)->postJson(route('payments.pay_now', 1), [
            'amt_paid' => 100,
        ]);
        // teamAccount middleware aborts or redirects non-team members
        $this->assertTrue(in_array($res->status(), [403, 302]));
    }

    /**
     * Test student cannot view another student's invoice.
     */
    public function test_student_cannot_view_another_students_invoice()
    {
        $student = $this->getStudent();
        $otherStudent = $this->getOtherStudent();

        $response = $this->actingAs($student)->get(route('payments.invoice', $otherStudent->id));
        $response->assertStatus(403);
    }

    /**
     * Test idempotency key reuse with different parameters is rejected with 422.
     */
    public function test_reusing_idempotency_key_with_different_amount_is_rejected()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Tamper Test Fee ' . rand(1000, 9999),
            'amount' => 5000,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid() . '_' . rand(100, 999),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'REC_' . uniqid() . '_' . rand(100, 999),
            'amt_paid' => 0,
            'balance' => 5000,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'tamper_key_' . uniqid();

        // 1. Initial valid payment of 1000 with idempotency key
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000,
            'idempotency_key' => $key,
        ]);
        $res1->assertStatus(200);

        // 2. Attempt to reuse SAME key with DIFFERENT amount (2000)
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 2000,
            'idempotency_key' => $key,
        ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('different payment parameters', $res2->json('msg'));
    }
}
