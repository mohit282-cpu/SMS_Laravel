<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Payment;
use App\Models\PaymentIdempotencyKey;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePaymentIdempotencyTest extends TestCase
{
    protected function getAccountant(): User
    {
        return User::where('user_type', 'accountant')->first() ?: User::factory()->create(['user_type' => 'accountant']);
    }

    protected function getOtherAccountant(): User
    {
        $first = $this->getAccountant();
        return User::where('user_type', 'accountant')->where('id', '!=', $first->id)->first()
            ?: User::factory()->create(['user_type' => 'accountant']);
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first() ?: User::factory()->create(['user_type' => 'student']);
    }

    /**
     * Test first payment with idempotency key persists record in payment_idempotency_keys table.
     */
    public function test_payment_persists_idempotency_record_in_database()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Lab Fee ' . uniqid(),
            'amount' => 1200.50,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1200.50,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'idem_key_' . uniqid();

        $response = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 500.50,
            'idempotency_key' => $key,
        ]);

        $response->assertStatus(200);

        // Verify database-backed idempotency record
        $idem = PaymentIdempotencyKey::where('idempotency_key', $key)
            ->where('user_id', $accountant->id)
            ->first();

        $this->assertNotNull($idem, 'PaymentIdempotencyKey record must be created in the database.');
        $this->assertEquals('completed', $idem->status);
        $this->assertEquals(500.50, (float)$idem->amount);
        $this->assertEquals($pr->id, $idem->payment_record_id);
        $this->assertEquals(200, $idem->response_code);
        $this->assertNotEmpty($idem->payload_hash);

        // Verify PaymentRecord updated with exact decimal precision
        $pr->refresh();
        $this->assertEquals(500.50, (float)$pr->amt_paid);
        $this->assertEquals(700.00, (float)$pr->balance);
        $this->assertEquals(0, $pr->paid);

        // Exactly one receipt created
        $receipts = Receipt::where('pr_id', $pr->id)->get();
        $this->assertCount(1, $receipts);
        $this->assertEquals(500.50, (float)$receipts[0]->amt_paid);
        $this->assertEquals(700.00, (float)$receipts[0]->balance);
    }

    /**
     * Test duplicate payment submission with identical key returns cached replay without duplicate receipt.
     */
    public function test_duplicate_submission_replays_response_without_duplicate_receipt()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Tuition Fee ' . uniqid(),
            'amount' => 3000.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 3000.00,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'idem_replay_' . uniqid();

        // Request 1
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000.00,
            'idempotency_key' => $key,
        ]);
        $res1->assertStatus(200);

        // Request 2 (Duplicate replay)
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000.00,
            'idempotency_key' => $key,
        ]);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('duplicate'));

        // Assert strictly only ONE receipt exists
        $this->assertEquals(1, Receipt::where('pr_id', $pr->id)->count());

        // Balance was decremented only once
        $pr->refresh();
        $this->assertEquals(1000.00, (float)$pr->amt_paid);
        $this->assertEquals(2000.00, (float)$pr->balance);
    }

    /**
     * Test reusing idempotency key with different payload is strictly rejected with 422.
     */
    public function test_reusing_key_with_different_payload_is_rejected()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Library Fee ' . uniqid(),
            'amount' => 2000.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 2000.00,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'idem_mismatch_' . uniqid();

        // First payment of Rs. 500
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $key,
        ]);
        $res1->assertStatus(200);

        // Second payment attempting to reuse same key for Rs. 800
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 800.00,
            'idempotency_key' => $key,
        ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('different payment parameters', $res2->json('msg'));

        // Assert balance was not affected by rejected attempt
        $pr->refresh();
        $this->assertEquals(500.00, (float)$pr->amt_paid);
        $this->assertEquals(1500.00, (float)$pr->balance);
        $this->assertEquals(1, Receipt::where('pr_id', $pr->id)->count());
    }

    /**
     * Test idempotency key scoping per user: different users can use same key string without conflict.
     */
    public function test_idempotency_keys_are_scoped_per_user()
    {
        $accountant1 = $this->getAccountant();
        $accountant2 = $this->getOtherAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Bus Fee ' . uniqid(),
            'amount' => 1500.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr1 = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR1_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1500.00,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $payment2 = Payment::create([
            'title' => 'Hostel Fee ' . uniqid(),
            'amount' => 1500.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr2 = PaymentRecord::create([
            'payment_id' => $payment2->id,
            'student_id' => $student->id,
            'ref_no' => 'PR2_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1500.00,
            'paid' => 0,
            'year' => $payment2->year,
        ]);

        $sharedKeyString = 'shared_key_' . uniqid();

        // User 1 uses key
        $res1 = $this->actingAs($accountant1)->postJson(route('payments.pay_now', $pr1->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $sharedKeyString,
        ]);
        $res1->assertStatus(200);

        // User 2 uses same key string for their own operation
        $res2 = $this->actingAs($accountant2)->postJson(route('payments.pay_now', $pr2->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $sharedKeyString,
        ]);
        $res2->assertStatus(200);

        // Both records exist independently in payment_idempotency_keys
        $this->assertEquals(2, PaymentIdempotencyKey::where('idempotency_key', $sharedKeyString)->count());
    }

    /**
     * Test fractional paisa precision and complete settlement.
     */
    public function test_fractional_paisa_precision_and_settlement()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Quarterly Science Fee ' . uniqid(),
            'amount' => 1525.75,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1525.75,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        // Pay installment 1: Rs. 525.25
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 525.25,
        ]);
        $res1->assertStatus(200);

        $pr->refresh();
        $this->assertEquals(525.25, (float)$pr->amt_paid);
        $this->assertEquals(1000.50, (float)$pr->balance);
        $this->assertEquals(0, $pr->paid);

        // Pay installment 2: Rs. 1000.50 (exact settlement)
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1000.50,
        ]);
        $res2->assertStatus(200);

        $pr->refresh();
        $this->assertEquals(1525.75, (float)$pr->amt_paid);
        $this->assertEquals(0.00, (float)$pr->balance);
        $this->assertEquals(1, $pr->paid);
    }

    /**
     * Test discount deduction adjusts net invoice balance and prevents overpayment.
     */
    public function test_discount_deduction_adjusts_payable_balance()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Annual Exam Fee ' . uniqid(),
            'amount' => 2000.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        // Invoice with Rs. 500 scholarship discount
        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1500.00,
            'discount_amt' => 500.00,
            'waiver_reason' => 'Merit Scholarship',
            'paid' => 0,
            'year' => $payment->year,
        ]);

        // Attempting to pay original gross amount of 2000 must be rejected
        $resOver = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 2000.00,
        ]);
        $resOver->assertStatus(422);
        $this->assertStringContainsString('exceeds the outstanding balance', $resOver->json('msg'));

        // Paying net amount of 1500 clears the invoice
        $resClear = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 1500.00,
        ]);
        $resClear->assertStatus(200);

        $pr->refresh();
        $this->assertEquals(1500.00, (float)$pr->amt_paid);
        $this->assertEquals(0.00, (float)$pr->balance);
        $this->assertEquals(1, $pr->paid);
    }

    /**
     * Test simulated concurrent insert race condition is handled safely by unique constraint.
     */
    public function test_concurrent_idempotency_insertion_handled_safely()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Concurrency Fee ' . uniqid(),
            'amount' => 1000.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1000.00,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'race_key_' . uniqid();
        $canonicalPayload = ['pr_id' => (int)$pr->id, 'amt_paid' => number_format(500.00, 2, '.', '')];
        $payloadHash = hash('sha256', json_encode($canonicalPayload));

        // Simulate an already processing/inserted key by a concurrent process
        DB::table('payment_idempotency_keys')->insert([
            'user_id' => $accountant->id,
            'operation' => 'payment.pay_now',
            'idempotency_key' => $key,
            'payload_hash' => $payloadHash,
            'status' => 'processing',
            'payment_record_id' => $pr->id,
            'amount' => 500.00,
            'response_code' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simultaneous incoming request should encounter the 409 status and not double charge
        $response = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $key,
        ]);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals(0, Receipt::where('pr_id', $pr->id)->count());
    }

    /**
     * Test concurrent request with separate database connection fails with unique constraint.
     */
    public function test_concurrent_requests_using_separate_connections_cannot_duplicate()
    {
        $accountant = $this->getAccountant();
        $student = $this->getStudent();

        $payment = Payment::create([
            'title' => 'Separate Conn Fee ' . uniqid(),
            'amount' => 1000.00,
            'year' => Qs::getCurrentSession(),
            'ref_no' => 'PAY_' . uniqid(),
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'ref_no' => 'PR_' . uniqid(),
            'amt_paid' => 0.00,
            'balance' => 1000.00,
            'paid' => 0,
            'year' => $payment->year,
        ]);

        $key = 'conn_race_' . uniqid();

        // 1. First connection processes the request
        $res1 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $key,
        ]);
        $res1->assertStatus(200);

        // 2. Open a separate distinct PDO connection
        $config = config('database.connections.' . config('database.default'));
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

        // Attempt direct insertion of the identical key on the second connection
        $caught = false;
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_idempotency_keys (user_id, operation, idempotency_key, payload_hash, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$accountant->id, 'payment.pay_now', $key, 'dummyhash', 'processing']);
        } catch (\PDOException $pe) {
            if ($pe->getCode() == 23000 || str_contains($pe->getMessage(), '1062')) {
                $caught = true;
            }
        }

        $this->assertTrue($caught, 'Database unique constraint must reject duplicate key insertion on separate connection.');

        // Replay through API on second request returns duplicate replay
        $res2 = $this->actingAs($accountant)->postJson(route('payments.pay_now', $pr->id), [
            'amt_paid' => 500.00,
            'idempotency_key' => $key,
        ]);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('duplicate'));

        // Receipt count remains strictly 1
        $this->assertEquals(1, Receipt::where('pr_id', $pr->id)->count());
    }
}
