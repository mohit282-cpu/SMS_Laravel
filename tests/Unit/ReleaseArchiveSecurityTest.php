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

    private function buildTarGz(array $entries, string $outPath, array $options = []): void
    {
        $tarData = '';
        foreach ($entries as $entry) {
            $name = $entry['name'];
            $content = $entry['content'] ?? '';
            $type = $entry['type'] ?? '0';
            $size = strlen($content);

            $sizeStr = !empty($entry['raw_size']) ? $entry['raw_size'] : (str_pad(decoct($size), 11, '0', STR_PAD_LEFT) . "\0");

            $header = str_pad(substr($name, 0, 100), 100, "\0");
            $header .= str_pad('0000644', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad('0000000', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad('0000000', 7, '0', STR_PAD_LEFT) . "\0";
            $header .= str_pad($sizeStr, 12, "\0", STR_PAD_RIGHT);
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
                $header = substr($header, 0, 250);
            }

            $tarData .= $header;
            if ($size > 0 && empty($entry['truncate_header'])) {
                if (!empty($entry['truncate_payload'])) {
                    $tarData .= substr($content, 0, (int) ($size / 2));
                } else {
                    $tarData .= $content;
                    $rem = $size % 512;
                    if ($rem > 0) {
                        $tarData .= str_repeat("\0", 512 - $rem);
                    }
                }
            }
        }

        // Handle End of Archive zero blocks
        $zeroBlocks = $options['zero_blocks'] ?? 2;
        if ($zeroBlocks > 0) {
            $tarData .= str_repeat("\0", 512 * $zeroBlocks);
        }

        if (!empty($options['invalid_second_block'])) {
            $tarData .= str_repeat("\0", 512);
            $tarData .= str_pad('CORRUPTED_NON_ZERO_DATA', 512, "\0");
        }

        if (!empty($options['trailing_data'])) {
            $tarData .= str_pad('TRAILING_DATA', 512, 'X');
        }

        $gz = gzencode($tarData, 9);

        if (!empty($options['corrupt_gzip_crc'])) {
            // Corrupt the 4-byte CRC at offset -8
            $gz = substr($gz, 0, -8) . "\xde\xad\xbe\xef" . substr($gz, -4);
        }

        if (!empty($options['truncate_gzip'])) {
            // Truncate compressed stream
            $gz = substr($gz, 0, -30);
        }

        file_put_contents($outPath, $gz);
    }

    public function test_valid_ustar_archive_passes()
    {
        $archive = sys_get_temp_dir() . '/sms_valid_ustar_' . uniqid() . '.tar.gz';
        $this->buildTarGz([
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

    public function test_valid_gnu_longname_archive_passes()
    {
        $archive = sys_get_temp_dir() . '/sms_valid_gnu_' . uniqid() . '.tar.gz';
        $longPath = 'app/Services/Deeply/Nested/Directory/Path/That/Exceeds/One/Hundred/Characters/For/Testing/Purposes/LongFileName.php';
        $this->buildTarGz([
            ['name' => '././@LongLink', 'type' => 'L', 'content' => $longPath . "\0"],
            ['name' => substr($longPath, 0, 100), 'content' => "<?php\n"],
            ['name' => '.release_sha', 'content' => str_repeat('a', 40) . "\n"],
            ['name' => 'artisan', 'content' => '#!/usr/bin/env php'],
        ], $archive);

        try {
            [$code, $msg] = $this->runValidator($archive);
            $this->assertEquals(0, $code, $msg);
            $this->assertStringContainsString('security check passed', $msg);
        } finally {
            @unlink($archive);
        }
    }

    public function test_valid_pax_archive_passes()
    {
        $archive = sys_get_temp_dir() . '/sms_valid_pax_' . uniqid() . '.tar.gz';
        $paxRecord = "29 path=app/ValidPaxFile.php\n";
        $this->buildTarGz([
            ['name' => 'PaxHeader/app_ValidPaxFile', 'type' => 'x', 'content' => $paxRecord],
            ['name' => 'app/dummy.php', 'content' => "<?php\n"],
            ['name' => '.release_sha', 'content' => str_repeat('a', 40) . "\n"],
            ['name' => 'artisan', 'content' => '#!/usr/bin/env php'],
        ], $archive);

        try {
            [$code, $msg] = $this->runValidator($archive);
            $this->assertEquals(0, $code, $msg);
            $this->assertStringContainsString('security check passed', $msg);
        } finally {
            @unlink($archive);
        }
    }

    public function test_adversarial_and_malformed_archives_are_strictly_rejected()
    {
        $cases = [
            'corrupted_header_checksum' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1', 'corrupt_checksum' => true]],
            ],
            'invalid_octal_size' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1', 'raw_size' => "99999999999\0"]],
            ],
            'truncated_header' => [
                'entries' => [['name' => 'app/Trunc.php', 'content' => 'test', 'truncate_header' => true]],
                'options' => ['zero_blocks' => 0],
            ],
            'truncated_payload' => [
                'entries' => [['name' => 'app/TruncPayload.php', 'content' => str_repeat('a', 1024), 'truncate_payload' => true]],
                'options' => ['zero_blocks' => 0],
            ],
            'missing_first_zero_block' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['zero_blocks' => 0],
            ],
            'missing_second_zero_block' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['zero_blocks' => 1],
            ],
            'invalid_end_of_archive_sequence' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['zero_blocks' => 0, 'invalid_second_block' => true],
            ],
            'unexpected_trailing_data' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['zero_blocks' => 2, 'trailing_data' => true],
            ],
            'malformed_pax_length' => [
                'entries' => [
                    ['name' => 'PaxHeader/bad', 'type' => 'x', 'content' => "999 path=bad\n"],
                    ['name' => 'app/File.php', 'content' => 'ok'],
                ],
            ],
            'invalid_pax_path' => [
                'entries' => [
                    ['name' => 'PaxHeader/bad', 'type' => 'x', 'content' => "28 path=../../etc/passwd\n"],
                    ['name' => 'app/File.php', 'content' => 'ok'],
                ],
            ],
            'invalid_gnu_longname' => [
                'entries' => [
                    ['name' => '././@LongLink', 'type' => 'L', 'content' => "bad\nname\0"],
                    ['name' => 'app/File.php', 'content' => 'ok'],
                ],
            ],
            'absolute_path' => [
                'entries' => [['name' => '/etc/passwd', 'content' => 'root:x']],
            ],
            'path_traversal' => [
                'entries' => [['name' => '../../etc/passwd', 'content' => 'root:x']],
            ],
            'windows_drive_path' => [
                'entries' => [['name' => 'C:\\Windows\\win.ini', 'content' => 'x']],
            ],
            'symlink' => [
                'entries' => [['name' => 'app/evil_link', 'type' => '2', 'linkname' => '/etc/passwd']],
            ],
            'hardlink' => [
                'entries' => [['name' => 'app/evil_hard', 'type' => '1', 'linkname' => '/etc/passwd']],
            ],
            'duplicate_normalized_filename' => [
                'entries' => [
                    ['name' => 'app/Dup.php', 'content' => '1'],
                    ['name' => './app/Dup.php', 'content' => '2'],
                ],
            ],
            'control_char_filename' => [
                'entries' => [['name' => "app/bad\nname.php", 'content' => '1']],
            ],
            'forbidden_env' => [
                'entries' => [['name' => '.env', 'content' => 'APP_KEY=secret']],
            ],
            'invalid_gzip_crc' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['corrupt_gzip_crc' => true],
            ],
            'truncated_gzip_stream' => [
                'entries' => [['name' => 'app/File.php', 'content' => '1']],
                'options' => ['truncate_gzip' => true],
            ],
        ];

        foreach ($cases as $label => $spec) {
            $archive = sys_get_temp_dir() . "/sms_{$label}_" . uniqid() . '.tar.gz';
            $this->buildTarGz($spec['entries'], $archive, $spec['options'] ?? []);
            try {
                [$code, $msg] = $this->runValidator($archive);
                $this->assertNotEquals(0, $code, "Adversarial fixture '{$label}' should have been rejected but passed: {$msg}");
            } finally {
                @unlink($archive);
            }
        }
    }
}
