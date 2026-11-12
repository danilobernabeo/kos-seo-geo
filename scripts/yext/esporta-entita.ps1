<#
  Linea C - Tabella di corrispondenza delle entità Yext: identificativo interno (meta.uid, quello delle statistiche
  in ENTITY_IDS) → ID entità (codice negozio dei profili Google = store.id RetailTune) → nome, tipo, città.
  Solo lettura. Chiave con permesso di lettura sulle entità (es. app «getSchemaData»), in $env:YEXT_KEY_ENT.

  Uso:
    $env:YEXT_KEY_ENT = Read-Host "Chiave API Yext (entità)"
    & "$R\scripts\yext\esporta-entita.ps1" -Uscita "$env:USERPROFILE\Downloads\yext-statistiche\entita.csv"
#>
param([Parameter(Mandatory = $true)][string]$Uscita, [string]$Versione = '20231101')
$ErrorActionPreference = 'Stop'
if (-not $env:YEXT_KEY_ENT) { throw 'Manca la chiave: $env:YEXT_KEY_ENT = Read-Host "Chiave API Yext (entità)"' }
$righe = @(); $token = ''
do {
    $u = "https://api.yext.com/v2/accounts/me/entities?v=$Versione&api_key=$($env:YEXT_KEY_ENT)&limit=50&fields=name,address,meta,closed"
    if ($token) { $u += "&pageToken=$token" }
    $r = Invoke-RestMethod -Uri $u
    foreach ($e in $r.response.entities) {
        $righe += [pscustomobject]@{
            uid = $e.meta.uid; entita = $e.meta.id; tipo = $e.meta.entityType; nome = $e.name
            citta = $e.address.city; provincia = $e.address.region; chiusa = [bool]$e.closed
        }
    }
    $token = $r.response.pageToken
    Start-Sleep -Milliseconds 400
} while ($token)
$righe | Export-Csv -Path $Uscita -NoTypeInformation -Encoding UTF8
Write-Host ("== {0} entità in {1}" -f $righe.Count, $Uscita)
