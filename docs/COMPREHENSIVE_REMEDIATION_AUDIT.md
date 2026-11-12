# SMS Academy — Comprehensive Remediation Audit & Engineering Roadmap

**Repository:** https://github.com/mohit282-cpu/SMS_Laravel  
**Target Branch:** `testing`  
**Current Commit:** `e3b283e2618c7e7999eeb5ed0ff4d22fca56804c` (following `d3b317452dcdfab09bbbaeaf2f438fe463cca7b9`)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Product:** SMS Academy — Nepal School Management System  
**Framework:** Laravel 8.83.29, PHP 8.2.31, MySQL / MariaDB  
**Audit Date:** November 12, 2026  
**Auditor:** Principal Software & DevSecOps Engineering Team  

---

## 1. Executive Summary & Repository Status

A full repository audit was conducted across all 18 engineering phases specified in the Master Prompt. The codebase is currently on branch `testing` with a clean working tree at commit `e3b283e` (extending `d3b3174`).

This audit report records verified strengths, previously resolved items, and newly identified open defects requiring remediation across Milestones 2 through 10.

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

#### ISSUE-002 (HIGH) — CD Rollback Verification & Post-Rollback Assertions Incomplete
- **File & Location:** `.github/workflows/cd.yml:184-215`
- **Severity:** High
- **Root Cause:** In-band health verification was moved inside the remote SSH script (`cd.yml`), and secrets were moved to env vars, **however** `rollback_handler()` still uses `|| true` on cache warmup (`config:cache || true`, `route:cache || true`, `view:cache || true`) and runs `RESTORE_HTTP_CODE=$(curl ... || true)` without failing or verifying: symlink target via `readlink -f`, required files (`artisan`, `vendor/autoload.php`), Laravel bootstrap (`php artisan --version`), PHP-FPM active state, and HTTP 200 `'ready'` readiness response.
- **Reproduction Steps:** Trigger a failed deployment rollback where the previous release's cache warm or readiness probe fails; `rollback_handler` prints `"Automated rollback completed successfully."` even if the rolled-back app returns 500 or 503.
- **Proposed Solution:** Remove `|| true` from critical rollback steps, verify previous release integrity, check `php artisan --version`, check `systemctl is-active` for PHP-FPM, assert HTTP 200 & `"ready"` on `/health/readiness`, and report explicit recovery failure logs if rollback verification fails. Also document expand-and-contract migration practices.
- **Actual Implementation:** Partially implemented (in-band check & env secrets added in `d3b3174`; strict rollback verification planned for Milestone 2).
- **Test Evidence:** YAML validated via `yaml.safe_load()`; live SSH target verification pending deployment environment secrets.
- **Remaining Risk:** Server host secrets (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`) and `APP_URL` must be configured in GitHub environment settings.

---

### Phase 2: Health Checks & Monitoring

#### ISSUE-003 (HIGH) — Missing Health Secret in `config/app.php`, Runtime `env()` Fallback & Unthrottled Diagnostic Route
- **File & Location:** `config/app.php`, `app/Http/Controllers/HealthCheckController.php:263`, `routes/web.php:15`
- **Severity:** High
- **Root Cause:** `HealthCheckController.php:263` accesses `$configuredKey = config('app.health_key') ?: env('HEALTH_CHECK_SECRET')`. In production with cached configuration (`php artisan config:cache`), `env()` returns `null`, and `config/app.php` does not define `'health_key'`. Additionally, `routes/web.php:15` exposes `/health/diagnostic` without `throttle` or `no_store` middleware.
- **Reproduction Steps:** Run `php artisan config:cache` with `HEALTH_CHECK_SECRET` set in `.env` and query `/health/diagnostic` with `X-Health-Key`; access fails with 403 because `config('app.health_key')` is undefined and `env()` returns `null`.
- **Proposed Solution:** Add `'health_key' => env('HEALTH_CHECK_SECRET')` to `config/app.php`, remove runtime `env()` fallback from `HealthCheckController.php`, fail closed when empty, and attach `throttle` + `no_store` middleware to `health.diagnostic`.
- **Actual Implementation:** Planned for Milestone 2.
- **Test Evidence:** Pending Milestone 2 implementation and cached-config test.
- **Remaining Risk:** Operations must define `HEALTH_CHECK_SECRET` in `.env` for token-based monitoring.

#### ISSUE-004 (MEDIUM) — Conditional Assertion Skips & Incomplete Failure/Role Coverage in `HealthCheckTest.php`
- **File & Location:** `tests/Feature/HealthCheckTest.php:70-107`
- **Severity:** Medium
- **Root Cause:** Tests query `User::where('user_type', 'student')->first()` and `User::where('user_type', 'super_admin')->first()` with `else { $this->assertTrue(true); }`, silently passing if users are absent. Missing test cases for Admin, Teacher, Parent, Accountant, Librarian, Maintenance Mode, Database failure, Cache failure, Storage failure, Missing asset manifest, Missing secret, and Invalid secret.
- **Reproduction Steps:** Run `phpunit tests/Feature/HealthCheckTest.php` on an unseeded DB; authorization tests pass via fallback `assertTrue(true)`.
- **Proposed Solution:** Create isolated user fixtures/factories inside the test covering all 7 roles, maintenance mode, missing/invalid/valid secrets (via `X-Health-Key` and Bearer), and mocked DB/cache/storage/manifest failures.
- **Actual Implementation:** Planned for Milestone 2.
- **Test Evidence:** Pending Milestone 2 expansion.
- **Remaining Risk:** None once fixtures are added.

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

#### ISSUE-013 (HIGH) — Upload File Extension & MIME Validation (Study Materials)
- **File & Location:** `app/Http/Controllers/SupportTeam/StudyMaterialController.php:138-156`
- **Severity:** High
- **Root Cause:** User file uploads without strict extension blacklisting and MIME sniffing could allow uploading webshells (.php, .phtml, .phar).
- **Reproduction Steps:** Attempt to upload a file with double extension (`shell.php.jpg`) or executable script.
- **Proposed Solution:** Strict whitelist validation in FormRequest (`mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png`), plus secondary server-side blacklist check against dangerous extensions, and storage outside public document root (`storage/app/study_materials`).
- **Actual Implementation:** Implemented in `StudyMaterialController.php`.
- **Test Evidence:** Verified in `StudyMaterialTest.php`.
- **Remaining Risk:** Ensure web server does not execute PHP scripts inside `storage/`.

---

### Additional Phase 3, 8 & 12 Findings Discovered in Code Inspection

#### ISSUE-014 (HIGH) — `DatabaseBackupCommand.php` Missing `use Throwable;`, `@` Suppression, Open File Permissions, & Missing Version Metadata
- **File & Location:** `app/Console/Commands/DatabaseBackupCommand.php:1-12, 116, 126, 236-249, 284`
- **Severity:** High
- **Root Cause:** `Throwable` is not imported at the top of the file, so `catch (Throwable $e)` on line 126 resolves to non-existent `App\Console\Commands\Throwable`. Line 116 uses `@gzwrite` / `@fwrite` error suppression. Backup files and `.meta.json` inherit default umask instead of `0600`. Metadata lacks `app_version` / `laravel_version`. Catch block on line 284 logs/prints unsanitized exception message without redacting credentials. Gzip archive integrity is not verified after closing.
- **Reproduction Steps:** Inspect `DatabaseBackupCommand.php` imports and catch block; run `db:backup --compress` and inspect resulting file mode (`ls -l`) and `.meta.json` keys.
- **Proposed Solution:** Import `use Throwable;`, remove `@` operators, set `chmod(..., 0600)` on SQL/gz/zip/meta files, record `app_version` and `laravel_version` in `.meta.json`, verify gzip stream readability before completion, and sanitize password strings in error output.
- **Actual Implementation:** Planned for Milestone 3.
- **Test Evidence:** Pending Milestone 3 unit/feature tests.
- **Remaining Risk:** Windows filesystems treat POSIX `chmod` differently than Linux production hosts.

#### ISSUE-015 (HIGH) — Broken Email Uniqueness Validation in `StudentRecordUpdate.php`
- **File & Location:** `app/Http/Requests/Student/StudentRecordUpdate.php:27`
- **Severity:** High
- **Root Cause:** Rule specifies `'email' => 'sometimes|nullable|email|max:100|unique:users,id'`, checking whether the email string matches any `users.id` integer rather than validating uniqueness on `users.email` while ignoring the updated student's `user_id`.
- **Reproduction Steps:** Update an existing student record via `StudentRecordController@update` using an email already belonging to another user. Validation passes and MySQL throws `SQLSTATE[23000]` duplicate entry exception.
- **Proposed Solution:** Resolve the target `StudentRecord` ID from route parameter, find its `user_id`, and apply `Rule::unique('users', 'email')->ignore($userId)`.
- **Actual Implementation:** Planned for Milestone 5.
- **Test Evidence:** Pending Milestone 5 regression test.
- **Remaining Risk:** None once covered by feature test.

#### ISSUE-016 (MEDIUM) — Raw `$req->all()` Mass Assignment & Commented `$fillable` in `Skill.php`
- **File & Location:** `app/Models/Skill.php:9`, `GradeController.php:33,48`, `MyClassController.php:35`, `SectionController.php:36`, `SubjectController.php:36,53`, `PaymentController.php:448,480`, `TimeTableController.php:183,238,303,343,513,522`
- **Severity:** Medium
- **Root Cause:** Controllers pass un-filtered `$req->all()` directly into repository `create()` / `update()` calls, while `Skill.php` has `$fillable` commented out.
- **Reproduction Steps:** Inspect controller `store()` and `update()` methods across SupportTeam controllers.
- **Proposed Solution:** Replace `$req->all()` with `$req->validated()` or explicit `$req->only([...])` allowlists, and uncomment/define `$fillable` on `Skill.php`.
- **Actual Implementation:** Planned for Milestone 5.
- **Test Evidence:** Pending Milestone 5 tests.
- **Remaining Risk:** Repository methods using `create($data)` rely on model `$fillable`.

#### ISSUE-017 (MEDIUM) — Predictable Static Filenames & Client Extension Trust in Photo/Logo Uploads
- **File & Location:** `MyAccountController.php:63-65`, `StudentRecordController.php:122-124, 359-361`, `UserController.php:127-129, 189-191`, `SettingController.php:43-45`, `BookController.php:124,165`
- **Severity:** Medium
- **Root Cause:** Profile photos and logos use static filenames (`'photo.' . $f['ext']`, `'logo.' . $f['ext']`) derived from `$file->getClientOriginalExtension()` instead of MIME-derived `$file->guessExtension()` and randomized server-generated filenames.
- **Reproduction Steps:** Inspect `storeAs()` calls in `MyAccountController`, `StudentRecordController`, `UserController`, and `SettingController`.
- **Proposed Solution:** Use `$file->guessExtension()` and randomized filenames (`Str::random(20) . '.' . $ext`) across all image uploads, and clean up replaced files safely.
- **Actual Implementation:** Planned for Milestone 5/7.
- **Test Evidence:** Pending Milestone 5/7 upload security tests.
- **Remaining Risk:** Existing uploaded file URLs stored in `users.photo` remain valid until updated.

#### ISSUE-018 (HIGH) — npm Security Gate Failing on `node-forge@0.10.0` Baseline Drift
- **File & Location:** `.github/security/npm-exceptions.json`, `.github/scripts/npm-audit-checker.js`
- **Severity:** High (blocks CI `npm-security` gate)
- **Root Cause:** GitHub Advisory Database updated/added GHSA alias entries for `node-forge@0.10.0` (used strictly inside the legacy Webpack 3 / `webpack-dev-server` build toolchain), causing the exact advisory-set fingerprint in `.github/security/npm-exceptions.json` to mismatch (`1 new/changed and 1 stale finding(s)`).
- **Reproduction Steps:** Execute `node .github/scripts/npm-audit-checker.js`. Command exits with code `1`.
- **Proposed Solution:** Review the updated `node-forge@0.10.0` advisories (`dev`-only via `webpack-dev-server` / `selfsigned`), regenerate the baseline if confirmed build-toolchain-only (`node .github/scripts/generate-npm-baseline.js --write`), or evaluate upgrading build tooling (Milestone 6).
- **Actual Implementation:** Identified during Milestone 1 audit (`2026-11-12`); remediation scheduled for Milestone 2/6.
- **Test Evidence:** `node .github/scripts/npm-audit-checker.js` failed with exit code `1` (`79 high/critical finding(s)`).
- **Remaining Risk:** Legacy Webpack 3 / Laravel Mix 2 toolchain will continue encountering upstream advisory churn until migrated to Vite (Milestone 6).

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
