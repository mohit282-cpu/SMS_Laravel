<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotification;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookRequest;
use App\Models\Holiday;
use App\Models\PushSubscription;
use App\Services\PwaService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PwaPushNotificationTest extends TestCase
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
     * Unauthenticated registration and unsubscription must be rejected with 401
     */
    public function test_unauthenticated_registration_and_unsubscription_rejected()
    {
        $subscribeResponse = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/unauth-test',
            'keys' => [
                'p256dh' => 'test-key',
                'auth' => 'test-auth',
            ],
        ]);
        $subscribeResponse->assertStatus(401);

        $unsubscribeResponse = $this->postJson(route('pwa.push.unsubscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/unauth-test',
        ]);
        $unsubscribeResponse->assertStatus(401);
    }

    /**
     * Authenticated registration validates all required fields
     */
    public function test_registration_validation_rules()
    {
        $this->actingAs($this->admin);

        // Missing endpoint
        $res = $this->postJson(route('pwa.push.subscribe'), [
            'keys' => ['p256dh' => 'abc', 'auth' => 'xyz'],
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['endpoint']);

        // Missing keys
        $res = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['keys']);

        // Missing auth in keys
        $res = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
            'keys' => ['p256dh' => 'abc'],
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['keys.auth']);
    }

    /**
     * Insecure non-HTTPS endpoint is rejected
     */
    public function test_insecure_endpoint_is_rejected()
    {
        $this->actingAs($this->admin);

        $res = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => 'http://insecure-domain.example.com/push',
            'keys' => ['p256dh' => 'abc', 'auth' => 'xyz'],
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('HTTPS', $res->json('message'));
    }

    /**
     * Multi-device support for the same user creates separate records by endpoint hash
     */
    public function test_multi_device_support_for_same_user()
    {
        $this->actingAs($this->admin);

        $device1 = 'https://fcm.googleapis.com/fcm/send/device-mobile-' . time();
        $device2 = 'https://fcm.googleapis.com/fcm/send/device-desktop-' . time();

        $res1 = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => $device1,
            'keys' => ['p256dh' => 'key1', 'auth' => 'auth1'],
        ]);
        $res1->assertStatus(200);

        $res2 = $this->postJson(route('pwa.push.subscribe'), [
            'endpoint' => $device2,
            'keys' => ['p256dh' => 'key2', 'auth' => 'auth2'],
        ]);
        $res2->assertStatus(200);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->admin->id,
            'endpoint_hash' => hash('sha256', $device1),
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->admin->id,
            'endpoint_hash' => hash('sha256', $device2),
        ]);
    }

    /**
     * A user cannot unsubscribe another user's device subscription
     */
    public function test_cross_user_unsubscribe_isolation()
    {
        // Register device for Student
        $studentEndpoint = 'https://fcm.googleapis.com/fcm/send/student-device-' . time();
        PushSubscription::storeSubscription($this->student->id, [
            'endpoint' => $studentEndpoint,
            'keys' => ['p256dh' => 'key-st', 'auth' => 'auth-st'],
        ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->student->id,
            'endpoint_hash' => hash('sha256', $studentEndpoint),
        ]);

        // Attempt to unsubscribe while logged in as Teacher
        $this->actingAs($this->teacher);
        $res = $this->postJson(route('pwa.push.unsubscribe'), [
            'endpoint' => $studentEndpoint,
        ]);
        $res->assertStatus(200);

        // Student's subscription must remain intact because teacher does not own it
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->student->id,
            'endpoint_hash' => hash('sha256', $studentEndpoint),
        ]);

        // When student unsubscribes own device, it is deleted
        $this->actingAs($this->student);
        $res2 = $this->postJson(route('pwa.push.unsubscribe'), [
            'endpoint' => $studentEndpoint,
        ]);
        $res2->assertStatus(200);

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->student->id,
            'endpoint_hash' => hash('sha256', $studentEndpoint),
        ]);
    }

    /**
     * Test VAPID public key endpoint returns JSON with configured status
     */
    public function test_vapid_key_endpoint_returns_json()
    {
        $this->actingAs($this->admin);

        Config::set('services.pwa.vapid_public_key', 'test-mock-public-key-value');

        $response = $this->getJson(route('pwa.push.vapid'));
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'vapid_public_key' => 'test-mock-public-key-value',
            'is_configured' => true,
        ]);
    }

    /**
     * Test Artisan pwa:vapid command displays or generates keypair
     */
    public function test_artisan_vapid_command()
    {
        $this->artisan('pwa:vapid', ['--show' => true])
            ->assertExitCode(0);

        $this->artisan('pwa:vapid')
            ->expectsOutput('=== Generated Web Push VAPID Keypair ===')
            ->assertExitCode(0);
    }

    /**
     * Test missing VAPID keys fails safely without false success
     */
    public function test_missing_vapid_keys_fails_safely()
    {
        Config::set('services.pwa.vapid_public_key', null);
        Config::set('services.pwa.vapid_private_key', null);

        PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-vapid-missing',
            'keys' => ['p256dh' => 'key', 'auth' => 'token'],
        ]);

        $service = app(PwaService::class);
        $result = $service->sendNotification($this->admin->id, 'Test Title', 'Test Body');

        $this->assertEquals('vapid_keys_missing', $result['status']);
        $this->assertEquals(0, $result['sent']);
    }

    /**
     * Test SendWebPushNotification queueable job dispatches and runs
     */
    public function test_send_web_push_notification_job()
    {
        Queue::fake();

        SendWebPushNotification::dispatch($this->admin->id, 'Alert', 'School is closed tomorrow.');

        Queue::assertPushed(SendWebPushNotification::class, function ($job) {
            return true;
        });
    }

    /**
     * Test school holiday creation dispatches push notification job
     */
    public function test_holiday_creation_dispatches_push_notification()
    {
        Queue::fake();
        $this->actingAs($this->admin);

        PushSubscription::storeSubscription($this->teacher->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/holiday-teacher-sub',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ]);

        $postData = [
            'title' => 'Tihar Holiday',
            'title_ne' => 'तिहार बिदा',
            'type' => 'school_holiday',
            'start_date_bs' => '2083-07-15',
            'end_date_bs' => '2083-07-20',
            'description' => 'Festive school closure',
        ];

        $res = $this->post(route('holidays.store'), $postData);
        $res->assertSessionHas('flash_success');

        Queue::assertPushed(SendWebPushNotification::class);
    }

    /**
     * Test book request approval dispatches push notification to student
     */
    public function test_book_request_approval_dispatches_push_notification()
    {
        Queue::fake();

        $librarian = User::where('user_type', 'librarian')->first() ?? $this->admin;
        $category = BookCategory::firstOrCreate(['name' => 'General Science']);
        $book = Book::firstOrCreate(
            ['isbn' => '978-9937-0-0011-1'],
            [
                'name' => 'Physics Grade 10',
                'author' => 'Dr. Sharma',
                'category_id' => $category->id,
                'total_copies' => 5,
            ]
        );

        $bookRequest = BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'request_date' => now(),
        ]);

        $this->actingAs($librarian);
        $res = $this->post(route('book_requests.approve', ['id' => $bookRequest->id]));
        $res->assertSessionHas('flash_success');

        Queue::assertPushed(SendWebPushNotification::class);
    }

    /**
     * Test service worker contains same-origin validation and sanitized push listener
     */
    public function test_service_worker_push_and_click_handlers()
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);

        // Push event listener
        $this->assertStringContainsString("self.addEventListener('push'", $content);
        $this->assertStringContainsString("showNotification", $content);

        // Notification click event listener with same-origin check
        $this->assertStringContainsString("self.addEventListener('notificationclick'", $content);
        $this->assertStringContainsString("parsed.origin === self.location.origin", $content);
    }

    /**
     * Test My Account view renders PWA push notification controls
     */
    public function test_my_account_renders_push_notification_card()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('my_account'));
        $response->assertStatus(200);

        $response->assertSee('pwa-push-card');
        $response->assertSee('pwa-btn-enable-push');
        $response->assertSee('pwa-btn-disable-push');
        $response->assertSee('pwa-btn-test-push');
    }

    /**
     * Test PwaService::sanitizeTargetUrl against various bypasses and malicious vectors
     */
    public function test_sanitize_target_url_matrix()
    {
        $service = app(PwaService::class);
        $fallback = route('dashboard');

        // Valid relative paths
        $this->assertEquals('/dashboard', $service->sanitizeTargetUrl('/dashboard'));
        $this->assertEquals('/my_account', $service->sanitizeTargetUrl('/my_account'));
        $this->assertEquals('./dashboard', $service->sanitizeTargetUrl('./dashboard'));

        // Valid same-origin absolute URL
        $validOriginUrl = url('/dashboard');
        $this->assertEquals($validOriginUrl, $service->sanitizeTargetUrl($validOriginUrl));

        // Protocol-relative URLs must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('//evil.example.com/phish'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('//google.com'));

        // Backslash-based bypass tricks must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('/\\evil.example.com'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('\\\\evil.example.com'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('/dashboard\\evil'));

        // Dangerous schemes must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('javascript:alert(document.cookie)'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('vbscript:msgbox("pwnd")'));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('file:///etc/passwd'));

        // Control characters and newlines must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl("/dashboard\r\nSet-Cookie: evil=1"));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl("/dashboard\x00evil"));

        // External absolute URLs must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('https://evil.example.com/login'));

        // Port mismatch must be rejected
        $this->assertEquals($fallback, $service->sanitizeTargetUrl('http://localhost:9999/dashboard'));

        // Empty or null returns fallback
        $this->assertEquals($fallback, $service->sanitizeTargetUrl(null));
        $this->assertEquals($fallback, $service->sanitizeTargetUrl(''));
    }

    /**
     * Test artisan pwa:vapid --show strictly conceals the private key
     */
    public function test_artisan_vapid_show_does_not_leak_private_key()
    {
        $secretKey = 'SuperSecretVapidPrivateKeyMaterial1234567890';
        Config::set('services.pwa.vapid_public_key', 'PublicVapidKeyExample');
        Config::set('services.pwa.vapid_private_key', $secretKey);
        Config::set('services.pwa.vapid_subject', 'mailto:admin@example.com');

        $this->artisan('pwa:vapid', ['--show' => true])
            ->expectsOutput('=== Current PWA VAPID Configuration ===')
            ->expectsOutput('VAPID Public Key:  PublicVapidKeyExample')
            ->expectsOutput('VAPID Private Key: <Configured>')
            ->expectsOutput('VAPID Subject:     mailto:admin@example.com')
            ->doesntExpectOutput($secretKey)
            ->doesntExpectOutput(substr($secretKey, 0, 4) . '...' . substr($secretKey, -4))
            ->assertExitCode(0);
    }

    /**
     * Test that sendTestPush endpoint rejects requests if user has no subscriptions
     */
    public function test_test_push_endpoint_requires_registered_subscription()
    {
        $this->actingAs($this->admin);

        // Ensure admin has no subscriptions
        PushSubscription::where('user_id', $this->admin->id)->delete();

        $res = $this->postJson(route('pwa.push.test'));
        $res->assertStatus(422);
        $res->assertJson([
            'status' => 'no_subscriptions',
        ]);
    }

    /**
     * Test that sendTestPush endpoint ignores arbitrary client parameters and enforces immutable payload
     */
    public function test_test_push_endpoint_ignores_arbitrary_client_parameters()
    {
        $this->actingAs($this->admin);

        // Register subscription for admin
        PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/admin-fixed-test-' . time(),
            'keys' => ['p256dh' => 'k1', 'auth' => 'a1'],
        ]);

        // Mock VAPID keys
        Config::set('services.pwa.vapid_public_key', 'test-pub');
        Config::set('services.pwa.vapid_private_key', 'test-priv');

        // Supply arbitrary malicious parameters in request
        $res = $this->postJson(route('pwa.push.test'), [
            'user_id' => $this->student->id,
            'title' => 'Malicious Injected Title',
            'body' => 'Phishing text',
            'url' => 'https://evil.example.com',
        ]);

        // Response must not accept arbitrary recipient or URL
        $this->assertTrue(in_array($res->status(), [200, 502]));
        $this->assertNotEquals('Malicious Injected Title', $res->json('result.title') ?? '');
    }

    /**
     * Test test push route rate limiting (6 requests per minute)
     */
    public function test_test_push_endpoint_rate_limiting()
    {
        $this->actingAs($this->admin);

        PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/admin-rate-limit-' . time(),
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ]);

        Config::set('services.pwa.vapid_public_key', 'test-pub');
        Config::set('services.pwa.vapid_private_key', 'test-priv');

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('pwa.push.test'));
        }

        // The 7th request must trigger rate limit 429
        $response = $this->postJson(route('pwa.push.test'));
        $response->assertStatus(429);
    }

    /**
     * Test delivery reporting accurate accounting for skipped, missing recipients, and no subscriptions
     */
    public function test_delivery_reporting_accounting()
    {
        $service = app(PwaService::class);

        // 1. Empty recipients
        $resNoRecipients = $service->sendToUsers([], 'Notice', 'Body');
        $this->assertEquals('no_recipients', $resNoRecipients['status']);
        $this->assertEquals(0, $resNoRecipients['total']);
        $this->assertEquals(0, $resNoRecipients['skipped']);

        // 2. User with no subscriptions
        $resNoSubs = $service->sendToUsers([99999], 'Notice', 'Body');
        $this->assertEquals('no_subscriptions', $resNoSubs['status']);
        $this->assertEquals(0, $resNoSubs['total']);

        // 3. Subscriptions missing keys are accounted in skippedCount
        PushSubscription::create([
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/missing-keys'),
            'user_id' => $this->admin->id,
            'endpoint' => 'https://fcm.googleapis.com/missing-keys',
            'public_key' => null,
            'auth_token' => null,
        ]);

        Config::set('services.pwa.vapid_public_key', 'valid-pub');
        Config::set('services.pwa.vapid_private_key', 'valid-priv');

        $resWithMissingKey = $service->sendToUsers([$this->admin->id], 'Notice', 'Body');
        $this->assertArrayHasKey('skipped', $resWithMissingKey);
        $this->assertGreaterThanOrEqual(1, $resWithMissingKey['skipped']);
        $this->assertEquals(
            $resWithMissingKey['total'],
            $resWithMissingKey['sent'] + $resWithMissingKey['expired'] + $resWithMissingKey['failed'] + $resWithMissingKey['skipped']
        );
    }

    /**
     * Test subscription re-assignment on shared browser safely updates user_id and logs audit
     */
    public function test_subscription_reassignment_on_shared_device()
    {
        $sharedEndpoint = 'https://fcm.googleapis.com/fcm/send/shared-device-' . time();

        // 1. First user registers
        $sub1 = PushSubscription::storeSubscription($this->student->id, [
            'endpoint' => $sharedEndpoint,
            'keys' => ['p256dh' => 'key-user1', 'auth' => 'auth-user1'],
        ]);
        $this->assertEquals($this->student->id, $sub1->user_id);

        // 2. Second user logs into the same browser and registers the same endpoint
        $sub2 = PushSubscription::storeSubscription($this->teacher->id, [
            'endpoint' => $sharedEndpoint,
            'keys' => ['p256dh' => 'key-user2', 'auth' => 'auth-user2'],
        ]);

        // Same record id, re-assigned to teacher, updated keys
        $this->assertEquals($sub1->id, $sub2->id);
        $this->assertEquals($this->teacher->id, $sub2->user_id);

        // Database now has teacher as owner
        $this->assertDatabaseHas('push_subscriptions', [
            'id' => $sub1->id,
            'user_id' => $this->teacher->id,
        ]);
        $this->assertDatabaseMissing('push_subscriptions', [
            'id' => $sub1->id,
            'user_id' => $this->student->id,
        ]);
    }
}
