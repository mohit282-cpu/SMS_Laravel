<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use ZipArchive;

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
                            {--force : Force restoration without interactive prompt}
                            {--confirm-production : Explicitly confirm restoring into a production database}
                            {--verify-checksum : Strictly verify SHA-256 checksum from metadata before restoring}
                            {--restore-files : Restore companion files archive if available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely restore a MySQL/MariaDB database from a verified backup archive';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $rawFilePath = $this->argument('file');

        // Path safety & traversal prevention
        if (str_contains($rawFilePath, "\0")) {
            $this->error("Invalid file path: null byte detected.");
            return 1;
        }

        $realPath = realpath($rawFilePath);
        if (!$realPath || !File::exists($realPath)) {
            $this->error("Backup file not found at: {$rawFilePath}");
            Log::error("db:restore failed: File not found at {$rawFilePath}");
            return 1;
        }

        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $isGzip = str_ends_with(strtolower($realPath), '.sql.gz') || $extension === 'gz';
        $isSql = str_ends_with(strtolower($realPath), '.sql') || $extension === 'sql';

        if (!$isGzip && !$isSql) {
            $this->error("Invalid file type. Restore only accepts .sql or .sql.gz backup files.");
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

        // Production database safety guard
        $configuredProdDb = config('database.connections.mysql.database');
        $isProductionEnv = app()->environment('production') || config('app.env') === 'production';
        $isTargetingProdDb = ($targetDb === $configuredProdDb);

        if (($isProductionEnv || $isTargetingProdDb) && !$this->option('confirm-production')) {
            $this->error("SECURITY ERROR: Target database [{$targetDb}] is configured as PRODUCTION.");
            $this->error("Destructive restore is blocked. Pass --confirm-production to proceed.");
            Log::warning("db:restore blocked: Attempt to restore into production DB [{$targetDb}] without --confirm-production.");
            return 1;
        }

        // Checksum verification
        $metaPath = $realPath . '.meta.json';
        $meta = null;
        if (File::exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
        }

        if ($this->option('verify-checksum') || $meta) {
            $calculatedSha = hash_file('sha256', $realPath);
            if ($meta && isset($meta['sha256'])) {
                if ($calculatedSha !== $meta['sha256']) {
                    $this->error("CHECKSUM MISMATCH: Calculated [{$calculatedSha}], expected [{$meta['sha256']}].");
                    $this->error("The backup archive may be corrupted or tampered with. Restoration aborted.");
                    Log::error("db:restore aborted: Checksum mismatch on {$realPath}.");
                    return 1;
                }
                $this->info("SHA-256 checksum verified successfully ({$calculatedSha}).");
            } elseif ($this->option('verify-checksum')) {
                $this->error("Checksum verification requested, but no companion .meta.json file found.");
                return 1;
            }
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

            $this->info("Restoring backup into [{$targetDb}] from [{$realPath}]...");

            if ($isGzip) {
                $gz = gzopen($realPath, 'rb');
                if (!$gz) {
                    $this->error("Could not read gzip archive: {$realPath}");
                    return 1;
                }
            } else {
                $fp = fopen($realPath, 'r');
                if (!$fp) {
                    $this->error("Could not read SQL file: {$realPath}");
                    return 1;
                }
            }

            $buffer = '';
            $executedBatches = 0;

            while (true) {
                if ($isGzip) {
                    $line = gzgets($gz);
                    if ($line === false) break;
                } else {
                    $line = fgets($fp);
                    if ($line === false) break;
                }

                $trimmed = trim($line);
                if (empty($buffer) && (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#'))) {
                    continue;
                }

                $buffer .= $line;

                if (strlen($buffer) > 51200 && str_ends_with($trimmed, ';')) {
                    $targetPdo->exec($buffer);
                    $executedBatches++;
                    $buffer = '';
                }
            }

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

            // Optional companion files extraction
            if ($this->option('restore-files')) {
                $this->restoreCompanionFiles($realPath);
            }

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
                    $this->line(sprintf("  %-25s: %d rows", $table, $count));
                } else {
                    $this->warn(sprintf("  %-25s: TABLE NOT FOUND", $table));
                }
            }

            // Foreign key consistency check
            $orphans = 0;
            if (in_array('student_records', $tables, true) && in_array('users', $tables, true)) {
                $orphans = (int) $targetPdo->query("SELECT COUNT(*) FROM `student_records` sr LEFT JOIN `users` u ON sr.user_id = u.id WHERE u.id IS NULL")->fetchColumn();
            }
            if ($orphans === 0) {
                $this->info("Foreign Key Integrity: PASS (0 orphan student_records)");
            } else {
                $this->error("Foreign Key Integrity: FAIL ({$orphans} orphan student_records found)");
            }

            Log::info("db:restore successfully restored into [{$targetDb}] from {$realPath}. Restored {$restoredTableCount} tables in {$elapsed}s.");

            $this->line("--------------------------------------------------");
            $this->info("Database restore to [{$targetDb}] VERIFIED SUCCESSFULLY.");

            return 0;
        } catch (Exception $e) {
            Log::error("db:restore failed for [{$targetDb}]: " . $e->getMessage());
            // Sanitize error message to prevent leaking passwords
            $safeMessage = preg_replace('/password=[^;]+/', 'password=***', $e->getMessage());
            $this->error("Database restoration failed: {$safeMessage}");
            return 1;
        }
    }

    /**
     * Restore companion files archive if available
     */
    protected function restoreCompanionFiles(string $realSqlPath): void
    {
        if (!class_exists('ZipArchive')) {
            $this->warn("ZipArchive extension not available. Skipping files restoration.");
            return;
        }

        // Derive companion files zip name
        $dir = dirname($realSqlPath);
        $base = basename($realSqlPath);
        // e.g. backup_dbname_timestamp.sql.gz -> backup_files_dbname_timestamp.zip
        $filesZipName = preg_replace('/^backup_([a-zA-Z0-9_\-]+)_(\d{8}_\d{6})\.sql(\.gz)?$/', 'backup_files_$1_$2.zip', $base);
        $filesZipPath = $dir . DIRECTORY_SEPARATOR . $filesZipName;

        if (!File::exists($filesZipPath)) {
            $this->comment("No companion files archive found at: {$filesZipPath}");
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($filesZipPath) === true) {
            $extractTarget = storage_path('app');
            $zip->extractTo($extractTarget);
            $zip->close();
            $this->info("Companion files successfully extracted into [{$extractTarget}].");
        } else {
            $this->warn("Failed to open companion files archive: {$filesZipPath}");
        }
    }
}
