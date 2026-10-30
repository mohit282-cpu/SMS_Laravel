# CI/CD Audit and Remediation Report

- **Repository:** `SMS_Laravel` (Laravel 8.83 school management system)
- **Branch audited:** `testing`
- **Audit date:** 2026-10-30
- **Scope:** `.github/workflows`, security gates, test isolation, migration lifecycle, frontend build, documentation
- **Constraint:** no deployment workflow was created; hosting details are unknown and were not invented.

---

## 1. Executive summary

The pipeline looked complete but several gates were ineffective or actively broken:

- The npm audit gate was a **no-op**. It only failed when `package.json.dependencies` was non-empty, and that object is empty. It ignored **33 critical and 46 high** vulnerabilities in the build toolchain.
- The database safety guard treated "running under PHPUnit" as proof that the database was disposable, so it could never fail. A misconfigured run could have targeted the development database.
- `migrate:rollback` could not complete. `2019_09_22_142514_create_fks.php` had an empty `down()`, and the library module dropped parent columns before child foreign keys.
- The test step used `php artisan test --log-junit`, an option this Laravel 8 installation does not support, so the configured JUnit report was never produced.
- The Composer allowlist had no expiry, no stale-entry detection, no wildcard rejection, and contained at least one demonstrably false rationale (TokenGuard was described as unused while the `api` guard is `driver=token`).
- The test suite itself failed on a clean database because one test hard-coded `exam_id = 1`.

All of the above are fixed and verified locally. The pipeline now fails closed, verifies rollback, proves the target database is disposable, uploads reports, and separates the Node 14 build from a modern Node audit. **CD is still not configured**, by design.

---

## 2. Defect register

Severity reflects impact on production safety, security, or the ability of CI to prove a change is safe.

| ID | Severity | Area | Defect | Impact | Fix |
| --- | --- | --- | --- | --- | --- |
| CI-01 | **Critical** | Test isolation | `DatabaseSafetyGuard` accepted `app()->runningUnitTests()` as proof of a disposable database | A local or CI run could wipe the development database | Added `app/Support/Testing/DatabaseSafetyGuard.php`; requires non-production env, a `test` marker in the DB name, and rejects protected names. Wired into `tests/TestCase.php`. |
| CI-02 | **Critical** | npm security gate | Gate only checked that `package.json.dependencies` was empty; `npm audit` findings were printed but never enforced | 33 critical / 46 high devDependency findings could ship without review | Rewrote `npm-audit-checker.js`, added `npm-audit-lib.js`, version-pinned baseline in `.github/security/npm-exceptions.json`, and a generator that is never run in CI. |
| CI-03 | **High** | Composer security gate | Allowlist had no owner/expiry/staleness/wildcard checks and accepted advisory IDs by loose matching; rationales were partly false | Accepted risk could grow silently; a resolved advisory stayed allowlisted forever | Rewrote `audit-checker.php` as a fail-closed policy validator; added `.github/security/composer-exceptions.json` with package + advisory IDs, owner, expiry, scope, and compensating controls. |
| CI-04 | **High** | Migration rollback | `create_fks.php::down()` was empty; library module dropped referenced columns before foreign keys | `migrate:rollback` failed with MySQL error 1451, so a bad deploy could not be rolled back | Implemented `down()` for every foreign key; fixed the library migration to drop `book_requests` foreign keys first. |
| CI-05 | **High** | Test suite | `ParentAccountManagementTest` inserted marks/exam records with hard-coded `exam_id = 1` | Clean-database CI run failed on `exam_records_exam_id_foreign` | Test now creates an `Exam` and uses `$exam->id`. |
| CI-06 | **High** | Test reporting | `php artisan test --verbose --log-junit ...` used an unsupported option | JUnit artifact never generated; failure diagnostics were empty | Switched to `vendor/bin/phpunit --log-junit build/reports/junit.xml`, creates the directory first, uploads the report. |
| CI-07 | **Medium** | Workflow robustness | No `workflow_dispatch`, no `timeout-minutes`, no artifact upload, no failure diagnostics, no rollback verification, no unsupported-option guard | Hung jobs consume minutes; failures are hard to diagnose; rollback defects reach production | New `ci.yml` adds manual dispatch, per-job timeouts, JUnit + log artifacts, failure diagnostics, and a fresh → rollback → migrate → seed lifecycle check. |
| CI-08 | **Medium** | Configuration validation | CI never proved that `config:cache` works; route loading was only checked after clearing config | A config value that cannot be cached would only fail during deployment | CI now runs `config:cache` and then `config:clear`, then `route:list`. |
| CI-09 | **Medium** | MySQL service readiness | Health check lacked a start period; connection retry was ad hoc | Flaky first-run failures | MySQL 8.0 service now has health check with start period and retries; a dedicated database-safety script also proves live connectivity. |
| CI-10 | **Low** | Documentation drift | README claimed PHP 8.1 support while `composer.lock` requires >= 8.2; `.env.example` omitted `DB_TEST_HOST/PORT/USERNAME/PASSWORD` | Developers install the wrong runtime; tests silently use the wrong credentials | README updated to PHP 8.2 only; `.env.example` now documents all test database variables. |
| CI-11 | **Low** | Repository hygiene | Generated `_ide_helper.php` and `.phpstorm.meta.php` were committed | Noise and drift in generated files | Removed; `/build` added to `.gitignore`. |

