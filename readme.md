# SMS Academy — School Management System

**SMS Academy** is a comprehensive school and campus management application built on Laravel. It provides academic, administrative, and financial lifecycle workflows for schools, including support for dual calendar systems (Gregorian AD and Nepali Bikram Sambat BS), timetable management, examinations, fee processing, progressive web application (PWA) features with Web Push notifications, library circulation, and multi-role student records.

---

## System Architecture & Roles

SMS Academy enforces strict role-based access control (RBAC) across seven distinct user roles:

- **Super Administrator**: Complete administrative authority, user provisioning, system settings, destructive mutations, and staff credential management.
- **Administrator**: Academic operations, student registrations, class/section assignments, and examinations.
- **Accountant**: Fee structures, payment processing, invoice tracking, and receipt printing.
- **Librarian**: Book cataloguing, circulation management, loan approvals, and inventory tracking.
- **Teacher**: Class/section rosters, attendance marking, study materials upload, mark entry, and timetable access.
- **Student**: Read-only grade reports, class timetable, library requests, and school notices.
- **Parent**: Consolidated views of linked children's attendance, marks, timetables, and invoices.

---

## Requirements

- **PHP**: 8.1 or 8.2 (with `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `gmp` or `bcmath` extensions)
- **Database**: MySQL 5.7+ or MariaDB 10.3+ (Test database: `sms_laravel_test` on `mysql_test` connection)
- **Composer**: 2.x
- **Node.js & NPM**: Node 14.x for legacy Laravel Mix 2 asset compilation (`npm ci` & `npm run prod` via committed `package-lock.json`); modernization to Vite/Laravel Mix 6 planned in roadmap.

---

## Installation & Local Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mohit282-cpu/SMS_Laravel.git
   cd SMS_Laravel
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit `.env` to configure your MySQL database credentials:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sms_laravel
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Run migrations and seed baseline data:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Generate VAPID keys for Web Push notifications:**
   ```bash
   php artisan webpush:vapid
   ```

6. **Serve the application:**
   ```bash
   php artisan serve
   ```
   Access the system at `http://127.0.0.1:8000`.

---

## Initial Administrative Provisioning & Password Security

In adherence to security hardening standards:
- **No predictable or hardcoded passwords** are provisioned in production.
- When new student or staff accounts are provisioned via administrative panels or seeders, cryptographically secure random temporary credentials are generated.
- Administrators are required to share temporary credentials securely with recipients, who should change their passwords upon first login.
- Password resets must be initiated via administrative POST actions with CSRF protection, and reset events are recorded in audit logs.

---

## Safe & Repeatable Testing

SMS Academy uses an isolated, disposable test database connection (`mysql_test`) configured in `phpunit.xml` to prevent any possibility of data loss on development or production databases.

1. **Create the test database:**
   ```sql
   CREATE DATABASE IF NOT EXISTS sms_laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Run migrations on the test database:**
   ```bash
   php artisan migrate --database=mysql_test
   php artisan db:seed --database=mysql_test
   ```

3. **Execute the test suite:**
   ```bash
   php artisan test
   ```

---

## Security Safeguards & Hardening Highlights

1. **Anti-SSRF Web Push Validation**: All push subscription endpoints are strictly validated (`PushEndpointValidator`) against loopback, private RFC1918 CIDRs, link-local addresses, cloud metadata services, and DNS rebinding attacks. Outbound HTTP redirects are disabled on WebPush delivery.
2. **Strict Cache-Control on Private Responses**: Middleware `NoStorePrivateResponses` enforces `Cache-Control: private, no-store, no-cache, must-revalidate` across all authenticated views, API endpoints, binary downloads (PDF receipts, study materials), and streamed files.
3. **Idempotent Payment Integrity**: Payment endpoints (`payments.pay_now`) bind idempotency keys to invoice IDs and payment amounts within row-level locked database transactions (`lockForUpdate`), preventing double charges, race conditions, and parameter tampering.
4. **Exact Scope Visibility**: Study materials and timetables enforce strict class, section, and subject boundaries. Teachers cannot view or download materials belonging to unassigned sections.
5. **Sanitized Exception Handling**: Server-side errors and SQL exceptions generate unique UUID correlation IDs for system logs without leaking table names, queries, or stack traces to end users.

---

## Modernization & Upgrade Path (Laravel 8 -> 10/11)

The application currently targets Laravel 8.40 on PHP 8.2. Because Laravel 8 is past its upstream security support window, modernizing to an LTS or supported release is strongly recommended in phased increments:

1. **Phase 1 (Completed)**: PHP 8.2 compatibility verified; deprecations and nullable return types addressed; all regression tests passing.
2. **Phase 2 (Target: Laravel 9)**:
   - Upgrade `laravel/framework` to `^9.0`.
   - Update `nunomaduro/collision` to `^6.1`, `fakerphp/faker` to `^1.19`.
   - Replace `fideloper/proxy` with Laravel 9's native `Illuminate\Http\Middleware\TrustProxies`.
   - Update filesystem accessors to Flysystem v3 API.
3. **Phase 3 (Target: Laravel 10/11)**:
   - Upgrade `laravel/framework` to `^10.0` / `^11.0`.
   - Migrate route registrations to invokable/callable class tuples.
   - Enforce native type hints on all controller action signatures.

---

## Production Deployment Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Configure `APP_URL=https://your-domain.edu` with valid TLS certificates.
- [ ] Set `SESSION_SECURE_COOKIE=true` and `SESSION_HTTP_ONLY=true`.
- [ ] Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
- [ ] Run queue workers for Web Push notifications (`php artisan queue:work --daemon`).
- [ ] Maintain automated, off-site encrypted MySQL database backups.
- [ ] Restrict outbound server networking to essential push endpoints and mail relays.
