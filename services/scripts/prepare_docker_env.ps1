param(
    [string]$SourceEnv = ".env",
    [string]$TargetEnv = ".env.docker.local"
)

$sourcePath = Join-Path $PSScriptRoot "..\$SourceEnv"
$targetPath = Join-Path $PSScriptRoot "..\$TargetEnv"

if (-not (Test-Path $sourcePath)) {
    Write-Error "Source env file not found: $sourcePath"
    exit 1
}

$lines = Get-Content $sourcePath
$out = New-Object System.Collections.Generic.List[string]
$written = @{}

function Set-Or-ReplaceLine {
    param(
        [System.Collections.Generic.List[string]]$List,
        [string]$Key,
        [string]$Value
    )

    $found = $false
    for ($i = 0; $i -lt $List.Count; $i++) {
        if ($List[$i] -match "^$([Regex]::Escape($Key))=") {
            $List[$i] = "$Key=$Value"
            $found = $true
            break
        }
    }

    if (-not $found) {
        $List.Add("$Key=$Value") | Out-Null
    }
}

foreach ($line in $lines) {
    $trim = $line.Trim()
    if ($trim -eq "" -or $trim.StartsWith("#") -or -not $trim.Contains("=")) {
        $out.Add($line) | Out-Null
        continue
    }

    $key, $value = $line -split "=", 2
    $k = $key.Trim()

    switch ($k) {
        "APP_URL" { $value = "http://localhost:8080" }
        "DB_HOST" { $value = "mysql" }
        "DB_PORT" { $value = "3306" }
        "REDIS_HOST" { $value = "redis" }
        "REDIS_PORT" { $value = "6379" }
        "CACHE_STORE" { $value = "redis" }
        "QUEUE_CONNECTION" { $value = "redis" }
        "SESSION_DRIVER" { $value = "redis" }
        "SESSION_DOMAIN" { $value = "localhost" }
    }

    $out.Add("$k=$value") | Out-Null
    $written[$k] = $true
}

Set-Or-ReplaceLine -List $out -Key "APP_ENV" -Value "production"
Set-Or-ReplaceLine -List $out -Key "APP_DEBUG" -Value "false"
Set-Or-ReplaceLine -List $out -Key "CACHE_STORE" -Value "redis"
Set-Or-ReplaceLine -List $out -Key "QUEUE_CONNECTION" -Value "redis"
Set-Or-ReplaceLine -List $out -Key "SESSION_DRIVER" -Value "redis"
Set-Or-ReplaceLine -List $out -Key "SESSION_CONNECTION" -Value "default"
Set-Or-ReplaceLine -List $out -Key "REDIS_CLIENT" -Value "phpredis"
Set-Or-ReplaceLine -List $out -Key "REDIS_DB" -Value "0"
Set-Or-ReplaceLine -List $out -Key "REDIS_CACHE_DB" -Value "1"

Set-Content -Path $targetPath -Value $out -NoNewline:$false
Write-Host "Generated $TargetEnv from $SourceEnv"
