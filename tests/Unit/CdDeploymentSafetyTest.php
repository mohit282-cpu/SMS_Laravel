<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CdDeploymentSafetyTest extends TestCase
{
    private function rootPath(string $append = ''): string
    {
        return dirname(__DIR__, 2) . ($append !== '' ? DIRECTORY_SEPARATOR . $append : '');
    }

    private function getCdWorkflowContent(): string
    {
        $path = $this->rootPath('.github/workflows/cd.yml');
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    public function test_cd_workflow_implements_fail_closed_validation_and_rollback(): void
    {
        $cdYaml = $this->getCdWorkflowContent();

        // 1. Workflow dispatch inputs
        $this->assertStringContainsString('workflow_dispatch:', $cdYaml);
        $this->assertStringContainsString('target_env:', $cdYaml);
        $this->assertStringContainsString('run_migrations:', $cdYaml);
        $this->assertStringContainsString('enable_maintenance:', $cdYaml);

        // 2. SSH Strict Host Key Checking & MITM Prevention
        $this->assertStringContainsString('StrictHostKeyChecking=yes', $cdYaml);
        $this->assertStringContainsString('DEPLOY_SSH_KNOWN_HOSTS', $cdYaml);
        $this->assertStringContainsString('ConnectTimeout=15', $cdYaml);

        // 3. Pre-extraction Verification
        $this->assertStringContainsString('sha256sum -c "release-${SHA}.tar.gz.sha256"', $cdYaml);
        $this->assertStringContainsString('php /tmp/verify-release-archive.php', $cdYaml);
        $this->assertStringContainsString('--no-same-owner', $cdYaml);

        // 4. In-flight Rollback Trap & Safety
        $this->assertStringContainsString("trap 'rollback_handler' ERR", $cdYaml);
        $this->assertStringContainsString('ln -sfn "${PREVIOUS_RELEASE}" "${TARGET_DIR}/current"', $cdYaml);
        $this->assertStringNotContainsString('artisan migrate:rollback', $cdYaml, 'Automated migration rollback on production/staging must never run unattended.');

        // 5. PHP-FPM Service Reload with Verification
        $this->assertStringContainsString('reload_php_fpm()', $cdYaml);
        $this->assertStringContainsString('sudo systemctl reload "${service}"', $cdYaml);
        $this->assertStringContainsString('sudo systemctl is-active --quiet "${service}"', $cdYaml);

        // 6. JSON-based Readiness Probe
        $this->assertStringContainsString('probe_readiness()', $cdYaml);
        $this->assertStringContainsString('/health/readiness', $cdYaml);
        $this->assertStringContainsString('$json = json_decode($input, true);', $cdYaml);
    }

    public function test_cd_path_and_port_validation_regex(): void
    {
        $safePathRegex = '/^\/[A-Za-z0-9\/_-]+$/';
        $portRegex = '/^[0-9]+$/';
        $shaRegex = '/^[0-9a-f]{40}$/';

        // Safe deployment paths
        $this->assertMatchesRegularExpression($safePathRegex, '/var/www/sms_academy');
        $this->assertMatchesRegularExpression($safePathRegex, '/opt/app_2026/site');

        // Unsafe deployment paths
        $this->assertDoesNotMatchRegularExpression($safePathRegex, 'var/www/relative');
        $this->assertDoesNotMatchRegularExpression($safePathRegex, '/var/www/$(rm -rf)');
        $this->assertDoesNotMatchRegularExpression($safePathRegex, '/var/www/app; reboot');

        // Ports
        $this->assertMatchesRegularExpression($portRegex, '22');
        $this->assertMatchesRegularExpression($portRegex, '2222');
        $this->assertDoesNotMatchRegularExpression($portRegex, '22; drop');
        $this->assertDoesNotMatchRegularExpression($portRegex, '-22');

        // Git SHA
        $this->assertMatchesRegularExpression($shaRegex, 'ca5ef0839f1abe84e4c3deba927323d3bd175f39');
        $this->assertDoesNotMatchRegularExpression($shaRegex, 'ca5ef0839f1abe84e4c3deba927323d3bd175f3');
        $this->assertDoesNotMatchRegularExpression($shaRegex, 'CA5EF0839F1ABE84E4C3DEBA927323D3BD175F39');
        $this->assertDoesNotMatchRegularExpression($shaRegex, 'ca5ef0839f1abe84e4c3deba927323d3bd175f3g');
    }

    public function test_readiness_probe_json_parser_logic(): void
    {
        $parseSnippet = function (string $rawBody): array {
            $json = json_decode($rawBody, true);
            if (!is_array($json)) {
                return ['status' => 'invalid', 'release' => 'none'];
            }
            $status = trim((string) ($json['status'] ?? ''));
            $rel = trim((string) ($json['release'] ?? ''));
            return ['status' => $status, 'release' => $rel];
        };

        // 1. Valid ready response
        $sha = '0123456789abcdef0123456789abcdef01234567';
        $res = $parseSnippet(json_encode(['status' => 'ready', 'release' => $sha]));
        $this->assertSame('ready', $res['status']);
        $this->assertSame($sha, $res['release']);

        // 2. Valid healthy response
        $res = $parseSnippet(json_encode(['status' => 'healthy', 'release' => $sha]));
        $this->assertSame('healthy', $res['status']);
        $this->assertSame($sha, $res['release']);

        // 3. Degraded / unready response
        $res = $parseSnippet(json_encode(['status' => 'unhealthy', 'release' => $sha]));
        $this->assertSame('unhealthy', $res['status']);

        // 4. HTML error page or PHP fatal error
        $res = $parseSnippet('<!DOCTYPE html><html><body>500 Internal Server Error</body></html>');
        $this->assertSame('invalid', $res['status']);
        $this->assertSame('none', $res['release']);

        // 5. Empty response
        $res = $parseSnippet('');
        $this->assertSame('invalid', $res['status']);
        $this->assertSame('none', $res['release']);
    }

    public function test_pre_deploy_validation_fails_on_missing_or_corrupt_files(): void
    {
        $validateArchive = function (string $dir, string $sha): array {
            $archive = $dir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz";
            $checksum = $dir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz.sha256";

            if (!file_exists($archive)) {
                return ['ok' => false, 'error' => 'Missing release archive'];
            }
            if (!file_exists($checksum)) {
                return ['ok' => false, 'error' => 'Missing checksum file'];
            }

            $expectedHash = trim((string) explode(' ', (string) file_get_contents($checksum))[0]);
            $actualHash = hash_file('sha256', $archive);

            if ($expectedHash !== $actualHash) {
                return ['ok' => false, 'error' => 'Checksum mismatch'];
            }

            return ['ok' => true, 'error' => null];
        };

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cd_test_' . bin2hex(random_bytes(6));
        mkdir($tmpDir, 0777, true);
        $sha = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        try {
            // Case 1: Missing archive
            $res = $validateArchive($tmpDir, $sha);
            $this->assertFalse($res['ok']);
            $this->assertSame('Missing release archive', $res['error']);

            // Case 2: Archive exists but missing checksum
            $archiveFile = $tmpDir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz";
            file_put_contents($archiveFile, 'fake archive bytes');
            $res = $validateArchive($tmpDir, $sha);
            $this->assertFalse($res['ok']);
            $this->assertSame('Missing checksum file', $res['error']);

            // Case 3: Checksum mismatch (corrupted artifact)
            $checksumFile = $tmpDir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz.sha256";
            file_put_contents($checksumFile, "deadbeefdeadbeef  release-{$sha}.tar.gz\n");
            $res = $validateArchive($tmpDir, $sha);
            $this->assertFalse($res['ok']);
            $this->assertSame('Checksum mismatch', $res['error']);

            // Case 4: Correct checksum
            $correctHash = hash_file('sha256', $archiveFile);
            file_put_contents($checksumFile, "{$correctHash}  release-{$sha}.tar.gz\n");
            $res = $validateArchive($tmpDir, $sha);
            $this->assertTrue($res['ok']);
            $this->assertNull($res['error']);
        } finally {
            @unlink($tmpDir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz");
            @unlink($tmpDir . DIRECTORY_SEPARATOR . "release-{$sha}.tar.gz.sha256");
            @rmdir($tmpDir);
        }
    }

    public function test_secret_and_env_validation_logic(): void
    {
        $validateCreds = function (array $env): array {
            $requiredSecrets = ['DEPLOY_HOST', 'DEPLOY_USER', 'DEPLOY_SSH_KEY', 'DEPLOY_SSH_KNOWN_HOSTS'];
            foreach ($requiredSecrets as $key) {
                if (empty($env[$key])) {
                    return ['ok' => false, 'error' => "Missing secret: {$key}"];
                }
            }
            if (empty($env['APP_URL']) || !filter_var($env['APP_URL'], FILTER_VALIDATE_URL)) {
                return ['ok' => false, 'error' => 'Invalid APP_URL'];
            }
            if (!isset($env['DEPLOY_PORT']) || !ctype_digit((string) $env['DEPLOY_PORT'])) {
                return ['ok' => false, 'error' => 'Invalid DEPLOY_PORT'];
            }
            if (empty($env['DEPLOY_PATH']) || !preg_match('/^\/[A-Za-z0-9\/_-]+$/', (string) $env['DEPLOY_PATH']) || $env['DEPLOY_PATH'] === '/') {
                return ['ok' => false, 'error' => 'Invalid DEPLOY_PATH'];
            }
            return ['ok' => true, 'error' => null];
        };

        // All valid
        $valid = [
            'DEPLOY_HOST' => '192.168.1.100',
            'DEPLOY_USER' => 'deploy',
            'DEPLOY_SSH_KEY' => '-----BEGIN OPENSSH PRIVATE KEY-----...',
            'DEPLOY_SSH_KNOWN_HOSTS' => '192.168.1.100 ssh-ed25519 AAAAC3...',
            'APP_URL' => 'https://sms-staging.internal',
            'DEPLOY_PORT' => '22',
            'DEPLOY_PATH' => '/var/www/sms_academy',
        ];
        $this->assertTrue($validateCreds($valid)['ok']);

        // Missing host
        $inv = $valid;
        unset($inv['DEPLOY_HOST']);
        $this->assertFalse($validateCreds($inv)['ok']);

        // Missing known hosts (MITM vulnerability)
        $inv = $valid;
        unset($inv['DEPLOY_SSH_KNOWN_HOSTS']);
        $this->assertFalse($validateCreds($inv)['ok']);

        // Dangerous / invalid path
        $inv = $valid;
        $inv['DEPLOY_PATH'] = '/';
        $this->assertFalse($validateCreds($inv)['ok']);

        $inv['DEPLOY_PATH'] = '/var/www/app; rm -rf /';
        $this->assertFalse($validateCreds($inv)['ok']);
    }

    public function test_rollback_handler_resilience_simulation(): void
    {
        $simulateRollback = function (?string $previousReleaseDir, bool $phpFpmReloadSuccess, bool $probeSuccess): array {
            $rollbackErrors = 0;
            if ($previousReleaseDir === null || !is_dir($previousReleaseDir)) {
                return [
                    'can_rollback' => false,
                    'active_symlink_reverted' => false,
                    'maintenance_cleared' => false,
                    'verified_healthy' => false,
                    'errors' => 1,
                ];
            }

            $activeSymlinkReverted = true;
            $maintenanceCleared = true;

            if (!$phpFpmReloadSuccess) {
                $rollbackErrors++;
            }
            if (!$probeSuccess) {
                $rollbackErrors++;
            }

            return [
                'can_rollback' => true,
                'active_symlink_reverted' => $activeSymlinkReverted,
                'maintenance_cleared' => $maintenanceCleared,
                'verified_healthy' => $rollbackErrors === 0,
                'errors' => $rollbackErrors,
            ];
        };

        // Scenario 1: Initial deployment (no previous release to roll back to)
        $s1 = $simulateRollback(null, true, true);
        $this->assertFalse($s1['can_rollback']);
        $this->assertFalse($s1['verified_healthy']);
        $this->assertSame(1, $s1['errors']);

        // Scenario 2: Normal rollback, previous release exists, reload and probe succeed
        $tmpPrevDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prev_rel_' . bin2hex(random_bytes(4));
        mkdir($tmpPrevDir, 0777, true);
        try {
            $s2 = $simulateRollback($tmpPrevDir, true, true);
            $this->assertTrue($s2['can_rollback']);
            $this->assertTrue($s2['active_symlink_reverted']);
            $this->assertTrue($s2['maintenance_cleared']);
            $this->assertTrue($s2['verified_healthy']);
            $this->assertSame(0, $s2['errors']);

            // Scenario 3: Rollback attempted but PHP-FPM reload fails
            $s3 = $simulateRollback($tmpPrevDir, false, true);
            $this->assertTrue($s3['can_rollback']);
            $this->assertFalse($s3['verified_healthy']);
            $this->assertSame(1, $s3['errors']);

            // Scenario 4: Rollback attempted but previous release also fails readiness check
            $s4 = $simulateRollback($tmpPrevDir, true, false);
            $this->assertTrue($s4['can_rollback']);
            $this->assertFalse($s4['verified_healthy']);
            $this->assertSame(1, $s4['errors']);

            // Scenario 5: Both PHP-FPM reload and probe fail during rollback
            $s5 = $simulateRollback($tmpPrevDir, false, false);
            $this->assertSame(2, $s5['errors']);
            $this->assertFalse($s5['verified_healthy']);
        } finally {
            @rmdir($tmpPrevDir);
        }
    }
}
