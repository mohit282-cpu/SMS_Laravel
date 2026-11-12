# SMS Academy — Repository Discovery & Remediation Baseline

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Current Commit SHA:** `57cf4850dfa3c491e5b0381a0465a0321f9892db`  
**Audit Date:** 2026-11-12  
**Product:** SMS Academy — School Management System for Nepal  
**Organization:** Sovryx Tech Pvt. Ltd.  

---

## 1. Runtime & Dependency Baseline

### 1.1 Runtime Versions (Observed Locally)
- **PHP:** `8.2.31` (ZTS Visual C++ 2019 x64)
- **Composer:** `2.10.3`
- **Node.js (Local Audit Environment):** `v24.16.0` (CI Frontend Build pinned to Node `14`; CI Audit Gate runs on Node `22`)
- **npm:** `11.13.0`
- **Database:** MySQL / MariaDB (`mysql_test` on `127.0.0.1:3306`)

### 1.2 Key Dependency Versions
- **Backend (`composer.json` / `composer.lock`):**
  - `laravel/framework`: `8.83.29` (`^8.40`, EOL; covered by time-bound exception expiring `2026-12-31`)
  - `barryvdh/laravel-dompdf`: `^2.0` (`2.2.0` locked; wraps `dompdf/dompdf` < 3.1.6)
  - `ernilambar/nepali-date`: `^1.0`
  - `hashids/hashids`: `^4.1.0`
  - `minishlink/web-push`: `^11.0`
  - `guzzlehttp/guzzle`: `^7.0.1`
  - `phpunit/phpunit`: `^9.3.3` (`9.6.38` installed)
- **Frontend (`package.json`):**
  - `laravel-mix`: `^2.0` (`2.1.14` locked, Webpack 3, `node-sass` 4.x)
  - `vue`: `^2.5.7`
  - `bootstrap`: `^4.0.0`
  - `axios`: `^0.18`
  - `jquery`: `^3.2`
  - `lodash`: `^4.17.5`
  - Runtime npm dependencies: `0` (all packages reside in `devDependencies`)

---

## 2. Existing CI & Test Status (Observed at `57cf485`)

| Check | Command / Job | Result | Details |
|---|---|---|---|
| **PHPUnit Full Suite** | `vendor/bin/phpunit` | **PASS** | `423 tests, 2822 assertions` in `50.764s` (PHPUnit 9.6.38; emits informational deprecated XML schema warning). |
| **Composer Security Gate** | `php .github/scripts/audit-checker.php` | **PASS** | `12` live advisories (`1` critical, `1` high, `6` medium, `4` low); all `7 of 7` documented exceptions applied (`reviewDue: 2026-12-31`). |
| **npm Security Gate** | `node .github/scripts/npm-audit-checker.js` | **FAIL (Exit 1)** | `79` high/critical findings (`33` critical, `46` high). Fails on `node-forge@0.10.0`: `1 new/changed and 1 stale finding(s)` due to upstream GHSA alias drift (e.g. `GHSA-CFM4-QJH2-4765`). |
| **GitHub Actions Run `43166314530`** | CI Workflow | **FAIL** | PHP 8.2 / Tests passed; Frontend build (Node 14) passed; npm audit gate (Node 22) failed; CI Integration Gate failed downstream. |

---

## 3. Confirmed Issues (Classified P0–P3)

