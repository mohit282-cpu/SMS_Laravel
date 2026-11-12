<?php

namespace Tests\Unit;

use Tests\TestCase;

class VerifyCiGateTest extends TestCase
{
    private function runVerifier(array $fixture, string $sha): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ci_gate_') . '.json';
        file_put_contents($tmp, json_encode($fixture));
        $script = base_path('.github/scripts/verify-ci-gate.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
            . ' --repo=mohit282-cpu/SMS_Laravel'
            . ' --sha=' . escapeshellarg($sha)
            . ' --fixture=' . escapeshellarg($tmp) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        @unlink($tmp);

        return [$code, implode("\n", $output)];
    }

    public function test_passes_when_exact_workflow_run_and_integration_gate_succeed()
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
    }

    public function test_rejects_missing_wrong_sha_failed_duplicate_incomplete_and_api_error()
    {
        $sha = str_repeat('b', 40);
        $wrongSha = str_repeat('c', 40);

        $badCases = [
            'missing_run' => ['workflow_runs' => [], 'check_runs_by_suite' => []],
            'wrong_sha' => [
                'workflow_runs' => [[
                    'id' => 1, 'head_sha' => $wrongSha, 'status' => 'completed', 'conclusion' => 'success', 'check_suite_id' => 10,
                ]],
                'check_runs_by_suite' => ['10' => [['name' => 'CI Integration Gate', 'status' => 'completed', 'conclusion' => 'success']]],
            ],
            'failed_gate' => [
                'workflow_runs' => [[
                    'id' => 2, 'head_sha' => $sha, 'status' => 'completed', 'conclusion' => 'success', 'check_suite_id' => 20,
                ]],
                'check_runs_by_suite' => ['20' => [['name' => 'CI Integration Gate', 'status' => 'completed', 'conclusion' => 'failure']]],
            ],
            'duplicate_gate' => [
                'workflow_runs' => [[
                    'id' => 3, 'head_sha' => $sha, 'status' => 'completed', 'conclusion' => 'success', 'check_suite_id' => 30,
                ]],
                'check_runs_by_suite' => ['30' => [
                    ['name' => 'CI Integration Gate', 'status' => 'completed', 'conclusion' => 'success'],
                    ['name' => 'CI Integration Gate', 'status' => 'completed', 'conclusion' => 'success'],
                ]],
            ],
            'incomplete_gate' => [
                'workflow_runs' => [[
                    'id' => 4, 'head_sha' => $sha, 'status' => 'completed', 'conclusion' => 'success', 'check_suite_id' => 40,
                ]],
                'check_runs_by_suite' => ['40' => [['name' => 'CI Integration Gate', 'status' => 'in_progress', 'conclusion' => null]]],
            ],
            'api_error' => ['api_error' => true],
        ];

        foreach ($badCases as $label => $fixture) {
            [$code, $out] = $this->runVerifier($fixture, $sha);
            $this->assertNotEquals(0, $code, "Expected failure for '{$label}', got exit 0: {$out}");
        }
    }
}

