<#
.SYNOPSIS
    Verifica la regola nginx del 31/10/2026 su anniazzurri.it:
    - URL "annidati" inventati -> 404 leggero da nginx (pochi KB, non la pagina MODX da ~82 KB)
    - pagine vere, file in /assets/, vecchi URL -> invariati
.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\test-annidati-anniazzurri.ps1
#>
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$base = "https://anniazzurri.it"

$casi = @(
  # gruppo, percorso, codice atteso
  @("annidato", "/news/cure-e-assistenza-domiciliare", "404"),
  @("annidato", "/residenza-anni-azzurri-idice/news/", "404"),
  @("annidato", "/residenza-anni-azzurri-idice/privacy", "404"),
  @("annidato", "/chi-siamo/il-gruppo-kos", "404"),
  @("annidato", "/rsa-in-provincia-di-roma/residenza-anni-azzurri-parco-di-veio", "404"),
  @("annidato", "/privacy/privacy/privacy", "404"),
  @("pagina",   "/", "200"),
  @("pagina",   "/residenza-anni-azzurri-idice", "200"),
  @("pagina",   "/rsa-in-lombardia", "200"),
  @("pagina",   "/chi-siamo/", "200"),
  @("pagina",   "/news/", "200"),
  @("pagina",   "/rsa-in-provincia-di-roma/", "200"),
  @("pagina",   "/alzheimer-cura-e-assistenza", "200"),
  @("pagina",   "/cerca?query=rsa", "200"),
  @("pagina",   "/sitemap.xml", "200"),
  @("file",     "/robots.txt", "200"),
  @("file",     "/assets/doc/strutture/residenza-anni-azzurri-idice/Carta%20dei%20servizi.pdf", "200"),
  @("vecchio",  "/rsa-idice", "301"),
  @("vecchio",  "/residenza-anni-azzurri-villa-olga", "301"),
  @("vecchio",  "/assets/doc/CDS_residenza-anni-azzurri-idice.pdf", "301"),
  @("vecchio",  "/home", "301")
)

$ris = foreach ($c in $casi) {
  $r = curl.exe -s -o NUL --max-time 30 -w "%{http_code};%{size_download};%{time_starttransfer}" "$base$($c[1])"
  $p = $r -split ';'
  $ok = ($p[0] -eq $c[2])
  if ($c[0] -eq "annidato" -and [int]$p[1] -gt 5000) { $ok = $false }   # deve essere il 404 leggero di nginx
  [pscustomobject]@{ Esito = $(if ($ok) { "OK" } else { "KO" }); Gruppo = $c[0]; Codice = $p[0]; Atteso = $c[2]
                     KB = [math]::Round([int]$p[1] / 1024, 1); TTFB_ms = [int]([double]$p[2] * 1000); Percorso = $c[1] }
}
$ris | Format-Table -AutoSize
$ko = @($ris | Where-Object Esito -eq "KO").Count
Write-Host ("Risultato: {0} OK, {1} KO" -f ($ris.Count - $ko), $ko) -ForegroundColor $(if ($ko) { "Red" } else { "Green" })
