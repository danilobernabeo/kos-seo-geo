<?php
/**
 * KOS-6 / AA-19 — Accesso dei crawler AI: cosa ha fatto il server con gli IP ufficiali dei bot.
 *
 * Scarica gli elenchi di IP pubblicati da OpenAI, Anthropic e Perplexity (con una copia di riserva
 * del 20/10/2026 se il cloud non esce su internet), cerca quegli IP nel log degli errori di nginx
 * e conta cosa è successo: sfide e ban di whackabot, limiti di frequenza, accessi negati, 404.
 * Un bot che non compare mai tra i blocchi non è stato bloccato dal server.
 *
 * Uso sul cloud (produzione):  php bot-ai-log.php [/log/nginx_error.log]
 * Legge il log con grep (veloce anche su file di diversi GB); PHP confronta solo gli IP unici.
 */

$log = $argv[1] ?? '/log/nginx_error.log';

$fonti = [
    'OpenAI GPTBot'         => 'https://openai.com/gptbot.json',
    'OpenAI OAI-SearchBot'  => 'https://openai.com/searchbot.json',
    'OpenAI ChatGPT-User'   => 'https://openai.com/chatgpt-user.json',
    'Anthropic (ClaudeBot, Claude-User, Claude-SearchBot)' => 'https://claude.com/crawling/bots.json',
    'PerplexityBot'         => 'https://www.perplexity.ai/perplexitybot.json',
    'Perplexity-User'       => 'https://www.perplexity.ai/perplexity-user.json',
];

// Copia di riserva (elenchi letti il 20/10/2026). Usata solo se il download non riesce.
$riserva = [
    'Anthropic (ClaudeBot, Claude-User, Claude-SearchBot)' => [
        '216.73.216.0/22', '34.162.230.222/32', '34.162.244.71/32', '34.162.191.81/32', '34.150.241.79/32',
        '34.85.172.162/32', '35.245.175.129/32', '34.182.222.37/32', '34.186.108.163/32', '35.245.89.239/32',
        '34.182.161.143/32', '34.182.218.27/32', '34.182.220.85/32', '34.11.34.31/32', '34.182.140.95/32',
        '136.107.176.208/32', '34.182.226.151/32', '34.182.226.221/32', '35.221.29.174/32', '34.182.225.167/32',
        '40.124.101.48/28', '20.102.46.224/28', '20.64.57.208/28', '16.58.26.69/32', '18.225.238.228/32',
        '18.227.226.255/32',
    ],
    'PerplexityBot' => [
        '107.20.236.150/32', '3.224.62.45/32', '18.210.92.235/32', '3.222.232.239/32', '3.211.124.183/32',
        '3.231.139.107/32', '18.97.1.228/30', '18.97.9.96/29',
    ],
];

function scarica(string $url): ?array
{
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'kos-seo-geo verifica bot']]);
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        return null;
    }
    $dati = json_decode($json, true);
    if (!is_array($dati) || empty($dati['prefixes'])) {
        return null;
    }
    $reti = [];
    foreach ($dati['prefixes'] as $p) {
        if (!empty($p['ipv4Prefix'])) {
            $reti[] = $p['ipv4Prefix'];
        }
    }
    return ['reti' => $reti, 'data' => $dati['creationTime'] ?? '?'];
}

function nellaRete(string $ip, string $cidr): bool
{
    [$rete, $bit] = explode('/', $cidr) + [1 => 32];
    $maschera = $bit == 0 ? 0 : (~0 << (32 - (int)$bit)) & 0xFFFFFFFF;
    return (ip2long($ip) & $maschera) === (ip2long($rete) & $maschera);
}

echo "== ELENCHI UFFICIALI DEGLI IP\n";
$elenchi = [];
foreach ($fonti as $bot => $url) {
    $e = scarica($url);
    if ($e) {
        printf("  %-55s %3d reti, aggiornato %s (%s)\n", $bot, count($e['reti']), $e['data'], $url);
        $elenchi[$bot] = $e['reti'];
    } elseif (isset($riserva[$bot])) {
        printf("  %-55s %3d reti, COPIA DI RISERVA del 20/10/2026 (download non riuscito)\n", $bot, count($riserva[$bot]));
        $elenchi[$bot] = $riserva[$bot];
    } else {
        printf("  %-55s NON DISPONIBILE (download non riuscito, nessuna riserva)\n", $bot);
    }
}

