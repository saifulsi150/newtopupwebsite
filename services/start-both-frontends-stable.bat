@echo off
setlocal
cd /d "%~dp0"

echo Starting both frontends in stable auto-restart mode...
echo - User:  http://127.0.0.1:3000
echo - Admin: http://127.0.0.1:3001

set "USER_SCRIPT=%~dp0start-user-frontend-stable.bat"
set "ADMIN_SCRIPT=%~dp0start-admin-frontend-stable.bat"

powershell -NoProfile -Command "$ports = @(3000,3001); foreach ($port in $ports) { Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } } }"

start "" "%USER_SCRIPT%"
start "" "%ADMIN_SCRIPT%"

start "" http://127.0.0.1:3000
start "" http://127.0.0.1:3001

echo Both frontend windows launched.
exit /b 0
