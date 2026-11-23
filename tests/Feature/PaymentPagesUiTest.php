<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\Payment;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentPagesUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAccountant(): User
    {
        return User::create([
            'name' => 'Accountant User ' . uniqid(),
            'email' => 'accountant_' . uniqid() . '@sms.test',
            'username' => 'acc_' . uniqid(),
            'user_type' => 'accountant',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    protected function createStudent(): User
    {
        return User::create([
            'name' => 'Student User ' . uniqid(),
            'email' => 'student_' . uniqid() . '@sms.test',
            'username' => 'stu_' . uniqid(),
            'user_type' => 'student',
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_payment_pages()
    {
        $this->get('/payments')->assertRedirect('/login');
        $this->get('/payments/create')->assertRedirect('/login');
        $this->get('/payments/manage')->assertRedirect('/login');
        $this->get('/payments/monthly_billing')->assertRedirect('/login');
        $this->get('/payments/reports')->assertRedirect('/login');
    }

    public function test_accountant_can_access_payment_create_page()
    {
        $accountant = $this->createAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Payment & Fee Structure');
        $response->assertSee('Fee Title');
        $response->assertSee('Amount (NPR)');
    }

    public function test_accountant_can_access_payments_index_and_sees_current_session()
    {
        $accountant = $this->createAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Payments & Fee Structures');
        $response->assertSee('All Fee Items');
        $response->assertSee('Filter By Class');
    }

    public function test_accountant_can_access_monthly_billing_page()
    {
        $accountant = $this->createAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.monthly_billing'));

        $response->assertStatus(200);
        $response->assertSee('Nepali 12-Month Billing Cycle');
        $response->assertSee('Total Invoiced');
        $response->assertSee('Cash Collected');
        $response->assertSee('Outstanding Balance');
        $response->assertSee('Generate Monthly Invoices');
    }

    public function test_accountant_can_access_reports_page()
    {
        $accountant = $this->createAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.reports'));

        $response->assertStatus(200);
        $response->assertSee('Annual Nepali Calendar Fee Collection Reports');
        $response->assertSee('Annual Billed Total');
        $response->assertSee('Annual Cash Collected');
        $response->assertSee('Outstanding Receivables');
    }

    public function test_accountant_can_access_payments_manage_page()
    {
        $accountant = $this->createAccountant();

        $response = $this->actingAs($accountant)->get(route('payments.manage'));

        $response->assertStatus(200);
        $response->assertSee('Student Payments & Invoice Management');
        $response->assertSee('Select Class for Fee Collection');
    }

    public function test_student_cannot_access_accounting_management_routes()
    {
        $student = $this->createStudent();

        $this->actingAs($student)->get(route('payments.create'))->assertStatus(403);
        $this->actingAs($student)->get(route('payments.index'))->assertStatus(403);
        $this->actingAs($student)->get(route('payments.manage'))->assertStatus(403);
        $this->actingAs($student)->get(route('payments.monthly_billing'))->assertStatus(403);
        $this->actingAs($student)->get(route('payments.reports'))->assertStatus(403);
    }
}
