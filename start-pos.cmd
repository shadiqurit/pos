@echo off
setlocal

set "PROJECT_DIR=%~dp0"
set "PHP_EXE=php"
set "PHP_OPTIONS="

where php >nul 2>nul
if errorlevel 1 (
    set "PHP_EXE=C:\Program Files\PHP\current\php.exe"
)

if not exist "%PROJECT_DIR%vendor\autoload.php" (
    echo Composer dependencies are missing. Run: composer install
    exit /b 1
)

if exist "%PROJECT_DIR%php-ci4.ini" (
    set "PHP_OPTIONS=-c %PROJECT_DIR%php-ci4.ini"
)

cd /d "%PROJECT_DIR%"
echo Starting POS at http://127.0.0.1:8081
"%PHP_EXE%" %PHP_OPTIONS% -S 127.0.0.1:8081 -t public vendor\codeigniter4\framework\system\rewrite.php

