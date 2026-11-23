# System Maintenance and Operations Guide

**Schedule**: Daily, Weekly, Monthly, and Annual Maintenance Routines  

---

## 1. Routine Maintenance Schedule

### Daily Routines
- Monitor daily backup generation in `storage/app/backups/`.
- Verify readiness probe: `curl -s https://school.yourdomain.edu.np/health/readiness`.
- Inspect error rate in `storage/logs/laravel.log`.

### Weekly Routines
- Rotate application logs if log size exceeds 100 MB.
- Prune expired password reset tokens and temporary sessions:
  ```bash
  php artisan auth:clear-resets
  ```
- Verify offsite backup synchronization integrity.

### Monthly Routines
- Perform routine MySQL table optimization:
  ```sql
  OPTIMIZE TABLE audit_logs, student_attendances, marks, payment_records;
  ```
- Audit Super Admin and Staff account directory: deactivate resigned personnel.
- Review SSL certificate validity and auto-renewal status (`certbot renew --dry-run`).

### Annual (End of Academic Session) Routines
- **Session Transition Workflow**:
  1. Capture a verified year-end database backup:
     ```bash
     php artisan db:backup --database=sms_laravel_prod --compress --include-files
     ```
  2. Finalize all term evaluations and lock past exams.
  3. Execute student class promotion via **Students -> Promotion** (`/students/promotion`).
  4. Activate the incoming academic session (e.g., `2084/85 BS`) in System Settings.

---

## 2. Maintenance Mode & Planned Outages

When performing server operating system updates or major version upgrades:

### Activating Maintenance Mode
```bash
php artisan down --secret="school-ops-bypass-key" --render="errors::503"
```
Staff with the secret key can bypass maintenance by visiting:
`https://school.yourdomain.edu.np/school-ops-bypass-key`

### Deactivating Maintenance Mode
```bash
php artisan up
```

