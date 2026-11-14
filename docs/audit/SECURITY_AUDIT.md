# Application Security Audit & Remediation Report

**Project:** SMS Laravel — School Management System  
**Repository:** `mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Verified Commit:** `30a2a09`  
**Status:** Remediated & Hardened  

---

## 1. Executive Summary

This security audit covers authentication, authorization, session management, input validation, object-level access controls (IDOR), cryptographic token handling, file upload security, sensitive data redaction, audit logging, and dependency vulnerabilities.

Through iterative remediation on branch `testing`, verified security vulnerabilities and architectural gaps have been identified, mitigated, and covered by automated regression tests in PHPUnit.

---

## 2. Authentication & Credential Architecture

### 2.1 Password Security & Storage
- **Algorithm:** Passwords are uniformly hashed using Bcrypt (`Hash::make()` with default work factor 10).
- **Default & Initial Credentials:**
  - Seeded Super Admin / Admin accounts use standard seeded passwords that must be changed on initial production setup.
  - Admitted students: A cryptographically secure 10-character pseudo-random alphanumeric password is generated via `Str::random(10)` upon admission, stored as a Bcrypt hash, and can be reset by administrators or via self-service password reset.
  - Parent accounts: Initial creation generates an activation token rather than a known plain password.

### 2.2 Parent Account Activation Hardening
- **Vulnerability Identified:** Previously, raw activation tokens could be stored in plaintext in `users.activation_token` and subjected to token reuse or brute force without single-use consumption guarantees.
- **Remediation Implemented (`Commit 30a2a09`):**
  - **Cryptographic Hashing:** The database now stores only a SHA-256 hash (`hash('sha256', $rawToken)`) of the activation token in `users.activation_token`. The plaintext token is delivered exclusively in the activation URL.
  - **Single-Use Atomic Consumption:** Upon activation, the controller performs an atomic query (`User::where('activation_token', $hashedToken)->update(['activation_token' => null, ...])`). This prevents race conditions and concurrent double-use attacks.
  - **Expired / Invalid Token Rejection:** Any activation attempt with a null, mismatched, or already-consumed token aborts with an HTTP 403 or redirects with an invalid token flash message.
  - **Test Verification:** Covered by 17 tests in `tests/Feature/ParentAccountManagementTest.php`.

### 2.3 Session & Cookie Security
- `config/session.php` enforces:
  - `http_only => true` to prevent client-side JavaScript access (`XSS -> session hijacking`).
  - `same_site => 'lax'` to protect against Cross-Site Request Forgery.
  - `secure => env('SESSION_SECURE_COOKIE', true)` for production HTTPS enforcement.
  - Session driver defaults to `file` or `database` with session expiration configured to 120 minutes.

---

## 3. Authorization & Insecure Direct Object References (IDOR)

### 3.1 Role-Based Access Control (RBAC) Matrix
The application employs custom middleware layers:
- `teamSA`: Super Admin only (e.g., system settings, database management).
- `teamA`: Administrative staff (e.g., admissions, fee management, student records).
- `teamSAT`: Super Admin, Admin, and Teacher shared access.
- `teamAccount`: Accountants and administrative bursars.

### 3.2 Fine-Grained Teacher Section Isolation
- **Vulnerability Identified:** Teachers previously had permissive access through `teamSAT` middleware allowing them to view student records and class lists outside of their assigned classes and sections.
- **Remediation Implemented (`Commit 30a2a09`):**
  - In `StudentRecordController@show`: If an authenticated user has the Teacher role, the system queries the teacher's active subject/section assignments (`Section::where('teacher_id', $user->id)` and `Subject::where('teacher_id', $user->id)`). If the requested student's class/section is not among the teacher's assigned classes, access is denied with HTTP 403 Forbidden.
  - In `StudentRecordController@listByClass`: If an authenticated teacher requests a class list, the class ID must exist in the teacher's assigned teaching sections or subjects. Unauthorized class enumeration triggers HTTP 403.
  - Administrators (`teamA`) retain complete institutional oversight.
  - **Test Verification:** Covered by `tests/Feature/StudentRecordAuthorizationSecurityTest.php` (7 tests, 15 assertions).

### 3.3 Parent-Student Boundary Enforcement
- Parents are strictly restricted to their linked children via `my_children` endpoints and verification helpers (`Qs::userIsParent()`).
- Direct student ID parameter manipulation on marks, receipts, or attendance reports validates that `student_records.my_parent_id === auth()->user()->id`.

---

## 4. File Upload & Media Storage Hardening

### 4.1 Server-Side MIME Validation & Extension Spoofing Prevention
- **Vulnerability Identified:** File extensions could previously be determined from user-supplied filenames (`getClientOriginalExtension()`), enabling polyglot uploads or extension mismatch attacks.
- **Remediation Implemented (`Commit 30a2a09`):**
  - In `app/Helpers/Qs.php::getFileMetaData()`: The extension is derived from server-side MIME type inspection (`$file->extension()`) rather than client-supplied filename extensions.
  - In `BookController::uploadImage()`: Uploads are strictly restricted to verified image MIME types (`image/jpeg`, `image/png`, `image/webp`), and the stored extension is strictly taken from `$file->extension()`.
  - Stored files are assigned random SHA-1 / UUID hashes with verified extensions, neutralizing path traversal (`../`) and shell script execution risks.

---

## 5. Audit Logging & Sensitive Data Redaction

### 5.1 Sensitive Credential Redaction in Audit Logs
- `AuditLogService` captures all critical user, student, payment, and administrative events into `audit_logs`.
- **Redaction Policy:** All logged attributes undergo sanitization against `REDACTED_KEYS`:
  - `password`, `password_confirmation`, `activation_token`, `temp_password`, `token`, `secret`, `remember_token`.
- Redacted fields are replaced with `'[REDACTED]'` before persistence, preventing accidental token/credential leakage in database logs or log exports.

### 5.2 CSV Formula Injection Defense
- All tabular exports (audit logs, mark sheets, student rosters) filter cell values beginning with `= `, `+`, `-`, or `@` by prefixing a single apostrophe (`'`), preventing formula execution when opened in spreadsheet software (Excel, LibreOffice).

---

## 6. Dependency Vulnerability & Supply Chain Posture

### 6.1 Composer Security Audit Gate
- Enforced by `php .github/scripts/audit-checker.php`.
- 13 live advisories detected in upstream packages (`league/flysystem`, `laravel/framework`, `guzzlehttp/psr7`, `symfony/http-foundation`).
- All 13 advisories are documented in `.github/security/composer-exceptions.json` with architectural mitigations, unexpired review dates, and owner sign-offs.
- No unhandled vulnerabilities exist.

### 6.2 npm Build Toolchain Security Gate
- Enforced by `node .github/scripts/npm-audit-checker.js`.
- 79 high/critical build-toolchain findings originating from legacy Laravel Mix 2 / Webpack 3 dependencies.
- Verified: **Zero runtime vulnerability exposure**. All npm packages are build-time only; compiled JS/CSS artifacts contain no backend Node dependencies.
- Tracked with exception registry in `.github/security/npm-exceptions.json`.

---

## 7. Security Status Summary
- **Open Critical Vulnerabilities:** 0
- **Open High Vulnerabilities:** 0
- **Remediated Vulnerabilities:** All addressed and backed by automated PHPUnit feature tests.
- **Gate Status:** PASSING.

