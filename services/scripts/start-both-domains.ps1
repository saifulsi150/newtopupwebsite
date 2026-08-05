param([int]$Port = 80)
$ErrorActionPreference = "Stop"
$projectRoot = "C:\Users\Administrator\Desktop\topup1\ghostbazar.online"
Set-Location $projectRoot
$domains = @("admin.ghostbazar.online", "topupo.ghostbazar.online")
$hostsFile = "$env:SystemRoot\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsFile -ErrorAction Stop
foreach ($domain in $domains) {
  $entry = "127.0.0.1`t$domain"
  if ($hostsContent -notmatch [regex]::Escape($domain)) {
    Add-Content $hostsFile "`n$entry"
  }
}
$php = (Get-Command php -ErrorAction Stop).Source
& $php -S "127.0.0.1:$Port" -t "public"

