#!/usr/bin/env sh
set -eu

REPO_DIR="${REPO_DIR:-/workspace}"
LOCK_DIR="${REPO_DIR}/.deploy-lock"

if ! mkdir "${LOCK_DIR}" 2>/dev/null; then
  echo "Another deployment is already running."
  exit 1
fi

cleanup() {
  rmdir "${LOCK_DIR}" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

cd "${REPO_DIR}"

echo "[deploy] Pulling latest code from main"
git pull --ff-only origin main

echo "[deploy] Rebuilding and restarting backend and frontends"
docker compose --project-directory "${REPO_DIR}" up -d --build backend user-frontend admin-frontend

echo "[deploy] Running Laravel migrations"
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan migrate --force

echo "[deploy] Refreshing Laravel caches"
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan optimize:clear
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan config:cache
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan route:cache

echo "[deploy] Done"
