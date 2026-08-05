param(
    [string]$EnvFile = ".env",
    [string]$ProjectRoot = ""
)

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
}

$envPath = Join-Path $ProjectRoot $EnvFile
if (-not (Test-Path $envPath)) {
    Write-Error "Env file not found: $envPath"
    exit 1
}

$backup = Get-ChildItem -Path "$envPath.bak.*" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($null -eq $backup) {
    Write-Warning "No backup file found. Applying safe fallback values directly."

    $content = Get-Content $envPath
    $result = foreach ($line in $content) {
        if ($line -match '^CACHE_STORE=') { 'CACHE_STORE=file'; continue }
        if ($line -match '^QUEUE_CONNECTION=') { 'QUEUE_CONNECTION=database'; continue }
        if ($line -match '^SESSION_DRIVER=') { 'SESSION_DRIVER=file'; continue }
        if ($line -match '^SESSION_CONNECTION=') { 'SESSION_CONNECTION='; continue }
        $line
    }
    Set-Content -Path $envPath -Value $result
} else {
    Copy-Item $backup.FullName $envPath -Force
    Write-Host "Restored env from backup: $($backup.FullName)"
}

& php artisan optimize:clear
Write-Host "High performance mode disabled."
