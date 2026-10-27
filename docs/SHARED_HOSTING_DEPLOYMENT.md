# ICTHospital - Shared Hosting Deployment Guide (Hostinger & cPanel)

This guide provides step-by-step instructions for deploying and running **ICTHospital** (Laravel 11) on shared hosting environments such as **Hostinger (hPanel)**, **cPanel**, **DirectAdmin**, or **Plesk**.

---

## 1. Prerequisites Checklist

Before beginning, ensure your shared hosting account meets the following requirements:

| Requirement | Supported Version | How to verify / enable |
| :--- | :--- | :--- |
| **PHP Version** | **PHP 8.2 or 8.3** (Required) | In Hostinger hPanel: **Advanced &rarr; PHP Configuration** |
| **PHP Extensions** | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `bcmath`, `gd`, `xml`, `curl` | Default in PHP 8.2+ on Hostinger & cPanel |
| **MySQL / MariaDB** | MySQL 5.7+ or MariaDB 10.3+ | In hPanel: **Databases &rarr; Management** |
| **SSL (HTTPS)** | Free Let's Encrypt | In hPanel: **Security &rarr; SSL** (1-click activate) |
| **Disk Space** | &ge; 500 MB | Standard shared hosting allocation |

---

## 2. Architecture: How Shared Hosting Works with ICTHospital

In standard shared hosting, the web server serves files directly out of `public_html/`. Laravel's entry point is inside `/public/index.php`. 

ICTHospital supports **two clean deployment methods**:

