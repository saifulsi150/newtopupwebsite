@echo off
set "PORT=3000"
set "FRONTEND_DIR=%~dp0frontend-legacy-nuxt"
echo Starting Nuxt frontend on http://127.0.0.1:%PORT%...
if not exist "%FRONTEND_DIR%" (
  echo Frontend folder not found: %FRONTEND_DIR%
  pause
  exit /b 1
)

echo Cleaning old process on port %PORT%...
powershell -NoProfile -Command "$port = %PORT%; Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } }"

for %%I in ("%FRONTEND_DIR%") do set "FRONTEND_DIR_SHORT=%%~fI"
start "Nuxt Frontend" cmd /k "cd /d ""%FRONTEND_DIR_SHORT%"" && if not exist node_modules (echo Installing frontend dependencies... && npm install) && npm run dev -- --host 127.0.0.1 --port %PORT%"
start "" http://127.0.0.1:%PORT%
