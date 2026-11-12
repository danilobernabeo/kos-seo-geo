<#
  Linea C - Esportazione delle ricerche fatte nelle barre di ricerca Yext dei siti (Yext Search / Answers),
  prima della dismissione della barra (entro il 18/12/2026). Solo lettura. Dal computer di Danilo.

  Chiave: la stessa di esporta-statistiche.ps1, in $env:YEXT_KEY (mai in un file).

  Uso:
    $env:YEXT_KEY = Read-Host "Chiave API Yext (Analytics)"
    & "$R\scripts\yext\esporta-ricerche-sito.ps1" -Da 2020-01-01 -A 2026-11-11 -Uscita "$env:USERPROFILE\Downloads\yext-statistiche"

  Scarica mese per mese ricerche-sito.csv: per giorno, experience (sito), ricerca normalizzata e presenza di risultati,
  il numero di ricerche e di ricerche con clic. Nessuna dimensione sull'utente (città, posizione, sessione).
  Le ricerche sono testo libero e possono contenere dati personali: il file resta fuori dal repository.
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
$percorso = Join-Path $Uscita 'ricerche-sito.csv'
if (Test-Path $percorso) { Remove-Item $percorso }

$inizio = [datetime]::ParseExact($Da, 'yyyy-MM-dd', $null)
$fine   = [datetime]::ParseExact($A,  'yyyy-MM-dd', $null)
$totale = 0
$m = Get-Date -Year $inizio.Year -Month $inizio.Month -Day 1
while ($m -le $fine) {
    $s = if ($m -lt $inizio) { $inizio } else { $m }
    $e = $m.AddMonths(1).AddDays(-1); if ($e -gt $fine) { $e = $fine }
    $corpo = @{ metrics = @('ANSWERS_SEARCHES', 'ANSWERS_SEARCHES_WITH_CLICKS');
                dimensions = @('DAYS', 'ANSWERS_EXPERIENCE_KEY', 'ANSWERS_SEARCH_TERM', 'ANSWERS_HAS_KG_RESULTS');
                filters = @{ startDate = $s.ToString('yyyy-MM-dd'); endDate = $e.ToString('yyyy-MM-dd') } } | ConvertTo-Json -Depth 5
    $tentativi = 0
    while ($true) {
        try { $risp = Invoke-RestMethod -Method Post -Uri $url -ContentType 'application/json' -Body $corpo; break }
        catch {
            $tentativi++
            if ($tentativi -ge 3) { throw "Errore su $($s.ToString('yyyy-MM')): $($_.Exception.Message)" }
            Start-Sleep -Seconds (10 * $tentativi)
        }
    }
    if ($risp.meta.errors -and $risp.meta.errors.Count -gt 0) {
        throw "Yext ha risposto con errori su $($s.ToString('yyyy-MM')): $($risp.meta.errors | ConvertTo-Json -Compress)"
    }
    $righe = @($risp.response.data)
    if ($righe.Count -gt 0) {
        # Yext restituisce le dimensioni con il prefisso SEARCH_ (es. SEARCH_SEARCH_TERM): colonne fissate qui
        $righe | Select-Object @{n='data';e={$_.DAYS}}, @{n='experience';e={$_.SEARCH_EXPERIENCE_KEY}},
                               @{n='ricerca';e={$_.SEARCH_SEARCH_TERM}}, @{n='risultati';e={$_.SEARCH_HAS_PLATFORM_RESULTS -eq 'Yes'}},
                               @{n='ricerche';e={$_.ANSWERS_SEARCHES}}, @{n='con_clic';e={$_.ANSWERS_SEARCHES_WITH_CLICKS}} |
            Export-Csv -Path $percorso -NoTypeInformation -Encoding UTF8 -Append
        $totale += $righe.Count
    }
    Write-Host ("{0}  {1,6} righe" -f $s.ToString('yyyy-MM'), $righe.Count)
    Start-Sleep -Milliseconds 800
    $m = $m.AddMonths(1)
}
Write-Host ("== {0} righe in {1}" -f $totale, $percorso)
