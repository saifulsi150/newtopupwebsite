@echo off
setlocal
cd /d "%~dp0"
start "AI-TOPUP-TAST-MS2BD" /min cmd /c "node server.js"
echo AI Topup empty clone started in background on port 3620.
endlocal
