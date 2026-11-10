<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class DisasterRecoveryDrillTest extends TestCase
{
    /**
     * Complete end-to-end disaster recovery drill:
     * Synthetic DB -> Snapshot backup -> Checksum verification -> Restoration into target DB -> Exact reconciliation.
     */
    public function test_complete_disaster_recovery_backup_and_restore_drill()
    {
        $srcConnection = config('database.default');
        $config = config("database.connections.{$srcConnection}");
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $sourceDb = $config['database'] ?? 'sms_laravel';

        $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 1. Gather Source Metrics
        $tablesStmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?");
        $tablesStmt->execute([$sourceDb]);
        $sourceTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($sourceTables, "Source database must have tables for DR drill");

        $sourceCounts = [];
        $keyTables = ['users', 'student_records', 'payments', 'payment_records', 'marks', 'attendances', 'my_classes', 'subjects'];
        foreach ($keyTables as $tbl) {
            if (in_array($tbl, $sourceTables)) {
                $count = $pdo->query("SELECT COUNT(*) FROM `{$sourceDb}`.`{$tbl}`")->fetchColumn();
                $sourceCounts[$tbl] = (int)$count;
            }
        }

        // Financial totals in source
        $sourceFinancials = [
            'total_payments' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `{$sourceDb}`.`payments`")->fetchColumn(),
            'total_paid' => (float)$pdo->query("SELECT COALESCE(SUM(amt_paid), 0) FROM `{$sourceDb}`.`payment_records`")->fetchColumn(),
            'total_balance' => (float)$pdo->query("SELECT COALESCE(SUM(balance), 0) FROM `{$sourceDb}`.`payment_records`")->fetchColumn(),
        ];

        // 2. Perform Backup
        $startTime = microtime(true);
        $this->artisan('db:backup', [
            '--database' => $sourceDb,
            '--compress' => true,
        ])->assertExitCode(0);
        $backupDuration = round(microtime(true) - $startTime, 3);

        $backupDir = storage_path('app/backups');
        $files = File::glob($backupDir . "/backup_{$sourceDb}_*.sql.gz");
        $this->assertNotEmpty($files, "Backup file was not created");
        $backupFile = end($files);
        $metaFile = $backupFile . '.meta.json';
        $this->assertFileExists($metaFile, "Metadata file must exist");

        // 3. Verify Checksum
        $meta = json_decode(File::get($metaFile), true);
        $computedHash = hash_file('sha256', $backupFile);
        $this->assertSame($meta['sha256'], $computedHash, "SHA-256 checksum must match metadata");

        // 4. Restore into Target Drill Database
        $targetDrillDb = 'sms_laravel_dr_drill';
        $restoreStartTime = microtime(true);
        $this->artisan('db:restore', [
            'file' => $backupFile,
            '--target-db' => $targetDrillDb,
            '--force' => true,
            '--verify-checksum' => true,
        ])->assertExitCode(0);
        $restoreDuration = round(microtime(true) - $restoreStartTime, 3);

        // 5. Compare Tables and Counts
        $drillTablesStmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?");
        $drillTablesStmt->execute([$targetDrillDb]);
        $drillTables = $drillTablesStmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertCount(count($sourceTables), $drillTables, "Table counts must match exactly");

        foreach ($sourceCounts as $tbl => $srcCount) {
            $drillCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$targetDrillDb}`.`{$tbl}`")->fetchColumn();
            $this->assertSame($srcCount, $drillCount, "Record count for [{$tbl}] must match exactly");
        }

        // 6. Compare Financial Totals
        $drillFinancials = [
            'total_payments' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `{$targetDrillDb}`.`payments`")->fetchColumn(),
            'total_paid' => (float)$pdo->query("SELECT COALESCE(SUM(amt_paid), 0) FROM `{$targetDrillDb}`.`payment_records`")->fetchColumn(),
            'total_balance' => (float)$pdo->query("SELECT COALESCE(SUM(balance), 0) FROM `{$targetDrillDb}`.`payment_records`")->fetchColumn(),
        ];

        $this->assertEqualsWithDelta($sourceFinancials['total_payments'], $drillFinancials['total_payments'], 0.01, "Payments total must reconcile");
        $this->assertEqualsWithDelta($sourceFinancials['total_paid'], $drillFinancials['total_paid'], 0.01, "Amount paid total must reconcile");
        $this->assertEqualsWithDelta($sourceFinancials['total_balance'], $drillFinancials['total_balance'], 0.01, "Balance total must reconcile");

        // 7. Cleanup Drill Database and Test Artifacts
        $pdo->exec("DROP DATABASE IF EXISTS `{$targetDrillDb}`");
        File::delete([$backupFile, $metaFile]);

        $this->assertTrue(true, "DR Drill completed successfully (Backup: {$backupDuration}s, Restore: {$restoreDuration}s)");
    }
}
