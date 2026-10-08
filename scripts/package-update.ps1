<#
.SYNOPSIS
  Build a cPanel UPDATE ZIP for an already-live Amore POS (does not wipe production).

.DESCRIPTION
  Same code/vendor/assets as package-production.ps1, but SAFE for live sites:
  - Does NOT include .env (keeps live DB credentials / APP_KEY)
  - Does NOT include storage/app/installed (keeps install lock)
  - Does NOT ship local uploads / logs / cache
  - Flat ZIP root (artisan at root) for Extract into existing app folder

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\package-update.ps1
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
$zipPath = Join-Path $parent "AmorePOS-Update-$date.zip"
$zipCopy = Join-Path $root 'AmorePOS-Update-Latest.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$staging = Join-Path $env:TEMP "AmorePOS-update-stage-$date"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

$destRoot = $staging

& robocopy $root $destRoot /E /NFL /NDL /NJH /NJS /nc /ns /np `
    /XD '.git' '.github' 'node_modules' 'tests' '.idea' '.vscode' '.cursor' 'agent-transcripts' `
    /XF '.env' '.phpunit.result.cache' '.DS_Store' 'Thumbs.db' 'desktop.ini' '*.log' '.editorconfig' 'Homestead.json' 'Homestead.yaml' 'auth.json' '*.zip' 'php.ini' '.user.ini' 'phpunit.xml' | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed: $LASTEXITCODE" }

# Strip runtime / local data - never overwrite live storage contents from this package
@(
    (Join-Path $destRoot 'storage\logs'),
    (Join-Path $destRoot 'storage\framework\cache\data'),
    (Join-Path $destRoot 'storage\framework\sessions'),
    (Join-Path $destRoot 'storage\framework\views'),
    (Join-Path $destRoot 'bootstrap\cache'),
    (Join-Path $destRoot 'storage\app\public'),
    (Join-Path $destRoot 'storage\app\private')
) | ForEach-Object {
    if (Test-Path $_) {
        Get-ChildItem $_ -File -Force -Recurse -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force -ErrorAction SilentlyContinue
        Get-ChildItem $_ -Directory -Force -ErrorAction SilentlyContinue |
            Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Keep install lock out of update ZIP (live already has it; do not remove server copy)
Remove-Item (Join-Path $destRoot 'storage\app\installed') -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $destRoot 'database\database.sqlite') -Force -ErrorAction SilentlyContinue
# Never ship a .env in update packages
Remove-Item (Join-Path $destRoot '.env') -Force -ErrorAction SilentlyContinue

@('storage\app\public','storage\app\private','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\logs','bootstrap\cache') | ForEach-Object {
    $p = Join-Path $destRoot $_
    if (-not (Test-Path $p)) { New-Item -ItemType Directory -Path $p -Force | Out-Null }
}

$viewsKeep = Join-Path $destRoot 'storage\framework\views\.gitignore'
if (-not (Test-Path $viewsKeep)) {
    Set-Content -Path $viewsKeep -Value "*`n!.gitignore`n" -Encoding ascii
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

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

$z = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $names = $z.Entries | ForEach-Object { $_.FullName.Replace('\', '/') }
    $required = @(
        'artisan',
        'vendor/autoload.php',
        'public/build/manifest.json',
        'resources/views/pos/kot_print.blade.php',
        'app/Services/NetworkPrinterService.php',
        'tools/print-bridge/Print-Bridge.ps1'
    )
    foreach ($r in $required) {
        if ($names -notcontains $r) {
            throw "Update ZIP is missing required file: $r"
        }
    }
    if ($names -contains '.env') {
        throw 'Update ZIP must NOT contain .env (would overwrite live secrets)'
    }
    if ($names -contains 'storage/app/installed') {
        throw 'Update ZIP must NOT contain storage/app/installed'
    }
} finally {
    $z.Dispose()
}

Copy-Item -Path $zipPath -Destination $zipCopy -Force

$item = Get-Item $zipPath
Write-Host ''
Write-Host "Created: $($item.FullName)"
Write-Host "Copy:    $zipCopy"
Write-Host ('Size: {0:N2} MB' -f ($item.Length / 1MB))
Write-Host 'Type:    UPDATE (no .env - safe for live code overwrite)'
Write-Host ''
Write-Host 'LIVE UPDATE steps (cPanel):'
Write-Host '  0. BACKUP MySQL + full site files first'
Write-Host '  1. Upload AmorePOS-Update-*.zip next to existing artisan'
Write-Host '  2. Extract with overwrite - keep existing .env and storage uploads'
Write-Host '  3. Do NOT delete storage/app/installed'
Write-Host '  4. Do NOT re-run /install'
Write-Host '  5. If new migrations exist and you have Terminal: php artisan migrate --force'
Write-Host '  6. Clear caches if possible: php artisan optimize:clear'
Write-Host '  7. Delete the ZIP from the server'
Write-Host ''
