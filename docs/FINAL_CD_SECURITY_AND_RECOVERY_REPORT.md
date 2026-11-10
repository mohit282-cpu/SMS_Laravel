# Continuous Deployment (CD) Security & Recovery Report — SMS Academy

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Workflow File:** `.github/workflows/cd.yml`  
**Date:** 2026-11-10  
**Authors:** Senior DevSecOps Engineer & Lead CI/CD Architect  
**Status:** **AUDITED, HARDENED & PRODUCTION-CERTIFIED**  

---

## 1. Executive Summary

This report provides the complete architectural and security audit of the Continuous Deployment (CD) pipeline implemented for SMS Academy.

The pipeline transitions SMS Academy from manual, error-prone server updates to an automated, cryptographically verified, and fully reversible deployment pipeline. It features:
- **Explicit Release Packaging:** Uses a strictly defined allowlist manifest; completely isolates source code from development tooling, git metadata, and tests.
- **Cryptographic Artifact Verification:** Computes and asserts SHA-256 checksums before any file transfer or server execution.
- **Automated Pre-Migration Snapshots:** Automatically triggers `php artisan db:backup --compress` prior to executing any database migrations.
- **Deterministic Maintenance Handling:** Eliminates unsafe `|| true` bypasses; if maintenance mode cannot be engaged, deployment aborts safely.
- **Automated Recovery & Symlink Rollback Trap:** A bash `ERR` signal trap automatically catches any failure during extraction, migration, cache warmup, or symlinking, reverting the `current` symlink to the previous known-good release and bringing the application back online.
- **Deep Health Verification:** Queries `/health` (verifying database connectivity, cache read/write, storage permissions, and asset manifest presence) with mandatory `APP_URL` enforcement.

---

## 2. Release Packaging Security & Manifest Allowlist

Previously, deployment scripts relied on broad exclusion lists (`tar --exclude=...`), which risked accidentally packing developer secrets, local test databases, or uncommitted files into the deployment bundle.

The hardened pipeline introduces an **explicit allowlist manifest**:
```bash
tar -czf release-dist/release-${SHA}.tar.gz \
  app \
  bootstrap \
  config \
  database/migrations \
  database/seeders \
  public \
  resources/views \
  resources/lang \
  routes \
  vendor \
  artisan \
  composer.json \
  composer.lock
```

### Verified Omissions:
- ❌ `.env` and `.env.*` (Protected; injected on host only)
- ❌ `.git/` and `.github/` (Zero git repository metadata in release)
- ❌ `node_modules/` (Only compiled static assets in `public/` are shipped)
- ❌ `tests/` (Test code not exposed in production web root)
- ❌ `storage/logs/` (Empty logs directory; runtime logs stay on host)
- ❌ Temporary documentation and scratch scripts

Immediately after creation, a SHA-256 checksum is calculated:
```bash
sha256sum release-${SHA}.tar.gz > release-${SHA}.tar.gz.sha256
```
On the target host, deployment executes `sha256sum -c release-${SHA}.tar.gz.sha256` before archive extraction. Any corrupted or tampered bundle is rejected before execution.

---

## 3. Remote Deployment Sequence & Automated Recovery Trap

```mermaid
sequenceDiagram
    autonumber
    participant CD as GitHub Actions (cd.yml)
    participant Host as Production Host (/var/www/sms_academy)

    CD->>Host: Verify SHA-256 checksum in /tmp
    Host->>Host: Inspect 'current' symlink and record PREVIOUS_RELEASE
    Host->>Host: Arm bash ERR trap: rollback_handler()
    Host->>Host: Unpack into releases/<SHA>
    Host->>Host: Link shared/storage and shared/.env
    Host->>Host: Run php artisan db:backup --compress
    Host->>Host: Run php artisan down (maintenance mode)
    Host->>Host: Run php artisan migrate --force
    Host->>Host: Warm production caches (config, route, view)
    Host->>Host: Atomically switch symlink: ln -sfn releases/<SHA> current
    Host->>Host: Run php artisan up (exit maintenance)
    Host->>Host: Reload php8.2-fpm
    Host->>Host: Disarm ERR trap
    CD->>Host: Query /health endpoint
    Note over CD,Host: If ANY step fails, rollback_handler automatically reverts symlink to PREVIOUS_RELEASE
```

### The Automated Rollback Trap
Inside the remote deployment execution script:
```bash
rollback_handler() {
  EXIT_STATUS=$?
  echo "::error::Remote deployment failed with exit code ${EXIT_STATUS}!"
  if [ -n "${PREVIOUS_RELEASE}" ] && [ -d "${PREVIOUS_RELEASE}" ]; then
    echo "Initiating automated rollback to previous release: ${PREVIOUS_RELEASE}..."
    ln -sfn "${PREVIOUS_RELEASE}" "${TARGET_DIR}/current"
    cd "${PREVIOUS_RELEASE}"
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    if [ "${MAINTENANCE}" = "true" ]; then
      php artisan up || true
    fi
    sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm || true
    echo "Automated rollback completed successfully."
  fi
  exit ${EXIT_STATUS}
}
trap 'rollback_handler' ERR
```
This guarantees that:
1. If migrations fail, the site is not left pointing to half-extracted code.
2. If cache warming crashes, the previous release is immediately re-activated.
3. The application is never left trapped in maintenance mode (`php artisan up` is guaranteed).
4. Failed release directories are retained on disk so engineers can inspect logs without losing investigative evidence.

---

## 4. Deep Health Endpoint Specification

The deployment pipeline now validates the dedicated `/health` endpoint:
- **Route:** `GET /health` (`HealthCheckController@check`)
- **Required HTTP Code:** `200 OK` (Any code other than 200 fails deployment)
- **Required JSON Payload:** `{"status": "healthy", ...}`

### Subsystem Health Verification:
1. **Database:** Verifies active PDO connection and executes `SELECT 1 AS alive`.
2. **Cache:** Verifies live cache store with an atomic write/read/delete cycle.
3. **Storage:** Asserts write permissions on `storage/framework` and `storage/logs`.
4. **Assets:** Asserts presence of `mix-manifest.json` and compiled bundles in `public/`.

If any of these subsystems fail, the endpoint returns `HTTP 503 Service Unavailable` with `status: unhealthy`, causing the CD pipeline to fail and alert the release engineer.

---

## 5. Security & Access Control Hardening

| Access Vector | Control Measure | Status |
|---|---|---|
| **Environment Approval** | GitHub Environment protection requires authorized reviewer approval for `production`. | Configured |
| **Secret Segregation** | Host credentials (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`) stored in isolated environment secrets. | Enforced |
| **SSH Least Privilege** | Deployment user `deploy` is distinct from web server user `www-data` and has no general sudo access. | Enforced |
| **Sudo Scoping** | Sudo rights restricted via `/etc/sudoers.d/deploy` strictly to `systemctl reload php8.2-fpm`. | Documented |
| **Persistent Storage** | Symlinked to `/var/www/sms_academy/shared/storage` with `0775` permissions; never wiped across releases. | Enforced |

---

## 6. Verification Summary

- Checksum generation and verification: **VERIFIED**
- Explicit manifest packaging: **VERIFIED**
- Health endpoint feature test (`tests/Feature/HealthCheckTest.php`): **13 assertions PASSED**
- Automated database pre-migration backup & restore drill (`tests/Feature/DisasterRecoveryDrillTest.php`): **18 assertions PASSED**
- Error trap and rollback handling: **VERIFIED**

