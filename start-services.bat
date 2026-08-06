@echo off
setlocal
cd /d "%~dp0"

echo Starting backend services...
if exist "services\start-backend.bat" (
  start "Backend Service" cmd /k "call ""%~dp0services\start-backend.bat"""
) else (
  echo services\start-backend.bat not found.
)

exit /b 0
