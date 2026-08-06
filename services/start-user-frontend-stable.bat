@echo off
setlocal
cd /d "%~dp0..\apps\user-frontend"


echo [USER] Auto-restart mode enabled on http://127.0.0.1:3000
if not exist node_modules (
  echo [USER] Installing dependencies...
  npm install
)

:user_loop
echo.
echo [USER] Starting Nuxt user frontend...
npm run dev
echo [USER] Process stopped. Restarting in 3 seconds...
timeout /t 3 >nul
goto user_loop
