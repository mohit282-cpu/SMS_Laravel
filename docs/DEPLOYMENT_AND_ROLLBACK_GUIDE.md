# SMS Academy — Production Deployment & Rollback Guide

**Product:** SMS Academy — School Management System  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Target Environments:** Staging / Production  
**Deployment Model:** Atomic Symlink Release with Automated Error-Trapped Rollback  

---

## 1. Release Architecture

SMS Academy utilizes an atomic symlink deployment architecture standard across enterprise PHP/Laravel deployments. This guarantees zero-downtime updates, reproducible releases, and instant rollback capability.

### 1.1 Server Directory Structure
```
/var/www/sms_academy/
├── current -> /var/www/sms_academy/releases/20261110_180000_d5803366  # Active release symlink
├── releases/
│   ├── 20261110_160000_1246fd2a                                     # Previous release (retained)
│   └── 20261110_180000_d5803366                                     # Current release
└── shared/
    ├── .env                                                         # Persistent environment file
    └── storage/                                                     # Persistent logs, sessions, student files
        ├── app/
        ├── framework/
        │   ├── cache/
        │   ├── sessions/
        │   └── views/
        └── logs/
```

---

## 2. GitHub Actions CI/CD Pipeline

The release pipeline consists of two workflows:
1. **CI Pipeline (`.github/workflows/ci.yml`):**
   - Triggers on push and pull requests to `testing`, `staging`, and `main`.
   - Runs `composer validate --strict`.
   - Runs strict security audit gates for Composer and npm.
   - Executes pre-migration `php artisan route:list` to verify route bootstrap isolation.
   - Runs database migrations and seeders on MySQL service container.
   - Executes full PHPUnit test suite (423 tests, 2788 assertions).
   - Verifies post-seeder `php artisan route:list`.
   - Passes final `integration-gate` job.
2. **CD Pipeline (`.github/workflows/cd.yml`):**
   - Triggered via `workflow_dispatch` with environment selection (`staging` or `production`), migration toggle, and maintenance mode toggle.
   - **Job 1 (pre-deploy-validation):**
     - Installs production Composer dependencies (`--no-dev --optimize-autoloader`).
     - Builds frontend assets with Node.js 14 (`npm ci && npm run prod`).
     - Verifies asset existence (`app.js`, `app.css`, `mix-manifest.json`).
     - Packages production archive with explicit allowlist (no dev files, git, or tests).
     - Computes SHA-256 digest and uploads release bundle.
   - **Job 2 (deploy):**
     - Verifies downloaded bundle against SHA-256 digest.
     - Connects to target server via SSH agent using environment secrets.
     - Transfers release archive to `/tmp/`.
     - Executes remote atomic script under bash `ERR` trap.

---

## 3. Environment & Secret Configuration

Configure the following secrets in GitHub Repository / Environment settings (`staging` and `production`):

| Secret / Variable Name | Type | Description | Example |
|---|---|---|---|
| `DEPLOY_HOST` | Secret | Target host IP or FQDN | `192.168.10.50` or `app.smsacademy.edu.np` |
| `DEPLOY_USER` | Secret | SSH username with deploy permissions | `deployer` |
| `DEPLOY_SSH_KEY` | Secret | Private Ed25519 or RSA deployment key | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `APP_URL` | Variable | Base URL of deployed application | `https://app.smsacademy.edu.np` |
| `DEPLOY_PATH` | Variable | Base directory path on remote server | `/var/www/sms_academy` |

---

## 4. Automated Deployment & In-Flight Rollback

The deployment script incorporates an automated `rollback_handler` bound to bash `trap 'rollback_handler' ERR`.

### 4.1 Deployment Sequence
1. Verifies SHA-256 checksum of uploaded release archive on remote server.
2. Identifies current active release symlink (`PREVIOUS_RELEASE`).
3. Extracts release to `/var/www/sms_academy/releases/<SHA>`.
4. Symlinks `shared/storage` and `shared/.env` into new release directory.
5. Executes pre-migration database snapshot: `php artisan db:backup --compress`.
6. Enables maintenance mode: `php artisan down`.
7. Executes pending database migrations: `php artisan migrate --force`.
8. Warms up Laravel production caches: `config:cache`, `route:cache`, `view:cache`.
9. Atomically points symlink: `ln -sfn "${RELEASE_DIR}" "${TARGET_DIR}/current"`.
10. Disables maintenance mode: `php artisan up`.
11. Reloads PHP-FPM service to clear OPcache.
12. **In-Band Health Verification:** Queries `${APP_URL}/health/readiness`. If status code != 200 or status != `ready`, the script immediately raises an error.
13. Prunes old releases, retaining the last 5 versions.
14. Disables `ERR` trap and marks deployment successful.

### 4.2 In-Flight Rollback Trigger
If **ANY** step fails (migration failure, syntax error, cache warm failure, or health probe failure):
1. `rollback_handler()` activates automatically.
2. Atomically switches `current` symlink back to `PREVIOUS_RELEASE`.
3. Warms up caches on previous release directory.
4. Executes `php artisan up` to ensure school traffic is not blocked.
5. Reloads PHP-FPM service.
6. Queries `${APP_URL}/health/readiness` to verify restored release is operational.
7. Deletes temporary upload files in `/tmp/` and exits with non-zero code.

---

## 5. Emergency Manual Rollback Procedure

In the unlikely event that operations personnel need to manually roll back to a known previous release:

```bash
# 1. SSH into the production server
ssh deployer@app.smsacademy.edu.np

# 2. Inspect available releases
cd /var/www/sms_academy/releases
ls -ltd */

# 3. Choose the target previous release (e.g., release_20261110_160000)
PREV_RELEASE="/var/www/sms_academy/releases/release_20261110_160000"

# 4. Atomically point symlink
ln -sfn "${PREV_RELEASE}" /var/www/sms_academy/current

# 5. Rebuild caches and restart PHP-FPM
cd /var/www/sms_academy/current
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

sudo systemctl reload php8.2-fpm

# 6. Verify health status
curl -s http://127.0.0.1/health/readiness | jq .
```

---

## 6. Post-Deployment Verification Checklist

- [ ] HTTP 200 returned from `GET /health/liveness` (`{"status": "alive"}`).
- [ ] HTTP 200 returned from `GET /health/readiness` (`{"status": "ready"}`).
- [ ] HTTP 403 returned from unauthenticated `GET /health/diagnostic`.
- [ ] School administrators can log in via `/login`.
- [ ] Student attendance recording screen loads without JS errors.
- [ ] Fee payment creation and receipt generation function normally.
- [ ] Nepali date picker (Bikram Sambat) displays current year 2081/2082 accurately.

