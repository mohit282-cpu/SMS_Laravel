# Deployment Rollback Procedure

## 1. Automated In-Flight Rollback
During `.github/workflows/cd.yml`, an `ERR` trap monitors checksum verification, binary archive pre-extraction validation, extraction, permissions, backup, migrations, cache warming, symlink switching, PHP-FPM reload, and the bounded-retry `/health/readiness` probe.
On any failure, `rollback_handler()`:
1. Restores the `current` symlink to `PREVIOUS_RELEASE`.
2. Rebuilds Laravel caches (`config:cache`, `route:cache`, `view:cache`) on the previous release.
3. Exits maintenance mode (`php artisan up`) if it was enabled, and reloads the active PHP-FPM service (auto-detecting `php8.2-fpm`, `php-fpm`, or `php8.1-fpm`).
4. Runs `probe_readiness` against `${APP_URL}/health/readiness` (verifying `status=ready` and exact match with previous `.release_sha`).
5. Tracks all recovery errors; exits non-zero if the rollback itself fails so operators are alerted immediately.

> **Database Rule:** `migrate:rollback` is **never** executed automatically during an application rollback, because schema rollbacks can cause data loss or break forward-only data fixes. Schema rollbacks must always be planned and executed manually using tested migration paths.

## 2. Manual Application Rollback on VPS
```bash
cd /var/www/sms_academy
ls -1dt releases/*/
ln -sfn /var/www/sms_academy/releases/<previous-sha> /var/www/sms_academy/current
cd /var/www/sms_academy/current
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
sudo systemctl reload php8.2-fpm
curl -fsS https://your-domain.example/health/readiness
```

## 3. Database Snapshot Restoration (When Required)
1. Locate the pre-migration snapshot created in `shared/storage/app/backups/`.
2. Enable maintenance mode (`php artisan down`).
3. Restore the verified backup into the target database and verify row counts before bringing the application back `up`.

