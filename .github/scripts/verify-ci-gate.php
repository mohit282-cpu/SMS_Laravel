<?php

/**
 * Strict CI Integration Gate Verifier for CD.
 *
 * Links Repository -> Workflow (ci.yml) -> Workflow Run ID -> head_sha ->
 * check_suite_id -> "CI Integration Gate" Check Run, with full pagination
 * and fail-closed rejection of missing, duplicate, incomplete, or mismatched runs.
 *
 * Usage:
 *   php .github/scripts/verify-ci-gate.php --repo=owner/repo --sha=<40hex> [--workflow=ci.yml] [--gate="CI Integration Gate"] [--fixture=path.json]
 */

$opts = getopt('', ['repo:', 'sha:', 'workflow::', 'gate::', 'fixture::']);
$repo = trim((string) ($opts['repo'] ?? ''));
$sha = trim((string) ($opts['sha'] ?? ''));
$workflow = trim((string) ($opts['workflow'] ?? 'ci.yml'));
$gateName = trim((string) ($opts['gate'] ?? 'CI Integration Gate'));
$fixtureFile = $opts['fixture'] ?? null;

function ciGateFail(string $msg): void
{
    fwrite(STDERR, "::error::{$msg}\n");
    exit(1);
}

if ($repo === '' || !preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo)) {
    ciGateFail("Invalid repository identifier '{$repo}'.");
}

if (!preg_match('/^[0-9a-f]{40}$/i', $sha)) {
    ciGateFail("Invalid commit SHA '{$sha}'.");
}

function ghPaginatedGet(string $endpoint): array
{
    $token = getenv('GH_TOKEN') ?: getenv('GITHUB_TOKEN') ?: '';
    if ($token === '') {
        ciGateFail('GH_TOKEN is required to verify CI status via GitHub API.');
    }

    $allPages = [];
    $page = 1;

    while (true) {
        $sep = strpos($endpoint, '?') !== false ? '&' : '?';
        $url = "https://api.github.com{$endpoint}{$sep}per_page=100&page={$page}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'User-Agent: SMS-Laravel-CD-Gate',
                'X-GitHub-Api-Version: 2022-11-28',
            ],
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            ciGateFail("GitHub API error at {$url} (HTTP {$httpCode}): {$err}");
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            ciGateFail("Malformed JSON from GitHub API at {$url}.");
        }

        $items = $decoded['workflow_runs'] ?? $decoded['check_runs'] ?? null;
        if (!is_array($items)) {
            ciGateFail("Unexpected API payload structure at {$url}.");
        }

        foreach ($items as $item) {
            $allPages[] = $item;
        }

        if (count($items) < 100) {
            break;
        }
        $page++;
        if ($page > 20) {
            ciGateFail("Exceeded pagination safety cap (20 pages) at {$endpoint}.");
        }
    }

    return $allPages;
}

if (is_string($fixtureFile) && $fixtureFile !== '') {
    $fixture = json_decode((string) file_get_contents($fixtureFile), true);
    if (!is_array($fixture)) {
        ciGateFail("Invalid fixture JSON: {$fixtureFile}");
    }
    if (!empty($fixture['api_error'])) {
        ciGateFail('Simulated GitHub API error.');
    }
    $workflowRuns = $fixture['workflow_runs'] ?? [];
    $checkRunsBySuite = $fixture['check_runs_by_suite'] ?? [];
} else {
    $workflowRuns = ghPaginatedGet("/repos/{$repo}/actions/workflows/" . rawurlencode($workflow) . "/runs?head_sha={$sha}");
    $checkRunsBySuite = null;
}

$matchingRuns = array_values(array_filter($workflowRuns, function ($run) use ($sha, $repo, $workflow) {
    if (!is_array($run)) {
        return false;
    }
    if (strtolower((string) ($run['head_sha'] ?? '')) !== strtolower($sha)) {
        return false;
    }
    if (isset($run['repository']['full_name']) && strcasecmp($run['repository']['full_name'], $repo) !== 0) {
        return false;
    }
    if (isset($run['path']) && substr((string) $run['path'], -strlen($workflow)) !== $workflow) {
        return false;
    }
    return true;
}));

if (empty($matchingRuns)) {
    ciGateFail("No '{$workflow}' workflow run found for {$repo}@{$sha}.");
}

// Reject any incomplete or non-success run among the latest/active runs unless clearly superseded,
// and require the latest run (highest run_number / id) to be completed + success.
usort($matchingRuns, fn ($a, $b) => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));
$latestRun = $matchingRuns[0];

if (($latestRun['status'] ?? '') !== 'completed' || ($latestRun['conclusion'] ?? '') !== 'success') {
    ciGateFail("Latest '{$workflow}' run #{$latestRun['id']} for {$sha} is not completed+success (status=" . ($latestRun['status'] ?? 'unknown') . ", conclusion=" . ($latestRun['conclusion'] ?? 'unknown') . ").");
}

$checkSuiteId = $latestRun['check_suite_id'] ?? null;
if (!$checkSuiteId) {
    ciGateFail("Workflow run #{$latestRun['id']} is missing check_suite_id.");
}

if (is_array($checkRunsBySuite)) {
    $checkRuns = $checkRunsBySuite[(string) $checkSuiteId] ?? [];
} else {
    $checkRuns = ghPaginatedGet("/repos/{$repo}/check-suites/{$checkSuiteId}/check-runs");
}

$gateRuns = array_values(array_filter($checkRuns, function ($cr) use ($gateName, $checkSuiteId) {
    if (!is_array($cr) || ($cr['name'] ?? '') !== $gateName) {
        return false;
    }
    if (isset($cr['check_suite']['id']) && (int) $cr['check_suite']['id'] !== (int) $checkSuiteId) {
        return false;
    }
    return true;
}));

if (count($gateRuns) === 0) {
    ciGateFail("Check suite #{$checkSuiteId} (run #{$latestRun['id']}) does not contain '{$gateName}'.");
}
if (count($gateRuns) > 1) {
    ciGateFail("Ambiguous duplicate '{$gateName}' check runs (" . count($gateRuns) . ") in check suite #{$checkSuiteId}.");
}

$gate = $gateRuns[0];
if (($gate['status'] ?? '') !== 'completed' || ($gate['conclusion'] ?? '') !== 'success') {
    ciGateFail("'{$gateName}' in check suite #{$checkSuiteId} failed/incomplete (status=" . ($gate['status'] ?? 'unknown') . ", conclusion=" . ($gate['conclusion'] ?? 'unknown') . ").");
}

fwrite(STDOUT, "Verified: {$repo}@{$sha} passed {$workflow} run #{$latestRun['id']} (suite #{$checkSuiteId}) and '{$gateName}'.\n");
exit(0);

