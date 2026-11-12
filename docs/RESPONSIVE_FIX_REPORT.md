# SMS Academy — Responsive UI/UX & Scroll Overflow Fix Report

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Date:** 2026-11-12  

---

## 1. Summary of Implemented Fixes

1. **Global Viewport & Flex/Grid Overflow Guards ([`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css)):**
   - Added `*, *::before, *::after { box-sizing: border-box; }`, `html, body { width: 100%; max-width: 100%; margin: 0; }`, and `body { overflow-x: clip; }`.
   - Added `img, video, iframe, svg, canvas { max-width: 100%; }` and `img { height: auto; }`.
   - Added `.page-content`, `.content-wrapper`, `.row > [class*="col-"]`, `.card`, and `.card-body` `min-width: 0` + `overflow-wrap: break-word` rules so flex/grid children shrink properly without pushing the page wider than `320px`.
2. **Nepali BS Calendar Mobile Overflow Fix ([`public/assets/css/nepali_bs_calendar.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/nepali_bs_calendar.css#L219-L233)):**
   - Removed `min-width: 320px` from `.nepali-calendar-days-grid` and `.nepali-calendar-weekdays-grid` at `@media (max-width: 576px)` and enabled bounded `overflow-x: auto` on `.nepali-calendar-wrapper`.
3. **Mobile Header, `100dvh`, Modals, Select2 & FullCalendar ([`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css)):**
   - Clamped `.sms-brand-title` to `max-width: 135px` on `< 576px` so the mobile navbar fits cleanly on `320px` screens.
   - Added `min-height: 100dvh` with `100vh` fallback on `.sms-auth-page` and `.sms-error-page`.
   - Constrained `.select2-container { max-width: 100% !important; }`, wrapped `.fc .fc-toolbar`, bounded mobile `.modal-dialog` (`max-width: calc(100vw - 1.5rem)`) and `.modal-body` (`max-height: calc(100dvh - 180px); overflow-y: auto;`), and enforced `44px` touch targets on mobile controls.
4. **Mobile Dock vs PWA Install Banner Collision ([`public/assets/css/pwa.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/pwa.css#L119-L125)):**
   - Offset `#pwa-install-banner` to `bottom: calc(74px + env(safe-area-inset-bottom, 0px))` on `< 992px` so it never covers `.sms-mobile-dock`.
5. **Responsive Table Wrappers & Activation Error View:**
   - Wrapped DataTables in `<div class="table-responsive">` in `classes/index.blade.php`, `dorms/index.blade.php`, and `academic_years/index.blade.php`.
   - Standardized [`resources/views/pages/parent/activate_error.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/pages/parent/activate_error.blade.php) onto `.sms-auth-page` / `.sms-auth-card`.

