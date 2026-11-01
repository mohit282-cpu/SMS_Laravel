# REMEDIATION CHANGELOG
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Period:** October - November 2026

---

## [Unreleased / Current Remediation Cycle]

### Fixed
- **Student Record Deletion Safety (`app/Http/Controllers/SupportTeam/StudentRecordController.php`):**
  - Resolved fatal error on null dereference `$sr->user` in `StudentRecordController::destroy()`.
  - Added data retention guards blocking student deletion if payment history or examination marks exist.
  - Wrapped draft deletion in atomic database transaction with attendance cleanup and directory deletion.
  - Added audit logging via `AuditLogService`.
- **Password Exposure Elimination (`StudentRecordController.php` & `UserController.php`):**
  - Removed plaintext temporary password concatenation from `flash_success` message string in `StudentRecordController::reset_pass()` and `UserController::reset_pass()`.
- **TokenGuard Vulnerability Elimination (`config/auth.php`):**
  - Migrated `api` guard driver from insecure `'token'` to `'session'`, closing CVE-2026-114509.
- **Financial Concurrency & Decimal Standardization (`PaymentController.php` & Migrations):**
  - Migrated monetary quantities in `payments`, `payment_records`, and `receipts` to `decimal(12,2)`.
  - Added `payment_idempotency_keys` table with 24h expiration preventing duplicate billing submissions.
- **Parent Account Activation Security (`ParentActivationController.php`):**
  - Implemented 64-character cryptographic tokens with 7-day expiration and atomic single-use invalidation.
- **Offline Attendance Idempotency (`AttendanceController.php` & Migrations):**
  - Added unique composite key `(student_record_id, attendance_date)` and idempotent sync endpoint.
  - Enforced teacher class-assignment verification on attendance submissions.

### Added
- **Automated Regression Test Suite (`tests/Feature/StudentRecordDestroySecurityTest.php`):**
  - Verified student destruction permissions, invalid/missing ID handling, retention rules, file cleanup, and audit logging.
- **Password Reset Verification Updates (`tests/Feature/PasswordManagementSecurityTest.php`):**
  - Verified `flash_success` does not leak temporary passwords.
- **Documentation Reports:**
  - `docs/COMPLETE_AUDIT_REPORT.md`
  - `docs/BUG_FIX_REPORT.md`
  - `docs/SECURITY_AUDIT_REPORT.md`
  - `docs/QA_TEST_REPORT.md`
  - `docs/DATABASE_AUDIT_REPORT.md`
  - `docs/PERFORMANCE_REPORT.md`
  - `docs/NEPAL_LOCALIZATION_REPORT.md`
  - `docs/PWA_AUDIT_REPORT.md`
  - `docs/CI_CD_REPORT.md`
  - `docs/PRODUCTION_READINESS.md`
  - `docs/REMEDIATION_CHANGELOG.md`

### Verified
- Automated test suite: 439 tests (398 feature tests, 41 unit tests), 0 failures, 0 errors.
- Migration fresh, rollback, re-application, and seeding: 45 migrations passed cleanly.
- Route loading and route caching: `php artisan route:list` and `php artisan route:cache` passed with exit code 0.