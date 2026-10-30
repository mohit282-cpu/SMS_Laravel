<?php

/**
 * Composer security audit gate.
 *
 * Fails closed: an unavailable or unparsable audit result is a CI failure, not
 * a pass. Advisories may only be accepted through the documented, time-bound
 * policy in .github/security/composer-exceptions.json. The gate fails when:
 *   - composer audit cannot run or does not return valid JSON;
 *   - an advisory is not covered by an exception;
 *   - an exception is expired, malformed, duplicated, or uses a wildcard;
 *   - an exception is stale (it no longer matches a live advisory);
 *   - a build-only exception tries to cover a runtime dependency.
 */

const POLICY_FILE = __DIR__ . '/../security/composer-exceptions.json';
const LOCK_FILE = __DIR__ . '/../../composer.lock';

$violations = [];
$notes = [];

function fail(string $message): void
{
    fwrite(STDERR, "::error::{$message}\n");
    exit(1);
}

function readJsonFile(string $path, string $label): array
{
    if (!is_file($path)) {
        fail("{$label} not found at {$path}.");
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        fail("{$label} is empty or unreadable at {$path}.");
    }

    $data = json_decode($contents, true);
    if (!is_array($data)) {
        fail("{$label} is not valid JSON: " . json_last_error_msg());
    }

    return $data;
}

function runComposerAudit(): array
{
    $binary = getenv('COMPOSER_BINARY') ?: 'composer';
    // Quoting a bare command name breaks %~dp0 resolution in composer.bat on
    // Windows, so only quote values that contain a path separator or space.
    $binaryCommand = preg_match('/[\/\\\\ ]/', $binary) ? escapeshellarg($binary) : $binary;
    $errorFile = tempnam(sys_get_temp_dir(), 'composer-audit-');
    if ($errorFile === false) {
        fail('Unable to create a temporary file for composer audit diagnostics.');
    }

    $command = $binaryCommand . ' audit --locked --format=json 2> ' . escapeshellarg($errorFile);

    $projectRoot = dirname(__DIR__, 2);
    if (getcwd() !== $projectRoot) {
        chdir($projectRoot);
    }

    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);

    $stdout = implode("\n", $output);
    $stderr = is_file($errorFile) ? trim((string) file_get_contents($errorFile)) : '';
    @unlink($errorFile);

    // Composer audit exit codes: 0 = no advisories, 1 = advisories found,
    // 2 (or anything else) = the audit itself failed.
    if (!in_array($exitCode, [0, 1], true)) {
        $detail = $stderr !== '' ? $stderr : $stdout;
        fail("composer audit failed to run (exit code {$exitCode}). This is a tool/network error, not a clean audit. Output: " . substr($detail, 0, 1500));
    }

    $data = json_decode($stdout, true);
    if (!is_array($data)) {
        $detail = $stderr !== '' ? $stderr : substr($stdout, 0, 1500);
        fail('composer audit did not return parsable JSON. Audit result is unavailable, so the security gate cannot pass. Output: ' . $detail);
    }

    return ['data' => $data, 'exitCode' => $exitCode, 'stderr' => $stderr];
}

$policy = readJsonFile(POLICY_FILE, 'Composer exception policy');
$lock = readJsonFile(LOCK_FILE, 'composer.lock');

$runtimePackages = [];
foreach ($lock['packages'] ?? [] as $package) {
    if (isset($package['name'])) {
        $runtimePackages[strtolower($package['name'])] = true;
    }
}

$exceptions = $policy['exceptions'] ?? null;
if (!is_array($exceptions)) {
    fail('Composer exception policy must contain an "exceptions" array.');
}

$today = new DateTimeImmutable('today');
$seenAdvisoryIds = [];
$validExceptions = [];

