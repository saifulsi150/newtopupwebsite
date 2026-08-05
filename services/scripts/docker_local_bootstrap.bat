@echo off
setlocal

set "BASE=%~dp0.."
pushd "%BASE%"

echo [1/5] Prepare Docker env from current .env
powershell -ExecutionPolicy Bypass -File "scripts\prepare_docker_env.ps1" -SourceEnv ".env" -TargetEnv ".env.docker.local"
if errorlevel 1 goto fail

echo [2/5] Ensure APP_KEY exists in .env.docker.local
findstr /B /C:"APP_KEY=" ".env.docker.local" >nul
if errorlevel 1 (
  echo APP_KEY=>>".env.docker.local"
)

echo [3/5] Build containers
copy /Y ".env.docker.local" ".env.docker" >nul
docker compose build
if errorlevel 1 goto fail

echo [4/5] Start containers
docker compose up -d
if errorlevel 1 goto fail

echo [5/5] Laravel warm-up in container
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear

echo.
echo Bootstrap complete.
echo Laravel: http://localhost:8080
echo Nuxt   : http://localhost:3000 and http://localhost:8080/nuxt/

goto end

:fail
echo.
echo Bootstrap failed. Check output above.
exit /b 1

:end
popd
endlocal
