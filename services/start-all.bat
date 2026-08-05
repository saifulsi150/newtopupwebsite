@echo off
cd /d "%~dp0"
call "%~dp0start-backend.bat"
call "%~dp0start-frontend.bat"
echo Both services started. Backend: http://127.0.0.1:8000 | Frontend: http://127.0.0.1:3000
