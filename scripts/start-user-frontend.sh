#!/usr/bin/env sh
set -eu

REPO_DIR="${REPO_DIR:-/workspace}"

cd "${REPO_DIR}"

echo "[start-user] Building and starting user-frontend"
docker compose --project-directory "${REPO_DIR}" up -d --build user-frontend

echo "[start-user] Done"
