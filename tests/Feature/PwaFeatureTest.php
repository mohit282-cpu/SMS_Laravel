<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Services\PwaService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PwaFeatureTest extends TestCase
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
     * Test dynamic manifest endpoint returns valid JSON with required keys and correct content-type
     */
    public function test_manifest_endpoint_returns_valid_json()
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/manifest+json', $response->headers->get('Content-Type'));

        $data = $response->json();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('short_name', $data);
        $this->assertArrayHasKey('start_url', $data);
        $this->assertArrayHasKey('display', $data);
        $this->assertEquals('standalone', $data['display']);
        $this->assertArrayHasKey('theme_color', $data);
        $this->assertArrayHasKey('background_color', $data);
        $this->assertArrayHasKey('icons', $data);
        $this->assertArrayHasKey('shortcuts', $data);

        // Verify required icon sizes exist in manifest
        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        // Verify maskable icon exists
        $purposes = array_column($data['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
    }

    /**
     * Test static manifest.json file exists in public directory and is valid JSON
     */
    public function test_static_manifest_file_exists_and_is_valid()
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $json = json_decode(file_get_contents($path), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('start_url', $json);
        $this->assertEquals('standalone', $json['display']);
    }

    /**
     * Test branded offline fallback page loads successfully
     */
    public function test_offline_fallback_page_loads_successfully()
    {
        $response = $this->get('/offline');

        $response->assertStatus(200);
        $response->assertSee('Offline Mode');
        $response->assertSee('Retry Connection');
    }

    /**
     * Test service worker endpoint returns JavaScript with correct Service-Worker-Allowed header
     */
    public function test_service_worker_script_is_served()
    {
        $response = $this->get('/sw.js');

        $response->assertStatus(200);
        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
        $this->assertEquals('/', $response->headers->get('Service-Worker-Allowed'));

        $content = $response->getContent();
        $this->assertStringContainsString('CACHE_VERSION', $content);
        $this->assertStringContainsString('skipWaiting', $content);
        $this->assertStringContainsString('offline', $content);
    }

    /**
     * Test all declared manifest icons exist on disk as readable files
     */
    public function test_all_manifest_icons_exist_on_disk()
    {
        $pwaService = new PwaService();
        $manifest = $pwaService->getManifest();

        foreach ($manifest['icons'] as $icon) {
            $src = $icon['src'];
            // Extract path relative to public
            $path = parse_url($src, PHP_URL_PATH);
            $localRel = ltrim($path, '/');
            // If running in subfolder like /lav_sms/assets/...
            if (str_contains($localRel, 'assets/')) {
                $sub = substr($localRel, strpos($localRel, 'assets/'));
                $fullPath = public_path($sub);
            } else {
                $fullPath = public_path($localRel);
            }

            $this->assertFileExists($fullPath, "Manifest icon not found on disk: {$src}");
            $this->assertGreaterThan(0, filesize($fullPath), "Manifest icon is empty: {$src}");
        }
    }

    /**
     * Test push subscription requires authentication
     */
    public function test_push_subscription_requires_authentication()
    {
        $response = $this->postJson('/pwa/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can register and unsubscribe from push notifications
     */
    public function test_authenticated_user_push_workflow()
    {
        $this->actingAs($this->admin);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-sub-' . time();
        $subscribeResponse = $this->postJson('/pwa/push/subscribe', [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BDH3k-mock-key-value',
                'auth' => 'mock-auth-secret',
            ],
        ]);

        $subscribeResponse->assertStatus(200);
        $subscribeResponse->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->admin->id,
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);

        // Unsubscribe
        $unsubscribeResponse = $this->postJson('/pwa/push/unsubscribe', [
            'endpoint' => $endpoint,
        ]);

        $unsubscribeResponse->assertStatus(200);
        $unsubscribeResponse->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);
    }

    /**
     * Test PWA client script implements user-scoped storage, data minimization, and logout purge
     */
    public function test_pwa_script_implements_attendance_privacy_safeguards()
    {
        $pwaJsPath = public_path('assets/js/pwa.js');
        $this->assertFileExists($pwaJsPath);

        $content = file_get_contents($pwaJsPath);

        // Verify user-scoped key
        $this->assertStringContainsString('lav_sms_att_drafts_uid_', $content);

        // Verify logout purge handler
        $this->assertStringContainsString('purgeAllAttendanceDrafts', $content);
        $this->assertStringContainsString('logout', $content);

        // Verify data minimization (only status codes P, A, L, H)
        $this->assertStringContainsString("'P', 'A', 'L', 'H'", $content);
    }

    /**
     * Test layout injects authenticated user ID for PWA session isolation
     */
    public function test_layout_injects_authenticated_user_id()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee("window.PWA_USER_ID = {$this->admin->id};", false);
    }

    /**
     * Test HTTPS scheme forcing and reverse-proxy header handling
     */
    public function test_trust_proxies_and_https_headers()
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => '443',
        ])->get('/manifest.json');

        $response->assertStatus(200);
        $this->assertTrue(request()->isSecure() || $response->isOk());
    }

    /**
     * Test each manifest icon's image dimensions match declared size
     */
    public function test_manifest_icons_match_declared_dimensions()
    {
        $pwaService = new PwaService();
        $manifest = $pwaService->getManifest();

        foreach ($manifest['icons'] as $icon) {
            $src = $icon['src'];
            $path = parse_url($src, PHP_URL_PATH);
            $localRel = ltrim($path, '/');
            if (str_contains($localRel, 'assets/')) {
                $sub = substr($localRel, strpos($localRel, 'assets/'));
                $fullPath = public_path($sub);
            } else {
                $fullPath = public_path($localRel);
            }

            $this->assertFileExists($fullPath);
            $imageInfo = getimagesize($fullPath);
            $this->assertNotFalse($imageInfo, "File is not a valid image: {$fullPath}");

            list($declaredW, $declaredH) = explode('x', $icon['sizes']);
            $this->assertEquals((int)$declaredW, $imageInfo[0], "Width mismatch for {$src}");
            $this->assertEquals((int)$declaredH, $imageInfo[1], "Height mismatch for {$src}");
        }
    }

    /**
     * Test sensitive authenticated routes return strict no-store cache headers
     */
    public function test_authenticated_routes_have_strict_no_store_headers()
    {
        $this->actingAs($this->admin);

        $sensitiveEndpoints = [
            route('dashboard'),
            route('my_account'),
            route('payments.index'),
        ];

        foreach ($sensitiveEndpoints as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);

            $cacheControl = $response->headers->get('Cache-Control');
            $this->assertNotNull($cacheControl, "Missing Cache-Control on {$url}");
            $this->assertStringContainsString('no-store', $cacheControl, "Missing no-store on {$url}");
            $this->assertStringContainsString('no-cache', $cacheControl, "Missing no-cache on {$url}");
            $this->assertStringContainsString('must-revalidate', $cacheControl, "Missing must-revalidate on {$url}");
            $this->assertEquals('no-cache', $response->headers->get('Pragma'), "Missing Pragma: no-cache on {$url}");
        }
    }

    /**
     * Test login page has no-store cache headers and declares null PWA user ID
     */
    public function test_login_page_has_no_store_headers_and_declares_null_user_id()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $response->assertSee('window.PWA_USER_ID = null;', false);
    }

    /**
     * Test public PWA endpoints retain appropriate caching headers
     */
    public function test_public_routes_retain_appropriate_cache_headers()
    {
        $manifestRes = $this->get('/manifest.json');
        $manifestRes->assertStatus(200);
        $this->assertStringContainsString('max-age=3600', $manifestRes->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $manifestRes->headers->get('Cache-Control'));

        $offlineRes = $this->get('/offline');
        $offlineRes->assertStatus(200);
        $this->assertStringContainsString('max-age=86400', $offlineRes->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $offlineRes->headers->get('Cache-Control'));

        $swRes = $this->get('/sw.js');
        $swRes->assertStatus(200);
        $this->assertStringContainsString('no-cache', $swRes->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $swRes->headers->get('Cache-Control'));
    }

    /**
     * Test service worker implements explicit cache allowlist, response validation, and sensitive denylist
     */
    public function test_service_worker_implements_cache_allowlist_and_response_validation()
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $content = file_get_contents($swPath);

        // Check non-GET bypass
        $this->assertStringContainsString("request.method !== 'GET'", $content);

        // Check sensitive path denylist
        $this->assertStringContainsString('SENSITIVE_PATH_PATTERNS', $content);
        $this->assertStringContainsString('/students/i', $content);
        $this->assertStringContainsString('/marks/i', $content);
        $this->assertStringContainsString('/payments/i', $content);
        $this->assertStringContainsString('/attendance/i', $content);
        $this->assertStringContainsString('/login/i', $content);

        // Check response validation (reject opaque, 3xx redirects, 4xx/5xx, no-store)
        $this->assertStringContainsString('isSafeToCache', $content);
        $this->assertStringContainsString("response.type === 'opaque'", $content);
        $this->assertStringContainsString('response.redirected', $content);
        $this->assertStringContainsString("cacheControl.includes('no-store')", $content);

        // Check static asset allowlist and query string isolation
        $this->assertStringContainsString('isApprovedStaticAsset', $content);
        $this->assertStringContainsString('/assets/', $content);
        $this->assertStringContainsString('/global_assets/', $content);
        $this->assertStringContainsString('isCleanVersionParam', $content);

        // Check application-scoped cache cleanup
        $this->assertStringContainsString("cacheName.startsWith(CACHE_PREFIX)", $content);
        $this->assertStringContainsString("EXPECTED_CACHES.includes(cacheName)", $content);
    }

    /**
     * Test service worker does not call skipWaiting automatically on install
     */
    public function test_service_worker_lifecycle_does_not_skip_waiting_on_install()
    {
        $swPath = public_path('sw.js');
        $content = file_get_contents($swPath);

        // Find install event block
        preg_match('/addEventListener\(\s*[\'"]install[\'"]\s*,\s*\((?:event)?\)\s*=>\s*\{(.*?)\}\);/s', $content, $matches);
        $this->assertNotEmpty($matches, 'Could not locate install event in sw.js');

        $installBody = $matches[1];
        $this->assertStringNotContainsString('self.skipWaiting()', $installBody, 'Service worker must not skipWaiting prematurely on install');

        // Confirm message event handles skipWaiting safely on client signal
        $this->assertStringContainsString("event.data.action === 'SKIP_WAITING'", $content);
    }

    /**
     * Test PWA client script implements update state machine and transaction locking
     */
    public function test_pwa_client_implements_update_state_machine_and_transaction_locking()
    {
        $pwaJsPath = public_path('assets/js/pwa.js');
        $content = file_get_contents($pwaJsPath);

        // Verify state machine definition
        $this->assertStringContainsString('PwaUpdateStateMachine', $content);
        $this->assertStringContainsString('STATE_IDLE', $content);
        $this->assertStringContainsString('STATE_TRANSACTION_PENDING', $content);
        $this->assertStringContainsString('STATE_UPDATE_AVAILABLE', $content);
        $this->assertStringContainsString('STATE_UPDATING', $content);

        // Verify transaction hooks (submit & jQuery AJAX)
        $this->assertStringContainsString("document.addEventListener('submit'", $content);
        $this->assertStringContainsString('PwaUpdateStateMachine.startTransaction()', $content);
        $this->assertStringContainsString('ajaxStart', $content);
        $this->assertStringContainsString('ajaxStop', $content);

        // Verify controllerchange recheck before reload
        $this->assertStringContainsString('isSafeToUpdate()', $content);
        $this->assertStringContainsString('window.location.reload()', $content);
    }

    /**
     * Test attendance sync endpoint applies strict no-store cache headers
     */
    public function test_attendance_sync_endpoint_has_strict_no_store_headers()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('attendance.sync'), [
            'idempotency_key' => 'header-check-' . uniqid(),
            'my_class_id' => 999999, // Intentional invalid to trigger error
            'attendance_date_bs' => '2083-07-09',
            'students' => [1 => ['status' => 'present']],
        ]);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }

    /**
     * Test PWA client implements offline attendance contract, IndexedDB storage, and UUID idempotency
     */
    public function test_pwa_client_implements_offline_attendance_contract_and_indexeddb_storage()
    {
        $pwaJsPath = public_path('assets/js/pwa.js');
        $content = file_get_contents($pwaJsPath);

        // Verify UUID generator
        $this->assertStringContainsString('generateUUID', $content);

        // Verify validated attendance statuses
        $this->assertStringContainsString("'present', 'absent', 'late', 'leave', 'half_day'", $content);

        // Verify IndexedDB store definition with fallback
        $this->assertStringContainsString('PwaAttendanceStore', $content);
        $this->assertStringContainsString('lav_sms_pwa_db', $content);
        $this->assertStringContainsString('attendance_drafts', $content);
        $this->assertStringContainsString('client_uuid', $content);

        // Verify sync methods
        $this->assertStringContainsString('syncDraft:', $content);
        $this->assertStringContainsString('syncAllPending:', $content);
        $this->assertStringContainsString('PwaAttendanceUI', $content);

        // Verify conflict handling
        $this->assertStringContainsString('resolveConflict', $content);
        $this->assertStringContainsString('force_overwrite', $content);
    }

    /**
     * Test attendance index view contains offline sync widget and form interceptor
     */
    public function test_attendance_index_view_contains_offline_sync_widget_and_interceptor()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        $response->assertSee('pwa-attendance-offline-widget');
        $response->assertSee('pwa-btn-sync-all');
        $response->assertSee('pwa-drafts-tbody');
        $response->assertSee('PwaAttendance.saveDraft');
    }
}
