@echo off
setlocal
cd /d "%~dp0"

echo =========================================
echo Starting Full Application Stack...
echo =========================================
echo Services: Backend + User Frontend + Admin Frontend
echo.

call "start-services.bat"
timeout /t 2 >nul
call "start-user-frontend.bat"
timeout /t 2 >nul
call "start-admin-frontend.bat"

echo.
echo Opening websites in browser...
start "" http://127.0.0.1:8000
start "" http://127.0.0.1:3000
start "" http://127.0.0.1:3001

echo.
echo Done. You can now use the websites.
exit /b 0
