param(
    [string]$EnvFile = ".env",
    [string]$ProjectRoot = "",
    [switch]$Force
)

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
}

$envPath = Join-Path $ProjectRoot $EnvFile
if (-not (Test-Path $envPath)) {
    Write-Error "Env file not found: $envPath"
    exit 1
}

$backupPath = "$envPath.bak.$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $envPath $backupPath -Force
Write-Host "Backup created: $backupPath"

$phpModules = & php -m 2>$null
$hasRedisExt = $false
if ($phpModules) {
    $hasRedisExt = ($phpModules | Where-Object { $_ -match '^redis$' }).Count -gt 0
}

$redisClient = if ($hasRedisExt) { "phpredis" } else { "predis" }
Write-Host "Redis client selected: $redisClient"

$lines = Get-Content $envPath
$pairs = [ordered]@{}
$order = New-Object System.Collections.Generic.List[string]

foreach ($line in $lines) {
    if ($line.Trim().StartsWith("#") -or -not $line.Contains("=")) {
        continue
    }
    $key, $value = $line -split "=", 2
    $k = $key.Trim()
    if (-not $pairs.Contains($k)) {
        $order.Add($k) | Out-Null
    }
    $pairs[$k] = $value
}

$redisHost = if ($pairs.Contains("REDIS_HOST")) { $pairs["REDIS_HOST"].Trim('"') } else { "127.0.0.1" }
$redisPort = if ($pairs.Contains("REDIS_PORT")) { [int]$pairs["REDIS_PORT"] } else { 6379 }

# Do not switch to Redis-backed drivers unless Redis is reachable,
# unless the caller explicitly forces the switch.
if (-not $Force) {
    $redisReachable = $false
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $async = $tcpClient.BeginConnect($redisHost, $redisPort, $null, $null)
        $redisReachable = $async.AsyncWaitHandle.WaitOne(1500, $false) -and $tcpClient.Connected
        $tcpClient.Close()
    } catch {
        $redisReachable = $false
    }

    if (-not $redisReachable) {
        Write-Warning "Redis is not reachable at $redisHost`:$redisPort. No changes applied."
        Write-Host "Tip: start Redis first, then rerun this script."
        exit 2
    }
}

$pairs["CACHE_STORE"] = "redis"
$pairs["QUEUE_CONNECTION"] = "redis"
$pairs["SESSION_DRIVER"] = "redis"
$pairs["SESSION_CONNECTION"] = "default"
$pairs["REDIS_CLIENT"] = $redisClient
if (-not $pairs.Contains("REDIS_HOST")) { $pairs["REDIS_HOST"] = "127.0.0.1" }
if (-not $pairs.Contains("REDIS_PORT")) { $pairs["REDIS_PORT"] = "6379" }
if (-not $pairs.Contains("REDIS_DB")) { $pairs["REDIS_DB"] = "0" }
if (-not $pairs.Contains("REDIS_CACHE_DB")) { $pairs["REDIS_CACHE_DB"] = "1" }

foreach ($needed in @("CACHE_STORE","QUEUE_CONNECTION","SESSION_DRIVER","SESSION_CONNECTION","REDIS_CLIENT","REDIS_HOST","REDIS_PORT","REDIS_DB","REDIS_CACHE_DB")) {
    if (-not $order.Contains($needed)) {
        $order.Add($needed) | Out-Null
    }
}

$out = New-Object System.Collections.Generic.List[string]
foreach ($line in $lines) {
    if ($line.Trim().StartsWith("#") -or -not $line.Contains("=")) {
        $out.Add($line) | Out-Null
    }
}

$out.Add("") | Out-Null
$out.Add("# Performance mode managed keys") | Out-Null
foreach ($k in $order) {
    if ($pairs.Contains($k)) {
        $out.Add("$k=$($pairs[$k])") | Out-Null
    }
}

Set-Content -Path $envPath -Value $out
Write-Host "Updated env for high performance mode: $envPath"

if ($redisClient -eq "predis") {
    Write-Host "Installing predis/predis (Redis PHP extension not found)..."
    & composer require predis/predis:^2.2 --no-interaction
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Composer install failed. Please run manually: composer require predis/predis:^2.2"
    }
}

Write-Host "Running cache clear..."
& php artisan optimize:clear

if ($LASTEXITCODE -ne 0) {
    Copy-Item $backupPath $envPath -Force
    Write-Warning "optimize:clear failed. Env has been restored from backup."
    exit 1
}

Write-Host "Done. High performance mode is enabled."
