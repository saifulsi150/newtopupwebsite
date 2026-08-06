@echo off
setlocal
cd /d "%~dp0"

echo Starting user frontend...
if exist "services\start-user-frontend-stable.bat" (
  start "User Frontend" cmd /k "call ""%~dp0services\start-user-frontend-stable.bat"""
) else (
  echo services\start-user-frontend-stable.bat not found.
)

exit /b 0
