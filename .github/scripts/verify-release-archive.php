<?php

/**
 * Release Archive Security & Resource-Limit Validator.
 *
 * Reads the .tar.gz stream directly via gzopen()/gzread() (USTAR + GNU/PAX
 * metadata support) with strict cryptographic, structural, and path checks.
 *
 * Fails closed on:
 *   - Gzip header corruption (missing 1F 8B magic header)
 *   - Corrupted TAR header checksums or malformed octal numbers
 *   - Truncated 512-byte headers or truncated file payloads
 *   - Archive size > 150 MB
 *   - Entry count > 50,000
 *   - Individual uncompressed file > 25 MB
 *   - Total uncompressed size > 500 MB or compression ratio > 100:1 (> 1 MB)
 *   - Symlinks, hard links, FIFOs, device nodes, or unknown entry types
 *   - Malformed GNU LongName or PAX extended headers
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

// 1. Verify Gzip Magic Bytes (0x1F, 0x8B)
$magicCheck = @fopen($archivePath, 'rb');
if ($magicCheck === false) {
    fwrite(STDERR, "::error::Unable to read archive file: {$archivePath}\n");
    exit(1);
}
$magic = fread($magicCheck, 2);
fclose($magicCheck);
if ($magic !== "\x1f\x8b") {
    fwrite(STDERR, "::error::Release archive is not a valid gzip file (missing gzip 1F 8B magic header).\n");
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

function readExact($gz, int $bytes): array
{
    $buf = '';
    while (strlen($buf) < $bytes && !gzeof($gz)) {
        $chunk = gzread($gz, $bytes - strlen($buf));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $buf .= $chunk;
    }
    return [strlen($buf) === $bytes, $buf];
}

function skipBytesExact($gz, int $bytes): bool
{
    $remaining = $bytes;
    while ($remaining > 0 && !gzeof($gz)) {
        $chunk = gzread($gz, min(65536, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $remaining -= strlen($chunk);
    }
    return $remaining === 0;
}

function parsePaxRecords(string $data): ?array
{
    $records = [];
    $len = strlen($data);
    $offset = 0;
    while ($offset < $len) {
        $spacePos = strpos($data, ' ', $offset);
        if ($spacePos === false) {
            return null;
        }
        $lenStr = substr($data, $offset, $spacePos - $offset);
        if (!preg_match('/^[0-9]+$/', $lenStr)) {
            return null;
        }
        $recordLen = (int) $lenStr;
        if ($recordLen < 5 || $offset + $recordLen > $len) {
            return null;
        }
        $record = substr($data, $offset, $recordLen);
        if ($record[$recordLen - 1] !== "\n") {
            return null;
        }
        $content = substr($record, strlen($lenStr) + 1, -1);
        $eqPos = strpos($content, '=');
        if ($eqPos === false) {
            return null;
        }
        $key = substr($content, 0, $eqPos);
        $val = substr($content, $eqPos + 1);
        $records[$key] = $val;
        $offset += $recordLen;
    }
    return $records;
}

$violations = [];
$seenPaths = [];
$entryCount = 0;
$totalUncompressed = 0;
$pendingLongName = null;
$pendingPaxPath = null;

while (!gzeof($gz)) {
    [$has512, $header] = readExact($gz, 512);
    if ($header === '') {
        break;
    }
    if (!$has512) {
        $violations[] = "Truncated TAR header: expected 512 bytes, received " . strlen($header) . " bytes.";
        break;
    }

    // Two consecutive zero blocks mark end-of-archive
    if ($header === str_repeat("\0", 512)) {
        [$hasSecondZero, $secondBlock] = readExact($gz, 512);
        if ($hasSecondZero && $secondBlock !== str_repeat("\0", 512)) {
            $violations[] = "Malformed archive termination: expected zero block after end-of-archive marker.";
        }
        break;
    }

    $nameField = rtrim(substr($header, 0, 100), "\0");
    $prefixField = rtrim(substr($header, 345, 155), "\0");
    $sizeOctal = trim(substr($header, 124, 12), "\0 ");
    $typeFlag = $header[156];

    // Verify TAR header checksum
    $chkRaw = substr($header, 148, 8);
    $chkOctal = trim($chkRaw, "\0 ");
    if (!preg_match('/^[0-7]+$/', $chkOctal)) {
        $violations[] = "Malformed or non-octal checksum in TAR header for '{$nameField}'.";
    } else {
        $storedChk = (int) octdec($chkOctal);
        $dummyHeader = substr($header, 0, 148) . str_repeat(' ', 8) . substr($header, 156, 356);
        $unsignedSum = 0;
        $signedSum = 0;
        for ($b = 0; $b < 512; $b++) {
            $ord = ord($dummyHeader[$b]);
            $unsignedSum += $ord;
            $signedSum += ($ord > 127 ? $ord - 256 : $ord);
        }
        if ($storedChk !== $unsignedSum && $storedChk !== $signedSum) {
            $violations[] = "Corrupted TAR header checksum for '{$nameField}' (stored {$storedChk}, expected {$unsignedSum}).";
        }
    }

    if ($sizeOctal !== '' && !preg_match('/^[0-7]+$/', $sizeOctal)) {
        $violations[] = "Malformed or non-octal size field in TAR header for '{$nameField}'.";
        $size = -1;
    } else {
        $size = $sizeOctal !== '' ? (int) octdec($sizeOctal) : 0;
    }

    $paddedSize = $size > 0 ? (int) (ceil($size / 512) * 512) : 0;

    // Handle GNU LongName ('L')
    if ($typeFlag === 'L') {
        if ($size <= 0 || $size > 4096) {
            $violations[] = "Invalid GNU LongName record length ({$size} bytes).";
            continue;
        }
        [$ok, $data] = readExact($gz, $size);
        if (!$ok) {
            $violations[] = "Truncated GNU LongName payload.";
            break;
        }
        if ($paddedSize > $size && !skipBytesExact($gz, $paddedSize - $size)) {
            $violations[] = "Truncated GNU LongName padding.";
            break;
        }
        $pendingLongName = rtrim($data, "\0");
        if ($pendingLongName === '' || preg_match('/[\r\n\0]/', $pendingLongName)) {
            $violations[] = "Invalid GNU LongName content.";
            $pendingLongName = null;
        }
        continue;
    }

    // Handle PAX Extended Header ('x' or 'g')
    if ($typeFlag === 'x' || $typeFlag === 'g') {
        if ($size <= 0 || $size > 65536) {
            $violations[] = "Invalid PAX extended header length ({$size} bytes).";
            continue;
        }
        [$ok, $data] = readExact($gz, $size);
        if (!$ok) {
            $violations[] = "Truncated PAX extended header payload.";
            break;
        }
        if ($paddedSize > $size && !skipBytesExact($gz, $paddedSize - $size)) {
            $violations[] = "Truncated PAX header padding.";
            break;
        }
        $paxRecords = parsePaxRecords($data);
        if ($paxRecords === null) {
            $violations[] = "Malformed PAX extended header structure.";
        } elseif ($typeFlag === 'x' && isset($paxRecords['path'])) {
            $pendingPaxPath = $paxRecords['path'];
        }
        continue;
    }

    if ($paddedSize > 0) {
        if (!skipBytesExact($gz, $paddedSize)) {
            $violations[] = "Truncated file payload for '{$nameField}' (expected {$paddedSize} padded bytes).";
            break;
        }
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

if ($entryCount === 0 && empty($violations)) {
    $violations[] = 'Archive is empty or contains no valid files.';
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
