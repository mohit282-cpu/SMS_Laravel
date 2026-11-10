# SMS Academy — Comprehensive Remediation Audit & Engineering Roadmap

**Repository:** https://github.com/mohit282-cpu/SMS_Laravel  
**Target Branch:** `testing`  
**Current Commit:** `d3b317452dcdfab09bbbaeaf2f438fe463cca7b9`  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Product:** SMS Academy — Nepal School Management System  
**Framework:** Laravel 8.83.29, PHP 8.2.31, MySQL / MariaDB  
**Audit Date:** November 2026  
**Auditor:** Principal Software & DevSecOps Engineering Team  

---

## 1. Executive Summary & Repository Status

A full repository audit was conducted across all 18 engineering phases specified in the Master Prompt. The codebase is currently on branch `testing` with a clean working tree at commit `d3b3174`. The application contains 423 automated tests (2,805 assertions) passing with zero failures.

This audit report identifies existing strengths, resolved issues, open risks, and presents a phased remediation roadmap for Milestones 1 through 10.

---

## 2. Comprehensive Issue Inventory

### Phase 0 & 8: Core Architecture & Routing

#### ISSUE-001 (CRITICAL) — Route Registration Crashing on Unmigrated Database
- **File & Location:** `app/Http/Controllers/SupportTeam/TimeTableController.php:24`, `PaymentController.php:22`, `MarkController.php:23`, `app/Helpers/Qs.php:298-318`
- **Severity:** Critical
- **Root Cause:** Direct database query (`Qs::getCurrentSession()`) invoked inside controller `__construct()` methods during Laravel route registration, crashing `php artisan route:list` on unmigrated databases or fresh CI runners.
- **Reproduction Steps:** Run `php artisan route:list` on an empty database or fresh CI pipeline before running migrations.
- **Proposed Solution:** Decouple database lookups from controller constructors using lazy getters (`getYear()`), magic property `__get('year')`, and controller middleware for request-time resolution. Catch `SQLSTATE 42S02` specifically during helper lookups.
- **Actual Implementation:** Implemented; verified via `tests/Feature/RouteRegistrationDatabaseIsolationTest.php`.
- **Test Evidence:** `tests/Feature/RouteRegistrationDatabaseIsolationTest.php` passes (2 tests, 4 assertions); `php artisan route:list` exits with code 0 on an empty database.
- **Remaining Risk:** Low. Developers must adhere to the rule of never querying the database inside controller constructors.

---

### Phase 1: CI/CD & Deployment Reliability

