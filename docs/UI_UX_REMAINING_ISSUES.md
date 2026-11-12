# SMS Academy — Remaining UI/UX Issues & Known Limitations

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Date:** 2026-11-12  

---

## 1. Remaining & Deferred Items

| ID | Severity | Area | Description | Status / Mitigation |
|---|---|---|---|---|
| **REM-001** | **P2** | Browser Visual QA | Real headless browser visual regression & screenshot capture across `320px`–`1920px` viewports and all 7 role dashboards. | **NOT RUN locally** (Playwright / Chromium not installed in this environment). Blade rendering, route access, and responsive CSS wrappers were verified via PHPUnit feature tests. |
| **REM-002** | **P2** | Frontend Mix Build | `npm run production` requires Node.js `14` for `node-sass@4.x` native bindings; local environment runs Node `v24.16.0` with an empty `node_modules` directory. | Verified in GitHub Actions `frontend-build` (Node 14) workflow; local `sms-design-system.css` is served directly as a static stylesheet without requiring Mix compilation. |
| **REM-003** | **P3** | Legacy CSS Files | `public/assets/css/apple-design-system.css` and `public/assets/css/apple-liquid-theme.css` remain on disk (unreferenced by `inc_top`, `login/inc_top`, and `layouts/app`) for safe rollback. | Can be safely deleted once staging browser QA confirms zero external references. |
| **REM-004** | **P3** | Minor Table Wrappers | A few low-traffic tables (`academic_years/index`, `classes/index`, `dorms/index`, `books/circulations`, `books/requests`) rely on the global `.dataTables_wrapper { overflow-x: auto }` rule added to `sms-design-system.css` rather than explicit `<div class="table-responsive">` wrappers. | Horizontal scrolling works via `.dataTables_wrapper`; explicit `<div class="table-responsive">` wrappers can be added during routine maintenance. |
| **REM-005** | **P3** | Stray Legacy Views | `resources/views/test.blade.php` and `resources/views/auth/passwords/Dmail.blade.php` remain in `resources/views/`. | Unreferenced by routes; safe to remove in a cleanup commit. |

