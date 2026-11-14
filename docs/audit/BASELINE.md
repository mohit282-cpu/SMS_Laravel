# Repository Technical Baseline & Engineering Audit

**Repository:** `mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Current Verified Commit:** `d813cb1`  
**Audit Timestamp:** 2026-11-14T20:45:00+05:45  
**Auditor:** Principal Software & Security Engineering Review  

---

## 1. Executive Baseline Summary

SMS Laravel is a multi-role School Management System built on Laravel 8, tailored for academic institutions in Nepal. It manages students, parents, teachers, administrators, attendance (AD/BS calendar support), grading, payments, library operations, and Progressive Web App (PWA) push notifications.

The repository is currently undergoing production hardening on branch `testing`. Recent commits have resolved CI/CD release verification vulnerabilities, migration destructive DDL/DML scanner gaps, and student admission column truncation issues.

---

## 2. Technology Stack & Environment

| Layer | Component | Version / Specification | Notes |
|---|---|---|---|
| **Framework** | Laravel Framework | `^8.40` (`8.83.29` installed) | PHP 8.2 runtime compatibility enforced in `composer.json` |
| **Language** | PHP | `8.2.30` | Strict syntax linting and type constraints |
| **Database** | MySQL | `8.0` (InnoDB, utf8mb4) | Strict mode enabled, foreign keys enforced |
| **Testing DB** | MySQL | `8.0` (`sms_laravel_test`) | Guarded by `verify-ci-database.php` |
| **Frontend** | Vue 2 / Mix / Webpack | Vue `2.5.17`, Mix `2.1.14`, Webpack `3.12.0` | Node.js 14.21.3 build environment |
| **PWA & Push** | Service Worker + WebPush | `minishlink/web-push ^11.0` | RFC 8292 VAPID encryption |
| **Calendar** | Bikram Sambat Engine | Custom `NepaliDateEngine` | Supports 1970–2100 BS conversions |

---

## 3. Architecture & Top-Level Structure

- **`app/Http/Controllers/`**:
  - `Auth/`: Authentication, password reset, parent activation.
  - `SupportTeam/`: Core academic operations (Students, Marks, Payments, Timetables, Classes, Sections).
  - `SuperAdmin/`: System settings, school branding, database backup/restore.
  - `Tenant/` / `Landing/`: Public school landing pages, online admission enquiry, entrance results.
- **`app/Models/`**: Eloquent models representing 57 database tables.
- **`app/Repositories/`**: Data access abstraction layer (`StudentRepo`, `UserRepo`, `MyClassRepo`, `LocationRepo`).
- **`app/Services/`**: Business logic isolation (`NepalLocationService`, `AuditLogService`, `BikramSambatService`, `PwaService`).
- **`database/migrations/`**: 57 migrations spanning core LMS, Nepal localization, finance, and attendance.
- **`database/seeders/`**: Seeders for settings, classes, sections, permissions, and Nepal geography.

---

## 4. Existing Automated Test Coverage

- **PHPUnit Suite:** **489 tests, 3,658 assertions** passing with 0 failures and 0 errors.
- **Key Test Suites:**
  - `NepalLocationHierarchyTest.php`: 10 tests, 79 assertions (strict counts, dry-run safety, cascade validation).
  - `StudentAdmissionStoreTest.php`: 3 tests, 16 assertions (uniqueness, long branding prefix, 422 validations).
  - `StudentRecordDestroySecurityTest.php`: Comprehensive authorization and file cleanup tests.
  - `CdDeploymentSafetyTest.php`: 6 tests, 71 assertions (deployment failure simulations, rollback triggers).
  - `VerifyCiGateTest.php`: 20 tests, 48 assertions (fail-closed API pagination, rerun policies).
  - `ReleaseArchiveSecurityTest.php`: 4 tests, 27 assertions (21 adversarial archive payloads).

---

## 5. Audit Risk Register (Categorized Findings)

### Critical
1. **Parent Activation Flow:** Must verify that tokens stored in the database are cryptographically hashed and cannot be replayed or brute-forced.
2. **Horizontal Privilege Escalation (IDOR):** Verify that parent endpoints (`my_children`, marks, receipts) strictly validate parent-child ownership server-side.
3. **Teacher Mark Manipulation:** Verify teachers can only grade students in subjects and classes assigned to them.

### High
1. **File Upload Verification:** Profile photos and library uploads must strictly enforce MIME sniffing, server-side generated filenames, and traversal blocking.
2. **Financial Reset & Manual Payment Manipulation:** Prevent tampering with invoice line items, balances, or payment history without administrative audit trails.
3. **PWA Push Subscription Ownership:** Prevent endpoint reuse hijacking between different users on shared devices.

### Medium
1. **Legacy Frontend Dependencies:** Node 14 / Webpack 3 / Mix 2 build tree requires ongoing maintenance and an eventual modernization path.
2. **N+1 Queries in Grade & Attendance Sheets:** Optimize relationship eager loading across class lists.

### Low / Informational
1. **Audit Log Exporter:** Ensure CSV formula injection protection (`=`, `+`, `-`, `@`) is present on all exports.
2. **Locale & Calendar State:** Ensure consistent AD/BS toggle persistence across user sessions.

---

## 6. Baseline Validation Evidence
- `composer validate --strict`: Passed.
- `php .github/scripts/audit-checker.php`: Passed (8 approved, time-bound exceptions).
- `node .github/scripts/npm-audit-checker.js`: Passed (79 build toolchain exceptions).
- `php .github/scripts/check-migration-safety.php --strict`: Passed (57 migrations scanned).
- `php .github/scripts/verify-ci-database.php --connection=mysql_test`: Passed.
- `vendor/bin/phpunit`: 489 tests, 3,658 assertions passing.
