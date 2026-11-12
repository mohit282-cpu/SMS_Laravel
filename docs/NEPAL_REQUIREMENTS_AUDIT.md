# SMS Academy — Nepal-First School Management System Requirements Audit & Enhancement Plan

**Repository:** `https://github.com/mohit282-cpu/SMS_Laravel`  
**Branch:** `testing`  
**Audit Date:** 2026-11-12  
**Product Vision:** Complete, production-ready Nepal-first School Management System  

---

## 1. Existing Nepal Foundation (Verified in Codebase)

| Domain | Existing Implementation | Status |
|---|---|---|
| **Dual Calendar (BS/AD)** | [`BikramSambatService`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Services/BikramSambatService.php), [`NepaliDateService`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Services/NepaliDateService.php), `ernilambar/nepali-date`, `nepali_datepicker.js`, `nepali_bs_calendar.js` | **Implemented & Tested** |
| **Academic Years & Holidays** | `academic_years` (`year_bs`, `start_date_bs`, `end_date_bs`, `is_active`, `is_closed`), `holidays` | **Implemented & Tested** |
| **Nepal Grading & Marks** | [`NepalGradingService`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Services/NepalGradingService.php), `theory_max/pass`, `practical_max/pass`, `credit_hour`, `gpa_point`, `mark_audit_trails` | **Implemented (Scale hardcoded as constant)** |
| **Attendance & Offline Sync** | `student_attendances`, `staff_attendances`, `attendance_audit_trails`, `AttendanceController::sync()` | **Implemented & Tested** |
| **NPR Finance & Billing** | `payments`, `payment_records` (`DECIMAL(10,2)`), `payment_idempotency_keys`, `due_date_bs`, `billing_month_bs`, [`NepaliHelper`](file:///z:/Xampp/htdocs/SMS_Laravel/app/Helpers/NepaliHelper.php) | **Implemented & Tested** |
| **Multilingual Support** | `en.json`, `ne.json`, `resources/lang/{en,ne}/*.php`, `Noto Sans Devanagari` typography | **Implemented & Tested** |

---

## 2. Feature Gap Analysis (Missing Nepal-Specific Capabilities)

| Gap ID | Domain | Current State | Required Enhancement |
|---|---|---|---|
| **NPL-001** | **School Classification & Profile** | Generic `settings` key-value table only; lacks EMIS code, school type (`community`/`institutional`/`montessori`/`technical`), PAN/VAT, Nepali name (`name_ne`), and administrative address. | Create `school_profiles` table & `SchoolProfile` model with Nepal administrative metadata. |
| **NPL-002** | **Nepal Geographic Hierarchy** | Legacy `states` / `lgas` tables from generic template. | Add `nepal_provinces`, `nepal_districts`, and `nepal_local_levels` tables with seeded 7 Provinces & 77 Districts (supporting municipal/rural municipal + manual fallback). |
| **NPL-003** | **Education Levels & Streams** | Legacy `class_types` (`N`, `PN`, `C`, `P`, `J`, `S`) without ECD, Basic (1–8), Secondary (9–10), Higher Secondary (11–12), or Technical/Vocational flags. | Add `nepal_education_levels` & `academic_streams` (e.g., Science, Management, Humanities, Education, Technical) linked cleanly to `my_classes`. |
| **NPL-004** | **Versioned Curriculum** | `subjects` links directly to `my_classes` without curriculum versioning, compulsory/optional/elective classification, or historical immutability. | Add `curricula` and `curriculum_subjects` tables supporting CDC-aligned configurable subject structures and effective dates. |
| **NPL-005** | **Configurable Grading Rules** | `NepalGradingService::STANDARD_SCALE` is a hardcoded PHP constant; cannot vary by academic year, level, or authority. | Add `grading_scales` table & update `NepalGradingService` to load configurable scales with CDC standard fallback. |
| **NPL-006** | **SEE & NEB Candidate Tracking** | No Grade 10 SEE candidate symbol-number/eligibility/result record or Grade 11–12 stream tracking. | Add `see_candidates` table for school-level SEE registration, symbol numbers, theory/practical/internal tracking, and GPA history (no fabricated NEB API claims). |
| **NPL-007** | **Student Nepali Identity & Family** | `student_records` lacks `name_ne`, `dob_bs`, `admission_date_bs`, `father_name`, `mother_name`, `ward_no`, `house`, `scholarship_category`. | Extend `student_records` with nullable backward-compatible Nepal student & guardian fields. |
| **NPL-008** | **Nepal Fiscal Year & Accounting** | `academic_years` exists, but `fiscal_years` (Shrawan–Ashadh) and general `finance_ledgers` (income/expense cashbook) are missing. | Add `fiscal_years` and `finance_ledgers` tables with decimal-safe accounting entries. |
| **NPL-009** | **Transport & Certificates** | No `transport_routes` / `student_transports` or verifiable `student_certificates` (Transfer, Character, Bonafide). | Add `transport_routes`, `student_transports`, and `student_certificates` (with unique verification code, BS/AD dates). |
| **NPL-010** | **IEMIS / EMIS Statistical Export** | No consolidated enrollment, gender, scholarship, and attendance summary export for MoEST/CEHRD reporting. | Add `IemisReportService` generating structured, versioned statistical summaries from live DB records. |

---

## 3. Nepal Compliance & Data Requirements Register

| Authority / Domain | Reference / Convention | Mandatory vs Optional | Implementation Impact & Claims Boundary |
|---|---|---|---|
| **MoEST / CDC** | School Education Levels (ECD, Basic 1–8, Secondary 9–10, Higher Secondary 11–12) & Letter Grading Guideline (35% pass threshold, 8-grade A+ to NG scale) | Configurable / Standard | Implemented via `NepalGradingService` and configurable `grading_scales` / `curricula`; schools may adjust if CDC updates guidelines. |
| **NEB / OCE** | SEE (Grade 10) & Grade 11–12 Streams / Symbol Numbers / Practical+Theory separation | School Internal Tracking | Tracks candidate records, symbol numbers, and internal/practical/theory marks. **Does NOT claim automated official NEB portal submission or API sync.** |
| **CEHRD (IEMIS)** | School Code, Enrollment by Grade/Gender/Scholarship, Teacher counts | Export Readiness | `IemisReportService` compiles school-level statistical exports. **Does NOT claim direct live government IEMIS API integration.** |
| **IRD (Tax/Billing)** | Fiscal Year (Shrawan 1 – Ashadh End), PAN/VAT fields on invoices/receipts | Configurable | Supports PAN/VAT numbers and fiscal-year grouping. **Does NOT claim automatic IRD CBMS real-time e-billing sync.** |
| **Nepal Geography** | 7 Provinces, 77 Districts, 753 Local Levels | Seeded Master + Manual Fallback | Seeds all 7 Provinces and 77 Districts accurately; supports municipal/local-level entry and manual override. |
| **Data Privacy** | Child & Student PII Protection | Mandatory | Strict role/parent-child authorization, `no_store` headers, minimal identity collection, audit logs. |

---

## 4. Phased Implementation Roadmap

- **Phase 1 (Current):** Audit, gap analysis, compliance register, database plan (`docs/NEPAL_REQUIREMENTS_AUDIT.md`).
- **Phase 2:** Nepal Core Foundation — `nepal_provinces`, `nepal_districts`, `nepal_local_levels`, `school_profiles`, `fiscal_years` migrations, models, and seeders.
- **Phase 3:** Academic, Curriculum & Grading Engine — `nepal_education_levels`, `curricula`, `curriculum_subjects`, `grading_scales`, `see_candidates` migrations/models + `NepalGradingService` dynamic scale support.
- **Phase 4:** Student, Finance, Transport, Certificates & IEMIS Reporting — extend `student_records`, add `finance_ledgers`, `transport_routes`, `student_transports`, `student_certificates`, and `IemisReportService`.
- **Phase 5:** Controllers, Routes, Admin UI Views & Comprehensive PHPUnit Verification across all new Nepal modules.

