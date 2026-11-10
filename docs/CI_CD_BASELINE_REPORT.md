# CI/CD Baseline Audit Report — SMS Academy

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Repository:** https://github.com/mohit282-cpu/SMS_Laravel  
**Target Branch:** `testing`  
**Date of Audit:** 2026-11-10  
**Audit Lead:** Senior DevOps / CI/CD Architect & QA Team  

---

## 1. Executive Summary

This report establishes the baseline status of the continuous integration, continuous delivery, dependency security, and automated testing architecture of the SMS Academy repository prior to remediation.

Before remediation, GitHub Actions CI was experiencing critical blocking failures:
1. **Critical PHP Route Registration Failure:** `php artisan route:list` crashed during CI before migrations ran due to controller constructors performing database lookups on unmigrated tables.
2. **Composer Security Gate Failures:** Five newly published live advisories (Nov 4, 2026) for Symfony components (`symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing`) caused `.github/scripts/audit-checker.php` to fail.
3. **npm Security Gate Drift:** `node-forge@0.10.0` received new GHSA advisories from npm, causing `.github/scripts/npm-audit-checker.js` to flag an unapproved high finding.
4. **Branch Protection Risk:** CI lacked a unified `integration-gate` job, meaning GitHub branch protection required tracking multiple separate jobs without an atomic pass/fail assertion.
5. **Continuous Deployment Gap:** No automated or staged deployment pipeline (`cd.yml`) existed; releases were manual without rollback drills.

---

## 2. Baseline Environment & Infrastructure

### 2.1 Git Baseline
- **Active Branch:** `testing`
- **Baseline Commit SHA:** `b7aec60f61447cc271fc6ad35339f9916a94591b`
- **Remote Origin:** `https://github.com/mohit282-cpu/SMS_Laravel.git`
- **Working Tree State:** Clean (verified via `git status --short`)

### 2.2 Local & CI Runtime Versions
| Component | Local Audit Version | CI Container Version | Status |
|---|---|---|---|
| **PHP** | 8.2.31 (ZTS x64) | 8.2 (shivammathur/setup-php) | Verified compatible |
| **Composer** | 2.10.3 | 2.x | Compliant with `composer.json` |
| **Node.js (Legacy Build)** | v24.16.0 (Host) | 14.x (`actions/setup-node@v4`) | Required for node-sass 4.14 |
| **Node.js (Audit)** | v24.16.0 (Host) | 22.x (`actions/setup-node@v4`) | Required for modern npm audit format |
| **npm** | 11.13.0 | npm 6 (Node 14) / npm 10+ (Node 22) | Verified dual-runtime strategy |
| **MySQL Service** | MySQL 8.0.x | `mysql:8.0` container | Disposable container with health checks |

---

## 3. Detailed Baseline Findings

### 3.1 Failure 1: Critical Route-List Database Lookup Defect (Severity: CRITICAL)
- **Error:** `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sms_laravel_test.settings' doesn't exist`
- **Location:**
  - `app/Http/Controllers/SupportTeam/TimeTableController.php` (line 25)
  - `app/Http/Controllers/SupportTeam/PaymentController.php` (line 27)
  - `app/Http/Controllers/SupportTeam/MarkController.php` (line 28)
  - `app/Helpers/Qs.php` (`getCurrentSession()` & `getSetting()`)
- **Root Cause:** When `php artisan route:list` executes, Laravel instantiates controllers to inspect their controller middleware. The constructors called `Qs::getCurrentSession()`, which queried `AcademicYear::current()` and `Setting::where('type', 'current_session')`. Because CI executes `route:list` on an unmigrated database before running migrations, the query crashed immediately with SQL error 1146/1109.

### 3.2 Failure 2: Live Composer Security Advisories (Severity: HIGH)
- **Command:** `php .github/scripts/audit-checker.php`
- **Output:** 18 live advisories affecting 7 packages (1 critical, 1 high, 7 medium, 4 low, 5 unknown).
- **Unapproved Advisories Discovered:**
  - `symfony/http-foundation`: `CVE-2026-118514`, `2026-11-04-21.yaml`, `2026-11-04-22.yaml`
  - `symfony/http-kernel`: `CVE-2026-123339`
  - `symfony/routing`: `CVE-2026-118516`
- **Status:** Unpatched `v5.4.54` packages installed. Security gate failed with 5 violations.

### 3.3 Failure 3: npm Security Baseline Drift (Severity: HIGH)
- **Command:** `node .github/scripts/npm-audit-checker.js`
- **Output:** 79 high/critical findings (33 critical, 46 high).
- **Issue:** `node-forge@0.10.0` had new GHSA advisories published to npm registry, invalidating the pinned baseline fingerprint in `.github/security/npm-exceptions.json`.

### 3.4 Failure 4: Missing Automated Deployment Pipeline (Severity: MEDIUM)
- No `cd.yml` was present. Deployments were unscripted, lacking pre-migration backups, maintenance mode handling, atomic symlinking, or post-deploy health verification.

---

## 4. Initial Quality & Testing Baseline
- **PHPUnit Suite:** 412 tests, 2,731 assertions. All passed in isolated test environment once database was seeded.
- **Syntax Lint:** 100% PHP files passed syntax checks (`php -l`).
- **Composer Manifest:** `composer validate --strict` passed cleanly.
- **Platform Requirements:** `composer check-platform-reqs` passed with all required extensions present.

---

## 5. Risk Assessment Summary
| Domain | Initial Risk | Remediation Priority |
|---|---|---|
| **CI Route Execution** | Critical (CI completely broken) | Immediate (Phase 1) |
| **PHP Dependencies** | High (Unpatched CVEs in Symfony) | Immediate (Phase 3) |
| **npm Toolchain** | High (79 vulnerabilities, legacy Mix) | High (Phase 4) |
| **CI Pipeline Architecture** | Medium (No unified gate) | High (Phase 9/10) |
| **CD & Release Operations** | High (Manual, no rollback drill) | High (Phase 11) |

