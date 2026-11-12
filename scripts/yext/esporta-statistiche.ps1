<#
  Linea C - Esportazione delle statistiche dei profili Google dall'API Analytics di Yext, prima della dismissione
  di Yext (fine gennaio 2027). Solo lettura. Da lanciare dal computer di Danilo (l'API Yext non è raggiungibile
  dall'ambiente di Claude).

  Chiave: app Yext «Export statistiche profili», permesso Analytics in sola lettura, in $env:YEXT_KEY (mai in un file).

  Uso:
    $env:YEXT_KEY = Read-Host "Chiave API Yext (Analytics)"
    & "$R\scripts\yext\esporta-statistiche.ps1" -Da 2025-05-01 -A 2026-11-11 -Uscita "$env:USERPROFILE\Downloads\yext-statistiche"

  Scarica mese per mese, per entità Yext (= codice negozio dei profili Google = store.id RetailTune):
    impressioni.csv   LISTINGS_IMPRESSIONS per giorno, entità, origine (APP: Maps / Search) e dispositivo
    azioni.csv        LISTINGS_ACTIONS per giorno, entità e tipo di azione (Tap to Call, Website, Driving Directions...)
    ricerche.csv      LISTINGS_GOOGLE_SEARCH_TERM_IMPRESSIONS per mese, entità e parola cercata
  Ogni file ha in testa le colonne restituite da Yext; i file si sovrascrivono a ogni esecuzione.
#>
param(
    [Parameter(Mandatory = $true)][string]$Da,
    [Parameter(Mandatory = $true)][string]$A,
    [Parameter(Mandatory = $true)][string]$Uscita,
    [string]$Versione = '20231101'
)
$ErrorActionPreference = 'Stop'
if (-not $env:YEXT_KEY) { throw 'Manca la chiave: $env:YEXT_KEY = Read-Host "Chiave API Yext (Analytics)"' }
New-Item -ItemType Directory -Force -Path $Uscita | Out-Null
$url = "https://api.yext.com/v2/accounts/me/analytics/reports?v=$Versione&api_key=$($env:YEXT_KEY)"

$report = @(
    @{ file = 'impressioni.csv'; metrica = 'LISTINGS_IMPRESSIONS'; dimensioni = @('DAYS', 'ENTITY_IDS', 'APP', 'DEVICE') },
    @{ file = 'azioni.csv';      metrica = 'LISTINGS_ACTIONS';     dimensioni = @('DAYS', 'ENTITY_IDS', 'ACTION') },
    @{ file = 'ricerche.csv';    metrica = 'LISTINGS_GOOGLE_SEARCH_TERM_IMPRESSIONS'; dimensioni = @('MONTHS', 'ENTITY_IDS', 'RAW_SEARCH_TERM') }
)

$inizio = [datetime]::ParseExact($Da, 'yyyy-MM-dd', $null)
$fine   = [datetime]::ParseExact($A,  'yyyy-MM-dd', $null)

foreach ($r in $report) {
    $percorso = Join-Path $Uscita $r.file
    if (Test-Path $percorso) { Remove-Item $percorso }
    $totale = 0
    $m = Get-Date -Year $inizio.Year -Month $inizio.Month -Day 1
    while ($m -le $fine) {
        $s = if ($m -lt $inizio) { $inizio } else { $m }
        $e = $m.AddMonths(1).AddDays(-1); if ($e -gt $fine) { $e = $fine }
        $corpo = @{ metrics = @($r.metrica); dimensions = $r.dimensioni;
                    filters = @{ startDate = $s.ToString('yyyy-MM-dd'); endDate = $e.ToString('yyyy-MM-dd') } } | ConvertTo-Json -Depth 5
        $tentativi = 0
        while ($true) {
            try { $risp = Invoke-RestMethod -Method Post -Uri $url -ContentType 'application/json' -Body $corpo; break }
            catch {
                $tentativi++
                if ($tentativi -ge 3) { throw "Errore su $($r.metrica) $($s.ToString('yyyy-MM')): $($_.Exception.Message)" }
                Start-Sleep -Seconds (10 * $tentativi)
            }
        }
        if ($risp.meta.errors -and $risp.meta.errors.Count -gt 0) {
            throw "Yext ha risposto con errori su $($r.metrica) $($s.ToString('yyyy-MM')): $($risp.meta.errors | ConvertTo-Json -Compress)"
        }
        if ($null -eq $risp.response.data) {
            throw "Risposta senza dati (report in differita?) su $($r.metrica) $($s.ToString('yyyy-MM')): $($risp.response | ConvertTo-Json -Compress -Depth 4)"
        }
        $righe = @($risp.response.data)
        if ($righe.Count -gt 0) {
            $righe | Export-Csv -Path $percorso -NoTypeInformation -Encoding UTF8 -Append
            $totale += $righe.Count
        }
        Write-Host ("{0,-42} {1}  {2,7} righe" -f $r.metrica, $s.ToString('yyyy-MM'), $righe.Count)
        Start-Sleep -Milliseconds 800
        $m = $m.AddMonths(1)
    }
    Write-Host ("== {0}: {1} righe in {2}" -f $r.metrica, $totale, $percorso)
}
