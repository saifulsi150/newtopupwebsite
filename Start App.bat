@echo off
setlocal
cd /d "%~dp0"

set "ROOT_DIR=%~dp0"
set "BACKEND_SCRIPT=%ROOT_DIR%services\start-backend.bat"
set "USER_SCRIPT=%ROOT_DIR%services\start-user-frontend-stable.bat"
set "ADMIN_SCRIPT=%ROOT_DIR%services\start-admin-frontend-stable.bat"

if not exist "%BACKEND_SCRIPT%" (
	echo [ERROR] Missing file: %BACKEND_SCRIPT%
	exit /b 1
)

if not exist "%USER_SCRIPT%" (
	echo [ERROR] Missing file: %USER_SCRIPT%
	exit /b 1
)

if not exist "%ADMIN_SCRIPT%" (
	echo [ERROR] Missing file: %ADMIN_SCRIPT%
	exit /b 1
)

echo =========================================
echo Starting Full Application Stack...
echo =========================================
echo Services: Backend + User Frontend + Admin Frontend
echo.

set "BACKEND_CAN_START=1"
powershell -NoProfile -Command "$v = [version]((php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>$null)); if ($null -eq $v -or $v -lt [version]'8.3') { exit 1 }" >nul 2>&1
if errorlevel 1 (
	set "BACKEND_CAN_START=0"
	echo [WARN] Laravel backend requires PHP 8.3+ but current CLI PHP is lower or unavailable.
	echo [WARN] Frontends will still start. Install/select PHP 8.3 to run backend on port 8000.
	echo.
)

echo Freeing ports 8000, 3000, 3001...
powershell -NoProfile -Command "$ports = @(8000,3000,3001); foreach ($port in $ports) { Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object { if ($_){ Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue } } }"

if "%BACKEND_CAN_START%"=="1" (
	echo Starting Backend...
	start "Laravel Backend" cmd /k "call ""%BACKEND_SCRIPT%"""
) else (
	echo Skipping Backend start due to PHP version check.
)

echo Starting User Frontend...
start "User Frontend" cmd /k "call ""%USER_SCRIPT%"""

echo Starting Admin Frontend...
start "Admin Frontend" cmd /k "call ""%ADMIN_SCRIPT%"""

echo.
echo Waiting 8 seconds for initial boot...
timeout /t 8 /nobreak >nul

echo.
echo Opening websites in browser...
start "" http://127.0.0.1:3000
start "" http://127.0.0.1:3001
start "" http://127.0.0.1:8000

echo.
echo Done. You can now use the websites.
exit /b 0
