# Final Controlled School Pilot Validation Plan and Operational Guide

**Product:** SMS Academy — School Management System for Nepal  
**Company:** Sovryx Tech  
**Target Pilot Institution:** Model Secondary School (Representative Kathmandu Valley / Bagmati Province School)  
**Academic Year:** 2083/84 BS (2026/27 AD)  
**Verification Date:** November 1, 2026  
**Status:** **READY FOR CONTROLLED PILOT EXECUTION**

---

## 1. Executive Summary & Objective

The objective of the controlled school pilot is to validate SMS Academy under live operational conditions across an entire school term using synthetic data and authorized volunteer staff accounts before wide-scale deployment across schools in Nepal.

The pilot evaluates the end-to-end operational lifecycle:
1. Academic calendar configuration (Bikram Sambat 2083/84).
2. Class and section enrollment (Nursery through Grade 12).
3. Daily teacher attendance recording and offline synchronization.
4. Terminal examination scheduling, GPA mark tabulation, and grade-sheet printing.
5. Monthly fee invoice generation in Nepalese Rupees (NPR), partial fee collection, and receipt printing.
6. Multi-role access boundaries across 7 distinct portals (Super Admin, Admin, Accountant, Librarian, Teacher, Student, Parent).
7. Automated daily backup and disaster recovery validation.

---

## 2. Synthetic Test Environment & Privacy Guardrails

To strictly comply with privacy regulations and avoid exposing real student Personally Identifiable Information (PII):
- **Synthetic Student Rosters:** All test students are populated with synthetic Nepali names (e.g., Aarav Sharma, Bina Adhikari, Roshan Thapa).
- **Synthetic Guardian Information:** Parent phone numbers use reserved test numbers (`9800000001` - `9800000050`).
- **Clean Audit Logs:** Test transactions are tagged in `audit_logs` under category `PILOT_TEST` for separate tracking.

---

## 3. End-to-End Pilot Workflow Validation

| Stage | Operational Workflow | Role | Acceptance Criteria | Pilot Status |
| :--- | :--- | :--- | :--- | :--- |
| **1. School Setup** | Configure school name, logo, address (Kathmandu, Ward 4), and BS calendar settings | Super Admin | School branding reflects across navbar, receipts, and PWA manifest | **VERIFIED** |
| **2. Academic Year** | Initialize Session `2083/84` with start/end BS dates | Admin | Current session properly resolves in dropdowns and invoice generation | **VERIFIED** |
| **3. Class & Section** | Create classes (Grade 1 to 10) with sections A, B | Admin | Capacity limits, class teacher assignment operational | **VERIFIED** |
| **4. Staff Provisioning** | Add teachers, accountants, and librarians | Admin | Users receive onboarding credentials; plaintext passwords never flashed | **VERIFIED** |
| **5. Student Enrollment**| Register synthetic students with BS birth dates | Admin / Super Admin | Student record created, user code generated, parent account automatically linked | **VERIFIED** |
| **6. Parent Portal** | Parent logs in via phone/email to view linked children | Parent | Parent can only view linked children; horizontal privilege escalation blocked | **VERIFIED** |
| **7. Subject Setup** | Assign core subjects (Nepali, English, Comp. Maths, Science, Social Studies) | Admin | Class timetable properly links assigned teachers and subjects | **VERIFIED** |
| **8. Daily Attendance** | Teacher marks attendance for morning roll call (09:45 NPT) | Teacher | Dual BS/AD date recorded; composite index prevents duplicate submission | **VERIFIED** |
| **9. Offline Sync** | Mark attendance with severed internet connection, then reconnect | Teacher | IndexedDB stores offline roster; batch sync merges without conflicts | **VERIFIED** |
| **10. Terminal Exam** | Create First Terminal Examination, schedule exam dates | Admin | Timetable visible to students only after administrator publishing | **VERIFIED** |
| **11. Marks Tabulation**| Subject teacher inputs theory and practical marks | Teacher | Teachers can only enter marks for assigned classes; out-of-range marks rejected | **VERIFIED** |
| **12. Report Cards** | Generate terminal grade sheets with SEE letter grading (A+, A, B+, B, C, D) | Admin / Teacher | Nepali number formatting and GPA calculations verified | **VERIFIED** |
| **13. Fee Invoicing** | Generate monthly tuition invoices across 12 BS months | Accountant | Invoices generated idempotently; scholarship and discount deductions applied | **VERIFIED** |
| **14. Fee Collection** | Accountant records cash fee payment against student invoice | Accountant | Concurrency lock prevents double-receipting; NPR change correctly calculated | **VERIFIED** |
| **15. Receipt Print** | Print dual-language receipt with invoice number and school stamp | Accountant | Printable CSS renders clean A4/half-page receipt without UI chrome | **VERIFIED** |
| **16. Web Push** | Dispatch school holiday or notice notification | Admin | Service worker delivers push notification to subscribed staff/parent devices | **VERIFIED** |
| **17. Disaster Recovery**| Nightly automated backup generation and test restoration | DevOps / Admin | `php artisan db:backup --compress --include-files` creates verified archive | **VERIFIED** |

---

## 4. Disaster Recovery & Operational Readiness

During the school pilot:
- **Daily Automated Backup:** Scheduled at `02:00 NPT` via system crontab:
  ```cron
  0 2 * * * cd /var/www/sms_academy && php artisan db:backup --compress --include-files --retention-days=14 >> /var/log/sms_backup.log 2>&1
  ```
- **Staging Test Restore:** Run weekly into a temporary staging database to verify backup integrity:
  ```bash
  php artisan db:restore storage/app/backups/latest.sql.gz --target-db=sms_pilot_restore_verify --verify-checksum --force
  ```
- **RTO / RPO Commitments:** RTO < 15 minutes, RPO < 24 hours.

---

## 5. Staff Training & Feedback Protocol

1. **Teacher Feedback:** Assess mobile attendance marking usability during morning assembly.
2. **Accountant Feedback:** Verify monthly fee collection speed during high-volume fee weeks.
3. **Parent Feedback:** Measure satisfaction with child marksheet and attendance visibility.
4. **Defect Tracking:** All pilot issues must be logged with timestamp, user role, URL, error log snippet, and reproduction steps.
