# Security Policy & Controls

## 1. Authentication, Authorization & Session Security
- Role-based access control (`super_admin`, `admin`, `teacher`, `student`, `parent`, `accountant`, `librarian`) enforced via route middleware (`teamSA`, `teamSAT`, `super_admin`) and controller/FormRequest checks.
- Diagnostic health endpoint (`/health/diagnostic`) requires either `super_admin` authentication or `X-Health-Key` matching `HEALTH_CHECK_SECRET`.
- Public `/health/readiness` returns only `status`, `timestamp`, and valid 40-char `release` SHA (`Cache-Control: no-cache, no-store, must-revalidate`) with zero internal telemetry or stack traces.

## 2. Dependency & Supply-Chain Gates
- **Composer:** `.github/scripts/audit-checker.php` runs `composer audit --locked --format=json` and fails closed on any advisory not covered by an unexpired, owner-assigned exception in `.github/security/composer-exceptions.json` (currently 13 Laravel 8 / `dompdf` / `flysystem` advisories including CVE-2026-128083, with documented compensating controls).
- **npm:** `.github/scripts/npm-audit-checker.js` runs on Node 22 and matches all 79 build-toolchain findings against `.github/security/npm-exceptions.json` (`0` runtime dependencies).
- **Release Archives:** `.github/scripts/verify-release-archive.php` inspects binary USTAR/gzip headers before extraction and rejects traversal, symlinks, hardlinks, `.env`, `.git`, `node_modules`, `tests`, oversized entries, and archive bombs.

## 3. SSH & Deployment Hardening
- `StrictHostKeyChecking=yes` with environment-scoped `DEPLOY_SSH_KNOWN_HOSTS`.
- Exact CI Gate verification via `.github/scripts/verify-ci-gate.php` linking repository, `ci.yml` workflow run ID, `head_sha`, `check_suite_id`, and `CI Integration Gate`.

