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

    private function buildTarGzFromEntries(array $entries, string $outPath): void
    {
        $tarData = '';
        foreach ($entries as $entry) {
            $name = $entry['name'];
            $content = $entry['content'] ?? '';
            $type = $entry['type'] ?? '0';
            $size = strlen($content);

            $header = str_pad(substr($name, 0, 100), 100, "\0");
            $header .= str_pad('0000644', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad('0000000', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad('0000000', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad(decoct($size), 11, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad(decoct(time()), 11, '0', STR_PAD_LEFT) . "\0";
            $header .= str_repeat(' ', 8); // checksum placeholder
            $header .= $type;
            $header .= str_pad(substr($entry['linkname'] ?? '', 0, 100), 100, "\0");
            $header .= "ustar\0" . '00';
            $header .= str_repeat("\0", 32 + 32 + 8 + 8 + 155 + 12);

            $checksum = 0;
            for ($i = 0; $i < 512; $i++) {
                $checksum += ord($header[$i]);
            }
            $chkStr = str_pad(decoct($checksum), 6, '0', STR_PAD_LEFT) . "\0 ";
            $header = substr_replace($header, $chkStr, 148, 8);

            $tarData .= $header;
            if ($size > 0) {
                $tarData .= $content;
                $rem = $size % 512;
                if ($rem > 0) {
                    $tarData .= str_repeat("\0", 512 - $rem);
                }
            }
        }
        $tarData .= str_repeat("\0", 1024);

        $gz = gzopen($outPath, 'wb9');
        gzwrite($gz, $tarData);
        gzclose($gz);
    }

    public function test_valid_release_archive_passes_security_check()
    {
        $archive = sys_get_temp_dir() . '/sms_valid_' . uniqid() . '.tar.gz';
        $this->buildTarGzFromEntries([
            ['name' => 'artisan', 'content' => '#!/usr/bin/env php'],
            ['name' => '.release_sha', 'content' => str_repeat('a', 40) . "\n"],
            ['name' => 'app/Test.php', 'content' => "<?php\n"],
        ], $archive);

        try {
            [$code, $msg] = $this->runValidator($archive);
            $this->assertEquals(0, $code, $msg);
        } finally {
            @unlink($archive);
        }
    }

    public function test_malicious_traversal_absolute_windows_symlink_duplicate_newline_and_env_are_rejected()
    {
        $cases = [
            'traversal' => [['name' => '../../etc/passwd', 'content' => 'root:x']],
            'absolute' => [['name' => '/etc/passwd', 'content' => 'root:x']],
            'windows_drive' => [['name' => 'C:\\Windows\\win.ini', 'content' => 'x']],
            'symlink' => [['name' => 'app/evil_link', 'type' => '2', 'linkname' => '/etc/passwd']],
            'hardlink' => [['name' => 'app/evil_hard', 'type' => '1', 'linkname' => '/etc/passwd']],
            'duplicate' => [
                ['name' => 'app/Dup.php', 'content' => '1'],
                ['name' => 'app/Dup.php', 'content' => '2'],
            ],
            'newline' => [['name' => "app/bad\nname.php", 'content' => '1']],
            'env_file' => [['name' => '.env', 'content' => 'APP_KEY=secret']],
            'tests_dir' => [['name' => 'tests/EvilTest.php', 'content' => '<?php']],
        ];

        foreach ($cases as $label => $entries) {
            $archive = sys_get_temp_dir() . "/sms_{$label}_" . uniqid() . '.tar.gz';
            $this->buildTarGzFromEntries($entries, $archive);
            try {
                [$code, $msg] = $this->runValidator($archive);
                $this->assertNotEquals(0, $code, "Case '{$label}' should have been rejected but passed: {$msg}");
            } finally {
                @unlink($archive);
            }
        }
    }
}
