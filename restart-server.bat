@echo off
REM OffMetaBG CMS - Restart Development Server
REM Kills existing PHP server and starts a new one

echo ================================================
echo  OffMetaBG CMS - Restart Server
echo ================================================
echo.

echo Stopping existing PHP server...
taskkill /F /IM php.exe >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Server stopped successfully.
) else (
    echo No running server found.
)

echo.
echo Starting PHP server on http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
echo ================================================
echo.

php -S localhost:8000 -t . router.php

pause
