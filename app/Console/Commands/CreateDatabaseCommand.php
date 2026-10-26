<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use PDO;

class CreateDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create
                            {--connection= : The database connection defined in config/database.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely create the configured database if it does not already exist';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$connectionName}");

        if (!$config) {
            $this->error("Database connection [{$connectionName}] is not configured.");
            return 1;
        }

        $driver = $config['driver'] ?? '';
        if ($driver !== 'mysql') {
            $this->info("Database creation via db:create is only required for MySQL connections. Current driver is [{$driver}].");
            return 0;
        }

        $database = $config['database'] ?? '';
        if (empty($database)) {
            $this->error("No database name configured for connection [{$connectionName}].");
            return 1;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        // Sanitize database identifier to prevent SQL injection: alphanumeric and underscores only
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $database)) {
            $this->error("Invalid database name [{$database}]. Name may only contain alphanumeric characters, hyphens, and underscores.");
            return 1;
        }

        $this->info("Checking database [{$database}] on host [{$host}:{$port}]...");

        try {
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $statement = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname");
            $statement->execute(['dbname' => $database]);
            $exists = (bool) $statement->fetch();

            if ($exists) {
                $this->info("Database [{$database}] already exists. No action needed.");
                return 0;
            }

            $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET {$charset} COLLATE {$collation}");
            $this->info("Database [{$database}] created successfully.");
            return 0;
        } catch (Exception $e) {
            // Never expose raw credentials or internal database dumps
            $this->error("Could not create database [{$database}]. Please verify database server availability and user privileges.");
            return 1;
        }
    }
}
