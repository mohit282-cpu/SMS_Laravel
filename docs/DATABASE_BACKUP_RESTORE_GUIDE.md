# SMS Academy — Database Backup & Restore Operations Guide

**Product:** SMS Academy — School Management System (Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Target Database:** MySQL 8.0+ / MariaDB 10.5+  
**Commands:** `php artisan db:backup`, `php artisan db:restore`  

---

## 1. Overview & Architecture

SMS Academy provides native, zero-dependency, PHP/PDO database backup and restoration commands designed specifically for high-integrity school database operations. The commands feature:
1. **Point-in-Time Snapshot Consistency:** Uses InnoDB consistent read transactions without table locks.
2. **Byte-Verified Writes:** Verifies every byte written to disk to prevent silent corruption from full disks.
3. **Cryptographic Checksum Verification:** Generates and validates SHA-256 hashes stored in companion metadata files.
4. **State-Machine Tokenizer for Restoration:** Intelligently parses SQL statements, tracking single quotes, double quotes, backticks, and comments to prevent improper splitting on embedded semicolons.
5. **Production Safety Guards:** Blocks accidental restoration into production databases unless explicitly confirmed.
6. **Student Files Companion Archiving:** Automatically bundles uploaded student photos, admission documents, and syllabus PDFs.

---

## 2. Command Reference

### 2.1 Database Backup (`db:backup`)

```bash
php artisan db:backup [options]
```

#### Options:
- `--connection=`: Specific database connection defined in `config/database.php` (defaults to default connection).
- `--database=`: Override database name to backup.
- `--destination=`: Custom directory path (defaults to `storage/app/backups`).
- `--compress`: Compress SQL archive with gzip (`.sql.gz`).
- `--include-files`: Bundle user uploads and study materials (`storage/app/public`) into a companion `.zip`.
- `--retention-days=30`: Number of days to retain old backups before automatic pruning (defaults to 30).

#### Examples:
```bash
# Standard compressed backup with companion files archive
php artisan db:backup --compress --include-files

# Custom destination and 60-day retention
php artisan db:backup --compress --destination=/mnt/secure_backups --retention-days=60
```

### 2.2 Database Restore (`db:restore`)

```bash
php artisan db:restore {file} [options]
```

#### Arguments:
- `file`: Path to `.sql` or `.sql.gz` backup file.

#### Options:
- `--connection=`: Target database connection.
- `--target-db=`: Destination database name to restore into.
- `--force`: Force restoration without interactive confirmation prompt.
- `--confirm-production`: Explicitly confirm restoring into a production database.
- `--verify-checksum`: Strictly require and verify SHA-256 digest from companion `.meta.json`.
- `--restore-files`: Extract companion files `.zip` into `storage/app`.

#### Examples:
```bash
# Safe test restore into a disaster recovery database
php artisan db:restore storage/app/backups/backup_sms_laravel_20261110_180000.sql.gz \
  --target-db=sms_laravel_dr_drill \
  --force \
  --verify-checksum

# Emergency production restore
php artisan db:restore storage/app/backups/backup_sms_laravel_20261110_180000.sql.gz \
  --target-db=sms_laravel \
  --confirm-production \
  --verify-checksum \
  --restore-files
```

---

## 3. Metadata & Checksum Format

Every backup operation writes a companion `.meta.json` file adjacent to the archive:
```json
{
    "database": "sms_laravel",
    "tables": 42,
    "total_rows": 12850,
    "file_name": "backup_sms_laravel_20261110_180000.sql.gz",
    "file_size_bytes": 1458290,
    "sha256": "4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945",
    "compressed": true,
    "created_at": "2026-11-10T18:00:00+05:45",
    "duration_seconds": 1.25,
    "files_archive": {
        "file_name": "backup_files_sms_laravel_20261110_180000.zip",
        "file_size_bytes": 2849102,
        "files_count": 340
    }
}
```

---

## 4. Automated Backup Scheduling

Configure the automated backup schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily database snapshot at 01:00 AM Nepal Time
    $schedule->command('db:backup --compress --include-files --retention-days=30')
        ->dailyAt('01:00')
        ->timezone('Asia/Kathmandu')
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/backup.log'));
}
```

Ensure the server crontab includes:
```bash
* * * * * cd /var/www/sms_academy/current && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Post-Restoration Verification & Reconciliation

When `db:restore` executes, it performs automatic schema and record integrity checks:
1. Verifies base table count matches expectation.
2. Checks row counts for critical tables (`users`, `student_records`, `payments`, `payment_records`, `marks`, `my_classes`).
3. Executes a foreign key orphan check between `student_records` and `users`.
4. Outputs a verification report and logs the audit event.

