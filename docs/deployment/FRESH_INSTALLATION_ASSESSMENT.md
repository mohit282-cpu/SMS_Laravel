# Fresh Installation and Database Assessment Report

**Environment**: Local Development & Staging Simulation  
**Evaluated At**: 2026-11-15T19:58:00+05:45  
**Assessor**: Senior Laravel Architect & Database Administrator  
**Branch**: `testing`  
**Repository**: `https://github.com/mohit282-cpu/SMS_Laravel`  

---

## 1. Executive Summary

This assessment evaluates the current state of the database and runtime assets for the **SMS Laravel (School Management System)** application in preparation for a fresh production database reset and school handover.

The current database `sms_laravel` contains development artifacts, seeded demo accounts (`cj@cj.com`, `admin@admin.com`, test teachers, demo students), dummy payment records, test library books, and simulated audit logs accumulated across engineering cycles. No genuine school production data exists in this database.

Per safety protocols, **the existing database is not treated as disposable**. A complete point-in-time backup will be captured and verified before any fresh initialization is executed.

---

## 2. Environment & Database Server Configuration

| Parameter | Current Value | Production Requirement |
|---|---|---|
| **Database Engine** | MySQL / MariaDB (InnoDB) | MySQL 8.0+ or MariaDB 10.4+ |
| **Server Version** | `10.4.32-MariaDB` | Verified compatible |
| **Current DB Name** | `sms_laravel` | Fresh production target: `sms_laravel_prod` |
| **Host & Port** | `127.0.0.1:3306` | Isolated production host / managed DB |
| **Application Environment** | `local` | `production` |
| **Debug Mode (`APP_DEBUG`)** | `true` | Must be `false` in production |
| **Session Driver** | `file` (or `database`) | Secure cookie, HTTPS only |
| **Cache Driver** | `file` | Verified working |
| **Queue Connection** | `sync` | Verified working |
| **Storage Symlink** | Configured (`storage/app/public` linked) | Permissions locked to 0750 / 0640 |

---

## 3. Comprehensive Table Inventory & Data Classification

Total tables evaluated: **62**  
Total records in existing database: **2,865**  

### Classification Categories:
- **Cat A — Application Configuration**: System defaults, academic session settings, UI parameters.
- **Cat B — Reference & Master Data**: Nepal geographic hierarchy (Provinces, Districts, Local Levels), blood groups, user types, class types, grading scales.
- **Cat C — School-Specific Operational Data**: Classes, sections, subjects, timetables, examinations, marks, student records, fee structures, receipts.
- **Cat D — User & Security Data**: System users, user roles, audit trails, password resets, push tokens.
- **Cat E — Temporary & Development Data**: Seeded demo accounts, test payments, mock student profiles, dummy notifications.

