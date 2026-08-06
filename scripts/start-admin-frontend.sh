#!/usr/bin/env sh
set -eu

REPO_DIR="${REPO_DIR:-/workspace}"

cd "${REPO_DIR}"

echo "[start-admin] Building and starting admin-frontend"
docker compose --project-directory "${REPO_DIR}" up -d --build admin-frontend

echo "[start-admin] Done"
