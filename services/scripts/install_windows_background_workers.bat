@echo off
setlocal

set "PROJECT_ROOT=%~dp0.."
set "TASK_QUEUE=TastFfuidQueueWorker"
set "TASK_SCHED=TastFfuidScheduler"
set "QUEUE_DRIVER=database"

for /f "tokens=1,2 delims==" %%A in ('type "%PROJECT_ROOT%\.env" ^| findstr /B /I "QUEUE_CONNECTION="') do (
	set "QUEUE_DRIVER=%%B"
)

if "%QUEUE_DRIVER%"=="" set "QUEUE_DRIVER=database"

echo Creating startup task for Queue Worker...
schtasks /Delete /TN "%TASK_QUEUE%" /F >nul 2>&1
schtasks /Create /TN "%TASK_QUEUE%" /SC ONSTART /RU SYSTEM /RL HIGHEST /TR "cmd /c \"%PROJECT_ROOT%\scripts\run_queue_worker.bat\"" /F
if errorlevel 1 goto fail

echo Creating startup task for Scheduler...
schtasks /Delete /TN "%TASK_SCHED%" /F >nul 2>&1
schtasks /Create /TN "%TASK_SCHED%" /SC ONSTART /RU SYSTEM /RL HIGHEST /TR "cmd /c \"%PROJECT_ROOT%\scripts\run_scheduler_worker.bat\"" /F
if errorlevel 1 goto fail

echo Running tasks now...
schtasks /Run /TN "%TASK_QUEUE%" >nul 2>&1
schtasks /Run /TN "%TASK_SCHED%" >nul 2>&1

echo Done. Queue worker (%QUEUE_DRIVER%) and scheduler are configured for auto-start on boot.
goto end

:fail
echo Failed to create one or more tasks.
exit /b 1

:end
endlocal
