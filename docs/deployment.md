# Deployment Readiness and CD Gap

- **Status:** **CD NOT CONFIGURED**
- **Date:** 2026-10-30
- **Related:** [`ci-cd-audit.md`](ci-cd-audit.md)

---

## 1. What exists today

The repository contains a **CI-only** pipeline (`.github/workflows/ci.yml`). It runs on pushes and pull requests to `main` and `testing`, and on manual dispatch. It does **not** deploy.

CI proves:

- Composer dependencies install from the lockfile.
- The Composer and npm security gates pass.
- PHP files parse and the Laravel config can be cached.
- Routes load.
- The target database is disposable and reachable.
- Migrations run forward, roll back completely, and re-apply.
- The full PHPUnit suite passes.
- The legacy frontend compiles on Node 14.

CI does **not** prove:

- That the application can run on the real host.
- That the production database credentials work.
- That a deployment can be reversed on the host.
- That the host has the required PHP extensions, web server configuration, queue worker, or TLS.

No deployment target, host, provider, or credential exists in this repository. Creating a deployment workflow without that information would mean inventing infrastructure, which this audit deliberately did not do.

---

## 2. Information required before CD can be implemented

Provide all of the following. Do not commit secrets; store them in GitHub Environments or the platform's secret store.

### Hosting

- [ ] Hosting provider and product (VPS, shared hosting, Platform-as-a-Service, container platform)
- [ ] Hostname / IP address and SSH port (if SSH-based)
- [ ] Deployment user and authentication method (SSH key, deploy token, cloud role)
- [ ] Absolute deployment path (for example `/var/www/sms`)
- [ ] Whether releases are atomic (symlink switch) or in-place
- [ ] PHP-FPM version and pool, or the runtime image
- [ ] Web server (Nginx/Apache/Caddy) and who manages its config
- [ ] Document root (must be `public/`, not the project root)

### Runtime services

- [ ] Production database engine, version, host, port, database name
- [ ] Application database user and its grants (must not be `root`)
- [ ] Queue driver and worker manager (Supervisor, systemd, platform worker)
- [ ] Scheduler mechanism (`schedule:run` every minute)
- [ ] Cache and session stores (file, Redis, database)
- [ ] Object storage / filesystem for uploads and generated PDFs
- [ ] Mail transport and sender identity
- [ ] Web Push VAPID keys and how they are injected
- [ ] Outbound network restrictions (if any)

### Safety and operations

- [ ] Backup tool, schedule, retention, and restore procedure for the database and uploads
- [ ] Health-check URL and expected response
- [ ] Log aggregation / retention
- [ ] Monitoring and alerting owner
- [ ] Maintenance-window policy
- [ ] Rollback authority and procedure
- [ ] Whether `testing` may deploy automatically (recommended: **no**)
- [ ] GitHub Environment name(s), required reviewers, and protection rules

---

## 3. Recommended CD shape (once the information above exists)

Keep CI and CD in separate workflows. Do not auto-deploy from `testing`.

```text
CI (push/PR to main, testing)
        │
        ▼
Manual "Deploy" workflow (workflow_dispatch only)
        │
        ├── environment: production  (required reviewers)
        ├── verify the exact commit SHA passed CI
        ├── take/verify a recent database backup
        ├── put the app into maintenance mode (optional, short)
        ├── upload a new release directory
        ├── composer install --no-dev --prefer-dist --optimize-autoloader
        ├── php artisan migrate --force
        ├── php artisan config:cache route:cache view:cache
        ├── switch the release symlink
        ├── reload PHP-FPM / restart queue workers
        ├── run the health check
        └── on failure: switch back to the previous release and report
```

Requirements for that workflow:

1. **Manual trigger only** for production. A push to `testing` must never deploy.
2. **Protected GitHub Environment** with required reviewers and environment-scoped secrets.
3. **Commit pinning:** deploy the SHA that passed CI, not "latest".
4. **Backup before migration.** If the backup cannot be verified, abort.
5. **Forward-only migrations in production.** CI proves rollback on a throwaway database, but production rollback should normally be a release switch, not a down-migration, because down-migrations can lose data.
6. **Health check after switch.** Fail the workflow and alert if it does not pass.
7. **No secrets in logs.** Use masked variables and avoid `set -x` around credential use.
8. **Least privilege.** The deploy user needs write access to the release path, not to the whole host.

---

## 4. Production configuration checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` set and backed up
- [ ] `APP_URL` uses HTTPS and matches the real hostname
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`
- [ ] `LOG_LEVEL` appropriate for production (not `debug` unless temporarily diagnosing)
- [ ] Database user is not `root`
- [ ] `config:cache`, `route:cache`, `view:cache` run during deployment
- [ ] Queue worker supervised and restarted on deploy
- [ ] Scheduler cron installed
- [ ] Writable `storage/` and `bootstrap/cache/`
- [ ] TLS certificate valid and auto-renewing
- [ ] Backups tested by restoring into a disposable database
- [ ] Security headers and trusted proxies configured for the actual edge

---

## 5. Rollback strategy

| Layer | Mechanism |
| --- | --- |
| Application code | Switch the `current` symlink back to the previous release |
| Configuration | Restore the previous `.env` / environment variables |
| Database | Restore the verified pre-deploy backup **only** if the schema change is incompatible; prefer forward fixes |
| Assets | Previous release contains its own compiled assets; no shared mutable asset directory |
| Queue workers | Restart against the restored release |

The CI rollback test proves the migrations are reversible on a disposable MySQL 8.0 database. It does not replace a production backup.

---

## 6. Current classification

**CD NOT CONFIGURED.** Deployment cannot be evaluated until the information in section 2 is supplied. CI is ready to be committed and run, but no claim is made that the application is deployable to a real host.
