<?php

/**
 * Token-based Expand-and-Contract Migration Safety Scanner.
 *
 * Uses PHP's native token_get_all() to inspect database/migrations/*.php (or a
 * single path passed via --file=...) without being fooled by comments, strings,
 * anonymous classes, or unusual formatting.
 *
 * Checks for:
 *   - Destructive Schema operations inside up(): dropColumn, drop, dropIfExists, renameColumn, rename, truncate
 *   - Static Schema operations inside up(): Schema::drop, Schema::dropIfExists, Schema::rename
 *   - Raw SQL destructive statements inside up(): DB::statement/unprepared with DROP, TRUNCATE, DELETE
 *   - Missing down() rollback methods
 *   - Honors @approved-destructive: <reason> annotations for verified schema refactors
 *
 * Usage:
 *   php .github/scripts/check-migration-safety.php [--strict] [--file=path/to/migration.php]
 */

$options = getopt('', ['strict', 'file::', 'allow-approved']);
$strict = array_key_exists('strict', $options);
$singleFile = $options['file'] ?? null;

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

function scanMigrationFile(string $path, array $destructiveOps): array
{
    $code = (string) file_get_contents($path);

    // Check for approved-destructive annotation
    $approvedReason = null;
    if (preg_match('/@approved-destructive:\s*([^\r\n*]+)/i', $code, $m)) {
        $approvedReason = trim($m[1]);
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
        'approvedReason' => $approvedReason,
        'findings' => $findings,
    ];
}

foreach ($files as $file) {
    $name = basename($file);
    $res = scanMigrationFile($file, $destructiveOps);

    if ($res['hasUp'] && !$res['hasDown']) {
        $warnings[] = "{$name}: missing down() rollback method.";
    }

    foreach ($res['findings'] as $f) {
        if ($res['approvedReason'] !== null) {
            $warnings[] = "{$name} (line {$f['line']}): up() calls '{$f['op']}' [APPROVED: {$res['approvedReason']}].";
            continue;
        }

        $msg = "{$name} (line {$f['line']}): up() calls '{$f['op']}', which may break backward compatibility or cause data loss.";
        if ($strict) {
            $errors[] = $msg;
        } else {
            $warnings[] = $msg;
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
