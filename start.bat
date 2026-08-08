@echo off
setlocal enabledelayedexpansion

color 0A
title CHRSD Development Servers

echo.
echo ============================================================================
echo  CHRSD Development Server Launcher
echo ============================================================================
echo.
echo This script will start both CMS and Website servers
echo.

REM Check PHP
echo [1/5] Checking PHP installation...
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found in PATH
    echo.
    echo Please install PHP and add it to your system PATH
    echo.
    pause
    exit /b 1
)
echo [OK] PHP is installed
echo.

REM Check Composer
echo [2/5] Checking Composer installation...
where composer >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer not found in PATH
    echo.
    echo Please install Composer and add it to your system PATH
    echo.
    pause
    exit /b 1
)
echo [OK] Composer is installed
echo.

REM Setup CMS
echo [3/5] Setting up CMS...
cd /d C:\Projects\CMS
if errorlevel 1 (
    echo [ERROR] Failed to change to CMS directory
    pause
    exit /b 1
)

if not exist vendor (
    echo Installing CMS dependencies... (this may take a moment)
    php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 composer install --no-interaction --prefer-dist --no-progress --no-security-blocking
    if errorlevel 1 (
        echo [WARNING] Composer install had issues, continuing anyway...
    )
)

php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan config:clear >nul 2>&1
php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan route:clear >nul 2>&1
php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan view:clear >nul 2>&1
echo [OK] CMS ready
echo.

REM Setup Website
echo [4/5] Checking Website setup...
if exist C:\Projects\Website\composer.json (
    echo Setting up Website...
    cd /d C:\Projects\Website

    if not exist vendor (
        echo Installing Website dependencies... (this may take a moment)
        php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 composer install --no-interaction --prefer-dist --no-progress --no-security-blocking
        if errorlevel 1 (
            echo [WARNING] Website Composer install had issues, continuing anyway...
        )
    )

    php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan config:clear >nul 2>&1
    php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan route:clear >nul 2>&1
    php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan view:clear >nul 2>&1
    echo [OK] Website ready
) else (
    echo [INFO] Website not found at C:\Projects\Website (skipping)
)
echo.

REM Start servers
echo [5/5] Starting servers...
echo.
echo ============================================================================
echo  LAUNCHING SERVERS
echo ============================================================================
echo.

REM Start CMS
echo Starting CMS on port 8000...
start "CMS-8000" cmd /k "title CMS Server (Port 8000) && cd /d C:\Projects\CMS && php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan serve --host=127.0.0.1 --port=8000"

REM Wait for CMS to start
timeout /t 3 /nobreak

REM Start Website if it exists
if exist C:\Projects\Website\composer.json (
    echo Starting Website on port 8001...
    start "Website-8001" cmd /k "title Website Server (Port 8001) && cd /d C:\Projects\Website && php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan serve --host=127.0.0.1 --port=8001"
)

echo.
echo ============================================================================
echo  SUCCESS - SERVERS ARE STARTING
echo ============================================================================
echo.
echo URLs:
echo   CMS:     http://127.0.0.1:8000/admin
echo   Website: http://127.0.0.1:8001/
echo.
echo Two new command windows should open for the servers.
echo The servers will keep running in those windows.
echo.
echo To stop the servers: Close each server window or press Ctrl+C
echo.
echo You can close this launcher window now.
echo.

pause
