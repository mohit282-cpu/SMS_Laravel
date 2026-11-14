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

    private function buildTarGzFromEntries(array $entries, string $outPath, array $options = []): void
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

            if (!empty($entry['corrupt_checksum'])) {
                $chkStr = '000001' . "\0 ";
            } else {
                $checksum = 0;
                for ($i = 0; $i < 512; $i++) {
                    $checksum += ord($header[$i]);
                }
                $chkStr = str_pad(decoct($checksum), 6, '0', STR_PAD_LEFT) . "\0 ";
            }
            $header = substr_replace($header, $chkStr, 148, 8);

            if (!empty($entry['truncate_header'])) {
                $header = substr($header, 0, 250); // Truncate header block
            }

            $tarData .= $header;
            if ($size > 0 && empty($entry['truncate_header'])) {
                if (!empty($entry['truncate_payload'])) {
                    $tarData .= substr($content, 0, (int) ($size / 2));
                    // Intentionally omit padding to simulate truncated stream
                } else {
                    $tarData .= $content;
                    $rem = $size % 512;
                    if ($rem > 0) {
                        $tarData .= str_repeat("\0", 512 - $rem);
                    }
                }
            }
        }

        if (empty($options['omit_terminator'])) {
            $tarData .= str_repeat("\0", 1024);
        }

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
            $this->assertStringContainsString('security check passed', $msg);
        } finally {
            @unlink($archive);
        }
    }

    public function test_adversarial_archives_are_strictly_rejected()
    {
        $cases = [
            'invalid_checksum' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1', 'corrupt_checksum' => true]],
            ],
            'absolute_path' => [
                'entries' => [['name' => '/etc/passwd', 'content' => 'root:x']],
            ],
            'path_traversal' => [
                'entries' => [['name' => '../../etc/passwd', 'content' => 'root:x']],
            ],
            'windows_drive' => [
                'entries' => [['name' => 'C:\\Windows\\win.ini', 'content' => 'x']],
            ],
            'symlink' => [
                'entries' => [['name' => 'app/evil_link', 'type' => '2', 'linkname' => '/etc/passwd']],
            ],
            'hardlink' => [
                'entries' => [['name' => 'app/evil_hard', 'type' => '1', 'linkname' => '/etc/passwd']],
            ],
            'duplicate_entry' => [
                'entries' => [
                    ['name' => 'app/Dup.php', 'content' => '1'],
                    ['name' => 'app/Dup.php', 'content' => '2'],
                ],
            ],
            'malformed_pax_header' => [
                'entries' => [
                    ['name' => 'PaxHeader/bad', 'type' => 'x', 'content' => "not_a_valid_pax_record\n"],
                    ['name' => 'app/File.php', 'content' => 'ok'],
                ],
            ],
            'invalid_gnu_longname' => [
                'entries' => [
                    ['name' => '././@LongLink', 'type' => 'L', 'content' => "bad\nname\0"],
                    ['name' => 'app/File.php', 'content' => 'ok'],
                ],
            ],
            'truncated_header' => [
                'entries' => [['name' => 'app/Trunc.php', 'content' => 'test', 'truncate_header' => true]],
                'options' => ['omit_terminator' => true],
            ],
            'truncated_payload' => [
                'entries' => [['name' => 'app/TruncPayload.php', 'content' => str_repeat('a', 1024), 'truncate_payload' => true]],
                'options' => ['omit_terminator' => true],
            ],
            'control_char_filename' => [
                'entries' => [['name' => "app/bad\nname.php", 'content' => '1']],
            ],
            'forbidden_env' => [
                'entries' => [['name' => '.env', 'content' => 'APP_KEY=secret']],
            ],
            'unexpected_top_level' => [
                'entries' => [['name' => 'evil_dir/Malicious.php', 'content' => '<?php']],
            ],
            'tests_dir' => [
                'entries' => [['name' => 'tests/EvilTest.php', 'content' => '<?php']],
            ],
        ];

        foreach ($cases as $label => $spec) {
            $archive = sys_get_temp_dir() . "/sms_{$label}_" . uniqid() . '.tar.gz';
            $this->buildTarGzFromEntries($spec['entries'], $archive, $spec['options'] ?? []);
            try {
                [$code, $msg] = $this->runValidator($archive);
                $this->assertNotEquals(0, $code, "Adversarial case '{$label}' should have been rejected but passed: {$msg}");
            } finally {
                @unlink($archive);
            }
        }
    }
}
