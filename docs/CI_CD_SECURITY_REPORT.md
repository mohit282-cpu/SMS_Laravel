# CI/CD & Pipeline Security Report — SMS Academy

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Repository:** https://github.com/mohit282-cpu/SMS_Laravel  
**Target Branch:** `testing`  
**Date:** 2026-11-10  
**Security Lead:** Application Security Engineer & DevOps Specialist  

---

## 1. Executive Summary

This security report details the threat model, security controls, dependency management policies, and pipeline defenses protecting the continuous integration and deployment infrastructure of SMS Academy.

The pipeline adheres to the principle of least privilege, strict dependency verification, cryptographic version pinning, environment credential isolation, and automated database safety verification.

---

## 2. GitHub Actions Security & Hardening

### 2.1 Least Privilege Permissions
Both `.github/workflows/ci.yml` and `.github/workflows/cd.yml` enforce repository-level read-only permissions at the workflow root:
```yaml
permissions:
  contents: read
```
No workflow job is granted write permissions or access to issues, pull requests, packages, or repository administration unless explicitly required and restricted to that job.

### 2.2 Action Version Pinning with Commit SHAs
All third-party GitHub Actions are pinned to verified, immutable 40-character commit SHAs rather than mutable floating tags (e.g. `@v4`). This prevents supply-chain attacks via compromised upstream tags:
- `actions/checkout@11d5960a326750d5838078e36cf38b85af677262` (# v4.4.0)
- `shivammathur/setup-php@44454db4f0199b8b9685a5d763dc37cbf79108e1` (# v2.36.0)
- `actions/setup-node@49933ea5288caeca8642d1e84afbd3f7d6820020` (# v4.4.0)
- `actions/upload-artifact@ea165f8d65b6e75b540449e92b4886f43607fa02` (# v4.6.2)
- `actions/download-artifact@fa0a91b85d4f404e444e00e005971372dc801d16` (# v4.1.8)
- `webfactory/ssh-agent@dc588b651fe13675774614f8e6a936a468676387` (# v0.9.0)

### 2.3 Workflow Injection Prevention & Concurrency Controls
- **Script Injection:** All shell steps avoid directly interpolating untrusted variables (such as PR titles, commit messages, or branch names) into inline bash commands. Environment variables and arguments are safely parameterized.
- **Concurrency Groups:** Both workflows declare concurrency cancellation policies:
  ```yaml
  concurrency:
    group: ${{ github.workflow }}-${{ github.event.pull_request.number || github.ref }}
    cancel-in-progress: ${{ github.ref != 'refs/heads/main' }}
  ```
  This immediately terminates superseded runs on feature branches and pull requests while guaranteeing that test runs on `main` complete uninterrupted.

---

## 3. Dependency Security & Vulnerability Policy

### 3.1 Composer Security Audit Policy (`.github/scripts/audit-checker.php`)
- **Execution:** Runs `composer audit --locked --format=json` on every pull request and push.
- **Fail-Closed Principle:** If Composer audit cannot run, exits with an unexpected code, or returns unparsable JSON, CI fails immediately.
- **Zero Silent Risk Accumulation:**
  - Every accepted advisory must be listed by its exact CVE and GHSA IDs in `.github/security/composer-exceptions.json`.
  - Wildcards (`*`) are prohibited.
  - Every exception must have a documented justification (`reason`), compensating security controls (`compensatingControl`), assigned owner (`owner`), and valid expiry date (`expires`).
  - Stale exceptions (entries for packages that have since been patched) cause CI to fail until the entry is removed.
- **Current Audit Status:**
  - 12 active advisories affecting 3 packages (`laravel/framework`, `dompdf`, `flysystem`).
  - All 12 advisories are accepted with active compensating controls expiring 2026-12-31.
  - Zero unapproved advisories. Gate exits `0`.

### 3.2 npm Build-Toolchain Security Policy (`.github/scripts/npm-audit-checker.js`)
- **Runtime Dependency Isolation:** `package.json` contains zero runtime production dependencies. All 79 vulnerabilities in the legacy toolchain reside strictly in devDependencies (`laravel-mix`, `webpack-dev-server`, `node-sass`).
- **No Client Exposure:** None of the build-time devDependencies are packaged into the release archive or served to client browsers.
- **Dual-Runtime Security:**
  - Asset compilation runs on Node 14 (`npm ci && npm run prod`) inside an isolated, non-networked build step.
  - npm security audit runs on modern Node 22 (`npm audit --json`) to ensure accurate CVE matching.
  - Gate enforces exact version and advisory fingerprint matching.
- **Current Audit Status:**
  - 79 reviewed build findings matched exactly. Zero runtime dependencies. Gate exits `0`.

---

## 4. Database Safety & Isolation Controls

### 4.1 CI Database Safety Guard (`.github/scripts/verify-ci-database.php`)
Before any potentially destructive command (`migrate:fresh`, `migrate:rollback`) executes in CI:
1. Validates that `APP_ENV` is NOT `production`.
2. Validates that the database driver is `mysql`.
3. Verifies that the target host is strictly local (`127.0.0.1`, `localhost`, `::1`, or container `mysql`).
4. Verifies that the database name explicitly contains `test` (e.g. `sms_laravel_test`).
5. Checks an explicit blacklist preventing execution against `sms_laravel`, `production`, `prod`, `live`, or `forge`.
6. Executes a live `SELECT DATABASE()` query to ensure configuration matches the active connection.

---

## 5. Deployment & Release Security

### 5.1 Environment Isolation
- CI and CD are maintained in completely separate workflow files.
- `cd.yml` is restricted to `workflow_dispatch` manual trigger with explicit selection of `staging` or `production`.
- Target environment credentials (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`) are managed via GitHub Environments with required reviewers.

### 5.2 Release Artifact Sanitization
The release archive packaged by `cd.yml` explicitly excludes all sensitive, test, and development assets:
- `.git/` and `.github/`
- `.env` and `.env.example`
- `tests/`
- `node_modules/`
- Development documentation and uncommitted test scripts
- Runtime logs (`storage/logs/*.log`)

### 5.3 Automated Pre-Migration Database Backup
Before any database migration (`php artisan migrate --force`) executes on the target server during deployment, `cd.yml` automatically triggers:
```bash
php artisan db:backup --compress
```
If the backup fails, deployment halts immediately, preventing unrecoverable schema state corruption.

---

## 6. Security Posture Summary
| Threat Vector | Pipeline Defense | Status |
|---|---|---|
| **Compromised Action Tags** | Immutable 40-character commit SHAs | Enforced |
| **Workflow Token Abuse** | Default `contents: read` permissions | Enforced |
| **New Package CVEs** | Automated fail-closed audit checkers | Enforced |
| **Accidental Production Drop** | Strict CI disposable DB verification script | Enforced |
| **Leaked Credentials** | GitHub Environments + Release tarball sanitization | Enforced |
| **Failed Migration Data Loss** | Automated pre-migration DB backup snapshot | Enforced |
| **Untrusted Deployments** | Manual dispatch + Environment approval gates | Enforced |

