<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MyClass;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuditLogFeatureTest extends TestCase
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

    /**
     * 1. Super admin can access the audit logs dashboard.
     */
    public function test_super_admin_can_access_audit_logs_dashboard()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('audit_logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Activity &amp; Audit Logs', false);
        $response->assertSee('Live Stream Active');
        $response->assertSee('Total Events');
    }

    /**
     * 2. Unauthorized roles (student, teacher) and guests receive 403 or redirect.
     */
    public function test_unauthorized_users_are_forbidden_from_audit_logs_dashboard()
    {
        // Guests redirected to login
        $guestResponse = $this->get(route('audit_logs.index'));
        $guestResponse->assertRedirect(route('login'));

        // Student forbidden
        $student = $this->getStudent();
        if ($student) {
            $studentResponse = $this->actingAs($student)->get(route('audit_logs.index'));
            $studentResponse->assertStatus(403);
        }

        // Teacher forbidden
        $teacher = $this->getTeacher();
        if ($teacher) {
            $teacherResponse = $this->actingAs($teacher)->get(route('audit_logs.index'));
            $teacherResponse->assertStatus(403);
        }
    }

    /**
     * 3. Authentication events automatically generate audit records via AuthAuditListener.
     */
    public function test_authentication_events_automatically_generate_audit_logs()
    {
        $admin = $this->getAdmin();

        // 3a. Login event
        event(new Login('web', $admin, false));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.login',
            'category' => 'authentication',
            'status' => 'success',
        ]);

        // 3b. Failed login event
        event(new Failed('web', null, ['identity' => 'intruder_user', 'password' => 'secret_pass_123']));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.failed_login',
            'category' => 'security',
            'status' => 'failure',
        ]);

        // 3c. Logout event
        event(new Logout('web', $admin));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.logout',
            'category' => 'authentication',
            'status' => 'success',
        ]);

        // 3d. PasswordReset event
        event(new PasswordReset($admin));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'auth.password_reset',
            'category' => 'security',
            'status' => 'success',
        ]);
    }

    /**
     * 4. Failed login attempts never record credentials, passwords, or hashes.
     */
    public function test_failed_login_does_not_expose_or_store_passwords()
    {
        $sensitivePassword = 'SuperSecretPlainPassword987!';
        event(new Failed('web', null, [
            'identity' => 'test_intruder',
            'password' => $sensitivePassword,
        ]));

        $log = AuditLog::where('action', 'auth.failed_login')->latest('id')->first();
        $this->assertNotNull($log);

        // Password should not exist in description
        $this->assertStringNotContainsString($sensitivePassword, $log->description);

        // Password should not exist anywhere in new_values
        $newValues = json_encode($log->new_values);
        $this->assertStringNotContainsString($sensitivePassword, (string) $newValues);
    }

    /**
     * 5. Sensitive fields are unconditionally redacted by AuditLogService.
     */
    public function test_sensitive_fields_are_redacted_by_service()
    {
        $service = app(AuditLogService::class);

        $old = [
            'user' => 'admin',
            'password' => 'old_pass_123',
            'remember_token' => 'token_xyz',
        ];

        $new = [
            'user' => 'admin_updated',
            'password' => 'new_pass_456',
            'card_number' => '4111222233334444',
            'cvv' => '123',
            'pin' => '0000',
            'api_key' => 'secret_key_abc',
        ];

        $log = $service->log(
            'test.redaction',
            'security',
            'Testing sensitive data redaction',
            null,
            $old,
            $new
        );

        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->old_values['password']);
        $this->assertEquals('[REDACTED]', $log->old_values['remember_token']);
        $this->assertEquals('admin', $log->old_values['user']);

        $this->assertEquals('[REDACTED]', $log->new_values['password']);
        $this->assertEquals('[REDACTED]', $log->new_values['card_number']);
        $this->assertEquals('[REDACTED]', $log->new_values['cvv']);
        $this->assertEquals('[REDACTED]', $log->new_values['pin']);
        $this->assertEquals('[REDACTED]', $log->new_values['api_key']);
        $this->assertEquals('admin_updated', $log->new_values['user']);
    }

    /**
     * 6. AuditLog model enforces immutability; ordinary update and delete operations throw RuntimeException.
     */
    public function test_audit_log_model_immutability()
    {
        $service = app(AuditLogService::class);
        $log = $service->log(
            'test.immutable',
            'administration',
            'Original immutable log'
        );

        $this->assertNotNull($log);

        // Attempting to update throws RuntimeException
        $updateFailed = false;
        try {
            $log->update(['description' => 'Tampered description']);
        } catch (\RuntimeException $e) {
            $updateFailed = true;
        }
        $this->assertTrue($updateFailed, 'Expected RuntimeException when updating an immutable AuditLog.');

        // Attempting to delete throws RuntimeException
        $deleteFailed = false;
        try {
            $log->delete();
        } catch (\RuntimeException $e) {
            $deleteFailed = true;
        }
        $this->assertTrue($deleteFailed, 'Expected RuntimeException when deleting an immutable AuditLog.');
    }

    /**
     * 7. No public or administrative HTTP routes exist to edit or delete audit logs.
     */
    public function test_no_edit_or_delete_routes_exist_for_audit_logs()
    {
        $admin = $this->getAdmin();

        $service = app(AuditLogService::class);
        $log = $service->log('test.route', 'administration', 'Route check');

        // PUT/PATCH /super_admin/audit_logs/{id} should return 404 or 405
        $putResponse = $this->actingAs($admin)->put("/super_admin/audit_logs/{$log->id}", [
            'description' => 'Tampered',
        ]);
        $this->assertTrue(in_array($putResponse->status(), [404, 405]));

        // DELETE /super_admin/audit_logs/{id} should return 404 or 405
        $delResponse = $this->actingAs($admin)->delete("/super_admin/audit_logs/{$log->id}");
        $this->assertTrue(in_array($delResponse->status(), [404, 405]));
    }

    /**
     * 8. Search, category, action, and status filtering work accurately.
     */
    public function test_search_and_filters_work_correctly()
    {
        $admin = $this->getAdmin();
        $service = app(AuditLogService::class);

        // Seed distinctive logs
        $uniqueQuery = 'UniqueSearchTerm_' . time();
        $service->log('custom.action1', 'finance', "Recorded {$uniqueQuery} item");
        $service->log('custom.action2', 'security', 'Another event', null, [], [], 'failure');

        // Filter by category
        $catResponse = $this->actingAs($admin)->get(route('audit_logs.index', ['category' => 'finance']));
        $catResponse->assertStatus(200);
        $catResponse->assertSee($uniqueQuery);

        // Filter by status
        $failResponse = $this->actingAs($admin)->get(route('audit_logs.index', ['status' => 'failure']));
        $failResponse->assertStatus(200);
        $failResponse->assertSee('custom.action2');

        // Search by query
        $searchResponse = $this->actingAs($admin)->get(route('audit_logs.index', ['search' => $uniqueQuery]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($uniqueQuery);
    }

    /**
     * 9. Real-time feed polling returns newly recorded events since `since_id`.
     */
    public function test_real_time_feed_polling()
    {
        $admin = $this->getAdmin();
        $service = app(AuditLogService::class);

        $initialLog = $service->log('feed.initial', 'academics', 'Initial log for feed');
        $sinceId = $initialLog->id;

        // Add a subsequent event
        $newLog = $service->log('feed.subsequent', 'finance', 'New incoming event for live feed');

        $feedResponse = $this->actingAs($admin)->getJson(route('audit_logs.feed', ['since_id' => $sinceId]));
        $feedResponse->assertStatus(200);
        $feedResponse->assertJson(['success' => true]);

        $events = $feedResponse->json('events');
        $this->assertNotEmpty($events);

        // The new log must be in the events array, while the older log should not
        $ids = array_column($events, 'id');
        $this->assertContains($newLog->id, $ids);
        $this->assertNotContains($initialLog->id, $ids);
    }

    /**
     * 10. Audit log detail endpoint returns JSON with safe fields and diffs.
     */
    public function test_audit_log_show_endpoint()
    {
        $admin = $this->getAdmin();
        $service = app(AuditLogService::class);

        $log = $service->log(
            'test.inspection',
            'academics',
            'Event to inspect in detail',
            null,
            ['grade' => 'B'],
            ['grade' => 'A+']
        );

        $response = $this->actingAs($admin)->getJson(route('audit_logs.show', $log->id));
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $log->id,
            'action' => 'test.inspection',
            'category' => 'academics',
            'old_values' => ['grade' => 'B'],
            'new_values' => ['grade' => 'A+'],
        ]);
    }

    /**
     * 11. CSV export streams safe headers and applies anti-formula injection.
     */
    public function test_audit_logs_csv_export_safely_escapes_formulas()
    {
        $admin = $this->getAdmin();
        $service = app(AuditLogService::class);

        // Formula injection vector starting with '='
        $formulaAttack = '=1+2';
        $service->log('test.csv_attack', 'security', $formulaAttack);

        $response = $this->actingAs($admin)->get(route('audit_logs.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
        $this->assertStringContainsString('"Log ID"', $content);
        // Formula '=' must be prefixed with single quote in exported CSV
        $this->assertStringContainsString("'=1+2", $content);
    }

    /**
     * 12. Stored XSS strings in descriptions and attributes are safely rendered.
     */
    public function test_xss_safety_in_event_values()
    {
        $admin = $this->getAdmin();
        $service = app(AuditLogService::class);

        $xssVector = "<script>alert('XSS_AUDIT_EXPLOIT')</script>";
        $service->log('test.xss', 'security', "Malicious payload: {$xssVector}");

        $response = $this->actingAs($admin)->get(route('audit_logs.index'));
        $response->assertStatus(200);

        // Blade escapes strings via e() into &lt;script&gt;
        $response->assertSee(e($xssVector), false);
        // Raw unescaped executable script tag must NOT be present
        $this->assertStringNotContainsString($xssVector, $response->getContent());
    }

    /**
     * 13. Settings update generates audit record.
     */
    public function test_settings_update_generates_audit_log()
    {
        $admin = $this->getAdmin();

        // Capture original settings to prevent test environment mutation
        $origSettings = Setting::all()->pluck('description', 'type')->toArray();

        try {
            $response = $this->actingAs($admin)->put(route('settings.update'), [
                'system_name' => 'SMS Academy Test Campus',
                'system_title' => 'SMSTEST',
                'phone' => '9800000000',
                'system_email' => 'admin@smsacademy.test',
                'address' => 'Kathmandu, Nepal',
                'current_session' => '2026-2027',
                'lock_exam' => 0,
            ]);

            $response->assertSessionHas('flash_success');

            $this->assertDatabaseHas('audit_logs', [
                'action' => 'settings.update',
                'category' => 'administration',
                'status' => 'success',
            ]);
        } finally {
            // Restore original settings
            foreach ($origSettings as $k => $v) {
                Setting::where('type', $k)->update(['description' => $v]);
            }
        }
    }

    /**
     * 14. Fee structure creation generates payment.create audit log.
     */
    public function test_payment_creation_generates_audit_log()
    {
        $admin = $this->getAdmin();
        $myClass = MyClass::first();

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'title' => 'Annual Examination Fee 2083',
            'amount' => 1500,
            'my_class_id' => $myClass ? $myClass->id : null,
            'method' => 'Cash',
            'description' => 'Mandatory exam fee',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.create',
            'category' => 'finance',
            'status' => 'success',
        ]);
    }
}
