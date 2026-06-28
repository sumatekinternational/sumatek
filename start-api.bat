@echo off
REM Starts the backend API on http://localhost:8000  (keep this window open)
cd /d "%~dp0"
echo Starting the API on http://localhost:8000  (press Ctrl+C to stop)
php artisan serve
pause