### Method A: Single-Folder Deployment via Root `.htaccess` (Recommended)
Everything lives directly inside `public_html/`. The included root [`.htaccess`](file:///d:/ICTHospital/.htaccess) automatically:
1. Rewrites all incoming HTTP requests to `/public/` transparently.
2. Denies web access to sensitive files (`.env`, `.git`, `composer.*`, `storage/logs/`, etc.).
3. Enables Gzip/Deflate compression and browser caching.

### Method B: Split Root Deployment (Traditional cPanel Style)
If your host prefers keeping core code above `public_html`:
1. Upload the project core to `/home/uXXXXX/icthospital/` (one level above `public_html`).
2. Move only the contents of `/public/*` into `/home/uXXXXX/public_html/`.
3. Edit `public_html/index.php` to point to the parent folder:
   ```php
   require __DIR__.'/../icthospital/vendor/autoload.php';
   $app = require_once __DIR__.'/../icthospital/bootstrap/app.php';
   ```

---

## 3. Step-by-Step Deployment on Hostinger hPanel

### Step 1: Select PHP 8.2 or 8.3
1. Log in to **Hostinger hPanel**.
2. Navigate to your website dashboard &rarr; **Advanced** &rarr; **PHP Configuration**.
3. Select **PHP 8.2** or **PHP 8.3** and click **Update**.
4. In the **PHP Extensions** tab, ensure `fileinfo`, `gd`, `mbstring`, `pdo_mysql`, and `bcmath` are checked.

### Step 2: Create MySQL Database & User
1. Go to **Databases** &rarr; **Management**.
2. Under **Create a New MySQL Database And Database User**:
   - **Database Name**: e.g., `u123456789_icthosp`
   - **Username**: e.g., `u123456789_admin`
   - **Password**: Enter a strong password.
3. Click **Create** and record these three credentials.

### Step 3: Upload ICTHospital Code
Choose either **Option 3.1** (Git) or **Option 3.2** (ZIP upload):

#### Option 3.1: Hostinger Git Deployment (Fastest & Easiest)
1. Go to **Advanced** &rarr; **Git** in hPanel.
2. Configure:
   - **Repository**: `https://github.com/Anshik011/ICTHospital.git`
   - **Branch**: `main`
   - **Install Directory**: `public_html`
3. Click **Create** and then **Deploy**.

#### Option 3.2: Upload ZIP via File Manager
1. On your local machine, download or ZIP the repository (including `vendor/`).
2. Go to **Files** &rarr; **File Manager** in hPanel.
3. Open `public_html/`, click **Upload**, and select your ZIP file.
4. Right-click the uploaded ZIP and click **Extract**.
5. Move the extracted files into the root of `public_html/`.

---

### Step 4: Configure the Production `.env`
1. In **File Manager**, locate `.env.shared-hosting.example`.
2. Rename or copy it to `.env`:
   ```bash
   cp .env.shared-hosting.example .env
   ```
3. Open `.env` in the editor and update:
   ```env
   APP_NAME="ICTHospital"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourhospitaldomain.com

   # Hostinger Database credentials from Step 2
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u123456789_icthosp
   DB_USERNAME=u123456789_admin
   DB_PASSWORD="your_strong_password"

   # Keep synchronous on shared hosting (no daemon workers needed)
   QUEUE_CONNECTION=sync
   CACHE_STORE=file
   SESSION_DRIVER=database

   # Setup helper secret passkey (for Step 5)
   INSTALL_HELPER_KEY="my_secret_setup_token_8829"
   ```
4. Save the file.

---

### Step 5: Run Database Migrations & Link Storage

Choose between **Method 5.1 (SSH Terminal)** or **Method 5.2 (Web Setup Helper)**:

#### Method 5.1: Via Hostinger SSH Terminal (Automated)
1. Go to **Advanced** &rarr; **SSH Access** in hPanel and click **Enable**.
2. Connect using terminal or web console:
   ```bash
   cd public_html
   bash deploy-shared-hosting.sh
   ```
   *The script automatically detects PHP 8.2+, creates directory permissions, runs `composer install`, executes `php artisan migrate --force`, links storage, and caches configurations!*

#### Method 5.2: Via Browser Setup Helper (No SSH Needed)
If SSH is not enabled on your hosting plan:
1. Open your browser and go to:
   ```
   https://yourhospitaldomain.com/install-helper.php
   ```
2. Enter the `INSTALL_HELPER_KEY` you configured in `.env`.
3. Follow the 4-step wizard:
   - **Test DB Connection**: Confirms connection to your Hostinger database.
   - **Run Migrations (`--force`)**: Creates all 50+ hospital database tables, roles, and schema.
   - **Link Public Storage**: Links uploaded patient records, lab files, and prescription logos.
   - **Cache & Optimize**: Generates route, config, and view caches for maximum speed.
4. Click **Lock Setup & Return to Hospital Portal** (this creates `storage/installed.lock` and deletes `install-helper.php` from your server so it cannot be accessed again).

---

### Step 6: Configure the 1-Minute Hostinger Cron Job
Hospital appointment reminders, SMS dispatches, and billing schedules require Laravel's scheduler.

1. Go to **Advanced** &rarr; **Cron Jobs** in hPanel.
2. Select **Custom** cron.
3. In **Command**, enter:
   ```bash
   * * * * * cd /home/uXXXXX/public_html && /usr/bin/php8.2 artisan schedule:run >> /dev/null 2>&1
   ```
   *(Replace `/home/uXXXXX/public_html` with your exact full home directory path displayed in hPanel)*.
4. Click **Save**.

---

### Step 7: Email / SMTP Configuration
To send password resets, patient invoices, and doctor notifications:
1. In hPanel, create an email account under **Emails** &rarr; **Email Accounts** (e.g., `noreply@yourhospitaldomain.com`).
2. Update your `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=noreply@yourhospitaldomain.com
   MAIL_PASSWORD="your_email_password"
   MAIL_ENCRYPTION=ssl
   MAIL_FROM_ADDRESS="noreply@yourhospitaldomain.com"
   MAIL_FROM_NAME="ICTHospital"
   ```

---

## 4. Troubleshooting & FAQ

### Q1: I see a blank white page or "500 Internal Server Error"
1. Verify directory write permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
2. Check the daily error log in `storage/logs/laravel-YYYY-MM-DD.log`.
3. Ensure `.env` exists and contains a valid `APP_KEY`. If empty, run `php artisan key:generate`.

### Q2: Patient photos or prescription logos do not display
- In shared hosting, `public/storage` must point to `storage/app/public`.
- If symlinks are restricted by your host, open File Manager and manually copy `storage/app/public` contents into `public/storage`, or run **Link Public Storage** from `install-helper.php`.

### Q3: "419 Page Expired" on login
- Ensure your `APP_URL` in `.env` matches your browser address (e.g. `https://` if using SSL).
- Ensure `SESSION_DOMAIN=null` in `.env`.
- Clear browser cookies and test in an incognito window.

### Q4: How do I update ICTHospital in the future?
Whenever new updates are pushed to GitHub:
- **If using Hostinger Git**: Simply click **Deploy** in hPanel Git, then run `bash deploy-shared-hosting.sh` or visit `/install-helper.php`.
- **If using SSH**:
  ```bash
  git pull origin main
  bash deploy-shared-hosting.sh
  ```
