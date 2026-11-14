<?php

/**
 * Release Archive Security & Resource-Limit Validator.
 *
 * Reads the .tar.gz stream directly via gzopen()/gzread() (USTAR + GNU/PAX
 * metadata support) with strict cryptographic, structural, and path checks.
 *
 * Fails closed on:
 *   - Missing or invalid gzip magic header (0x1F, 0x8B)
 *   - Gzip footer CRC32 mismatch or ISIZE mismatch
 *   - Truncated gzip stream or premature EOF
 *   - Corrupted TAR header checksums (signed & unsigned)
 *   - Malformed or non-octal numeric fields (mode, uid, gid, size, mtime, chksum)
 *   - Truncated 512-byte headers, file payloads, or block padding
 *   - Missing or malformed end-of-archive markers (requires two 512-byte zero blocks)
 *   - Unexpected non-zero trailing content after end-of-archive
 *   - Malformed GNU LongName records
 *   - Malformed PAX records (invalid length, missing newline, invalid key=val)
 *   - Unsupported / dangerous PAX keys (linkpath, etc.)
 *   - Symlinks, hard links, FIFOs, device nodes, or unsupported TAR types
 *   - Absolute paths (/...), Windows drive paths (C:\...), or path traversal (..)
 *   - Control characters, newlines, or NUL bytes in filenames
 *   - Duplicate normalized archive paths
 *   - Files outside the production allowlist or matching forbidden patterns (.env, tests, etc.)
 *   - Resource limits (archive size, single file, total uncompressed, compression ratio)
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
if ($archiveBytes < 18 || $archiveBytes > MAX_ARCHIVE_BYTES) {
    fwrite(STDERR, "::error::Release archive size ({$archiveBytes} bytes) is invalid or exceeds limit (" . MAX_ARCHIVE_BYTES . " bytes).\n");
    exit(1);
}

// 1. Verify Gzip Magic Bytes (0x1F, 0x8B) & Footer
$fp = @fopen($archivePath, 'rb');
if ($fp === false) {
    fwrite(STDERR, "::error::Unable to read archive file: {$archivePath}\n");
    exit(1);
}
$magic = fread($fp, 2);
if ($magic !== "\x1f\x8b") {
    fclose($fp);
    fwrite(STDERR, "::error::Release archive is not a valid gzip file (missing gzip 1F 8B magic header).\n");
    exit(1);
}

// Read gzip trailer (last 8 bytes: CRC32, ISIZE)
fseek($fp, -8, SEEK_END);
$trailer = fread($fp, 8);
fclose($fp);

if (strlen($trailer) < 8) {
    fwrite(STDERR, "::error::Truncated gzip archive: missing 8-byte footer.\n");
    exit(1);
}
$unpackedTrailer = unpack('Vcrc/Visize', $trailer);
$expectedGzCrc = (int) $unpackedTrailer['crc'];
$expectedGzIsize = (int) $unpackedTrailer['isize'];

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

$crcContext = hash_init('crc32b');

function readExactTracked($gz, int $bytes, $crcContext): array
{
    $buf = '';
    while (strlen($buf) < $bytes && !gzeof($gz)) {
        $chunk = gzread($gz, $bytes - strlen($buf));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $buf .= $chunk;
    }
    if ($buf !== '') {
        hash_update($crcContext, $buf);
    }
    return [strlen($buf) === $bytes, $buf];
}

function skipBytesTracked($gz, int $bytes, $crcContext): bool
{
    $remaining = $bytes;
    while ($remaining > 0 && !gzeof($gz)) {
        $chunk = gzread($gz, min(65536, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        hash_update($crcContext, $chunk);
        $remaining -= strlen($chunk);
    }
    return $remaining === 0;
}

function parsePaxRecordsStrict(string $data, array &$violations): ?array
{
    $records = [];
    $len = strlen($data);
    $offset = 0;
    while ($offset < $len) {
        $spacePos = strpos($data, ' ', $offset);
        if ($spacePos === false) {
            $violations[] = "Malformed PAX extended header: missing space delimiter.";
            return null;
        }
        $lenStr = substr($data, $offset, $spacePos - $offset);
        if (!preg_match('/^[0-9]+$/', $lenStr)) {
            $violations[] = "Malformed PAX extended header: non-numeric length '{$lenStr}'.";
            return null;
        }
        $recordLen = (int) $lenStr;
        if ($recordLen < 5 || $offset + $recordLen > $len) {
            $violations[] = "Malformed PAX extended header: invalid record length {$recordLen}.";
            return null;
        }
        $record = substr($data, $offset, $recordLen);
        if ($record[$recordLen - 1] !== "\n") {
            $violations[] = "Malformed PAX extended header: record does not terminate with newline.";
            return null;
        }
        $content = substr($record, strlen($lenStr) + 1, -1);
        $eqPos = strpos($content, '=');
        if ($eqPos === false) {
            $violations[] = "Malformed PAX extended header: missing '=' in record.";
            return null;
        }
        $key = substr($content, 0, $eqPos);
        $val = substr($content, $eqPos + 1);

        if (in_array(strtolower($key), ['linkpath', 'schily.dev', 'schily.ino', 'schily.nlink'], true)) {
            $violations[] = "Unsupported dangerous PAX attribute '{$key}' rejected.";
            return null;
        }

        $records[$key] = $val;
        $offset += $recordLen;
    }
    return $records;
}

function validateOctalField(string $raw, string $field, string $name, array &$violations): ?int
{
    $trimmed = trim($raw, "\0 ");
    if ($trimmed === '') {
        return 0;
    }
    if (!preg_match('/^[0-7]+$/', $trimmed)) {
        $violations[] = "Malformed or non-octal {$field} field ('{$trimmed}') in TAR header for '{$name}'.";
        return null;
    }
    return (int) octdec($trimmed);
}

$violations = [];
$seenPaths = [];
$entryCount = 0;
$totalUncompressed = 0;
$pendingLongName = null;
$pendingPaxPath = null;
$hasEoaFirstBlock = false;
$hasEoaSecondBlock = false;

while (!gzeof($gz)) {
    [$has512, $header] = readExactTracked($gz, 512, $crcContext);
    if ($header === '') {
        break;
    }
    $totalUncompressed += strlen($header);

    if (!$has512) {
        $violations[] = "Truncated TAR header: expected 512 bytes, received " . strlen($header) . " bytes.";
        break;
    }

    // Two consecutive zero blocks mark end-of-archive
    if ($header === str_repeat("\0", 512)) {
        $hasEoaFirstBlock = true;
        [$hasSecondZero, $secondBlock] = readExactTracked($gz, 512, $crcContext);
        if ($secondBlock !== '') {
            $totalUncompressed += strlen($secondBlock);
        }
        if (!$hasSecondZero) {
            $violations[] = "Malformed archive termination: missing second 512-byte zero block (stream ended prematurely).";
            break;
        }
        if ($secondBlock !== str_repeat("\0", 512)) {
            $violations[] = "Malformed archive termination: expected second 512-byte zero block, found non-zero data.";
            break;
        }
        $hasEoaSecondBlock = true;

        // Verify remaining stream is all zero padding
        while (!gzeof($gz)) {
            [$hasMore, $trailingChunk] = readExactTracked($gz, 512, $crcContext);
            if ($trailingChunk === '') {
                break;
            }
            $totalUncompressed += strlen($trailingChunk);
            if ($trailingChunk !== str_repeat("\0", strlen($trailingChunk))) {
                $violations[] = "Unexpected non-zero trailing data found after end-of-archive marker.";
                break;
            }
        }
        break;
    }

    $nameField = rtrim(substr($header, 0, 100), "\0");
    $prefixField = rtrim(substr($header, 345, 155), "\0");
    $typeFlag = $header[156];

    // Validate numeric fields strictly
    validateOctalField(substr($header, 100, 8), 'mode', $nameField, $violations);
    validateOctalField(substr($header, 108, 8), 'uid', $nameField, $violations);
    validateOctalField(substr($header, 116, 8), 'gid', $nameField, $violations);
    $size = validateOctalField(substr($header, 124, 12), 'size', $nameField, $violations);
    validateOctalField(substr($header, 136, 12), 'mtime', $nameField, $violations);
    $storedChk = validateOctalField(substr($header, 148, 8), 'checksum', $nameField, $violations);

    // Verify TAR header checksum
    if ($storedChk !== null) {
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

    if ($size === null || $size < 0) {
        $size = 0;
    }

    $paddedSize = $size > 0 ? (int) (ceil($size / 512) * 512) : 0;

    // Handle GNU LongName ('L')
    if ($typeFlag === 'L') {
        if ($size <= 0 || $size > 4096) {
            $violations[] = "Invalid GNU LongName record length ({$size} bytes).";
            continue;
        }
        [$ok, $data] = readExactTracked($gz, $size, $crcContext);
        $totalUncompressed += strlen($data);
        if (!$ok) {
            $violations[] = "Truncated GNU LongName payload.";
            break;
        }
        if ($paddedSize > $size) {
            if (!skipBytesTracked($gz, $paddedSize - $size, $crcContext)) {
                $violations[] = "Truncated GNU LongName padding.";
                break;
            }
            $totalUncompressed += ($paddedSize - $size);
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
        [$ok, $data] = readExactTracked($gz, $size, $crcContext);
        $totalUncompressed += strlen($data);
        if (!$ok) {
            $violations[] = "Truncated PAX extended header payload.";
            break;
        }
        if ($paddedSize > $size) {
            if (!skipBytesTracked($gz, $paddedSize - $size, $crcContext)) {
                $violations[] = "Truncated PAX header padding.";
                break;
            }
            $totalUncompressed += ($paddedSize - $size);
        }
        $paxRecords = parsePaxRecordsStrict($data, $violations);
        if ($paxRecords !== null && $typeFlag === 'x' && isset($paxRecords['path'])) {
            $pendingPaxPath = $paxRecords['path'];
        }
        continue;
    }

    if ($paddedSize > 0) {
        if (!skipBytesTracked($gz, $paddedSize, $crcContext)) {
            $violations[] = "Truncated file payload for '{$nameField}' (expected {$paddedSize} padded bytes).";
            break;
        }
        $totalUncompressed += $paddedSize;
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

// Validate end of archive marker
if (!$hasEoaFirstBlock || !$hasEoaSecondBlock) {
    $violations[] = "Archive is missing required two 512-byte end-of-archive zero blocks.";
}

// Verify Gzip CRC32 and uncompressed ISIZE
$computedCrc = hexdec(hash_final($crcContext));
if ($computedCrc !== $expectedGzCrc) {
    $violations[] = sprintf("Gzip CRC32 checksum mismatch: computed 0x%08X, footer contains 0x%08X.", $computedCrc, $expectedGzCrc);
}
if (($totalUncompressed & 0xFFFFFFFF) !== $expectedGzIsize) {
    $violations[] = sprintf("Gzip uncompressed size mismatch: computed %d bytes, footer specifies %d bytes.", $totalUncompressed, $expectedGzIsize);
}

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
