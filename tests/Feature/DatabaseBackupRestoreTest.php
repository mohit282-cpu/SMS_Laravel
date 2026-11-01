<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupRestoreTest extends TestCase
{
    protected function tearDown(): void
    {
        $backupDir = storage_path('app/backups');
        if (File::isDirectory($backupDir)) {
            $files = File::glob($backupDir . '/backup_test_*');
            foreach ($files as $file) {
                File::delete($file);
            }
        }
        parent::tearDown();
    }

    /** @test */
    public function it_can_generate_database_backup_and_metadata()
    {
        $this->artisan('db:backup', [
            '--database' => 'sms_laravel_test',
            '--compress' => true,
        ])->assertExitCode(0);

        $backupDir = storage_path('app/backups');
        $files = File::glob($backupDir . '/backup_sms_laravel_test_*.sql.gz');
        $this->assertNotEmpty($files, 'Backup file was not created');

        $latestBackup = end($files);
        $this->assertFileExists($latestBackup);
        $this->assertGreaterThan(1000, filesize($latestBackup));

        $metaFile = $latestBackup . '.meta.json';
        $this->assertFileExists($metaFile);

        $meta = json_decode(file_get_contents($metaFile), true);
        $this->assertEquals('sms_laravel_test', $meta['database']);
        $this->assertGreaterThan(0, $meta['tables']);
        $this->assertTrue($meta['compressed']);
        $this->assertNotEmpty($meta['sha256']);
    }

    /** @test */
    public function it_can_bundle_uploaded_files_when_requested()
    {
        // Create dummy study material file to bundle
        $dummyDir = storage_path('app/study_materials');
        if (!File::isDirectory($dummyDir)) {
            File::makeDirectory($dummyDir, 0755, true);
        }
        $dummyFile = $dummyDir . '/test_doc.txt';
        File::put($dummyFile, 'Test study material content');

        $this->artisan('db:backup', [
            '--database' => 'sms_laravel_test',
            '--compress' => true,
            '--include-files' => true,
        ])->assertExitCode(0);

        $backupDir = storage_path('app/backups');
        $files = File::glob($backupDir . '/backup_files_sms_laravel_test_*.zip');
        $this->assertNotEmpty($files, 'Files archive was not created');

        $zipFile = end($files);
        $this->assertFileExists($zipFile);
        $this->assertGreaterThan(0, filesize($zipFile));

        // Clean up dummy file
        File::delete($dummyFile);
    }

    /** @test */
    public function it_can_prune_old_backups_with_retention_days()
    {
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // Create an artificial old backup
        $oldFile = $backupDir . '/backup_test_old_20200101_000000.sql';
        File::put($oldFile, '-- Old SQL backup');
        touch($oldFile, time() - (40 * 86400)); // 40 days old

        $this->artisan('db:backup', [
            '--database' => 'sms_laravel_test',
            '--retention-days' => '30',
        ])->assertExitCode(0);

        $this->assertFileDoesNotExist($oldFile, 'Old backup was not pruned according to retention policy');
    }

    /** @test */
    public function it_can_restore_database_from_backup_archive_with_checksum_verification()
    {
        // First create backup
        $this->artisan('db:backup', [
            '--database' => 'sms_laravel_test',
            '--compress' => true,
        ])->assertExitCode(0);

        $backupDir = storage_path('app/backups');
        $files = File::glob($backupDir . '/backup_sms_laravel_test_*.sql.gz');
        $this->assertNotEmpty($files);
        $backupFile = end($files);

        // Restore into an isolated database with checksum verification
        $tempDb = 'sms_laravel_restore_unit_test';
        $this->artisan('db:restore', [
            'file' => $backupFile,
            '--target-db' => $tempDb,
            '--force' => true,
            '--verify-checksum' => true,
        ])->assertExitCode(0);

        // Drop temporary test database
        $config = config('database.connections.' . config('database.default'));
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        
        $pdo = new \PDO("mysql:host={$host};port={$port}", $username, $password);
        $pdo->exec("DROP DATABASE IF EXISTS `{$tempDb}`");
    }

    /** @test */
    public function it_rejects_restoring_when_checksum_is_corrupted()
    {
        $backupDir = storage_path('app/backups');
        $corruptFile = $backupDir . '/backup_test_corrupt.sql';
        File::put($corruptFile, '-- Valid SQL');
        
        // Write incorrect checksum metadata
        $metaFile = $corruptFile . '.meta.json';
        File::put($metaFile, json_encode([
            'sha256' => '0000000000000000000000000000000000000000000000000000000000000000',
        ]));

        $this->artisan('db:restore', [
            'file' => $corruptFile,
            '--target-db' => 'sms_laravel_restore_corrupt_test',
            '--force' => true,
            '--verify-checksum' => true,
        ])->assertExitCode(1);

        File::delete([$corruptFile, $metaFile]);
    }

    /** @test */
    public function it_rejects_restoring_with_invalid_target_database_name()
    {
        $backupDir = storage_path('app/backups');
        $dummyFile = $backupDir . '/backup_test_dummy.sql';
        File::put($dummyFile, '-- Dummy SQL');

        $this->artisan('db:restore', [
            'file' => $dummyFile,
            '--target-db' => 'invalid;drop database test;--',
            '--force' => true,
        ])->assertExitCode(1);

        File::delete($dummyFile);
    }

    /** @test */
    public function it_rejects_restoring_into_production_database_without_explicit_confirmation()
    {
        $backupDir = storage_path('app/backups');
        $dummyFile = $backupDir . '/backup_test_dummy.sql';
        File::put($dummyFile, '-- Dummy SQL');

        $prodDb = config('database.connections.mysql.database');

        $this->artisan('db:restore', [
            'file' => $dummyFile,
            '--target-db' => $prodDb,
            '--force' => true,
        ])->assertExitCode(1);

        File::delete($dummyFile);
    }

    /** @test */
    public function it_rejects_restoring_non_sql_files()
    {
        $dummyFile = storage_path('app/backups/test.txt');
        File::put($dummyFile, 'Plain text file');

        $this->artisan('db:restore', [
            'file' => $dummyFile,
            '--target-db' => 'sms_laravel_restore_dummy',
            '--force' => true,
        ])->assertExitCode(1);

        File::delete($dummyFile);
    }
}
