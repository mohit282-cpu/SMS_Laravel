'use strict';

/**
 * Shared normalization for npm audit output.
 *
 * npm 7+ reports vulnerabilities per installed package but exposes the
 * affected range, not the installed version. The exact installed version is
 * read from package-lock.json so baseline entries are genuinely version-pinned.
 */

const fs = require('fs');
const path = require('path');

function advisoryIdFromUrl(url) {
    if (!url) return null;
    const match = String(url).match(/(GHSA-[a-z0-9-]+|CVE-\d{4}-\d+)/i);
    return match ? match[1].toUpperCase() : null;
}

function loadInstalledVersions(root) {
    const lockPath = path.join(root, 'package-lock.json');
    const versions = new Map();

    if (!fs.existsSync(lockPath)) {
        return versions;
    }

    const lock = JSON.parse(fs.readFileSync(lockPath, 'utf8').replace(/^\uFEFF/, ''));
    const packages = lock.packages || {};

    for (const [key, value] of Object.entries(packages)) {
        if (!key.startsWith('node_modules/')) continue;
        const name = key.slice('node_modules/'.length);
        if (value && value.version && !versions.has(name)) {
            versions.set(name, value.version);
        }
    }

    return versions;
}

function collectFindings(audit, installedVersions) {
    const vulnerabilities = audit && audit.vulnerabilities;
    if (!vulnerabilities || typeof vulnerabilities !== 'object') {
        throw new Error('npm audit JSON has no "vulnerabilities" section (npm 7+ output required).');
    }

    return Object.entries(vulnerabilities).map(([name, vulnerability]) => {
        const via = Array.isArray(vulnerability.via) ? vulnerability.via : [];
        const advisories = [];
        const viaPackages = [];

        for (const item of via) {
            if (typeof item === 'string') {
                viaPackages.push(item);
                continue;
            }

            const id = item.url ? advisoryIdFromUrl(item.url) : null;
            if (id) {
                advisories.push(id);
            }
        }

        return {
            package: name,
            version: installedVersions.get(name) || vulnerability.range || 'unknown',
            severity: String(vulnerability.severity || 'unknown').toLowerCase(),
            advisories: [...new Set(advisories)].sort(),
            via: [...new Set(viaPackages)].sort(),
            direct: Boolean(vulnerability.isDirect),
        };
    });
}

function fingerprint(finding) {
    return [
        finding.package,
        finding.version,
        finding.severity,
        (finding.advisories || []).join(','),
        (finding.via || []).join(','),
    ].join('|');
}

function canonicalEntry(finding) {
    return {
        package: finding.package,
        version: finding.version,
        severity: finding.severity,
        advisories: finding.advisories || [],
        via: finding.via || [],
    };
}

module.exports = {
    collectFindings,
    fingerprint,
    canonicalEntry,
    loadInstalledVersions,
};
