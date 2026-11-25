<?php
/**
 * Clinica Dentale Villa dei Pini su kosgroup.com (lavoro extra, fuori dal progetto SEO/GEO).
 * Ispezione in SOLA LETTURA prima di creare la scheda: nessuna scrittura nel database né nel webroot.
 *
 * Uso:  php ispeziona.php /www   → stampa lo stato e salva campi, contenuti e TV in ~/clinica-dentale-ispezione/
 *       (poi: tar czf ~/clinica-dentale-ispezione.tgz -C ~ clinica-dentale-ispezione)
 */
$webroot = rtrim($argv[1] ?? '/www', '/');
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
use MODX\Revolution\{modResource, modTemplate, modChunk, modSnippet, modTemplateVar, modTemplateVarResource,
    modTemplateVarTemplate, modSystemSetting, modContextSetting};

$out = (getenv('HOME') ?: '.') . '/clinica-dentale-ispezione';
@mkdir($out, 0775, true);
$salva = fn($nome, $dati) => file_put_contents("$out/$nome", is_string($dati) ? $dati : json_encode($dati,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$CAMPI = ['id', 'context_key', 'parent', 'pagetitle', 'longtitle', 'menutitle', 'description', 'introtext', 'alias',
    'uri', 'uri_override', 'template', 'published', 'publishedon', 'editedon', 'hidemenu', 'searchable', 'cacheable',
    'richtext', 'menuindex', 'isfolder', 'class_key', 'content_type', 'link_attributes', 'deleted', 'createdon'];

/** Campi, contenuto e TV (anche vuote, con nome e caption) di una risorsa; salva tutto in risorsa-<id>.json/.html */
$ispeziona = function (modResource $r, $etichetta) use ($modx, $CAMPI, $salva) {
    $t = $modx->getObject(modTemplate::class, $r->get('template'));
    $campi = [];
    foreach ($CAMPI as $k) $campi[$k] = $r->get($k);
    $campi['template_nome'] = $t ? $t->get('templatename') : '-';
    $tv = [];
    if ($t) {
        foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid' => $t->get('id')]) as $tt) {
            $v = $modx->getObject(modTemplateVar::class, $tt->get('tmplvarid'));
            if (!$v) continue;
            $tv[$v->get('name')] = ['caption' => $v->get('caption'), 'tipo' => $v->get('type'),
                'valore' => (string) $r->getTVValue($v->get('name')), 'grezzo' => (string) ($modx->getObject(
                    modTemplateVarResource::class, ['tmplvarid' => $v->get('id'), 'contentid' => $r->get('id')])?->get('value') ?? '')];
        }
    }
    $salva("risorsa-{$r->get('id')}.json", ['campi' => $campi, 'tv' => $tv]);
    $salva("risorsa-{$r->get('id')}.html", (string) $r->get('content'));
    printf("\n-- %s: id %d /%s\n", $etichetta, $r->get('id'), $r->get('uri'));
    foreach (['parent', 'template_nome', 'published', 'hidemenu', 'searchable', 'cacheable', 'richtext', 'menuindex',
              'pagetitle', 'longtitle', 'menutitle', 'description'] as $k) {
        echo "   $k: " . str_replace("\n", ' ', (string) $campi[$k]) . "\n";
    }
    echo "   content: " . strlen((string) $r->get('content')) . " byte\n";
    echo "   TV compilate:\n";
    foreach ($tv as $n => $d) if ($d['grezzo'] !== '') echo "     $n = " . mb_substr(str_replace("\n", '\n', $d['grezzo']), 0, 160) . "\n";
    if ($t) $salva('template-' . $t->get('templatename') . '.html', (string) $t->get('content'));
    return $r;
};
$perUri = fn($uri) => $modx->getObject(modResource::class, ['uri' => $uri]);

echo "== RISORSE DI RIFERIMENTO\n";
$rif = [];
foreach (['it/centri/clinica-dentale-porto-potenza-picena' => 'clinica Porto Potenza',
          'it/centri/clinica-dentale-villalba' => 'clinica Villalba',
          'it/centri/villa-dei-pini-civitanova' => 'Villa dei Pini Civitanova',
          'it/landing/clinica-dentale-civitanova' => 'landing clinica dentale',
          'it/strutture/cliniche-dentali' => 'elenco cliniche dentali',
          'it/centri' => 'cartella Centri',
          'it/news-e-media/news/2026/villa-dei-pini-inaugurata-la-clinica-dentale-kos' => 'news inaugurazione'] as $u => $e) {
    $r = $perUri($u);
    if (!$r) { echo "\n-- $e: /$u NON TROVATA\n"; continue; }
    $rif[$e] = $ispeziona($r, $e);
}

echo "\n== ALIAS LIBERO\n";
foreach (['clinica-dentale-villa-dei-pini', 'clinica-dentale-civitanova', 'clinica-dentale-villa-dei-pini-civitanova'] as $a) {
    $n = $modx->getCount(modResource::class, ['alias' => $a]);
    echo "  $a: " . ($n ? "USATO ($n)" : 'libero') . "\n";
    foreach ($modx->getIterator(modResource::class, ['alias' => $a]) as $r) echo "     id {$r->get('id')} /{$r->get('uri')}\n";
}

echo "\n== FIGLI DI /it/centri CON «dental» NEL TITOLO O ALIAS, E LORO ORDINE\n";
if (isset($rif['cartella Centri'])) {
    $q = $modx->newQuery(modResource::class, ['parent' => $rif['cartella Centri']->get('id')]);
    $q->sortby('menuindex');
    foreach ($modx->getIterator(modResource::class, $q) as $r) {
        $segna = stripos($r->get('alias') . $r->get('pagetitle'), 'dental') !== false ? ' <==' : '';
        printf("  %4d  id %4d  pub %d  menu %s  ric %d  tpl %3d  %s%s\n", $r->get('menuindex'), $r->get('id'),
            $r->get('published'), $r->get('hidemenu') ? 'nascosta' : 'visibile', $r->get('searchable'),
            $r->get('template'), $r->get('alias'), $segna);
    }
}

echo "\n== CHI CITA LE CLINICHE DENTALI (contenuti e chunk)\n";
$ids = array_map(fn($r) => $r->get('id'), array_filter([$rif['clinica Porto Potenza'] ?? null, $rif['clinica Villalba'] ?? null]));
$chiavi = array_merge(['clinica-dentale', 'cliniche-dentali'], array_map(fn($i) => "[[~$i]]", $ids));
foreach ($chiavi as $k) {
    foreach ($modx->getIterator(modResource::class, ['content:LIKE' => "%$k%"]) as $r) {
        echo "  risorsa {$r->get('id')} /{$r->get('uri')} (tpl {$r->get('template')}) contiene «$k»\n";
    }
    foreach ($modx->getIterator(modChunk::class, ['snippet:LIKE' => "%$k%"]) as $c) {
        echo "  chunk {$c->get('name')} contiene «$k»\n";
        $salva('chunk-' . $c->get('name') . '.html', (string) $c->get('snippet'));
    }
    foreach ($modx->getIterator(modTemplate::class, ['content:LIKE' => "%$k%"]) as $t) echo "  template {$t->get('templatename')} contiene «$k»\n";
}
// TV con valore che cita le cliniche (es. elenchi di id nelle pagine di categoria)
foreach ($ids as $i) {
    $q = $modx->newQuery(modTemplateVarResource::class);
    $q->where("FIND_IN_SET($i, REPLACE(`modTemplateVarResource`.`value`, ' ', '')) > 0 OR `modTemplateVarResource`.`value` = '$i'");
    foreach ($modx->getIterator(modTemplateVarResource::class, $q) as $v) {
        $tvo = $modx->getObject(modTemplateVar::class, $v->get('tmplvarid'));
        $ro = $modx->getObject(modResource::class, $v->get('contentid'));
        printf("  TV %s di id %d /%s = %s\n", $tvo ? $tvo->get('name') : $v->get('tmplvarid'), $v->get('contentid'),
            $ro ? $ro->get('uri') : '?', mb_substr((string) $v->get('value'), 0, 120));
    }
}

echo "\n== NEWS: idStruttura E NEWS ORFANE DI VILLA DEI PINI\n";
$tvS = $modx->getObject(modTemplateVar::class, ['name' => 'idStruttura']);
$orfane = ['avviata-la-chirurgia-oculistica-a-villa-pini', 'salute-della-donna-villa-dei-pini-rafforza-l-offerta',
    'la-donna-il-centro-di-tutti-al-centro-di-tutto', 'controllo-del-peso-e-del-diabete', 'a-villa-dei-pini-una-nuova-terapia-rigenerativa'];
$q = $modx->newQuery(modResource::class);
foreach ($orfane as $o) $q->orCondition(['alias:LIKE' => "$o%"]);
$q->orCondition(['pagetitle:LIKE' => '%dental%']);
$q->orCondition(['alias:LIKE' => '%dental%']);
$q->orCondition(['content:LIKE' => '%clinica dentale%']);
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $t = $modx->getObject(modTemplate::class, $r->get('template'));
    printf("  id %d  /%s\n     tpl %s  pub %d  publishedon %s  menu %s  ric %d  idStruttura «%s»\n", $r->get('id'), $r->get('uri'),
        $t ? $t->get('templatename') : '-', $r->get('published'), date('Y-m-d', (int) $r->get('publishedon')),
        $r->get('hidemenu') ? 'nascosta' : 'visibile', $r->get('searchable'), $tvS ? (string) $r->getTVValue('idStruttura') : '-');
}
if ($tvS && isset($rif['Villa dei Pini Civitanova'])) {
    $vdp = $rif['Villa dei Pini Civitanova']->get('id');
    $q = $modx->newQuery(modTemplateVarResource::class, ['tmplvarid' => $tvS->get('id')]);
    $q->where("FIND_IN_SET($vdp, REPLACE(`modTemplateVarResource`.`value`, ' ', '')) > 0");
    $n = 0; foreach ($modx->getIterator(modTemplateVarResource::class, $q) as $v) $n++;
    echo "  risorse con idStruttura che contiene $vdp (Villa dei Pini): $n (lo snippet contenutiStruttura ne mostra al massimo 12)\n";
}

