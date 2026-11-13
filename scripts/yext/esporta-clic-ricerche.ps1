<#
  Linea C - Clic per ricerca nelle barre Yext dei siti: per mese, sito e ricerca, la pagina aperta e quante volte.
  Serve come riferimento per giudicare i risultati di Vertex (piano-prova-vertex.md). Solo lettura.
  Chiave in $env:YEXT_KEY (Analytics in sola lettura).

  Uso:
    $env:YEXT_KEY = Read-Host "Chiave API Yext"
    & "$R\scripts\yext\esporta-clic-ricerche.ps1" -Da 2020-11-01 -A 2026-11-11 -Uscita "$env:USERPROFILE\Downloads\yext-statistiche"

  Produce clic-ricerche.csv (mese, experience, ricerca, url, clic). Testo libero: resta fuori dal repository.
#>
param(
    [Parameter(Mandatory = $true)][string]$Da,
    [Parameter(Mandatory = $true)][string]$A,
    [Parameter(Mandatory = $true)][string]$Uscita,
    [string]$Versione = '20231101'
)
$ErrorActionPreference = 'Stop'
if (-not $env:YEXT_KEY) { throw 'Manca la chiave: $env:YEXT_KEY = Read-Host "Chiave API Yext"' }
New-Item -ItemType Directory -Force -Path $Uscita | Out-Null
$url = "https://api.yext.com/v2/accounts/me/analytics/reports?v=$Versione&api_key=$($env:YEXT_KEY)"
$percorso = Join-Path $Uscita 'clic-ricerche.csv'
if (Test-Path $percorso) { Remove-Item $percorso }

# Yext rinomina le dimensioni nella risposta (es. ANSWERS_SEARCH_TERM -> SEARCH_SEARCH_TERM): si cerca per suffisso
function Campo($riga, $suffisso) {
    $p = $riga.PSObject.Properties | Where-Object { $_.Name -like "*$suffisso" } | Select-Object -First 1
    if ($p) { $p.Value } else { $null }
}

$inizio = [datetime]::ParseExact($Da, 'yyyy-MM-dd', $null)
$fine   = [datetime]::ParseExact($A,  'yyyy-MM-dd', $null)
$totale = 0; $primo = $true
$m = Get-Date -Year $inizio.Year -Month $inizio.Month -Day 1
while ($m -le $fine) {
    $s = if ($m -lt $inizio) { $inizio } else { $m }
    $e = $m.AddMonths(1).AddDays(-1); if ($e -gt $fine) { $e = $fine }
    $corpo = @{ metrics = @('ANSWERS_CLICKS');
                dimensions = @('MONTHS', 'ANSWERS_EXPERIENCE_KEY', 'ANSWERS_SEARCH_TERM', 'ANSWERS_CLICK_URL');
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
    if ($primo -and $righe.Count -gt 0) {
        Write-Host ('Colonne restituite: ' + (($righe[0].PSObject.Properties.Name) -join ', '))
        $primo = $false
    }
    $righe = @($righe | Where-Object { [int](Campo $_ 'CLICKS') -gt 0 })
    if ($righe.Count -gt 0) {
        $righe | ForEach-Object {
            [pscustomobject]@{
                mese = $_.MONTHS; experience = (Campo $_ 'EXPERIENCE_KEY'); ricerca = (Campo $_ 'SEARCH_TERM')
                url = (Campo $_ 'CLICK_URL'); clic = [int](Campo $_ 'CLICKS')
            }
        } | Export-Csv -Path $percorso -NoTypeInformation -Encoding UTF8 -Append
        $totale += $righe.Count
    }
    Write-Host ("{0}  {1,6} righe" -f $s.ToString('yyyy-MM'), $righe.Count)
    Start-Sleep -Milliseconds 800
    $m = $m.AddMonths(1)
}
Write-Host ("== {0} righe in {1}" -f $totale, $percorso)
