<?php
/**
 * KOS-11 (medici) — SOLA LETTURA: esporta le pagine dei professionisti di kosgroup.com (template «medici») con i
 * loro campi e TV, i collegamenti dalle schede struttura e le tabelle kos_* della prenotazione, per il confronto con le
 * risorse TuoTempo (scripts/checks/kos-medici-confronto.py).
 *
 * Uso: php scripts/checks/kos-medici-pagine.php /www
 * Scrive ~/kos-medici-pagine-<AAAAMMGG>.csv (separatore ;) e stampa un riepilogo (TV del template, tabelle kos_*).
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-medici-pagine.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modTemplateVar, modTemplateVarTemplate, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('web');
$uno = fn($s) => trim((string) preg_replace('/\s+/u', ' ', (string) $s));

$tplMed = [];
foreach ($modx->getIterator(modTemplate::class) as $t) if (preg_match('/medic/i', (string) $t->get('templatename'))) $tplMed[$t->get('id')] = $t->get('templatename');
echo "== Template dei professionisti: " . json_encode($tplMed, JSON_UNESCAPED_UNICODE) . "\n";
if (!$tplMed) exit(2);

// TV assegnate ai template
$tvs = [];
foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid:IN' => array_keys($tplMed)]) as $l) {
    $tv = $modx->getObject(modTemplateVar::class, $l->get('tmplvarid'));
    if ($tv) $tvs[$tv->get('id')] = $tv->get('name');
}
asort($tvs);
echo "== TV del template (" . count($tvs) . "): " . implode(', ', $tvs) . "\n";

// testi delle schede struttura e delle altre pagine, per contare chi collega il medico
$testi = [];
$q = $modx->newQuery(modResource::class, ['published' => 1, 'deleted' => 0, 'template:NOT IN' => array_keys($tplMed)]);
$q->select(['id', 'uri', 'content']);
if ($q->prepare() && $q->stmt->execute()) foreach ($q->stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $testi[$r['id']] = [$r['uri'], (string) $r['content']];
$tvTesti = [];
$s = $modx->query('SELECT contentid, value FROM ' . $modx->getTableName(modTemplateVarResource::class));
if ($s) foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) $tvTesti[$r['contentid']][] = (string) $r['value'];

$out = fopen(getenv('HOME') . '/kos-medici-pagine-' . date('Ymd') . '.csv', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, array_merge(['id', 'uri', 'pagetitle', 'longtitle', 'publicato', 'ricercabile', 'genitore', 'parole contenuto', 'collegata da'], array_values($tvs)), ';');
$n = 0; $pub = 0;
$q = $modx->newQuery(modResource::class, ['template:IN' => array_keys($tplMed), 'deleted' => 0]);
$q->sortby('pagetitle');
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $id = (int) $r->get('id'); $uri = trim((string) $r->get('uri'), '/');
    $da = [];
    foreach ($testi as $rid => [$ruri, $c]) {
        if (str_contains($c, "[[~$id]]") || ($uri !== '' && str_contains($c, $uri))) { $da[] = $ruri; continue; }
        foreach ($tvTesti[$rid] ?? [] as $v) if (preg_match('/(^|[,|])' . $id . '($|[,|])/', $v) || ($uri !== '' && str_contains($v, $uri))) { $da[] = $ruri; break; }
    }
    $p = $r->get('parent'); $par = $p ? $modx->getObject(modResource::class, $p) : null;
    $riga = [$id, $uri, $uno($r->get('pagetitle')), $uno($r->get('longtitle')), (int) $r->get('published'), (int) $r->get('searchable'),
        $par ? $par->get('pagetitle') : '', str_word_count(strip_tags((string) $r->get('content'))), implode(' | ', array_slice($da, 0, 8))];
    foreach (array_keys($tvs) as $tid) {
        $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tid, 'contentid' => $id]);
        $riga[] = $v ? mb_substr($uno(strip_tags((string) $v->get('value'))), 0, 300) : '';
    }
    fputcsv($out, $riga, ';');
    $n++; $pub += (int) $r->get('published');
}
fclose($out);
echo "== Pagine con template dei professionisti: $n (pubblicate $pub). CSV: ~/kos-medici-pagine-" . date('Ymd') . ".csv\n";

// tabelle della prenotazione (struttura, righe)
foreach ($modx->query("SHOW TABLES LIKE 'kos\\_%'")->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $cols = $modx->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $righe = (int) $modx->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  tabella $t ($righe righe): " . implode(', ', $cols) . "\n";
}
