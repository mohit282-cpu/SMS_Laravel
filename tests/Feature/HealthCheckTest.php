<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test that the /health endpoint responds with valid JSON and expected checks.
     */
    public function test_health_endpoint_returns_successful_status_json()
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
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
            'timestamp',
        ]);
        $response->assertJson([
            'status' => 'healthy',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
                'storage' => 'ok',
            ],
        ]);
    }
}

