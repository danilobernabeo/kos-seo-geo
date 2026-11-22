<#
.SYNOPSIS
    TTFB delle schede dei centri piu' lente e di due pagine di confronto, 5 misure ciascuna (mediana).
    Dalla chat prestazioni (22/11), con -Base per misurare anche lo stage (password da $env:AUTH, utente kos).
    Le prime richieste dopo uno svuotamento della cache rigenerano le pagine: si scarta un primo giro di riscaldamento.
.EXAMPLE
    .\misura-centri.ps1                                  # produzione
    .\misura-centri.ps1 -Base https://stage.kosgroup.com # stage
#>
param([string]$Base = "https://kosgroup.com", [int]$Ripetizioni = 5)
$pagine = @(
  "it/centri/villa-dei-pini-civitanova",
  "it/centri/myolab",
  "it/centri/villa-margherita-vicenza-riabilitazione",
  "it/centri/villa-rosa-diagnostica",
  "it/centri/centro-medico-riabilitativo",
  "it/chi-siamo/qualita-delle-cure/medici",
  "it/centri/villa-pineta",
  "it/ritiro-referti",
  "it/strutture"
)
$intest = @()
if ($Base -match "stage\.") {
  if (-not $env:AUTH) { Write-Error "Impostare `$env:AUTH con la password dello stage"; exit 1 }
  $b64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("kos:" + $env:AUTH))
  $intest = @("-H", "Authorization: Basic $b64")
}
function Misura($url) { [double]((curl.exe -s -o NUL @intest -w "%{time_starttransfer}" $url) -replace ',', '.') }
foreach ($p in $pagine) { [void](Misura "$Base/$p") }   # riscaldamento
$ris = foreach ($p in $pagine) {
  $t = 1..$Ripetizioni | ForEach-Object { Misura "$Base/$p" } | Sort-Object
  [pscustomobject]@{ Pagina = $p; "TTFB mediano (ms)" = [int]($t[[int][math]::Floor($Ripetizioni / 2)] * 1000); "min (ms)" = [int]($t[0] * 1000); "max (ms)" = [int]($t[-1] * 1000) }
}
$ris | Format-Table -AutoSize
$nome = ($Base -replace "https?://", "" -replace "[^a-z0-9]+", "-")
$file = Join-Path ([Environment]::GetFolderPath("Desktop")) ("misura-centri_{0}_{1}.csv" -f $nome, (Get-Date -Format "yyyyMMdd_HHmm"))
$ris | Export-Csv $file -NoTypeInformation -Delimiter ";" -Encoding UTF8
Write-Host "Salvato: $file"
