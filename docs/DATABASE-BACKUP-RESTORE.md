# Database Backup and Disaster Recovery Runbook

**System:** SMS Laravel — School Management System  
**Database Engine:** MySQL 8.0+  
**Target Architecture:** Linux (Ubuntu 22.04 LTS / Debian 12)

---

## 1. Overview & Guarantees

The database layer stores academic records, parent and student identities, examination marks, and immutable financial receipts. All backup procedures enforce:
- **Transaction Consistency:** Single-transaction snapshots (`--single-transaction`) avoiding locks on active reader/writer tables.
- **Data Integrity:** Routine SHA-256 verification of compressed database archives.
- **Atomic Pre-Migration Safeguards:** Automatic backup execution before any database migration in production CD pipelines.

---

## 2. Artisan Backup Command (`db:backup`)

The application includes an internal artisan command `php artisan db:backup` located at `app/Console/Commands/DatabaseBackupCommand.php`.

### Command Syntax:
```bash
php artisan db:backup [--compress] [--output=path/to/backup.sql]
```

### Automation in Production CD:
Before running `php artisan migrate --force`, the deployment script automatically invokes:
```bash
php artisan db:backup --compress
```
Backups are archived under `storage/app/backups/` with ISO timestamps:
`backup-YYYY-MM-DD-HHmmss.sql.gz`

---

## 3. Manual MySQL Backup Procedures

### A. Full Consistent Snapshot
```bash
mysqldump \
  --host=127.0.0.1 \
  --port=3306 \
  --user=sms_app \
  --password \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  sms_laravel | gzip -9 > /var/backups/sms_laravel/sms_db_$(date +%Y%m%d_%H%M%S).sql.gz
```

### B. Verify Backup Archive Integrity
```bash
gzip -t /var/backups/sms_laravel/sms_db_*.sql.gz
sha256sum /var/backups/sms_laravel/sms_db_*.sql.gz > /var/backups/sms_laravel/sms_db_*.sql.gz.sha256
```

---

## 4. Disaster Recovery & Database Restoration

> [!CAUTION]
> Database restoration replaces current database state. Always execute on a separate staging or test database first before performing restoration on production.

### Step 1: Place Application into Maintenance Mode
```bash
cd /var/www/sms_academy/current
php artisan down --message="Database maintenance in progress. Please stand by." --retry=60
```

### Step 2: Terminate Active Connections
Ensure no long-running queue workers or web workers are executing writes.
```bash
sudo systemctl stop php8.2-fpm
```

### Step 3: Decompress and Restore Snapshot
```bash
gunzip < /var/backups/sms_laravel/sms_db_20261114_100000.sql.gz | mysql \
  --host=127.0.0.1 \
  --port=3306 \
  --user=sms_app \
  --password \
  sms_laravel
```

### Step 4: Verify Post-Restoration Consistency
Run the internal database integrity check:
```bash
php artisan migrate:status
php .github/scripts/verify-ci-database.php --connection=mysql
```

### Step 5: Resume Application Services
```bash
sudo systemctl start php8.2-fpm
cd /var/www/sms_academy/current
php artisan up
```

---

## 5. Automated Verification Test

The disaster recovery and backup command is covered by automated PHPUnit test suites:
- `Tests\Feature\DatabaseBackupCommandTest`
- `Tests\Feature\DatabasePaymentIdempotencyTest`

