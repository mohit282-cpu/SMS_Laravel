<?php

/**
 * Expand-and-contract migration safety scanner.
 *
 * Scans database/migrations/*.php for potentially destructive operations
 * (dropColumn, dropIfExists, drop, renameColumn) that lack an explicit
 * data-preservation guard (count() / whereNotNull / RuntimeException).
 */

$migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "::error::Migrations directory not found: {$migrationsDir}\n");
    exit(1);
}

$files = glob($migrationsDir . '/*.php') ?: [];
sort($files);

$warnings = [];

foreach ($files as $file) {
    $name = basename($file);
    $content = (string) file_get_contents($file);

    // Check up() for destructive schema changes without guards
    if (preg_match('/function\s+up\s*\([^)]*\)\s*\{(.*?)function\s+down\s*\(/s', $content, $m)) {
        $upBody = $m[1];
        if (preg_match('/->\s*(dropColumn|renameColumn|drop|dropIfExists)\s*\(/', $upBody, $op)) {
            $warnings[] = "{$name}: up() contains potentially backward-incompatible operation '{$op[1]}'. Prefer expand-and-contract migrations.";
        }
    }
}

if (!empty($warnings)) {
    foreach ($warnings as $w) {
        fwrite(STDOUT, "::warning::{$w}\n");
    }
}

fwrite(STDOUT, "Migration safety scan completed (" . count($files) . " migration file(s) inspected, " . count($warnings) . " expand-and-contract warning(s)).\n");
exit(0);

