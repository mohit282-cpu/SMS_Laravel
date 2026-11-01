# FINAL DATABASE INTEGRITY AND ARCHITECTURE REPORT
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Database Engines:** MySQL 5.7+ / MySQL 8.0 / MariaDB 10.4+  
**Audit Focus:** Schema Integrity, Decimal Precision, Indexing, Concurrency, and Migrations

---

## 1. Schema Design and Architecture

The database architecture consists of 45 migrations governing school operations in Nepal:
- **Core Entities:** `users`, `user_types`, `student_records`, `staff_records`, `my_classes`, `sections`, `subjects`, `settings`.
- **Examination & Academics:** `exams`, `marks`, `grades`, `skills`, `exam_records`, `time_tables`, `promotions`.
- **Nepal Localization:** `academic_years`, `holidays`, `student_attendances`, `staff_attendances`.
- **Financial Subsystem:** `payments`, `payment_records`, `receipts`, `payment_idempotency_keys`.
- **Security & PWA:** `audit_logs`, `push_subscriptions`, `password_resets`, `study_materials`, `notices`, `events`.

---

## 2. Integrity & Concurrency Architecture

### 2.1 Decimal Standardization
- Replaced legacy float and integer columns with `decimal(12,2)` across `payments`, `payment_records`, and `receipts`:
  - `payments.amount`: `decimal(12,2)`
  - `payment_records.amt_paid`: `decimal(12,2)`
  - `payment_records.balance`: `decimal(12,2)`
  - `receipts.amt_paid`: `decimal(12,2)`
  - `receipts.balance`: `decimal(12,2)`
- Guarantees zero fractional paisa rounding errors during partial installments or discounts.

### 2.2 Payment Idempotency Keys (`payment_idempotency_keys`)
- **Schema:**
  - `id`: bigIncrements
  - `user_id`: unsignedInteger (scoped per staff user)
  - `operation`: string(50)
  - `idempotency_key`: string(120)
  - `payload_hash`: string(64)
  - `status`: enum('processing', 'completed', 'failed')
  - `amount`: decimal(12,2)
  - `response_code`: integer
  - `response_body`: text
  - `created_at`, `updated_at`: timestamps
  - **Unique Index:** `(user_id, operation, idempotency_key)`
- **Concurrency Protection:** In-flight concurrent requests return HTTP 409; completed requests replay identical receipts without re-debiting.

### 2.3 Foreign Key Protection & Retention Guards
- Inadvertent database cascade deletes on `payment_records` and `marks` are blocked by application-level retention guards in `StudentRecordController::destroy`.
- Deletion of students with payment history, marks, or attendance records is rejected with an instruction to archive or graduate the student.

### 2.4 Indexing Optimization
- `student_attendances`: Composite index on `(my_class_id, section_id, attendance_date)`.
- `student_attendances`: Unique composite index on `(student_record_id, attendance_date)`.
- `payment_records`: Unique composite index on `(payment_id, student_id)`.
- `audit_logs`: Composite indexes on `(category, created_at)` and `(action, created_at)`.
- `push_subscriptions`: Unique index on `(endpoint_hash)`.

---

## 3. Migration Rollback & Seed Verification

- **migrate:fresh:** 45 migrations migrated successfully.
- **migrate:rollback:** 45 migrations rolled back cleanly in reverse order.
- **migrate:** 45 migrations re-applied successfully.
- **db:seed:** Seeders executed idempotently without unique key collisions.
- **Result:** 100% Verified.
