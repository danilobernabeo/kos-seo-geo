<?php
/**
 * AA-1 / AA-6 — esportazione in SOLA LETTURA del contenuto delle risorse di alcuni template (default: schede
 * residenza, centri diurni, pagine regione e provincia), per studiare le varianti del markup e scrivere lo script di conversione.
 *
 * Uso:  php aa-esporta-contenuti.php /www [template,template,...]
 *       php aa-esporta-contenuti.php /www rsa,centro-diurno,regione,provincia
 *       php aa-esporta-contenuti.php /www rsa,centro-diurno,regione,provincia '--contiene=background|show-less'
 *         (in più: tutte le risorse il cui contenuto contiene uno dei testi)
 * Output in ~/aa-contenuti-<data>/:
 *   risorse/<id>.html   campo content così com'è nel database (tag MODX compresi)
 *   indice.json         id, pagetitle, uri, template, pubblicata, ricercabile, chunk richiamati, TV valorizzate
 *   chunk/<nome>.html   i chunk richiamati nei contenuti esportati (contatti, gallerie...)
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$nomi = array_filter(array_map('trim', explode(',', $argv[2] ?? 'rsa,centro-diurno,regione,provincia')));
if ($webroot === '') { fwrite(STDERR, "Uso: php aa-esporta-contenuti.php /www [template,...]\n"); exit(1); }
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modChunk, modTemplateVarResource, modTemplateVar};
$modx = modX::getInstance();
$modx->initialize('mgr');

$out = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/aa-contenuti-' . date('Ymd-His');
mkdir("$out/risorse", 0775, true);
mkdir("$out/chunk", 0775, true);

// &contiene: oltre ai template indicati, tutte le risorse il cui contenuto contiene uno dei testi (separati da |)
$contiene = [];
foreach ($argv as $a) if (str_starts_with($a, '--contiene=')) $contiene = array_filter(explode('|', substr($a, 11)));
$tutti = [];
foreach ($modx->getIterator(modTemplate::class) as $t) $tutti[$t->get('id')] = $t->get('templatename');
$tutti[0] = '(nessuno)';
$tpl = array_filter($tutti, fn($n) => in_array($n, $nomi, true));
if (!$tpl && !$contiene) { fwrite(STDERR, "Nessun template trovato tra: " . implode(', ', $nomi) . "\n"); exit(1); }
$tvNomi = [];
foreach ($modx->getIterator(modTemplateVar::class) as $tv) $tvNomi[$tv->get('id')] = $tv->get('name');

$indice = []; $chunkUsati = [];
$gruppo = $tpl ? [['template:IN' => array_keys($tpl)]] : [];
foreach ($contiene as $txt) $gruppo[] = [($gruppo ? 'OR:' : '') . 'content:LIKE' => '%' . $txt . '%'];
$q = $modx->newQuery(modResource::class);
$q->where(['deleted' => 0, 'context_key' => 'web', $gruppo]);
$q->sortby('uri');
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    if ($r->get('deleted') || $r->get('context_key') !== 'web') continue;   // sicurezza sul raggruppamento delle condizioni
    $c = (string) $r->get('content');
    file_put_contents("$out/risorse/{$r->get('id')}.html", $c);
    preg_match_all('/\[\[\!?\$([\w.-]+)/', $c, $m);
    $ch = array_values(array_unique($m[1]));
    foreach ($ch as $n) $chunkUsati[$n] = true;
    $tv = [];
    foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $r->get('id')]) as $v)
        if (trim((string) $v->get('value')) !== '') $tv[$tvNomi[$v->get('tmplvarid')] ?? $v->get('tmplvarid')] = mb_substr((string) $v->get('value'), 0, 200);
    $indice[] = ['id' => $r->get('id'), 'pagetitle' => $r->get('pagetitle'), 'longtitle' => $r->get('longtitle'),
        'uri' => $r->get('uri'), 'alias' => $r->get('alias'), 'template' => $tutti[$r->get('template')] ?? (string) $r->get('template'),
        'published' => (int) $r->get('published'), 'searchable' => (int) $r->get('searchable'),
        'editedon' => $r->get('editedon'), 'byte' => strlen($c), 'chunk' => $ch, 'tv' => $tv];
}
// Chunk richiamati (anche quelli richiamati a loro volta, un livello)
foreach (array_keys($chunkUsati) as $n) {
    $obj = $modx->getObject(modChunk::class, ['name' => $n]);
    if (!$obj) continue;
    $s = (string) $obj->get('snippet');
    file_put_contents("$out/chunk/$n.html", $s);
    if (preg_match_all('/\[\[\!?\$([\w.-]+)/', $s, $m)) foreach ($m[1] as $n2) {
        if (isset($chunkUsati[$n2]) || is_file("$out/chunk/$n2.html")) continue;
        $o2 = $modx->getObject(modChunk::class, ['name' => $n2]);
        if ($o2) file_put_contents("$out/chunk/$n2.html", (string) $o2->get('snippet'));
    }
}
file_put_contents("$out/indice.json", json_encode($indice, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo count($indice) . " risorse (" . implode(', ', $tpl) . ($contiene ? ' + contenuti con ' . implode('|', $contiene) : '') . "), " . count(glob("$out/chunk/*.html")) . " chunk. Output in $out/\n";
