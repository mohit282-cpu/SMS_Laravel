<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Lga;
use App\Models\MyClass;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Section;
use App\Models\State;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParentAccountManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $state;
    protected $lga;
    protected $nationality;
    protected $myClass;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->nationality = Nationality::first() ?: Nationality::create(['name' => 'Nepali']);
        $this->state = State::first() ?: State::create(['name' => 'Bagmati', 'nal_id' => $this->nationality->id]);
        $this->lga = Lga::first() ?: Lga::create(['name' => 'Kathmandu', 'state_id' => $this->state->id]);

        $this->myClass = MyClass::first();
        if (!$this->myClass) {
            $classType = \App\Models\ClassType::first() ?: \App\Models\ClassType::create(['name' => 'Primary', 'code' => 'P']);
            $this->myClass = MyClass::create(['name' => 'Class 1', 'class_type_id' => $classType->id]);
        }

        $this->section = Section::where('my_class_id', $this->myClass->id)->first();
        if (!$this->section) {
            $this->section = Section::create([
                'name' => 'A',
                'my_class_id' => $this->myClass->id,
                'active' => 1,
            ]);
        }
    }

    protected function createTestStudentData(array $overrides = []): array
    {
        $unique = mt_rand(10000, 99999);
        return array_merge([
            'name' => 'Test Student ' . $unique,
            'gender' => 'Male',
            'year_admitted' => '2024',
            'address' => 'Kathmandu Ward 4',
            'state_id' => $this->state->id,
            'lga_id' => $this->lga->id,
            'nal_id' => $this->nationality->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ], $overrides);
    }

    public function test_student_registration_automatically_creates_parent_account()
    {
        $unique = mt_rand(1000, 9999);
        $parentPhone = '9811' . $unique . '01';
        $parentEmail = 'auto_parent_' . $unique . '@smsacademy.edu.np';

        $studentData = $this->createTestStudentData([
            'parent_name' => 'Ram Bahadur Shrestha',
            'parent_phone' => $parentPhone,
            'parent_email' => $parentEmail,
            'parent_relationship' => 'Father',
            'parent_address' => 'Patan, Lalitpur',
        ]);

        $response = $this->actingAs($this->admin)->post(route('students.store'), $studentData);
        $response->assertStatus(200);

        // Verify Parent account was automatically created
        $normalizedPhone = Qs::normalizePhone($parentPhone);
        $parent = User::where('user_type', 'parent')
            ->where('phone', $normalizedPhone)
            ->first();

        $this->assertNotNull($parent, 'Parent account was not created automatically.');
        $this->assertEquals('Ram Bahadur Shrestha', $parent->name);
        $this->assertEquals(strtolower($parentEmail), $parent->email);
        $this->assertTrue((bool)$parent->is_active);
        $this->assertNotNull($parent->activation_token, 'Activation token should be generated.');
        $this->assertNotNull($parent->activation_token_expires_at);

        // Verify Student Record is linked to the newly created parent
        $studentUser = User::where('name', $studentData['name'])->first();
        $this->assertNotNull($studentUser);
        $studentRecord = StudentRecord::where('user_id', $studentUser->id)->first();
        $this->assertNotNull($studentRecord);
        $this->assertEquals($parent->id, $studentRecord->my_parent_id);
        $this->assertEquals('Father', $studentRecord->parent_relationship);

        // Verify session flashed activation details
        $this->assertTrue(session()->has('parent_created_msg'));
        $this->assertTrue(session()->has('parent_activation_url'));
    }

    public function test_student_registration_links_existing_parent_by_normalized_phone()
    {
        $unique = mt_rand(1000, 9999);
        $phone = '9841' . $unique . '99';
        $normalizedPhone = Qs::normalizePhone($phone);

        // Pre-create existing parent
        $existingParent = User::create([
            'name' => 'Sita Maya Tamang',
            'phone' => $normalizedPhone,
            'email' => 'sita_' . $unique . '@example.com',
            'user_type' => 'parent',
            'username' => 'p_sita_' . $unique,
            'password' => Hash::make('Secret123!'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $initialParentCount = User::where('user_type', 'parent')->count();

        // Register student with +977 formatted phone
        $studentData = $this->createTestStudentData([
            'name' => 'Child One ' . $unique,
            'parent_name' => 'Sita Tamang',
            'parent_phone' => '+977-' . $phone, // should be normalized and matched
            'parent_relationship' => 'Mother',
        ]);

        $response = $this->actingAs($this->admin)->post(route('students.store'), $studentData);
        $response->assertStatus(200);

        // Verify no duplicate parent created
        $this->assertEquals($initialParentCount, User::where('user_type', 'parent')->count());

        // Verify child linked to existing parent
        $studentUser = User::where('name', $studentData['name'])->first();
        $studentRecord = StudentRecord::where('user_id', $studentUser->id)->first();
        $this->assertEquals($existingParent->id, $studentRecord->my_parent_id);
        $this->assertEquals('Mother', $studentRecord->parent_relationship);
        $this->assertTrue(session()->has('parent_linked_msg'));
    }

    public function test_multiple_children_can_belong_to_one_parent()
    {
        $unique = mt_rand(1000, 9999);
        $phone = '9851' . $unique . '88';

        $student1Data = $this->createTestStudentData([
            'name' => 'First Sibling ' . $unique,
            'parent_name' => 'Hari Prasad Karki',
            'parent_phone' => $phone,
            'parent_relationship' => 'Father',
        ]);

        $this->actingAs($this->admin)->post(route('students.store'), $student1Data);

        $parent = User::where('user_type', 'parent')
            ->where('phone', Qs::normalizePhone($phone))
            ->first();
        $this->assertNotNull($parent);

        // Register second sibling with same parent phone
        $student2Data = $this->createTestStudentData([
            'name' => 'Second Sibling ' . $unique,
            'parent_name' => 'Hari Prasad Karki',
            'parent_phone' => $phone,
            'parent_relationship' => 'Father',
        ]);

        $this->actingAs($this->admin)->post(route('students.store'), $student2Data);

        // Check relationship
        $parent->refresh();
        $this->assertEquals(2, $parent->children()->count());

        $childrenNames = $parent->children->map(function ($sr) {
            return $sr->user->name;
        })->toArray();

        $this->assertContains('First Sibling ' . $unique, $childrenNames);
        $this->assertContains('Second Sibling ' . $unique, $childrenNames);
    }

    public function test_parent_account_creation_rolls_back_if_student_creation_fails()
    {
        $unique = mt_rand(1000, 9999);
        $phone = '9861' . $unique . '77';
        $normalizedPhone = Qs::normalizePhone($phone);

        // Provide an invalid student creation payload that passes Request validation but triggers DB transaction failure
        // We can simulate this by intercepting student creation with an impossible foreign key or invalid section
        // StudentRecordCreate validates section belongs to class. Let's test that validation error prevents parent creation:
        $studentData = $this->createTestStudentData([
            'parent_name' => 'Rollback Parent ' . $unique,
            'parent_phone' => $phone,
            'my_class_id' => 999999, // Non-existent class
        ]);

        $response = $this->actingAs($this->admin)->post(route('students.store'), $studentData);
        // Should fail validation or error
        $this->assertTrue($response->status() >= 300 || session()->has('errors'));

        // Parent should NOT be in database
        $parent = User::where('user_type', 'parent')
            ->where('phone', $normalizedPhone)
            ->first();
        $this->assertNull($parent, 'Parent should not be created if student registration fails.');
    }

    public function test_parent_can_activate_account_securely()
    {
        $token = Str::random(64);
        $unique = mt_rand(1000, 9999);

        $parent = User::create([
            'name' => 'Unactivated Parent ' . $unique,
            'username' => 'p_unactivated_' . $unique,
            'email' => 'unact_' . $unique . '@example.com',
            'phone' => '9801' . $unique . '11',
            'user_type' => 'parent',
            'password' => Hash::make(Str::random(32)),
            'code' => strtoupper(Str::random(10)),
            'activation_token' => $token,
            'activation_token_expires_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        // 1. Visit activation form
        $getRes = $this->get(route('parent.activate', $token));
        $getRes->assertStatus(200);
        $getRes->assertSee('Parent Account Activation');

        // 2. Submit new password
        $postRes = $this->post(route('parent.activate.submit', $token), [
            'password' => 'MyNewPassword123#',
            'password_confirmation' => 'MyNewPassword123#',
        ]);

        $postRes->assertRedirect(route('login'));
        $postRes->assertSessionHas('flash_success');

        // Verify parent state in DB
        $parent->refresh();
        $this->assertNull($parent->activation_token, 'Activation token must be cleared after single use.');
        $this->assertNull($parent->activation_token_expires_at);
        $this->assertTrue(Hash::check('MyNewPassword123#', $parent->password));

        // 3. Re-using same token should fail
        $reuseRes = $this->get(route('parent.activate', $token));
        $reuseRes->assertStatus(200);
        $reuseRes->assertSee('Activation Link Invalid');
    }

    public function test_expired_activation_token_is_rejected()
    {
        $token = Str::random(64);
        $unique = mt_rand(1000, 9999);

        User::create([
            'name' => 'Expired Token Parent ' . $unique,
            'username' => 'p_expired_' . $unique,
            'email' => 'exp_' . $unique . '@example.com',
            'phone' => '9802' . $unique . '22',
            'user_type' => 'parent',
            'password' => Hash::make(Str::random(32)),
            'code' => strtoupper(Str::random(10)),
            'activation_token' => $token,
            'activation_token_expires_at' => now()->subDay(), // Expired!
            'is_active' => true,
        ]);

        $response = $this->get(route('parent.activate', $token));
        $response->assertStatus(200);
        $response->assertSee('Activation Link Invalid');

        $submitResponse = $this->post(route('parent.activate.submit', $token), [
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);
        $submitResponse->assertRedirect(route('login'));
        $submitResponse->assertSessionHas('flash_danger');
    }

    public function test_parent_can_login_using_phone_email_or_username()
    {
        $unique = mt_rand(1000, 9999);
        $plainPassword = 'ParentPassword99$';
        $phone = '9803' . $unique . '33';
        $email = 'login_parent_' . $unique . '@sms.edu.np';
        $username = 'parent_login_' . $unique;

        $parent = User::create([
            'name' => 'Multi Login Parent ' . $unique,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'user_type' => 'parent',
            'password' => Hash::make($plainPassword),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        // Login by username
        $resUser = $this->post(route('login'), [
            'login' => $username,
            'password' => $plainPassword,
        ]);
        $resUser->assertRedirect();
        $this->assertAuthenticatedAs($parent);
        auth()->logout();

        // Login by email
        $resEmail = $this->post(route('login'), [
            'login' => $email,
            'password' => $plainPassword,
        ]);
        $resEmail->assertRedirect();
        $this->assertAuthenticatedAs($parent);
        auth()->logout();

        // Login by phone
        $resPhone = $this->post(route('login'), [
            'login' => $phone,
            'password' => $plainPassword,
        ]);
        $resPhone->assertRedirect();
        $this->assertAuthenticatedAs($parent);
        auth()->logout();

        // Login by phone with international prefix +977
        $resPhoneIntl = $this->post(route('login'), [
            'login' => '+977-' . $phone,
            'password' => $plainPassword,
        ]);
        $resPhoneIntl->assertRedirect();
        $this->assertAuthenticatedAs($parent);
        auth()->logout();
    }

    public function test_deactivated_parent_cannot_login()
    {
        $unique = mt_rand(1000, 9999);
        $plainPassword = 'SecretPassword123!';

        $deactivatedParent = User::create([
            'name' => 'Deactivated Parent ' . $unique,
            'username' => 'p_deact_' . $unique,
            'email' => 'deact_' . $unique . '@example.com',
            'phone' => '9804' . $unique . '44',
            'user_type' => 'parent',
            'password' => Hash::make($plainPassword),
            'code' => strtoupper(Str::random(10)),
            'is_active' => false, // Deactivated
        ]);

        $response = $this->post(route('login'), [
            'login' => $deactivatedParent->username,
            'password' => $plainPassword,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_strict_data_isolation_parent_cannot_view_other_parent_student_profile()
    {
        $unique = mt_rand(1000, 9999);

        // Parent A with Child A
        $parentA = User::create([
            'name' => 'Parent Alpha ' . $unique,
            'username' => 'p_alpha_' . $unique,
            'email' => 'alpha_' . $unique . '@test.com',
            'phone' => '9805' . $unique . '51',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $studentUserA = User::factory()->create(['user_type' => 'student']);
        $srA = StudentRecord::create([
            'user_id' => $studentUserA->id,
            'my_parent_id' => $parentA->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        // Parent B with Child B
        $parentB = User::create([
            'name' => 'Parent Beta ' . $unique,
            'username' => 'p_beta_' . $unique,
            'email' => 'beta_' . $unique . '@test.com',
            'phone' => '9805' . $unique . '52',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $studentUserB = User::factory()->create(['user_type' => 'student']);
        $srB = StudentRecord::create([
            'user_id' => $studentUserB->id,
            'my_parent_id' => $parentB->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        // Parent A views own Child A -> Allowed (200)
        $resA = $this->actingAs($parentA)->get(route('students.show', Qs::hash($srA->id)));
        $resA->assertStatus(200);

        // Parent A attempts to view Child B -> FORBIDDEN (403)
        $resB = $this->actingAs($parentA)->get(route('students.show', Qs::hash($srB->id)));
        $resB->assertStatus(403);

        // Also test plain ID manipulation attempt (returns 302 back with error or 403)
        $resBPlain = $this->actingAs($parentA)->get(route('students.show', $srB->id));
        $this->assertTrue(in_array($resBPlain->status(), [302, 403, 404]));
    }

    public function test_strict_data_isolation_parent_cannot_access_other_student_marksheets()
    {
        $unique = mt_rand(1000, 9999);

        $parentA = User::create([
            'name' => 'Parent Mark A ' . $unique,
            'username' => 'p_mark_a_' . $unique,
            'email' => 'mark_a_' . $unique . '@test.com',
            'phone' => '9806' . $unique . '61',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $studentUserA = User::factory()->create(['user_type' => 'student']);
        $srA = StudentRecord::create([
            'user_id' => $studentUserA->id,
            'my_parent_id' => $parentA->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        // Attach an exam record so exam years exist for Student A.
        // exam_records.exam_id has a foreign key to exams.id, so the parent
        // exam row must be created instead of assuming a seeded exam exists.
        $exam = \App\Models\Exam::create([
            'name' => 'Terminal Exam ' . $unique,
            'term' => 1,
            'year' => '2024-2025',
        ]);

        \App\Models\ExamRecord::create([
            'exam_id' => $exam->id,
            'student_id' => $studentUserA->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'year' => '2024-2025',
        ]);

        $studentUserB = User::factory()->create(['user_type' => 'student']);
        $srB = StudentRecord::create([
            'user_id' => $studentUserB->id,
            'my_parent_id' => null, // Not Parent A's child
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        // Parent A accessing own child year selector -> Allowed (200)
        $resOwn = $this->actingAs($parentA)->get(route('marks.year_selector', Qs::hash($studentUserA->id)));
        $resOwn->assertStatus(200);

        // Parent A accessing other child year selector -> FORBIDDEN (403)
        $resOther = $this->actingAs($parentA)->get(route('marks.year_selector', Qs::hash($studentUserB->id)));
        $resOther->assertStatus(403);
    }

    public function test_strict_data_isolation_parent_cannot_pay_or_access_other_student_payments()
    {
        $unique = mt_rand(1000, 9999);

        $parentA = User::create([
            'name' => 'Parent Pay A ' . $unique,
            'username' => 'p_pay_a_' . $unique,
            'email' => 'pay_a_' . $unique . '@test.com',
            'phone' => '9807' . $unique . '71',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $studentUserB = User::factory()->create(['user_type' => 'student']);
        $srB = StudentRecord::create([
            'user_id' => $studentUserB->id,
            'my_parent_id' => null, // Not Parent A
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        $payment = Payment::create([
            'title' => 'Tuition Fee ' . $unique,
            'amount' => 5000,
            'my_class_id' => $this->myClass->id,
            'ref_no' => 'PAY/' . $unique,
            'year' => '2024-2025',
        ]);

        $pr = PaymentRecord::create([
            'payment_id' => $payment->id,
            'student_id' => $studentUserB->id,
            'ref_no' => 'REC/' . $unique,
            'year' => '2024-2025',
        ]);

        // Parent A attempts to pay for Student B -> FORBIDDEN (403)
        $response = $this->actingAs($parentA)->post(route('payments.pay_now', Qs::hash($pr->id)), [
            'amt_paid' => 1000,
        ]);

        $response->assertStatus(403);
    }

    public function test_parent_dashboard_displays_only_linked_children()
    {
        $unique = mt_rand(1000, 9999);

        $parent = User::create([
            'name' => 'Parent Dash ' . $unique,
            'username' => 'p_dash_' . $unique,
            'email' => 'dash_' . $unique . '@test.com',
            'phone' => '9808' . $unique . '81',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $child = User::factory()->create(['user_type' => 'student', 'name' => 'My Lovely Child ' . $unique]);
        StudentRecord::create([
            'user_id' => $child->id,
            'my_parent_id' => $parent->id,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        $unrelatedChild = User::factory()->create(['user_type' => 'student', 'name' => 'Unrelated Child ' . $unique]);
        StudentRecord::create([
            'user_id' => $unrelatedChild->id,
            'my_parent_id' => null,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        $response = $this->actingAs($parent)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('My Lovely Child ' . $unique);
        $response->assertDontSee('Unrelated Child ' . $unique);

        // Parent without children should see helpful empty state
        $lonelyParent = User::create([
            'name' => 'Lonely Parent ' . $unique,
            'username' => 'p_lonely_' . $unique,
            'email' => 'lonely_' . $unique . '@test.com',
            'phone' => '9808' . $unique . '99',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => strtoupper(Str::random(10)),
            'is_active' => true,
        ]);

        $emptyRes = $this->actingAs($lonelyParent)->get(route('dashboard'));
        $emptyRes->assertStatus(200);
        $emptyRes->assertSee('No Linked Students Found');
    }

    public function test_parent_can_update_own_profile_but_cannot_modify_protected_fields()
    {
        $unique = mt_rand(1000, 9999);

        $parent = User::create([
            'name' => 'Profile Parent ' . $unique,
            'username' => 'p_prof_' . $unique,
            'email' => 'prof_' . $unique . '@test.com',
            'phone' => '9809' . $unique . '01',
            'user_type' => 'parent',
            'password' => Hash::make('password123'),
            'code' => 'SAFECODE123',
            'is_active' => true,
            'address' => 'Old Address',
        ]);

        $response = $this->actingAs($parent)->put(route('my_account.update'), [
            'address' => 'New Safe Address 456',
            'phone2' => '01445566',
            'user_type' => 'super_admin', // Escalation attempt
            'code' => 'HACKEDCODE',       // Protected attempt
        ]);

        $response->assertSessionHas('flash_success');

        $parent->refresh();
        $this->assertEquals('New Safe Address 456', $parent->address);
        $this->assertEquals('parent', $parent->user_type, 'User type must not be escalatable.');
        $this->assertEquals('SAFECODE123', $parent->code, 'User code must not be changeable.');
    }

    public function test_administrator_parent_management_workflows()
    {
        // 1. Admin views parents index
        $indexRes = $this->actingAs($this->admin)->get(route('parents.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Parent Account Management');

        // 2. Admin creates parent manually
        $unique = mt_rand(1000, 9999);
        $createRes = $this->actingAs($this->admin)->from(route('parents.index'))->post(route('parents.store'), [
            'name' => 'Manual Parent ' . $unique,
            'phone' => '9810' . $unique . '12',
            'email' => 'manual_' . $unique . '@school.np',
            'address' => 'Bhaktapur Ward 2',
        ]);
        $createRes->assertRedirect(route('parents.index'));
        $createRes->assertSessionHas('flash_success');

        $createdParent = User::where('email', 'manual_' . $unique . '@school.np')->first();
        $this->assertNotNull($createdParent);
        $this->assertEquals('parent', $createdParent->user_type);
        $this->assertNotNull($createdParent->activation_token);

        // 3. Admin views parent details
        $showRes = $this->actingAs($this->admin)->get(route('parents.show', $createdParent->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Manual Parent ' . $unique);

        // 4. Admin updates parent details
        $updateRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->put(route('parents.update', $createdParent->id), [
            'name' => 'Manual Parent Updated ' . $unique,
            'phone' => '9810' . $unique . '99',
            'email' => 'manual_updated_' . $unique . '@school.np',
            'address' => 'Updated Address',
        ]);
        $updateRes->assertSessionHas('flash_success');
        $createdParent->refresh();
        $this->assertEquals('Manual Parent Updated ' . $unique, $createdParent->name);

        // 5. Admin toggles active status
        $toggleRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->post(route('parents.toggle_active', $createdParent->id));
        $toggleRes->assertSessionHas('flash_success');
        $createdParent->refresh();
        $this->assertFalse((bool)$createdParent->is_active);

        // 6. Admin resets activation token
        $resetRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->post(route('parents.reset_access', $createdParent->id));
        $resetRes->assertSessionHas('flash_success');
        $createdParent->refresh();
        $this->assertNotNull($createdParent->activation_token);

        // 7. Admin links an existing student to this parent
        $studentUser = User::factory()->create(['user_type' => 'student']);
        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_parent_id' => null,
            'my_class_id' => $this->myClass->id,
            'section_id' => $this->section->id,
            'session' => '2024-2025',
        ]);

        $linkRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->post(route('parents.link_student', $createdParent->id), [
            'student_id' => $studentUser->id,
            'parent_relationship' => 'Guardian',
        ]);
        $linkRes->assertSessionHas('flash_success');

        $sr->refresh();
        $this->assertEquals($createdParent->id, $sr->my_parent_id);
        $this->assertEquals('Guardian', $sr->parent_relationship);

        // 8. Admin attempts to delete parent with linked student -> Must be prevented!
        $delBlockedRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->delete(route('parents.destroy', $createdParent->id));
        $delBlockedRes->assertSessionHas('flash_danger');
        $this->assertDatabaseHas('users', ['id' => $createdParent->id]);

        // 9. Admin unlinks student
        $unlinkRes = $this->actingAs($this->admin)->from(route('parents.show', $createdParent->id))->post(route('parents.unlink_student', [$createdParent->id, $sr->id]));
        $unlinkRes->assertSessionHas('flash_success');
        $sr->refresh();
        $this->assertNull($sr->my_parent_id);

        // 10. Admin deletes parent with 0 linked students -> Succeeds
        $delSuccessRes = $this->actingAs($this->admin)->from(route('parents.index'))->delete(route('parents.destroy', $createdParent->id));
        $delSuccessRes->assertSessionHas('flash_success');
        $this->assertDatabaseMissing('users', ['id' => $createdParent->id]);
    }

    public function test_unauthorized_users_cannot_access_parent_management()
    {
        $student = User::factory()->create(['user_type' => 'student']);

        // Guest redirected to login
        $guestRes = $this->get(route('parents.index'));
        $guestRes->assertRedirect(route('login'));

        // Student redirected to login by teamSA middleware
        $studentRes = $this->actingAs($student)->get(route('parents.index'));
        $studentRes->assertRedirect(route('login'));
    }
}
