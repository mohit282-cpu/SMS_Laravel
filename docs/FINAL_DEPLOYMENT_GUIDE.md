# Final Deployment & Staging Infrastructure Guide

**Product:** SMS Academy — School Management System for Nepal  
**Company:** Sovryx Tech  
**Release Target:** Ubuntu 22.04 LTS VPS / Shared cPanel Hosting  
**Verification Date:** November 1, 2026  
**Status:** **READY FOR DEPLOYMENT**

---

## 1. System Requirements & Prerequisites

The production or staging server must provide:

### Runtime & Language
- **PHP 8.2 or 8.3** (CLI & FPM)
- **Required PHP Extensions:**
  - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `curl`, `zlib` (for compressed backups)
  - `memory_limit >= 256M`
  - `upload_max_filesize >= 20M`
  - `post_max_size >= 25M`
- **Database:** MySQL 8.0+ or MariaDB 10.4+ (Default charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci`)
- **Web Server:** Nginx 1.20+ or Apache 2.4+ (with `mod_rewrite` enabled)
- **HTTPS:** Valid TLS/SSL certificate (Let's Encrypt / Certbot) is **mandatory** for PWA Service Worker and Web Push notifications.

---

## 2. Server Deployment Options

### Option A: Modern VPS Deployment (Ubuntu 22.04 LTS / Nginx / PHP 8.2-FPM)

#### Step 1: Clone Repository and Set Permissions
```bash
cd /var/www
git clone -b testing https://github.com/mohit282-cpu/SMS_Laravel.git sms_academy
cd sms_academy

# Set directory ownership
sudo chown -R www-data:www-data /var/www/sms_academy
sudo chmod -R 775 /var/www/sms_academy/storage /var/www/sms_academy/bootstrap/cache
```

#### Step 2: Install Composer Dependencies
```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

#### Step 3: Configure Production Environment (`.env`)
```bash
cp .env.example .env
php artisan key:generate
```
Configure critical keys in `.env`:
```ini
APP_NAME="SMS Academy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sms.yourschool.edu.np

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_academy_prod
DB_USERNAME=sms_db_user
DB_PASSWORD="SECURE_GENERATED_PASSWORD"

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

VAPID_PUBLIC_KEY="YOUR_GENERATED_VAPID_PUBLIC_KEY"
VAPID_PRIVATE_KEY="YOUR_GENERATED_VAPID_PRIVATE_KEY"
VAPID_SUBJECT="mailto:admin@yourschool.edu.np"
```

#### Step 4: Run Database Migrations
```bash
php artisan migrate --force
php artisan db:seed --force
# Note: Demo users are automatically prevented from seeding in production.
```

#### Step 5: Pre-compile Caches & Symlink Storage
```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 6: Configure Nginx Virtual Host
```nginx
server {
    listen 80;
    server_name sms.yourschool.edu.np;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sms.yourschool.edu.np;

    root /var/www/sms_academy/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/sms.yourschool.edu.np/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sms.yourschool.edu.np/privkey.pem;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 180;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Step 7: Background Worker and Cron Scheduling
Create systemd cron entry (`sudo crontab -e -u www-data`):
```cron
* * * * * cd /var/www/sms_academy && php artisan schedule:run >> /dev/null 2>&1
0 2 * * * cd /var/www/sms_academy && php artisan db:backup --compress >> /var/log/sms_backup.log 2>&1
```

---

### Option B: Shared cPanel Hosting Deployment

When deploying to shared hosting without SSH root or Supervisor:
1. **Document Root Isolation:** Point the cPanel Primary Domain or Subdomain to `SMS_Laravel/public`.
   - If cPanel forces `public_html/`, move application code outside `public_html/` (e.g. `~/sms_core/`) and place only `public/*` inside `public_html/`, updating `index.php` paths accordingly.
2. **Database Provisioning:** Create database `cpaneluser_sms` and user via cPanel MySQL Database Wizard.
3. **Run Migrations via Cron or Web Setup:** Run `php artisan migrate --force` using cPanel Cron Jobs (once) or an authorized setup script.
4. **Automated Scheduled Tasks:** Add a cPanel cron job executing every 5 minutes:
   ```cron
   /usr/local/bin/php /home/cpaneluser/sms_core/artisan schedule:run >/dev/null 2>&1
   ```
5. **Nightly Database Backup Cron:**
   ```cron
   0 2 * * * /usr/local/bin/php /home/cpaneluser/sms_core/artisan db:backup --compress >/dev/null 2>&1
   ```

---

## 3. Post-Deployment Verification Checklist

- [ ] HTTPS lock icon active and valid across all pages.
- [ ] Login page displays school branding without plaintext temporary passwords.
- [ ] Admin dashboard loads cleanly.
- [ ] PWA Install banner triggers on Chrome/Edge and Android.
- [ ] Offline fallback page loads when network connection is severed.
- [ ] Dual Bikram Sambat and Gregorian calendars render properly.
- [ ] Student creation, fee payment, and PDF receipt printing verified.
- [ ] Database backup generated cleanly in `storage/app/backups/`.
- [ ] Error logs in `storage/logs/laravel.log` are empty or free of fatal exceptions.
