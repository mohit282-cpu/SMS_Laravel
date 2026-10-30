#!/usr/bin/env node

/**
 * npm security audit gate.
 *
 * Fails closed: a tool/network failure is a CI failure, not a pass.
 *
 * Policy:
 *   - Production dependencies must have zero high/critical vulnerabilities.
 *     This project has no runtime npm dependencies, so the accepted baseline
 *     can never cover a runtime dependency.
 *   - High/critical findings in the legacy build toolchain may only pass when
 *     they exactly match a version-pinned, review-dated baseline entry in
 *     .github/security/npm-exceptions.json. New, changed, or fixed findings
 *     all fail the gate, so the baseline cannot silently grow or go stale.
 *   - Moderate/low findings are reported but do not block the build.
 */

'use strict';

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const {
    collectFindings,
    fingerprint,
    loadInstalledVersions,
} = require('./npm-audit-lib');

const ROOT = path.resolve(__dirname, '..', '..');
const POLICY_FILE = path.join(ROOT, '.github', 'security', 'npm-exceptions.json');
const PACKAGE_FILE = path.join(ROOT, 'package.json');
const BLOCKING_SEVERITIES = new Set(['high', 'critical']);

function fail(message) {
    console.error(`::error::${message}`);
    process.exit(1);
}

function readJson(file, label) {
    if (!fs.existsSync(file)) {
        fail(`${label} not found at ${file}.`);
    }

    const raw = fs.readFileSync(file, 'utf8').replace(/^\uFEFF/, '');
    try {
        return JSON.parse(raw);
    } catch (error) {
        fail(`${label} is not valid JSON: ${error.message}`);
    }
}

function validatePolicy(policy) {
    const problems = [];
    const today = new Date();
    today.setUTCHours(0, 0, 0, 0);

    if (typeof policy.reviewDue !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(policy.reviewDue)) {
        problems.push('reviewDue must be a YYYY-MM-DD date.');
    } else {
        const due = new Date(`${policy.reviewDue}T00:00:00Z`);
        if (Number.isNaN(due.getTime())) {
            problems.push('reviewDue is not a valid date.');
        } else if (due < today) {
            problems.push(`The build-toolchain risk acceptance expired on ${policy.reviewDue}. Re-review it or complete the frontend modernization.`);
        }
    }

    if (typeof policy.owner !== 'string' || policy.owner.trim() === '') {
        problems.push('owner is required.');
    }

    if (typeof policy.reason !== 'string' || policy.reason.trim().length < 20) {
        problems.push('reason must describe why the residual build-time risk is acceptable.');
    }

    if (!Array.isArray(policy.mitigations) || policy.mitigations.length === 0) {
        problems.push('mitigations must list at least one compensating control.');
    }

    if (!Array.isArray(policy.accepted)) {
        problems.push('accepted must be an array of version-pinned findings.');
        return problems;
    }

    const seen = new Set();
    for (const [index, entry] of policy.accepted.entries()) {
        if (!entry || typeof entry !== 'object') {
            problems.push(`accepted[${index}] must be an object.`);
            continue;
        }

        if (typeof entry.package !== 'string' || entry.package.trim() === '') {
            problems.push(`accepted[${index}].package is required.`);
        }
        if (typeof entry.version !== 'string' || entry.version.trim() === '') {
            problems.push(`accepted[${index}].version is required.`);
        }
        if (!BLOCKING_SEVERITIES.has(entry.severity)) {
            problems.push(`accepted[${index}].severity must be "high" or "critical".`);
        }
        if (!Array.isArray(entry.advisories)) {
            problems.push(`accepted[${index}].advisories must be an array.`);
        } else if (entry.advisories.some((id) => typeof id !== 'string' || id.trim() === '')) {
            problems.push(`accepted[${index}].advisories contains an empty advisory ID.`);
        }
        if (!Array.isArray(entry.via)) {
            problems.push(`accepted[${index}].via must be an array.`);
        }

        const key = JSON.stringify(entry);
        if (seen.has(key)) {
            problems.push(`accepted[${index}] duplicates another baseline entry.`);
        }
        seen.add(key);
    }

    return problems;
}

