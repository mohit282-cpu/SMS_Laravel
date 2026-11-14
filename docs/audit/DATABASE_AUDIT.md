# Database Integrity, Schema & Migration Safety Audit

**Project:** SMS Laravel — School Management System  
**Database:** MySQL 8.0 (InnoDB)  
**Total Migrations:** 57  
**Branch:** `testing`  
**Status:** Validated & Safe  

---

## 1. Database Schema & Architecture Overview

The database comprises 57 discrete migration files supporting:
- Core identity & role-based permissions (`users`, `roles`, `permissions`)
- Academic hierarchy (`my_classes`, `class_types`, `sections`, `subjects`, `syllabuses`)
- Student tracking & enrollment (`student_records`, `promotions`, `dormitories`)
- Assessment & grading (`exams`, `grades`, `marks`, `skills`, `skill_types`)
- Financial accounting (`payments`, `payment_records`, `receipts`)
- Attendance & scheduling (`attendances`, `time_tables`, `time_slots`)
- Nepal geographical localization (`provinces`, `districts`, `municipalities`)
- System operations & governance (`settings`, `audit_logs`, `push_subscriptions`, `sessions`)

Engine: **InnoDB** across all tables, ensuring ACID compliance and referential integrity via foreign key constraints.  
Default Character Set: `utf8mb4` with collation `utf8mb4_unicode_ci` to support multilingual characters (Devanagari, English).

---

## 2. Referential Integrity & Foreign Key Analysis

### 2.1 Foreign Key Relationships & Cascade Policies
- **`student_records`:**
  - `user_id` -> `users.id` (`onDelete('cascade')`): Removing a user record purges corresponding student record.
  - `my_class_id` -> `my_classes.id`: Preserved to maintain class history.
  - `section_id` -> `sections.id`: Preserved with safety constraints.
  - `my_parent_id` -> `users.id`: References parent user account.
- **`marks`:**
  - `student_id` -> `student_records.id` (`onDelete('cascade')`).
  - `exam_id` -> `exams.id` (`onDelete('cascade')`).
  - `subject_id` -> `subjects.id` (`onDelete('cascade')`).
  - `my_class_id` -> `my_classes.id` (`onDelete('cascade')`).
  - `section_id` -> `sections.id` (`onDelete('cascade')`).
- **`nepal_locations` (`provinces`, `districts`, `municipalities`):**
  - `districts.province_id` -> `provinces.id` (`onDelete('cascade')`).
  - `municipalities.district_id` -> `districts.id` (`onDelete('cascade')`).
  - Covered by `NepalLocationHierarchyTest.php` ensuring zero orphaned child records.

---

## 3. Indexing Strategy & Query Optimization

### 3.1 Primary & Foreign Key Indexing
- Every table defines an unsigned auto-incrementing `id` primary key.
- Foreign keys in MySQL InnoDB automatically establish b-tree indexes.
- Critical query paths are indexed to prevent full table scans:
  - `student_records`: Indexed on `(user_id)`, `(my_class_id)`, `(section_id)`, `(my_parent_id)`, `(adm_no)`.
  - `marks`: Indexed on `(student_id, exam_id, subject_id, year)` to ensure fast tabulations and prevent duplicate mark entries.
  - `attendances`: Indexed on `(student_id, date)` and `(section_id, date)` for daily roll-call queries.
  - `payments`: Indexed on `(my_class_id, year)` for term-based fee lookups.
  - `audit_logs`: Indexed on `(user_id, created_at)` for compliance timelines.

---

## 4. Column Data Types & Constraints Hardening

### 4.1 Admission Number Expansion (`adm_no`)
- **Issue:** Prior to migration `2026_11_14_210000`, `student_records.adm_no` was limited to `VARCHAR(30)`. When schools used long institutional branding names (e.g., "Apex Model Secondary School"), generated admission numbers exceeded 30 characters, causing fatal SQL 1406 errors.
- **Remediation:** Added migration `2026_11_14_210000_expand_adm_no_length_on_student_records_table.php` expanding `adm_no` to `VARCHAR(100)` and adding an index on `adm_no`. Both application and test databases were updated.

### 4.2 Financial Precision
- `payments.amount`, `payment_records.amt_paid`, `payment_records.balance`: Defined with `decimal(10, 2)` or integer cents to avoid floating-point rounding inaccuracies.

### 4.3 Activation Token Storage
- `users.activation_token`: Stored as `VARCHAR(64)` nullable to accommodate 64-character SHA-256 cryptographic hexadecimal hashes.

---

## 5. Migration Safety & CI Verification Gate

### 5.1 Automated Safety Scanner
The repository enforces migration safety via `.github/scripts/check-migration-safety.php`.
- **Scan Policy:**
  - Forbids raw `DROP TABLE`, `DROP DATABASE`, or `TRUNCATE` operations.
  - Forbids unindexed `ALTER TABLE ... DROP COLUMN` on production tables without deprecation phases.
  - Requires safe `down()` rollback methods for every `up()` migration.
- **Verification Execution:**
  ```bash
  php .github/scripts/check-migration-safety.php --strict
  ```
  Result: **57 migration file(s) inspected, 0 warning(s).**

### 5.2 CI Database Isolation Gate
- `.github/scripts/verify-ci-database.php` validates that database connections in CI and automated testing point exclusively to dedicated test instances (`sms_laravel_test`), preventing accidental test execution against live or development data.
