@echo off
setlocal
cd /d "%~dp0.."
set "QUEUE_DRIVER=database"
for /f "tokens=1,2 delims==" %%A in ('type ".env" ^| findstr /B /I "QUEUE_CONNECTION="') do set "QUEUE_DRIVER=%%B"
if "%QUEUE_DRIVER%"=="" set "QUEUE_DRIVER=database"
php artisan queue:work %QUEUE_DRIVER% --queue=default --sleep=1 --tries=3 --timeout=120
endlocal
