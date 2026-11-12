<?php
/**
 * KOS-11 / voce 24 — Villa Margherita Vicenza: la Casa di Cura sulla landing /villamargherita (#localbusiness),
 * le unità di riabilitazione e psichiatria come reparti (#department). Con prova, backup e ripristino.
 *
 * Uso (in SSH sul cloud di kosgroup.com, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/kos-reparti-villa-margherita.php /www kosgroup/dati/voce-24/villa-margherita-vicenza.json
 *   ... --scrivi
 *   php scripts/deploy/kos-reparti-villa-margherita.php /www --ripristina <cartella backup>/ripristino.json [--scrivi]
 *
 * Cosa fa:
 *   1. trova la landing (uri del campo "sede") e le pagine delle unità ("reparti");
 *   2. aggiunge il template della landing a kos.tpl_struttura (le landing senza str.via restano come prima: la
 *      libreria ricade sul legame lnd.struttura);
 *   3. imposta kos.reparti = "idUnità:idSede,..." ;
 *   4. collega al template della landing le TV str.* che mancano e scrive i valori del file sulla landing;
 *   5. svuota la cache. Richiede la libreria comune con il supporto dei reparti (shared/snippets/seoSchema.php del 12/11).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$args = array_slice($argv, 1);
$scrivi = in_array('--scrivi', $args, true);
$webroot = rtrim($args[0] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) exit("Uso: php kos-reparti-villa-margherita.php /www <dati.json> [--scrivi] | --ripristina <file> [--scrivi]\n");
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modTemplateVar, modTemplateVarTemplate, modTemplateVarResource, modSystemSetting};
$modx = modX::getInstance();
$modx->initialize('mgr');

$impostazione = function (string $k) use ($modx): ?modSystemSetting { return $modx->getObject(modSystemSetting::class, ['key' => $k]); };
$scriviImp = function (string $k, ?string $v) use ($modx, $impostazione) {
    $s = $impostazione($k);
    if ($v === null) { if ($s) $s->remove(); return; }
    if (!$s) { $s = $modx->newObject(modSystemSetting::class); $s->set('key', $k); $s->set('namespace', 'core'); $s->set('area', 'kos'); $s->set('xtype', 'textfield'); }
    $s->set('value', $v); $s->save();
};
$valoreTv = function (int $tvId, int $resId) use ($modx): ?string {
    $o = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tvId, 'contentid' => $resId]);
    return $o ? (string) $o->get('value') : null;
};
$scriviTv = function (int $tvId, int $resId, ?string $v) use ($modx) {
    $o = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tvId, 'contentid' => $resId]);
    if ($v === null || $v === '') { if ($o) $o->remove(); return; }
    if (!$o) { $o = $modx->newObject(modTemplateVarResource::class); $o->set('tmplvarid', $tvId); $o->set('contentid', $resId); }
    $o->set('value', $v); $o->save();
};

/* ---------- ripristino ---------- */
$i = array_search('--ripristina', $args, true);
if ($i !== false) {
    $r = json_decode((string) @file_get_contents($args[$i + 1] ?? ''), true);
    if (!$r) exit("File di ripristino illeggibile.\n");
    echo $scrivi ? "== RIPRISTINO\n" : "== PROVA RIPRISTINO (aggiungere --scrivi)\n";
    foreach ($r['impostazioni'] as $k => $v) { echo "  < $k = " . ($v ?? '(assente)') . "\n"; if ($scrivi) $scriviImp($k, $v); }
    foreach ($r['tv_valori'] as $x) { echo "  < TV {$x['tv']} risorsa {$x['res']} = " . ($x['prima'] ?? '(vuota)') . "\n"; if ($scrivi) $scriviTv($x['tvId'], $x['res'], $x['prima']); }
    foreach ($r['tv_collegate'] as $x) {
        echo "  - scollega TV {$x['tv']} dal template {$x['tpl']}\n";
        if ($scrivi && ($o = $modx->getObject(modTemplateVarTemplate::class, ['tmplvarid' => $x['tvId'], 'templateid' => $x['tplId']]))) $o->remove();
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Ripristinato, cache svuotata.\n"; } else echo "== Prova riuscita.\n";
    exit;
}

/* ---------- prova ---------- */
$d = json_decode((string) @file_get_contents($args[1] ?? ''), true);
if (!$d || empty($d['sede']) || empty($d['reparti']) || empty($d['tv'])) exit("File dati non valido.\n");
echo $scrivi ? "== SCRITTURA\n" : "== PROVA (nessuna scrittura: aggiungere --scrivi)\n";
$fermi = 0;
$perUri = function (string $uri) use ($modx): ?modResource {
    $uri = ltrim($uri, '/');
    return $modx->getObject(modResource::class, ['uri' => $uri, 'deleted' => 0, 'context_key' => 'web'])
        ?: $modx->getObject(modResource::class, ['uri' => rtrim($uri, '/') . '/', 'deleted' => 0, 'context_key' => 'web']);
};
$nomeTpl = function (modResource $r) { $t = $r->getOne('Template'); return $t ? (string) $t->get('templatename') : ''; };

$sede = $perUri($d['sede']);
if (!$sede) { echo "  FERMO: nessuna risorsa con uri {$d['sede']}\n"; $fermi++; }
else echo "  sede: risorsa {$sede->get('id')} «{$sede->get('pagetitle')}», template «" . $nomeTpl($sede) . "» (id {$sede->get('template')}), " . ($sede->get('published') ? 'pubblicata' : 'NON pubblicata') . "\n";

$tplStr = array_filter(array_map('trim', explode(',', (string) $modx->getOption('kos.tpl_struttura'))));
$coppie = [];
foreach ($d['reparti'] as $u) {
    $r = $perUri($u);
    if (!$r) { echo "  FERMO: nessuna risorsa con uri $u\n"; $fermi++; continue; }
    $t = $nomeTpl($r);
    echo "  unità: risorsa {$r->get('id')} «{$r->get('pagetitle')}», template «{$t}»" . (in_array($t, $tplStr, true) ? '' : ' (template fuori da kos.tpl_struttura: la libreria la tratta comunque come reparto)') . "\n";
    if ($sede) $coppie[] = $r->get('id') . ':' . $sede->get('id');
}
if (!$sede || $fermi) exit("== $fermi FERMO: nessuna scrittura.\n");

$tplSede = $nomeTpl($sede);
$nuovoTplStr = in_array($tplSede, $tplStr, true) ? null : implode(',', array_merge($tplStr, [$tplSede]));
echo '  kos.tpl_struttura: ' . ($nuovoTplStr ? "«" . implode(',', $tplStr) . "» → «$nuovoTplStr»" : 'contiene già il template della landing') . "\n";
$repartiAttuale = (string) $modx->getOption('kos.reparti', null, '');
$repartiNuovo = implode(',', array_unique(array_merge(array_filter(array_map('trim', explode(',', $repartiAttuale))), $coppie)));
echo "  kos.reparti: «$repartiAttuale» → «$repartiNuovo»\n";
echo '  kos.struttura_attiva = ' . $modx->getOption('kos.struttura_attiva', null, '(assente)') . "\n";

$collegare = []; $valori = [];
foreach ($d['tv'] as $nome => $v) {
    $tvo = $modx->getObject(modTemplateVar::class, ['name' => $nome]);
    if (!$tvo) { echo "  FERMO: TV $nome inesistente\n"; $fermi++; continue; }
    $col = (bool) $modx->getObject(modTemplateVarTemplate::class, ['tmplvarid' => $tvo->get('id'), 'templateid' => $sede->get('template')]);
    if (!$col) $collegare[] = $tvo;
    $prima = $valoreTv((int) $tvo->get('id'), (int) $sede->get('id'));
    if ((string) $prima !== (string) $v) $valori[] = [$tvo, $prima, (string) $v];
    printf("  %-18s %s%s\n", $nome, $col ? '' : '[da collegare al template] ', $prima === (string) $v ? "= $v" : (($prima === null ? '(vuota)' : $prima) . " → $v"));
}
if ($fermi) exit("== $fermi FERMO: nessuna scrittura.\n");
if (!$scrivi) exit("== Prova riuscita: " . count($collegare) . " TV da collegare, " . count($valori) . " valori da scrivere.\n");

/* ---------- scrittura ---------- */
$bk = rtrim((string) (getenv('HOME') ?: '/tmp'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-reparti-villa-margherita';
mkdir($bk, 0775, true);
$rip = ['data' => date('c'), 'impostazioni' => [], 'tv_valori' => [], 'tv_collegate' => []];
foreach (['kos.tpl_struttura' => $nuovoTplStr, 'kos.reparti' => $repartiNuovo] as $k => $v) {
    if ($v === null) continue;
    $s = $impostazione($k);
    $rip['impostazioni'][$k] = $s ? (string) $s->get('value') : null;
}
foreach ($collegare as $tvo) $rip['tv_collegate'][] = ['tv' => $tvo->get('name'), 'tvId' => (int) $tvo->get('id'), 'tpl' => $tplSede, 'tplId' => (int) $sede->get('template')];
foreach ($valori as [$tvo, $prima]) $rip['tv_valori'][] = ['tv' => $tvo->get('name'), 'tvId' => (int) $tvo->get('id'), 'res' => (int) $sede->get('id'), 'prima' => $prima];
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($collegare as $tvo) {
    $o = $modx->newObject(modTemplateVarTemplate::class);
    $o->set('tmplvarid', $tvo->get('id')); $o->set('templateid', $sede->get('template')); $o->set('rank', 0); $o->save();
}
foreach ($valori as [$tvo, , $v]) $scriviTv((int) $tvo->get('id'), (int) $sede->get('id'), $v);
if ($nuovoTplStr) $scriviImp('kos.tpl_struttura', $nuovoTplStr);
$scriviImp('kos.reparti', $repartiNuovo);
$modx->cacheManager->refresh();
echo "== Fatto: " . count($collegare) . " TV collegate, " . count($valori) . " valori scritti, impostazioni aggiornate, cache svuotata.\n";
echo "   Ripristino: php scripts/deploy/kos-reparti-villa-margherita.php $webroot --ripristina $bk/ripristino.json --scrivi\n";