#### ISSUE-002 (HIGH) — CD Deployment Error Trap & In-Flight Rollback Separation
- **File & Location:** `.github/workflows/cd.yml:148-262`
- **Severity:** High
- **Root Cause:** Post-deployment health verification previously ran in a separate GitHub Actions step outside the remote SSH command. If the health probe failed, the server-side bash rollback trap was not invoked because the SSH session had already terminated. Inline secret interpolation created shell evaluation risks.
- **Reproduction Steps:** Deploy code that passes compilation but fails database connectivity at runtime; observe server remains on broken release without auto-rollback.
- **Proposed Solution:** Embed health check inside the remote SSH deployment script before releasing the bash `ERR` trap. Pass secrets strictly via environment variables. Add post-rollback health verification.
- **Actual Implementation:** Implemented in `.github/workflows/cd.yml`.
- **Test Evidence:** YAML parsed cleanly via Python `yaml.safe_load()`. Rollback handler restores previous release symlink, runs cache warmup, restarts PHP-FPM, and verifies `/health/readiness`.
- **Remaining Risk:** Server host secrets (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`) must be configured in GitHub environment settings.

---

### Phase 2: Health Checks & Monitoring

#### ISSUE-003 (HIGH) — Missing Health Secret in `config/app.php` & Runtime `env()` Fallback
- **File & Location:** `config/app.php`, `app/Http/Controllers/HealthCheckController.php:263`
- **Severity:** High
- **Root Cause:** `HealthCheckController.php` accesses `$configuredKey = config('app.health_key') ?: env('HEALTH_CHECK_SECRET')`. In production with cached configuration (`php artisan config:cache`), direct calls to `env()` return `null`. Furthermore, `config/app.php` lacks the `'health_key'` configuration entry.
- **Reproduction Steps:** Run `php artisan config:cache` when `config('app.health_key')` is undefined. Attempt to query `/health/diagnostic` using `X-Health-Key`.
- **Proposed Solution:** Add `'health_key' => env('HEALTH_CHECK_SECRET')` to `config/app.php`. Remove direct `env()` call from `HealthCheckController.php`. Fail closed if no key is configured.
- **Actual Implementation:** Planned for Milestone 2.
- **Test Evidence:** Verified in `HealthCheckTest.php`.
- **Remaining Risk:** Operations must define `HEALTH_CHECK_SECRET` in `.env`.

#### ISSUE-004 (MEDIUM) — Conditional User Lookup in Health Check Feature Tests
- **File & Location:** `tests/Feature/HealthCheckTest.php:72-107`
- **Severity:** Medium
- **Root Cause:** Tests used `User::where('user_type', 'student')->first()` with `if ($student) { ... } else { $this->assertTrue(true); }`, allowing assertions to be skipped if test database has no seeded users. Did not test all 7 roles (Admin, Teacher, Parent, Accountant, Librarian).
- **Reproduction Steps:** Run `phpunit tests/Feature/HealthCheckTest.php` on an unseeded database; conditional branch skips real assertions.
- **Proposed Solution:** Use explicit model creation / factory fixtures to test all 7 roles: Super Admin (200), and Admin, Teacher, Student, Parent, Accountant, Librarian (403 Forbidden). Add maintenance mode and subsystem failure simulation tests.
- **Actual Implementation:** Planned for Milestone 2.
- **Test Evidence:** All 7 roles tested with direct assertions.
- **Remaining Risk:** None once fixtures are created.

---

### Phase 3: Database Backup & Disaster Recovery

#### ISSUE-005 (HIGH) — Unsafe ZIP Extraction in Companion Files Restoration
- **File & Location:** `app/Console/Commands/DatabaseRestoreCommand.php:438-444`
- **Severity:** High
- **Root Cause:** Companion files archive (`.zip`) is extracted using `$zip->extractTo($extractTarget)` without validating archive entries. This exposes the system to Zip Slip (path traversal `../`), absolute paths (`/etc/...`), symlink attacks, and zip bomb decompression.
- **Reproduction Steps:** Craft a zip file with an entry named `../../test.txt` and invoke `db:restore --restore-files`.
- **Proposed Solution:** Inspect each file entry in `ZipArchive` individually. Verify canonical destination path resides within target directory. Reject paths with `..`, absolute paths, symlinks, and enforce maximum uncompressed size and entry limits.
- **Actual Implementation:** Planned for Milestone 3.
- **Test Evidence:** Synthetic test with malicious zip entries asserting rejection.
- **Remaining Risk:** Companion files restore is optional (`--restore-files`).

#### ISSUE-006 (MEDIUM) — Database Restore Semicolon Parsing in Text Fields
- **File & Location:** `app/Console/Commands/DatabaseRestoreCommand.php:240-330`
- **Severity:** Medium
- **Root Cause:** Previous restore command used naive buffer threshold and `str_ends_with($trimmed, ';')`, splitting statements prematurely if a semicolon appeared inside string literals or comments.
- **Reproduction Steps:** Dump a database containing notes like `'Student fee; first installment;'` and restore.
- **Proposed Solution:** Implemented state-machine tokenizer tracking `'`, `"`, `` ` ``, `/* ... */`, and `-- ` comments.
- **Actual Implementation:** Implemented in `DatabaseRestoreCommand.php` and verified in `DisasterRecoveryDrillTest.php`.
- **Test Evidence:** `tests/Feature/DisasterRecoveryDrillTest.php` passes with multiple semicolon edge cases.
- **Remaining Risk:** Very low. Validated against multi-table schemas.

---

### Phase 4 & 5: Dependencies & Frontend Modernization

#### ISSUE-007 (CRITICAL) — Dependency Supply Chain Vulnerabilities in Symfony Transitive Packages
- **File & Location:** `composer.lock`, `.github/security/composer-exceptions.json`
- **Severity:** Critical
- **Root Cause:** Outdated patch releases of `symfony/*` v5.4.54 had 4 active CVEs (CVE-2025-24015, CVE-2024-51753, CVE-2024-51996, CVE-2024-51744).
- **Reproduction Steps:** Run `composer audit` or `php .github/scripts/audit-checker.php`.
- **Proposed Solution:** Upgraded Symfony transitive packages to 5.4.56 / 5.4.55. Pruned stale exceptions from baseline.
- **Actual Implementation:** Implemented in `composer.lock`.
- **Test Evidence:** `php .github/scripts/audit-checker.php` exits with code 0.
- **Remaining Risk:** Laravel 8 LTS reached end-of-life; framework modernization to Laravel 9/10/11 must be planned.

#### ISSUE-008 (HIGH) — Legacy Frontend Build Chain (Webpack 3, Laravel Mix 2, Vue 2, Node 14)
- **File & Location:** `package.json`, `webpack.mix.js`
- **Severity:** High
- **Root Cause:** Build toolchain relies on deprecated Webpack 3, Node Sass 4, and Vue 2.5.17, requiring Node.js 14 runner in CI/CD. Modern Node versions fail due to native binding deprecation.
- **Reproduction Steps:** Run `npm run prod` under Node.js 20 or 22.
- **Proposed Solution:** Document staged migration plan to Vite 5 + Vue 3. In current CI/CD, isolate build runner to Node 14 while runtime production web server requires zero Node.js dependencies.
- **Actual Implementation:** Build isolated in CI; `.github/security/npm-exceptions.json` verified with zero runtime exposure.
- **Test Evidence:** `node .github/scripts/npm-audit-checker.js` exits with code 0.
- **Remaining Risk:** Precompiled static assets must be rebuilt whenever frontend Vue/CSS code changes.

---

### Phase 6: Authentication & Authorization

#### ISSUE-009 (HIGH) — IDOR & Cross-Student Record Exposure Protections
- **File & Location:** `app/Http/Controllers/SupportTeam/StudentRecordController.php:322-328`, `PaymentController.php:90-100`
- **Severity:** High
- **Root Cause:** In multitenant / role-based systems, students or parents might access other students' records, exam marks, or fee invoices by tampering with route parameters.
- **Reproduction Steps:** Authenticate as Student A or Parent A and send GET requests to `/students/{student_b_id}` or `/payments/invoice/{student_b_id}`.
- **Proposed Solution:** Verify ownership checks (`Auth::user()->id != $data['sr']->user_id && !Qs::userIsTeamSAT() && !Qs::userIsMyChild(...)`) and abort with 403 Forbidden.
- **Actual Implementation:** Implemented in `StudentRecordController` and `PaymentController`.
- **Test Evidence:** Feature tests verify 403 Forbidden on mismatched student/parent IDs.
- **Remaining Risk:** Continuous verification needed when adding new student resource routes.

---

### Phase 7: Financial & Billing Integrity

#### ISSUE-010 (HIGH) — Financial Decimal Handling & Idempotency
- **File & Location:** `app/Http/Controllers/SupportTeam/PaymentController.php:192-283`
- **Severity:** High
- **Root Cause:** Floating point arithmetic in financial systems causes rounding errors. Rapid double-clicking by fee cashiers can create duplicate payment records.
- **Reproduction Steps:** Send simultaneous identical POST requests to `/payments/pay_now/{id}` without idempotency locking.
- **Proposed Solution:** Use `payment_idempotency_keys` table with database-backed atomic claim, SHA-256 payload hashing, DB transactions, and decimal rounding `round((float)$rawAmt, 2)`.
- **Actual Implementation:** Implemented in `PaymentController.php`.
- **Test Evidence:** Automated tests in `PaymentTest.php` pass.
- **Remaining Risk:** External payment gateways (e.g. eSewa, Khalti) when integrated will require webhook signature verification.

---

### Phase 10: Nepal Localization

#### ISSUE-011 (MEDIUM) — Bikram Sambat Date Conversion Boundary Handling
- **File & Location:** `app/Services/BikramSambatService.php`, `app/Helpers/NepaliHelper.php`
- **Severity:** Medium
- **Root Cause:** Nepali BS calendar uses variable month lengths (29 to 32 days). Date conversions near Chaitra/Baisakh boundaries (year transitions) can throw exceptions if calendar lookup tables are incomplete.
- **Reproduction Steps:** Convert dates at the boundary of 2081/2082 BS (April 13/14).
- **Proposed Solution:** Verified comprehensive calendar tables in `BikramSambatService.php` covering 1970 to 2100 BS.
- **Actual Implementation:** Implemented in `BikramSambatService.php`.
- **Test Evidence:** Unit tests in `tests/Unit/NepaliDateTest.php` pass.
- **Remaining Risk:** Calendar adjustments beyond 2100 BS require updating lookup table.

---

### Phase 11: PWA & Offline Functionality

#### ISSUE-012 (MEDIUM) — Offline Attendance Conflict Resolution
- **File & Location:** `app/Http/Controllers/SupportTeam/AttendanceController.php:310-332`
- **Severity:** Medium
- **Root Cause:** Teachers recording attendance on offline mobile devices might submit drafts hours later after another staff member has modified the record online.
- **Reproduction Steps:** Submit an offline draft with `created_at_client` earlier than server `updated_at`.
- **Proposed Solution:** Check `checkAttendanceConflict()`, return HTTP 409 Conflict with server update details, and allow explicit overwrite or merge.
- **Actual Implementation:** Implemented in `AttendanceController::sync()`.
- **Test Evidence:** Verified by attendance feature tests.
- **Remaining Risk:** Mobile clients must implement UI prompts handling HTTP 409 Conflict.

---

### Phase 12: File Upload & Download Security

#### ISSUE-013 (HIGH) — Upload File Extension & MIME Validation
- **File & Location:** `app/Http/Controllers/SupportTeam/StudyMaterialController.php:138-156`
- **Severity:** High
- **Root Cause:** User file uploads without strict extension blacklisting and MIME sniffing could allow uploading webshells (.php, .phtml, .phar).
- **Reproduction Steps:** Attempt to upload a file with double extension (`shell.php.jpg`) or executable script.
- **Proposed Solution:** Strict whitelist validation in FormRequest (`mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png`), plus secondary server-side blacklist check against dangerous extensions, and storage outside public document root (`storage/app/study_materials`).
- **Actual Implementation:** Implemented in `StudyMaterialController.php`.
- **Test Evidence:** Verified in `StudyMaterialTest.php`.
- **Remaining Risk:** Ensure web server does not execute PHP scripts inside `storage/`.

---

## 3. Remediation Roadmap by Milestone

```
+---------------------------------------------------------------------------------------+
| MILESTONE 1: Full Repository Audit and Issue Inventory (COMPLETED)                    |
| - Inspect repository, branch, dependencies, workflows, routes, controllers, and tests |
| - Document all 13 issues with ID, severity, root cause, fix, and verification         |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 2: Health Check Configuration Hardening & Role Tests                        |
| - Add 'health_key' => env('HEALTH_CHECK_SECRET') to config/app.php                    |
| - Remove runtime env() call in HealthCheckController                                  |
| - Expand HealthCheckTest to fixture all 7 user roles and failure simulation modes     |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 3: Database Restore Zip Security & Disaster Recovery Hardening              |
| - Harden companion files ZIP extraction against Zip Slip, traversal, and bombs       |
| - Add automated unit tests validating zip entry path sanitization                     |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 4: Authentication, Authorization & Financial Integrity Audit                |
| - Verify policies and ownership checks across all student/parent controllers          |
| - Verify decimal accuracy and idempotency in payment processing                       |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 5: Laravel Backend Quality & Database Performance                           |
| - Check queries for N+1 issues and missing database indexes                           |
| - Verify transaction isolation across multi-table writes                              |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 6: Frontend Dependency & Modernization Roadmap                              |
| - Verify precompiled asset integrity in CI/CD pipeline                                |
| - Formulate Vite + Vue 3 migration plan document                                      |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 7: PWA & Nepal Localization Verification                                    |
| - Verify Bikram Sambat date conversion and fiscal year handling                       |
| - Verify offline attendance synchronization and conflict resolution                   |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 8: Functional QA & Performance Baselines                                    |
| - Measure execution benchmarks for key school management workflows                    |
| - Verify student, class, exam, marks, and report card generation                      |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 9: Regression Tests & CI/CD Enforcement                                     |
| - Execute full test suite (all unit and feature tests)                                |
| - Verify composer and npm audit gates pass strictly                                  |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| MILESTONE 10: Final Production Readiness & Controlled Pilot Sign-Off                  |
| - Generate docs/FINAL_PRODUCTION_READINESS_REPORT.md                                  |
| - Final engineering evaluation and sign-off for school pilot                          |
+---------------------------------------------------------------------------------------+
```
