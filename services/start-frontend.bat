@echo off
set "USER_PORT=3000"
set "ADMIN_PORT=3001"
set "USER_FRONTEND_DIR=%~dp0..\apps\user-frontend"
set "ADMIN_FRONTEND_DIR=%~dp0..\apps\admin-frontend"

echo Starting user frontend on http://127.0.0.1:%USER_PORT%...
if not exist "%USER_FRONTEND_DIR%" (
  echo User frontend folder not found: %USER_FRONTEND_DIR%
  pause
  exit /b 1
)

echo Starting admin frontend on http://127.0.0.1:%ADMIN_PORT%...
if not exist "%ADMIN_FRONTEND_DIR%" (
  echo Admin frontend folder not found: %ADMIN_FRONTEND_DIR%
  pause
  exit /b 1
)

echo Cleaning old processes on ports %USER_PORT% and %ADMIN_PORT%...
powershell -NoProfile -Command "$ports = @(%USER_PORT%, %ADMIN_PORT%); foreach ($port in $ports) { Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } } }"

for %%I in ("%USER_FRONTEND_DIR%") do set "USER_FRONTEND_DIR_SHORT=%%~fI"
for %%I in ("%ADMIN_FRONTEND_DIR%") do set "ADMIN_FRONTEND_DIR_SHORT=%%~fI"

start "Nuxt User Frontend" cmd /k "cd /d ""%USER_FRONTEND_DIR_SHORT%"" && if not exist node_modules (echo Installing user frontend dependencies... && npm install) && npm run dev"
start "Nuxt Admin Frontend" cmd /k "cd /d ""%ADMIN_FRONTEND_DIR_SHORT%"" && if not exist node_modules (echo Installing admin frontend dependencies... && npm install) && npm run dev -- --host 127.0.0.1 --port %ADMIN_PORT%"

start "" http://127.0.0.1:%USER_PORT%
start "" http://127.0.0.1:%ADMIN_PORT%
