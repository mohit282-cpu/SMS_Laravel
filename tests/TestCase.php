<?php

namespace Tests;

use App\Support\Testing\DatabaseSafetyGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        DatabaseSafetyGuard::assertSafe((string) app()->environment(), $connection, $database);
    }
}
