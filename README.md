# TAST Topup Monorepo

This repository contains 3 apps:
- User Frontend (Nuxt 3)
- Admin Frontend (Nuxt 3)
- Backend API (Laravel)

Use this document as a full setup/install guide for local development, VPS deployment, and private repository usage.

## 1) Project Layout
- `apps/user-frontend` - User website
- `apps/admin-frontend` - Admin panel
- `services` - Laravel backend API
- `database-backup/schema.sql` - Base schema
- `Start App.bat` - One-click root launcher (Windows)

## 2) Default Ports
- User frontend: http://127.0.0.1:3000
- Admin frontend: http://127.0.0.1:3001
- Backend API: http://127.0.0.1:8000
- Backend health check: http://127.0.0.1:8000/healthz

## 3) Prerequisites

### Windows Local
- Git
- Node.js 20+
- npm 10+
- PHP 8.2+
- MySQL 8+
- Redis (recommended)

### Linux VPS
- Git
- Node.js 20+
- npm 10+
- PHP 8.2+
- Composer
- MySQL 8+
- Redis (recommended)
- Nginx/Apache (optional, if serving through reverse proxy)

## 4) Clone Repository

### If repository is public
```bash
git clone https://github.com/saifulsi150/tast.topup.git
cd tast.topup
```

### If repository is private (recommended setup)
Use SSH deploy key on server/machine, then clone via SSH:

```bash
git clone git@github.com:saifulsi150/tast.topup.git
cd tast.topup
```

If using HTTPS for private repository, configure Git credentials (PAT) on that machine first.

## 5) Environment Setup

All services must use consistent DB/Redis connection values.

### Backend (`services/.env`)
Minimum required example:

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=topup_db_tast_ffuid
DB_USERNAME=topup_user_1091
DB_PASSWORD=your_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### User frontend runtime vars
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`
- `REDIS_URL`

Optional:
- `NUXT_PUBLIC_SITE_NAME`
- `NUXT_PUBLIC_SUPPORT_URL`

### Admin frontend runtime vars
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`
- `ADMIN_SECRET`

Optional (deploy agent mode):
- `DEPLOY_AGENT_URL`
- `DEPLOY_WEBHOOK_TOKEN`

## 6) Database Setup

1. Create MySQL database and user.
2. Import base schema:

```bash
mysql -u <user> -p <database> < database-backup/schema.sql
```

3. Run migrations:

```bash
cd services
php artisan migrate --force
```

## 7) Install Dependencies

```bash
cd apps/user-frontend && npm install
cd ../admin-frontend && npm install
cd ../../services && composer install
```

## 8) Run Locally

### Option A: One-click from repo root (Windows)
```bat
Start App.bat
```

### Option B: Manual (3 terminals)

Backend:
```bash
cd services
php artisan serve --host=127.0.0.1 --port=8000
```

User frontend:
```bash
cd apps/user-frontend
npm run dev -- --host 127.0.0.1 --port 3000
```

Admin frontend:
```bash
cd apps/admin-frontend
npm run dev -- --host 127.0.0.1 --port 3001
```

## 9) External One-Click Helpers (Windows)

Optional helper scripts can be kept outside the repository folder.

Recommended location:
- `C:\Users\HP\OneDrive\Desktop\topup\AUTO_PUSH_NOW.bat`
- `C:\Users\HP\OneDrive\Desktop\topup\RUN_STACK_NO_BROWSER.bat`
- `C:\Users\HP\OneDrive\Desktop\topup\run-stack-no-browser.ps1`

Behavior:
- `AUTO_PUSH_NOW.bat`: fetch -> stage -> commit (if changed) -> push.
- `RUN_STACK_NO_BROWSER.bat`: starts MySQL/backend/user/admin services without auto-opening browser, then prints links.

## 10) Update Workflow

### Local update
```bash
git pull --ff-only origin main
cd services
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Admin System Update button behavior
- If deploy-agent is configured (`DEPLOY_AGENT_URL` + `DEPLOY_WEBHOOK_TOKEN`): uses deploy-agent job flow.
- If deploy-agent is not configured: uses local fallback update job and still returns job logs/status.

## 11) VPS Install / Deploy (Standard Flow)

1. Clone repository on VPS.
2. Configure backend env (`services/.env`).
3. Install dependencies.
4. Setup database and run migrations.
5. Run/build services according to your process manager (systemd/pm2/supervisor/docker).
6. Validate with:
	- user: `http://<host>:3000`
	- admin: `http://<host>:3001`
	- backend: `http://<host>:8000/healthz`

## 12) Private Repository Notes

For any new machine (developer PC or VPS):
- Configure Git authentication first (SSH key or HTTPS+PAT).
- Verify with:

```bash
git fetch origin main
git pull --ff-only origin main
```

If these work without repeated auth prompts, auto update/push workflows will work normally.

## 13) Troubleshooting

- If `3000` or `3001` is down: restart the corresponding Nuxt app.
- If backend `/` gives 404: this can be normal for API-first backend. Use `/healthz`.
- If topup page says package not found: verify product slug/id mapping and active package availability.
- If update button fails: check update logs in admin settings page.
- If port is busy: stop old node/php process and restart.
