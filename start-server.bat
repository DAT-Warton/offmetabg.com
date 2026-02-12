@echo off
REM OffMetaBG CMS - Local Development Server
REM Starts PHP built-in web server on localhost:8000

echo ================================================
echo  OffMetaBG CMS - Development Server
echo ================================================
echo.
echo Starting PHP server on http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
echo ================================================
echo.

php -S localhost:8000 -t . router.php

pause
