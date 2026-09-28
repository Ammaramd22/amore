<#
.SYNOPSIS
  Build a cPanel-ready production ZIP of Restaurant POS (no business-logic changes).

.DESCRIPTION
  - Assumes you already ran: composer install --no-dev --optimize-autoloader
  - Assumes you already ran: npm install && npm run build
  - Writes a FLAT ZIP (artisan / app / public / vendor at ZIP root) so cPanel
    Extract into /public_html does NOT create a nested RestaurantPOS/ folder.
  - Excludes nested *.zip, .env, and local junk.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\package-production.ps1
#>

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $root 'artisan'))) {
    $root = (Get-Location).Path
}
Set-Location $root

if (-not (Test-Path 'vendor\autoload.php')) {
    throw 'vendor/ missing. Run: composer install --no-dev --optimize-autoloader'
}
if (-not (Test-Path 'public\build\manifest.json')) {
    throw 'public/build missing. Run: npm install && npm run build'
}

$date = Get-Date -Format 'yyyyMMdd-HHmm'
$parent = Split-Path $root -Parent
$zipPath = Join-Path $parent "RestaurantPOS-Production-$date.zip"
$zipCopy = Join-Path $root 'Avenque-QRPOS-Production-Update.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$staging = Join-Path $env:TEMP "RestaurantPOS-prod-stage-$date"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

# Flat layout: ZIP root = Laravel app root (correct for Extract → /public_html)
$destRoot = $staging

# NOTE: Do NOT exclude all folders named "dist" — Laravel ships required assets in
# vendor/laravel/framework/.../exceptions/renderer/dist/{styles.css,scripts.js}
& robocopy $root $destRoot /E /NFL /NDL /NJH /NJS /nc /ns /np `
    /XD '.git' '.github' 'node_modules' 'tests' '.idea' '.vscode' '.cursor' `
    /XF '.env' '.phpunit.result.cache' '.DS_Store' 'Thumbs.db' 'desktop.ini' '*.log' '.editorconfig' 'Homestead.json' 'Homestead.yaml' 'auth.json' '*.zip' | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed: $LASTEXITCODE" }

@(
    (Join-Path $destRoot 'storage\logs'),
    (Join-Path $destRoot 'storage\framework\cache\data'),
    (Join-Path $destRoot 'storage\framework\sessions'),
    (Join-Path $destRoot 'storage\framework\views'),
    (Join-Path $destRoot 'bootstrap\cache')
) | ForEach-Object {
    if (Test-Path $_) {
        Get-ChildItem $_ -File -Force -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force -ErrorAction SilentlyContinue
    }
}
Remove-Item (Join-Path $destRoot 'storage\app\installed') -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $destRoot 'database\database.sqlite') -Force -ErrorAction SilentlyContinue

@('storage\app\public','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\logs','bootstrap\cache') | ForEach-Object {
    $p = Join-Path $destRoot $_
    if (-not (Test-Path $p)) { New-Item -ItemType Directory -Path $p -Force | Out-Null }
}

# Ensure writable placeholders for cPanel (empty views dir is OK)
$viewsKeep = Join-Path $destRoot 'storage\framework\views\.gitignore'
if (-not (Test-Path $viewsKeep)) {
    Set-Content -Path $viewsKeep -Value "*`n!.gitignore`n" -Encoding ascii
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

# CreateFromDirectory on Windows PowerShell writes '\' separators; Linux/cPanel
# extractors can treat those as literal filename characters instead of folders.
# Build entries by hand so paths use '/' as the ZIP spec requires.
$stageRoot = (Resolve-Path $staging).Path.TrimEnd('\')
$archive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $stageRoot -Directory -Recurse -Force | ForEach-Object {
        if (-not (Get-ChildItem -LiteralPath $_.FullName -Force)) {
            $rel = $_.FullName.Substring($stageRoot.Length + 1).Replace('\', '/') + '/'
            $archive.CreateEntry($rel) | Out-Null
        }
    }
    Get-ChildItem -LiteralPath $stageRoot -File -Recurse -Force | ForEach-Object {
        $rel = $_.FullName.Substring($stageRoot.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive, $_.FullName, $rel, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $archive.Dispose()
}
Remove-Item $staging -Recurse -Force

# Post-verify critical production assets inside ZIP (FLAT paths)
$z = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $names = $z.Entries | ForEach-Object { $_.FullName.Replace('\', '/') }
    $required = @(
        'artisan',
        'vendor/autoload.php',
        'public/build/manifest.json',
        'resources/views/pos/kot_print.blade.php',
        'app/Services/NetworkPrinterService.php',
        'tools/print-bridge/Print-Bridge.ps1',
        'tools/print-bridge/Start-Print-Bridge.bat',
        'vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer/dist/styles.css',
        'vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer/dist/scripts.js'
    )
    foreach ($r in $required) {
        if ($names -notcontains $r) {
            throw "Packaged ZIP is missing required file: $r"
        }
    }
    $nested = $names | Where-Object { $_ -like 'RestaurantPOS/*' } | Select-Object -First 1
    if ($nested) {
        throw "ZIP still has nested RestaurantPOS/ prefix - expected flat public_html extract"
    }
} finally {
    $z.Dispose()
}

Copy-Item -Path $zipPath -Destination $zipCopy -Force

$item = Get-Item $zipPath
Write-Host ""
Write-Host "Created: $($item.FullName)"
Write-Host "Copy:    $zipCopy"
Write-Host ("Size: {0:N2} MB" -f ($item.Length / 1MB))
Write-Host "Layout:  FLAT (extract into /public_html - artisan at ZIP root)"
Write-Host "Verified: vendor + public/build + KOT print files + no nested RestaurantPOS/"
Write-Host ""
Write-Host "cPanel steps:"
Write-Host "  1. Upload ZIP to /public_html"
Write-Host "  2. Extract destination = /public_html"
Write-Host "  3. Overwrite code files - DO NOT overwrite live .env"
Write-Host "  4. Delete the ZIP after extract"
