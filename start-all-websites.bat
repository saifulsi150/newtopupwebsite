@echo off
setlocal
cd /d "%~dp0"

set "ROOT_DIR=%~dp0"
set "BACKEND_SCRIPT=%ROOT_DIR%services\start-backend.bat"
set "USER_SCRIPT=%ROOT_DIR%services\start-user-frontend-stable.bat"
set "ADMIN_SCRIPT=%ROOT_DIR%services\start-admin-frontend-stable.bat"

echo Starting Laravel backend plus both websites...
echo - Backend: http://127.0.0.1:8000
echo - User:    http://127.0.0.1:3000
echo - Admin:   http://127.0.0.1:3001

echo Freeing ports 8000, 3000, 3001...
powershell -NoProfile -Command "$ports = @(8000,3000,3001); foreach ($port in $ports) { Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } } }"

echo Starting Backend...
start "Laravel Backend" cmd /k "call "%BACKEND_SCRIPT%""

echo Starting User Frontend...
start "User Frontend" cmd /k "call "%USER_SCRIPT%""

echo Starting Admin Frontend...
start "Admin Frontend" cmd /k "call "%ADMIN_SCRIPT%""

echo.
echo All services launched in separate windows.
echo Browsers will open in 15 seconds (waiting for servers to start)...
timeout /t 15 >nul

start "" http://127.0.0.1:8000
start "" http://127.0.0.1:3000
start "" http://127.0.0.1:3001

echo Done!
exit /b 0
