@echo off
setlocal

set "TASK_QUEUE=TastFfuidQueueWorker"
set "TASK_SCHED=TastFfuidScheduler"

echo Removing %TASK_QUEUE% ...
schtasks /Delete /TN "%TASK_QUEUE%" /F >nul 2>&1

echo Removing %TASK_SCHED% ...
schtasks /Delete /TN "%TASK_SCHED%" /F >nul 2>&1

echo Done.
endlocal
