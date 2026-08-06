@echo off
cd /d "%~dp0"
set "PORT=8000"
set "PHP_BIN=php"

if defined PHP83_PATH (
	if exist "%PHP83_PATH%" set "PHP_BIN=%PHP83_PATH%"
)

"%PHP_BIN%" -r "exit((PHP_MAJOR_VERSION > 8 || (PHP_MAJOR_VERSION == 8 && PHP_MINOR_VERSION >= 3)) ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
	echo [ERROR] Laravel backend requires PHP 8.3+.
	echo [ERROR] Current PHP does not satisfy requirement. Set PHP83_PATH to a PHP 8.3 php.exe or update PATH.
	exit /b 1
)

echo Starting Laravel backend on http://127.0.0.1:%PORT%...
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=%PORT%
