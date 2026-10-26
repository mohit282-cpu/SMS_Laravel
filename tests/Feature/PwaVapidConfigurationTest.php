<?php

namespace Tests\Feature;

use App\Services\PwaService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PwaVapidConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('user_type', 'admin')->first()
            ?? User::where('user_type', 'super_admin')->first();
    }

    /**
     * Test vapidPublicKey endpoint requires authentication
     */
    public function test_vapid_endpoint_requires_authentication()
    {
        $response = $this->getJson(route('pwa.push.vapid'));
        $response->assertStatus(401);
    }

    /**
     * Test vapidPublicKey endpoint returns 503 and safe error message when key is unconfigured
     */
    public function test_vapid_endpoint_returns_503_when_unconfigured()
    {
        $this->actingAs($this->admin);
        Config::set('services.pwa.vapid_public_key', null);

        $response = $this->getJson(route('pwa.push.vapid'));

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'error',
            'message' => 'VAPID public key is not configured on the server.',
            'is_configured' => false,
            'vapid_public_key' => null,
        ]);

        // Verifies no-cache headers to prevent stale browser caching of missing key
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertTrue(
            strpos($cacheControl, 'no-cache') !== false ||
            strpos($cacheControl, 'no-store') !== false,
            'Endpoint must send no-cache headers when unconfigured'
        );
    }

    /**
     * Test vapidPublicKey endpoint returns 200 and public key when properly configured
     */
    public function test_vapid_endpoint_returns_public_key_when_configured()
    {
        $this->actingAs($this->admin);
        $testPubKey = 'BB0llw58iLjdLO4rtbxLrhnSy3SXa3srxGjnhLcxMdabTOW6yYVaaivP6h15Fqb-pI5EkpcwIokpaeO0qdbL3T8';
        $testPrivKey = 'SENSITIVE_TEST_PRIVATE_KEY_DO_NOT_EXPOSE';

        Config::set('services.pwa.vapid_public_key', $testPubKey);
        Config::set('services.pwa.vapid_private_key', $testPrivKey);

        $response = $this->getJson(route('pwa.push.vapid'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'is_configured' => true,
            'vapid_public_key' => $testPubKey,
        ]);

        // Verify private key is never exposed in response
        $this->assertStringNotContainsString($testPrivKey, $response->getContent());
        $this->assertArrayNotHasKey('vapid_private_key', $response->json());
        $this->assertArrayNotHasKey('private_key', $response->json());
    }

    /**
     * Test rendered HTML pages never expose the private key
     */
    public function test_private_key_never_exposed_in_rendered_html()
    {
        $testPubKey = 'TEST_PUBLIC_KEY_SAFE_FOR_HTML';
        $testPrivKey = 'SECRET_VAPID_PRIVATE_KEY_MUST_STAY_HIDDEN_12345';

        Config::set('services.pwa.vapid_public_key', $testPubKey);
        Config::set('services.pwa.vapid_private_key', $testPrivKey);

        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Public key should be rendered or referenced
        $content = $response->getContent();
        $this->assertStringContainsString($testPubKey, $content);

        // Private key MUST NEVER appear in rendered HTML
        $this->assertStringNotContainsString($testPrivKey, $content);
    }

    /**
     * Test VAPID Artisan command displays status without exposing private key
     */
    public function test_artisan_vapid_show_does_not_leak_private_key()
    {
        $testPubKey = 'TEST_PUBLIC_KEY_COMMAND';
        $testPrivKey = 'SUPER_SECRET_PRIVATE_KEY_COMMAND_HIDDEN';

        Config::set('services.pwa.vapid_public_key', $testPubKey);
        Config::set('services.pwa.vapid_private_key', $testPrivKey);

        Artisan::call('pwa:vapid', ['--show' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString($testPubKey, $output);
        $this->assertStringContainsString('<Configured>', $output);
        $this->assertStringNotContainsString($testPrivKey, $output);
    }

    /**
     * Test Artisan save refuses overwrite when keys already exist unless --force is passed
     */
    public function test_artisan_save_protects_existing_keys_from_accidental_overwrite()
    {
        $testPubKey = 'EXISTING_PUBLIC_KEY';
        $testPrivKey = 'EXISTING_PRIVATE_KEY';

        Config::set('services.pwa.vapid_public_key', $testPubKey);
        Config::set('services.pwa.vapid_private_key', $testPrivKey);

        Artisan::call('pwa:vapid', ['--save' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('already configured', $output);
        $this->assertStringContainsString('--force', $output);
    }

    /**
     * Test PwaService uses config helper rather than direct env calls
     */
    public function test_pwa_service_relies_on_config_not_env()
    {
        $testPubKey = 'CONFIG_LOADED_PUBLIC_KEY_ABC123';
        $testPrivKey = 'CONFIG_LOADED_PRIVATE_KEY_XYZ789';

        Config::set('services.pwa.vapid_public_key', $testPubKey);
        Config::set('services.pwa.vapid_private_key', $testPrivKey);

        $service = app(PwaService::class);
        $this->assertTrue($service->isPushConfigured());
        $this->assertEquals($testPubKey, $service->getVapidPublicKey());
    }

    /**
     * Test PwaService safely handles missing VAPID configuration during sendToUsers
     */
    public function test_pwa_service_safe_diagnostic_when_unconfigured()
    {
        Config::set('services.pwa.vapid_public_key', null);
        Config::set('services.pwa.vapid_private_key', null);

        $service = app(PwaService::class);
        $this->assertFalse($service->isPushConfigured());

        \App\Models\PushSubscription::storeSubscription($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-vapid-diag-' . time(),
            'keys' => ['p256dh' => 'test-p256dh', 'auth' => 'test-auth'],
        ]);

        $result = $service->sendToUsers([$this->admin->id], 'Test Title', 'Test Body');
        $this->assertEquals('vapid_keys_missing', $result['status']);
        $this->assertEquals('VAPID keys not configured in server configuration.', $result['message']);
    }
}
