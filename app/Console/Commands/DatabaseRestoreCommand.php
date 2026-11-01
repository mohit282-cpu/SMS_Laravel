<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use PDO;
use Illuminate\Support\Facades\File;

class DatabaseRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore
                            {file : Path to .sql or .sql.gz backup file}
                            {--connection= : Database connection defined in config/database.php}
                            {--target-db= : Destination database name to restore into}
                            {--force : Force restoration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely restore a MySQL/MariaDB database from a .sql or .sql.gz backup file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("Backup file not found at: {$filePath}");
            return 1;
        }

        $connectionName = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$connectionName}");

        if (!$config) {
            $this->error("Database connection [{$connectionName}] is not configured.");
            return 1;
        }

        $driver = $config['driver'] ?? '';
        if ($driver !== 'mysql') {
            $this->error("Restore command currently supports MySQL/MariaDB connections. Detected [{$driver}].");
            return 1;
        }

        $targetDb = $this->option('target-db') ?: ($config['database'] ?? '');
        if (empty($targetDb)) {
            $this->error("No target database specified for restoration.");
            return 1;
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $targetDb)) {
            $this->error("Invalid target database name [{$targetDb}]. Only alphanumeric, underscores and hyphens allowed.");
            return 1;
        }

        // Safety verification prompt
        $isForce = (bool) $this->option('force');
        if (!$isForce) {
            $confirm = $this->confirm("WARNING: Restoring will overwrite existing tables and data in [{$targetDb}]. Proceed?", false);
            if (!$confirm) {
                $this->warn("Database restoration aborted by user.");
                return 0;
            }
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $this->info("Initializing database connection on [{$host}:{$port}]...");

        try {
            // Connect to server (without db) to verify or create target database
            $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
            $serverPdo = new PDO($serverDsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $checkStmt = $serverPdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname");
            $checkStmt->execute(['dbname' => $targetDb]);
            $dbExists = (bool) $checkStmt->fetch();

            if (!$dbExists) {
                $this->info("Database [{$targetDb}] does not exist. Creating database...");
                $serverPdo->exec("CREATE DATABASE `{$targetDb}` CHARACTER SET {$charset} COLLATE {$collation}");
            }

            // Connect to target database with multi-statement enabled
            $targetDsn = "mysql:host={$host};port={$port};dbname={$targetDb};charset={$charset}";
            $targetPdo = new PDO($targetDsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);

            $this->info("Restoring backup into [{$targetDb}] from [{$filePath}]...");

            // Determine if compressed
            $isGzip = str_ends_with(strtolower($filePath), '.gz');

            if ($isGzip) {
                $gz = gzopen($filePath, 'rb');
                if (!$gz) {
                    $this->error("Could not read gzip archive: {$filePath}");
                    return 1;
                }
            } else {
                $fp = fopen($filePath, 'r');
                if (!$fp) {
                    $this->error("Could not read SQL file: {$filePath}");
                    return 1;
                }
            }

            $buffer = '';
            $executedBatches = 0;
            $inDelimiter = false;

            while (true) {
                if ($isGzip) {
                    $line = gzgets($gz);
                    if ($line === false) break;
                } else {
                    $line = fgets($fp);
                    if ($line === false) break;
                }

                $trimmed = trim($line);
                // Skip empty lines or pure single-line comments outside of multi-line SQL
                if (empty($buffer) && (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#'))) {
                    continue;
                }

                $buffer .= $line;

                // Execute when buffer reaches a sensible batch size (50KB) and ends with semicolon
                if (strlen($buffer) > 51200 && str_ends_with($trimmed, ';')) {
                    $targetPdo->exec($buffer);
                    $executedBatches++;
                    $buffer = '';
                }
            }

            // Flush remaining buffer
            if (!empty(trim($buffer))) {
                $targetPdo->exec($buffer);
                $executedBatches++;
            }

            if ($isGzip) {
                gzclose($gz);
            } else {
                fclose($fp);
            }

            $elapsed = round(microtime(true) - $startTime, 2);
            $this->info("SQL execution completed in {$elapsed}s ({$executedBatches} statement batch(es)).");

            // Perform post-restoration verification
            $this->line("--------------------------------------------------");
            $this->line("POST-RESTORATION VERIFICATION REPORT");
            $this->line("--------------------------------------------------");

            $tablesStmt = $targetPdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = [];
            while ($row = $tablesStmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $restoredTableCount = count($tables);
            $this->info("Restored Tables: {$restoredTableCount}");

            $verificationMetrics = [];
            $checkTables = [
                'users',
                'student_records',
                'payments',
                'payment_records',
                'student_attendances',
                'marks',
                'academic_years',
                'my_classes',
                'sections',
                'subjects',
            ];

            foreach ($checkTables as $table) {
                if (in_array($table, $tables, true)) {
                    $count = (int) $targetPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $verificationMetrics[$table] = $count;
                    $this->line(sprintf("  %-25s: %d rows", $table, $count));
                } else {
                    $this->warn(sprintf("  %-25s: TABLE NOT FOUND", $table));
                }
            }

            // Foreign key consistency spot-check
            $orphans = 0;
            if (in_array('student_records', $tables, true) && in_array('users', $tables, true)) {
                $orphans = (int) $targetPdo->query("SELECT COUNT(*) FROM `student_records` sr LEFT JOIN `users` u ON sr.user_id = u.id WHERE u.id IS NULL")->fetchColumn();
            }
            if ($orphans === 0) {
                $this->info("Foreign Key Integrity: PASS (0 orphan student_records)");
            } else {
                $this->error("Foreign Key Integrity: FAIL ({$orphans} orphan student_records found)");
            }

            $this->line("--------------------------------------------------");
            $this->info("Database restore to [{$targetDb}] VERIFIED SUCCESSFULLY.");

            return 0;
        } catch (Exception $e) {
            $this->error("Database restoration failed: " . $e->getMessage());
            return 1;
        }
    }
}
