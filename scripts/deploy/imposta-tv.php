<?php
/**
 * imposta-tv.php — scrive valori di TV su risorse MODX 3 da un file JSON del repository, con prova, backup e ripristino.
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/imposta-tv.php /www kosgroup/dati/voce-24/landing.json            # prova: non scrive
 *   php scripts/deploy/imposta-tv.php /www kosgroup/dati/voce-24/landing.json --scrivi   # applica
 *   ... --forza   sovrascrive anche i valori già presenti e diversi (senza: FERMO su quelle risorse)
 * Ripristino: php scripts/deploy/imposta-tv.php /www ~/backup-kos-seo-geo/<data>-tv/ripristino.json --scrivi --forza
 *
 * Formato:
 *   {"nome": "...", "risorse": [{"id": 1065, "tv": {"lnd.struttura": "@uri:it/centri/centro-cardinal-ferrari", ...}},
 *                               {"uri": "it/centri/myolab", "tv": {...}}]}
 *   - la risorsa si indica con "id" o con "uri" (campo uri di MODX, senza slash iniziale);
 *   - un valore «@uri:<uri>» diventa l'id della risorsa con quell'uri (collegamenti tra pagine);
 *   - la TV deve esistere ed essere collegata al template della risorsa;
 *   - valore "" = svuota la TV.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$file    = $argv[2] ?? '';
$scrivi  = in_array('--scrivi', $argv, true);
$forza   = in_array('--forza', $argv, true);
if ($webroot === '' || !is_file($file)) { fwrite(STDERR, "Uso: php imposta-tv.php /www <file.json> [--scrivi] [--forza]\n"); exit(1); }
$d = json_decode((string) file_get_contents($file), true);
if (!is_array($d) || !isset($d['risorse'])) { fwrite(STDERR, "File non valido.\n"); exit(1); }

require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');

$perUri = function (string $uri) use ($modx): ?modResource {
    $uri = ltrim($uri, '/');
    return $modx->getObject(modResource::class, ['uri' => $uri, 'deleted' => 0])
        ?: $modx->getObject(modResource::class, ['uri' => rtrim($uri, '/') . '/', 'deleted' => 0]);
};
$tvCache = [];
$tvDi = function (string $nome) use ($modx, &$tvCache) {
    return $tvCache[$nome] ??= $modx->getObject(modTemplateVar::class, ['name' => $nome]);
};

$stamp = date('Ymd-His');
$bkDir = rtrim((string) (getenv('HOME') ?: '.'), '/') . "/backup-kos-seo-geo/$stamp-tv";
$rip = ['nome' => 'ripristino TV ' . $stamp, 'risorse' => []];
$errori = 0; $cambi = 0;
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . ($d['nome'] ?? $file) . "\n";

foreach ($d['risorse'] as $voce) {
    $r = isset($voce['id']) ? $modx->getObject(modResource::class, (int) $voce['id']) : $perUri((string) ($voce['uri'] ?? ''));
    $chi = isset($voce['id']) ? "risorsa {$voce['id']}" : "uri {$voce['uri']}";
    if (!$r) { echo "  ! $chi non trovata — FERMO\n"; $errori++; continue; }
    $id = (int) $r->get('id');
    $intest = "  $id {$r->get('pagetitle')}";
    $daScrivere = []; $vecchi = []; $ferma = false;
    foreach ($voce['tv'] as $nome => $val) {
        $val = (string) $val;
        if (str_starts_with($val, '@uri:')) {
            $dest = $perUri(substr($val, 5));
            if (!$dest) { echo "$intest\n    ! $nome: pagina " . substr($val, 5) . " non trovata — FERMO\n"; $ferma = true; continue; }
            $val = (string) $dest->get('id');
        }
        $tv = $tvDi($nome);
        if (!$tv) { echo "$intest\n    ! TV $nome inesistente — FERMO\n"; $ferma = true; continue; }
        if (!$modx->getObject(modTemplateVarTemplate::class, ['tmplvarid' => $tv->get('id'), 'templateid' => $r->get('template')])) {
            echo "$intest\n    ! TV $nome non collegata al template della risorsa — FERMO\n"; $ferma = true; continue;
        }
        $att = (string) $tv->getValue($id);
        if (str_replace("\r\n", "\n", $att) === str_replace("\r\n", "\n", $val)) continue;
        if ($att !== '' && !$forza) {
            echo "$intest\n    ! $nome già valorizzata con un altro valore — FERMO (--forza per sovrascrivere)\n        attuale: "
                . mb_substr(str_replace("\n", ' ⏎ ', $att), 0, 120) . "\n";
            $ferma = true; continue;
        }
        $daScrivere[$nome] = $val; $vecchi[$nome] = $att;
    }
    if ($ferma) { $errori++; continue; }
    if (!$daScrivere) { echo "$intest: già aggiornata\n"; continue; }
    echo "$intest\n";
    foreach ($daScrivere as $nome => $val) {
        echo "    ~ $nome: " . ($vecchi[$nome] === '' ? '(vuota)' : mb_substr(str_replace("\n", ' ⏎ ', $vecchi[$nome]), 0, 60))
            . " → " . mb_substr(str_replace("\n", ' ⏎ ', $val), 0, 90) . "\n";
    }
    $rip['risorse'][] = ['id' => $id, 'tv' => $vecchi];
    $cambi += count($daScrivere);
    if ($scrivi) {
        foreach ($daScrivere as $nome => $val) { $r->setTVValue($nome, $val); }
    }
}

if ($scrivi && $rip['risorse']) {
    mkdir($bkDir, 0775, true);
    file_put_contents("$bkDir/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $modx->cacheManager->refresh();
    echo "== $cambi valori scritti, cache svuotata. Ripristino: $bkDir/ripristino.json (con --scrivi --forza)\n";
} elseif (!$scrivi) {
    echo "== $cambi valori da scrivere\n";
}
echo $errori ? "== $errori risorse ferme: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
