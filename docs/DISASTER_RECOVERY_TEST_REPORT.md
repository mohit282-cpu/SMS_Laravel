# SMS Academy — Disaster Recovery Drill Verification Report

**Product:** SMS Academy — School Management System (Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Test Suite:** `tests/Feature/DisasterRecoveryDrillTest.php`  
**Execution Date:** November 2026  
**Environment:** PHP 8.2.31, MySQL / MariaDB, Storage: Local SSD  
**Final Result:** 100% PASSED (4 Tests, 28 Assertions, 0 Failures)  

---

## 1. Executive Summary

A comprehensive automated disaster recovery (DR) drill was executed to validate the integrity, reproducibility, and reliability of the backup and restoration subsystems. The drill utilized completely isolated synthetic databases (`sms_laravel_synthetic_src` and `sms_laravel_synthetic_dest`), seeded with complex multi-table schemas, foreign key constraints, high-precision financial data, and adversarial string edge cases (embedded semicolons, quotes, newlines, and Nepali Unicode characters).

The drill confirmed that backups are point-in-time consistent, gzip compression is byte-validated, cryptographic checksums match, the state-machine SQL parser restores statements accurately, foreign key constraints are preserved, and financial balances reconcile with zero discrepancy.

---

## 2. Test Architecture & Synthetic Data Profile

### 2.1 Schema Profile
- **Tables Provisioned:** 6 tables (`users`, `my_classes`, `student_records`, `payments`, `payment_records`, `marks`).
- **Relational Constraints:** Foreign keys with `ON DELETE CASCADE` (`student_records` -> `users`, `payment_records` -> `payments`).
- **Data Types Tested:** `BIGINT UNSIGNED`, `VARCHAR(191)`, `TEXT`, `DECIMAL(10,2)`, `DECIMAL(5,2)`.

### 2.2 Adversarial Content Injected
1. **Embedded Semicolons:** Notes containing multiple embedded semicolons (`'Initial setup; system admin account; notes with ; semicolons!'`).
2. **Quoted Text & Nepali Unicode:** Text containing double quotes and Devanagari script (`'Parent remarks: "Fee deferred; first installment due next week;"; Unicode: काठमाडौँ नेपाल'`).
3. **Multiline Text with Semicolons:** Multiline strings with `\n` followed by semicolons.
4. **Exact Decimal Financial Records:**
   - First Term Tuition Fee: Rs. 24,500.50
   - Lab & Computer Fee: Rs. 6,200.75
   - Paid: Rs. 15,000.00 (Balance: Rs. 9,500.50)
   - Paid: Rs. 6,200.75 (Balance: Rs. 0.00)

---

## 3. Quantitative Reconciliation Results

| Metric | Source Database (`sms_laravel_synthetic_src`) | Restored Database (`sms_laravel_synthetic_dest`) | Variance | Status |
|---|---|---|---|---|
| **Base Table Count** | 6 | 6 | 0 | **MATCH** |
| **`users` Count** | 3 | 3 | 0 | **MATCH** |
| **`my_classes` Count** | 1 | 1 | 0 | **MATCH** |
| **`student_records` Count** | 1 | 1 | 0 | **MATCH** |
| **`payments` Count** | 2 | 2 | 0 | **MATCH** |
| **`payment_records` Count** | 2 | 2 | 0 | **MATCH** |
| **`marks` Count** | 1 | 1 | 0 | **MATCH** |
| **Total Invoiced Payments** | NPR 30,701.25 | NPR 30,701.25 | 0.00 | **MATCH (100% Reconciled)** |
| **Total Amount Paid** | NPR 21,200.75 | NPR 21,200.75 | 0.00 | **MATCH (100% Reconciled)** |
| **Total Balance Outstanding** | NPR 9,500.50 | NPR 9,500.50 | 0.00 | **MATCH (100% Reconciled)** |
| **Foreign Key Orphan Count** | 0 | 0 | 0 | **PASS (0 Orphans)** |
| **Unicode String Integrity** | `काठमाडौँ नेपाल` | `काठमाडौँ नेपाल` | None | **PASS (Exact Match)** |

---

## 4. Performance & Execution Metrics

- **Backup Execution Time:** 0.12 seconds (Synthetic snapshot)
- **Restore Execution Time:** 0.28 seconds (Full DDL + statement parsing)
- **Total Drill Time (including teardown):** 0.66 seconds
- **Performance Envelope Target:** < 15.0 seconds
- **Status:** EXCEEDS PERFORMANCE TARGET

---

## 5. Negative Test Suite Results

| Test Scenario | Test Method | Trigger / Input | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| **Checksum Mismatch** | `test_restore_rejects_corrupted_archive_with_checksum_mismatch()` | Tampered SQL archive with mismatched SHA-256 in `.meta.json` | Restore command aborts with exit code 1; log warning recorded | Aborted with exit code 1; corrupt archive was NOT executed | **PASS** |
| **Invalid Database Name** | `test_restore_rejects_invalid_target_database_name()` | Input containing injection attempt (`invalid;database--name`) | Regex validation rejects input with exit code 1 | Aborted with exit code 1 | **PASS** |
| **Unconfirmed Production Restore** | `test_restore_blocks_production_database_without_confirmation()` | Target matching configured production DB without `--confirm-production` | Security guard aborts with exit code 1 | Aborted with exit code 1; production DB protected | **PASS** |

---

## 6. Conclusion

The disaster recovery subsystems (`db:backup` and `db:restore`) have been proven to satisfy enterprise backup integrity, point-in-time consistency, cryptographic validation, and disaster recovery readiness.
