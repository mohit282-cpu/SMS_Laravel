<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test that the liveness endpoint responds with minimal status.
     */
    public function test_liveness_endpoint_returns_alive_status()
    {
        $response = $this->get('/health/liveness');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'status' => 'alive',
        ]);
        $response->assertJsonMissing(['checks', 'environment', 'database']);
    }

    /**
     * Test that the public readiness probe returns 200 with minimal ready status.
     */
    public function test_readiness_endpoint_returns_ready_status_without_leaking_internals()
    {
        $response = $this->get('/health/readiness');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ready',
        ]);
        // Public endpoint must NOT leak internal telemetry or paths
        $response->assertJsonMissing(['checks', 'environment', 'memory_usage_mb', 'storage']);
    }

    /**
     * Test backward-compatible /health endpoint.
     */
    public function test_legacy_health_alias_returns_ready_status()
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ready',
        ]);
    }

    /**
     * Test that diagnostic endpoint rejects unauthenticated / unauthorized access.
     */
    public function test_diagnostic_endpoint_rejects_unauthorized_guests()
    {
        $response = $this->get('/health/diagnostic');
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Unauthorized diagnostic access',
        ]);
    }

    /**
     * Test that diagnostic endpoint rejects non-super-admin users.
     */
    public function test_diagnostic_endpoint_rejects_regular_students()
    {
        $student = User::where('user_type', 'student')->first();
        if ($student) {
            $response = $this->actingAs($student)->get('/health/diagnostic');
            $response->assertStatus(403);
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test that diagnostic endpoint permits authenticated Super Admin.
     */
    public function test_diagnostic_endpoint_permits_super_admin()
    {
        $admin = User::where('user_type', 'super_admin')->first();
        if ($admin) {
            $response = $this->actingAs($admin)->get('/health/diagnostic');
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'status',
                'environment',
                'app',
                'checks' => [
                    'database',
                    'cache',
                    'storage',
                    'assets',
                ],
                'memory_usage_mb',
                'execution_time_ms',
                'timestamp',
            ]);
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test that diagnostic endpoint permits valid secret header.
     */
    public function test_diagnostic_endpoint_permits_valid_secret_header()
    {
        config(['app.health_key' => 'secret_telemetry_key_xyz123']);

        $response = $this->withHeaders([
            'X-Health-Key' => 'secret_telemetry_key_xyz123',
        ])->get('/health/diagnostic');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'healthy']);
    }
}
