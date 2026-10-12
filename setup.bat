@echo off
rem Windows local dev setup. ASCII only: cmd misparses UTF-8 batch files.
setlocal
cd /d "%~dp0"

echo [1/4] Checking Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo Docker Desktop is not installed: https://www.docker.com/products/docker-desktop/
    goto :fail
)
docker info >nul 2>&1
if errorlevel 1 (
    echo Docker Desktop is not running. Start it and run setup.bat again.
    goto :fail
)

echo [2/4] Preparing .env
if exist .env goto :env_exists
copy .env.example .env >nul
echo Created .env from .env.example
goto :env_ready
:env_exists
echo Using existing .env
:env_ready
set WEB_PORT=8088
set PMA_PORT=8089
set DB_PORT_HOST=3310
for /f "usebackq eol=# tokens=1,* delims==" %%a in (".env") do set "%%a=%%b"

echo [3/4] Building and starting containers
docker compose up -d --build
if errorlevel 1 (
    echo Failed to start. If a port is in use, change it in .env and run again.
    goto :fail
)

echo [4/4] Waiting for database
set /a tries=0
:wait_db
docker compose exec -T web php -r "require 'app/bootstrap.php'; db();" >nul 2>&1
if not errorlevel 1 goto :ok
set /a tries+=1
if %tries% geq 30 (
    echo Database connection failed. Check: docker compose logs db
    goto :fail
)
ping -n 3 127.0.0.1 >nul
goto :wait_db

:ok
echo.
echo Done
echo   Site        http://localhost:%WEB_PORT%
echo   phpMyAdmin  http://localhost:%PMA_PORT%
echo   MariaDB     localhost:%DB_PORT_HOST%  (fuselab / fuselab)
echo   Stop:       docker compose down
pause
exit /b 0

:fail
pause
exit /b 1
