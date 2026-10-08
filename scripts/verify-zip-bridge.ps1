param([Parameter(Mandatory = $true)][string]$ZipPath)

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
try {
    $bridge = $zip.Entries | Where-Object { $_.FullName.Replace('\', '/') -like 'tools/print-bridge/*' }
    foreach ($entry in $bridge) {
        Write-Output $entry.FullName
    }
    $ps1 = $bridge | Where-Object { $_.Name -eq 'Print-Bridge.ps1' } | Select-Object -First 1
    if ($ps1) {
        $reader = New-Object System.IO.StreamReader($ps1.Open())
        Write-Output ('FIRST LINE: ' + $reader.ReadLine())
        $reader.Dispose()
    }
} finally {
    $zip.Dispose()
}
