<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\PushSubscription;
use App\Services\PwaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PwaController extends Controller
{
    protected $pwaService;

    public function __construct(PwaService $pwaService)
    {
        $this->pwaService = $pwaService;
    }

    /**
     * Dynamic Web App Manifest
     */
    public function manifest(): JsonResponse
    {
        $manifest = $this->pwaService->getManifest();

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Branded Offline Fallback View
     */
    public function offline()
    {
        return response()
            ->view('pages.pwa.offline', [
                'systemName' => Qs::getSystemName() ?: config('app.name', 'School Management System Academy'),
            ])
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Serve Service Worker with proper headers
     */
    public function serviceWorker(): Response
    {
        $swPath = public_path('sw.js');
        if (!file_exists($swPath)) {
            abort(404, 'Service worker script not found');
        }

        $content = file_get_contents($swPath);

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Save web push subscription
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string|url|max:1000',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'content_encoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ]);

        $endpoint = $request->input('endpoint');
        $host = parse_url($endpoint, PHP_URL_HOST);
        $isLocalhost = in_array(strtolower($host ?: ''), ['localhost', '127.0.0.1']);

        if (!str_starts_with(strtolower($endpoint), 'https://') && !$isLocalhost) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscription endpoint must use secure HTTPS protocol',
            ], 422);
        }

        $sub = PushSubscription::storeSubscription(Auth::id(), $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Push subscription registered successfully',
            'id' => $sub->id,
        ]);
    }

    /**
     * Unsubscribe from web push
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string|url|max:1000',
        ]);

        $hash = hash('sha256', $request->endpoint);
        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint_hash', $hash)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Push subscription removed successfully',
        ]);
    }

    /**
     * Retrieve VAPID Public Key for client subscription handshake
     */
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('services.pwa.vapid_public_key');

        if (empty($publicKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'VAPID public key is not configured on the server.',
                'vapid_public_key' => null,
                'is_configured' => false,
            ], 503)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        return response()->json([
            'status' => 'success',
            'vapid_public_key' => $publicKey,
            'is_configured' => true,
        ])
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Send a safe test notification to the authenticated user.
     * Hardened against arbitrary recipient/payload injection and verifies subscriptions and VAPID.
     */
    public function sendTestPush(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // 1. Ensure user has registered subscriptions
        $hasSubscriptions = PushSubscription::where('user_id', $userId)->exists();
        if (!$hasSubscriptions) {
            return response()->json([
                'status' => 'no_subscriptions',
                'message' => 'No registered devices found for your account. Please enable notifications on this device first.',
            ], 422);
        }

        // 2. Ensure VAPID keys are configured
        $vapidPublic = config('services.pwa.vapid_public_key');
        $vapidPrivate = config('services.pwa.vapid_private_key');
        if (empty($vapidPublic) || empty($vapidPrivate)) {
            return response()->json([
                'status' => 'vapid_keys_missing',
                'message' => 'Server VAPID keys are not configured. Notifications cannot be delivered.',
            ], 422);
        }

        // 3. Send test notification strictly to authenticated user with immutable parameters
        $result = $this->pwaService->sendNotification(
            $userId,
            'Test Notification',
            'Web push notifications are configured and functioning correctly.',
            route('dashboard')
        );

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => 'Test notification accepted by push service for delivery.',
                'result' => $result,
            ], 200);
        }

        if ($result['status'] === 'partial') {
            return response()->json([
                'status' => 'partial',
                'message' => 'Test notification delivered to some registered devices, but others failed or expired.',
                'result' => $result,
            ], 200);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $result['message'] ?? 'Failed to deliver test notification to registered devices.',
            'result' => $result,
        ], 502);
    }
}
