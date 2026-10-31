<?php
/**
 * aggiorna-campi.php — scrive nel database i campi pagetitle, longtitle, description, introtext e published di un insieme di
 * risorse preparato nel repository (es. anniazzurri/dati/aa-20/campi-giro-1.json), con controlli e ripristino.
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/aggiorna-campi.php /www anniazzurri/dati/aa-20/campi-giro-1.json             # prova
 *   php scripts/deploy/aggiorna-campi.php /www anniazzurri/dati/aa-20/campi-giro-1.json --scrivi
 *   php scripts/deploy/aggiorna-campi.php /www --ripristina ~/backup-kos-seo-geo/<data>-campi [--scrivi]
 *
 * File JSON: {"nome": "...", "risorse": [{"id": 11, "uri": "rsa-in-lombardia", "campi": {"longtitle":
 *   {"prima": "...", "dopo": "..."}, "description": {...}}}]}. Per ogni campo:
 *   - già uguale a «dopo»: niente da fare;
 *   - uguale a «prima» (istantanea da cui sono partiti i testi): si sostituisce;
 *   - altrimenti qualcuno l'ha cambiato dopo l'istantanea → FERMO per quella risorsa, salvo --forza.
 * Risorsa per id o, senza id, per uri (contesto web). Facoltativo "attesi": {"template": 0, "contenuto_vuoto": true}
 *   → FERMO se la risorsa non è come atteso. published vale "1" o "0".
 * Controlli sulla risorsa: uri uguale a quello atteso; longtitle non scritto se la risorsa (contenuto, template o
 * chunk richiamati) usa [[*longtitle]] per altro (cartelle di foto e documenti delle landing) o se il longtitle
 * attuale è un alias (minuscole, cifre, trattini).
 * Prima di scrivere: tabella di backup <prefisso>site_content_bak_<AAAAMMGG_HHMM> (id e campi toccati) e copia JSON
 * in ~/backup-kos-seo-geo/<data>-campi/ per il ripristino. Alla fine svuota la cache.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$arg = array_slice($argv, 1);
$scrivi = in_array('--scrivi', $arg, true);
$forza = in_array('--forza', $arg, true);
$ripristina = in_array('--ripristina', $arg, true);
$pos = array_values(array_filter($arg, fn($a) => !str_starts_with($a, '--')));
$webroot = rtrim($pos[0] ?? '', '/');
$origine = rtrim($pos[1] ?? '', '/');
if ($webroot === '' || $origine === '') {
    fwrite(STDERR, "Uso: php aggiorna-campi.php /www <file.json | --ripristina cartella> [--scrivi] [--forza]\n"); exit(1);
}
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
use MODX\Revolution\{modResource, modTemplate, modChunk};

const CAMPI = ['pagetitle', 'longtitle', 'description', 'introtext', 'published'];
$val = fn($res, string $c): string => $c === 'published' ? (string) (int) $res->get($c) : (string) $res->get($c);
$bkRoot = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo';
$stamp = date('Ymd-His');
$errori = 0; $fatte = 0;
$breve = fn(string $s) => mb_strlen($s) > 70 ? mb_substr($s, 0, 67) . '…' : $s;

/* ---------- ripristino ---------- */
if ($ripristina) {
    $bk = json_decode((string) @file_get_contents("$origine/campi.json"), true);
    if (!is_array($bk) || empty($bk['risorse'])) { fwrite(STDERR, "campi.json mancante o vuoto in $origine\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " — " . count($bk['risorse']) . " risorse\n";
    foreach ($bk['risorse'] as $r) {
        $res = $modx->getObject(modResource::class, (int) $r['id']);
        if (!$res) { echo "  ! risorsa {$r['id']} non trovata\n"; $errori++; continue; }
        $cambia = false;
        foreach ($r['prima'] as $c => $v) {
            $att = $val($res, $c);
            if ($att === $v) continue;
            if ($att !== $r['scritto'][$c] && !$forza) {
                echo "  ! {$r['id']} $c modificato dopo l'aggiornamento — FERMO (--forza per ripristinare comunque)\n"; $errori++; continue 2;
            }
            $cambia = true;
            if ($scrivi) $res->set($c, $v);
        }
        if (!$cambia) { echo "  = {$r['id']} già com'era\n"; continue; }
        echo "  ← {$r['id']} {$res->get('pagetitle')}\n";
        if ($scrivi) $res->save() ? $fatte++ : $errori++;
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo "== $fatte ripristinate" . ($errori ? ", $errori problemi" : '') . ($scrivi ? '; cache svuotata' : '') . "\n";
    exit($errori ? 2 : 0);
}

/* ---------- aggiornamento ---------- */
$man = json_decode((string) @file_get_contents($origine), true);
if (!is_array($man) || empty($man['risorse'])) { fwrite(STDERR, "$origine mancante o vuoto\n"); exit(1); }
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . ($man['nome'] ?? $origine) . ", " . count($man['risorse']) . " risorse\n";

// chunk che usano [[*longtitle]]: una risorsa che li richiama non deve avere il longtitle cambiato
$chunkLong = [];
foreach ($modx->getIterator(modChunk::class) as $ch) {
    if (str_contains((string) $ch->get('snippet'), '*longtitle')) $chunkLong[] = $ch->get('name');
}
$usaLongtitle = function (modResource $res) use ($modx, $chunkLong): string {
    $testi = ['contenuto' => (string) $res->get('content')];
    $t = $modx->getObject(modTemplate::class, $res->get('template'));
    if ($t) $testi['template ' . $t->get('templatename')] = (string) $t->get('content');
    foreach ($testi as $dove => $txt) {
        if (preg_match('/\[\[\*longtitle\b/', $txt) && !preg_match('/<title>[^<]*\*longtitle/', $txt)) {
            // il template convegnoAA usa il longtitle anche come H1: è un titolo, non un altro uso
            if (!preg_match('/<h1[^>]*>\s*\[\[\*longtitle/', $txt)) return $dove;
        }
        foreach ($chunkLong as $n) if (preg_match('/\[\[\$' . preg_quote($n, '/') . '[\]?:]/', $txt)) return "$dove → chunk $n";
    }
    return '';
};

$daFare = [];
foreach ($man['risorse'] as $r) {
    $res = isset($r['id']) ? $modx->getObject(modResource::class, (int) $r['id'])
         : $modx->getObject(modResource::class, ['context_key' => 'web', 'deleted' => 0, 'uri:IN' => [trim($r['uri'], '/'), trim($r['uri'], '/') . '/']]);
    if (!$res) { echo "  ! risorsa " . ($r['id'] ?? $r['uri']) . " non trovata\n"; $errori++; continue; }
    $r['id'] = (int) $res->get('id');
    $etichetta = "{$r['id']} " . $breve((string) $res->get('pagetitle'));
    $uri = trim((string) $res->get('uri'), '/');
    if (isset($r['uri']) && $uri !== trim($r['uri'], '/')) {
        echo "  ! $etichetta: uri «{$uri}» invece di «{$r['uri']}» — FERMO\n"; $errori++; continue;
    }
    if (isset($r['attesi']['template']) && (int) $res->get('template') !== (int) $r['attesi']['template']) {
        echo "  ! $etichetta: template {$res->get('template')} invece di {$r['attesi']['template']} — FERMO\n"; $errori++; continue;
    }
    if (!empty($r['attesi']['contenuto_vuoto']) && trim((string) $res->get('content')) !== '') {
        echo "  ! $etichetta: il contenuto non è vuoto — FERMO\n"; $errori++; continue;
    }
    $nuovi = []; $fermo = false; $note = [];
    foreach ($r['campi'] as $c => $v) {
        if (!in_array($c, CAMPI, true)) { echo "  ! $etichetta: campo $c non previsto\n"; $fermo = true; break; }
        $att = $val($res, $c);
        if ($att === $v['dopo']) continue;
        if ($c === 'longtitle') {
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', trim($att))) { echo "  ! $etichetta: il longtitle attuale «{$att}» è un alias (cartella) — FERMO\n"; $fermo = true; break; }
            if ($dove = $usaLongtitle($res)) { echo "  ! $etichetta: il longtitle è usato da $dove — FERMO\n"; $fermo = true; break; }
        }
        if ($att !== $v['prima']) {
            echo "  ! $etichetta: $c cambiato dopo l'istantanea («" . $breve($att) . "»)" . ($forza ? " — procedo per --forza\n" : " — FERMO\n");
            if (!$forza) { $fermo = true; break; }
        }
        $nuovi[$c] = [$att, $v['dopo']];
        $note[] = "$c: «" . $breve($att) . "» → «" . $breve($v['dopo']) . "»";
    }
    if ($fermo) { $errori++; continue; }
    if (!$nuovi) { echo "  = $etichetta già aggiornata\n"; continue; }
    echo "  ~ $etichetta\n      " . implode("\n      ", $note) . "\n";
    $daFare[] = [$res, $nuovi];
}

if ($scrivi && $daFare) {
    if ($errori && !$forza) { echo "== $errori problemi: nessuna scrittura (correggere o usare --forza per scrivere le altre).\n"; exit(2); }
    $tab = $modx->getTableName(modResource::class);
    $bakTab = '`' . trim($tab, '`') . '_bak_' . date('Ymd_Hi') . '`';
    $ids = implode(',', array_map(fn($x) => (int) $x[0]->get('id'), $daFare));
    if ($modx->exec("CREATE TABLE $bakTab AS SELECT id, " . implode(', ', CAMPI) . " FROM $tab WHERE id IN ($ids)") === false) {
        fwrite(STDERR, "Creazione della tabella di backup $bakTab fallita: nessuna modifica.\n"); exit(1);
    }
    $bkDir = "$bkRoot/$stamp-campi";
    mkdir($bkDir, 0775, true);
    $copia = ['creato' => date('c'), 'tabella' => trim($bakTab, '`'), 'origine' => $origine, 'risorse' => []];
    foreach ($daFare as [$res, $nuovi]) {
        $copia['risorse'][] = ['id' => $res->get('id'), 'prima' => array_map(fn($x) => $x[0], $nuovi), 'scritto' => array_map(fn($x) => $x[1], $nuovi)];
    }
    file_put_contents("$bkDir/campi.json", json_encode($copia, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    foreach ($daFare as [$res, $nuovi]) {
        foreach ($nuovi as $c => [, $v]) $res->set($c, $v);
        if ($res->save()) $fatte++; else { echo "  ! salvataggio fallito: {$res->get('id')}\n"; $errori++; }
    }
    $modx->cacheManager->refresh();
    echo "== $fatte risorse aggiornate; cache svuotata. Backup: tabella {$copia['tabella']} e $bkDir/campi.json\n";
    echo "   Ripristino: php scripts/deploy/aggiorna-campi.php /www --ripristina $bkDir --scrivi\n";
}
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
