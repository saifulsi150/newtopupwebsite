#!/bin/bash

set -e

echo "=== Starting Deployment ==="

# 1. Setup Backend (Laravel)
echo "Setting up Laravel Backend..."
cd services
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "Warning: services/.env.example not found. Create services/.env manually."
    fi
fi

# install composer dependencies (assumes composer is installed globally)
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
else
    echo "composer not found in PATH. Please install composer or run this step manually."
fi

# Laravel setup
if command -v php >/dev/null 2>&1; then
    php artisan key:generate
    php artisan migrate --force
else
    echo "php not found in PATH. Please install PHP and run migrations manually."
fi
cd ..

# 2. Setup User Frontend
echo "Setting up User Frontend..."
if [ -d "apps/user-frontend" ]; then
    cd apps/user-frontend
    if command -v npm >/dev/null 2>&1; then
        npm install
        npm run build
    else
        echo "npm not found in PATH. Please install Node.js/npm or build frontends manually."
    fi
    cd ../..
else
    echo "apps/user-frontend not found, skipping."
fi

# 3. Setup Admin Frontend
echo "Setting up Admin Frontend..."
if [ -d "apps/admin-frontend" ]; then
    cd apps/admin-frontend
    if command -v npm >/dev/null 2>&1; then
        npm install
        npm run build
    else
        echo "npm not found in PATH. Please install Node.js/npm or build frontends manually."
    fi
    cd ../..
else
    echo "apps/admin-frontend not found, skipping."
fi

echo "=== Deployment Completed Successfully ==="