echo "\n== LOG\n";
if (!is_readable($log)) {
    exit("  $log non leggibile\n");
}
foreach (glob(dirname($log) . '/*') as $f) {
    printf("  %-40s %10.1f MB  %s\n", basename($f), filesize($f) / 1048576, date('Y-m-d H:i', filemtime($f)));
}
$prima = trim(substr((string)shell_exec('head -1 ' . escapeshellarg($log)), 0, 19));
$ultima = trim(substr((string)shell_exec('tail -1 ' . escapeshellarg($log)), 0, 19));
echo "  periodo: $prima → $ultima\n";

echo "\n== IP UNICI NEL LOG (grep, può richiedere qualche minuto)\n";
$righe = shell_exec("grep -oE 'client: [0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+' " . escapeshellarg($log) . " | sort | uniq -c");
$trovati = [];   // ip => [bot, righe]
foreach (explode("\n", trim((string)$righe)) as $r) {
    if (!preg_match('/^\s*(\d+) client: ([\d.]+)$/', $r, $m)) {
        continue;
    }
    foreach ($elenchi as $bot => $reti) {
        foreach ($reti as $cidr) {
            if (nellaRete($m[2], $cidr)) {
                $trovati[$m[2]] = [$bot, (int)$m[1]];
                break 2;
            }
        }
    }
}

$tipi = [
    'whackabot (sfida o ban)' => '/whackabot/i',
    'limite di frequenza'     => '/limiting (requests|connections)/',
    'accesso negato'          => '/access forbidden|forbidden by rule|denied/',
    'indice di cartella'      => '/directory index of .* is forbidden/',
    'file mancante (404)'     => '/open\(\) ".*" failed|No such file/',
    'tempo scaduto'           => '/timed out/',
    // messaggi tecnici di nginx, non risposte d'errore al client
    'richiesta registrata (syslog non raggiungibile)' => '/send\(\) to syslog failed while logging request/',
    'cache sessioni SSL piena (handshake prosegue)'   => '/could not allocate new session in SSL session shared cache/',
];

echo "\n== ESITO PER BOT (righe del log degli errori dagli IP ufficiali)\n";
foreach ($elenchi as $bot => $reti) {
    $ips = array_keys(array_filter($trovati, fn($t) => $t[0] === $bot));
    if (!$ips) {
        printf("  %-55s nessuna riga: nessun blocco, nessun errore registrato\n", $bot);
        continue;
    }
    $file = tempnam(sys_get_temp_dir(), 'bot');
    file_put_contents($file, implode("\n", array_map(fn($ip) => "client: $ip,", $ips)) . "\n");
    $dettaglio = (string)shell_exec('grep -F -f ' . escapeshellarg($file) . ' ' . escapeshellarg($log));
    unlink($file);
    $conta = array_fill_keys(array_keys($tipi), 0) + ['altro' => 0];
    $esempi = [];
    foreach (explode("\n", trim($dettaglio)) as $riga) {
        $tipo = 'altro';
        foreach ($tipi as $nome => $re) {
            if (preg_match($re, $riga)) {
                $tipo = $nome;
                break;
            }
        }
        $conta[$tipo]++;
        if (!isset($esempi[$tipo])) {
            $esempi[$tipo] = substr(preg_replace('/^(\S+ \S+) \[\w+\] [\d#]+: \*\d+ /', '$1 ', $riga), 0, 230);
        }
    }
    printf("  %-55s %d IP, %d righe\n", $bot, count($ips), array_sum($conta));
    foreach ($conta as $tipo => $n) {
        if ($n) {
            printf("      %-48s %6d   es. %s\n", $tipo, $n, $esempi[$tipo]);
        }
    }
}

echo "\nNota: access.log non attivo su MODX Cloud, quindi le richieste andate a buon fine (200) non\n";
echo "compaiono qui: il log degli errori registra solo blocchi, sfide e risposte d'errore.\n";
