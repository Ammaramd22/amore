QRPOS Local Print Bridge v13
==============================

REQUIRED for cloud-hosted POS (e.g. https://zingers-qrpos.avenque.io)
when printers are on the shop LAN (e.g. 192.168.1.11:9100).

Flow:
  Browser POS (HTTPS)
    -> http://127.0.0.1:18181  (this bridge, localhost only)
    -> TCP raw ESC/POS
    -> Bill printer OR Kitchen printer (separate job types)

v13 IMPORTANT
-------------
- Bill/receipt jobs print ONLY to the bill printer (never KOT).
- BOT/bar jobs print ONLY to the bar printer (never KOT).
- Kitchen/KOT jobs keep TCP → Windows KOT fallback.
- If no bar printer is configured, BOT is skipped (not sent to KOT).

v12 IMPORTANT
-------------
- Bill/receipt jobs (job_type=receipt) print ONLY to the bill printer.
  They never fall back to the KOT Windows queue (fixes bill printing on both).
- Kitchen/KOT jobs keep TCP → Windows KOT fallback.

START (POS Windows PC)
----------------------
1. Stop old bridge (Stop-Print-Bridge.bat or close window)
2. Double-click: Start-Print-Bridge.bat  (must be v12)
3. KEEP THE WINDOW OPEN while using POS
4. Test health in browser on this PC:
     http://127.0.0.1:18181/health
   Check "version": 12

AUTO-START AT LOGIN
-------------------
Double-click: Setup-Print-Bridge-Auto.bat
  or Install-Print-Bridge-Startup.bat

BRAVE / CHROME
--------------
If POS cannot reach the bridge (Failed to fetch):
  - Brave: turn Shields DOWN for the POS site
  - Or use Chrome for the POS terminal
Bridge sends Access-Control-Allow-Private-Network for HTTPS->localhost.

STOP
----
Close the bridge window, or run Stop-Print-Bridge.bat
Log: %TEMP%\QRPOS-Print-Bridge.log

API
---
POST /print  JSON: { ip, port, printer_name, mode, job_type, data }
  job_type: receipt | kitchen | drawer
POST /drawer JSON: drawer kick (receipt printer only)
GET  /health
GET  /probe?ip=&port=
GET  /health
GET  /probe?ip=&port=
POST /print   JSON: { ip, port, printer_name, mode: "tcp"|"windows", data: "<base64 ESC/POS>" }
POST /drawer

For kitchen LAN, POS sends mode=tcp with the Kitchen settings IP/port.
ESC/POS payload (including cut) is built by the Laravel app — bridge only relays bytes.

TCP to the kitchen printer retries automatically (printer sleep / first job after idle).
Restart this bridge after updating Print-Bridge.ps1.
