<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for the compensating controls documented against the
 * Laravel 8 TokenGuard advisory (CVE-2026-114509) and the signed-URL advisory.
 *
 * If the application ever starts issuing API tokens, exposing signed URLs, or
 * changes the default guard, this test fails and forces a re-review of the
 * documented risk acceptance.
 */
class ApiGuardSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_users_table_does_not_expose_token_authentication_columns(): void
    {
        $this->assertFalse(
            Schema::hasColumn('users', 'api_token'),
            'The users table must not expose api_token while the unpatched TokenGuard advisory is accepted.'
        );
    }

    public function test_api_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_default_guard_remains_session_based(): void
    {
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame('session', config('auth.guards.web.driver'));
    }

    public function test_application_does_not_expose_signed_url_routes(): void
    {
        $sources = [];
        $directories = ['app', 'routes', 'config'];

        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($directory), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $sources[] = (string) file_get_contents($file->getPathname());
                }
            }
        }

        $source = implode("\n", $sources);

        $this->assertStringNotContainsString('temporarySignedRoute(', $source);
        $this->assertStringNotContainsString('signedRoute(', $source);
        $this->assertStringNotContainsString('hasValidSignature(', $source);
    }
}
