@echo off
REM Stop background QRPOS Print Bridge
echo Stopping Print Bridge...
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -Command ^
  "$procs = Get-CimInstance Win32_Process -Filter \"name='powershell.exe'\" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -match 'Print-Bridge\.ps1' }; if (-not $procs) { Write-Host 'No Print Bridge process found.'; exit 0 }; $procs | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue; Write-Host ('Stopped PID ' + $_.ProcessId) }"
echo Done.
timeout /t 2 >nul
