<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        // Refuse to execute tests in production environment
        if (app()->environment('production')) {
            throw new \RuntimeException("Safety Guard: Refusing to execute test suite in production environment!");
        }

        // Verify the database is explicitly designated for testing
        $isDisposableTestDb = str_contains($database, 'test')
            || str_contains($database, 'sqlite')
            || $connection === 'mysql_test'
            || app()->runningUnitTests();

        if (!$isDisposableTestDb || $database === 'sms_laravel' || $database === 'production') {
            throw new \RuntimeException("Safety Guard: Target database '{$database}' on connection '{$connection}' is not a designated disposable test database!");
        }
    }
}
