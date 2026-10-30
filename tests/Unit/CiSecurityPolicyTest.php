<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CiSecurityPolicyTest extends TestCase
{
    private function rootPath(string $append = ''): string
    {
        return dirname(__DIR__, 2) . ($append !== '' ? DIRECTORY_SEPARATOR . $append : '');
    }

    private function readJson(string $path): array
    {
        $this->assertFileExists($path);

        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded, "{$path} must contain valid JSON.");

        return $decoded;
    }

    public function test_composer_exception_policy_is_documented_and_not_expired(): void
    {
        $policy = $this->readJson($this->rootPath('.github/security/composer-exceptions.json'));

        $this->assertNotEmpty($policy['owner']);
        $this->assertNotEmpty($policy['reviewDue']);
        $this->assertNotSame('', trim((string) $policy['description']));
        $this->assertNotEmpty($policy['exceptions']);

        foreach ($policy['exceptions'] as $exception) {
            foreach (['package', 'advisoryIds', 'severity', 'scope', 'reason', 'compensatingControl', 'owner', 'expires'] as $field) {
                $this->assertArrayHasKey($field, $exception, "Composer exception is missing {$field}.");
            }

            $this->assertContains($exception['scope'], ['runtime', 'build']);
            $this->assertContains($exception['severity'], ['critical', 'high', 'medium', 'low']);
            $this->assertNotEmpty($exception['advisoryIds']);
            $this->assertGreaterThanOrEqual(20, strlen((string) $exception['reason']));

            $expires = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $exception['expires']);
            $this->assertNotFalse($expires, 'Composer exception expiry must be YYYY-MM-DD.');
            $this->assertGreaterThanOrEqual(
                new \DateTimeImmutable('today'),
                $expires,
                "Composer exception for {$exception['package']} has expired and must be re-reviewed."
            );

            foreach ($exception['advisoryIds'] as $id) {
                $this->assertStringNotContainsString('*', $id, 'Wildcard advisory IDs are not allowed.');
            }
        }
    }

    public function test_npm_baseline_is_version_pinned_and_not_expired(): void
    {
        $policy = $this->readJson($this->rootPath('.github/security/npm-exceptions.json'));

        $this->assertNotEmpty($policy['owner']);
        $this->assertNotEmpty($policy['reason']);
        $this->assertNotEmpty($policy['mitigations']);
        $this->assertNotEmpty($policy['accepted']);

        $reviewDue = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $policy['reviewDue']);
        $this->assertNotFalse($reviewDue, 'npm reviewDue must be YYYY-MM-DD.');
        $this->assertGreaterThanOrEqual(new \DateTimeImmutable('today'), $reviewDue);

        foreach ($policy['accepted'] as $entry) {
            $this->assertNotEmpty($entry['package']);
            $this->assertNotEmpty($entry['version']);
            $this->assertContains($entry['severity'], ['critical', 'high']);
            $this->assertIsArray($entry['advisories']);
            $this->assertIsArray($entry['via']);
        }
    }

    public function test_runtime_php_constraint_matches_installed_dependency_requirements(): void
    {
        $composer = $this->readJson($this->rootPath('composer.json'));

        // minishlink/web-push 11 and several locked transitive packages require
        // PHP >= 8.2, so advertising PHP 7.x/8.0/8.1 support is misleading.
        $this->assertSame('^8.2', $composer['require']['php']);
    }
}
