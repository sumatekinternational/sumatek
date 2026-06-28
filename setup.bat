@echo off
REM ============================================================
REM  One-time setup for local testing (Windows).
REM  Double-click this file, or run it from a terminal.
REM ============================================================
cd /d "%~dp0"

echo.
echo [1/5] Installing PHP dependencies (composer)...
call composer install || goto :error

echo.
echo [2/5] Creating local configuration (.env)...
copy /Y .env.sqlite .env >nul

echo.
echo [3/5] Creating the database file...
if not exist database mkdir database
if not exist database\database.sqlite type nul > database\database.sqlite

echo.
echo [4/5] Generating the application key...
call php artisan key:generate || goto :error

echo.
echo [5/5] Creating tables and demo data...
call php artisan migrate --seed --force || goto :error

echo.
echo ============================================================
echo  Setup complete!
echo  Next: double-click  start-api.bat   (keep it open)
echo        then double-click  start-web.bat
echo  Then open  http://localhost:3000  in your browser.
echo  Login:  a.admin@agency.test  /  password123
echo ============================================================
echo.
pause
exit /b 0

:error
echo.
echo *** Something went wrong. Copy the message above and send it back. ***
pause
exit /b 1
