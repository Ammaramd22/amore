# QRPOS Local Print Bridge v12
# Cloud POS (HTTPS) -> this bridge on POS PC -> TCP LAN printer (ESC/POS)
# Listen: http://127.0.0.1:18181/  (localhost only)
#
# v12: Receipt (bill) prints must NEVER fall back to the KOT Windows queue.
#      Kitchen jobs keep TCP → Windows KOT fallback.

$ErrorActionPreference = 'Continue'
$ListenPrefix = 'http://127.0.0.1:18181/'
$DefaultPrinterIp = '192.168.1.11'
$DefaultPrinterPort = 9100
$DefaultKitchenWindowsPrinter = 'XP-80Kitchen KOT'
$DefaultReceiptWindowsPrinter = 'XP-80C'
$BridgeVersion = 13
$LogFile = Join-Path $env:TEMP 'QRPOS-Print-Bridge.log'
$MutexName = 'Global\QRPOSPrintBridge'

function Write-BridgeLog([string]$msg, [string]$level = 'INFO') {
    $line = ('[{0}] [{1}] {2}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $level, $msg)
    try { Add-Content -Path $LogFile -Value $line -Encoding UTF8 -ErrorAction SilentlyContinue } catch {}
    if ($Host.Name -eq 'ConsoleHost') {
        $color = 'Gray'
        if ($level -eq 'ERROR') { $color = 'Red' }
        elseif ($level -eq 'WARN') { $color = 'Yellow' }
        elseif ($level -eq 'OK') { $color = 'Green' }
        Write-Host $line -ForegroundColor $color
    }
}

$script:BridgeMutex = $null
try {
    $created = $false
    $script:BridgeMutex = New-Object System.Threading.Mutex($true, $MutexName, [ref]$created)
    if (-not $created) {
        Write-BridgeLog 'Another Print Bridge instance is already running. Exiting.' 'WARN'
        exit 0
    }
} catch {
    Write-BridgeLog ('Mutex warning: ' + $_.Exception.Message) 'WARN'
}

Write-BridgeLog ('QRPOS Local Print Bridge v' + $BridgeVersion) 'OK'
Write-BridgeLog ('Listening on ' + $ListenPrefix + ' (localhost only)')
Write-BridgeLog ('Default LAN target: ' + $DefaultPrinterIp + ':' + $DefaultPrinterPort)
Write-BridgeLog ('Kitchen Windows printer: ' + $DefaultKitchenWindowsPrinter)
Write-BridgeLog ('Receipt Windows printer: ' + $DefaultReceiptWindowsPrinter)
Write-BridgeLog ('Log: ' + $LogFile)

$winspoolCode = @'
using System;
using System.Runtime.InteropServices;
public class RawPrinterHelper {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
  }
  [DllImport("winspool.Drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);
  [DllImport("winspool.Drv", EntryPoint="ClosePrinter", SetLastError=true, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool ClosePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", EntryPoint="StartDocPrinterA", SetLastError=true, CharSet=CharSet.Ansi, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);
  [DllImport("winspool.Drv", EntryPoint="EndDocPrinter", SetLastError=true, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool EndDocPrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", EntryPoint="StartPagePrinter", SetLastError=true, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool StartPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", EntryPoint="EndPagePrinter", SetLastError=true, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool EndPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", EntryPoint="WritePrinter", SetLastError=true, ExactSpelling=true, CallingConvention=CallingConvention.StdCall)]
  public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

  public static string SendBytes(string printerName, byte[] bytes) {
    IntPtr hPrinter;
    if (!OpenPrinter(printerName, out hPrinter, IntPtr.Zero)) {
      return "OpenPrinter failed for '" + printerName + "' - check Devices and Printers name";
    }
    var di = new DOCINFOA();
    di.pDocName = "QRPOS";
    di.pDataType = "RAW";
    try {
      if (!StartDocPrinter(hPrinter, 1, di)) return "StartDocPrinter failed";
      if (!StartPagePrinter(hPrinter)) { EndDocPrinter(hPrinter); return "StartPagePrinter failed"; }
      IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
      Marshal.Copy(bytes, 0, p, bytes.Length);
      int written;
      bool ok = WritePrinter(hPrinter, p, bytes.Length, out written);
      Marshal.FreeCoTaskMem(p);
      EndPagePrinter(hPrinter);
      EndDocPrinter(hPrinter);
      if (!ok) return "WritePrinter failed";
      return null;
    } finally {
      ClosePrinter(hPrinter);
    }
  }
}
'@

try {
    Add-Type -TypeDefinition $winspoolCode -Language CSharp -ErrorAction Stop | Out-Null
} catch {
    $msg = $_.Exception.Message
    if ($msg -notmatch 'already exists') {
        Write-BridgeLog ('Winspool load warning: ' + $msg) 'WARN'
    }
}

function Test-PrinterPort([string]$ip, [int]$port, [int]$timeoutMs = 4000) {
    $client = $null
    try {
        $addr = [System.Net.IPAddress]::Parse($ip.Trim())
        $client = New-Object System.Net.Sockets.TcpClient($addr.AddressFamily)
        $ar = $client.BeginConnect($addr, [int]$port, $null, $null)
        $ok = $ar.AsyncWaitHandle.WaitOne($timeoutMs, $false)
        if (-not $ok) {
            try { $client.Close() } catch {}
            return @{ ok = $false; message = ('Kitchen printer cannot be reached at ' + $ip + ':' + $port) }
        }
        $client.EndConnect($ar)
        try { $client.Close() } catch {}
        return @{ ok = $true; message = ('TCP OK ' + $ip + ':' + $port) }
    } catch {
        try { if ($client) { $client.Close() } } catch {}
        return @{ ok = $false; message = ('Kitchen printer cannot be reached at ' + $ip + ':' + $port + ' (' + $_.Exception.Message + ')') }
    }
}

function Send-Tcp([string]$ip, [int]$port, [byte[]]$data) {
    # XP-80 / similar LAN printers often miss the first TCP after idle (sleep).
    # Retry the connect only — bytes are written once a connection succeeds.
    # After a successful Write, treat as success even if close is noisy
    # (otherwise bill can print on TCP AND fall back to KOT Windows queue).
    $timeoutsMs = @(8000, 10000, 12000)
    $lastMsg = ('Printer cannot be reached at ' + $ip + ':' + $port)
    $addr = [System.Net.IPAddress]::Parse($ip.Trim())

    for ($i = 0; $i -lt $timeoutsMs.Count; $i++) {
        if ($i -gt 0) {
            Start-Sleep -Milliseconds (600 * $i)
            Write-BridgeLog ('TCP retry ' + ($i + 1) + '/' + $timeoutsMs.Count + ' ' + $ip + ':' + $port) 'WARN'
        }

        $client = $null
        $stream = $null
        try {
            $client = New-Object System.Net.Sockets.TcpClient($addr.AddressFamily)
            $client.NoDelay = $true
            $client.SendTimeout = 8000
            $client.ReceiveTimeout = 8000
            $ar = $client.BeginConnect($addr, [int]$port, $null, $null)
            $ok = $ar.AsyncWaitHandle.WaitOne([int]$timeoutsMs[$i], $false)
            if (-not $ok) {
                try { $client.Close() } catch {}
                $client = $null
                $lastMsg = ('Printer cannot be reached at ' + $ip + ':' + $port)
                continue
            }
            $client.EndConnect($ar)
            if (-not $client.Connected) {
                try { $client.Close() } catch {}
                $client = $null
                $lastMsg = ('Printer cannot be reached at ' + $ip + ':' + $port)
                continue
            }
            $stream = $client.GetStream()
            $stream.Write($data, 0, $data.Length)
            try { $stream.Flush() } catch {}
            Start-Sleep -Milliseconds 120
            return $true
        } catch {
            $lastMsg = $_.Exception.Message
            if ($lastMsg -notmatch 'cannot be reached') {
                $lastMsg = ('Printer cannot be reached at ' + $ip + ':' + $port + ' (' + $lastMsg + ')')
            }
        } finally {
            try { if ($stream) { $stream.Close() } } catch {}
            try { if ($client) { $client.Close() } } catch {}
        }
    }

    throw $lastMsg
}

function Get-InstalledPrinterNames {
    try {
        return @(Get-Printer | Select-Object -ExpandProperty Name)
    } catch {
        return @()
    }
}

function Resolve-WindowsPrinterName([string]$wanted, [string]$jobType = 'kitchen') {
    $names = Get-InstalledPrinterNames
    $wanted = if ($wanted) { $wanted.Trim() } else { '' }
    $isReceipt = ($jobType -eq 'receipt' -or $jobType -eq 'bill' -or $jobType -eq 'drawer')
    $isBot = ($jobType -eq 'bot' -or $jobType -eq 'bar')

    if ($names.Count -eq 0) {
        if ($wanted) { return $wanted }
        if ($isReceipt) { return $DefaultReceiptWindowsPrinter }
        if ($isBot) { return 'Bar Printer' }
        return $DefaultKitchenWindowsPrinter
    }

    if ($wanted -and ($names -contains $wanted)) { return $wanted }
    if ($wanted) {
        $ci = $names | Where-Object { $_.ToLowerInvariant() -eq $wanted.ToLowerInvariant() } | Select-Object -First 1
        if ($ci) { return [string]$ci }
    }

    if ($isReceipt) {
        # Bill / receipt printer only — never remap to KOT / kitchen queue
        if ($wanted) {
            $partial = $names | Where-Object {
                $_ -match [regex]::Escape($wanted) -and $_ -notmatch '(?i)KOT|Kitchen|BOT|Bar'
            } | Select-Object -First 1
            if ($partial) { return [string]$partial }
        }
        $bill = $names | Where-Object { $_ -match '(?i)(bill|receipt|cashier|counter)' } | Select-Object -First 1
        if ($bill) { return [string]$bill }
        $xp = $names | Where-Object { $_ -match '(?i)XP-?80' -and $_ -notmatch '(?i)KOT|Kitchen|BOT|Bar' } | Select-Object -First 1
        if ($xp) { return [string]$xp }
        $pos = $names | Where-Object { $_ -match '(?i)POS-?80|PrinterPOS' -and $_ -notmatch '(?i)KOT|Kitchen' } | Select-Object -First 1
        if ($pos) { return [string]$pos }
        if ($wanted) { return $wanted }
        return $DefaultReceiptWindowsPrinter
    }

    if ($isBot) {
        # Bar / BOT only — never remap to kitchen KOT
        if ($wanted) {
            $partial = $names | Where-Object {
                $_ -match [regex]::Escape($wanted) -and $_ -notmatch '(?i)KOT|Kitchen'
            } | Select-Object -First 1
            if ($partial) { return [string]$partial }
        }
        $bar = $names | Where-Object { $_ -match '(?i)\b(BOT|Bar|Juice|Drink)\b' } | Select-Object -First 1
        if ($bar) { return [string]$bar }
        if ($wanted) { return $wanted }
        return 'Bar Printer'
    }

    # Kitchen / KOT / BOT
    if ($wanted) {
        $partial = $names | Where-Object { $_ -match [regex]::Escape($wanted) } | Select-Object -First 1
        if ($partial) { return [string]$partial }
    }
    $partial = $names | Where-Object { $_ -match '(?i)XP-?80.*KOT|KOT.*XP-?80|Kitchen.*KOT|KOT.*Kitchen' } | Select-Object -First 1
    if ($partial) { return [string]$partial }
    $partial = $names | Where-Object { $_ -match '(?i)\bKOT\b|Kitchen' } | Select-Object -First 1
    if ($partial) { return [string]$partial }
    $partial = $names | Where-Object { $_ -match '(?i)XP-?80' } | Select-Object -First 1
    if ($partial) { return [string]$partial }
    if ($wanted) { return $wanted }
    return $DefaultKitchenWindowsPrinter
}

$installed = Get-InstalledPrinterNames
Write-BridgeLog ('Installed printers: ' + ($(if ($installed.Count) { $installed -join ', ' } else { '(none)' })))

try { netsh http add urlacl url=$ListenPrefix user=Everyone 2>$null | Out-Null } catch {}

function Set-CorsHeaders($response) {
    $response.Headers['Access-Control-Allow-Origin'] = '*'
    $response.Headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
    $response.Headers['Access-Control-Allow-Headers'] = 'Content-Type, Accept, Access-Control-Request-Private-Network'
    # Required for HTTPS cloud POS -> http://127.0.0.1 (Chrome / Brave Private Network Access)
    $response.Headers['Access-Control-Allow-Private-Network'] = 'true'
}

function Send-Json($response, $status, $obj) {
    try {
        $json = ($obj | ConvertTo-Json -Compress -Depth 6)
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
        $response.StatusCode = $status
        $response.ContentType = 'application/json; charset=utf-8'
        Set-CorsHeaders $response
        $response.ContentLength64 = $bytes.Length
        $response.OutputStream.Write($bytes, 0, $bytes.Length)
        $response.OutputStream.Close()
    } catch {
        try { $response.Abort() } catch {}
    }
}

function Send-WindowsPrinter([string]$printerName, [byte[]]$data) {
    $err = [RawPrinterHelper]::SendBytes($printerName, $data)
    if ($err) { throw $err }
}

function Send-UsbRawPorts([byte[]]$data) {
    $tried = @()
    foreach ($port in @('USB001','USB002','USB003','USB004')) {
        $path = '\\.\' + $port
        try {
            $fs = [System.IO.File]::Open($path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Write, [System.IO.FileShare]::ReadWrite)
            $fs.Write($data, 0, $data.Length)
            $fs.Flush()
            $fs.Close()
            return $port
        } catch {
            $tried += ($port + ':' + $_.Exception.Message)
        }
    }
    throw ('USB raw ports failed: ' + ($tried -join ' | '))
}

function Build-DefaultDrawerKick {
    return [byte[]](0x1B,0x40, 0x1B,0x61,0x01, 0x0A, 0x1B,0x70,0x00,0xFA,0xFA)
}

function Handle-HttpContext($ctx) {
    $req = $ctx.Request
    $res = $ctx.Response
    $path = $req.Url.AbsolutePath.TrimEnd('/').ToLowerInvariant()
    if ([string]::IsNullOrEmpty($path)) { $path = '/' }

    try {
        if ($req.HttpMethod -eq 'OPTIONS') {
            $res.StatusCode = 204
            Set-CorsHeaders $res
            $res.Close()
            return
        }

        if ($req.HttpMethod -eq 'GET' -and ($path -eq '/' -or $path -eq '/health')) {
            Send-Json $res 200 @{
                success = $true
                service = 'qrpos-print-bridge'
                version = $BridgeVersion
                listen = $ListenPrefix
                windows_printer = $DefaultKitchenWindowsPrinter
                receipt_windows_printer = $DefaultReceiptWindowsPrinter
                default_printer_ip = $DefaultPrinterIp
                default_printer_port = $DefaultPrinterPort
                mode = 'tcp-first-when-ip'
                drawer = $true
                background = $true
            }
            return
        }

        if ($req.HttpMethod -eq 'GET' -and $path -eq '/probe') {
            $ip = if ($req.QueryString['ip']) { [string]$req.QueryString['ip'] } else { $DefaultPrinterIp }
            $port = if ($req.QueryString['port']) { [int]$req.QueryString['port'] } else { $DefaultPrinterPort }
            $names = Get-InstalledPrinterNames
            $resolved = Resolve-WindowsPrinterName $DefaultKitchenWindowsPrinter 'kitchen'
            $hasWin = $names -contains $resolved
            $p = Test-PrinterPort $ip $port
            Send-Json $res 200 @{
                success = $true
                windows_printer = $resolved
                windows_printer_found = [bool]$hasWin
                printers = $names
                printer_ip = $ip
                printer_port = $port
                tcp_ok = [bool]$p.ok
                tcp_message = $p.message
                message = $p.message
            }
            return
        }

        if ($req.HttpMethod -eq 'POST' -and ($path -eq '/drawer' -or $path -eq '/print')) {
            $reader = New-Object System.IO.StreamReader($req.InputStream, $req.ContentEncoding)
            $body = $reader.ReadToEnd()
            $reader.Close()
            $obj = $null
            if ($body) {
                try { $obj = $body | ConvertFrom-Json } catch { $obj = $null }
            }
            $ip = ''
            if ($obj -and $obj.ip) { $ip = ([string]$obj.ip).Trim() }
            $port = $DefaultPrinterPort
            if ($obj -and $obj.port) { $port = [int]$obj.port }
            if ($port -lt 1 -or $port -gt 65535) { $port = 9100 }

            $jobType = 'kitchen'
            if ($path -eq '/drawer') { $jobType = 'drawer' }
            if ($obj -and $obj.job_type) { $jobType = ([string]$obj.job_type).ToLowerInvariant() }
            elseif ($obj -and $obj.job) { $jobType = ([string]$obj.job).ToLowerInvariant() }
            if ($jobType -eq 'bill') { $jobType = 'receipt' }
            if ($jobType -eq 'bar') { $jobType = 'bot' }

            $isReceiptJob = ($jobType -eq 'receipt' -or $jobType -eq 'drawer')
            $isBotJob = ($jobType -eq 'bot')

            $winWanted = if ($isReceiptJob) { $DefaultReceiptWindowsPrinter } elseif ($isBotJob) { 'Bar Printer' } else { $DefaultKitchenWindowsPrinter }
            if ($obj -and $obj.printer_name) { $winWanted = [string]$obj.printer_name }
            $winName = Resolve-WindowsPrinterName $winWanted $jobType

            $mode = 'windows'
            if ($obj -and $obj.mode) { $mode = ([string]$obj.mode).ToLowerInvariant() }
            elseif ($ip) { $mode = 'tcp' }

            # Normalize modes
            if ($mode -eq 'network' -or $mode -eq 'lan' -or $mode -eq 'auto') { $mode = $(if ($ip) { 'tcp' } else { 'windows' }) }

            # Receipt/bill/BOT: never fall back to KOT Windows queue after TCP
            $allowWindowsFallback = -not ($isReceiptJob -or $isBotJob)
            if ($obj -and ($null -ne $obj.allow_windows_fallback)) {
                $allowWindowsFallback = [bool]$obj.allow_windows_fallback
            }
            if ($isReceiptJob -or $isBotJob) { $allowWindowsFallback = $false }

            $isDrawer = ($path -eq '/drawer')
            if ($isDrawer) {
                if ($obj -and $obj.data) {
                    $bytes = [Convert]::FromBase64String([string]$obj.data)
                } else {
                    $bytes = Build-DefaultDrawerKick
                }
            } else {
                if (-not $obj -or -not $obj.data) { throw 'Missing data (base64 ESC/POS)' }
                $bytes = [Convert]::FromBase64String([string]$obj.data)
            }

            $used = $null
            $errors = @()

            # LAN: TCP first
            if ((-not $used) -and $ip -and ($mode -eq 'tcp')) {
                try {
                    [void](Send-Tcp $ip $port $bytes)
                    $used = ('tcp:' + $ip + ':' + $port)
                } catch {
                    $errors += $_.Exception.Message
                    Write-BridgeLog ('TCP failed' + $(if ($allowWindowsFallback) { ', trying Windows printer: ' + $winName } else { ' (no Windows fallback for ' + $jobType + ')' })) 'WARN'
                }
            }

            # Windows spooler — kitchen may fall back; receipt/bill must not (stops bill→KOT)
            if ((-not $used) -and ($mode -eq 'windows' -or $allowWindowsFallback)) {
                $attempts = 0
                while ((-not $used) -and ($attempts -lt 2)) {
                    $attempts++
                    try {
                        Send-WindowsPrinter $winName $bytes
                        $used = ('windows:' + $winName)
                    } catch {
                        $errors += ('Windows(' + $winName + ') try' + $attempts + ': ' + $_.Exception.Message)
                        if ($attempts -lt 2) { Start-Sleep -Milliseconds 350 }
                    }
                }
            }

            if ((-not $used) -and $isDrawer) {
                try {
                    $usb = Send-UsbRawPorts $bytes
                    $used = ('usb-raw:' + $usb)
                } catch {
                    $errors += $_.Exception.Message
                }
            }

            if (-not $used) {
                if ($ip) {
                    throw ('Printer cannot be reached at ' + $ip + ':' + $port)
                }
                throw ($errors -join ' | ')
            }

            $label = if ($isDrawer) { 'Drawer kick' } else { 'Printed' }
            Write-BridgeLog ($label + ' ' + $bytes.Length + ' bytes via ' + $used + ' job=' + $jobType) 'OK'
            Send-Json $res 200 @{
                success = $true
                message = ($label + ' via ' + $used)
                via = $used
                printer_name = $winName
                printer_ip = $ip
                printer_port = $port
                job_type = $jobType
                drawer = [bool]$isDrawer
            }
            return
        }

        Send-Json $res 404 @{ success = $false; message = 'Not found. Use GET /health, GET /probe, POST /print, POST /drawer' }
    } catch {
        Write-BridgeLog ('ERROR ' + $_.Exception.Message) 'ERROR'
        try {
            Send-Json $res 500 @{ success = $false; message = $_.Exception.Message }
        } catch {}
    }
}

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ' QRPOS Print Bridge is RUNNING' -ForegroundColor Green
Write-Host ' http://127.0.0.1:18181/health' -ForegroundColor Cyan
Write-Host ' Keep this window open while using POS' -ForegroundColor Yellow
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

while ($true) {
    $listener = $null
    try {
        $listener = New-Object System.Net.HttpListener
        $listener.Prefixes.Add($ListenPrefix)
        $listener.Start()
        Write-BridgeLog 'Bridge ready. Waiting for POS print jobs...' 'OK'

        while ($listener.IsListening) {
            try {
                $ctx = $listener.GetContext()
                Handle-HttpContext $ctx
            } catch {
                if ($_.Exception.Message -match 'stop|disposed|cancel|Cannot access') { break }
                Write-BridgeLog ('Listen loop: ' + $_.Exception.Message) 'WARN'
                Start-Sleep -Milliseconds 150
            }
        }
    } catch {
        Write-BridgeLog ('Listener failed: ' + $_.Exception.Message) 'ERROR'
        Write-BridgeLog 'Retry in 3s. If URL ACL error, run once as Administrator.' 'WARN'
        Start-Sleep -Seconds 3
    } finally {
        try {
            if ($listener -and $listener.IsListening) { $listener.Stop() }
            if ($listener) { $listener.Close() }
        } catch {}
    }
    Start-Sleep -Seconds 1
}
