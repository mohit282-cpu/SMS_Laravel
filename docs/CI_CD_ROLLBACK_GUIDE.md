# Continuous Deployment Rollback Guide — SMS Academy

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Target:** Emergency and Planned Rollback Procedures  
**Date:** 2026-11-10  
**Target Architecture:** Linux / Nginx / PHP 8.2-FPM / MySQL 8.0  

---

## 1. Overview & Rollback Principles

When a production defect is discovered following a release, the response must be swift, deterministic, and safe. Because SMS Academy uses atomic release symlinking and automated pre-migration database snapshots, rollbacks can be executed with minimal downtime.

### Guiding Principles:
1. **Preserve User Data:** Never perform a blind database rollback or restore if new student or financial transactions occurred unless strictly necessary.
2. **Prefer Code-Only Rollback:** If the database schema is backward-compatible with the prior release, roll back the code symlink without altering the database.
3. **Use Verified Backups:** If a destructive migration occurred, restore only from the automated pre-migration backup created immediately before deployment.

---

## 2. Rollback Triage Matrix

| Failure Type | Description | Recommended Procedure | Estimated Downtime |
|---|---|---|---|
| **Scenario A** | Frontend bug, PHP error, or logic defect; **no database migrations** were executed. | Code Symlink Rollback (Section 3) | < 30 seconds |
| **Scenario B** | Defect with **reversible migrations** (clean `down()` methods in migration files). | Migration Rollback + Symlink Rollback (Section 4) | 1–2 minutes |
| **Scenario C** | Irreversible schema corruption or critical database failure. | Full Snapshot Restore via `php artisan db:restore` (Section 5) | 3–5 minutes |

---

## 3. Scenario A: Fast Code-Only Rollback (< 30s)

Use this scenario when the newly deployed code has a defect, but database migrations were either not run or did not break backward compatibility.

### Step 1: SSH into the Target Server
```bash
ssh deploy@sms.yourschool.edu.np
cd /var/www/sms_academy
```

### Step 2: Identify the Previous Release Directory
List releases sorted by timestamp:
```bash
ls -lt releases/
```
Output example:
```text
drwxr-xr-x 12 deploy www-data 4096 Nov 10 15:30 89ef12c4 (Current Broken Release)
drwxr-xr-x 12 deploy www-data 4096 Nov 10 12:15 b7aec60f (Previous Stable Release)
```

### Step 3: Switch the Symlink to the Previous Stable Release
```bash
ln -sfn /var/www/sms_academy/releases/b7aec60f /var/www/sms_academy/current
```

### Step 4: Refresh Application Caches & Restart PHP-FPM
```bash
cd /var/www/sms_academy/current
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl reload php8.2-fpm
```

### Step 5: Verify Application Health
```bash
curl -I https://sms.yourschool.edu.np/
```
Confirm HTTP 200 OK and verify that the defect is resolved.

---

## 4. Scenario B: Rollback with Reversible Database Migrations

Use this scenario if migrations were executed, but the changes can be safely reversed using Laravel's migration rollback mechanism.

### Step 1: Enable Emergency Maintenance Mode
```bash
cd /var/www/sms_academy/current
php artisan down --message="System rollback in progress. Service will return shortly." --retry=60
```

### Step 2: Roll Back the Latest Migration Batch
Check the migration status:
```bash
php artisan migrate:status
```
Roll back the last batch of migrations:
```bash
php artisan migrate:rollback --force
```

### Step 3: Revert Code Symlink to Previous Release
```bash
ln -sfn /var/www/sms_academy/releases/<PREVIOUS_STABLE_SHA> /var/www/sms_academy/current
cd /var/www/sms_academy/current
```

### Step 4: Warm Caches & Bring Application Online
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

sudo systemctl reload php8.2-fpm
```

---

## 5. Scenario C: Full Database Disaster Recovery Restore

Use this scenario if migrations corrupted data or cannot be rolled back safely.

### Step 1: Enable Maintenance Mode Immediately
```bash
cd /var/www/sms_academy/current
php artisan down --message="Database maintenance in progress. Please stand by."
```

### Step 2: Locate the Pre-Migration Backup Snapshot
List available backups created by `db:backup`:
```bash
ls -lh /var/www/sms_academy/shared/storage/app/backups/
```
Identify the backup created right before deployment (e.g. `backup-sms_academy_prod-2026-11-10_15-28-00.sql.gz`).

### Step 3: Restore the Database Using `db:restore`
Execute the built-in, verified restore tool:
```bash
php artisan db:restore --file="backup-sms_academy_prod-2026-11-10_15-28-00.sql.gz" --force
```
The command automatically:
1. Validates archive integrity and uncompresses SQL in memory.
2. Disables foreign key checks during import.
3. Replaces the schema and data deterministically.
4. Re-enables foreign key constraints and verifies row integrity.

### Step 4: Re-point Symlink to Previous Stable Release
```bash
ln -sfn /var/www/sms_academy/releases/<PREVIOUS_STABLE_SHA> /var/www/sms_academy/current
cd /var/www/sms_academy/current

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

sudo systemctl reload php8.2-fpm
```

### Step 5: Post-Restore Verification Drill
1. Verify user authentication (Super Admin, Teacher, Student).
2. Verify financial records (`payments` table row counts and balance reconciliation).
3. Verify student marksheets and attendance records.
4. Inspect `storage/logs/laravel.log` for any database connection errors.

---

## 6. Emergency Contacts & Escalation
- **Lead DevOps Engineer:** Sovryx Tech Infrastructure Team
- **Database Administrator:** Sovryx Tech DBA Team
- **Incident Channel:** Slack `#ops-incidents` / Security Escalations

