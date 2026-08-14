#!/bin/bash
set -e
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="https://github.com/saifulsi150/newtopupwebsite.git"

echo "=== PULLING LATEST CODE ==="
git -C "$PROJECT_ROOT" fetch "$REPO" main
git -C "$PROJECT_ROOT" reset --hard FETCH_HEAD

echo "=== UPDATING LARAVEL ==="
cd "$PROJECT_ROOT/services"
php artisan migrate --force
php artisan optimize:clear

echo "=== BUILDING NUXT & RELOADING PM2 ==="
cd "$PROJECT_ROOT/apps/user-frontend"
npm run build

# Read Nuxt env variables
if [ -f "$PROJECT_ROOT/apps/user-frontend/.env" ]; then
  # Load vars
  export $(grep -v '^#' "$PROJECT_ROOT/apps/user-frontend/.env" | xargs)
fi

PM2_APP_NAME="${PM2_APP_NAME:-$(basename "$PROJECT_ROOT")-frontend}"

echo "=== RELOADING PM2 PROCESS: $PM2_APP_NAME ==="
sudo ADMIN_DOMAIN="$ADMIN_DOMAIN" \
     APP_DOMAIN="$APP_DOMAIN" \
     MYSQL_HOST="$MYSQL_HOST" \
     MYSQL_PORT="$MYSQL_PORT" \
     MYSQL_USER="$MYSQL_USER" \
     MYSQL_PASSWORD="$MYSQL_PASSWORD" \
     MYSQL_DATABASE="$MYSQL_DATABASE" \
     PORT="${PORT:-3000}" \
     HOST="${HOST:-127.0.0.1}" \
     pm2 --hp /root reload "$PM2_APP_NAME" --update-env || \
sudo ADMIN_DOMAIN="$ADMIN_DOMAIN" \
     APP_DOMAIN="$APP_DOMAIN" \
     MYSQL_HOST="$MYSQL_HOST" \
     MYSQL_PORT="$MYSQL_PORT" \
     MYSQL_USER="$MYSQL_USER" \
     MYSQL_PASSWORD="$MYSQL_PASSWORD" \
     MYSQL_DATABASE="$MYSQL_DATABASE" \
     PORT="${PORT:-3000}" \
     HOST="${HOST:-127.0.0.1}" \
     pm2 --hp /root start .output/server/index.mjs --name "$PM2_APP_NAME" --update-env

echo "=== UPDATE COMPLETED SUCCESSFULLY ==="
