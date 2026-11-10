# Continuous Deployment Guide — SMS Academy

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Workflow:** `.github/workflows/cd.yml`  
**Date:** 2026-11-10  
**Target Architecture:** Linux / Nginx / PHP 8.2-FPM / MySQL 8.0  

---

## 1. Overview & Release Strategy

SMS Academy uses an **atomic symlink release deployment strategy**. Rather than deploying in-place over active files, each release is unpacked into an isolated directory (`releases/<COMMIT_SHA>`), verified, and then activated near-instantaneously by updating the `current` symlink.

```text
/var/www/sms_academy/
├── current -> /var/www/sms_academy/releases/b7aec60f...
├── releases/
│   ├── a1b2c3d4...
│   └── b7aec60f... (active release)
└── shared/
    ├── .env
    └── storage/
        ├── app/
        ├── framework/
        │   ├── cache/
        │   ├── sessions/
        │   └── views/
        └── logs/
```

This guarantees:
- **Zero Downtime:** Atomic symlink update (`ln -sfn`) ensures requests never hit half-copied files.
- **Data Preservation:** Student uploads, generated PDF report cards, and logs reside in `shared/storage` and persist across releases.
- **Safety First:** Automated pre-migration database backup (`php artisan db:backup`) is taken before any schema migrations run.
- **Instant Rollback:** If a defect is detected post-deployment, pointing the symlink back to the previous release restores service immediately.

---

## 2. Server Prerequisites

### 2.1 Software Stack
- **Operating System:** Ubuntu 22.04 LTS or 24.04 LTS
- **Web Server:** Nginx 1.18+ or Apache 2.4+
- **PHP:** PHP 8.2 with extensions:
  `php8.2-cli`, `php8.2-fpm`, `php8.2-mysql`, `php8.2-mbstring`, `php8.2-xml`, `php8.2-bcmath`, `php8.2-gd`, `php8.2-zip`, `php8.2-curl`, `php8.2-gmp`
- **Database:** MySQL 8.0 or MariaDB 10.6+
- **Database Tool:** `mysqldump` available on PATH for `php artisan db:backup`

### 2.2 Directory Layout Setup
Execute the following on the target server once as `root` or `sudo`:
```bash
# Create base deployment tree
mkdir -p /var/www/sms_academy/{releases,shared/storage/{app/public,framework/{cache,sessions,views},logs}}

# Set ownership to web server user
sudo chown -R www-data:www-data /var/www/sms_academy
sudo chmod -R 775 /var/www/sms_academy/shared/storage

# Add deploy user to www-data group
sudo usermod -aG www-data deploy
```

### 2.3 Shared Environment Configuration (`shared/.env`)
Create `/var/www/sms_academy/shared/.env`:
```ini
APP_NAME="SMS Academy"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://sms.yourschool.edu.np

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_academy_prod
DB_USERNAME=sms_user
DB_PASSWORD=YOUR_STRONG_DB_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 2.4 Nginx Virtual Host Configuration
Ensure the Nginx document root points to `/var/www/sms_academy/current/public`:
```nginx
server {
    listen 80;
    server_name sms.yourschool.edu.np;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sms.yourschool.edu.np;

    root /var/www/sms_academy/current/public;
    index index.php index.html;

    # SSL configuration here (Let's Encrypt / Certbot)

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

---

## 3. GitHub Actions Configuration

### 3.1 GitHub Environments Setup
1. In the GitHub repository, navigate to **Settings** > **Environments**.
2. Create two environments:
   - `staging`
   - `production`
3. For `production`, configure **Required reviewers** (e.g. Lead DevOps / Lead Architect approval required before deployment execution).

### 3.2 Required Secrets and Variables
Configure the following inside each environment:

#### Environment Secrets
| Secret Name | Description | Example |
|---|---|---|
| `DEPLOY_HOST` | Target server IP or hostname | `198.51.100.50` |
| `DEPLOY_USER` | SSH deployment user on target server | `deploy` |
| `DEPLOY_SSH_KEY` | Private SSH key authorized in `~deploy/.ssh/authorized_keys` | `-----BEGIN OPENSSH PRIVATE KEY...` |

#### Environment Variables
| Variable Name | Description | Default / Example |
|---|---|---|
| `DEPLOY_PATH` | Base deployment directory on server | `/var/www/sms_academy` |
| `APP_URL` | Application public URL for health check | `https://sms.yourschool.edu.np` |

---

## 4. How to Trigger a Deployment

1. Go to the **Actions** tab in GitHub.
2. Under **Workflows**, select **CD (Deployment)**.
3. Click **Run workflow**:
   - **Target Deployment Environment:** Select `staging` or `production`.
   - **Execute database migrations:** `true` (default).
   - **Enable maintenance mode during deployment:** `true` (default).
4. Click **Run workflow**.

---

## 5. Deployment Stages & Execution Flow

```mermaid
sequenceDiagram
    autonumber
    actor Dev as Release Engineer
    participant GH as GitHub Actions (cd.yml)
    participant Host as Production Server

    Dev->>GH: Trigger CD Workflow (staging/production)
    GH->>GH: Validate PHP & compile production frontend assets
    GH->>GH: Package sanitized release tarball
    GH->>Host: Transfer release tarball to /tmp
    Host->>Host: Unpack into /var/www/sms_academy/releases/<SHA>
    Host->>Host: Symlink shared/.env and shared/storage
    Host->>Host: Run php artisan db:backup --compress
    Host->>Host: Run php artisan down (maintenance mode)
    Host->>Host: Run php artisan migrate --force
    Host->>Host: Warm caches (config, route, view)
    Host->>Host: Atomically update 'current' symlink
    Host->>Host: Run php artisan up
    Host->>Host: Reload php8.2-fpm
    GH->>Host: Execute HTTP smoke test (APP_URL)
    GH-->>Dev: Deployment Successful!
```

---

## 6. Post-Deployment Verification Checklist

After the CD pipeline completes, verify:
- [ ] Application responds with HTTP 200 at `APP_URL`.
- [ ] Login screen renders properly with compiled CSS and JS (`/login`).
- [ ] Super Admin and Admin can log in to `/dashboard`.
- [ ] Student list and attendance records load properly.
- [ ] PWA manifest loads at `/manifest.json`.
- [ ] `storage/logs/laravel.log` shows no errors.
- [ ] A pre-migration backup was created in `storage/app/backups/`.

