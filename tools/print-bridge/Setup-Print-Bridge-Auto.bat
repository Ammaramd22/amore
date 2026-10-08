@echo off
REM One-time setup: start Print Bridge now + auto-start at every Windows login
cd /d "%~dp0"

echo ========================================
echo  QRPOS Print Bridge - Auto Setup
echo ========================================
echo.

REM Start bridge immediately (hidden)
wscript.exe //nologo "%~dp0Start-Print-Bridge-Hidden.vbs"
timeout /t 2 /nobreak >nul

REM Install login startup shortcut
call "%~dp0Install-Print-Bridge-Startup.bat"

echo.
echo DONE.
echo  - Print Bridge is starting now in the background
echo  - It will also start automatically when you log into Windows
echo.
echo Test: open http://127.0.0.1:18181/health in this PC's browser
echo If using Brave: turn Shields DOWN for your POS site
echo.
echo For fully automatic KOT without Print Bridge:
echo  Admin -^> Kitchens -^> set Printer IP (LAN XP-80) + port 9100
echo.
pause
