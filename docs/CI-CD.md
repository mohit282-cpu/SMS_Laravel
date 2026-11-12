# CI/CD Pipeline Architecture

## 1. Overview
SMS Academy uses two GitHub Actions workflows:
- **CI (`.github/workflows/ci.yml`):** Automated validation on pushes to `main` and `testing`, pull requests targeting `main` and `testing`, and manual `workflow_dispatch`.
- **CD (`.github/workflows/cd.yml`):** Manual (`workflow_dispatch`) release-based deployment to `staging` or `production` environments.

## 2. CI Jobs & Mandatory Integration Gate
| Job ID | Job Name | Environment | Purpose |
|---|---|---|---|
| `php-tests` | `PHP 8.2 / Tests` | Ubuntu 22.04, PHP 8.2, MySQL 8.0 | Composer validate/install, PHP syntax lint, config cache check, route load check, disposable DB guard (`verify-ci-database.php`), `migrate:fresh`, `migrate:rollback`, `migrate`, `db:seed`, PHPUnit with JUnit XML artifact |
| `frontend-build` | `Frontend build (Node 14)` | Ubuntu 22.04, Node 14 | `npm ci`, `npm run prod` (Laravel Mix 2 / Webpack 3 / Vue 2), verifies `public/js/app.js`, `public/css/app.css`, and `public/mix-manifest.json` |
| `composer-audit` | `Composer security audit` | Ubuntu 22.04, PHP 8.2 | Runs `.github/scripts/audit-checker.php` against `composer.lock` and `.github/security/composer-exceptions.json` |
| `npm-audit` | `npm audit gate (Node 22)` | Ubuntu 22.04, Node 22 | Runs `.github/scripts/npm-audit-checker.js` against `package-lock.json` and `.github/security/npm-exceptions.json` |
| `integration-gate` | `CI Integration Gate` | Ubuntu 22.04 | `if: always()`; fails unless all 4 upstream jobs report `success` |

## 3. Branch Protection & Repository Security
Recommended settings for `main` and `testing`:
- Require a pull request before merging (minimum 1 approval on `main`).
- Require status checks to pass before merging: **`CI Integration Gate`**.
- Require branches to be up to date before merging.
- Require conversation resolution before merging.
- Block force pushes and branch deletions.
- Restrict `production` environment deployments to authorized maintainers with manual environment approval.
- All third-party GitHub Actions are pinned to 40-character commit SHAs with least-privilege `permissions`.

