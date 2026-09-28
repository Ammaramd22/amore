@echo off
REM QRPOS Print Bridge — keep this window open while using POS
cd /d "%~dp0"
title QRPOS Print Bridge
color 0A

echo ========================================
echo  QRPOS Local Print Bridge
echo ========================================
echo.
echo  POS cloud site talks to this PC at:
echo    http://127.0.0.1:18181
echo.
echo  Kitchen LAN print uses Kitchen settings IP/port
echo  (e.g. 192.168.1.11:9100) via raw TCP ESC/POS.
echo.
echo  KEEP THIS WINDOW OPEN while using the POS.
echo  Close window = stop bridge.
echo ========================================
echo.

REM If already running, tell user and exit
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { $r = Invoke-WebRequest -Uri 'http://127.0.0.1:18181/health' -UseBasicParsing -TimeoutSec 2; if ($r.StatusCode -eq 200) { Write-Host 'Print Bridge is ALREADY running.' -ForegroundColor Green; Write-Host 'You can minimize this window.'; exit 0 } else { exit 1 } } catch { exit 1 }"
if %ERRORLEVEL%==0 (
  echo.
  echo Log: %TEMP%\QRPOS-Print-Bridge.log
  echo Optional: Install-Print-Bridge-Startup.bat for auto-start at login.
  pause
  exit /b 0
)

echo Starting bridge...
echo.
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0Print-Bridge.ps1"

echo.
echo Bridge stopped.
echo Log: %TEMP%\QRPOS-Print-Bridge.log
pause
