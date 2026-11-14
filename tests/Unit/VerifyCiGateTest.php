<?php

namespace Tests\Unit;

use Tests\TestCase;

class VerifyCiGateTest extends TestCase
{
    private function runVerifierWithRaw(string $rawContent, string $sha, string $repo = 'mohit282-cpu/SMS_Laravel', string $workflow = 'ci.yml'): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ci_gate_') . '.json';
        file_put_contents($tmp, $rawContent);
        $script = base_path('.github/scripts/verify-ci-gate.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
            . ' --repo=' . escapeshellarg($repo)
            . ' --sha=' . escapeshellarg($sha)
            . ' --workflow=' . escapeshellarg($workflow)
            . ' --fixture=' . escapeshellarg($tmp) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        @unlink($tmp);

        return [$code, implode("\n", $output)];
    }

    private function runVerifier(array $fixture, string $sha, string $repo = 'mohit282-cpu/SMS_Laravel', string $workflow = 'ci.yml'): array
    {
        return $this->runVerifierWithRaw(json_encode($fixture), $sha, $repo, $workflow);
    }

    public function test_correct_repository_and_sha_passes()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => [
                '555' => [[
                    'id' => 9001,
                    'name' => 'CI Integration Gate',
                    'status' => 'completed',
                    'conclusion' => 'success',
                    'check_suite' => ['id' => 555],
                ]],
            ],
        ], $sha);

        $this->assertEquals(0, $code, $out);
        $this->assertStringContainsString('Verified: mohit282-cpu/SMS_Laravel@', $out);
    }

    public function test_rejects_wrong_repository()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'attacker/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_missing_repository_identity()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                // repository is omitted
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_wrong_workflow()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                'path' => '.github/workflows/other.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_missing_workflow_identity()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                // path is omitted
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_wrong_sha()
    {
        $sha = str_repeat('a', 40);
        $wrongSha = str_repeat('b', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1001,
                'head_sha' => $wrongSha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_missing_workflow_run()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [],
            'check_runs_by_suite' => [],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('No \'ci.yml\' workflow run found', $out);
    }

    public function test_rejects_failed_workflow()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1002,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'failure',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('not completed+success', $out);
    }

    public function test_rejects_in_progress_workflow()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1003,
                'head_sha' => $sha,
                'status' => 'in_progress',
                'conclusion' => null,
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('not completed+success', $out);
    }

    public function test_rejects_cancelled_workflow()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1004,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'cancelled',
                'check_suite_id' => 555,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => ['555' => []],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('not completed+success', $out);
    }

    public function test_rejects_missing_check_suite_id()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1005,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                // check_suite_id omitted
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => [],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('missing check_suite_id', $out);
    }

    public function test_rejects_missing_integration_gate()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1006,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 777,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => [
                '777' => [[
                    'id' => 1,
                    'name' => 'Some Other Check',
                    'status' => 'completed',
                    'conclusion' => 'success',
                    'check_suite' => ['id' => 777],
                ]],
            ],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('does not contain \'CI Integration Gate\'', $out);
    }

    public function test_rejects_duplicate_integration_gate()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1007,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 888,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => [
                '888' => [
                    [
                        'id' => 1,
                        'name' => 'CI Integration Gate',
                        'status' => 'completed',
                        'conclusion' => 'success',
                        'check_suite' => ['id' => 888],
                    ],
                    [
                        'id' => 2,
                        'name' => 'CI Integration Gate',
                        'status' => 'completed',
                        'conclusion' => 'success',
                        'check_suite' => ['id' => 888],
                    ],
                ],
            ],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('duplicate \'CI Integration Gate\'', $out);
    }

    public function test_rejects_failed_integration_gate()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'workflow_runs' => [[
                'id' => 1008,
                'head_sha' => $sha,
                'status' => 'completed',
                'conclusion' => 'success',
                'check_suite_id' => 999,
                'path' => '.github/workflows/ci.yml',
                'repository' => ['full_name' => 'mohit282-cpu/SMS_Laravel'],
            ]],
            'check_runs_by_suite' => [
                '999' => [[
                    'id' => 1,
                    'name' => 'CI Integration Gate',
                    'status' => 'completed',
                    'conclusion' => 'failure',
                    'check_suite' => ['id' => 999],
                ]],
            ],
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('failed/incomplete', $out);
    }

    public function test_rejects_api_error_response()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifier([
            'api_error' => true,
        ], $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('Simulated GitHub API error', $out);
    }

    public function test_rejects_malformed_json_response()
    {
        $sha = str_repeat('a', 40);
        [$code, $out] = $this->runVerifierWithRaw('{malformed_json: true, unclosed', $sha);

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('Invalid fixture JSON', $out);
    }

    public function test_rejects_when_live_api_missing_auth_token()
    {
        $sha = str_repeat('a', 40);
        $script = base_path('.github/scripts/verify-ci-gate.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
            . ' --repo=mohit282-cpu/SMS_Laravel'
            . ' --sha=' . escapeshellarg($sha) . ' 2>&1';
        $output = [];
        $code = 0;
        // Run with cleared GH_TOKEN / GITHUB_TOKEN
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes, null, ['GH_TOKEN' => '', 'GITHUB_TOKEN' => '']);
        $out = '';
        if (is_resource($process)) {
            $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
        }

        $this->assertNotEquals(0, $code);
        $this->assertStringContainsString('GH_TOKEN is required', $out);
    }
}
