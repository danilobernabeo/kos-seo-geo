# KOS-6 / AA-19 — Accesso dei crawler AI: risposta dei due siti agli user agent dei bot.
# Uso:  powershell -ExecutionPolicy Bypass -File scripts\checks\verifica-bot-ai.ps1
#
# Le richieste partono dal PC, non dagli IP dei bot: un 200 dimostra che nessuna regola blocca lo
# user agent (robots.txt a parte); il controllo sugli IP ufficiali lo fa bot-ai-log.php sul server.
# Poche richieste, distanziate: whackabot di MODX Cloud sfida gli IP che fanno molte richieste in
# fretta, e un ban colpirebbe anche il PC. Al primo codice diverso da 200 lo script si ferma.
$ErrorActionPreference = "Continue"

$bot = [ordered]@{
  "GPTBot"           = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)"
  "OAI-SearchBot"    = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; OAI-SearchBot/1.3; +https://openai.com/searchbot)"
  "ChatGPT-User"     = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot"
  "ClaudeBot"        = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)"
  "Claude-User"      = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)"
  "Claude-SearchBot" = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-SearchBot/1.0; +Claude-SearchBot@anthropic.com)"
  "PerplexityBot"    = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)"
  "Perplexity-User"  = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)"
  "Bingbot"          = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36"
}
$pagine = @(
  "https://anniazzurri.it/residenza-anni-azzurri-vico-mercati",
  "https://kosgroup.com/it/centri/casa-cima"
)

Write-Host "== robots.txt: gruppi User-agent e Disallow (atteso: solo il gruppo *, nessun blocco dei bot AI)"
foreach ($s in "https://anniazzurri.it", "https://kosgroup.com") {
  Write-Host "  $s/robots.txt"
  (curl.exe -s "$s/robots.txt") -split "`n" | Where-Object { $_ -match '^(User-agent|Disallow|Allow|Crawl-delay)' } |
    ForEach-Object { Write-Host "       $($_.Trim())" }
}

Write-Host "`n== risposta per user agent (codice, byte, tempo, JSON-LD nell'HTML servito)"
Write-Host ("  {0,-17} {1,-58} {2,4} {3,8} {4,7} {5}" -f "bot", "pagina", "cod", "byte", "sec", "JSON-LD")
$fermo = $false
foreach ($nome in $bot.Keys) {
  foreach ($u in $pagine) {
    $tmp = [System.IO.Path]::GetTempFileName()
    $out = curl.exe -s -o $tmp -A $bot[$nome] -w "%{http_code} %{size_download} %{time_total}" $u
    $cod, $byte, $sec = $out -split ' '
    $html = Get-Content $tmp -Raw -Encoding UTF8
    Remove-Item $tmp
    $ld = if ($html -match 'application/ld\+json') { "si" } else { "no" }
    $col = if ($cod -eq "200") { "Green" } else { "Red" }
    Write-Host ("  {0,-17} {1,-58} {2,4} {3,8} {4,7} {5}" -f $nome, $u.Replace("https://", ""), $cod, $byte, $sec, $ld) -ForegroundColor $col
    if ($cod -ne "200") { $fermo = $true; break }
    Start-Sleep -Milliseconds 1500
  }
  if ($fermo) { Write-Host "  FERMO al primo codice diverso da 200: incollare l'output e non rilanciare subito." -ForegroundColor Yellow; break }
}

Write-Host "`n== confronto con un browser normale (atteso: stessi byte, a meno di parti dinamiche)"
foreach ($u in $pagine) {
  $out = curl.exe -s -o NUL -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36" -w "%{http_code} %{size_download}" $u
  Write-Host "  browser  $($u.Replace('https://', ''))  $out"
}
