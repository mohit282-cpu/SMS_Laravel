<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\User;
use Tests\TestCase;

class PaymentNepalYearTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_payment_test@example.com'],
            [
                'name' => 'Payment Super Admin',
                'username' => 'admin_payment_test',
                'password' => bcrypt('password'),
                'user_type' => 'super_admin',
                'code' => strtoupper(substr(md5(uniqid()), 0, 10)),
            ]
        );
    }

    public function test_admin_can_access_payments_index()
    {
        $response = $this->actingAs($this->admin)->get(route('payments.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.payments.index');
    }

    public function test_select_year_redirects_to_show_with_nepal_bs_session()
    {
        $response = $this->actingAs($this->admin)->post(route('payments.select_year'), [
            'year' => '2083/84 BS Updated',
        ]);

        $response->assertRedirect(route('payments.show', '2083/84 BS Updated'));
    }

    public function test_payments_show_loads_with_slash_in_nepal_bs_year()
    {
        $response = $this->actingAs($this->admin)->get('/payments/2083/84%20BS%20Updated');

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.payments.index');
        $response->assertSee('2083/84 BS Updated');
    }

    public function test_payments_show_loads_with_canonical_bs_year()
    {
        $response = $this->actingAs($this->admin)->get('/payments/2083/84%20BS');

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.payments.index');
        $response->assertSee('2083/84 BS');
    }

    public function test_payments_resource_edit_route_still_works()
    {
        $payment = Payment::first();
        if ($payment) {
            $response = $this->actingAs($this->admin)->get(route('payments.edit', $payment->id));
            $response->assertStatus(200);
            $response->assertViewIs('pages.support_team.payments.edit');
        }
    }

    public function test_payments_invoice_loads_with_hashed_student_id()
    {
        $student = \App\Models\StudentRecord::where('user_id', 55)->first();
        if ($student) {
            $hashedId = \App\Helpers\Qs::hash($student->user_id);
            $response = $this->actingAs($this->admin)->get('/payments/invoice/' . $hashedId);
            $response->assertStatus(200);
            $response->assertViewIs('pages.support_team.payments.invoice');
            $response->assertSee('Mohit Shrestha');
        }
    }

    public function test_payments_invoice_loads_with_bs_year()
    {
        $student = \App\Models\StudentRecord::where('user_id', 55)->first();
        if ($student) {
            $hashedId = \App\Helpers\Qs::hash($student->user_id);
            $response = $this->actingAs($this->admin)->get('/payments/invoice/' . $hashedId . '/2083/84%20BS');
            $response->assertStatus(200);
            $response->assertViewIs('pages.support_team.payments.invoice');
            $response->assertSee('Mohit Shrestha');
        }
    }
}
