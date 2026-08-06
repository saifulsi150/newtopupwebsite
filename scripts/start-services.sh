#!/usr/bin/env sh
set -eu

REPO_DIR="${REPO_DIR:-/workspace}"

cd "${REPO_DIR}"

echo "[start-services] Starting backend dependencies (mysql, redis)"
docker compose --project-directory "${REPO_DIR}" up -d mysql redis

echo "[start-services] Building and starting backend"
docker compose --project-directory "${REPO_DIR}" up -d --build backend

echo "[start-services] Done"
