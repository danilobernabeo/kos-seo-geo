<#
.SYNOPSIS
    Misura ripetuta del tempo di risposta di una pagina, scomposto per fase.

.DESCRIPTION
    Esegue N richieste a intervalli regolari e registra per ognuna:
    DNS, connessione, negoziazione HTTPS, elaborazione del server e TTFB.
    Alla fine mostra minimo, massimo, media e mediana di ogni fase,
    elenca i campioni anomali e salva tutti i dati in un file CSV.

.PARAMETER Url
    Indirizzo da misurare.

.PARAMETER Campioni
    Numero di misure (predefinito: 10).

.PARAMETER Intervallo
    Secondi tra l'inizio di una misura e la successiva (predefinito: 6).

.PARAMETER Soglia
    TTFB in millisecondi oltre il quale un campione è segnalato come anomalo
    (predefinito: 1000).

.PARAMETER Csv
    Percorso del file CSV. Se omesso, viene creato nella cartella corrente
    con data e ora nel nome.

.EXAMPLE
    .\misura-ttfb.ps1
.EXAMPLE
    .\misura-ttfb.ps1 -Url https://kosgroup.com/it/landing/rm-tac-marche -Campioni 30
.EXAMPLE
    .\misura-ttfb.ps1 -Url https://www.google.com -Campioni 30
#>
param(
    [string]$Url = "https://kosgroup.com/it",
    [int]$Campioni = 10,
    [double]$Intervallo = 6,
    [int]$Soglia = 1000,
    [string]$Csv = ""
)

$inv = [Globalization.CultureInfo]::InvariantCulture
if (-not $Csv) {
    $host_ = ([Uri]$Url).Host -replace '[^a-zA-Z0-9.-]', '_'
    $Csv = "ttfb_{0}_{1}.csv" -f $host_, (Get-Date -Format "yyyyMMdd_HHmmss")
}

Write-Host ""
Write-Host "Misura TTFB" -ForegroundColor Cyan
Write-Host ("URL: {0}   Campioni: {1}   Intervallo: {2} s   Soglia anomalia: {3} ms" -f $Url, $Campioni, $Intervallo, $Soglia)
Write-Host ""
Write-Host (" {0,3}  {1,-8}  {2,6}  {3,6}  {4,6}  {5,6}  {6,7}" -f "N", "Ora", "DNS", "Conn", "HTTPS", "Server", "TTFB")
Write-Host (" " + ("-" * 56))

$risultati = @()
for ($i = 1; $i -le $Campioni; $i++) {
    $inizio = Get-Date
    $raw = curl.exe -s -o NUL -w "%{time_namelookup};%{time_connect};%{time_appconnect};%{time_starttransfer};%{http_code}" $Url
    $p = $raw -split ";"

    if ($p.Count -lt 5 -or $p[4] -eq "000") {
        Write-Host (" {0,3}  {1,-8}  richiesta fallita" -f $i, $inizio.ToString("HH:mm:ss")) -ForegroundColor Red
        $risultati += [pscustomobject]@{ N = $i; Ora = $inizio.ToString("HH:mm:ss"); DNS = $null; Connessione = $null; HTTPS = $null; Server = $null; TTFB = $null; Codice = "errore" }
    }
    else {
        $t = $p[0..3] | ForEach-Object { [double]::Parse($_, $inv) * 1000 }
        $o = [pscustomobject]@{
            N           = $i
            Ora         = $inizio.ToString("HH:mm:ss")
            DNS         = [math]::Round($t[0])
            Connessione = [math]::Round($t[1] - $t[0])
            HTTPS       = [math]::Round($t[2] - $t[1])
            Server      = [math]::Round($t[3] - $t[2])
            TTFB        = [math]::Round($t[3])
            Codice      = $p[4]
        }
        $risultati += $o
        $riga = " {0,3}  {1,-8}  {2,6}  {3,6}  {4,6}  {5,6}  {6,7}" -f $o.N, $o.Ora, $o.DNS, $o.Connessione, $o.HTTPS, $o.Server, $o.TTFB
        if ($o.TTFB -gt $Soglia) { Write-Host ($riga + "  ANOMALO") -ForegroundColor Yellow }
        else { Write-Host $riga }
    }

    if ($i -lt $Campioni) {
        $attesa = $Intervallo - ((Get-Date) - $inizio).TotalSeconds
        if ($attesa -gt 0) { Start-Sleep -Milliseconds ([int]($attesa * 1000)) }
    }
}

function Get-Stat([double[]]$v) {
    $s = $v | Sort-Object
    $n = $s.Count
    $med = if ($n % 2) { $s[[int][math]::Floor($n / 2)] } else { ($s[$n / 2 - 1] + $s[$n / 2]) / 2 }
    [pscustomobject]@{
        Min     = [math]::Round($s[0])
        Max     = [math]::Round($s[-1])
        Media   = [math]::Round(($s | Measure-Object -Average).Average)
        Mediana = [math]::Round($med)
    }
}

$validi = $risultati | Where-Object { $_.TTFB -ne $null }
Write-Host ""
Write-Host "Riepilogo (ms)" -ForegroundColor Cyan
Write-Host ("  {0,-12} {1,7} {2,7} {3,7} {4,8}" -f "Fase", "Min", "Max", "Media", "Mediana")
foreach ($fase in "DNS", "Connessione", "HTTPS", "Server", "TTFB") {
    $st = Get-Stat ($validi | ForEach-Object { $_.$fase })
    Write-Host ("  {0,-12} {1,7} {2,7} {3,7} {4,8}" -f $fase, $st.Min, $st.Max, $st.Media, $st.Mediana)
}

$anomali = @($validi | Where-Object { $_.TTFB -gt $Soglia })
$falliti = @($risultati | Where-Object { $_.TTFB -eq $null })
Write-Host ""
Write-Host ("Campioni anomali (TTFB oltre {0} ms): {1} su {2}" -f $Soglia, $anomali.Count, $Campioni)
foreach ($a in $anomali) {
    $fasi = [ordered]@{ DNS = $a.DNS; Connessione = $a.Connessione; HTTPS = $a.HTTPS; Server = $a.Server }
    $peggiore = ($fasi.GetEnumerator() | Sort-Object Value -Descending | Select-Object -First 1)
    Write-Host ("  n. {0} alle {1}: {2} ms, fase piu lenta: {3} ({4} ms)" -f $a.N, $a.Ora, $a.TTFB, $peggiore.Key, $peggiore.Value) -ForegroundColor Yellow
}
if ($falliti.Count) { Write-Host ("Richieste fallite: {0}" -f $falliti.Count) -ForegroundColor Red }

$risultati | Export-Csv -Path $Csv -NoTypeInformation -Delimiter ";" -Encoding UTF8
Write-Host ""
Write-Host "Dati salvati in: $Csv"
