<?php

/**
 * Release Archive Security Validator.
 *
 * Inspects a .tar.gz release archive BEFORE extraction and fails closed if any
 * entry contains:
 *   - absolute paths (/...)
 *   - path traversal (../ or ..\)
 *   - symlinks, hard links, FIFOs, or device nodes
 *   - forbidden files (.env, .git, node_modules, tests, storage/logs, *.sql)
 *   - unexpected top-level paths outside the production allowlist
 *
 * Usage:
 *   php .github/scripts/verify-release-archive.php /path/to/release.tar.gz
 */

if ($argc < 2 || trim((string) $argv[1]) === '') {
    fwrite(STDERR, "::error::Usage: php verify-release-archive.php <archive.tar.gz>\n");
    exit(1);
}

$archivePath = $argv[1];
if (!is_file($archivePath) || !is_readable($archivePath)) {
    fwrite(STDERR, "::error::Release archive not found or unreadable: {$archivePath}\n");
    exit(1);
}

$allowedTopLevel = [
    '.release_sha',
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'vendor',
    'artisan',
    'composer.json',
    'composer.lock',
];

$forbiddenPatterns = [
    '/(^|\/)\.env(\.|$)/i',
    '/(^|\/)\.git(\/|$)/i',
    '/(^|\/)node_modules(\/|$)/i',
    '/^tests(\/|$)/i',
    '/^storage\/logs(\/|$)/i',
    '/\.sql(\.gz)?$/i',
];

$cmd = 'tar -tvzf ' . escapeshellarg($archivePath) . ' 2>&1';
$lines = [];
$exitCode = 0;
exec($cmd, $lines, $exitCode);

if ($exitCode !== 0 || empty($lines)) {
    fwrite(STDERR, "::error::Failed to list archive entries (exit {$exitCode}): " . implode("\n", array_slice($lines, 0, 10)) . "\n");
    exit(1);
}

$violations = [];
$entryCount = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $entryCount++;

    // First char in `tar -tvzf` output indicates entry type:
    // '-' = regular file, 'd' = directory, 'l' = symlink, 'h' = hardlink, 'c'/'b'/'p' = special
    $typeChar = $line[0];
    if (!in_array($typeChar, ['-', 'd'], true)) {
        $violations[] = "Unsafe archive entry type '{$typeChar}': {$line}";
        continue;
    }

    // Parse path after GNU (YYYY-MM-DD HH:MM) or BSD (Mon DD HH:MM|YYYY) timestamp
    if (!preg_match('/^(?:\S+\s+){2,6}(?:\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}|[A-Z][a-z]{2}\s+\d{1,2}\s+(?:\d{2}:\d{2}|\d{4}))\s+(.+)$/', $line, $m)) {
        $violations[] = "Unparsable tar entry: {$line}";
        continue;
    }

    $entryPath = trim($m[1]);
    // Strip leading './' if present
    if (strpos($entryPath, './') === 0) {
        $entryPath = substr($entryPath, 2);
    }

    if ($entryPath === '' || $entryPath === '.') {
        continue;
    }

    // Reject absolute paths (Unix or Windows drive)
    if ($entryPath[0] === '/' || $entryPath[0] === '\\' || preg_match('/^[A-Za-z]:[\/\\\\]/', $entryPath)) {
        $violations[] = "Absolute path rejected: {$entryPath}";
        continue;
    }

    // Reject path traversal
    $normalized = str_replace('\\', '/', $entryPath);
    $segments = explode('/', rtrim($normalized, '/'));
    if (in_array('..', $segments, true)) {
        $violations[] = "Path traversal rejected: {$entryPath}";
        continue;
    }

    // Reject unexpected top-level entries
    $top = $segments[0];
    if (!in_array($top, $allowedTopLevel, true)) {
        $violations[] = "Unexpected top-level path '{$top}' in entry: {$entryPath}";
        continue;
    }

    // Reject forbidden files
    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match($pattern, $normalized)) {
            $violations[] = "Forbidden file rejected: {$entryPath}";
            break;
        }
    }
}

if (!empty($violations)) {
    fwrite(STDERR, "::error::Release archive security validation failed with " . count($violations) . " violation(s):\n");
    foreach (array_slice($violations, 0, 20) as $v) {
        fwrite(STDERR, " - {$v}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Release archive security check passed ({$entryCount} entries verified).\n");
exit(0);
