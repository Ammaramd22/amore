@echo off
REM Optional: show console for debugging (visible CMD)
cd /d "%~dp0"
title QRPOS Local Print Bridge (DEBUG)
echo DEBUG mode — visible window. Prefer Start-Print-Bridge.bat for normal use.
echo.
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0Print-Bridge.ps1"
echo.
echo Bridge stopped.
pause
