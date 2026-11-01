# DATABASE AUDIT AND INTEGRITY REPORT
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Database Engines:** MySQL 5.7+ / MariaDB 10.4+  
**Evaluation:** Schema Design, Data Integrity, Foreign Keys, Indexing, and Transactions

---

## 1. Schema Overview

The database contains 45 migrations defining relational entities for school operations in Nepal:
- **Core Entities:** `users`, `user_types`, `student_records`, `staff_records`, `my_classes`, `sections`, `subjects`, `settings`.
- **Academics & Examination:** `exams`, `marks`, `grades`, `skills`, `exam_records`, `time_tables`, `promotions`.
- **Nepal Localization:** `academic_years`, `holidays`, `student_attendances`, `staff_attendances`.
- **Financial Architecture:** `payments`, `payment_records`, `receipts`, `payment_idempotency_keys`.
- **Security & PWA:** `audit_logs`, `push_subscriptions`, `password_resets`, `study_materials`, `notices`, `events`.

---

## 2. Integrity Enhancements & Critical Fixes

### 2.1 Financial Column Standardization to Decimal
- **Historical Issue:** Earlier migrations stored monetary quantities in integers or floats, introducing rounding inaccuracies during fee balance calculations.
- **Remediation:** Standardized columns across `payments`, `payment_records`, and `receipts` to `decimal(12,2)`.
  - `payments.amount`: `decimal(12,2)`
  - `payment_records.amt_paid`: `decimal(12,2)`
  - `payment_records.balance`: `decimal(12,2)`
  - `receipts.amt_paid`: `decimal(12,2)`
  - `receipts.balance`: `decimal(12,2)`

### 2.2 Payment Idempotency Keys (`payment_idempotency_keys`)
- **Schema:**
  - `id`: bigIncrements
  - `idempotency_key`: string(100), unique index
  - `payment_id`: unsignedInteger, foreign key to payments
  - `student_id`: unsignedInteger, foreign key to users
  - `amount`: decimal(12,2)
  - `response_payload`: json
  - `expires_at`: timestamp
  - `created_at`, `updated_at`: timestamps
- **Guarantee:** Strict concurrency prevention; repeated requests return identical cached receipt data without duplicate database inserts.

### 2.3 Foreign Key Protection & Data Retention Policy
- **Cascade Delete Hazards:** Database cascade deletes on `payment_records` and `marks` were prevented from being inadvertently triggered via application-level retention guards in `StudentRecordController::destroy`.
- **Archival Rule:** If a student has financial or exam records, administrative deletion is rejected with an instruction to archive or graduate the student.

### 2.4 Indexing & Query Optimization
- **Attendance Lookup:** Composite index on `(my_class_id, section_id, attendance_date)`.
- **Duplicate Prevention:** Unique composite constraint on `(student_record_id, attendance_date)`.
- **Push Endpoint:** Unique index on `endpoint_hash` to prevent multi-device subscription collisions.
- **Audit Logs:** Multi-column indexes on `(category, created_at)` and `(action, created_at)` for real-time monitoring and reporting.

---

## 3. Migration Rollback & Re-Apply Verification

All 45 migrations were tested sequentially against an isolated testing database:
1. `php artisan migrate:fresh --force --database=mysql_test` (45 applied)
2. `php artisan migrate:rollback --force --database=mysql_test` (45 rolled back cleanly)
3. `php artisan migrate --force --database=mysql_test` (45 re-applied)
4. `php artisan db:seed --force --database=mysql_test` (lookups and fixtures populated)

**Result:** Zero SQL errors, zero orphan rows, 100% foreign key referential integrity.
