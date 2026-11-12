# SMS Academy — Responsive & Scroll Overflow Test Report

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Test Date:** 2026-11-12  

---

## 1. Automated Verification Executed

| Check | Result | Scope |
|---|---|---|
| `vendor/bin/phpunit tests/Feature/UiShellAndNavigationTest.php` | **PASS** | Verified responsive CSS overflow/touch-target rules, Nepali calendar grid fix, PWA dock clearance, and Blade rendering. |
| Full `vendor/bin/phpunit` | **PASS** (`430 tests, 2979 assertions`, `27.20s`) | Executed across entire repository after all responsive edits (informational PHPUnit 9 XML schema warning present; note one prior run hit an unrelated `rand(100,999)` collision in `SecurityHardeningAndIntegrityTest` which passed on re-run). |
| `php artisan route:list` | **PASS** | Exit code `0`. |
| `php .github/scripts/audit-checker.php` | **PASS** | Exit code `0`. |
| `node .github/scripts/npm-audit-checker.js` | **PASS** | Exit code `0`. |

---

## 2. Browser Visual & Viewport Screenshot Testing (`320px–1920px`)

- **Status:** **NOT RUN locally** — Playwright / Chromium is not installed in this CLI environment (`Test-Path "$env:USERPROFILE\.cache\ms-playwright"` returned `False`).
- **Next Step:** Execute Playwright horizontal-overflow and screenshot verification across `320px`–`1920px` viewports on staging/CI where Chromium is available.

