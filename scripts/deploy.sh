#!/bin/bash
set -euo pipefail

# Deploy strategy for primary cPanel domain:
# - full Laravel app lives outside public_html
# - only public/ contents are copied into public_html
# - public_html/index.php is rewritten to bootstrap the app from APP_DIR

PHP_BIN_DEFAULT="/opt/cpanel/ea-php84/root/usr/bin/php"
REPO_DIR="$(cd "$(dirname "$0")/.." && pwd)"
APP_DIR="${CPANEL_APP_ROOT:-$HOME/lacaracola-app}"
WEB_DIR="${CPANEL_WEB_ROOT:-$HOME/public_html}"

if [[ -x "$PHP_BIN_DEFAULT" ]]; then
    PHP_BIN="$PHP_BIN_DEFAULT"
else
    PHP_BIN="$(command -v php)"
fi

if [[ -z "${PHP_BIN:-}" ]]; then
    echo "[ERROR] PHP binary not found"
    exit 1
fi

echo "=== Deploy settings ==="
echo "REPO_DIR=$REPO_DIR"
echo "APP_DIR=$APP_DIR"
echo "WEB_DIR=$WEB_DIR"
echo "PHP_BIN=$PHP_BIN"

echo "=== Step 0: Clean and update repository ==="
cd "$REPO_DIR"
rm -f error_log
git reset --hard
git clean -fd
git pull origin main || git pull

echo "=== Step 1: Prepare application directories ==="
mkdir -p \
    "$APP_DIR" \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/framework/cache" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/bootstrap/cache" \
    "$WEB_DIR"

echo "=== Step 2: Sync Laravel app outside web root ==="
find "$APP_DIR" -mindepth 1 -maxdepth 1 \
    ! -name .env \
    ! -name storage \
    -exec rm -rf {} +

find "$REPO_DIR" -mindepth 1 -maxdepth 1 \
    ! -name .git \
    ! -name .github \
    ! -name docs \
    ! -name node_modules \
    ! -name storage \
    ! -name scripts \
    -exec cp -R {} "$APP_DIR/" \;

echo "=== Step 3: Sync public assets into public_html ==="
find "$WEB_DIR" -mindepth 1 -maxdepth 1 \
    ! -name .well-known \
    ! -name cgi-bin \
    -exec rm -rf {} +

find "$APP_DIR/public" -mindepth 1 -maxdepth 1 -exec cp -R {} "$WEB_DIR/" \;

cat > "$WEB_DIR/index.php" <<EOF
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(
    \$maintenance = '${APP_DIR}/storage/framework/maintenance.php'
)) {
    require \$maintenance;
}

require '${APP_DIR}/vendor/autoload.php';

/** @var Application \$app */
\$app = require_once '${APP_DIR}/bootstrap/app.php';

\$app->handleRequest(Request::capture());
EOF

echo "=== Step 4: Laravel setup ==="
cd "$APP_DIR"
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear || true
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "=== Step 5: Storage symlink and permissions ==="
rm -rf "$WEB_DIR/storage"
ln -s "$APP_DIR/storage/app/public" "$WEB_DIR/storage"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "=== Deploy completed successfully ==="
