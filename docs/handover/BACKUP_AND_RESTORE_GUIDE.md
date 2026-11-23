# Production Backup and Disaster Recovery Guide

**System**: SMS Laravel (School Management System)  
**Target Database**: `sms_laravel_prod`  
**Storage Destination**: `storage/app/backups/` (Offsite sync required)  

---

## 1. Automated Backup Command

The application provides a zero-dependency, transactional MySQL backup engine:

```bash
php artisan db:backup --database=sms_laravel_prod --compress --include-files
```

### Options:
- `--database=`: Database name (defaults to current `.env` database).
- `--compress`: Compresses SQL dump with gzip (`.sql.gz`), reducing storage by ~85%.
- `--include-files`: Automatically zips student photos and study materials.
- `--retention-days=30`: Automatically purges old archives older than specified retention days.

### Generated Artifacts:
1. `backup_sms_laravel_prod_YYYYMMDD_HHMMSS.sql.gz`: Compressed SQL snapshot.
2. `backup_sms_laravel_prod_YYYYMMDD_HHMMSS.sql.gz.meta.json`: Cryptographic manifest containing SHA-256 hash, table counts, and row counts.
3. `backup_files_sms_laravel_prod_YYYYMMDD_HHMMSS.zip`: Uploaded media archive.

---

## 2. Automated Daily Cron Schedule

Configure a Linux cron job under the `www-data` or deployment user:

```bash
crontab -e
```

Add the following schedule to execute daily backups at 01:00 AM (Nepal Time):
```cron
# Run daily transactional backup at 01:00 AM
0 1 * * * cd /var/www/sms_laravel && php artisan db:backup --database=sms_laravel_prod --compress --include-files --retention-days=30 >> /var/log/sms_backup.log 2>&1
```

---

## 3. Disaster Recovery & Restoration Procedure

In the event of hardware failure, catastrophic corruption, or ransomware compromise:

### Step 1: Verify Integrity of Backup Archive
```bash
php artisan db:restore storage/app/backups/backup_sms_laravel_prod_20261115_195915.sql.gz \
  --verify-checksum \
  --confirm-production \
  --force
```

### Step 2: Restore Uploaded Media Files
```bash
php artisan db:restore storage/app/backups/backup_sms_laravel_prod_20261115_195915.sql.gz \
  --restore-files \
  --confirm-production \
  --force
```

### Step 3: Clear Caches and Test System Readiness
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
curl -I https://school.yourdomain.edu.np/health/readiness
```

---

## 4. Offsite Backup Synchronization

To protect against physical datacenter disasters, sync backup files to a secondary cloud bucket (e.g. AWS S3, Google Cloud Storage, or secondary NAS):

```bash
# Example rsync or rclone command
rclone sync /var/www/sms_laravel/storage/app/backups remote_backup:apex-school-backups/
```

