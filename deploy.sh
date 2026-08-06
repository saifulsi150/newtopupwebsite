#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_ENV_FILE="${DEPLOY_ENV_FILE:-${ROOT_DIR}/.env}"

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$1"
}

fail() {
    printf '\n[ERROR] %s\n' "$1" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

compose() {
    docker compose --project-directory "$ROOT_DIR" "$@"
}

load_env() {
    if [ ! -f "$DEPLOY_ENV_FILE" ]; then
        cp "$ROOT_DIR/.env.example" "$DEPLOY_ENV_FILE"
        log "Created $DEPLOY_ENV_FILE from .env.example"
    fi

    set -a
    . "$DEPLOY_ENV_FILE"
    set +a

    : "${APP_NAME:=tast.topup}"
    : "${APP_ENV:=production}"
    : "${APP_DEBUG:=false}"
    : "${APP_URL:=http://127.0.0.1:8000}"
    : "${MYSQL_DATABASE:=topup_db_tast_ffuid}"
    : "${MYSQL_USER:=topup_user_1091}"
    : "${MYSQL_PASSWORD:=change_me_now}"
    : "${MYSQL_ROOT_PASSWORD:=change_root_password_now}"
    : "${MYSQL_HOST:=mysql}"
    : "${MYSQL_PORT:=3306}"
    : "${REDIS_HOST:=redis}"
    : "${REDIS_PORT:=6379}"
    : "${REDIS_URL:=redis://redis:6379}"
    : "${APP_DOMAIN:=127.0.0.1}"
    : "${ADMIN_DOMAIN:=127.0.0.1}"
    : "${NUXT_APP_BASE_URL:=/}"
    : "${NUXT_PUBLIC_SITE_NAME:=tast.topup}"
    : "${NUXT_PUBLIC_SUPPORT_URL:=https://t.me/admimapp}"
    : "${ADMIN_SECRET:=change_this_secret_123}"
    : "${DEPLOY_AGENT_URL:=http://deploy-agent:8099}"
    : "${DEPLOY_WEBHOOK_TOKEN:=change_this_deploy_webhook_token_now}"
    : "${DEPLOY_SCHEMA_FILE:=database-backup/schema.sql}"
    : "${DEPLOY_SAMPLE_SQL_FILE:=apps/user-frontend/docker/mysql/init.sql}"
}

write_backend_env() {
    cat > "$ROOT_DIR/services/.env" <<EOF
APP_NAME=${APP_NAME}
APP_ENV=${APP_ENV}
APP_KEY=
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_DATABASE=${MYSQL_DATABASE}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=${REDIS_HOST}
REDIS_PASSWORD=null
REDIS_PORT=${REDIS_PORT}

MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME="${APP_NAME}"

GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-}
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET:-}
GOOGLE_REDIRECT_URI=${GOOGLE_REDIRECT_URI:-}
UDDOKTAPAY_API_KEY=${UDDOKTAPAY_API_KEY:-}
UDDOKTAPAY_BASE_URL=${UDDOKTAPAY_BASE_URL:-}
EOF
}

wait_for_mysql() {
    log "Waiting for MySQL container"
    for _ in $(seq 1 60); do
        if compose exec -T mysql mysqladmin ping -h127.0.0.1 -uroot "-p${MYSQL_ROOT_PASSWORD}" --silent >/dev/null 2>&1; then
            return 0
        fi
        sleep 2
    done
    fail "MySQL did not become ready in time"
}

import_sql_file() {
    local file_path="$1"
    [ -f "$ROOT_DIR/$file_path" ] || fail "SQL file not found: $file_path"
    log "Importing $file_path"
    compose exec -T mysql mysql -uroot "-p${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < "$ROOT_DIR/$file_path"
}

prepare_database() {
    log "Starting MySQL and Redis"
    compose up -d mysql redis
    wait_for_mysql

    log "Creating database and database user"
    compose exec -T mysql mysql -uroot "-p${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL

    import_sql_file "$DEPLOY_SCHEMA_FILE"

    if [ -n "${DEPLOY_SAMPLE_SQL_FILE:-}" ] && [ -f "$ROOT_DIR/$DEPLOY_SAMPLE_SQL_FILE" ]; then
        import_sql_file "$DEPLOY_SAMPLE_SQL_FILE"
    fi

    local has_orders
    local has_user_type
    has_orders="$(compose exec -T mysql mysql -N -s -uroot "-p${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" -e "SHOW TABLES LIKE 'orders';")"
    has_user_type="$(compose exec -T mysql mysql -N -s -uroot "-p${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" -e "SHOW COLUMNS FROM users LIKE 'user_type';" || true)"

    if [ -z "$has_orders" ] || [ -z "$has_user_type" ]; then
        log "Warning: the imported schema looks incomplete for the full website. Use a sanitized full schema dump in DEPLOY_SCHEMA_FILE for production deployments."
    fi
}

deploy_stack() {
    log "Building and starting application containers"
    compose up -d --build deploy-agent backend user-frontend admin-frontend

    log "Running Laravel post-deploy tasks"
    compose exec -T backend php artisan key:generate --force
    compose exec -T backend php artisan migrate --force
    compose exec -T backend php artisan storage:link || true
    compose exec -T backend php artisan optimize:clear
    compose exec -T backend php artisan config:cache
    compose exec -T backend php artisan view:cache

    if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
        log "Seeding deployment admin account"
        compose exec -T backend php artisan db:seed --class=DeploymentAdminSeeder --force
    fi
}

print_summary() {
    log "Deployment completed"
    printf 'Backend:        %s\n' "http://127.0.0.1:${BACKEND_PORT:-8000}"
    printf 'User frontend:  %s\n' "http://127.0.0.1:${USER_FRONTEND_PORT:-3000}"
    printf 'Admin frontend: %s\n' "http://127.0.0.1:${ADMIN_FRONTEND_PORT:-3001}"

    if [ "$DEPLOY_SCHEMA_FILE" = "database-backup/schema.sql" ]; then
        printf '\nNotice: database-backup/schema.sql is a minimal clean schema. For a complete production install, set DEPLOY_SCHEMA_FILE to a sanitized full schema dump before rerunning deploy.sh.\n'
    fi
}

main() {
    require_cmd docker
    load_env
    write_backend_env
    prepare_database
    deploy_stack
    print_summary
}

main "$@"
