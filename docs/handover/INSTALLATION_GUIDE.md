# SMS Laravel — Clean Production Installation Guide

**Target Audience**: DevOps Engineers, System Administrators, School IT Officers  
**Platform**: Linux (Ubuntu 22.04 LTS / Debian 12 / RHEL 9) or Windows Server (IIS / XAMPP)  
**Framework**: Laravel 8 on PHP 8.2+ with MySQL 8.0+ / MariaDB 10.4+  

---

## 1. System Requirements

### Hardware Prerequisites
- **CPU**: 2+ Cores (4+ recommended for high-concurrency admission/exam periods)
- **RAM**: 4 GB minimum (8 GB recommended)
- **Disk**: 40 GB+ NVMe/SSD storage (with dedicated backup volume)

### Software & PHP Extensions
- **PHP**: `8.0`, `8.1`, or `8.2`
- **Required Extensions**:
  - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`, `zlib`, `curl`, `bcmath`
- **Database**: MySQL 8.0+ or MariaDB 10.4+ with InnoDB engine
- **Web Server**: Nginx 1.20+ or Apache 2.4+ with `mod_rewrite` enabled

---

## 2. Server Setup & Repository Preparation

### Step 1: Clone Repository & Checkout Handover Branch
```bash
git clone https://github.com/mohit282-cpu/SMS_Laravel.git /var/www/sms_laravel
cd /var/www/sms_laravel
git checkout testing
```

### Step 2: Install Composer Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Step 3: Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Edit `.env` to configure production parameters:
```dotenv
APP_NAME="Apex Model Secondary School"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://school.yourdomain.edu.np

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_laravel_prod
DB_USERNAME=sms_user
DB_PASSWORD=YOUR_STRONG_DATABASE_PASSWORD

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

Generate the cryptographic application encryption key:
```bash
php artisan key:generate
```

---

## 3. Fresh Production Database Initialization

Execute the built-in, production-hardened installation command:

```bash
php artisan sms:install \
  --database=sms_laravel_prod \
  --school-name="Apex Model Secondary School" \
  --school-short="Apex School" \
  --school-address="Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal" \
  --school-phone="+977-25-560000" \
  --school-email="info@apexschool.edu.np" \
  --session="2083/84 BS" \
  --admin-name="Principal Officer" \
  --admin-username="school_superadmin" \
  --admin-email="superadmin@apexschool.edu.np" \
  --admin-phone="+977-9801234567" \
  --admin-password="SET_A_SECURE_SUPERADMIN_PASSWORD"
```

### What this command automates:
1. Connects to the target MySQL database and verifies engine compatibility.
2. Runs all 57 schema migrations to create clean database tables.
3. Seeds essential reference data:
   - 7 Provinces, 77 Districts, 753 Municipalities of Nepal (MoFAGA).
   - Official blood groups, user types, class types, and grading scales.
   - Initial academic session (2083/84 BS).
4. Creates the initial Super Admin account using secure bcrypt hashing.
5. Populates official school settings (no dummy Nigerian or demo data).
6. Creates required storage directories and establishes the `public/storage` symlink.
7. Clears and optimizes application caches.

---

## 4. File Permissions & Web Server Configuration

### File Permissions (Linux)
```bash
chown -R www-data:www-data /var/www/sms_laravel
find /var/www/sms_laravel -type d -exec chmod 755 {} \;
find /var/www/sms_laravel -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/sms_laravel/storage /var/www/sms_laravel/bootstrap/cache
```

### Nginx Virtual Host Configuration
```nginx
server {
    listen 80;
    server_name school.yourdomain.edu.np;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name school.yourdomain.edu.np;

    ssl_certificate /etc/letsencrypt/live/school.yourdomain.edu.np/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/school.yourdomain.edu.np/privkey.pem;

    root /var/www/sms_laravel/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Verification & Health Check

Confirm system readiness via the HTTP probe:
```bash
curl -I https://school.yourdomain.edu.np/health/readiness
```
Expected response:
```http
HTTP/1.1 200 OK
Content-Type: application/json
{"status":"ready","timestamp":"..."}
```

