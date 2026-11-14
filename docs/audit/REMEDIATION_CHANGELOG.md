# Engineering Remediation Changelog

**Project:** SMS Laravel — School Management System  
**Repository:** `mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Latest Baseline:** `30a2a09`  

---

## Commit `30a2a09`: Security Hardening (Parent Tokens, Teacher Authorization, MIME Uploads)
- **Parent Account Activation:**
  - Implemented cryptographic SHA-256 token hashing (`users.activation_token` stores `hash('sha256', $rawToken)`).
  - Enforced atomic single-use token consumption in `ParentActivationController` to prevent concurrent race condition reuse.
- **Teacher Section Isolation:**
  - Enforced strict teacher authorization in `StudentRecordController@show` and `StudentRecordController@listByClass`.
  - Restricts teachers from viewing or enumerating student rosters outside their assigned subjects/sections.
- **MIME Upload Hardening:**
  - Modified `app/Helpers/Qs.php::getFileMetaData()` to inspect server-side MIME type via `$file->extension()` rather than relying on client-supplied extensions.
  - Hardened `BookController@uploadImage` to validate image MIME types.
- **Audit Logging Redaction:**
  - Added `'activation_token'` and `'temp_password'` to `AuditLogService::REDACTED_KEYS`.
- **Automated Tests:**
  - Added `tests/Feature/StudentRecordAuthorizationSecurityTest.php` (7 tests, 15 assertions).
  - Expanded `tests/Feature/ParentAccountManagementTest.php` with 17 tests, 120 assertions.

---

## Commit `d813cb1`: Student Admission Column Capacity & Collision Protection
- **Issue:** HTTP 500 error on `/students/create` caused by `SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'adm_no'`.
- **Database Migration:**
  - Created migration `database/migrations/2026_11_14_210000_expand_adm_no_length_on_student_records_table.php` expanding `adm_no` from `VARCHAR(30)` to `VARCHAR(100)`.
- **Controller Logic:**
  - Implemented unique candidate collision generation in `StudentRecordController::createRecord()`.
  - Added 422 JSON validation response for conflicting admission numbers.
- **Automated Tests:**
  - Added `tests/Feature/StudentAdmissionStoreTest.php` (3 tests, 16 assertions).

---

## Commit `0c6e0b7`: CI/CD Workflow Setup-PHP Cache Configuration
- **Issue:** Workflow failed with `Invalid action input 'cache' @ cd.yml:L62`.
- **Fix:** Removed unsupported `cache: composer` parameter from `shivammathur/setup-php` across `.github/workflows/ci.yml` and `.github/workflows/cd.yml`.
- Standardized dependency caching using `actions/cache@v4`.

---

## Commit `ecc3a55`: CD Deployment Safety & Archive Verification
- **Automated Tests:** Added `tests/Unit/CdDeploymentSafetyTest.php` (6 tests, 71 assertions) verifying atomic symlink rollback logic.
- **Migration Safety Gate:** Validated migration safety policy in `tests/Unit/CiSecurityPolicyTest.php`.
- **Archive Extraction:** Hardened tar unpacking on remote CD targets with `--no-same-owner`.

---

## Commit `1fff469` & `d535a5e`: Strict CI Gates & Archive Integrity
- Added strict tar checksum and end-of-archive validation in `.github/scripts/verify-release-archive.php`.
- Hardened `.github/scripts/verify-ci-gate.php` with fail-closed pagination and rerun safety.
- Made `.github/scripts/check-migration-safety.php` run in strict mode by default.
- Removed error suppression (`|| true`) from deployment rollback paths.
