<#
.SYNOPSIS
    TTFB delle schede dei centri piu' lente e di due pagine di confronto, 5 misure ciascuna (mediana).
    Da lanciare PRIMA e DOPO le modifiche del 22/11 (SQL sui contenuti e snippet della barra di prenotazione).
.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\misura-centri.ps1
#>
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
$ris = foreach ($p in $pagine) {
  $t = 1..5 | ForEach-Object {
    [double]((curl.exe -s -o NUL -w "%{time_starttransfer}" "https://kosgroup.com/$p") -replace ',', '.')
  } | Sort-Object
  [pscustomobject]@{ Pagina = $p; "TTFB mediano (ms)" = [int]($t[2] * 1000); "min (ms)" = [int]($t[0] * 1000) }
}
$ris | Format-Table -AutoSize
$file = Join-Path ([Environment]::GetFolderPath("Desktop")) ("misura-centri_{0}.csv" -f (Get-Date -Format "yyyyMMdd_HHmm"))
$ris | Export-Csv $file -NoTypeInformation -Delimiter ";" -Encoding UTF8
Write-Host "Salvato: $file"
