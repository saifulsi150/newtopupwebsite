@echo off
cd /d "%~dp0"
set "PORT=8000"
echo Starting Laravel backend on http://127.0.0.1:%PORT%...
php artisan serve --host=127.0.0.1 --port=%PORT%
