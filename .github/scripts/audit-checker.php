<?php

/**
 * CI/CD Security Audit Gate with Documented Vulnerability Exception Policy
 *
 * Replaces blanket continue-on-error by validating all composer audit findings
 * against an explicit, audited security allowlist. Any unapproved, new, or unvetted
 * vulnerability fails the CI pipeline immediately.
 */

$auditJson = shell_exec('composer audit --locked --format=json 2>&1');
if (!$auditJson) {
    echo "::error::Failed to execute composer audit.\n";
    exit(1);
}

$data = json_decode($auditJson, true);
if (!is_array($data)) {
    echo "::error::Could not parse composer audit JSON output.\n";
    echo $auditJson . "\n";
    exit(1);
}

$advisories = $data['advisories'] ?? [];

// Documented, vetted vulnerability allowlist for Laravel 8.83.27 LTS baseline
$allowlist = [
    // laravel/framework advisories inherent to Laravel 8 LTS
    'GHSA-f9x3-7vq9-cph6' => 'TokenGuard non-string credentials: app uses session auth, not TokenGuard',
    'GHSA-jh5r-qr3c-85q8' => 'XSS in debug page: APP_DEBUG is false in production',
    'GHSA-crmm-hgp2-wgrp' => 'Signed URL path confusion: signed URLs not used for state mutations',
    'GHSA-5vg9-5847-vvmq' => 'CRLF in email rule: standard RFC email validation rules enforced',
    'GHSA-78fx-h6xr-vch4' => 'File validation bypass: application implements server-side MIME and extension validation',

    // league/flysystem advisory inherent to flysystem v1
    'GHSA-cxf4-7mrp-vvpr' => 'Flysystem control char bypass: file uploads use safe random filenames',

    // symfony/mime advisory
    'CVE-2026-45070' => 'Email header injection in mime parameter: mail parameters sanitized',
    'GHSA-vqc8-7275-q272' => 'Email header injection in mime parameter (GitHub advisory alias)',

    // dompdf advisories
    'GHSA-8hg6-c449-896m' => 'Dompdf BMP dimensions: marksheet PDFs do not render external BMPs',
    'GHSA-cx96-42px-69fm' => 'Dompdf SVG data-URI: marksheets do not render arbitrary user SVG data-URIs',
    'GHSA-7x2p-4jvh-6384' => 'Dompdf font-face oracle: font paths are local and restricted',
    'GHSA-wvh6-f5jh-8gw4' => 'Dompdf chroot validation: restricted internal template rendering',
    'CVE-2026-59943' => 'Dompdf embedded SVG file leak: marksheets do not render external SVGs',
    'CVE-2026-59942' => 'Dompdf oversized image bitmap DoS: marksheets do not render arbitrary external images',
];

$unapproved = [];
$approvedCount = 0;

foreach ($advisories as $package => $packageAdvisories) {
    foreach ($packageAdvisories as $advisory) {
        $cve = $advisory['cve'] ?? null;
        $advisoryId = $advisory['advisoryId'] ?? null;
        $remoteIds = array_column($advisory['sources'] ?? [], 'remoteId');

        $matched = false;
        foreach (array_merge([$cve, $advisoryId], $remoteIds) as $id) {
            if ($id && isset($allowlist[$id])) {
                $matched = true;
                $approvedCount++;
                break;
            }
        }

        if (!$matched) {
            $unapproved[] = [
                'package' => $package,
                'title' => $advisory['title'] ?? 'Unknown',
                'severity' => $advisory['severity'] ?? 'unknown',
                'cve' => $cve,
                'remoteIds' => $remoteIds,
            ];
        }
    }
}

if (!empty($unapproved)) {
    echo "::error::Composer Security Audit Gate Failed! Unapproved vulnerabilities detected:\n";
    foreach ($unapproved as $vuln) {
        echo " - [{$vuln['severity']}] {$vuln['package']}: {$vuln['title']} (CVE: {$vuln['cve']})\n";
    }
    exit(1);
}

echo "Composer security audit passed: {$approvedCount} documented EOL advisories verified against policy; 0 unapproved vulnerabilities.\n";
exit(0);
