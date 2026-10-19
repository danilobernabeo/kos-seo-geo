# Verifica dopo il rilascio in produzione (sprint 1): dato strutturato, H1, versione CSS/JS, cache.
# Uso:  powershell -ExecutionPolicy Bypass -File scripts\checks\verifica-rilascio-sprint1.ps1
$ErrorActionPreference = "Continue"
function Riga($ok, $testo) { if ($ok) { Write-Host "  OK   $testo" -ForegroundColor Green } else { Write-Host "  KO   $testo" -ForegroundColor Red } }

Write-Host "== anniazzurri.it: dato strutturato (atteso: 1 blocco; 2 sportello; 3 lrsa-volpiano)"
$attesi = @{ "/" = 1; "/residenza-anni-azzurri-vico-mercati" = 1; "/rsa-in-lombardia" = 1; "/rsa-in-provincia-di-monza-e-brianza" = 1;
             "/cosa-sono-le-rsa" = 1; "/sportello-sociale-orientamento-per-assistenza-anziani" = 2; "/lrsa-volpiano" = 3 }
foreach ($p in $attesi.Keys) {
  $h = curl.exe -s "https://anniazzurri.it$p"
  $n = [regex]::Matches($h, 'application/ld\+json').Count
  $graph = $h -match '"@graph"'
  Riga (($n -eq $attesi[$p]) -and $graph) "$p  JSON-LD: $n"
}

Write-Host "== anniazzurri.it: briciole di Vico Mercati (4 livelli)"
$h = curl.exe -s "https://anniazzurri.it/residenza-anni-azzurri-vico-mercati"
$liv = [regex]::Matches($h, '"@type":"ListItem"').Count
Riga ($liv -eq 4) "BreadcrumbList con $liv livelli"
Riga ($h -match 'href="/rsa-in-lombardia"') "briciole visibili con link a percorso assoluto"
Riga (-not ($h -match '"dateModified":"1970')) "dateModified valida"

Write-Host "== anniazzurri.it: H1 della home"
$h = curl.exe -s "https://anniazzurri.it/"
$h1 = [regex]::Matches($h, '<h1\b')
Riga ($h1.Count -eq 1) "un solo H1 ($($h1.Count))"
Riga ($h -match 'RSA e case di riposo in Italia</h1>') "testo H1"

Write-Host "== versione automatica di CSS e JS (?v=)"
foreach ($u in "https://anniazzurri.it/", "https://kosgroup.com/it") {
  $h = curl.exe -s $u
  $n = [regex]::Matches($h, '(?:href|src)="[^"]+\.(?:css|js)\?v=\d+"').Count
  Riga ($n -gt 0) "$u  file con ?v=: $n"
}

Write-Host "== cache (atteso: CSS/JS 15552000 dopo le nuove regole web, immagini 15552000, font 31536000)"
foreach ($u in "https://anniazzurri.it/dist/css/app.css", "https://kosgroup.com/assets/css/bootstrap.min.css",
              "https://anniazzurri.it/assets/logo-anni.svg", "https://anniazzurri.it/dist/assets/fonts/OpenSans-Bold.woff2") {
  $cc = (curl.exe -sI $u | Select-String "^[Cc]ache-[Cc]ontrol").ToString()
  Write-Host "       $u  $cc"
}

Write-Host "== pagine principali rispondono 200"
foreach ($u in "https://anniazzurri.it/", "https://anniazzurri.it/news/", "https://kosgroup.com/it", "https://kosgroup.com/it/centri/casa-cima") {
  $c = curl.exe -s -o NUL -w "%{http_code}" $u
  Riga ($c -eq "200") "$u  $c"
}
