#!/bin/bash
set -e

# Export standard system paths so npm, node, php, pm2, git are always in PATH
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"
export PM2_HOME="/root/.pm2"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="https://github.com/saifulsi150/newtopupwebsite.git"

echo "=== CONFIGURE GIT SAFE DIRECTORY ==="
git config --global --add safe.directory "*" 2>/dev/null || true
git config --global --add safe.directory "$PROJECT_ROOT" 2>/dev/null || true

echo "=== PULLING LATEST CODE FROM $REPO ==="
git -C "$PROJECT_ROOT" fetch "$REPO" main
git -C "$PROJECT_ROOT" reset --hard FETCH_HEAD

echo "=== UPDATING LARAVEL ==="
cd "$PROJECT_ROOT/services"
php artisan migrate --force || true
php artisan optimize:clear || true

echo "=== BUILDING NUXT FRONTEND ==="
cd "$PROJECT_ROOT/apps/user-frontend"
npm run build

# Read Nuxt env variables
if [ -f "$PROJECT_ROOT/apps/user-frontend/.env" ]; then
  export $(grep -v '^#' "$PROJECT_ROOT/apps/user-frontend/.env" | xargs) 2>/dev/null || true
fi

PM2_APP_NAME="${PM2_APP_NAME:-$(basename "$PROJECT_ROOT")-frontend}"

echo "=== RELOADING PM2 PROCESS ($PM2_APP_NAME) ==="
# Multi-fallback reload and restart to guarantee new build is served
sudo -n env PM2_HOME=/root/.pm2 pm2 reload "$PM2_APP_NAME" --update-env 2>/dev/null || \
sudo -n env PM2_HOME=/root/.pm2 pm2 restart "$PM2_APP_NAME" --update-env 2>/dev/null || \
env PM2_HOME=/root/.pm2 pm2 reload all --update-env 2>/dev/null || \
sudo env PM2_HOME=/root/.pm2 pm2 restart all 2>/dev/null || true

echo "=== UPDATE COMPLETED SUCCESSFULLY ==="
