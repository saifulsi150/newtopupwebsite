@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

echo =========================================
echo Auto GitHub Push (main)
echo =========================================
echo.

where git >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Git is not installed or not in PATH.
  pause
  exit /b 1
)

git add -A
git restore --staged "apps/admin-frontend/.nuxt" >nul 2>&1

set "HAS_CHANGES="
for /f "delims=" %%i in ('git diff --cached --name-only') do (
  set "HAS_CHANGES=1"
  goto :after_stage_check
)
:after_stage_check

if defined HAS_CHANGES (
  for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "TS=%%d"
  set "MSG=Auto update !TS!"
  git commit -m "!MSG!"
) else (
  echo No new changes. Skipping commit.
)

git push origin main
echo.
pause
exit /b 0
