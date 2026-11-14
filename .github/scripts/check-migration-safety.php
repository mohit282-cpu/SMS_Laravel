<?php

/**
 * Token-based Expand-and-Contract Migration Safety Scanner.
 *
 * Uses PHP's native token_get_all() to inspect database/migrations/*.php (or a
 * single path passed via --file=...) without being fooled by comments, strings,
 * anonymous classes, or unusual formatting.
 *
 * Fails closed by default on:
 *   - Destructive Schema operations inside up(): dropColumn, drop, dropIfExists, renameColumn, rename, truncate
 *   - Static Schema operations inside up(): Schema::drop, Schema::dropIfExists, Schema::rename
 *   - Raw SQL destructive statements inside up(): DB::statement/unprepared with DROP, TRUNCATE, DELETE
 *   - Missing down() rollback methods (warnings by default, errors in strict mode)
 *
 * Approvals require an unexpired, complete record in .github/security/migration-exceptions.json
 * or a fully-qualified JSON annotation:
 *   @approved-destructive: {"operation": "...", "justification": "...", "preservationStrategy": "...", "recoveryStrategy": "...", "owner": "...", "approvalDate": "YYYY-MM-DD", "expires": "YYYY-MM-DD"}
 * Generic, unverified annotations are strictly rejected.
 *
 * Usage:
 *   php .github/scripts/check-migration-safety.php [--file=path/to/migration.php] [--allow-warnings]
 */

$options = getopt('', ['strict', 'allow-warnings', 'file::']);
$warnOnly = array_key_exists('allow-warnings', $options);
$singleFile = $options['file'] ?? null;

$policyFile = dirname(__DIR__) . '/security/migration-exceptions.json';
$policyExceptions = [];
if (is_file($policyFile)) {
    $decodedPolicy = json_decode((string) file_get_contents($policyFile), true);
    if (is_array($decodedPolicy) && isset($decodedPolicy['exceptions']) && is_array($decodedPolicy['exceptions'])) {
        $policyExceptions = $decodedPolicy['exceptions'];
    }
}

$files = [];
if (is_string($singleFile) && $singleFile !== '') {
    if (!is_file($singleFile)) {
        fwrite(STDERR, "::error::Migration file not found: {$singleFile}\n");
        exit(1);
    }
    $files = [$singleFile];
} else {
    $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
    if (!is_dir($migrationsDir)) {
        fwrite(STDERR, "::error::Migrations directory not found: {$migrationsDir}\n");
        exit(1);
    }
    $files = glob($migrationsDir . '/*.php') ?: [];
    sort($files);
}

$destructiveOps = ['dropColumn', 'dropIfExists', 'drop', 'renameColumn', 'rename', 'truncate'];
$warnings = [];
$errors = [];

function validateApproval(array $approval, string $filename, string $operation): ?string
{
    $requiredKeys = [
        'operation' => 'Exact destructive operation',
        'justification' => 'Business justification',
        'preservationStrategy' => 'Data preservation strategy',
        'recoveryStrategy' => 'Rollback or recovery strategy',
        'owner' => 'Reviewer/owner',
        'approvalDate' => 'Approval date',
        'expires' => 'Expiration date',
    ];

    foreach ($requiredKeys as $k => $label) {
        if (empty($approval[$k]) || !is_string($approval[$k])) {
            return "Missing or invalid '{$k}' ({$label}) in approval.";
        }
    }

    if (strcasecmp(trim($approval['operation']), trim($operation)) !== 0) {
        return "Approval operation '{$approval['operation']}' does not match detected operation '{$operation}'.";
    }

    $today = date('Y-m-d');
    if ($approval['expires'] < $today) {
        return "Approval expired on {$approval['expires']}.";
    }

    return null;
}

