<?php

/**
 * CI database safety gate.
 *
 * Runs before any destructive Artisan command so a misconfigured CI job can
 * never point `migrate:fresh` at a shared or production database. It fails
 * closed when the resolved connection is not obviously disposable.
 *
 * Usage:
 *   php .github/scripts/verify-ci-database.php --connection=mysql_test
 */

$options = getopt('', ['connection::']);
$connection = $options['connection'] ?? 'mysql_test';

function abortWith(string $message): void
{
    fwrite(STDERR, "::error::CI database safety check failed: {$message}\n");
    exit(1);
}

function isLocalHost(string $host): bool
{
    $host = strtolower(trim($host));

    return in_array($host, ['127.0.0.1', 'localhost', '::1', 'mysql'], true);
}

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';

$app = require $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$environment = (string) $app->environment();

if ($environment === 'production') {
    abortWith('APP_ENV is "production"; refusing to run destructive CI commands.');
}

if (!is_string($connection) || $connection === '') {
    abortWith('no database connection name was provided.');
}

$config = $app['config']->get("database.connections.{$connection}");

if (!is_array($config)) {
    abortWith("connection '{$connection}' is not defined in config/database.php.");
}

$driver = strtolower((string) ($config['driver'] ?? ''));
$host = (string) ($config['host'] ?? '');
$database = strtolower(trim((string) ($config['database'] ?? '')));

if ($driver !== 'mysql') {
    abortWith("connection '{$connection}' uses driver '{$driver}', expected 'mysql'.");
}

if (!isLocalHost($host)) {
    abortWith("connection '{$connection}' points at non-local host '{$host}'.");
}

if ($database === '' || strpos($database, 'test') === false) {
    abortWith("database '{$database}' is not clearly a disposable test database (name must contain 'test').");
}

if (in_array($database, ['sms_laravel', 'production', 'prod', 'live', 'forge'], true)) {
    abortWith("database '{$database}' is explicitly protected.");
}

// `getenv` is not enough: the value must survive Laravel's config cache. Reading
// the resolved connection above already proves that, but the live query proves
// the credentials actually connect to the database we validated.
try {
    $liveDatabase = $app['db']->connection($connection)->selectOne('SELECT DATABASE() AS db');
} catch (Throwable $exception) {
    abortWith("could not connect to '{$connection}': " . $exception->getMessage());
}

$liveName = strtolower(trim((string) ($liveDatabase->db ?? '')));

if ($liveName === '') {
    abortWith("the live connection to '{$connection}' did not report a database name.");
}

if ($liveName !== $database) {
    abortWith("config database '{$database}' does not match live database '{$liveName}'.");
}

fwrite(STDOUT, "CI database safety check passed: {$connection} -> {$liveName} @ {$host}\n");
