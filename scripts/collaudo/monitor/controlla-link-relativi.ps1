<#
.SYNOPSIS
    Cerca link relativi (href che non iniziano con /, http, #, mailto:, tel:, javascript:) in tutte le pagine della sitemap.
    Funziona con qualsiasi sito (kosgroup.com, anniazzurri.it...).
.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1
.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1 -Sito https://anniazzurri.it
.EXAMPLE
    # Tutte le pagine del database (non solo la sitemap) + elenco di quelle fuori sitemap
    powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1 -Sito https://anniazzurri.it -Elenco .\uri-anniazzurri.csv
#>
param(
    [string]$Sito = "https://kosgroup.com",
    [string]$Elenco = ""     # facoltativo: CSV/TXT con una colonna "uri" estratta dal database
)

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$Sito = $Sito.TrimEnd('/')
$nomeSito = ([Uri]$Sito).Host -replace '^www\.', ''
$stamp = Get-Date -Format "yyyyMMdd_HHmm"
$desktop = [Environment]::GetFolderPath("Desktop")

$xml = (curl.exe -s --max-time 60 "$Sito/sitemap.xml") -join "`n"
$inSitemap = @([regex]::Matches($xml, '<loc>\s*([^<\s]+)\s*</loc>') | ForEach-Object { $_.Groups[1].Value -replace '&amp;', '&' } | Select-Object -Unique)

if ($Elenco) {
    if (-not (Test-Path $Elenco)) { Write-Host "File non trovato: $Elenco" -ForegroundColor Red; exit 1 }
    # Legge il file qualunque sia il formato dell'export (righe CRLF, LF, solo CR,
    # valori tra virgolette attaccati "a""b""c"...): prende ogni pezzo senza virgolette/a capo/;
    $raw = Get-Content $Elenco -Raw -Encoding UTF8
    $pagine = @([regex]::Matches($raw, '[^"\r\n;\t]+') |
        ForEach-Object { $_.Value.Trim() } |
        Where-Object { $_ -ne '' -and $_ -ne 'uri' } |
        ForEach-Object { "$Sito/" + $_.TrimStart('/') } | Select-Object -Unique)
    if ($pagine.Count -eq 0) { Write-Host "Nessun URI trovato in $Elenco." -ForegroundColor Red; exit 1 }

    # Pagine del database che non compaiono nella sitemap
    $norm = { param($u) [Uri]::UnescapeDataString($u).TrimEnd('/').ToLower() }
    $setSitemap = @{}
    foreach ($u in $inSitemap) { $setSitemap[(& $norm $u)] = 1 }
    $fuori = @($pagine | Where-Object { -not $setSitemap.ContainsKey((& $norm $_)) })
    Write-Host ("Pagine nel database: {0}   nella sitemap: {1}   fuori sitemap: {2}" -f $pagine.Count, $inSitemap.Count, $fuori.Count) -ForegroundColor Cyan
    if ($fuori.Count) {
        $fileFuori = Join-Path $desktop ("fuori-sitemap_{0}_{1}.csv" -f $nomeSito, $stamp)
        $fuori | ForEach-Object { [pscustomobject]@{ Pagina = $_ } } | Export-Csv $fileFuori -NoTypeInformation -Delimiter ";" -Encoding UTF8
        Write-Host "Elenco pagine fuori sitemap: $fileFuori"
    }
    Write-Host ""
} else {
    $pagine = $inSitemap
    if ($pagine.Count -eq 0) { Write-Host "Nessuna pagina trovata nella sitemap." -ForegroundColor Red; exit 1 }
}

$i = 0
$ris = foreach ($p in $pagine) {
    $i++
    Write-Progress -Activity "Link relativi" -Status "$i / $($pagine.Count)  $p" -PercentComplete (100 * $i / $pagine.Count)
    $html = (curl.exe -s --max-time 30 $p) -join "`n"
    # Gli indirizzi email offuscati (mailto: scritto con entita' HTML, es. &#109;&#97;...) vengono decodificati
    # prima del controllo; i template JavaScript (href="${...}") dentro gli script non sono link della pagina.
    $m = @([regex]::Matches($html, '(?i)href="([^"]+)"') | ForEach-Object {
        $v = [System.Net.WebUtility]::HtmlDecode($_.Groups[1].Value)
        if ($v -notmatch '^(?i)(/|https?:|#|mailto:|tel:|javascript:|data:|\?|\[\[|\$\{)') { 'href="' + $v + '"' }
    })
    if ($m.Count) {
        [pscustomobject]@{ Pagina = $p; Link = $m.Count; Esempio = $m[0]; Tutti = (($m | Select-Object -Unique) -join ' ') }
    }
}
Write-Progress -Activity "Link relativi" -Completed

Write-Host ""
$col = if (@($ris).Count -eq 0) { "Green" } else { "Yellow" }
Write-Host ("Pagine con link relativi: {0} su {1}" -f @($ris).Count, $pagine.Count) -ForegroundColor $col

if (@($ris).Count) {
    Write-Host ""
    Write-Host "Link relativi piu' frequenti:" -ForegroundColor Cyan
    $ris | ForEach-Object { $_.Tutti -split ' ' } | Group-Object | Sort-Object Count -Descending |
        Select-Object Count, Name -First 25 | Format-Table -AutoSize -Wrap

    Write-Host "Pagine per sezione:" -ForegroundColor Cyan
    $ris | ForEach-Object { ($_.Pagina -replace '^https?://[^/]+/', '') -replace '^((?:[^/]+/){0,2}[^/]+).*', '$1' } |
        Group-Object | Sort-Object Count -Descending | Select-Object Count, Name | Format-Table -AutoSize

    $file = Join-Path $desktop ("link-relativi_{0}_{1}.csv" -f $nomeSito, $stamp)
    $ris | Select-Object Pagina, Link, Tutti | Export-Csv $file -NoTypeInformation -Delimiter ";" -Encoding UTF8
    Write-Host "Elenco completo: $file"
}
