@echo off
set "PHP_INI_SCAN_DIR=C:\Projects\CMS\.claude\php-conf.d"
cd /d "C:\Projects\CMS"
php artisan serve --host=127.0.0.1 --port=8000
