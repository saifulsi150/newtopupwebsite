@echo off
cd /d "%~dp0"
call "%~dp0start-backend.bat"
call "%~dp0start-frontend.bat"
echo Local services started.
echo Backend: http://127.0.0.1:8000
echo User frontend: http://127.0.0.1:3000
echo Admin frontend: http://127.0.0.1:3001
