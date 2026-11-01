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
    public function it_can_restore_database_from_backup_archive()
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

        // Restore into an isolated database
        $tempDb = 'sms_laravel_restore_unit_test';
        $this->artisan('db:restore', [
            'file' => $backupFile,
            '--target-db' => $tempDb,
            '--force' => true,
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
}