---

## 3. Security gate design

### Composer (`php .github/scripts/audit-checker.php`)

- Runs `composer audit --locked --format=json`.
- Exit code `0` = clean, `1` = advisories, `>1` = tool failure. A tool failure is a **CI failure**, never a pass.
- Validates the policy file itself: owner, expiry, non-empty reason, scope (`runtime` / `build`), advisory IDs.
- Fails when an advisory is not covered, an exception is expired, an exception is stale (no longer matches a live advisory), an entry is duplicated, or a wildcard is used.
- A `build` exception cannot cover a runtime package.
- Current live result: **13 advisories** (1 critical, 1 high, 7 medium, 4 low), all covered by **8 documented package-level exceptions**, expiry **2026-12-31**.

### npm (`node .github/scripts/npm-audit-checker.js`)

- Runs on Node 22 / npm 10 in a dedicated job because npm 6 (Node 14) does not emit stable machine-readable audit output.
- Fails if `npm audit` cannot run or does not return parsable JSON.
- Runtime dependencies must have zero high/critical findings; the repository declares **zero runtime npm dependencies**, so the build-time baseline can never cover runtime code.
- High/critical findings must match the version-pinned baseline in `.github/security/npm-exceptions.json` exactly. New, changed, or stale findings all fail.
- Current live result: **79 high/critical findings** (33 critical, 46 high) plus 56 moderate / 6 low, all devDependencies, all explicitly accepted with a review date of **2026-12-31**.

Why the baseline exists: the frontend is pinned to Laravel Mix 2 / Webpack 3 / node-sass 4 / Vue 2, which only builds on Node 14. Fixing the findings requires replacing the toolchain, not bumping a version. The migration plan and exit criteria are in [`frontend-modernization.md`](frontend-modernization.md).

---

## 4. Pipeline design

```text
push / PR / workflow_dispatch
├── php-tests (Ubuntu 22.04, PHP 8.2, MySQL 8.0 service)
│   ├── composer validate --strict
│   ├── composer install (cached by setup-php)
│   ├── Composer audit gate
│   ├── PHP syntax lint (app bootstrap config database routes tests)
│   ├── .env + key:generate
│   ├── config:cache → config:clear
│   ├── route:list
│   ├── verify-ci-database.php (refuses non-local host, non-test name, production env)
│   ├── migrate:fresh → migrate:rollback → migrate → db:seed
│   ├── vendor/bin/phpunit --log-junit
│   └── upload JUnit + laravel.log on every run
├── frontend-build (Ubuntu 22.04, Node 14)
│   ├── npm ci
│   ├── npm run prod
│   ├── assert public/js/app.js and public/css/app.css
│   └── upload compiled assets
└── npm-audit (Ubuntu 22.04, Node 22)
    └── npm audit gate
```

Design notes:

- `permissions: contents: read` is the workflow default.
- Concurrency cancels superseded branch/PR runs but does not cancel `main`.
- Every job has a timeout.
- No job deploys anything.
- No secrets are printed; the CI MySQL password is a throwaway service credential.
- `shivammathur/setup-php` and the first-party `actions/*` steps are pinned to commit SHAs (setup-php v2.36.0).