| ID | Severity | Area | Affected File(s) | Problem & Impact | Proposed Fix |
|---|---|---|---|---|---|
| **BASE-001** | **P0** | Database / File Security | [`app/Console/Commands/DatabaseRestoreCommand.php`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Console/Commands/DatabaseRestoreCommand.php#L438-L444) | `restoreCompanionFiles()` calls `$zip->extractTo($extractTarget)` without validating archive entries. Vulnerable to Zip Slip (`../`), absolute paths, symlink attacks, and zip bombs. | Inspect every `ZipArchive` entry before extraction; reject `..`, absolute paths, encoded separators, symlinks; enforce canonical destination, max entry count, max archive/uncompressed size; clean up on failure. |
| **BASE-002** | **P1** | CI / Security Gate | [`.github/security/npm-exceptions.json`](file:///z:/Xampp/htdocs/SMS_Laravel/.github/security/npm-exceptions.json), [`.github/scripts/npm-audit-checker.js`](file:///z:/Xampp/htdocs/SMS_Laravel/.github/scripts/npm-audit-checker.js) | Current CI failure (`run 43166314530`): `npm-audit-checker.js` exits 1 because GitHub Advisory DB updated GHSA aliases for `node-forge@0.10.0` (dev-only `webpack-dev-server` transitive dep), causing exact baseline mismatch. | Audit the exact delta in `npm audit --json`, verify `node-forge@0.10.0` remains dev-only/non-runtime, update or remediate compatible transitive chain if safe without `--force`, and document in `npm-exceptions.json` with owner & expiry. |
| **BASE-003** | **P1** | Health Checks & Config | [`config/app.php`](file:///z:/Xampp/htdocs/SMS_Laravel/config/app.php), [`app/Http/Controllers/HealthCheckController.php`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Http/Controllers/HealthCheckController.php#L263), [`routes/web.php`](file:///z:/Xampp/htdocs/SMS_Laravel/routes/web.php#L15) | `HealthCheckController` uses `config('app.health_key') ?: env('HEALTH_CHECK_SECRET')`. `config/app.php` lacks `'health_key'`, so under `php artisan config:cache` `env()` returns `null` and token auth fails. `/health/diagnostic` lacks `throttle` and `no_store` middleware. | Add `'health_key' => env('HEALTH_CHECK_SECRET')` to `config/app.php`, remove runtime `env()` call, fail closed when unset, add `throttle` + `no_store` middleware on `/health/diagnostic`. |
| **BASE-004** | **P1** | Deployment / Rollback | [`.github/workflows/cd.yml`](file:///z:/Xampp/htdocs/SMS_Laravel/.github/workflows/cd.yml#L184-L215) | `rollback_handler()` uses `|| true` on `config:cache`, `route:cache`, `view:cache`, and `curl` (`RESTORE_HTTP_CODE=$(curl ... || true)`) without failing if rollback verification fails or checking `readlink -f`, required files, `php artisan --version`, PHP-FPM active state, or `"ready"` JSON status. | Remove `|| true` from critical verification checks; explicitly verify previous release files, symlink target, Laravel bootstrap, PHP-FPM active state, and HTTP 200 `"ready"` status; fail loud if rollback fails. |
| **BASE-005** | **P1** | Database Backup | [`app/Console/Commands/DatabaseBackupCommand.php`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Console/Commands/DatabaseBackupCommand.php#L1-L286) | Missing `use Throwable;` import causes `catch (Throwable $e)` (L126) to reference non-existent `App\Console\Commands\Throwable`. Uses `@gzwrite`/`@fwrite`. Backup & metadata files lack `0600` permissions. `.meta.json` omits `app_version`. Catch block (L284) does not redact passwords. | Import `use Throwable;`, remove `@` error suppression, apply `chmod(..., 0600)`, include `app_version`/`laravel_version` in `.meta.json`, verify gzip readability, and sanitize credentials in error messages. |
| **BASE-006** | **P1** | Validation / Backend Bug | [`app/Http/Requests/Student/StudentRecordUpdate.php`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Http/Requests/Student/StudentRecordUpdate.php#L27) | Rule `'email' => 'sometimes|nullable|email|max:100|unique:users,id'` validates email against `users.id` instead of `users.email` ignoring the student's `user_id`. Updating to an existing email causes a SQLSTATE 23000 crash. | Resolve student's `user_id` from route parameter and enforce `Rule::unique('users', 'email')->ignore($userId)`. |
| **BASE-007** | **P1** | Dependency Lifecycle | [`composer.json`](file:///z:/Xampp/htdocs/SMS_Laravel/composer.json), [`.github/security/composer-exceptions.json`](file:///z:/Xampp/htdocs/SMS_Laravel/.github/security/composer-exceptions.json) | `laravel/framework` 8.83.29 (EOL) and `dompdf/dompdf` < 3.1.6 rely on 7 risk-accepted exceptions expiring `2026-12-31` (49 days from audit date). | Author `docs/LARAVEL_MODERNIZATION_ROADMAP.md` detailing staged upgrade path (Laravel 8 -> 9 -> 10/11), package compatibility, and Vite migration. |
| **BASE-008** | **P2** | QA / Health Tests | [`tests/Feature/HealthCheckTest.php`](file:///z:/Xampp/htdocs/SMS_Laravel/tests/Feature/HealthCheckTest.php#L70-L107) | Role tests silently pass via `else { $this->assertTrue(true); }` when DB is unseeded. Missing tests for Admin, Teacher, Parent, Accountant, Librarian, maintenance mode, DB/cache/storage/manifest failures, and cached config. | Replace conditional DB queries with deterministic user fixtures for all 7 roles; add failure-mode, secret, bearer, and `config:cache` tests. |
| **BASE-009** | **P2** | Input Validation / Models | [`app/Models/Skill.php`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Models/Skill.php#L9), SupportTeam controllers | `Skill::$fillable` is commented out; multiple controllers (`GradeController`, `MyClassController`, `SectionController`, `SubjectController`, `PaymentController::store/update`, `TimeTableController`) pass raw `$req->all()` instead of `$req->validated()` or explicit allowlists. | Define `$fillable` on `Skill.php` and replace `$req->all()` with validated/explicit field arrays. |
| **BASE-010** | **P2** | File Upload Security | `MyAccountController.php`, `StudentRecordController.php`, `UserController.php`, `SettingController.php` | Image uploads use predictable static names (`photo.<clientExt>`, `logo.<clientExt>`) via `getClientOriginalExtension()`. | Use `$file->guessExtension()` and server-generated random filenames (`Str::random()`). |
| **BASE-011** | **P3** | Test Configuration | [`phpunit.xml`](file:///z:/Xampp/htdocs/SMS_Laravel/phpunit.xml) | PHPUnit 9.6.38 emits deprecated XML schema validation warning on every test run. | Review `phpunit.xml` schema compatibility during Milestone 10 / Laravel modernization planning. |

---

## 4. Potential Issues Requiring Reproduction & Verification

1. **IDOR & Cross-Role Scope Audit (Phase 2 / Milestone 6):** Verify all endpoints accepting student, parent, teacher, payment, invoice, exam, attendance, and document IDs (`MarkController`, `ExamController`, `BookRequestController`, `ParentController`, `UserController`, `PaymentController`, `StudentRecordController`) for missing ownership checks or predictable sequential IDs.
2. **Financial Rounding & Atomic Reconciliation (Phase 7 / Milestone 7):** Audit `PaymentController` and `PaymentRecord` calculations to ensure decimal/integer precision without float drift during partial payments, discounts, and receipt generation.
3. **BS/AD Boundary & Fiscal Year Calculations (Phase 9 / Milestone 9):** Reproduce boundary conversions (e.g. Chaitra 30/31 <-> Baisakh 1, Shrawan 1 Nepal fiscal year boundary) in `BikramSambatService` and `NepaliDateService`.
4. **Performance & N+1 Profiling (Phase 12 / Milestone 10):** Profile dashboard, mark-sheet tabulation, and attendance matrix queries for N+1 loops and missing indexes.
5. **Documentation Accuracy Audit (Phase 15 / Milestone 10):** Audit `docs/` for overstated claims ("100% secure", "fully production-ready") and align all reports with verified commit evidence.

---

## 5. Existing Test Coverage Summary

- **Total Test Files:** 52 (`7` Unit test files, `45` Feature test files)
- **Observed Baseline:** `423` tests, `2,822` assertions passing (`0` failures, `0` errors)
- **Covered Domains:**
  - Auth, Password & API Guard Security (`ApiGuardSecurityTest`, `PasswordManagementSecurityTest`, `PublicPortalSecurityTest`, `ExceptionHandlingSecurityTest`)
  - Roles & Module Authorization (`MarksAuthorizationTest`, `EventAuthorizationAndIntegrityTest`, `GraduationSecurityTest`, `StudentRecordDestroySecurityTest`, `StudyMaterialSecurityTest`, `TimeTableSecurityTest`)
  - Financial & Idempotency (`DatabasePaymentIdempotencyTest`, `PaymentHardeningTest`, `PaymentNepalYearTest`, `NepaliMonthlyBillingTest`)
  - Nepal Localization (`BikramSambatServiceTest`, `NepalGradingServiceTest`, `NepaliDateEngineTest`, `NepaliCalendarTest`, `LocalizationTest`)
  - PWA & Push Security (`PwaFeatureTest`, `PwaJobRetryTest`, `PwaPushNotificationTest`, `PwaSecurityRemediationTest`, `PwaVapidConfigurationTest`, `PushSsrfSecurityTest`)
  - Backup & Recovery (`DatabaseBackupRestoreTest`, `DisasterRecoveryDrillTest`, `DatabaseSafetyGuardTest`)
  - Routing & Health (`RouteRegistrationDatabaseIsolationTest`, `RouteSmokeTest`, `HealthCheckTest`)

---

## 6. Milestone Execution Plan

- **Milestone 1 (Current):** Repository discovery and baseline audit (`docs/REMEDIATION_BASELINE.md`).
- **Milestone 2:** Fix current CI `npm-audit-checker.js` failure (`BASE-002`) with justified security review.
- **Milestone 3:** Fix ZIP extraction security in `DatabaseRestoreCommand.php` (`BASE-001`) and `DatabaseBackupCommand.php` hardening (`BASE-005`).
- **Milestone 4:** Fix Health Check configuration (`config/app.php`, `HealthCheckController.php`, `routes/web.php`) and expand `HealthCheckTest.php` (`BASE-003`, `BASE-008`).
- **Milestone 5:** Fix `cd.yml` rollback verification (`BASE-004`).
- **Milestone 6:** Authentication, authorization, and IDOR verification across all 7 roles.
- **Milestone 7:** Financial integrity, `StudentRecordUpdate` email validation (`BASE-006`), mass-assignment (`BASE-009`), and upload hardening (`BASE-010`).
- **Milestone 8:** Dependency & frontend modernization roadmaps (`BASE-007`).
- **Milestone 9:** Nepal localization & PWA private-data verification.
- **Milestone 10:** Performance profiling, documentation accuracy cleanup, and `docs/FINAL_REMEDIATION_REPORT.md`.

