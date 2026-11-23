# Operational Troubleshooting Guide

**Scope**: Common System Faults, Diagnostic Procedures, and Recovery Actions  

---

## 1. Quick Diagnostic Endpoints

The system provides lightweight health diagnostic endpoints:
- **Liveness Probe**: `GET /health/liveness` (Returns HTTP 200 `{"status":"alive"}`)
- **Readiness Probe**: `GET /health/readiness` (Verifies DB connection, cache, and storage write permissions)
- **Super Admin Deep Diagnostic**: `GET /health/diagnostic` (Authenticated Super Admin only)

---

## 2. Common Issues & Solutions

### Issue A: "500 Internal Server Error" on Public Pages
- **Potential Causes**:
  - `storage` or `bootstrap/cache` write permissions missing.
  - Database connection refused or credentials incorrect in `.env`.
- **Diagnostic Command**:
  ```bash
  tail -n 50 /var/www/sms_laravel/storage/logs/laravel.log
  ```
- **Remediation**:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  php artisan config:clear
  ```

### Issue B: "Target class does not exist" or Route Error
- **Cause**: Cached routes out of sync with updated controllers.
- **Remediation**:
  ```bash
  php artisan route:clear
  php artisan cache:clear
  composer dump-autoload
  ```

### Issue C: Student Photos or Logos Not Displaying (404)
- **Cause**: Missing or broken public storage symbolic link.
- **Remediation**:
  ```bash
  rm -f public/storage
  php artisan storage:link
  ```

### Issue D: Database Migrations Hanging or Foreign Key Error
- **Cause**: Concurrent DDL locks or manual schema edits.
- **Remediation**:
  ```bash
  # Check active processes in MySQL
  mysql -u root -p -e "SHOW PROCESSLIST;"
  # Run safe backup restoration if schema is corrupted
  php artisan db:restore storage/app/backups/backup_latest.sql.gz --confirm-production --force
  ```

### Issue E: "CSRF token mismatch" (419 Error)
- **Cause**: Expired session, timezone mismatch, or missing `_token` in form submission.
- **Remediation**: Ensure server NTP clock is synchronized (`chrony` or `systemd-timesyncd`). Clear browser cookies or verify `APP_URL` in `.env` matches the current browser domain exactly.

