@echo off
REM Starts the web app on http://localhost:3000  (keep this window open)
cd /d "%~dp0web"
echo Installing web dependencies (first run only)...
call npm install
echo.
echo Starting the web app on http://localhost:3000  (press Ctrl+C to stop)
call npm run dev
pause
