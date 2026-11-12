<?php

namespace Tests\Unit;

use Tests\TestCase;

class ReleaseArchiveSecurityTest extends TestCase
{
    private function runValidator(string $archivePath): array
    {
        $script = base_path('.github/scripts/verify-release-archive.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($archivePath) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        return [$code, implode("\n", $output)];
    }

    public function test_valid_release_archive_passes_security_check()
    {
        $tmpDir = sys_get_temp_dir() . '/sms_valid_arch_' . uniqid();
        $archive = sys_get_temp_dir() . '/sms_valid_' . uniqid() . '.tar.gz';
        mkdir($tmpDir . '/app', 0777, true);
        file_put_contents($tmpDir . '/artisan', '#!/usr/bin/env php');
        file_put_contents($tmpDir . '/.release_sha', str_repeat('a', 40) . "\n");
        file_put_contents($tmpDir . '/app/Test.php', "<?php\n");

        try {
            $cmd = 'tar -czf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($tmpDir) . ' artisan .release_sha app/Test.php 2>&1';
            exec($cmd, $out, $tarCode);
            $this->assertEquals(0, $tarCode, implode("\n", $out));

            [$code, $msg] = $this->runValidator($archive);
            $this->assertEquals(0, $code, $msg);
        } finally {
            @unlink($archive);
            @unlink($tmpDir . '/app/Test.php');
            @rmdir($tmpDir . '/app');
            @unlink($tmpDir . '/artisan');
            @unlink($tmpDir . '/.release_sha');
            @rmdir($tmpDir);
        }
    }

    public function test_malicious_archive_with_forbidden_env_or_unexpected_path_is_rejected()
    {
        $tmpDir = sys_get_temp_dir() . '/sms_bad_arch_' . uniqid();
        $archive = sys_get_temp_dir() . '/sms_bad_' . uniqid() . '.tar.gz';
        mkdir($tmpDir . '/tests', 0777, true);
        file_put_contents($tmpDir . '/.env', "APP_KEY=secret\n");
        file_put_contents($tmpDir . '/tests/EvilTest.php', "<?php\n");

        try {
            $cmd = 'tar -czf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($tmpDir) . ' .env tests/EvilTest.php 2>&1';
            exec($cmd, $out, $tarCode);
            $this->assertEquals(0, $tarCode, implode("\n", $out));

            [$code, $msg] = $this->runValidator($archive);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('violation', strtolower($msg));
            $this->assertStringContainsString('.env', $msg);
        } finally {
            @unlink($archive);
            @unlink($tmpDir . '/tests/EvilTest.php');
            @rmdir($tmpDir . '/tests');
            @unlink($tmpDir . '/.env');
            @rmdir($tmpDir);
        }
    }

    public function test_migration_safety_scanner_runs_cleanly()
    {
        $script = base_path('.github/scripts/check-migration-safety.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        $this->assertEquals(0, $code, implode("\n", $output));
    }
}