function scanMigrationFile(string $path, array $destructiveOps, array $policyExceptions): array
{
    $code = (string) file_get_contents($path);
    $filename = basename($path);

    // Extract inline structured approval if present
    $inlineApproval = null;
    $inlineApprovalError = null;
    if (preg_match('/@approved-destructive:\s*(\{[^\r\n*]+\})/i', $code, $m)) {
        $json = json_decode($m[1], true);
        if (is_array($json)) {
            $inlineApproval = $json;
        } else {
            $inlineApprovalError = "Malformed JSON in @approved-destructive annotation.";
        }
    } elseif (preg_match('/@approved-destructive:\s*([^\r\n*]+)/i', $code, $m)) {
        $inlineApprovalError = "Generic @approved-destructive string rejected. Must be a complete structured JSON object with all required fields.";
    }

    $tokens = token_get_all($code);
    $count = count($tokens);

    $hasUp = false;
    $hasDown = false;
    $findings = [];

    $braceDepth = 0;
    $inUp = false;
    $upTargetDepth = -1;
    $waitingForMethodBody = null;

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];

        if (is_string($tok)) {
            if ($tok === '{') {
                $braceDepth++;
                if ($waitingForMethodBody === 'up') {
                    $inUp = true;
                    $upTargetDepth = $braceDepth;
                    $waitingForMethodBody = null;
                } elseif ($waitingForMethodBody === 'down') {
                    $waitingForMethodBody = null;
                }
            } elseif ($tok === '}') {
                if ($inUp && $braceDepth === $upTargetDepth) {
                    $inUp = false;
                    $upTargetDepth = -1;
                }
                $braceDepth = max(0, $braceDepth - 1);
            }
            continue;
        }

        [$id, $text, $line] = $tok;

        // Detect function up() / function down()
        if ($id === T_FUNCTION) {
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_array($next) && $next[0] === T_WHITESPACE) {
                    continue;
                }
                if (is_array($next) && $next[0] === T_STRING) {
                    $fnName = strtolower($next[1]);
                    if ($fnName === 'up') {
                        $hasUp = true;
                        $waitingForMethodBody = 'up';
                    } elseif ($fnName === 'down') {
                        $hasDown = true;
                        $waitingForMethodBody = 'down';
                    }
                }
                break;
            }
            continue;
        }

        // Inside up(), detect ->dropColumn( / ->dropIfExists( / ->drop( / ->renameColumn( / ->truncate(
        if ($inUp && $id === T_OBJECT_OPERATOR) {
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_array($next) && $next[0] === T_WHITESPACE) {
                    continue;
                }
                if (is_array($next) && $next[0] === T_STRING && in_array($next[1], $destructiveOps, true)) {
                    for ($k = $j + 1; $k < $count; $k++) {
                        $after = $tokens[$k];
                        if (is_array($after) && $after[0] === T_WHITESPACE) {
                            continue;
                        }
                        if ($after === '(') {
                            $findings[] = [
                                'line' => $line,
                                'op' => $next[1],
                            ];
                        }
                        break;
                    }
                }
                break;
            }
        }

        // Detect Schema::drop(...) / Schema::dropIfExists(...) / Schema::rename(...) inside up()
        if ($inUp && $id === T_DOUBLE_COLON) {
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_array($next) && $next[0] === T_WHITESPACE) {
                    continue;
                }
                if (is_array($next) && $next[0] === T_STRING && in_array($next[1], ['drop', 'dropIfExists', 'rename'], true)) {
                    $findings[] = [
                        'line' => $line,
                        'op' => 'Schema::' . $next[1],
                    ];
                }
                break;
            }
        }

        // Detect DB::statement("DROP ...") / DB::unprepared("DROP ...") inside up()
        if ($inUp && $id === T_DOUBLE_COLON) {
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_array($next) && $next[0] === T_WHITESPACE) {
                    continue;
                }
                if (is_array($next) && $next[0] === T_STRING && in_array($next[1], ['statement', 'unprepared'], true)) {
                    for ($k = $j + 1; $k < $count; $k++) {
                        $after = $tokens[$k];
                        if ($after === '(' || (is_array($after) && $after[0] === T_WHITESPACE)) {
                            continue;
                        }
                        if (is_array($after) && $after[0] === T_CONSTANT_ENCAPSED_STRING) {
                            $sql = strtoupper($after[1]);
                            if (preg_match('/\b(DROP\s+TABLE|DROP\s+COLUMN|TRUNCATE\s+TABLE|DELETE\s+FROM)\b/', $sql)) {
                                $findings[] = [
                                    'line' => $line,
                                    'op' => 'DB::' . $next[1] . ' (raw destructive SQL)',
                                ];
                            }
                        }
                        break;
                    }
                }
                break;
            }
        }
    }

    return [
        'hasUp' => $hasUp,
        'hasDown' => $hasDown,
        'inlineApproval' => $inlineApproval,
        'inlineApprovalError' => $inlineApprovalError,
        'findings' => $findings,
    ];
}

foreach ($files as $file) {
    $name = basename($file);
    $res = scanMigrationFile($file, $destructiveOps, $policyExceptions);

    if ($res['hasUp'] && !$res['hasDown']) {
        $msg = "{$name}: missing down() rollback method.";
        if ($warnOnly) {
            $warnings[] = $msg;
        } else {
            $errors[] = $msg;
        }
    }

    if (!empty($res['inlineApprovalError'])) {
        $errors[] = "{$name}: {$res['inlineApprovalError']}";
    }

    foreach ($res['findings'] as $f) {
        $op = $f['op'];

        // Check if covered by policy exceptions or inline approval
        $approved = null;
        if (!empty($res['inlineApproval'])) {
            $err = validateApproval($res['inlineApproval'], $name, $op);
            if ($err === null) {
                $approved = $res['inlineApproval'];
            } else {
                $errors[] = "{$name} (line {$f['line']}): invalid inline approval: {$err}";
            }
        }

        if ($approved === null) {
            foreach ($policyExceptions as $pex) {
                if (isset($pex['migration']) && (basename($pex['migration']) === $name || $pex['migration'] === $name)) {
                    $err = validateApproval($pex, $name, $op);
                    if ($err === null) {
                        $approved = $pex;
                        break;
                    }
                }
            }
        }

        if ($approved !== null) {
            $warnings[] = "{$name} (line {$f['line']}): up() calls '{$op}' [APPROVED by {$approved['owner']}: {$approved['justification']}].";
            continue;
        }

        $msg = "{$name} (line {$f['line']}): up() calls destructive operation '{$op}', which may cause data loss or break zero-downtime deployment.";
        if ($warnOnly) {
            $warnings[] = $msg;
        } else {
            $errors[] = $msg;
        }
    }
}

foreach ($warnings as $w) {
    fwrite(STDOUT, "::warning::{$w}\n");
}

if (!empty($errors)) {
    foreach ($errors as $e) {
        fwrite(STDERR, "::error::{$e}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Migration safety scan completed (" . count($files) . " migration file(s) inspected, " . count($warnings) . " warning(s)).\n");
exit(0);
