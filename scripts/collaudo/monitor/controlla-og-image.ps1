<#
.SYNOPSIS
    Controlla l'immagine di anteprima social (og:image) di tutte le pagine della sitemap di un sito.

.DESCRIPTION
    Per ogni pagina della sitemap:
      - verifica che la pagina risponda 200 (altrimenti la segnala come redirect o errore);
      - cerca il tag <meta property="og:image" content="..."> (in qualsiasi ordine degli attributi);
      - verifica che l'immagine risponda 200.
    Gestisce anche le sitemap indice (sitemap che rimandano ad altre sitemap) e, se /sitemap.xml
    non esiste, cerca l'indirizzo della sitemap in robots.txt.
    Salva le pagine con problemi in un CSV sul Desktop e, se trova il CSV del controllo precedente
    dello stesso sito, mostra quante pagine sono state sistemate.

.EXAMPLE
    .\controlla-og-image.ps1 -Sito https://kosgroup.com
.EXAMPLE
    .\controlla-og-image.ps1 -Sito https://anniazzurri.it
.EXAMPLE
    .\controlla-og-image.ps1 -Sito https://kosgroup.com -Filtro "/medici/"
    (controlla solo le pagine il cui indirizzo contiene il testo indicato)
#>
param(
    [Parameter(Mandatory = $true)][string]$Sito,
    [string]$Sitemap = "",
    [string]$Filtro = ""
)
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8   # legge correttamente accenti e caratteri speciali

$Sito = $Sito.TrimEnd('/')
$nomeSito = ([Uri]$Sito).Host -replace '^www\.', ''
$desk = [Environment]::GetFolderPath("Desktop")

function Get-Url([string]$u) { (& curl.exe -s -L --max-time 30 $u) -join "`n" }

# ---------------------------------------------------------------- sitemap
if (-not $Sitemap) {
    $Sitemap = "$Sito/sitemap.xml"
    $test = Get-Url $Sitemap
    if ($test -notmatch '<(urlset|sitemapindex)') {
        $robots = Get-Url "$Sito/robots.txt"
        $m = [regex]::Match($robots, '(?im)^\s*sitemap:\s*(\S+)')
        if ($m.Success) { $Sitemap = $m.Groups[1].Value }
    }
}
Write-Host "Sitemap: $Sitemap"

$pages = New-Object System.Collections.Generic.List[string]
$daLeggere = New-Object System.Collections.Generic.Queue[string]
$daLeggere.Enqueue($Sitemap)
$lette = 0
while ($daLeggere.Count -gt 0 -and $lette -lt 50) {
    $xml = Get-Url $daLeggere.Dequeue(); $lette++
    if ($xml -match '<sitemapindex') {
        [regex]::Matches($xml, '<sitemap>\s*<loc>\s*([^<\s]+)\s*</loc>') | ForEach-Object { $daLeggere.Enqueue($_.Groups[1].Value) }
    } else {
        [regex]::Matches($xml, '<url>\s*<loc>\s*([^<\s]+)\s*</loc>') | ForEach-Object { $pages.Add($_.Groups[1].Value) }
        if ($pages.Count -eq 0) { [regex]::Matches($xml, '<loc>\s*([^<\s]+)\s*</loc>') | ForEach-Object { $pages.Add($_.Groups[1].Value) } }
    }
}
$pages = @($pages | ForEach-Object { $_ -replace '&amp;', '&' } | Select-Object -Unique)
if ($Filtro) { $pages = @($pages | Where-Object { $_ -like "*$Filtro*" }) }
Write-Host ("Pagine nella sitemap{0}: {1}" -f $(if ($Filtro) { " (filtro '$Filtro')" } else { "" }), $pages.Count)
if ($pages.Count -eq 0) { Write-Host "Nessuna pagina trovata: indica la sitemap con -Sitemap <url>." -ForegroundColor Red; exit 1 }

# ---------------------------------------------------------------- controllo
$i = 0
$ris = foreach ($p in $pages) {
    $i++
    Write-Progress -Activity "Controllo og:image" -Status "$i / $($pages.Count)  $p" -PercentComplete (100 * $i / $pages.Count)
    $resp = (& curl.exe -s --max-time 30 -w "`n__CODE__%{http_code}" $p) -join "`n"
    $codePag = [regex]::Match($resp, '__CODE__(\d+)$').Groups[1].Value
    $img = ""
    if ($codePag -eq "200") {
        $m = [regex]::Match($resp, '<meta[^>]+property\s*=\s*["'']og:image["''][^>]*>')
        if ($m.Success) { $img = [regex]::Match($m.Value, 'content\s*=\s*["'']([^"'']*)["'']').Groups[1].Value.Trim() }
    }
    if ($img -and $img -notmatch '^https?://') { $img = [Uri]::new([Uri]$p, $img).AbsoluteUri }   # og:image relativo
    $esito = if ($codePag -ne "200") { "pagina $codePag (redirect o errore)" }
             elseif (-not $img) { "manca il tag" }
             elseif ($img -match '\s') { "URL immagine con spazi (non codificato)" }
             elseif ($img -match '[^\x21-\x7E]') { "URL immagine con caratteri accentati (non codificato)" }
             else { & curl.exe -s -o NUL -w "%{http_code}" --max-time 30 $img }
    [pscustomobject]@{ Pagina = $p; Immagine = $img; Esito = $esito }
}
Write-Progress -Activity "Controllo og:image" -Completed

# ---------------------------------------------------------------- risultati
$ko = @($ris | Where-Object { $_.Esito -ne "200" })
$suff = if ($Filtro) { "_" + ($Filtro -replace '[^\w-]', '') } else { "" }
$file = Join-Path $desk ("og-image_{0}{1}_{2}.csv" -f $nomeSito, $suff, (Get-Date -Format "yyyyMMdd_HHmm"))
$ko | Export-Csv $file -NoTypeInformation -Delimiter ";" -Encoding UTF8

Write-Host ""
Write-Host ("Anteprime funzionanti: {0} su {1}" -f @($ris | Where-Object Esito -eq "200").Count, $ris.Count) -ForegroundColor Cyan
$ko | Group-Object Esito | Select-Object Count, Name | Format-Table -AutoSize

# Confronto con il controllo precedente dello stesso sito
$precedenti = @(Get-ChildItem $desk -Filter ("og-image_{0}_*.csv" -f $nomeSito) | Where-Object { $_.FullName -ne $file -and $_.Name -match ('^og-image_{0}_\d' -f [regex]::Escape($nomeSito)) } | Sort-Object LastWriteTime)
if ($nomeSito -eq "anniazzurri.it" -and (Test-Path (Join-Path $desk "og-image-mancanti.csv"))) {
    $precedenti = @(Get-Item (Join-Path $desk "og-image-mancanti.csv")) + $precedenti
}
if ($precedenti.Count -gt 0) {
    $ultimo = $precedenti[-1]
    $prima = @((Import-Csv $ultimo.FullName -Delimiter ";").Pagina | Where-Object { (-not $Filtro) -or ($_ -like "*$Filtro*") })
    $ora = @($ko.Pagina)
    Write-Host ("Rispetto al controllo precedente ({0}):" -f $ultimo.Name)
    Write-Host ("  sistemate:      {0}" -f @($prima | Where-Object { $_ -notin $ora }).Count)
    Write-Host ("  ancora da fare: {0}" -f @($prima | Where-Object { $_ -in $ora }).Count)
    Write-Host ("  nuovi problemi: {0}" -f @($ora | Where-Object { $_ -notin $prima }).Count)
}
Write-Host ""
Write-Host "Elenco delle pagine con problemi: $file"
