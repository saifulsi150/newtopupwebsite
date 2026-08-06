@echo off
setlocal
cd /d "%~dp0"

echo Starting admin frontend...
if exist "services\start-admin-frontend-stable.bat" (
  start "Admin Frontend" cmd /k "call ""%~dp0services\start-admin-frontend-stable.bat"""
) else (
  echo services\start-admin-frontend-stable.bat not found.
)

exit /b 0
