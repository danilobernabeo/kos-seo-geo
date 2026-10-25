<?php
/**
 * KOS voce 24 — completa le associazioni struttura ↔ servizi e cure presenti sul sito in una sola direzione.
 *
 * Uso (SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/kos-mappatura-inversa.php /www            # prova: elenca le aggiunte e scrive ~/mappatura-inversa-<data>.csv
 *   php scripts/deploy/kos-mappatura-inversa.php /www --scrivi   # applica (backup e ripristino in ~/backup-kos-seo-geo/<data>-mappatura/)
 * Ripristino: php scripts/deploy/imposta-tv.php /www ~/backup-kos-seo-geo/<data>-mappatura/ripristino.json --scrivi --forza
 *
 * Le due direzioni:
 *   scheda → servizio   TV della scheda struttura: mapAreeRiabilitazione, mapAreePsichiatria, mapAreeMedica,
 *                       mapAreeChirurgica, mapAreeDiagnostica, mapInterventiRiabilitazione, mapInterventiPsichiatria,
 *                       mapInterventiDiagnostica
 *   servizio → scheda   TV mapServiziStrutture della pagina del servizio (sezione it/servizi-e-cure/)
 * Regola: nessuna associazione si toglie, si aggiunge solo la corrispondenza mancante.
 *   - presente solo sulla scheda  → la scheda si aggiunge a mapServiziStrutture della pagina del servizio;
 *   - presente solo sul servizio  → il servizio si aggiunge alla scheda, nella TV in cui quella pagina compare più
 *     spesso sulle altre schede, tra quelle del template della scheda (es. gli ambulatori sono quasi sempre in
 *     mapAreeRiabilitazione); se non compare mai, TV predefinita per sezione. Se la TV non è collegata al template della scheda, l'aggiunta si segnala e si salta.
 * Solo schede e servizi pubblicati. I valori restano liste di id separati da virgola, in coda a quelli esistenti.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi  = in_array('--scrivi', $argv, true);
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-mappatura-inversa.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$TV_SCHEDA = ['mapAreeRiabilitazione', 'mapAreePsichiatria', 'mapAreeMedica', 'mapAreeChirurgica', 'mapAreeDiagnostica',
              'mapInterventiRiabilitazione', 'mapInterventiPsichiatria', 'mapInterventiDiagnostica'];
$PREDEFINITA = ['riabilitazione/trattamenti' => 'mapInterventiRiabilitazione', 'psichiatria/trattamenti' => 'mapInterventiPsichiatria',
                'riabilitazione' => 'mapAreeRiabilitazione', 'psichiatria' => 'mapAreePsichiatria',
                'ambulatori-e-medicina-specialistica' => 'mapAreeMedica'];
$tpl = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('kos.tpl_struttura') ?: 'strutture,strutture-VDP'))));
$ids = fn(string $v) => array_values(array_unique(array_filter(array_map('intval', preg_split('/[^\d]+/', $v)))));

// TV: id per nome e template collegati
$tvId = []; $tvTpl = [];
foreach (array_merge($TV_SCHEDA, ['mapServiziStrutture']) as $n) {
    $tv = $modx->getObject(modTemplateVar::class, ['name' => $n]);
    if (!$tv) continue;
    $tvId[$n] = (int) $tv->get('id');
    foreach ($modx->getIterator(modTemplateVarTemplate::class, ['tmplvarid' => $tv->get('id')]) as $l) $tvTpl[$n][(int) $l->get('templateid')] = true;
}
$valore = function (int $rid, string $tv) use ($db, $p, $tvId): string {
    if (!isset($tvId[$tv])) return '';
    $st = $db->prepare("SELECT value FROM {$p}site_tmplvar_contentvalues WHERE contentid = ? AND tmplvarid = ?");
    $st->execute([$rid, $tvId[$tv]]);
    return (string) ($st->fetchColumn() ?: '');
};

// schede pubblicate
$in = implode(',', array_fill(0, count($tpl), '?'));
$st = $db->prepare("SELECT c.id, c.pagetitle, c.uri, c.template FROM {$p}site_content c JOIN {$p}site_templates t ON t.id = c.template
                    WHERE t.templatename IN ($in) AND c.published = 1 AND c.deleted = 0");
$st->execute($tpl);
$schede = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $schede[(int) $r['id']] = $r;

// servizi pubblicati
$servizi = [];
foreach ($db->query("SELECT id, pagetitle, uri FROM {$p}site_content WHERE uri LIKE 'it/servizi-e-cure/%' AND published = 1 AND deleted = 0")
         ->fetchAll(PDO::FETCH_ASSOC) as $r) $servizi[(int) $r['id']] = $r;
$sezione = function (string $uri): string {
    $p = explode('/', trim($uri, '/'));
    return ($p[2] ?? '') . (in_array('trattamenti', $p, true) ? '/trattamenti' : '');
};

// stato attuale delle due direzioni
$A = []; $tvDi = []; $uso = [];
foreach ($schede as $sid => $s) {
    foreach ($TV_SCHEDA as $n) {
        foreach ($ids($valore($sid, $n)) as $pid) {
            if (!isset($servizi[$pid])) continue;
            $A[$sid][$pid] = true;
            $tvDi[$sid][$pid] = $n;
            $uso[$pid][$n] = ($uso[$pid][$n] ?? 0) + 1;
        }
    }
}
$B = []; $msVal = [];
foreach ($servizi as $pid => $x) {
    $msVal[$pid] = $valore($pid, 'mapServiziStrutture');
    foreach ($ids($msVal[$pid]) as $sid) if (isset($schede[$sid])) $B[$sid][$pid] = true;
}

$aggServizio = [];   // pid => [sid...]
$aggScheda = [];     // sid => [tv => [pid...]]
$csv = [['Struttura', 'Scheda', 'Servizio', 'Pagina del servizio', 'Aggiunta', 'Dove']];
$saltate = [];
foreach ($schede as $sid => $s) {
    foreach (array_keys($A[$sid] ?? []) as $pid) {
        if (isset($B[$sid][$pid])) continue;
        $aggServizio[$pid][] = $sid;
        $csv[] = [$s['pagetitle'], '/' . $s['uri'], $servizi[$pid]['pagetitle'], '/' . $servizi[$pid]['uri'],
                  'struttura aggiunta alla pagina del servizio', 'mapServiziStrutture'];
    }
    foreach (array_keys($B[$sid] ?? []) as $pid) {
        if (isset($A[$sid][$pid])) continue;
        // TV più usata per quella pagina tra quelle collegate al template della scheda, altrimenti la predefinita
        $cand = array_filter($uso[$pid] ?? [], fn($n, $tv) => !empty($tvTpl[$tv][(int) $s['template']]), ARRAY_FILTER_USE_BOTH);
        arsort($cand);
        $tv = $cand ? array_key_first($cand) : ($PREDEFINITA[$sezione($servizi[$pid]['uri'])] ?? null);
        if (!$tv || empty($tvTpl[$tv][(int) $s['template']])) {
            $saltate[] = "{$s['pagetitle']} ← {$servizi[$pid]['pagetitle']}: nessuna TV adatta" . ($tv ? " ($tv non collegata al template)" : '');
            continue;
        }
        $aggScheda[$sid][$tv][] = $pid;
        $csv[] = [$s['pagetitle'], '/' . $s['uri'], $servizi[$pid]['pagetitle'], '/' . $servizi[$pid]['uri'],
                  'servizio aggiunto alla scheda', $tv];
    }
}

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — associazioni struttura ↔ servizi in una sola direzione\n";
$nServ = array_sum(array_map('count', $aggServizio));
$nSch = 0; foreach ($aggScheda as $x) foreach ($x as $l) $nSch += count($l);
echo "  pagine dei servizi da completare: " . count($aggServizio) . " ($nServ strutture da aggiungere)\n";
echo "  schede da completare: " . count($aggScheda) . " ($nSch servizi da aggiungere)\n";
foreach ($aggScheda as $sid => $x) foreach ($x as $tv => $l) {
    echo "    {$schede[$sid]['pagetitle']} — $tv: + " . implode(', ', array_map(fn($pid) => $servizi[$pid]['pagetitle'], $l)) . "\n";
}
foreach ($saltate as $s) echo "  ! $s\n";

$stamp = date('Ymd-His');
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');
$f = fopen("$home/mappatura-inversa-$stamp.csv", 'w');
foreach ($csv as $r) fputcsv($f, $r);
fclose($f);
echo "== Elenco delle aggiunte: $home/mappatura-inversa-$stamp.csv\n";

if ($scrivi) {
    $rip = ['nome' => 'ripristino mappatura inversa ' . $stamp, 'risorse' => []];
    foreach ($aggServizio as $pid => $sids) {
        $nuovo = implode(',', array_unique(array_merge($ids($msVal[$pid]), $sids)));
        $rip['risorse'][] = ['id' => $pid, 'tv' => ['mapServiziStrutture' => $msVal[$pid]]];
        $modx->getObject(modResource::class, $pid)->setTVValue('mapServiziStrutture', $nuovo);
    }
    foreach ($aggScheda as $sid => $x) {
        $r = $modx->getObject(modResource::class, $sid);
        $vecchi = [];
        foreach ($x as $tv => $pids) {
            $att = $valore($sid, $tv);
            $vecchi[$tv] = $att;
            $r->setTVValue($tv, implode(',', array_unique(array_merge($ids($att), $pids))));
        }
        $rip['risorse'][] = ['id' => $sid, 'tv' => $vecchi];
    }
    $bk = "$home/backup-kos-seo-geo/$stamp-mappatura";
    mkdir($bk, 0775, true);
    file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $modx->cacheManager->refresh();
    echo "== Scritto. Cache svuotata. Ripristino: $bk/ripristino.json (imposta-tv.php --scrivi --forza)\n";
}
