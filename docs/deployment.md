# Production & Staging Deployment Guide

## 1. Release Directory Layout
Deployments use atomic symlinked releases under `DEPLOY_PATH` (default `/var/www/sms_academy`):
```text
/var/www/sms_academy/
├── current -> /var/www/sms_academy/releases/<commit-sha>
├── releases/
│   └── <commit-sha>/
│       ├── .env -> /var/www/sms_academy/shared/.env
│       ├── .release_sha
│       └── storage -> /var/www/sms_academy/shared/storage
└── shared/
    ├── .env
    └── storage/
```

## 2. Pre-Deployment Gate
Before any deployment runs, `.github/workflows/cd.yml` (`pre-deploy-validation`) enforces:
1. Valid 40-character commit SHA.
2. `ci.yml` workflow run and `CI Integration Gate` concluded `success` on that exact `github.sha` (paginated GitHub API verification).
3. `composer validate --strict` and `composer install --no-dev --optimize-autoloader`.
4. `npm ci && npm run prod` on Node 14 with verified `public/js/app.js`, `public/css/app.css`, and `public/mix-manifest.json`.
5. Explicit allowlist archive (`.release_sha`, `app`, `bootstrap`, `config`, `database/data`, `database/migrations`, `database/seeders`, `public`, `resources/views`, `resources/lang`, `routes`, `vendor`, `artisan`, `composer.json`, `composer.lock`) validated by `.github/scripts/verify-release-archive.php` (both on the CI runner and on the target host prior to extraction) with SHA-256 checksum, TAR checksum validation, strict octal checks, and end-of-archive validation. Extraction is executed safely with `--no-same-owner`.
6. PHP-FPM service auto-detection (`php8.2-fpm`, `php-fpm`, `php8.1-fpm`) with active verification after reload.

## 3. Manual Deployment Steps
1. Open **Actions → CD (Deployment) → Run workflow**.
2. Select branch (`testing` for staging, `main` for production).
3. Choose `target_env` (`staging` or `production`), `run_migrations` (`true`/`false`), and `enable_maintenance` (`true`/`false`).
4. Approve the `production` environment gate when prompted.

## 4. Health Verification (`/health/readiness`)
`HealthCheckController::readiness()` verifies maintenance status, database connectivity, cache read/write, storage writability, and production asset manifest, returning HTTP `200` with `{"status":"ready","release":"<sha>"}`. Both in-band and external probes retry up to 6 times at 5-second intervals.

## 5. Database Migration Policy
- Only backward-compatible **expand-and-contract** migrations should be deployed to production.
- Pre-migration snapshot (`php artisan db:backup --compress`) runs automatically when `run_migrations=true`.
- Application rollback **never** runs `migrate:rollback` automatically.
