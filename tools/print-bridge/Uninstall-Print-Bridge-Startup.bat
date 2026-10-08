@echo off
set "LINK=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\QRPOS-Print-Bridge.lnk"
if exist "%LINK%" (
  del "%LINK%"
  echo Removed Startup shortcut.
) else (
  echo Startup shortcut was not installed.
)
timeout /t 2 >nul
