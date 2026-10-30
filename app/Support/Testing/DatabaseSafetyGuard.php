<?php

namespace App\Support\Testing;

use RuntimeException;

/**
 * Fails fast whenever the test suite is about to run against a database that
 * is not explicitly designated as disposable.
 *
 * The previous guard treated "running under PHPUnit" as proof that the
 * database was safe, which made the check a no-op: every test run is a unit
 * test run, so the database name was never actually validated.
 */
class DatabaseSafetyGuard
{
    /**
     * Database names that must never be used by the automated test suite.
     *
     * @var array<int, string>
     */
    public const FORBIDDEN_NAMES = [
        '',
        'sms_laravel',
        'production',
        'prod',
        'live',
        'forge',
    ];

    public static function assertSafe(string $environment, string $connection, string $database): void
    {
        if ($environment === 'production') {
            throw new RuntimeException(
                'Safety Guard: Refusing to execute the test suite in the production environment.'
            );
        }

        $database = strtolower(trim($database));
        $connection = strtolower(trim($connection));

        if (self::isDisposableSqlite($connection, $database)) {
            return;
        }

        if (in_array($database, self::FORBIDDEN_NAMES, true)) {
            throw new RuntimeException(
                "Safety Guard: Target database '{$database}' on connection '{$connection}' is protected and cannot be used for tests."
            );
        }

        // Require an explicit "test" marker in the database name. This prevents
        // tests from silently running against development or production data
        // even when a developer forgets to export DB_TEST_* variables.
        if (strpos($database, 'test') === false) {
            throw new RuntimeException(
                "Safety Guard: Target database '{$database}' on connection '{$connection}' is not a designated disposable test database."
            );
        }
    }

    private static function isDisposableSqlite(string $connection, string $database): bool
    {
        if ($connection !== 'sqlite') {
            return false;
        }

        return $database === ':memory:'
            || $database === ''
            || strpos($database, 'test') !== false;
    }
}
