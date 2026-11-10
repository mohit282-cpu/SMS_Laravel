<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class DisasterRecoveryDrillTest extends TestCase
{
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = config('database.default');
        $config = config("database.connections.{$conn}");
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = (int) ($config['port'] ?? 3306);
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';

        $this->pdo = new PDO("mysql:host={$this->host};port={$this->port}", $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * Complete end-to-end disaster recovery drill using isolated synthetic source and destination databases:
     * Synthetic DB -> Semicolons & Multiline SQL data -> Snapshot backup -> Checksum verification -> Restoration -> Full reconciliation.
     */
    public function test_complete_disaster_recovery_backup_and_restore_drill()
    {
        $sourceDb = 'sms_laravel_synthetic_src';
        $destDb = 'sms_laravel_synthetic_dest';
        $backupDir = storage_path('app/backups');
        $createdFiles = [];

        try {
            // 1. Create fresh synthetic source database
            $this->pdo->exec("DROP DATABASE IF EXISTS `{$sourceDb}`");
            $this->pdo->exec("DROP DATABASE IF EXISTS `{$destDb}`");
            $this->pdo->exec("CREATE DATABASE `{$sourceDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 2. Provision synthetic schema
            $this->pdo->exec("USE `{$sourceDb}`");
            $this->pdo->exec("
                CREATE TABLE `users` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `name` varchar(191) NOT NULL,
                    `email` varchar(191) NOT NULL,
                    `user_type` varchar(50) NOT NULL,
                    `notes` text DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE `my_classes` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE `student_records` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `user_id` bigint(20) unsigned NOT NULL,
                    `my_class_id` bigint(20) unsigned NOT NULL,
                    `adm_no` varchar(50) NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE `payments` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `title` varchar(191) NOT NULL,
                    `amount` decimal(10,2) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE `payment_records` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `payment_id` bigint(20) unsigned NOT NULL,
                    `student_id` bigint(20) unsigned NOT NULL,
                    `amt_paid` decimal(10,2) NOT NULL,
                    `balance` decimal(10,2) NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `fk_pr_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE `marks` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `student_id` bigint(20) unsigned NOT NULL,
                    `score` decimal(5,2) NOT NULL,
                    `remarks` text DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Seed synthetic data with tricky semicolon & quote edge cases
            $userStmt = $this->pdo->prepare("INSERT INTO `users` (`name`, `email`, `user_type`, `notes`) VALUES (?, ?, ?, ?)");
            $userStmt->execute(['Admin User', 'admin@sms.local', 'super_admin', 'Initial setup; system admin account; notes with ; semicolons!']);
            $userStmt->execute(['Aarav Sharma', 'aarav@sms.local', 'student', "Parent remarks: \"Fee deferred; first installment due next week;\"; Unicode: काठमाडौँ नेपाल"]);
            $userStmt->execute(['Sunita Thapa', 'sunita@sms.local', 'teacher', "Specialization: Science & Math;\nLine 2 with ; semicolon;\nLine 3."]);

            $classStmt = $this->pdo->prepare("INSERT INTO `my_classes` (`name`) VALUES (?)");
            $classStmt->execute(['Class 10 - Sagarmatha']);

            $srStmt = $this->pdo->prepare("INSERT INTO `student_records` (`user_id`, `my_class_id`, `adm_no`) VALUES (?, ?, ?)");
            $srStmt->execute([2, 1, 'SMS-2081-001']);

            $payStmt = $this->pdo->prepare("INSERT INTO `payments` (`title`, `amount`) VALUES (?, ?)");
            $payStmt->execute(['First Term Tuition Fee', 24500.50]);
            $payStmt->execute(['Lab & Computer Fee', 6200.75]);

            $prStmt = $this->pdo->prepare("INSERT INTO `payment_records` (`payment_id`, `student_id`, `amt_paid`, `balance`) VALUES (?, ?, ?, ?)");
            $prStmt->execute([1, 1, 15000.00, 9500.50]);
            $prStmt->execute([2, 1, 6200.75, 0.00]);

            $markStmt = $this->pdo->prepare("INSERT INTO `marks` (`student_id`, `score`, `remarks`) VALUES (?, ?, ?)");
            $markStmt->execute([1, 94.50, 'Distinction grade; exceptional in algebra & geometry;']);

            // Record baseline financial metrics from source
            $srcTotalPayments = (float) $this->pdo->query("SELECT SUM(amount) FROM `{$sourceDb}`.`payments`")->fetchColumn();
            $srcTotalPaid = (float) $this->pdo->query("SELECT SUM(amt_paid) FROM `{$sourceDb}`.`payment_records`")->fetchColumn();
            $srcTotalBalance = (float) $this->pdo->query("SELECT SUM(balance) FROM `{$sourceDb}`.`payment_records`")->fetchColumn();

            $this->assertEquals(30701.25, $srcTotalPayments);
            $this->assertEquals(21200.75, $srcTotalPaid);
            $this->assertEquals(9500.50, $srcTotalBalance);

            // 4. Execute backup command on synthetic source
            $backupStartTime = microtime(true);
            $this->artisan('db:backup', [
                '--database' => $sourceDb,
                '--compress' => true,
            ])->assertExitCode(0);
            $backupDuration = microtime(true) - $backupStartTime;

            // Locate created backup archive
            $matches = File::glob($backupDir . "/backup_{$sourceDb}_*.sql.gz");
            $this->assertNotEmpty($matches, "Backup archive was not created in {$backupDir}");
            $backupFile = end($matches);
            $metaFile = $backupFile . '.meta.json';
            $createdFiles[] = $backupFile;
            $createdFiles[] = $metaFile;

            $this->assertFileExists($backupFile);
            $this->assertFileExists($metaFile);

            // 5. Verify cryptographic integrity
            $metaData = json_decode(File::get($metaFile), true);
            $computedSha = hash_file('sha256', $backupFile);
            $this->assertSame($metaData['sha256'], $computedSha, "SHA-256 hash in metadata must match physical file hash");
            $this->assertEquals(6, $metaData['tables']);
            $this->assertEquals(10, $metaData['total_rows']);

            // 6. Execute restore command into synthetic destination
            $restoreStartTime = microtime(true);
            $this->artisan('db:restore', [
                'file' => $backupFile,
                '--target-db' => $destDb,
                '--force' => true,
                '--verify-checksum' => true,
            ])->assertExitCode(0);
            $restoreDuration = microtime(true) - $restoreStartTime;

            // 7. Full data reconciliation between source and destination
            $destTablesStmt = $this->pdo->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '{$destDb}'");
            $destTables = $destTablesStmt->fetchAll(PDO::FETCH_COLUMN);
            $this->assertCount(6, $destTables);

            // Row counts
            $this->assertEquals(3, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`users`")->fetchColumn());
            $this->assertEquals(1, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`my_classes`")->fetchColumn());
            $this->assertEquals(1, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`student_records`")->fetchColumn());
            $this->assertEquals(2, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`payments`")->fetchColumn());
            $this->assertEquals(2, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`payment_records`")->fetchColumn());
            $this->assertEquals(1, (int) $this->pdo->query("SELECT COUNT(*) FROM `{$destDb}`.`marks`")->fetchColumn());

            // Check content with semicolons & unicode survived intact
            $user2 = $this->pdo->query("SELECT notes FROM `{$destDb}`.`users` WHERE id = 2")->fetch(PDO::FETCH_ASSOC);
            $this->assertStringContainsString('काठमाडौँ नेपाल', $user2['notes']);
            $this->assertStringContainsString('Fee deferred; first installment due next week;', $user2['notes']);

            // Reconcile financial figures
            $destTotalPayments = (float) $this->pdo->query("SELECT SUM(amount) FROM `{$destDb}`.`payments`")->fetchColumn();
            $destTotalPaid = (float) $this->pdo->query("SELECT SUM(amt_paid) FROM `{$destDb}`.`payment_records`")->fetchColumn();
            $destTotalBalance = (float) $this->pdo->query("SELECT SUM(balance) FROM `{$destDb}`.`payment_records`")->fetchColumn();

            $this->assertEqualsWithDelta($srcTotalPayments, $destTotalPayments, 0.001);
            $this->assertEqualsWithDelta($srcTotalPaid, $destTotalPaid, 0.001);
            $this->assertEqualsWithDelta($srcTotalBalance, $destTotalBalance, 0.001);

            $this->assertLessThan(15.0, $backupDuration, "Backup completed within performance envelope");
            $this->assertLessThan(15.0, $restoreDuration, "Restore completed within performance envelope");
        } finally {
            // Teardown synthetic databases and test artifacts
            $this->pdo->exec("DROP DATABASE IF EXISTS `{$sourceDb}`");
            $this->pdo->exec("DROP DATABASE IF EXISTS `{$destDb}`");
            foreach ($createdFiles as $f) {
                if (File::exists($f)) {
                    File::delete($f);
                }
            }
        }
    }

    /**
     * Negative test: Checksum mismatch rejects restore with exit code 1.
     */
    public function test_restore_rejects_corrupted_archive_with_checksum_mismatch()
    {
        $dummyFile = storage_path('app/backups/corrupted_test_backup.sql');
        $dummyMeta = $dummyFile . '.meta.json';

        try {
            File::ensureDirectoryExists(dirname($dummyFile));
            File::put($dummyFile, "CREATE TABLE dummy (id INT);\n");
            File::put($dummyMeta, json_encode([
                'sha256' => 'deadbeefcafebabe000000000000000000000000000000000000000000000000',
            ]));

            $this->artisan('db:restore', [
                'file' => $dummyFile,
                '--target-db' => 'sms_dummy_db',
                '--force' => true,
                '--verify-checksum' => true,
            ])->assertExitCode(1);
        } finally {
            File::delete([$dummyFile, $dummyMeta]);
        }
    }

    /**
     * Negative test: Disallowed characters in database name are rejected.
     */
    public function test_restore_rejects_invalid_target_database_name()
    {
        $dummyFile = storage_path('app/backups/valid_name_test.sql');
        try {
            File::ensureDirectoryExists(dirname($dummyFile));
            File::put($dummyFile, "SELECT 1;\n");

            $this->artisan('db:restore', [
                'file' => $dummyFile,
                '--target-db' => 'invalid;database--name',
                '--force' => true,
            ])->assertExitCode(1);
        } finally {
            File::delete($dummyFile);
        }
    }

    /**
     * Negative test: Target configured production database without --confirm-production is blocked.
     */
    public function test_restore_blocks_production_database_without_confirmation()
    {
        $prodDb = config('database.connections.mysql.database');
        $dummyFile = storage_path('app/backups/prod_test.sql');

        try {
            File::ensureDirectoryExists(dirname($dummyFile));
            File::put($dummyFile, "SELECT 1;\n");

            $this->artisan('db:restore', [
                'file' => $dummyFile,
                '--target-db' => $prodDb,
                '--force' => true,
            ])->assertExitCode(1);
        } finally {
            File::delete($dummyFile);
        }
    }
}
