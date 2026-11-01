<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use ZipArchive;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--connection= : Database connection defined in config/database.php}
                            {--database= : Override specific database name}
                            {--destination= : Destination directory path}
                            {--compress : Compress the backup file using gzip}
                            {--include-files : Bundle uploaded student and study material files into an archive}
                            {--retention-days=30 : Number of days to retain old backups before pruning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a standalone, portable SQL backup of the MySQL/MariaDB database with optional files archive and retention pruning';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $connectionName = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$connectionName}");

        if (!$config) {
            $this->error("Database connection [{$connectionName}] is not configured.");
            Log::error("db:backup failed: Connection [{$connectionName}] not configured.");
            return 1;
        }

        $driver = $config['driver'] ?? '';
        if ($driver !== 'mysql') {
            $this->error("Backup command currently supports MySQL/MariaDB connections. Detected [{$driver}].");
            return 1;
        }

        $database = $this->option('database') ?: ($config['database'] ?? '');
        if (empty($database)) {
            $this->error("No database specified for backup.");
            return 1;
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $database)) {
            $this->error("Invalid database name [{$database}].");
            return 1;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $destinationDir = $this->option('destination') ?: storage_path('app/backups');
        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0750, true);
        }

        $timestamp = date('Ymd_His');
        $baseFilename = "backup_{$database}_{$timestamp}.sql";
        $filePath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $baseFilename;

        $compress = (bool) $this->option('compress');
        if ($compress && !function_exists('gzopen')) {
            $this->warn("ext-zlib is not available. Proceeding with uncompressed SQL backup.");
            $compress = false;
        }

        if ($compress) {
            $filePath .= '.gz';
            $fileHandle = gzopen($filePath, 'wb9');
        } else {
            $fileHandle = fopen($filePath, 'wb');
        }

        if (!$fileHandle) {
            $this->error("Failed to open backup file for writing: {$filePath}");
            Log::error("db:backup failed to open file for writing: {$filePath}");
            return 1;
        }

        $this->info("Starting backup for database [{$database}] on [{$host}:{$port}]...");

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $write = function (string $text) use ($fileHandle, $compress) {
                if ($compress) {
                    gzwrite($fileHandle, $text);
                } else {
                    fwrite($fileHandle, $text);
                }
            };

            // Write header and safety directives
            $write("-- --------------------------------------------------------\n");
            $write("-- SMS Academy MySQL Database Backup\n");
            $write("-- Database: `{$database}`\n");
            $write("-- Generated: " . date('Y-m-d H:i:s T') . "\n");
            $write("-- --------------------------------------------------------\n\n");

            $write("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
            $write("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
            $write("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
            $write("/*!40101 SET NAMES {$charset} */;\n");
            $write("/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n");
            $write("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
            $write("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n");
            $write("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n");

            // List all tables
            $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = [];
            while ($row = $tablesStmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $tableCount = count($tables);
            $totalRows = 0;
            $this->info("Found {$tableCount} base table(s). Dumping schema and data...");

            foreach ($tables as $table) {
                $write("--\n-- Table structure for table `{$table}`\n--\n\n");
                $write("DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                $createTableSql = $createRow[1] ?? '';
                $write($createTableSql . ";\n\n");

                // Dump table rows
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $rowCount = (int) $countStmt->fetchColumn();
                $totalRows += $rowCount;

                if ($rowCount > 0) {
                    $write("--\n-- Dumping data for table `{$table}` (Rows: {$rowCount})\n--\n\n");
                    $write("/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n");

                    $selectStmt = $pdo->query("SELECT * FROM `{$table}`");
                    $batch = [];
                    $batchCount = 0;

                    while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
                        $values = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $values[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $values[] = $val;
                            } else {
                                $values[] = $pdo->quote($val);
                            }
                        }
                        $batch[] = '(' . implode(', ', $values) . ')';
                        $batchCount++;

                        if ($batchCount >= 200) {
                            $write("INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n");
                            $batch = [];
                            $batchCount = 0;
                        }
                    }

                    if ($batchCount > 0) {
                        $write("INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n");
                    }

                    $write("/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n\n");
                }
            }

            // Restore SQL mode and constraints
            $write("/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n");
            $write("/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
            $write("/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n");
            $write("/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
            $write("/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
            $write("/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");
            $write("/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n");
            $write("-- Dump completed on " . date('Y-m-d H:i:s T') . "\n");

            if ($compress) {
                gzclose($fileHandle);
            } else {
                fclose($fileHandle);
            }

            $elapsed = round(microtime(true) - $startTime, 2);
            $fileSize = filesize($filePath);
            $sha256 = hash_file('sha256', $filePath);

            // Optional coordinated files archive
            $filesArchiveMeta = null;
            if ($this->option('include-files')) {
                $filesArchiveMeta = $this->createFilesArchive($destinationDir, $database, $timestamp);
            }

            // Write metadata file
            $metaPath = $filePath . '.meta.json';
            $metaData = [
                'database' => $database,
                'tables' => $tableCount,
                'total_rows' => $totalRows,
                'file_name' => basename($filePath),
                'file_size_bytes' => $fileSize,
                'sha256' => $sha256,
                'compressed' => $compress,
                'created_at' => date('c'),
                'duration_seconds' => $elapsed,
                'files_archive' => $filesArchiveMeta,
            ];

            file_put_contents($metaPath, json_encode($metaData, JSON_PRETTY_PRINT));

            // Retention pruning
            $retentionDays = (int) $this->option('retention-days');
            $prunedCount = $this->pruneOldBackups($destinationDir, $retentionDays);

            Log::info("db:backup completed for [{$database}]. Size: {$fileSize} bytes, SHA256: {$sha256}, Duration: {$elapsed}s.");

            $this->info("Backup completed successfully in {$elapsed}s.");
            $this->line("File: {$filePath}");
            $this->line("Size: " . number_format($fileSize) . " bytes");
            $this->line("SHA-256: {$sha256}");
            $this->line("Tables: {$tableCount} | Total Rows: {$totalRows}");

            if ($filesArchiveMeta) {
                $this->info("Files Archive: " . $filesArchiveMeta['file_name'] . " (" . number_format($filesArchiveMeta['file_size_bytes']) . " bytes)");
            }

            if ($prunedCount > 0) {
                $this->comment("Pruned {$prunedCount} old backup file(s) older than {$retentionDays} days.");
            }

            return 0;
        } catch (Exception $e) {
            if (is_resource($fileHandle)) {
                if ($compress) {
                    gzclose($fileHandle);
                } else {
                    fclose($fileHandle);
                }
            }
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            Log::error("db:backup error for [{$database}]: " . $e->getMessage());
            $this->error("Database backup failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Bundle user uploads and study materials into a companion zip archive
     */
    protected function createFilesArchive(string $destinationDir, string $database, string $timestamp): ?array
    {
        if (!class_exists('ZipArchive')) {
            $this->warn("ZipArchive extension not available. Skipping files bundling.");
            return null;
        }

        $filesZipName = "backup_files_{$database}_{$timestamp}.zip";
        $filesZipPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $filesZipName;

        $zip = new ZipArchive();
        if ($zip->open($filesZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->warn("Could not create files archive: {$filesZipPath}");
            return null;
        }

        $sourceDirs = [
            'uploads' => storage_path('app/uploads'),
            'public_uploads' => storage_path('app/public/uploads'),
            'study_materials' => storage_path('app/study_materials'),
        ];

        $filesAdded = 0;
        foreach ($sourceDirs as $prefix => $dir) {
            if (File::isDirectory($dir)) {
                $files = File::allFiles($dir);
                foreach ($files as $file) {
                    $relativePath = $prefix . '/' . $file->getRelativePathname();
                    $zip->addFile($file->getRealPath(), $relativePath);
                    $filesAdded++;
                }
            }
        }

        $zip->close();

        if (!File::exists($filesZipPath)) {
            return null;
        }

        return [
            'file_name' => $filesZipName,
            'file_size_bytes' => filesize($filesZipPath),
            'sha256' => hash_file('sha256', $filesZipPath),
            'files_count' => $filesAdded,
        ];
    }

    /**
     * Prune backups older than specified retention period
     */
    protected function pruneOldBackups(string $destinationDir, int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $threshold = time() - ($retentionDays * 86400);
        $files = File::files($destinationDir);
        $pruned = 0;

        foreach ($files as $file) {
            $name = $file->getFilename();
            if (str_starts_with($name, 'backup_')) {
                if ($file->getMTime() < $threshold) {
                    File::delete($file->getRealPath());
                    $pruned++;
                }
            }
        }

        return $pruned;
    }
}
