<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Public Liveness Probe.
     * Minimal footprint: confirms PHP-FPM / Laravel process is responsive.
     *
     * @return JsonResponse
     */
    public function liveness(): JsonResponse
    {
        if (app()->isDownForMaintenance()) {
            return response()->json([
                'status' => 'maintenance',
                'timestamp' => now()->toIso8601String(),
            ], 503)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ], 200)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Public Readiness Probe.
     * Verifies critical subsystems required to serve student & school traffic.
     * Never exposes internal exception traces, paths, or credentials.
     *
     * @return JsonResponse
     */
    public function readiness(): JsonResponse
    {
        if (app()->isDownForMaintenance()) {
            return response()->json([
                'status' => 'not_ready',
                'reason' => 'maintenance_mode',
                'timestamp' => now()->toIso8601String(),
            ], 503)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        $allOk = true;

        // 1. Database Check (with timeout / query limit)
        if (!$this->checkDatabase()) {
            $allOk = false;
        }

        // 2. Cache Check
        if (!$this->checkCache()) {
            $allOk = false;
        }

        // 3. Storage Permissions Check
        if (!$this->checkStorage()) {
            $allOk = false;
        }

        // 4. Asset Manifest Check (strict in production)
        if (!$this->checkAssets()) {
            $allOk = false;
        }

        if (!$allOk) {
            return response()->json([
                'status' => 'not_ready',
                'timestamp' => now()->toIso8601String(),
            ], 503)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        $payload = [
            'status' => 'ready',
            'timestamp' => now()->toIso8601String(),
        ];

        $releaseFile = base_path('.release_sha');
        if (is_file($releaseFile) && is_readable($releaseFile)) {
            $releaseSha = trim((string) file_get_contents($releaseFile));
            if ($releaseSha !== '') {
                $payload['release'] = $releaseSha;
            }
        }

        return response()->json($payload, 200)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Backward-compatible alias for existing deployment and monitoring scripts.
     * Calls readiness().
     */
    public function check(): JsonResponse
    {
        return $this->readiness();
    }

    /**
     * Restricted Diagnostic Endpoint.
     * Detailed subsystem diagnostics for Super Admins or authorized DevOps agents.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function diagnostic(Request $request): JsonResponse
    {
        if (!$this->isDiagnosticAuthorized($request)) {
            return response()->json([
                'error' => 'Unauthorized diagnostic access',
            ], 403)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        $start = microtime(true);
        $checks = [];
        $isHealthy = true;

        // Detailed Database Diagnostic
        $dbStart = microtime(true);
        try {
            DB::connection()->getPdo();
            $alive = DB::select('SELECT 1 AS alive');
            $dbDuration = round((microtime(true) - $dbStart) * 1000, 2);

            if (!empty($alive) && $alive[0]->alive == 1) {
                $checks['database'] = [
                    'status' => 'ok',
                    'latency_ms' => $dbDuration,
                ];
            } else {
                $checks['database'] = ['status' => 'unexpected_response'];
                $isHealthy = false;
            }
        } catch (Throwable $e) {
            $checks['database'] = ['status' => 'failed'];
            $isHealthy = false;
        }

        // Detailed Cache Diagnostic
        $cacheStart = microtime(true);
        try {
            $key = 'diag_ping_' . microtime(true);
            Cache::put($key, '1', 5);
            $read = Cache::get($key);
            Cache::forget($key);
            $cacheDuration = round((microtime(true) - $cacheStart) * 1000, 2);

            if ($read === '1') {
                $checks['cache'] = [
                    'status' => 'ok',
                    'latency_ms' => $cacheDuration,
                ];
            } else {
                $checks['cache'] = ['status' => 'read_mismatch'];
                $isHealthy = false;
            }
        } catch (Throwable $e) {
            $checks['cache'] = ['status' => 'failed'];
            $isHealthy = false;
        }

        // Detailed Storage Diagnostic
        $paths = [
            'framework' => storage_path('framework'),
            'logs' => storage_path('logs'),
        ];
        $storageStatus = [];
        foreach ($paths as $name => $p) {
            $writable = is_dir($p) && is_writable($p);
            $storageStatus[$name] = $writable ? 'writable' : 'unwritable';
            if (!$writable) {
                $isHealthy = false;
            }
        }
        $checks['storage'] = $storageStatus;

        // Assets Diagnostic
        $manifestExists = file_exists(public_path('mix-manifest.json'));
        $checks['assets'] = [
            'manifest' => $manifestExists ? 'present' : 'missing',
        ];
        if (!$manifestExists && app()->environment('production')) {
            $isHealthy = false;
        }

        $totalDuration = round((microtime(true) - $start) * 1000, 2);

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'environment' => app()->environment(),
            'app' => config('app.name'),
            'checks' => $checks,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'execution_time_ms' => $totalDuration,
            'timestamp' => now()->toIso8601String(),
        ], $isHealthy ? 200 : 503)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Internal Database Connectivity Checker.
     */
    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            $res = DB::select('SELECT 1 AS alive');
            return !empty($res) && $res[0]->alive == 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Internal Cache Storage Checker.
     */
    protected function checkCache(): bool
    {
        try {
            $k = 'health_check_' . microtime(true);
            Cache::put($k, 'val', 5);
            $v = Cache::get($k);
            Cache::forget($k);
            return $v === 'val';
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Internal Storage Permission Checker.
     */
    protected function checkStorage(): bool
    {
        $dirs = [storage_path('framework'), storage_path('logs')];
        foreach ($dirs as $dir) {
            if (!is_dir($dir) || !is_writable($dir)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Internal Asset Manifest Checker.
     */
    protected function checkAssets(): bool
    {
        $manifest = public_path('mix-manifest.json');
        if (app()->environment('production')) {
            return file_exists($manifest);
        }
        return true;
    }

    /**
     * Assert authorization for restricted diagnostic telemetry.
     */
    protected function isDiagnosticAuthorized(Request $request): bool
    {
        // 1. Authenticated Super Admin session
        if (Auth::check() && Auth::user()->user_type === 'super_admin') {
            return true;
        }

        // 2. Secret Token in Header (X-Health-Key or Bearer token)
        $configuredKey = config('app.health_key') ?: env('HEALTH_CHECK_SECRET');
        if (!empty($configuredKey)) {
            $headerKey = $request->header('X-Health-Key');
            if ($headerKey && hash_equals($configuredKey, $headerKey)) {
                return true;
            }

            $bearer = $request->bearerToken();
            if ($bearer && hash_equals($configuredKey, $bearer)) {
                return true;
            }
        }

        return false;
    }
}
