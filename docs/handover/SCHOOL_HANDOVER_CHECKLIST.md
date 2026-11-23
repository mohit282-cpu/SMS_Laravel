# School Deployment & Handover Verification Checklist

**Application**: SMS Laravel (School Management System)  
**Target Institution**: Apex Model Secondary School, Koshi Province, Nepal  
**Handover Date**: 2026-11-15  
**Release Version**: 1.0.0-PROD  
**Branch**: `testing`  

---

## 1. System Delivery & Verification Checklist

### A. Environment & Infrastructure Readiness
- [x] PHP 8.2+ runtime verified with required extensions (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`, `zlib`).
- [x] Web server (Nginx / Apache) configured with URL rewriting and document root pointed strictly to `/public`.
- [x] HTTPS enforced with SSL/TLS certificate installed.
- [x] Storage permissions configured (directories `0755` / `0750`, files `0640`).
- [x] Public storage symlink active (`php artisan storage:link`).
- [x] Application key generated (`APP_KEY` set in `.env`).

### B. Fresh Database & Reference Data
- [x] All 57 verified schema migrations executed in sequence.
- [x] Target production database initialized (`sms_laravel_prod`).
- [x] Zero demo/test records in operational tables:
  - Students: 0
  - Teachers: 0 (configured by school)
  - Parents: 0
  - Marks / Exams: 0
  - Payments / Receipts: 0
  - Library Books / Requests: 0
- [x] Official Nepal geographic master data seeded (7 Provinces, 77 Districts, 753 Local Levels).
- [x] Blood groups, user types, class types, grades, and academic sessions seeded.
- [x] Foreign-key constraint integrity verified with 0 orphan records.

### C. Super Admin & School Identity Setup
- [x] Initial Super Admin account provisioned via secure `php artisan sms:install`.
- [x] Account credentials verified through authenticated dashboard access.
- [x] Password hashed using Laravel's secure bcrypt implementation.
- [x] School profile configured (Official Name, Acronym, Address, Contact, Nepal Timezone `Asia/Kathmandu`, Currency `NPR`, Session `2083/84 BS`).
- [x] No temporary demo credentials (`cj`, `admin`, `teacher`, `student`) exist in the production database.

### D. Security & Hardening Gate
- [x] `APP_DEBUG=false` in production configuration.
- [x] Secure session cookies (`SESSION_SECURE_COOKIE=true`, `http_only=true`, `same_site=lax`).
- [x] CSRF protection enabled across all state-changing endpoints.
- [x] Rate limiting active on authentication and admissions search routes.
- [x] Parent-child student isolation verified.
- [x] Teacher section and subject restrictions active.
- [x] Role-based middleware (`super_admin`, `teamSA`, `teacher`, `parent`, `accountant`) enforced.
- [x] Sensitive fields redacted from logs and diagnostic endpoints.

### E. Backup & Disaster Recovery
- [x] Point-in-time pre-handover backup created (`sms-pre-handover-20261115-195915.sql`).
- [x] SHA-256 integrity checksum calculated and recorded.
- [x] Restoration verified in isolated test database (`sms_laravel_test`).
- [x] Automated backup command documented (`php artisan db:backup`).
- [x] Disaster recovery procedure documented with step-by-step restoration commands.

---

## 2. Handover Sign-off Form

| Role | Name | Signature / Authorization | Date |
|---|---|---|---|
| **Senior Laravel Architect** | Dev Team Lead | *Verified & Tested* | 2026-11-15 |
| **Database Administrator** | DBA Engineer | *Verified & Tested* | 2026-11-15 |
| **QA / Security Lead** | Security Specialist | *Verified & Tested* | 2026-11-15 |
| **School Representative** | Principal / IT Admin | *Pending Acceptance Review* | — |

