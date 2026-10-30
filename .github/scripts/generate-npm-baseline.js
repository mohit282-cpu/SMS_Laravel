#!/usr/bin/env node

/**
 * Regenerates .github/security/npm-exceptions.json from a live npm audit.
 *
 * Usage:
 *   node .github/scripts/generate-npm-baseline.js --from audit.json --write
 *   node .github/scripts/generate-npm-baseline.js --write
 *
 * Regenerating is a reviewed action: every new or changed high/critical
 * finding must be triaged before the resulting diff is committed. Never run
 * this in CI.
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const {
    collectFindings,
    canonicalEntry,
    loadInstalledVersions,
} = require('./npm-audit-lib');

const ROOT = path.resolve(__dirname, '..', '..');
const POLICY_FILE = path.join(ROOT, '.github', 'security', 'npm-exceptions.json');
const BLOCKING = new Set(['high', 'critical']);

const args = process.argv.slice(2);
const write = args.includes('--write');
const fromIndex = args.indexOf('--from');
const fromFile = fromIndex !== -1 ? args[fromIndex + 1] : null;

function loadAudit() {
    if (fromFile) {
        return fs.readFileSync(path.resolve(process.cwd(), fromFile), 'utf8');
    }

    const npm = process.env.NPM_BINARY || (process.platform === 'win32' ? 'npm.cmd' : 'npm');
    const command = `${npm} audit --json`;

    try {
        return execSync(command, {
            cwd: ROOT,
            encoding: 'utf8',
            maxBuffer: 64 * 1024 * 1024,
            stdio: ['ignore', 'pipe', 'pipe'],
        });
    } catch (error) {
        if (error.stdout && String(error.stdout).trim().startsWith('{')) {
            return String(error.stdout);
        }
        throw new Error(error.stderr ? String(error.stderr) : 'npm audit failed');
    }
}

const audit = JSON.parse(loadAudit().replace(/^\uFEFF/, ''));
const findings = collectFindings(audit, loadInstalledVersions(ROOT))
    .filter((finding) => BLOCKING.has(finding.severity))
    .sort((a, b) => a.package.localeCompare(b.package) || a.version.localeCompare(b.version));

const policy = {
    policy: 'npm-build-dependency-exceptions',
    version: 1,
    description: 'Version-pinned risk acceptance for high/critical advisories in the legacy Laravel Mix 2 / Webpack 3 / Vue 2 build toolchain. All entries are devDependencies: they compile static assets on CI and are never shipped to browsers or executed in production. The audit gate fails on any new, changed, or fixed finding, so this file cannot silently grow',
    owner: 'Repository owner (mohit282-cpu)',
    reviewDue: '2026-12-31',
    reason: 'The frontend toolchain is pinned to laravel-mix 2.1.14 / webpack 3 / node-sass 4.14 / Vue 2.7. node-sass 4.x has no prebuilt binaries for Node versions newer than 14 and Laravel Mix 2 depends on Webpack 3, so the build cannot move to a supported Node LTS without a dedicated toolchain migration. All affected packages are build-time devDependencies; no npm package is served to users.',
    mitigations: [
        'package.json declares zero runtime dependencies, so the accepted findings cannot reach a user browser or production runtime.',
        'CI installs strictly from the committed package-lock.json with npm ci on an isolated ephemeral runner.',
        'The legacy webpack-dev-server / hot-reload mode is never exposed by CI or production.',
        'Every entry is version-pinned; any dependency change, new advisory, or removed advisory fails the gate and requires a documented re-review.',
        'docs/frontend-modernization.md defines the migration to Vite or Laravel Mix 6 on a supported Node LTS with an exit review date.',
    ],
    accepted: findings.map(canonicalEntry),
};

if (write) {
    fs.writeFileSync(POLICY_FILE, `${JSON.stringify(policy, null, 4)}\n`);
    console.log(`Wrote ${policy.accepted.length} reviewed high/critical finding(s) to ${path.relative(ROOT, POLICY_FILE)}.`);
} else {
    process.stdout.write(`${JSON.stringify(policy, null, 4)}\n`);
}
