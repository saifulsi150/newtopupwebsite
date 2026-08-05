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

### Step 2: Database Setup

1. Create a fresh MySQL database on your VPS / phpMyAdmin.
2. Import the clean SQL schema:

```bash
mysql -u <db_user> -p <db_name> < database-backup/schema.sql
```

### Step 3: Run Auto-Deployment Script

Give execution permission and run the setup script:

```bash
chmod +x deploy.sh
./deploy.sh
```

### Step 4: Environment Configuration

Update your database credentials and API URLs inside services/.env:

```bash
nano services/.env
```

Then run artisan cache clear and caches:

```bash
cd services
php artisan config:cache
php artisan route:cache
```

---

Notes:
- Ensure PHP, Composer, Node.js and NPM are installed on your VPS before running deploy.sh.
- The repository includes a clean SQL schema (database-backup/schema.sql) without demo users or products. If you need to seed an admin user, use a Laravel seeder or create the user manually after importing the schema.
