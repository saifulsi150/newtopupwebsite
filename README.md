# TAST Topup Monorepo

This project has 3 runnable apps:
- user frontend (Nuxt 3)
- admin frontend (Nuxt 3)
- backend API (Laravel)

Laravel is used as API/backend service. User/admin UI should run from Nuxt apps.

## Project Structure
- apps/user-frontend: User website (Nuxt)
- apps/admin-frontend: Admin panel (Nuxt)
- services: Laravel backend API
- database-backup/schema.sql: Base database schema
- Start App.bat: One-click local launcher for all 3 services

## Default Local Ports
- User frontend: http://127.0.0.1:3000
- Admin frontend: http://127.0.0.1:3001
- Laravel API: http://127.0.0.1:8000
- Laravel health check: http://127.0.0.1:8000/healthz

## Quick Start (Windows)
### Option A: One click
From repository root, run:

```bat
Start App.bat
```

This starts all 3 services in separate terminal windows.

### Option B: Run manually (3 terminals)
1) Backend API

```powershell
cd services
php artisan serve --host=127.0.0.1 --port=8000
```

2) User frontend

```powershell
cd apps/user-frontend
npm install
npm run dev -- --host 127.0.0.1 --port 3000
```

3) Admin frontend

```powershell
cd apps/admin-frontend
npm install
npm run dev -- --host 127.0.0.1 --port 3001
```

## Environment and Connections

All services need the same MySQL/Redis connection values.

### Backend env file
Use `services/.env` for Laravel DB/cache/session/queue config.

Minimum required values (example):

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

### User frontend env
Nuxt user app uses server-side runtime DB access from `apps/user-frontend/nuxt.config.ts`.

Set these environment variables before running user frontend:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`
- `REDIS_URL`

Optional public vars:
- `NUXT_PUBLIC_SITE_NAME`
- `NUXT_PUBLIC_SUPPORT_URL`

### Admin frontend env
Nuxt admin app also uses DB values from `apps/admin-frontend/nuxt.config.ts`.

Set:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`
- `ADMIN_SECRET`

Optional deploy-agent vars:
- `DEPLOY_AGENT_URL`
- `DEPLOY_WEBHOOK_TOKEN`

## Database Setup
1) Create MySQL database and user.
2) Import base schema:

```bash
mysql -u <user> -p <database> < database-backup/schema.sql
```

3) Run Laravel migrations if needed:

```powershell
cd services
php artisan migrate
```

## Troubleshooting
- If `127.0.0.1:3000` or `127.0.0.1:3001` is down, restart Nuxt app in that folder.
- If backend root `/` returns 404, that can be normal for API-only backend. Use `/healthz` to confirm backend is up.
- If topup page shows `Package not found.`, the product exists but no active package is available for that product.
- If ports are busy, close old node/php processes or re-run `Start App.bat`.

## Production (Docker)
For server deployment, use root scripts:
- `deploy.sh`
- `docker-compose.yml`

Set root `.env` values first, then run deploy script on Linux VPS.
