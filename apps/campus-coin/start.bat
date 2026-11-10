@echo off
setlocal
cd /d "%~dp0"

echo ============================================
echo  Campus Coin - one-click start
echo ============================================
echo.

where python >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Python not found on PATH. Install Python 3.10+ and re-run.
  pause
  exit /b 1
)

if not exist "venv\Scripts\python.exe" (
  echo [setup] Creating Python virtual environment and installing packages...
  python -m venv venv
  call "venv\Scripts\python.exe" -m pip install --upgrade pip >nul
  call "venv\Scripts\python.exe" -m pip install -r requirements.txt
  if errorlevel 1 (
    echo [ERROR] pip install failed. Check your internet connection and re-run.
    pause
    exit /b 1
  )
)

if not exist "frontend\node_modules" (
  where npm >nul 2>&1
  if errorlevel 1 (
    echo [ERROR] Node.js/npm not found on PATH. Install Node.js 18+ and re-run.
    pause
    exit /b 1
  )
  echo [setup] Installing frontend packages - first run only...
  pushd frontend
  call npm install
  popd
)

if not exist ".env" (
  echo [setup] Creating .env from .env.example - adjust MySQL settings inside if needed.
  copy /y ".env.example" ".env" >nul
)

echo.
echo [start] Django API   -^> http://127.0.0.1:8000/api/
start "CampusCoin API" cmd /k "venv\Scripts\python.exe manage.py runserver 127.0.0.1:8000"

echo [start] React website -^> http://localhost:5173
start "CampusCoin Website" cmd /k "cd frontend && npm run dev"

echo.
echo Both servers are starting in their own windows.
echo Open http://localhost:5173 in your browser (give it ~10 seconds).
echo If the API window shows a MySQL connection error, start your MySQL
echo server first, or fix the MYSQL_* lines in .env.
echo.
pause
