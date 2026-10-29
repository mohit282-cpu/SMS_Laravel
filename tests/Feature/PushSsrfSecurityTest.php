<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Services\PushEndpointValidator;
use App\Services\PwaService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PushSsrfSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::where('user_type', 'admin')->first();
    }

    public function test_push_endpoint_validator_unit_checks()
    {
        // Prohibited IP and hosts
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('127.0.0.1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('10.10.0.1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('192.168.1.1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('172.16.0.1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('169.254.169.254'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('::1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('fe80::1'));
        $this->assertTrue(PushEndpointValidator::isPrivateOrReservedIp('0.0.0.0'));

        // Public IP
        $this->assertFalse(PushEndpointValidator::isPrivateOrReservedIp('8.8.8.8'));
        $this->assertFalse(PushEndpointValidator::isPrivateOrReservedIp('1.1.1.1'));

        // URLs validation
        $this->assertFalse(PushEndpointValidator::isValid('http://fcm.googleapis.com/test')); // No HTTP
        $this->assertFalse(PushEndpointValidator::isValid('https://localhost/push')); // No localhost
        $this->assertFalse(PushEndpointValidator::isValid('https://127.0.0.1/push')); // No loopback
        $this->assertFalse(PushEndpointValidator::isValid('https://169.254.169.254/meta-data')); // No metadata
        $this->assertFalse(PushEndpointValidator::isValid('https://metadata.google.internal/v1')); // No cloud metadata
        $this->assertFalse(PushEndpointValidator::isValid('https://user:pass@fcm.googleapis.com/push')); // No userinfo
        $this->assertFalse(PushEndpointValidator::isValid('not-a-url')); // Malformed

        // Valid URLs
        $this->assertTrue(PushEndpointValidator::isValid('https://fcm.googleapis.com/fcm/send/test-token'));
        $this->assertTrue(PushEndpointValidator::isValid('https://updates.push.services.mozilla.com/wpush/v2/test-token'));
        $this->assertTrue(PushEndpointValidator::isValid('https://wns2-sn1p.notify.windows.com/w/?token=test-token'));
    }

    public function test_registering_loopback_or_private_endpoint_is_rejected()
    {
        $this->actingAs($this->admin);

        $prohibitedEndpoints = [
            'http://localhost:8000/push',
            'https://localhost:8000/push',
            'https://127.0.0.1/push',
            'https://10.0.0.1/push',
            'https://192.168.1.50/push',
            'https://172.16.5.5/push',
            'https://169.254.169.254/latest/meta-data',
            'https://metadata.google.internal/computeMetadata/v1',
            'https://admin:secret@fcm.googleapis.com/fcm/send',
        ];

        foreach ($prohibitedEndpoints as $endpoint) {
            $response = $this->postJson(route('pwa.push.subscribe'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key',
                ],
            ]);

            $response->assertStatus(422);
            $this->assertEquals('error', $response->json('status'), "Failed to block endpoint: $endpoint");
        }
    }

    public function test_registering_legitimate_https_endpoint_succeeds()
    {
        $this->actingAs($this->admin);

        $legitimateEndpoints = [
            'https://fcm.googleapis.com/fcm/send/valid-client-' . time(),
            'https://updates.push.services.mozilla.com/wpush/v2/client-' . time(),
            'https://wns2-sn1p.notify.windows.com/w/?token=token-' . time(),
        ];

        foreach ($legitimateEndpoints as $endpoint) {
            $response = $this->postJson(route('pwa.push.subscribe'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'valid-p256dh-key',
                    'auth' => 'valid-auth-key',
                ],
            ]);

            $response->assertStatus(200);
            $this->assertEquals('success', $response->json('status'));
        }
    }

    public function test_delivery_boundary_skips_invalid_stored_push_subscriptions()
    {
        // Insert a malicious SSRF endpoint directly into the database
        $maliciousSub = PushSubscription::create([
            'user_id' => $this->admin->id,
            'endpoint' => 'https://169.254.169.254/latest/meta-data',
            'endpoint_hash' => hash('sha256', 'https://169.254.169.254/latest/meta-data'),
            'public_key' => 'dummy-public-key',
            'auth_token' => 'dummy-auth-token',
        ]);

        $service = app(PwaService::class);
        $result = $service->sendToUsers([$this->admin->id], 'Test Title', 'Test Body');

        // It must NOT dispatch to the malicious endpoint and must report failed/skipped
        $this->assertContains($result['status'], ['failed', 'no_subscriptions', 'vapid_keys_missing']);
        $this->assertEquals(0, $result['sent']);
    }

    public function test_invalid_endpoint_skipping_does_not_log_sensitive_tokens_in_full()
    {
        config([
            'services.pwa.vapid_public_key' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckj0Ks2S0301eBb4EllxmtxdRhRhB4',
            'services.pwa.vapid_private_key' => '1234567890abcdef1234567890abcdef1234567890a',
            'services.pwa.vapid_subject' => 'mailto:test@example.com',
        ]);

        $sensitiveToken = 'SUPER_SENSITIVE_BEARER_AUTH_TOKEN_SECRET_987654321';
        $maliciousEndpoint = "https://127.0.0.1/push/{$sensitiveToken}";

        PushSubscription::create([
            'user_id' => $this->admin->id,
            'endpoint' => $maliciousEndpoint,
            'endpoint_hash' => hash('sha256', $maliciousEndpoint),
            'public_key' => 'dummy-public-key',
            'auth_token' => 'dummy-auth-token',
        ]);

        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) use ($sensitiveToken) {
                // Ensure the sensitive token/full URL is NOT in the log message
                return !str_contains($message, $sensitiveToken);
            });

        // Allow any other info/error logs
        \Illuminate\Support\Facades\Log::shouldReceive('info')->andReturnNull();
        \Illuminate\Support\Facades\Log::shouldReceive('error')->andReturnNull();

        $service = app(PwaService::class);
        $service->sendToUsers([$this->admin->id], 'Title', 'Body');
    }
}