foreach ($exceptions as $index => $exception) {
    $label = "Exception #{$index} (" . ($exception['package'] ?? 'unknown package') . ')';
    $required = ['package', 'advisoryIds', 'severity', 'scope', 'reason', 'compensatingControl', 'owner', 'expires'];

    foreach ($required as $key) {
        if (!isset($exception[$key]) || (is_string($exception[$key]) && trim($exception[$key]) === '')) {
            $violations[] = "{$label} is missing required field \"{$key}\".";
        }
    }

    $package = strtolower(trim((string) ($exception['package'] ?? '')));
    $scope = (string) ($exception['scope'] ?? '');
    $severity = strtolower((string) ($exception['severity'] ?? ''));

    if (!in_array($scope, ['runtime', 'build'], true)) {
        $violations[] = "{$label} has invalid scope \"{$scope}\" (expected runtime or build).";
    }

    if (!in_array($severity, ['critical', 'high', 'medium', 'low'], true)) {
        $violations[] = "{$label} has invalid severity \"{$severity}\".";
    }

    if (isset($exception['reason']) && strlen(trim((string) $exception['reason'])) < 20) {
        $violations[] = "{$label} reason is too short to be an auditable justification.";
    }

    $expires = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($exception['expires'] ?? ''));
    if (!$expires || $expires->format('Y-m-d') !== (string) ($exception['expires'] ?? '')) {
        $violations[] = "{$label} has an invalid expiry date; expected YYYY-MM-DD.";
    } elseif ($expires < $today) {
        $violations[] = "{$label} expired on {$expires->format('Y-m-d')}; re-review the risk or upgrade the dependency.";
    }

    $ids = $exception['advisoryIds'] ?? [];
    if (!is_array($ids) || $ids === []) {
        $violations[] = "{$label} must list at least one advisory ID.";
        $ids = [];
    }

    foreach ($ids as $id) {
        if (!is_string($id) || trim($id) === '') {
            $violations[] = "{$label} contains an empty or non-string advisory ID.";
            continue;
        }

        if (strpos($id, '*') !== false) {
            $violations[] = "{$label} contains wildcard advisory ID \"{$id}\"; wildcards are not allowed.";
        }

        $key = $package . '|' . strtolower(trim($id));
        if (isset($seenAdvisoryIds[$key])) {
            $violations[] = "{$label} duplicates advisory ID \"{$id}\" already claimed by {$seenAdvisoryIds[$key]}.";
        }
        $seenAdvisoryIds[$key] = $label;
    }

    $validExceptions[] = [
        'package' => $package,
        'ids' => array_map(static fn ($id) => strtolower(trim((string) $id)), $ids),
        'scope' => $scope,
        'label' => $label,
    ];
}

$audit = runComposerAudit();
$advisories = $audit['data']['advisories'] ?? [];

if (!is_array($advisories)) {
    fail('composer audit JSON has an invalid "advisories" section.');
}

if ($audit['exitCode'] === 1 && $advisories === []) {
    fail('composer audit reported vulnerabilities (exit code 1) but returned no advisories. Treating the result as unavailable.');
}

$matchedExceptions = [];
$findingCount = 0;
$severityCounts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'unknown' => 0];

foreach ($advisories as $package => $packageAdvisories) {
    $normalizedPackage = strtolower((string) $package);
    $packageScope = isset($runtimePackages[$normalizedPackage]) ? 'runtime' : 'build';

    foreach ($packageAdvisories as $advisory) {
        $findingCount++;

        $severity = strtolower((string) ($advisory['severity'] ?? 'unknown'));
        $severityCounts[isset($severityCounts[$severity]) ? $severity : 'unknown']++;

        $ids = array_filter(array_unique(array_merge(
            [(string) ($advisory['advisoryId'] ?? '')],
            [(string) ($advisory['cve'] ?? '')],
            array_map(static fn ($source) => (string) ($source['remoteId'] ?? ''), $advisory['sources'] ?? [])
        )));
        $ids = array_map(static fn ($id) => strtolower(trim($id)), $ids);

        $matched = null;
        foreach ($validExceptions as $exception) {
            if ($exception['package'] !== $normalizedPackage) {
                continue;
            }

            if (array_intersect($ids, $exception['ids']) !== []) {
                $matched = $exception;
                break;
            }
        }

        $title = $advisory['title'] ?? 'Unknown advisory';

        if ($matched === null) {
            $violations[] = sprintf(
                'UNAPPROVED %s advisory for %s: %s (IDs: %s). Fix the dependency or add a documented, time-bound exception.',
                strtoupper($severity),
                $package,
                $title,
                implode(', ', $ids) ?: 'none'
            );
            continue;
        }

        $matchedExceptions[$matched['label']] = true;

        if ($matched['scope'] !== $packageScope) {
            $violations[] = sprintf(
                'SCOPE MISMATCH: %s is a %s dependency but is covered by a "%s" exception (%s). Runtime advisories require runtime-scope acceptance.',
                $package,
                $packageScope,
                $matched['scope'],
                $title
            );
        }
    }
}

foreach ($validExceptions as $exception) {
    if (!isset($matchedExceptions[$exception['label']])) {
        $violations[] = "STALE EXCEPTION: {$exception['label']} no longer matches any live advisory. Remove it so the policy cannot silently accumulate blanket approvals.";
    }
}

echo "Composer audit policy: {$findingCount} live advisor" . ($findingCount === 1 ? 'y' : 'ies') . " "
    . "(critical: {$severityCounts['critical']}, high: {$severityCounts['high']}, medium: {$severityCounts['medium']}, low: {$severityCounts['low']}, unknown: {$severityCounts['unknown']}).\n";
echo 'Documented exceptions applied: ' . count($matchedExceptions) . " of " . count($validExceptions) . ".\n";

if ($violations !== []) {
    foreach ($violations as $violation) {
        fwrite(STDERR, "::error::{$violation}\n");
    }

    fwrite(STDERR, '::error::Composer security gate failed with ' . count($violations) . " violation(s).\n");
    exit(1);
}

echo "Composer security gate passed: every live advisory is covered by an unexpired, owner-assigned risk acceptance.\n";
exit(0);
