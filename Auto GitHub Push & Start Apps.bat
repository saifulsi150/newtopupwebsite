@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

echo =========================================
echo Auto GitHub Push + Start 3 Websites
echo =========================================
echo Project: %cd%
echo.

where git >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Git is not installed or not in PATH.
  pause
  exit /b 1
)

echo [1/4] Staging changes...
git add -A

rem Keep generated Nuxt cache out of commits.
git restore --staged "apps/admin-frontend/.nuxt" >nul 2>&1

set "HAS_CHANGES="
for /f "delims=" %%i in ('git diff --cached --name-only') do (
  set "HAS_CHANGES=1"
  goto :after_stage_check
)
:after_stage_check

if defined HAS_CHANGES (
  echo [2/4] Creating commit...
  for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "TS=%%d"
  set "MSG=Auto update !TS!"
  git commit -m "!MSG!"
  if errorlevel 1 (
    echo [WARN] Commit was not created. Continuing...
  )
) else (
  echo [2/4] No new code changes to commit.
)

echo [3/4] Pushing to GitHub main...
git push origin main
if errorlevel 1 (
  echo [WARN] Push failed. Check Git login or remote access.
  echo You can re-run this file after fixing auth.
)

echo [4/4] Starting all websites...
call "Start App.bat"

echo.
echo Done.
echo - Backend: http://127.0.0.1:8000
echo - User:    http://127.0.0.1:3000
echo - Admin:   http://127.0.0.1:3001
echo.
pause
exit /b 0
