<#
  Linea C - Archivio dei dati Yext prima della dismissione (fine gennaio 2027). Solo lettura. Dal computer di Danilo.
  Chiave: app «Export statistiche profili» con Entità, Analytics e Listings in sola lettura, in $env:YEXT_KEY.

  Uso:
    $env:YEXT_KEY = Read-Host "Chiave API Yext"
    & "$R\scripts\yext\esporta-archivio.ps1" -Uscita "$env:USERPROFILE\Downloads\yext-statistiche\archivio"

  Produce (JSON, una sezione non blocca le altre):
    entita-completo.json      tutte le entità con tutti i campi (sedi, FAQ, servizi, convenzioni, news, ...)
    profili-lingua.json       versioni in altre lingue delle entità (se esistono)
    listings.json             stato della scheda per sede e portale (Google, Bing, Apple, Facebook...), con URL
    portali.json              elenco dei portali gestiti
    duplicati.json            schede doppie individuate e soppresse da Yext
  Contengono dati di contatto e testi pubblici; restano fuori dal repository.
#>
param([Parameter(Mandatory = $true)][string]$Uscita, [string]$Versione = '20231101')
$ErrorActionPreference = 'Stop'
if (-not $env:YEXT_KEY) { throw 'Manca la chiave: $env:YEXT_KEY = Read-Host "Chiave API Yext"' }
New-Item -ItemType Directory -Force -Path $Uscita | Out-Null
$base = 'https://api.yext.com/v2/accounts/me'
$chiave = "v=$Versione&api_key=$($env:YEXT_KEY)"

function Chiama($u) {
    for ($t = 1; $t -le 3; $t++) {
        try { return Invoke-RestMethod -Uri $u }
        catch { if ($t -eq 3) { throw }; Start-Sleep -Seconds (10 * $t) }
    }
}
function Salva($nome, $dati) {
    $p = Join-Path $Uscita $nome
    ConvertTo-Json -InputObject @($dati) -Depth 60 | Out-File -FilePath $p -Encoding UTF8
    Write-Host ("== {0,-22} {1,6} elementi" -f $nome, @($dati).Count)
}
# Paginazione con pageToken (entità, profili)
function PerToken($percorso, $campo) {
    $tutti = @(); $token = ''
    do {
        $u = "$base/$percorso`?$chiave&limit=50"
        if ($token) { $u += "&pageToken=$token" }
        $r = Chiama $u
        $tutti += @($r.response.$campo)
        $token = $r.response.pageToken
        Start-Sleep -Milliseconds 400
    } while ($token)
    return $tutti
}
# Paginazione con offset (listings)
function PerOffset($percorso, $campo) {
    $tutti = @(); $offset = 0
    do {
        $r = Chiama "$base/$percorso`?$chiave&limit=100&offset=$offset"
        $pagina = @($r.response.$campo)
        $tutti += $pagina
        $offset += 100
        Start-Sleep -Milliseconds 400
    } while ($pagina.Count -eq 100)
    return $tutti
}

$sezioni = @(
    @{ nome = 'entita-completo.json'; f = { PerToken 'entities' 'entities' } },
    @{ nome = 'profili-lingua.json';  f = { PerToken 'entityprofiles' 'profileLists' } },
    @{ nome = 'listings.json';        f = { PerOffset 'listings/listings' 'listings' } },
    @{ nome = 'portali.json';         f = { (Chiama "$base/listings/publishers?$chiave").response.publishers } },
    @{ nome = 'duplicati.json';       f = { PerOffset 'listings/duplicates' 'duplicates' } }
)
foreach ($s in $sezioni) {
    try { Salva $s.nome (& $s.f) }
    catch { Write-Host ("!! {0}: {1}" -f $s.nome, $_.Exception.Message) -ForegroundColor Yellow }
}
