@echo off
setlocal enabledelayedexpansion
title CHRSD CMS - Dev Runner

REM =====================================================================
REM  CHRSD Management System — start.bat
REM  Boots the Filament panel, queue worker, and Vite asset pipeline.
REM  C:\php\php.ini has gd/curl/pdo_mysql/pdo_sqlite/sqlite3 disabled.
REM  We enable them two ways:
REM   * -d extension=... on this parent process (so artisan boots cleanly)
REM   * PHP_INI_SCAN_DIR points at .claude\php-conf.d so worker php.exe
REM     children spawned by `artisan serve` also load them.
REM =====================================================================

set "PROJECT=%~dp0"
cd /d "%PROJECT%"

set "PHPFLAGS=-d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=pdo_sqlite -d extension=sqlite3 -d memory_limit=512M -d max_execution_time=300"
set "PHP_INI_SCAN_DIR=%PROJECT%.claude\php-conf.d"

echo.
echo ================================================================
echo   CHRSD CMS — Dev Environment
echo   Project: %PROJECT%
echo ================================================================
echo.

REM ---- 0. Pre-flight ---------------------------------------------------
where php >nul 2>&1 || (echo [FATAL] php not found in PATH & pause & exit /b 1)
where composer >nul 2>&1 || (echo [FATAL] composer not found in PATH & pause & exit /b 1)
where node >nul 2>&1 || (echo [WARN ] node not found — Vite/Puppeteer will not work)

if not exist ".env" (echo [FATAL] .env missing & pause & exit /b 1)
if not exist "vendor\autoload.php" (
    echo [INFO ] vendor missing — running composer install ...
    php %PHPFLAGS% C:\php\composer install --no-interaction || (echo [FATAL] composer install failed & pause & exit /b 1)
)
if not exist "node_modules" (
    echo [INFO ] node_modules missing — running npm install ...
    call npm install || (echo [WARN ] npm install failed, continuing without asset pipeline)
)

REM ---- 1. DB warm-up ---------------------------------------------------
echo.
echo [1/4] Warming up database ...
findstr /B /C:"DB_CONNECTION=sqlite" .env >nul && (
    if not exist "database\database.sqlite" (
        echo   creating empty sqlite file ...
        php -r "touch('database/database.sqlite');"
    )
)
php %PHPFLAGS% artisan migrate --graceful --force

REM Ensure the public/storage → storage/app/public symlink exists so Filament
REM can serve media (Spatie MediaLibrary drops PDFs into storage/app/public).
if not exist "public\storage" (
    echo   creating storage symlink ...
    php %PHPFLAGS% artisan storage:link
)

REM ---- 2. Seed roles, permissions, lookups, admin user -----------------
echo.
echo [2/4] Seeding roles, lookups, and admin user (idempotent) ...
php %PHPFLAGS% artisan db:seed --force

REM ---- 3. Optimize --------------------------------------------------
echo.
echo [3/4] Clearing stale caches ...
php %PHPFLAGS% artisan optimize:clear >nul

REM ---- 4. Launch three parallel workers -------------------------------
echo.
echo [4/4] Launching workers (three windows will open) ...
echo.
echo   -- Admin panel:  http://127.0.0.1:8000/admin      (admin@chrsd.org / password)
echo   -- Employee portal: http://127.0.0.1:8000/portal (employee@chrsd.org / password)
echo   -- Public verify:   http://127.0.0.1:8000/verify/{64-hex-hash}
echo.

start "CHRSD :: HTTP" cmd /k "cd /d %PROJECT% & php %PHPFLAGS% artisan serve --host=127.0.0.1 --port=8000"
start "CHRSD :: Queue" cmd /k "cd /d %PROJECT% & php %PHPFLAGS% artisan queue:listen --tries=1 --timeout=120"
if exist "node_modules" start "CHRSD :: Vite" cmd /k "cd /d %PROJECT% & npm run dev"

timeout /t 4 >nul
start "" "http://127.0.0.1:8000/admin"

echo.
echo Dev environment launched. Close the three worker windows to stop.
echo Press any key to close this launcher window.
pause >nul
endlocal
