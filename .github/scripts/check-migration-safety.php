<?php

/**
 * Token-based Expand-and-Contract Migration Safety Scanner.
 *
 * Uses PHP's native token_get_all() to inspect database/migrations/*.php (or a
 * single path passed via --file=...) without being fooled by comments, strings,
 * anonymous classes, or unusual formatting.
 *
 * Usage:
 *   php .github/scripts/check-migration-safety.php [--strict] [--file=path/to/migration.php]
 */

$options = getopt('', ['strict', 'file::']);
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

$destructiveOps = ['dropColumn', 'dropIfExists', 'drop', 'renameColumn', 'truncate'];
$warnings = [];
$errors = [];

function scanMigrationFile(string $path, array $destructiveOps): array
{
    $code = (string) file_get_contents($path);
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

        // Ignore comments, docblocks, whitespace, and literal strings completely
        if (in_array($id, [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_CONSTANT_ENCAPSED_STRING], true)) {
            continue;
        }

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
                    // Confirm followed by '('
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

        // Also detect Schema::drop(...) / Schema::dropIfExists(...) inside up()
        if ($inUp && $id === T_DOUBLE_COLON) {
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_array($next) && $next[0] === T_WHITESPACE) {
                    continue;
                }
                if (is_array($next) && $next[0] === T_STRING && in_array($next[1], ['drop', 'dropIfExists'], true)) {
                    $findings[] = [
                        'line' => $line,
                        'op' => 'Schema::' . $next[1],
                    ];
                }
                break;
            }
        }
    }

    return [
        'hasUp' => $hasUp,
        'hasDown' => $hasDown,
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