function runAudit() {
    try {
        // The command is a fixed constant; no user input is interpolated.
        return execSync('npm audit --json', {
            cwd: ROOT,
            encoding: 'utf8',
            maxBuffer: 64 * 1024 * 1024,
            stdio: ['ignore', 'pipe', 'pipe'],
        });
    } catch (error) {
        const stdout = error.stdout ? String(error.stdout) : '';
        if (stdout.trim().startsWith('{')) {
            return stdout;
        }

        const stderr = error.stderr ? String(error.stderr).trim() : '';
        fail(`npm audit could not run. This is a tool/network error, not a clean audit. ${stderr.slice(0, 1500)}`);
    }
}

function main() {
    const policy = readJson(POLICY_FILE, 'npm exception policy');
    const problems = validatePolicy(policy);
    if (problems.length > 0) {
        problems.forEach((problem) => console.error(`::error::Policy: ${problem}`));
        fail(`npm exception policy is invalid (${problems.length} problem(s)).`);
    }

    const packageJson = readJson(PACKAGE_FILE, 'package.json');
    const runtimeDependencies = Object.keys(packageJson.dependencies || {});
    if (runtimeDependencies.length > 0) {
        fail(`Runtime dependencies are present (${runtimeDependencies.join(', ')}). The build-toolchain baseline cannot cover them; add a separate runtime audit review.`);
    }

    let audit;
    try {
        audit = JSON.parse(runAudit().replace(/^\uFEFF/, ''));
    } catch (error) {
        fail(`npm audit did not return parsable JSON. The audit result is unavailable: ${error.message}`);
    }

    if (audit.error) {
        fail(`npm audit failed: ${audit.error.summary || audit.error.detail || 'unknown tool error'}. The audit result is unavailable.`);
    }

    let findings;
    try {
        findings = collectFindings(audit, loadInstalledVersions(ROOT));
    } catch (error) {
        fail(`npm audit result is unusable: ${error.message}`);
    }

    const blocking = findings.filter((finding) => BLOCKING_SEVERITIES.has(finding.severity));
    const counts = findings.reduce((accumulator, finding) => {
        accumulator[finding.severity] = (accumulator[finding.severity] || 0) + 1;
        return accumulator;
    }, {});

    const baseline = new Map(policy.accepted.map((entry) => [fingerprint(entry), entry]));
    const current = new Map(blocking.map((finding) => [fingerprint(finding), finding]));

    const newOrChanged = [...current.entries()].filter(([key]) => !baseline.has(key));
    const stale = [...baseline.keys()].filter((key) => !current.has(key));

    console.log(`npm audit: ${blocking.length} high/critical finding(s) `
        + `(critical: ${counts.critical || 0}, high: ${counts.high || 0}, `
        + `moderate: ${counts.moderate || 0}, low: ${counts.low || 0}).`);

    for (const [, finding] of newOrChanged) {
        console.error(`::error::UNAPPROVED ${finding.severity.toUpperCase()} finding: ${finding.package}@${finding.version} `
            + `(${finding.advisories.join(', ') || 'transitive'}). Fix it, or update the reviewed baseline with justification.`);
    }

    for (const key of stale) {
        const entry = baseline.get(key);
        console.error(`::error::STALE baseline entry: ${entry.package}@${entry.version} is no longer reported. `
            + 'Remove it so accepted risk cannot silently accumulate.');
    }

    if (newOrChanged.length > 0 || stale.length > 0) {
        fail(`npm security gate failed: ${newOrChanged.length} new/changed and ${stale.length} stale finding(s).`);
    }

    console.log(`npm security gate passed: ${baseline.size} reviewed high/critical build-toolchain finding(s) matched exactly; 0 runtime dependencies.`);
}

main();
