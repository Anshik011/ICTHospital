#!/usr/bin/env bash
# ==============================================================================
# ICTHospital - Turnkey Shared Hosting Deployment & Update Script
# ==============================================================================
# Suitable for Hostinger SSH Terminal, cPanel Terminal, or Git Post-Receive Hooks.
#
# Usage:
#   bash deploy-shared-hosting.sh
# ==============================================================================

set -e

echo "========================================================="
echo " ICTHospital - Shared Hosting Deployment & Optimization "
echo "========================================================="

# ------------------------------------------------------------------------------
# 1. Locate PHP >= 8.2 Binary
# ------------------------------------------------------------------------------
PHP_BIN=""
CANDIDATE_PHPS=(
    "php8.3"
    "php8.2"
    "/usr/bin/php8.3"
    "/usr/bin/php8.2"
    "/usr/local/bin/php8.3"
    "/usr/local/bin/php8.2"
    "/opt/cpanel/ea-php83/root/usr/bin/php"
    "/opt/cpanel/ea-php82/root/usr/bin/php"
    "php"
)

for p in "${CANDIDATE_PHPS[@]}"; do
    if command -v "$p" >/dev/null 2>&1; then
        VERSION=$("$p" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)
        if [ "$VERSION" -ge 80200 ]; then
            PHP_BIN="$p"
            break
        fi
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "[ERROR] Could not find PHP 8.2 or higher!"
    echo "Please configure PHP 8.2 or 8.3 in your hosting panel (hPanel / cPanel)."
    exit 1
fi

echo "[INFO] Using PHP: $PHP_BIN ($("$PHP_BIN" -v | head -n 1))"

# ------------------------------------------------------------------------------
# 2. Check Composer
# ------------------------------------------------------------------------------
COMPOSER_BIN=""
if command -v composer >/dev/null 2>&1; then
    COMPOSER_BIN="composer"
elif [ -f "./composer.phar" ]; then
    COMPOSER_BIN="$PHP_BIN ./composer.phar"
else
    echo "[INFO] Downloading composer.phar..."
    "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "$PHP_BIN" composer-setup.php --quiet
    "$PHP_BIN" -r "unlink('composer-setup.php');"
    COMPOSER_BIN="$PHP_BIN ./composer.phar"
fi

echo "[INFO] Using Composer: $COMPOSER_BIN"

# ------------------------------------------------------------------------------
# 3. Verify Environment File (.env)
# ------------------------------------------------------------------------------
if [ ! -f ".env" ]; then
    if [ -f ".env.shared-hosting.example" ]; then
        echo "[WARNING] No .env found. Copying .env.shared-hosting.example to .env..."
        cp .env.shared-hosting.example .env
    elif [ -f ".env.example" ]; then
        echo "[WARNING] No .env found. Copying .env.example to .env..."
        cp .env.example .env
    fi
    echo "[ACTION REQUIRED] Please configure your database credentials in .env before continuing!"
fi

# Ensure APP_KEY exists
KEY_CHECK=$("$PHP_BIN" -r "echo file_exists('.env') && preg_match('/^APP_KEY=.+/m', file_get_contents('.env')) ? 'yes' : 'no';" 2>/dev/null || echo "no")
if [ "$KEY_CHECK" != "yes" ]; then
    echo "[INFO] Generating application encryption key (APP_KEY)..."
    "$PHP_BIN" artisan key:generate --force
fi

# ------------------------------------------------------------------------------
# 4. Storage & Cache Permissions
# ------------------------------------------------------------------------------
echo "[INFO] Ensuring storage & cache write permissions..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ------------------------------------------------------------------------------
# 5. Install / Update PHP Dependencies
# ------------------------------------------------------------------------------
if [ -f "composer.json" ]; then
    echo "[INFO] Installing production dependencies with Composer..."
    $COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

# ------------------------------------------------------------------------------
# 6. Link Public Storage Directory
# ------------------------------------------------------------------------------
if [ ! -e "public/storage" ]; then
    echo "[INFO] Creating storage symlink..."
    "$PHP_BIN" artisan storage:link || true
else
    echo "[INFO] public/storage link already exists."
fi

# ------------------------------------------------------------------------------
# 7. Generate Passport Encryption Keys (if missing)
# ------------------------------------------------------------------------------
if [ ! -f "storage/oauth-private.key" ]; then
    echo "[INFO] Generating OAuth passport keys..."
    "$PHP_BIN" artisan passport:keys --force || true
fi

# ------------------------------------------------------------------------------
# 8. Run Database Migrations
# ------------------------------------------------------------------------------
echo "[INFO] Running database migrations..."
"$PHP_BIN" artisan migrate --force

# ------------------------------------------------------------------------------
# 8. Clear & Rebuild Production Caches
# ------------------------------------------------------------------------------
echo "[INFO] Optimizing application for production..."
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

echo ""
echo "========================================================="
echo " ICTHospital Deployment Completed Successfully!         "
echo "========================================================="
echo ""
echo "Next steps for Shared Hosting (Hostinger / cPanel):"
echo "1. Set up your 1-minute Cron Job in your hosting panel:"
echo "   * * * * * cd $(pwd) && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "2. Ensure SSL (HTTPS) is activated for your domain."
echo "3. Visit your hospital URL and log in to the admin panel."
echo "========================================================="
