<?php

/**
 * Release Archive Security & Resource-Limit Validator.
 *
 * Reads the .tar.gz stream directly via gzopen()/gzread() (USTAR + GNU/PAX
 * longname support) without relying on fragile human-readable `tar -tvzf` regex
 * parsing. Fails closed on:
 *   - Archive size > 150 MB
 *   - Entry count > 50,000
 *   - Individual uncompressed file > 25 MB
 *   - Total uncompressed size > 500 MB or compression ratio > 100:1 (> 1 MB)
 *   - Symlinks, hard links, FIFOs, device nodes, or unknown entry types
 *   - Control characters / newlines / NUL bytes in filenames
 *   - Absolute paths (/...) or Windows drive paths (C:\...)
 *   - Path traversal (.. segments with / or \)
 *   - Duplicate normalized paths
 *   - Unexpected top-level paths outside the production allowlist
 *   - Forbidden files (.env, .git, node_modules, tests, storage/logs, *.sql)
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

const MAX_ARCHIVE_BYTES = 157286400;       // 150 MB
const MAX_ENTRIES = 50000;
const MAX_SINGLE_FILE_BYTES = 26214400;  // 25 MB
const MAX_TOTAL_UNCOMPRESSED = 524288000; // 500 MB
const MAX_COMPRESSION_RATIO = 100.0;

$archiveBytes = (int) filesize($archivePath);
if ($archiveBytes <= 0 || $archiveBytes > MAX_ARCHIVE_BYTES) {
    fwrite(STDERR, "::error::Release archive size ({$archiveBytes} bytes) is invalid or exceeds limit (" . MAX_ARCHIVE_BYTES . " bytes).\n");
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

$gz = @gzopen($archivePath, 'rb');
if ($gz === false) {
    fwrite(STDERR, "::error::Unable to open gzip archive: {$archivePath}\n");
    exit(1);
}

function readExact($gz, int $bytes): string
{
    $buf = '';
    while (strlen($buf) < $bytes && !gzeof($gz)) {
        $chunk = gzread($gz, $bytes - strlen($buf));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $buf .= $chunk;
    }
    return $buf;
}

function skipBytes($gz, int $bytes): void
{
    $remaining = $bytes;
    while ($remaining > 0 && !gzeof($gz)) {
        $chunk = gzread($gz, min(65536, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $remaining -= strlen($chunk);
    }
}

$violations = [];
$seenPaths = [];
$entryCount = 0;
$totalUncompressed = 0;
$pendingLongName = null;
$pendingPaxPath = null;

while (!gzeof($gz)) {
    $header = readExact($gz, 512);
    if ($header === '' || strlen($header) < 512) {
        break;
    }

    // Two consecutive zero blocks mark end-of-archive
    if ($header === str_repeat("\0", 512)) {
        break;
    }

    $nameField = rtrim(substr($header, 0, 100), "\0");
    $prefixField = rtrim(substr($header, 345, 155), "\0");
    $sizeOctal = trim(substr($header, 124, 12), "\0 ");
    $size = $sizeOctal !== '' ? (int) octdec($sizeOctal) : 0;
    $typeFlag = $header[156];

    $paddedSize = (int) (ceil($size / 512) * 512);

    // Handle GNU LongName ('L') or PAX extended header ('x' / 'g')
    if ($typeFlag === 'L') {
        $data = $size > 0 ? readExact($gz, $size) : '';
        if ($paddedSize > $size) {
            skipBytes($gz, $paddedSize - $size);
        }
        $pendingLongName = rtrim($data, "\0");
        continue;
    }

    if ($typeFlag === 'x' || $typeFlag === 'g') {
        $data = $size > 0 ? readExact($gz, $size) : '';
        if ($paddedSize > $size) {
            skipBytes($gz, $paddedSize - $size);
        }
        if ($typeFlag === 'x' && preg_match('/\d+ path=([^\n]+)\n/', $data, $pm)) {
            $pendingPaxPath = $pm[1];
        }
        continue;
    }

    if ($paddedSize > 0) {
        skipBytes($gz, $paddedSize);
    }

    $entryPath = $pendingPaxPath ?? $pendingLongName ?? ($prefixField !== '' ? $prefixField . '/' . $nameField : $nameField);
    $pendingLongName = null;
    $pendingPaxPath = null;

    $entryCount++;
    if ($entryCount > MAX_ENTRIES) {
        $violations[] = "Archive exceeds maximum entry count (" . MAX_ENTRIES . ").";
        break;
    }

    // Only regular files ('0' or "\0") and directories ('5') are permitted
    if (!in_array($typeFlag, ['0', "\0", '5'], true)) {
        $violations[] = "Unsafe archive entry type '{$typeFlag}' rejected for '{$entryPath}'.";
        continue;
    }

    if ($size < 0 || $size > MAX_SINGLE_FILE_BYTES) {
        $violations[] = "Oversized entry ({$size} bytes > " . MAX_SINGLE_FILE_BYTES . ") rejected: {$entryPath}";
        continue;
    }

    $totalUncompressed += $size;
    if ($totalUncompressed > MAX_TOTAL_UNCOMPRESSED) {
        $violations[] = "Total uncompressed size exceeds limit (" . MAX_TOTAL_UNCOMPRESSED . " bytes).";
        break;
    }

    if (preg_match('/[\r\n\0]/', $entryPath)) {
        $violations[] = "Control character or newline in filename rejected.";
        continue;
    }

    // Strip leading './'
    $clean = $entryPath;
    if (strpos($clean, './') === 0) {
        $clean = substr($clean, 2);
    }
    if ($clean === '' || $clean === '.') {
        continue;
    }

    if ($clean[0] === '/' || $clean[0] === '\\' || preg_match('/^[A-Za-z]:[\/\\\\]/', $clean)) {
        $violations[] = "Absolute or drive path rejected: {$entryPath}";
        continue;
    }

    $normalized = str_replace('\\', '/', rtrim($clean, '/'));
    $segments = explode('/', $normalized);
    if (in_array('..', $segments, true)) {
        $violations[] = "Path traversal rejected: {$entryPath}";
        continue;
    }

    if (isset($seenPaths[$normalized])) {
        $violations[] = "Duplicate normalized archive path rejected: {$normalized}";
        continue;
    }
    $seenPaths[$normalized] = true;

    $top = $segments[0];
    if (!in_array($top, $allowedTopLevel, true)) {
        $violations[] = "Unexpected top-level path '{$top}' rejected: {$entryPath}";
        continue;
    }

    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match($pattern, $normalized)) {
            $violations[] = "Forbidden file rejected: {$entryPath}";
            break;
        }
    }
}

gzclose($gz);

if ($entryCount === 0) {
    $violations[] = 'Archive is empty or unreadable.';
}

if ($totalUncompressed > 1048576 && ($totalUncompressed / max(1, $archiveBytes)) > MAX_COMPRESSION_RATIO) {
    $violations[] = sprintf(
        'Suspicious compression ratio (%.1f:1 > %.1f:1) rejected.',
        $totalUncompressed / max(1, $archiveBytes),
        MAX_COMPRESSION_RATIO
    );
}

if (!empty($violations)) {
    fwrite(STDERR, "::error::Release archive security validation failed with " . count($violations) . " violation(s):\n");
    foreach (array_slice($violations, 0, 20) as $v) {
        fwrite(STDERR, " - {$v}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Release archive security check passed ({$entryCount} entries, {$totalUncompressed} uncompressed bytes).\n");
exit(0);
