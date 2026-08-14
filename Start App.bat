@echo off
setlocal
cd /d "%~dp0"

set "ROOT_DIR=%~dp0"
set "BACKEND_SCRIPT=%ROOT_DIR%services\start-backend.bat"
set "USER_SCRIPT=%ROOT_DIR%services\start-user-frontend-stable.bat"

if not exist "%BACKEND_SCRIPT%" (
	echo [ERROR] Missing file: %BACKEND_SCRIPT%
	exit /b 1
)

if not exist "%USER_SCRIPT%" (
	echo [ERROR] Missing file: %USER_SCRIPT%
	exit /b 1
)

echo =========================================
echo Starting TAST Topup Application Stack...
echo =========================================
echo Services: Laravel Backend (API + Filament Admin) + Nuxt User Frontend
echo.

if not defined FORCE_PORT_CLEANUP set "FORCE_PORT_CLEANUP=0"

set "BACKEND_CAN_START=1"
powershell -NoProfile -Command "$v = [version]((php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>$null)); if ($null -eq $v -or $v -lt [version]'8.3') { exit 1 }" >nul 2>&1
if errorlevel 1 (
	set "BACKEND_CAN_START=0"
	echo [WARN] Laravel backend requires PHP 8.3+ but current CLI PHP is lower or unavailable.
	echo [WARN] User frontend will still start. Install/select PHP 8.3 to run backend on port 8000.
	echo.
)

if "%FORCE_PORT_CLEANUP%"=="1" (
	echo Force cleanup enabled. Freeing ports 8000, 3000...
	powershell -NoProfile -Command "$ports = @(8000,3000); foreach ($port in $ports) { Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } } }"
) else (
	echo Keeping existing running services (set FORCE_PORT_CLEANUP=1 for hard restart^).
)

if "%BACKEND_CAN_START%"=="1" (
	echo Starting Laravel Backend + Filament Admin...
	start "Laravel Backend & Filament Admin" cmd /k "call ""%BACKEND_SCRIPT%"""
) else (
	echo Skipping Backend start due to PHP version check.
)

echo Starting Nuxt User Frontend...
start "Nuxt User Frontend" cmd /k "call ""%USER_SCRIPT%"""

echo.
echo Waiting 6 seconds for services to boot...
timeout /t 6 /nobreak >nul

echo.
echo Opening websites in browser...
start "" http://127.0.0.1:3000
if "%BACKEND_CAN_START%"=="1" (
	start "" http://127.0.0.1:8000/admin
)

echo.
echo =========================================
echo Stack is LIVE:
echo - User Frontend:  http://127.0.0.1:3000
echo - Filament Admin: http://127.0.0.1:8000/admin
echo =========================================
exit /b 0
