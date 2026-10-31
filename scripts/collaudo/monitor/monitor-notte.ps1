<#
.SYNOPSIS
    Un giro di monitoraggio: kosgroup.com/it e www.google.com in parallelo,
    30 campioni ciascuno (circa 3 minuti). Pensato per essere lanciato da
    un'attivita' pianificata ogni 20 minuti.
    Deve stare nella stessa cartella di misura-ttfb.ps1.
    I CSV vanno in Desktop\monitor-notte, con data e ora nel nome.
#>
$dir = Join-Path ([Environment]::GetFolderPath("Desktop")) "monitor-notte"
New-Item -ItemType Directory -Force -Path $dir | Out-Null
$script = Join-Path $PSScriptRoot "misura-ttfb.ps1"
$ts = Get-Date -Format "yyyyMMdd_HHmm"

$j1 = Start-Job -ScriptBlock { param($s, $f) & $s -Url "https://kosgroup.com/it" -Campioni 100 -Csv $f *> $null } `
      -ArgumentList $script, (Join-Path $dir "kos_$ts.csv")
$j2 = Start-Job -ScriptBlock { param($s, $f) & $s -Url "https://www.google.com" -Campioni 100 -Csv $f *> $null } `
      -ArgumentList $script, (Join-Path $dir "google_$ts.csv")
$j1, $j2 | Wait-Job -Timeout 900 | Out-Null
$j1, $j2 | Remove-Job -Force
