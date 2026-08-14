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
pm2 reload vottopup-frontend 2>/dev/null || pm2 start .output/server/index.mjs --name "vottopup-frontend"

echo "=== UPDATE COMPLETED SUCCESSFULLY ==="
