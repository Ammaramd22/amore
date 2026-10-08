@echo off
REM Add Print Bridge to Windows Startup (runs hidden at login)
cd /d "%~dp0"
set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "VBS=%~dp0Start-Print-Bridge-Hidden.vbs"
set "LINK=%STARTUP%\QRPOS-Print-Bridge.lnk"

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -Command ^
  "$vbs = '%VBS%'; $link = '%LINK%'; $ws = New-Object -ComObject WScript.Shell; $sc = $ws.CreateShortcut($link); $sc.TargetPath = 'wscript.exe'; $sc.Arguments = '//nologo \"' + $vbs + '\"'; $sc.WorkingDirectory = (Split-Path -Parent $vbs); $sc.WindowStyle = 7; $sc.Description = 'QRPOS Print Bridge (background)'; $sc.Save(); Write-Host ('Installed: ' + $link) -ForegroundColor Green"

echo.
echo Print Bridge will start hidden when this Windows user logs in.
echo Run Stop-Print-Bridge.bat anytime to stop it.
echo Run Uninstall-Print-Bridge-Startup.bat to remove auto-start.
timeout /t 4 >nul