---

## 5. Verification evidence

Executed locally on 2026-10-30 with PHP 8.2.31, Composer 2.10.3, MariaDB 10.4.32, Node 14.21.3 and Node 22.23.4.

| Command | Result |
| --- | --- |
| `composer validate --strict` | `./composer.json is valid` |
| `composer install --prefer-dist --no-interaction --no-progress` | exit 0 |
| `php .github/scripts/audit-checker.php` | passed: 13 live advisories, 8/8 exceptions applied |
| PHP lint over `app bootstrap config database routes tests` | 314 files, 0 errors |
| `php artisan config:cache` → `config:clear` | cached and cleared successfully |
| `php artisan route:list` | routes load |
| `php .github/scripts/verify-ci-database.php --connection=mysql_test` | passed: `mysql_test -> sms_laravel_test @ 127.0.0.1` |
| `php artisan migrate:fresh --force --database=mysql_test` | passed |
| `php artisan migrate:rollback --force --database=mysql_test` | passed (full rollback to zero migrations) |
| `php artisan migrate --force --database=mysql_test` | passed |
| `php artisan db:seed --force --database=mysql_test` | passed |
| `vendor/bin/phpunit --log-junit build/reports/junit.xml` | **OK (393 tests, 2595 assertions)** |
| `node .github/scripts/npm-audit-checker.js` (Node 22) | passed: 79 reviewed findings matched exactly |
| `npm ci` + `npm run prod` (Node 14) | passed; `public/js/app.js` 366 kB, `public/css/app.css` 178 kB |
| YAML parse of `.github/workflows/ci.yml` | valid; jobs `php-tests`, `frontend-build`, `npm-audit` |
| `git diff --check` | no whitespace errors (line-ending warnings only) |

The GitHub-hosted workflow itself was **not** executed from this machine (no push was performed). The commands above are the exact steps the workflow runs, executed locally against the same PHP/Node/MySQL versions.

---

## 6. Deployment readiness

- **CI:** ready, but only after the changes in this report are committed and the workflow runs once on GitHub.
- **CD:** **not configured.** There is no deployment workflow, no host, no SSH target, no cloud provider, and no environment definition in the repository. Creating one would require inventing infrastructure details.
- **Required before CD:** see [`deployment.md`](deployment.md) for the exact missing information.

---

## 7. Remaining risks

1. **Laravel 8 is end-of-life.** The critical TokenGuard advisory cannot be patched on the 8.x line. The compensating control is that no tokens are issued and `ApiGuardSecurityTest` enforces this. The real fix is the Laravel 9+/10 migration.
2. **Node 14 is end-of-life.** It is required only to build legacy assets. The build runs on an ephemeral runner, produces static files, and never exposes a Node runtime. Exit criteria are in `frontend-modernization.md`.
3. **Accepted npm findings are dev-only but numerous.** Any lockfile change can invalidate the baseline and will fail the gate until reviewed. That is intentional.
4. **Security exceptions expire 2026-12-31.** After that date both gates fail until the policy is renewed or the dependencies are fixed. A new advisory published after this audit also fails the gate by design; it must be triaged rather than silently accepted.
5. **No CD means no automated rollback.** CI proves migrations roll back; production rollback still depends on the hosting platform and backups.
6. **MySQL version difference.** CI uses MySQL 8.0; local development used MariaDB 10.4. Migration rollback was verified on MariaDB here and is exercised on MySQL in CI. The first GitHub run should confirm the MySQL path.

---

## 8. Reproduce locally

```bash
composer validate --strict
composer install --prefer-dist --no-interaction --no-progress
php .github/scripts/audit-checker.php

# .env must point mysql_test at a database whose name contains "test"
php .github/scripts/verify-ci-database.php --connection=mysql_test
php artisan migrate:fresh --force --database=mysql_test
php artisan migrate:rollback --force --database=mysql_test
php artisan migrate --force --database=mysql_test
php artisan db:seed --force --database=mysql_test

vendor/bin/phpunit --verbose --log-junit build/reports/junit.xml

# Node 22 for the audit gate
node .github/scripts/npm-audit-checker.js

# Node 14 for the legacy build
npm ci
npm run prod
```