| Table Name | Existing Count | Category | Disposition in Fresh Production | Purpose & Dependency Notes |
|---|---|---|---|---|
| `academic_years` | 10 | B/C | Initialize standard current | Nepali BS Academic years; FK referenced by events & exams |
| `admission_inquiries` | 0 | C | Clean (0) | School admission inquiries |
| `attendance_audit_trails` | 56 | E/D | Reset (0) | Development attendance test logs; FK to users |
| `audit_logs` | 46 | E/D | Reset (0) | Test audit logs; fresh DB starts with 0 |
| `blood_groups` | 8 | B | **Preserve / Seed** | Required master data (A+, A-, B+, B-, etc.) |
| `book_categories` | 21 | B/C | Reset / Initialize Defaults | Library book categories |
| `book_requests` | 78 | E/C | Reset (0) | Test student book borrowing transactions |
| `books` | 147 | E/C | Reset (0) | Demo library books catalog |
| `class_types` | 6 | B | **Preserve / Seed** | Master categories: Nursery, Primary, JSS, SSS |
| `curricula` | 0 | B/C | Clean (0) | CDC Nepal curriculum frameworks |
| `curriculum_subjects` | 0 | B/C | Clean (0) | Subject mappings under Nepal curriculum |
| `dorms` | 5 | C | Reset (0) | Dormitory facilities (empty in clean install) |
| `entrance_results` | 0 | C | Clean (0) | Entrance exam results |
| `events` | 15 | E/C | Reset (0) | Calendar demo events |
| `exam_records` | 0 | C | Clean (0) | Term exam aggregate student records |
| `exams` | 44 | E/C | Reset (0) | Test examination schedules |
| `finance_ledgers` | 0 | C | Clean (0) | Double-entry accounting ledgers |
| `fiscal_years` | 0 | B/C | Clean (0) | Nepali fiscal years (e.g. 2083/84) |
| `grades` | 6 | B | **Preserve / Seed** | Grading definitions (A+, A, B, C, D, F) |
| `grading_scales` | 0 | B | Clean (0) | NEB / CDC grading scale definitions |
| `holidays` | 15 | B/C | Reset / Clean (0) | Public holidays (configured by school) |
| `lgas` | 0 | B | Clean (0) | Legacy LGA table superseded by Nepal local levels |
| `mark_audit_trails` | 0 | D | Clean (0) | Audit log for mark modifications |
| `marks` | 0 | C | Clean (0) | Student examination subject marks |
| `migrations` | 57 | System | **Preserve Schema** | Migration batch records tracking schema version |
| `my_classes` | 10 | E/C | Reset (0) | Test classes; fresh school configures own classes |
| `nationalities` | 965 | B | **Preserve / Seed** | Global master reference table |
| `nepal_districts` | 77 | B | **Preserve / Seed** | Official 77 districts of Nepal (MoFAGA) |
| `nepal_education_levels` | 0 | B | Clean (0) | Basic / Secondary Nepal education levels |
| `nepal_local_levels` | 753 | B | **Preserve / Seed** | Official 753 Municipalities/Rural Municipalities |
| `nepal_provinces` | 7 | B | **Preserve / Seed** | 7 Provinces of Federal Democratic Republic of Nepal |
| `nepal_settlements` | 0 | B | Clean (0) | Settlement/Tole directory |
| `nepal_wards` | 0 | B | Clean (0) | Ward directory |
| `notices` | 47 | E/C | Reset (0) | Demo notices posted during testing |
| `password_resets` | 0 | D | Clean (0) | Temporary tokens |
| `payment_idempotency_keys` | 0 | D | Clean (0) | Anti-duplicate payment tokens |
| `payment_records` | 113 | E/C | Reset (0) | Test fee payments and invoices |
| `payments` | 85 | E/C | Reset (0) | Test fee structures |
| `pins` | 0 | D | Clean (0) | Scratch card scratch pins |
| `promotions` | 0 | C | Clean (0) | Student academic session promotions |
| `push_subscriptions` | 13 | E/D | Reset (0) | Browser push notification tokens |
| `receipts` | 84 | E/C | Reset (0) | Test payment receipts |
| `school_profiles` | 0 | A | Initialized in Phase 5 | School identity metadata profile |
| `sections` | 1 | E/C | Reset (0) | Test class sections |
| `see_candidates` | 0 | C | Clean (0) | Grade 10 SEE registration records |
| `settings` | 36 | A | **Clean Defaults** | School settings; configured with clean blanks/placeholders |
| `skills` | 0 | B/C | Clean (0) | Behavioral/psychomotor evaluation skills |
| `staff_attendances` | 0 | C | Clean (0) | Staff daily attendance |
| `staff_records` | 0 | C | Clean (0) | Staff employment profiles |
| `states` | 37 | B | Clean / Retain | Legacy state table |
| `student_attendances` | 0 | C | Clean (0) | Student daily attendance |
| `student_certificates` | 0 | C | Clean (0) | Character / transfer certificates |
| `student_records` | 1 | E/C | Reset (0) | Demo student enrollment record |
| `student_transports` | 0 | C | Clean (0) | Transport assignments |
| `study_materials` | 0 | C | Clean (0) | Uploaded notes/PDFs |
| `subjects` | 0 | C | Clean (0) | Curriculum subjects |
| `time_slots` | 0 | C | Clean (0) | Class period schedules |
| `time_table_records` | 1 | E/C | Reset (0) | Timetable slot mapping |
| `time_tables` | 0 | C | Clean (0) | Timetable definitions |
| `transport_routes` | 0 | C | Clean (0) | School bus routes |
| `user_types` | 16 | B | **Preserve / Seed** | Roles: super_admin, admin, teacher, parent, student, etc. |
| `users` | 105 | E/D | 1 Authorized Super Admin | All 105 demo/test accounts replaced with 1 initial admin |

