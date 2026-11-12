# SMS Academy — Responsive UI/UX & Scroll Overflow Audit

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Audit Date:** 2026-11-12  

---

## Prioritized Defect Register

| ID | Severity | File / Component | Description & Root Cause | Proposed Fix | Status |
|---|---|---|---|---|---|
| **RESP-001** | **P0** | [`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css) | Missing global `box-sizing: border-box`, `html, body { width: 100%; max-width: 100%; }`, `body { overflow-x: clip; }`, `img, svg, canvas, iframe { max-width: 100%; }`, and `.page-content` / flex child `min-width: 0` guards. | Add foundational viewport, media, flex/grid shrinking, and word-wrapping safeguards to `sms-design-system.css`. | **Planned (Phase B)** |
| **RESP-002** | **P0** | [`public/assets/css/nepali_bs_calendar.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/nepali_bs_calendar.css#L219-L230) | `@media (max-width: 576px)` sets `min-width: 320px` on `.nepali-calendar-days-grid` and `.nepali-calendar-weekdays-grid` inside padded containers, overflowing `320px–375px` viewports. | Set `overflow-x: auto` on `.nepali-calendar-wrapper` and `min-width: 0; width: 100%;` on the grids at `max-width: 576px`. | **Planned (Phase C)** |
| **RESP-003** | **P1** | [`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css) | `.sms-brand-title` (`max-width: 240px`) + mobile navbar actions exceed `320px`–`360px` viewports; `.sms-auth-page` & `.sms-error-page` use `100vh` without `100dvh` fallback. | Clamp `.sms-brand-title` to `max-width: 135px` on `< 480px`; use `min-height: 100vh; min-height: 100dvh;`; ensure navbar flex children have `min-width: 0`. | **Planned (Phase B)** |
| **RESP-004** | **P1** | [`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css), Select2, Modals, FullCalendar | Select2 inline widths, tall modals on mobile viewports, and `.fc-toolbar` buttons in FullCalendar can overflow `< 576px` screens. | Constrain `.select2-container { max-width: 100% !important; }`, bound modal dialogs (`max-width: calc(100vw - 1.5rem)`) and `.modal-body` (`max-height: calc(100dvh - 180px); overflow-y: auto;`), and wrap `.fc-toolbar`. | **Planned (Phase C)** |
| **RESP-005** | **P1** | [`public/assets/css/pwa.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/pwa.css#L120-L130) | `#pwa-install-banner` (`bottom: 15px`) overlaps `.sms-mobile-dock` (`height: ~60px`) on `< 992px` viewports. | Offset `#pwa-install-banner` to `bottom: calc(74px + env(safe-area-inset-bottom, 0px))` on `< 992px`. | **Planned (Phase B)** |
| **RESP-006** | **P1** | Multiple Blade table views (`academic_years`, `classes`, `dorms`, `books`, `students/list`, `users/index`, `payments/index`) | Tables without `<div class="table-responsive">` can push `.card` / `.content-wrapper` wider than the viewport on narrow mobile devices. | Wrap remaining tables in `<div class="table-responsive">` and enforce `.table-responsive` + `.dataTables_wrapper` bounded horizontal scrolling. | **Planned (Phase D)** |
| **RESP-007** | **P2** | [`resources/views/pages/parent/activate_error.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/pages/parent/activate_error.blade.php#L6) | Uses inline `border-radius: 18px` instead of unified `.sms-auth-card`. | Align with `.sms-auth-page` / `.sms-auth-card`. | **Planned (Phase D)** |
| **RESP-008** | **P2** | [`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css) | Touch target sizes on small pagination/action buttons and `44x44` mobile dock hit areas need explicit `@media (max-width: 767.98px)` enforcement. | Enforce `min-height: 44px` on mobile form inputs, buttons, and `44x44` icon buttons on touch viewports. | **Planned (Phase E)** |

