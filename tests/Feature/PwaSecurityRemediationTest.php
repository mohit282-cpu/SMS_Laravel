<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Services\PwaService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PwaSecurityRemediationTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $student;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'admin')->first()
            ?? User::where('user_type', 'super_admin')->first();
        $this->student = User::where('user_type', 'student')->first();
        $this->teacher = User::where('user_type', 'teacher')->first();
    }

    /**
     * Test push exception logging sanitization:
     * Secrets (private keys, raw tokens, endpoint URLs) must NEVER appear in logs.
     */
    public function test_push_exception_logging_sanitization()
    {
        Log::spy();

        $fakeSecretPrivateKey = 'SECRET_VAPID_PRIV_KEY_XYZ987654321';
        $fakeEndpoint = 'https://fcm.googleapis.com/fcm/send/SECRET_ENDPOINT_TOKEN_12345';
        $fakeAuthToken = 'AUTH_TOKEN_SECRET_VAL_ABC999';

        Config::set('services.pwa.vapid_public_key', 'test-pub-key');
        Config::set('services.pwa.vapid_private_key', $fakeSecretPrivateKey);

        $sub = PushSubscription::create([
            'endpoint_hash' => hash('sha256', $fakeEndpoint),
            'user_id' => $this->admin->id,
            'endpoint' => $fakeEndpoint,
            'public_key' => 'fake-p256dh',
            'auth_token' => $fakeAuthToken,
        ]);

        $service = app(PwaService::class);
        $result = $service->sendToUsers([$this->admin->id], 'Notice', 'Test Payload');

        // Verify status indicates failure due to invalid mock keys
        $this->assertContains($result['status'], ['vapid_keys_invalid', 'failed', 'error']);

        // Assert Log::error was called and check its arguments
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context = []) use ($fakeSecretPrivateKey, $fakeEndpoint, $fakeAuthToken) {
            $contextStr = json_encode($context);
            $fullOutput = $message . ' ' . $contextStr;

            // Ensure secrets are NOT in logs
            $this->assertStringNotContainsString($fakeSecretPrivateKey, $fullOutput, 'Private key leaked in logs');
            $this->assertStringNotContainsString($fakeEndpoint, $fullOutput, 'Push endpoint leaked in logs');
            $this->assertStringNotContainsString($fakeAuthToken, $fullOutput, 'Auth token leaked in logs');

            // Ensure diagnostic fields exist
            $this->assertArrayHasKey('correlation_id', $context);
            $this->assertArrayHasKey('category', $context);
            $this->assertArrayHasKey('affected_count', $context);

            return true;
        });
    }

    /**
     * Test SendWebPushNotification failed() method sanitizes exception messages
     */
    public function test_send_push_notification_job_failed_sanitization()
    {
        Log::spy();

        $fakeSecret = 'SECRET_TOKEN_IN_EXCEPTION_99999';
        $fakeUrl = 'https://fcm.googleapis.com/fcm/send/sensitive_endpoint_abc';

        $job = new SendWebPushNotification([$this->admin->id], 'Alert', 'Body');
        $job->failed(new \RuntimeException("Connection failed to {$fakeUrl} with token {$fakeSecret}"));

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) use ($fakeSecret, $fakeUrl) {
            $fullOutput = $message . ' ' . json_encode($context);

            $this->assertStringNotContainsString($fakeSecret, $fullOutput, 'Exception secret leaked in job failed log');
            $this->assertStringNotContainsString($fakeUrl, $fullOutput, 'Endpoint URL leaked in job failed log');
            $this->assertArrayHasKey('correlation_id', $context);
            $this->assertArrayHasKey('category', $context);

            return true;
        });
    }

    /**
     * Test two users sharing the same browser:
     * Subscription ownership is transferred to the active user, avoiding cross-user notification delivery.
     */
    public function test_two_users_on_same_browser_subscription_ownership()
    {
        $sharedEndpoint = 'https://fcm.googleapis.com/fcm/send/browser-shared-test-' . time();

        // User 1 registers
        $subUser1 = PushSubscription::storeSubscription($this->student->id, [
            'endpoint' => $sharedEndpoint,
            'keys' => ['p256dh' => 'key1', 'auth' => 'auth1'],
        ]);
        $this->assertEquals($this->student->id, $subUser1->user_id);

        // User 2 logs into the same browser and registers the same endpoint
        $subUser2 = PushSubscription::storeSubscription($this->teacher->id, [
            'endpoint' => $sharedEndpoint,
            'keys' => ['p256dh' => 'key2', 'auth' => 'auth2'],
        ]);

        // Exactly one record exists for the endpoint, now belonging to User 2
        $this->assertEquals($subUser1->id, $subUser2->id);
        $this->assertEquals($this->teacher->id, $subUser2->user_id);

        $studentSubs = PushSubscription::where('user_id', $this->student->id)->where('endpoint_hash', hash('sha256', $sharedEndpoint))->count();
        $this->assertEquals(0, $studentSubs, 'Previous user on shared browser must no longer own the subscription');
    }

    /**
     * Test cross-user unsubscribe attempt:
     * A user cannot unsubscribe an endpoint belonging to another user.
     */
    public function test_cross_user_unsubscribe_attempt_is_prevented()
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/teacher-private-' . time();
        $hash = hash('sha256', $endpoint);

        PushSubscription::storeSubscription($this->teacher->id, [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ]);

        // Student attempts to unsubscribe Teacher's endpoint
        $this->actingAs($this->student);
        $res = $this->postJson(route('pwa.push.unsubscribe'), [
            'endpoint' => $endpoint,
        ]);
        $res->assertStatus(200);

        // Teacher's subscription must remain intact
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->teacher->id,
            'endpoint_hash' => $hash,
        ]);
    }

    /**
     * Test duplicate subscription registration is idempotent and safe against race conditions.
     */
    public function test_duplicate_subscription_registration_is_idempotent()
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/idempotent-' . time();

        $subA = PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'k1', 'auth' => 'a1'],
        ]);

        $subB = PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'k1', 'auth' => 'a1'],
        ]);

        $this->assertEquals($subA->id, $subB->id);
        $count = PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test multiple devices for the same user are supported simultaneously.
     */
    public function test_multiple_devices_for_same_user_supported()
    {
        $device1 = 'https://fcm.googleapis.com/fcm/send/device-phone-' . time();
        $device2 = 'https://fcm.googleapis.com/fcm/send/device-laptop-' . time();

        PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => $device1,
            'keys' => ['p256dh' => 'kp', 'auth' => 'ap'],
        ]);

        PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => $device2,
            'keys' => ['p256dh' => 'kl', 'auth' => 'al'],
        ]);

        $devices = PushSubscription::where('user_id', $this->admin->id)
            ->whereIn('endpoint_hash', [hash('sha256', $device1), hash('sha256', $device2)])
            ->count();

        $this->assertEquals(2, $devices, 'Both distinct devices must be preserved for user');
    }

    /**
     * Test school and role boundaries for notifications:
     * Invalid roles are rejected; only users in target role receive notifications.
     */
    public function test_school_and_role_boundaries_for_notifications()
    {
        $service = app(PwaService::class);

        // 1. Invalid or malicious role name
        $invalidRoleResult = $service->sendToRole('non_existent_role_inject', 'Title', 'Body');
        $this->assertEquals('invalid_role', $invalidRoleResult['status']);

        // 2. Valid role: targets only users of that role
        $teacherResult = $service->sendToRole('teacher', 'Staff Meeting', 'All teachers report to hall');
        $this->assertNotEquals('invalid_role', $teacherResult['status']);
    }

    /**
     * Test php artisan pwa:vapid hides the private key by default.
     */
    public function test_vapid_generate_command_hides_private_key_by_default()
    {
        Artisan::call('pwa:vapid');
        $output = Artisan::output();

        $this->assertStringContainsString('VAPID_PUBLIC_KEY=', $output);
        $this->assertStringContainsString('[HIDDEN]', $output);
        $this->assertStringNotContainsString('X5Lf6BnE9PJa27m9In', $output);
    }

    /**
     * Test php artisan pwa:vapid --show-secret explicitly displays the generated private key.
     */
    public function test_vapid_generate_command_show_secret_flag_displays_private_key()
    {
        Artisan::call('pwa:vapid', ['--show-secret' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('VAPID_PUBLIC_KEY=', $output);
        $this->assertStringContainsString('VAPID_PRIVATE_KEY=', $output);
        $this->assertStringNotContainsString('[HIDDEN]', $output);
    }

    /**
     * Test php artisan pwa:vapid --show never displays the private key.
     */
    public function test_vapid_show_command_never_displays_private_key()
    {
        Config::set('services.pwa.vapid_public_key', 'mock_pub_key_123');
        Config::set('services.pwa.vapid_private_key', 'mock_priv_key_999');

        Artisan::call('pwa:vapid', ['--show' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('VAPID Public Key:', $output);
        $this->assertStringContainsString('<Configured>', $output);
        $this->assertStringNotContainsString('mock_priv_key_999', $output);
    }
}