---

## 4. Foreign Key Constraints & Dependency Tree

The database enforces **99 foreign key constraints**.
Key structural hierarchies:
1. **User Identity Root**:
   - `users.id` is the primary parent for `student_records`, `staff_records`, `audit_logs`, `attendance_audit_trails`, `book_requests`, `events`, `push_subscriptions`.
2. **Academic Hierarchy**:
   - `class_types` -> `my_classes` -> `sections` -> `student_records`
   - `my_classes` -> `subjects` -> `marks`
   - `exams` -> `exam_records` & `marks`
3. **Financial Hierarchy**:
   - `my_classes` -> `payments` -> `payment_records` -> `receipts`
4. **Nepal Geographic Hierarchy**:
   - `nepal_provinces` -> `nepal_districts` -> `nepal_local_levels`
   - Referenced optionally by `users` (`province_id`, `district_id`, `local_level_id`) and `student_records`.

**Reset Safety Implication**: Direct `DELETE` or `TRUNCATE` operations on parent tables without cascading or proper ordering will violate foreign keys. The clean approach is running clean migrations on a new database (`sms_laravel_prod`) or executing migrations fresh via controlled artisan workflows.

---

## 5. Uploaded Assets Inventory

Storage scan results:
- `storage/app/public/uploads/student/`:
  - `IYHGWBMIJM/photo.jpg` (392,901 bytes - seeded mock photo)
  - `JT9J74VKGV/photo.jpg` (392,901 bytes - seeded mock photo)
  - `XKTZORIPIR/photo.jpg` (392,901 bytes - seeded mock photo)
- `public/uploads`: Directory does not exist (symlink points to storage).
- `storage/app/backups/`: Contains 259 testing/verification backup snapshots.

**Action**: Demo student photos will be securely archived and removed from the production storage directory. The backup directory will be preserved securely outside the public document root.

---

## 6. Execution Plan for Fresh Production Handover

1. **Phase 2 — Point-In-Time Backup**:
   - Capture full SQL snapshot of `sms_laravel`.
   - Verify backup file existence, file size, SHA-256 checksum.
   - Run restoration test in `sms_laravel_test` to guarantee backup integrity.
   - Document in `docs/deployment/BACKUP_AND_RESTORE_REPORT.md`.

2. **Phase 3 & 4 — Controlled Fresh Production Database Initialization**:
   - Target database: `sms_laravel_prod`.
   - Execute all 57 migrations in sequence.
   - Run production seeders for master reference tables only (`BloodGroups`, `UserTypes`, `ClassTypes`, `Nationalities`, `NepalGeography`, `SettingsDefaults`).
   - Strictly prohibit `DemoUsersSeeder`, `StudentRecordsTableSeeder`, `MyClassesTableSeeder`, and demo fee seeders.
   - Implement idempotent setup command `php artisan sms:install`.
   - Create initial Super Admin account with secure prompt/options.

3. **Phase 5 — Clean School Configuration Initializer**:
   - Initialize school profile settings with clean placeholders (no dummy phone numbers or demo URLs).
   - Ensure school administrators can configure official identity via UI/CLI.

4. **Phase 6 & 7 — File Storage & Config Hardening**:
   - Clean out demo uploaded photos.
   - Verify storage link and file upload permissions (0750 / 0640).

5. **Phase 8 — Production Security Audit**:
   - Verify CSRF, session security, debug mode disabled, authorization guards.

6. **Phase 9, 10 & 11 — Validation & Onboarding Simulation**:
   - Validate clean database zero-count expectations on operational tables.
   - Run complete end-to-end first-school onboarding test in disposable test database.

7. **Phase 12 & 13 — Handover Documentation Package**:
   - Generate all 10 required guides in `docs/handover/`.
   - Formulate official System Acceptance Report.

