<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Perform an end-to-end system health check for load balancers and deployment verification.
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $httpCode = 200;
        $checks = [];

        // 1. Database Connectivity
        try {
            DB::connection()->getPdo();
            $dbCheck = DB::select('SELECT 1 AS alive');
            if (!empty($dbCheck) && $dbCheck[0]->alive == 1) {
                $checks['database'] = 'ok';
            } else {
                $checks['database'] = 'unexpected_response';
                $status = 'unhealthy';
                $httpCode = 503;
            }
        } catch (Throwable $e) {
            $checks['database'] = 'failed';
            $status = 'unhealthy';
            $httpCode = 503;
        }

        // 2. Cache Availability
        try {
            $cacheKey = 'health_ping_' . microtime(true);
            Cache::put($cacheKey, 'ok', 10);
            $cacheValue = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cacheValue === 'ok') {
                $checks['cache'] = 'ok';
            } else {
                $checks['cache'] = 'read_write_mismatch';
                $status = 'unhealthy';
                $httpCode = 503;
            }
        } catch (Throwable $e) {
            $checks['cache'] = 'failed';
            $status = 'unhealthy';
            $httpCode = 503;
        }

        // 3. Storage Permissions
        $storagePaths = [
            'framework' => storage_path('framework'),
            'logs' => storage_path('logs'),
        ];
        $storageOk = true;
        foreach ($storagePaths as $name => $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $storageOk = false;
                break;
            }
        }
        $checks['storage'] = $storageOk ? 'ok' : 'unwritable';
        if (!$storageOk) {
            $status = 'unhealthy';
            $httpCode = 503;
        }

        // 4. Asset Manifest Presence
        $manifestPath = public_path('mix-manifest.json');
        $checks['assets'] = file_exists($manifestPath) ? 'ok' : 'missing_manifest';
        if (!file_exists($manifestPath)) {
            // In testing or minimal environment, manifest might be in build dir, but for prod it must be in public
            if (app()->environment('production')) {
                $status = 'unhealthy';
                $httpCode = 503;
            }
        }

        return response()->json([
            'status' => $status,
            'environment' => app()->environment(),
            'app' => config('app.name'),
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $httpCode)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}

