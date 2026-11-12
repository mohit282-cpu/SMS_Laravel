# SMS Academy — Complete UI/UX Audit, Page Inventory & Redesign Plan

**Product:** SMS Academy — School Management System (`Smart School Management. Simplified.`)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Commit SHA:** `adb54e8f63386707322b8837780acf7024837e02`  
**Audit Date:** 2026-11-12  
**Frontend Stack:** Laravel 8 Blade, Bootstrap 4, Limitless Admin Template, jQuery, DataTables, Select2, FullCalendar, Vue 2 (Laravel Mix 2 / Webpack 3)  

---

## 1. Executive Summary

An exhaustive UI/UX, architectural, accessibility, and responsiveness audit was conducted across all **143 Blade templates**, **21 CSS files**, and **5 custom JS files** in SMS Academy.

While the underlying Laravel 8 backend and Nepal localization services are rich and functional, the frontend suffers from **fragmented styling layers**—specifically, Limitless Bootstrap 4 base stylesheets are overridden by two separate, competing custom CSS engines (`apple-design-system.css` at 1,194 lines and `apple-liquid-theme.css` at 2,619 lines), while the login screen uses a third unrelated UI pattern (`Uiverse.io` purple-accent card). Additionally, the mobile navigation dock contains **broken/unauthorized links** for Teachers and Parents, views execute duplicate database queries directly in Blade, and several accessibility/WCAG 2.2 AA gaps exist.

---

## 2. Frontend Architecture & Component Inventory

### 2.1 Layouts (`resources/views/layouts/`)
- [`master.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/layouts/master.blade.php) — Authenticated application shell (top navbar, sidebar, page header, flash/validation alerts, content area, mobile dock).
- [`login_master.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/layouts/login_master.blade.php) — Authentication shell.
- [`landing.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/layouts/landing.blade.php) — Public institutional website shell.
- [`app.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/layouts/app.blade.php) — Default Laravel scaffold layout (unused/redundant).

