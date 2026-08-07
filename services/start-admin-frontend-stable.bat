@echo off
setlocal
cd /d "%~dp0..\apps\admin-frontend"

echo [ADMIN] Auto-restart mode enabled on http://127.0.0.1:3001
set "NUXT_IGNORE_LOCK=1"
if not exist node_modules (
  echo [ADMIN] Installing dependencies...
  npm install
)

:admin_loop
echo.
echo [ADMIN] Starting Nuxt admin frontend...
npm run dev
echo [ADMIN] Process stopped. Restarting in 3 seconds...
timeout /t 3 >nul
goto admin_loop
