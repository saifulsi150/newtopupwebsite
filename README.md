# tast.topup

This repository contains the backend service and two frontend applications (User & Admin).

## Repository Structure
- services/: Laravel Backend API
- apps/user-frontend/: Frontend application for general users
- apps/admin-frontend/: Admin dashboard panel
- database-backup/: Clean MySQL Database schema

---

## VPS Quick Deployment Guide

### Step 1: Clone the Repository

```bash
git clone https://github.com/saifulsi150/tast.topup.git project
cd project
```

### Step 2: Create Deployment Config

```bash
cp .env.example .env
```

Update `.env` with your database passwords, admin login, domains, and `DEPLOY_SCHEMA_FILE`.

For Docker-safe admin-triggered updates, also set:
- `DEPLOY_WEBHOOK_TOKEN` (strong random string)
- `DEPLOY_AGENT_URL` (default `http://deploy-agent:8099`)

Important:
- `database-backup/schema.sql` is only a minimal clean schema.
- For a full production install, point `DEPLOY_SCHEMA_FILE` at a sanitized full schema dump for this website.

### Step 3: Run One-Script Deployment

```bash
chmod +x deploy.sh
./deploy.sh
```

This script will:
- start MySQL and Redis with Docker Compose
- create the database and database user
- import the configured SQL schema files
- generate `services/.env`
- build and start Laravel, user frontend, and admin frontend containers
- run Laravel key generation, migrations, cache steps, and admin bootstrap

### Step 4: Access the Services

- User frontend: `http://your-server-ip:3000`
- Admin frontend: `http://your-server-ip:3001`
- Backend API: `http://your-server-ip:8000`

### Step 5: Optional Reverse Proxy

If you want port 80/443 domains, point Nginx or Cloudflare Tunnel to:
- user site -> `127.0.0.1:3000`
- admin site -> `127.0.0.1:3001`
- backend/API -> `127.0.0.1:8000`

---

Notes:
- Ensure Docker Engine with the Compose plugin is installed on your VPS before running `deploy.sh`.
- The repository includes only a minimal clean schema by default. Full website installation needs a sanitized complete schema dump.
- Default ports are 3000 for the user frontend, 3001 for the admin frontend, 8000 for the backend API, 3306 for MySQL, and 6379 for Redis.
