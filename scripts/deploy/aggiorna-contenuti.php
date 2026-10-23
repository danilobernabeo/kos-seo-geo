<?php
/**
 * aggiorna-contenuti.php — scrive nel database il campo content di un insieme di risorse, preparato nel repository
 * (es. anniazzurri/dati/aa-6-contenuti/, generato da scripts/titoli/converti-titoli.py), con controlli e ripristino.
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/aggiorna-contenuti.php /www anniazzurri/dati/aa-6-contenuti             # prova
 *   php scripts/deploy/aggiorna-contenuti.php /www anniazzurri/dati/aa-6-contenuti --scrivi
 *   php scripts/deploy/aggiorna-contenuti.php /www --ripristina ~/backup-kos-seo-geo/<data>-contenuti [--scrivi]
 *
 * Per ogni risorsa del manifest.json:
 *   - se il contenuto attuale è già quello nuovo: niente da fare;
 *   - se è quello da cui è partita la conversione (impronta sha1_prima): lo sostituisce;
 *   - altrimenti qualcuno l'ha modificato dopo l'esportazione → FERMO per quella risorsa (rifare esportazione e
 *     conversione), salvo --forza.
 * Prima di scrivere: tabella di backup <prefisso>site_content_bak_<AAAAMMGG_HHMM> (id, content) e copia JSON in
 * ~/backup-kos-seo-geo/<data>-contenuti/ per il ripristino. Alla fine svuota la cache.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$arg = array_slice($argv, 1);
$scrivi = in_array('--scrivi', $arg, true);
$forza = in_array('--forza', $arg, true);
$ripristina = in_array('--ripristina', $arg, true);
$pos = array_values(array_filter($arg, fn($a) => !str_starts_with($a, '--')));
$webroot = rtrim($pos[0] ?? '', '/');
$cartella = rtrim($pos[1] ?? '', '/');
if ($webroot === '' || $cartella === '' || !is_dir($cartella)) {
    fwrite(STDERR, "Uso: php aggiorna-contenuti.php /www <cartella con manifest.json> [--scrivi] [--forza]\n"); exit(1);
}
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
use MODX\Revolution\modResource;

$bkRoot = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo';
$stamp = date('Ymd-His');
$errori = 0; $fatte = 0;

/* ---------- ripristino ---------- */
if ($ripristina) {
    $bk = json_decode((string) @file_get_contents("$cartella/contenuti.json"), true);
    if (!is_array($bk) || empty($bk['risorse'])) { fwrite(STDERR, "contenuti.json mancante o vuoto in $cartella\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " — " . count($bk['risorse']) . " risorse\n";
    foreach ($bk['risorse'] as $r) {
        $res = $modx->getObject(modResource::class, (int) $r['id']);
        if (!$res) { echo "  ! risorsa {$r['id']} non trovata\n"; $errori++; continue; }
        $att = (string) $res->get('content');
        if ($att === $r['content']) { echo "  = {$r['id']} già com'era\n"; continue; }
        if (sha1($att) !== $r['sha1_scritto'] && !$forza) {
            echo "  ! {$r['id']} {$res->get('pagetitle')}: modificata dopo l'aggiornamento — FERMO (--forza per ripristinare comunque)\n"; $errori++; continue;
        }
        echo "  ← {$r['id']} {$res->get('pagetitle')}\n";
        if ($scrivi) { $res->set('content', $r['content']); $res->save() ? $fatte++ : $errori++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo "== $fatte ripristinate" . ($errori ? ", $errori problemi" : '') . ($scrivi ? '; cache svuotata' : '') . "\n";
    exit($errori ? 2 : 0);
}

/* ---------- aggiornamento ---------- */
$man = json_decode((string) @file_get_contents("$cartella/manifest.json"), true);
if (!is_array($man) || empty($man['risorse'])) { fwrite(STDERR, "manifest.json mancante o vuoto in $cartella\n"); exit(1); }
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . ($man['nome'] ?? $cartella) . ", " . count($man['risorse']) . " risorse\n";

$daFare = [];
foreach ($man['risorse'] as $r) {
    $nuovo = @file_get_contents("$cartella/{$r['file']}");
    if ($nuovo === false) { echo "  ! file mancante {$r['file']}\n"; $errori++; continue; }
    $res = $modx->getObject(modResource::class, (int) $r['id']);
    if (!$res) { echo "  ! risorsa {$r['id']} non trovata\n"; $errori++; continue; }
    $att = (string) $res->get('content');
    $etichetta = "{$r['id']} {$res->get('pagetitle')}";
    if ($att === $nuovo) { echo "  = $etichetta già aggiornata\n"; continue; }
    if (sha1($att) !== $r['sha1_prima']) {
        echo "  ! $etichetta: contenuto cambiato dopo l'esportazione" . ($forza ? " — procedo per --forza\n" : " — FERMO (rifare esportazione e conversione)\n");
        if (!$forza) { $errori++; continue; }
    }
    $regole = isset($r['regole']) ? ' (' . implode(', ', array_map(fn($k, $v) => "$k×$v", array_keys($r['regole']), $r['regole'])) . ')' : '';
    echo "  ~ $etichetta$regole\n";
    $daFare[] = [$res, $att, $nuovo];
}

if ($scrivi && $daFare) {
    // Tabella di backup nel database (regola di progetto prima degli UPDATE massivi) e copia JSON per il ripristino
    $tab = $modx->getTableName(modResource::class);
    $bakTab = '`' . trim($tab, '`') . '_bak_' . date('Ymd_Hi') . '`';
    $ids = implode(',', array_map(fn($x) => (int) $x[0]->get('id'), $daFare));
    if ($modx->exec("CREATE TABLE $bakTab AS SELECT id, content FROM $tab WHERE id IN ($ids)") === false) {
        fwrite(STDERR, "Creazione della tabella di backup $bakTab fallita: nessuna modifica.\n"); exit(1);
    }
    $bkDir = "$bkRoot/$stamp-contenuti";
    mkdir($bkDir, 0775, true);
    $copia = ['creato' => date('c'), 'tabella' => trim($bakTab, '`'), 'origine' => $cartella, 'risorse' => []];
    foreach ($daFare as [$res, $att, $nuovo]) $copia['risorse'][] = ['id' => $res->get('id'), 'content' => $att, 'sha1_scritto' => sha1($nuovo)];
    file_put_contents("$bkDir/contenuti.json", json_encode($copia, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    foreach ($daFare as [$res, $att, $nuovo]) {
        $res->set('content', $nuovo);
        if ($res->save()) $fatte++; else { echo "  ! salvataggio fallito: {$res->get('id')}\n"; $errori++; }
    }
    $modx->cacheManager->refresh();
    echo "== $fatte risorse aggiornate; cache svuotata. Backup: tabella {$copia['tabella']} e $bkDir/contenuti.json\n";
    echo "   Ripristino: php scripts/deploy/aggiorna-contenuti.php /www --ripristina $bkDir --scrivi\n";
}
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