echo "\n== IMPOSTAZIONI kos.* UTILI\n";
foreach ($modx->getIterator(modSystemSetting::class, ['key:LIKE' => 'kos.%']) as $s) {
    if (preg_match('/tpl_|reparti|struttura|landing|sitemap|breadcrumb/', $s->get('key'))) echo "  {$s->get('key')} = " . mb_substr((string) $s->get('value'), 0, 200) . "\n";
}

echo "\n== ELEMENTI USATI DALLE SCHEDE (salvati)\n";
foreach (['contenutiStruttura', 'galleryStruttura', 'kosSchema', 'myBreadcrumb', 'kosHreflang'] as $n) {
    $s = $modx->getObject(modSnippet::class, ['name' => $n]);
    echo "  snippet $n: " . ($s ? strlen((string) $s->get('snippet')) . ' byte' : 'assente') . "\n";
    if ($s) $salva("snippet-$n.php", (string) $s->get('snippet'));
}
foreach (['head-strutture', 'menu', 'footer', 'scripts', 'breadcrumb'] as $n) {
    $c = $modx->getObject(modChunk::class, ['name' => $n]);
    echo "  chunk $n: " . ($c ? strlen((string) $c->get('snippet')) . ' byte' : 'assente') . "\n";
    if ($c) $salva("chunk-$n.html", (string) $c->get('snippet'));
}
$sm = $perUri('sitemap.xml');
if ($sm) $salva('risorsa-sitemap.xml.txt', (string) $sm->get('content'));

echo "\n== FILE (dimensioni)\n";
foreach (['assets/img/strutture/clinica-dentale-villalba', 'assets/img/strutture/clinica-dentale-porto-potenza-picena',
          'assets/img/strutture/clinica-dentale-villa-dei-pini', 'assets/img/landing/clinica-dentale-civitanova',
          'assets/doc/strutture/clinica-dentale-villalba', 'assets/doc/strutture/clinica-dentale-porto-potenza-picena',
          'assets/img/header', 'assets/img/footer'] as $d) {
    $file = glob("$webroot/$d/*") ?: [];
    if (in_array($d, ['assets/img/header', 'assets/img/footer'], true)) $file = array_filter($file, fn($f) => stripos($f, 'dental') !== false || stripos($f, 'pini') !== false);
    echo "  /$d: " . (is_dir("$webroot/$d") ? count($file) . ' file' : 'assente') . "\n";
    foreach ($file as $f) printf("     %7d KB  %s\n", filesize($f) / 1024, basename($f));
}
echo "\nSalvato in $out/\n";
