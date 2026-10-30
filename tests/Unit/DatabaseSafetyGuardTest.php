<?php

namespace Tests\Unit;

use App\Support\Testing\DatabaseSafetyGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseSafetyGuardTest extends TestCase
{
    /**
     * @dataProvider protectedEnvironments
     */
    public function test_it_rejects_protected_environments_and_databases(
        string $environment,
        string $connection,
        string $database
    ): void {
        $this->expectException(RuntimeException::class);

        DatabaseSafetyGuard::assertSafe($environment, $connection, $database);
    }

    public function protectedEnvironments(): array
    {
        return [
            'production environment' => ['production', 'mysql_test', 'sms_laravel_test'],
            'development database' => ['testing', 'mysql', 'sms_laravel'],
            'production database name' => ['testing', 'mysql_test', 'production'],
            'empty database name' => ['testing', 'mysql_test', ''],
            'unmarked database name' => ['testing', 'mysql', 'school_data'],
            'sqlite file outside test namespace' => ['testing', 'sqlite', 'database/database.sqlite'],
        ];
    }

    /**
     * @dataProvider disposableDatabases
     */
    public function test_it_accepts_disposable_test_databases(
        string $environment,
        string $connection,
        string $database
    ): void {
        DatabaseSafetyGuard::assertSafe($environment, $connection, $database);

        $this->assertTrue(true);
    }

    public function disposableDatabases(): array
    {
        return [
            'dedicated mysql test connection' => ['testing', 'mysql_test', 'sms_laravel_test'],
            'mysql database with test marker' => ['testing', 'mysql', 'ci_sms_test'],
            'in-memory sqlite' => ['testing', 'sqlite', ':memory:'],
            'sqlite test file' => ['testing', 'sqlite', 'database/testing.sqlite'],
        ];
    }
}