### 2.2 Global Shell Partials (`resources/views/partials/`)
- [`inc_top.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/inc_top.blade.php) — Loads 12 CSS stylesheets, Google Fonts (`Roboto`, `Noto Sans Devanagari`), IcoMoon icons, jQuery, Bootstrap bundle, BlockUI.
- [`inc_bottom.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/inc_bottom.blade.php) — Loads Select2, Steps, Uniform, Inputmask, jQuery Validate, SweetAlert2, PNotify, DataTables (+ JSZip/pdfmake), Moment, DatePickers, FileInput, FullCalendar, custom JS, Nepali BS Calendar/Datepicker, and PWA config.
- [`top_menu.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/top_menu.blade.php) — Top navbar with brand, role badge, notices badge, language toggle (`EN` / `ने`), and user dropdown.
- [`menu.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/menu.blade.php) — Main left accordion sidebar (312 lines) including role-specific menu includes (`super_admin/menu`, `admin/menu`, `teacher/menu`, `student/menu`, `parent/menu`, `accountant/menu`, `librarian/menu`).
- [`header.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/header.blade.php) — Page header bar showing page title, system name, BS/AD date, and active session pill.
- [`mobile_dock.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/mobile_dock.blade.php) — Fixed bottom 5-button mobile navigation dock (`Home`, `Academics`, `Notices`, `Finance/Library/Users`, `Menu`).
- [`js/custom_js.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/js/custom_js.blade.php) — Inline PNotify/SweetAlert flash triggers and AJAX helpers.

### 2.3 Stylesheets (`public/assets/css/` & `resources/sass/`)
- **Base Framework:** `bootstrap.min.css`, `bootstrap_limitless.min.css`, `layout.min.css`, `components.min.css`, `colors.min.css`
- **Custom & Theme Layers:** `qs.css`, `apple-design-system.css` (1,194 lines), `apple-liquid-theme.css` (2,619 lines)
- **Feature/Print CSS:** `pwa.css`, `nepali_datepicker.css`, `nepali_bs_calendar.css`, `my_print.css`, `print_tabulation.css`, `receipt.css`
- **Mix Source:** `resources/sass/app.scss`, `resources/sass/_variables.scss`, `resources/js/app.js`, `resources/js/components/ExampleComponent.vue`

---

## 3. Complete Page & Module Inventory (143 Blade Templates)

| Module / Domain | Views | Key Roles |
|---|---|---|
| **Authentication & Errors** | `auth/login`, `register`, `verify`, `passwords/email`, `passwords/reset`, `passwords/Dmail` (stray), `pages/parent/activate`, `activate_error`, `pages/pwa/offline` *(missing `errors/403`, `404`, `500`)* | All / Guest |
| **Public Portal** | `landing/index`, `about`, `events`, `contact`, `entrance_results`, `pages/other/privacy_policy`, `terms_of_use` | Public |
| **Dashboards** | `pages/support_team/dashboard` (545 lines, multi-role), `pages/admin/dashboard` (97 lines) | Super Admin, Admin, Teacher, Student, Parent, Accountant, Librarian |
| **Account & Users** | `my_account`, `users/index`, `users/edit`, `users/show`, `super_admin/settings`, `super_admin/audit_logs/index` | Super Admin, Admin, All |
| **Students & Parents** | `students/add`, `edit`, `list`, `show`, `graduated`, `students/promotion/{index,promote,reset,selector}`, `parents/index`, `parents/show`, `pages/parent/children` | TeamSA, Teacher, Parent |
| **Academic Structure** | `academic_years/{index,edit}`, `classes/{index,edit}`, `sections/{index,edit}`, `subjects/{index,edit}`, `dorms/{index,edit}`, `grades/{index,edit}` | Super Admin, Admin |
| **Attendance** | `attendance/{index,monthly,class_report,student_history}`, `staff_attendance/{index,monthly}` | TeamSA, Teacher, Student, Parent |
| **Exams & Marks** | `exams/{index,edit}`, `marks/{index,edit,manage,show,tabulation,bulk,selector,print}`, `marks/show/{index,comments,skills,sheet}` | TeamSA, Teacher, Student, Parent |
| **Fees & Accounting** | `payments/{index,create,edit,manage,invoice,receipt,monthly_billing,reports}` | Super Admin, Accountant, Student, Parent |
| **Timetables** | `timetables/{index,edit,manage,show,print,my_timetable,teacher_timetable,parent_children_timetable}`, `timetables/subjects/{add,edit}`, `timetables/time_slots/{index,add,edit,manage}` | Academic, Teacher, Student, Parent |
| **Library & Materials** | `books/{index,edit,show,catalogue,categories,circulations,requests,my_requests}`, `study_materials/index` | Librarian, TeamSA, Teacher, Student |
| **Communication & Events** | `notices/{index,edit,show}` (and partials), `events/{index,create,edit,show}`, `holidays/*`, `pins/{index,create,enter}` | All |

---

## 4. Prioritized UI/UX, Accessibility & Functional Defects

### Critical / High Priority
1. **Broken & Unauthorized Mobile Dock Links ([`mobile_dock.blade.php:63,73`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/mobile_dock.blade.php#L63-L73)):**
   - For **Parents**, the Finance tab links to `route('payments.invoice', [Qs::hash(Auth::user()->id), ...])`, passing the parent's user ID instead of a child's student ID (fails with 403/empty). Should link to `route('my_children')`.
   - For **Teachers** (and other non-SA/Accountant/Librarian roles), the `else` branch links to `route('users.index')`, which is restricted by `teamSA` middleware and returns **403 Forbidden**. Should link to `route('attendance.index')` or `route('marks.index')` for teachers.
2. **Competing CSS Design Engines & Excessive Glassmorphism ([`inc_top.blade.php:18-34`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/inc_top.blade.php#L18-L34)):**
   - Stacking Limitless + `apple-design-system.css` + `apple-liquid-theme.css` causes heavy `!important` specificity wars, duplicate CSS variables (`--apple-*` vs `--color-*`), `backdrop-filter: blur(25px)` performance penalties on mobile, radial background gradients, and oversized `20px` border radii.
3. **Disconnected Login & Missing Branded Error Views ([`login.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/auth/login.blade.php), `resources/views/errors/`):**
   - `login.blade.php` uses an unrelated Uiverse.io purple theme (`rgba(162, 104, 255, ...)`), lacks visible/associated `<label>` tags on inputs (WCAG 2.2 AA failure), and has no branded `403`, `404`, or `500` error pages.
4. **Duplicate DB Queries in Global Shell ([`top_menu.blade.php:29`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/top_menu.blade.php#L29), [`mobile_dock.blade.php:42`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/mobile_dock.blade.php#L42), [`menu.blade.php:108`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/menu.blade.php#L108)):**
   - Notice counts and `MyClass::orderBy('name')->get()` are queried directly inside Blade partials on every single page load.
5. **Inconsistent Role Dashboards (`admin/dashboard.blade.php` vs `support_team/dashboard.blade.php`):**
   - `admin/dashboard.blade.php` uses a dark gradient hero card and oversized `120px` icon box that conflicts with the executive light header in `support_team/dashboard.blade.php`.

### Medium / Low Priority
6. **Deep 3-Level Sidebar Nesting & Inline Styles ([`menu.blade.php:1,70-112`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/menu.blade.php#L1)):**
   - Inline `style="background: rgba(17, 24, 39, 0.95) !important; backdrop-filter: blur(25px)"`, neon orange avatar glow, and 3-level submenus make mobile/desktop navigation cramped.
7. **Missing Global Footer & Page Breadcrumbs ([`master.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/layouts/master.blade.php), [`header.blade.php`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/header.blade.php)):**
   - No standardized footer with Sovryx Tech / SMS Academy copyright; `header.blade.php` lacks breadcrumbs and has a dead `href="#"` session pill for non-Super-Admins.
8. **Malformed HTML Attributes & Duplicate Meta Tags ([`inc_top.blade.php:18-34`](file:///z:/Xampp/htdocs/SMS_Laravel/resources/views/partials/inc_top.blade.php#L18-L34)):**
   - Leading spaces inside `href=" {{ asset(...) }}"` and duplicate `<meta name="theme-color">` tags across `master.blade.php` and `inc_top.blade.php`.
9. **Missing `prefers-reduced-motion` & Accessibility Semantics:**
   - Icon-only buttons and dropdown triggers need explicit `aria-label`s; custom transitions need `@media (prefers-reduced-motion: reduce)` guards; forms need consistent required indicators (`*`) and submit-loading states.
10. **Stray / Legacy Files:**
   - `resources/views/test.blade.php` and `resources/views/auth/passwords/Dmail.blade.php` should be audited for removal/cleanup.

---

## 5. Prioritized Redesign Plan (Phases 2–9)

1. **Phase 2 — Unified SaaS Design System (`public/assets/css/sms-design-system.css`):**
   - Consolidate conflicting variables into a single clean semantic token set (Midnight Navy `#111827`, Sovryx Orange `#FF5700`, clean neutral surfaces `#F8FAFC` / `#FFFFFF`, refined 8–12px radii, crisp 1px borders, WCAG AA contrast, Devanagari-friendly typography, and `prefers-reduced-motion`).
   - Standardize buttons, cards, stat cards, tables, badges, form controls, modals, empty states, and loading states.
2. **Phase 3 — Global Application Shell (`layouts/master`, `partials/top_menu`, `menu`, `header`, `mobile_dock`, `footer`):**
   - Clean sidebar hierarchy (no inline blur/neon glows), unified top navbar, breadcrumbs in `header.blade.php`, standardized footer, and fixed role-safe `mobile_dock.blade.php` links.
   - Cache or share common shell counts cleanly via View Composer rather than duplicate inline queries.
3. **Phase 4 — Authentication, Error Pages & Role Dashboards:**
   - Unify `auth/login`, `passwords/email`, `passwords/reset`, `parent/activate`, and add branded `errors/{403,404,500}.blade.php`.
   - Align `admin/dashboard.blade.php` and all role sections in `support_team/dashboard.blade.php` under one cohesive SaaS visual language using real backend data only.
4. **Phase 5 — Core Academic & People Modules:**
   - Standardize forms, tables, empty states, and mobile layouts across Students, Parents, Teachers/Users, Classes/Sections/Subjects, Attendance, Exams, and Marks.
5. **Phase 6 — Financial, Library, Communication & Settings Modules:**
   - Standardize Payments, Invoices, Receipts, Monthly Billing (BS), Library, Study Materials, Notices, Events, and System Settings.
6. **Phase 7–9 — Responsive, Accessibility, QA & Documentation:**
   - Verify 320px–1920px+ viewports, keyboard navigation, contrast, PHPUnit suite, route smoke tests, and deliver `docs/DESIGN_SYSTEM.md`, `docs/UI_UX_IMPLEMENTATION_REPORT.md`, and `docs/UI_UX_TEST_REPORT.md`.

