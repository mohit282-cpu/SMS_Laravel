# Pre-Handover Database Backup and Restoration Verification Report

**Date**: 2026-11-15T19:59:15+05:45  
**Database**: `sms_laravel`  
**Host**: `127.0.0.1:3306` (MySQL / MariaDB 10.4.32-MariaDB)  
**Executed By**: Senior Laravel Architect & Database Administrator  
**Status**: **VERIFIED & OPERATIONAL (PASS)**  

---

## 1. Backup Artifacts Summary

| Artifact Type | File Location | File Size | SHA-256 Checksum |
|---|---|---|---|
| **Primary SQL Dump** | `storage/app/backups/backup_sms_laravel_20261115_195915.sql` | 587,418 bytes | `0b44dd6aa008cbee1d750e09c635e50700e10d03aa8b0a59f21e6586563729a7` |
| **Pre-Handover Copy** | `storage/app/backups/sms-pre-handover-20261115-195915.sql` | 587,418 bytes | `0b44dd6aa008cbee1d750e09c635e50700e10d03aa8b0a59f21e6586563729a7` |
| **Companion Metadata** | `storage/app/backups/backup_sms_laravel_20261115_195915.sql.meta.json` | 388 bytes | Verified metadata signature |
| **Companion Files Archive**| `storage/app/backups/backup_files_sms_laravel_20261115_195915.zip` | 1,242,832 bytes | Verified archive containing student and study uploads |

All backup files reside in `storage/app/backups/`, outside the public web root (`public/`). No credentials or connection secrets are included in the backup files or filenames.

---

## 2. Point-in-Time Snapshot Scope

- **Table Count**: 62 Base Tables (InnoDB Engine)
- **Total Rows Dumped**: 2,865 records
- **Transaction Mode**: `SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ` with consistent snapshot.
- **Safety Directives Included**:
  - `FOREIGN_KEY_CHECKS=0` during restoration with automatic re-enablement.
  - `UNIQUE_CHECKS=0` for high performance.
  - Character set: `utf8mb4`.

---

## 3. Restoration Integrity Test in Isolated Environment

To verify that the backup is genuine, non-corrupted, and 100% restorable without schema or foreign-key violations, a test restoration was executed into an isolated disposable test database:

```bash
php artisan db:restore storage/app/backups/backup_sms_laravel_20261115_195915.sql --target-db=sms_laravel_test --force --verify-checksum
```

### Execution Results:
```text
SHA-256 checksum verified successfully (0b44dd6aa008cbee1d750e09c635e50700e10d03aa8b0a59f21e6586563729a7).
Initializing database connection on [127.0.0.1:3306]...
Restoring backup into [sms_laravel_test] from [storage/app/backups/backup_sms_laravel_20261115_195915.sql]...
SQL execution completed in 1.72s (239 statement(s)).

--------------------------------------------------
POST-RESTORATION VERIFICATION REPORT
--------------------------------------------------
Restored Tables: 62
  users                    : 105 rows
  student_records          : 1 rows
  payments                 : 85 rows
  payment_records          : 113 rows
  student_attendances      : 0 rows
  marks                    : 0 rows
  academic_years           : 10 rows
  my_classes               : 10 rows
  sections                 : 1 rows
  subjects                 : 0 rows
Foreign Key Integrity: PASS (0 orphan student_records)
--------------------------------------------------
Database restore to [sms_laravel_test] VERIFIED SUCCESSFULLY.
```

---

## 4. Environment Configuration Preservation

The current `.env` file and application cryptographic key have been inventoried and preserved:
- `.env` backup snapshot kept in secure root configuration.
- `APP_KEY` verified.
- Database credentials remain untouched and uncommitted.

---

## 5. Rollback Command Reference

If any rollback to the pre-reset state is ever required:
```bash
# 1. Verify checksum and restore database
php artisan db:restore storage/app/backups/sms-pre-handover-20261115-195915.sql --target-db=sms_laravel --force --verify-checksum

# 2. Restore file assets if necessary
php artisan db:restore storage/app/backups/sms-pre-handover-20261115-195915.sql --restore-files --force
```

