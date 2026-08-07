#!/usr/bin/env sh
set -eu

REPO_DIR="${REPO_DIR:-/workspace}"
LOCK_DIR="${REPO_DIR}/.deploy-lock"

log() {
  printf '[deploy] %s\n' "$1"
}

fail() {
  printf '[deploy][ERROR] %s\n' "$1" >&2
  exit 1
}

require_clean_git_state() {
  UNMERGED="$(git diff --name-only --diff-filter=U)"
  if [ -n "${UNMERGED}" ]; then
    printf '[deploy][ERROR] Unmerged files:\n%s\n' "${UNMERGED}" >&2
    fail "Unmerged files detected. Resolve conflicts before running System Update."
  fi

  DIRTY="$(git status --porcelain)"
  if [ -n "${DIRTY}" ]; then
    printf '[deploy][ERROR] Dirty working tree entries:\n%s\n' "${DIRTY}" >&2
    fail "Working tree is not clean. Commit or stash local changes before update."
  fi
}

if ! mkdir "${LOCK_DIR}" 2>/dev/null; then
  echo "Another deployment is already running."
  exit 1
fi

cleanup() {
  rmdir "${LOCK_DIR}" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

cd "${REPO_DIR}"

log "Running git pre-checks"
require_clean_git_state

log "Fetching latest main branch metadata"
git fetch origin main

BEHIND_COUNT="$(git rev-list --count HEAD..origin/main 2>/dev/null || echo "0")"
if [ "${BEHIND_COUNT}" = "0" ]; then
  log "Repository already up to date."
else
  log "Pulling ${BEHIND_COUNT} new commit(s) from main"
  git pull --ff-only origin main || fail "Fast-forward pull failed. Check branch state and try again."
fi

log "Rebuilding and restarting backend and frontends"
docker compose --project-directory "${REPO_DIR}" up -d --build backend user-frontend admin-frontend

log "Running Laravel migrations"
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan migrate --force

log "Refreshing Laravel caches"
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan optimize:clear
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan config:cache
docker compose --project-directory "${REPO_DIR}" exec -T backend php artisan route:cache

log "Done"
