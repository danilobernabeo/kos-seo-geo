<?php
/**
 * AA-8 — diagnosi in SOLA LETTURA del JSON-LD di una risorsa di anniazzurri.it (nato per la Residenza San Lorenzo,
 * risorsa 64, senza blocco JSON-LD nella verifica del 30/10). Non scrive nulla nel database né nella cache.
 *
 * Uso (dalla cartella in cui è estratto l'archivio del repository):
 *   php scripts/checks/aa-schema-risorsa.php /www 64
 * Stampa: dati della risorsa (modifica, template, cache), snippet aaSchema (statico, file sorgente raggiungibile),
 * file di cache della pagina (contiene o no il JSON-LD), esecuzione dello snippet con tutti gli avvisi PHP.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$id = (int) ($argv[2] ?? 0);
if (!is_file("$webroot/config.core.php") || $id <= 0) { fwrite(STDERR, "Uso: php aa-schema-risorsa.php /www <id>\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modSnippet, modTemplate, modUser};
use MODX\Revolution\Sources\modMediaSource;
$modx = modX::getInstance();
$modx->initialize('web');

$r = $modx->getObject(modResource::class, $id);
if (!$r) { echo "Risorsa $id assente\n"; exit(2); }
$utente = fn($u) => ($o = $modx->getObject(modUser::class, (int) $u)) ? $o->get('username') : "#$u";
$data = fn($t) => $t ? date('Y-m-d H:i:s', is_numeric($t) ? (int) $t : strtotime((string) $t)) : '-';
$tpl = $modx->getObject(modTemplate::class, (int) $r->get('template'));
echo "== Risorsa $id: " . $r->get('pagetitle') . ' (' . $r->get('uri') . ")\n";
echo '   template ' . $r->get('template') . ' ' . ($tpl ? $tpl->get('templatename') : '?') . ', pubblicata ' . (int) $r->get('published')
   . ', cacheable ' . (int) $r->get('cacheable') . ', ricercabile ' . (int) $r->get('searchable') . "\n";
echo '   modificata ' . $data($r->get('editedon')) . ' da ' . $utente($r->get('editedby'))
   . ', pubblicata il ' . $data($r->get('publishedon')) . "\n";

echo "== Snippet aaSchema\n";
$s = $modx->getObject(modSnippet::class, ['name' => 'aaSchema']);
if (!$s) { echo "   ASSENTE\n"; }
else {
    echo '   statico ' . (int) $s->get('static') . ', media source ' . $s->get('source') . ', file «' . $s->get('static_file') . "»\n";
    $file = '';
    if ($s->get('static')) {
        $ms = $modx->getObject(modMediaSource::class, (int) $s->get('source'));
        if ($ms) {
            $ms->initialize();
            $prop = $ms->getProperties();
            $base = (string) ($prop['basePath']['value'] ?? '');
            $rel = !empty($prop['basePathRelative']['value']);
            $base = $rel ? MODX_BASE_PATH . ltrim($base, '/') : $base;
            $file = rtrim($base, '/') . '/' . ltrim((string) $s->get('static_file'), '/');
            echo "   media source «" . $ms->get('name') . "» basePath $base\n";
        }
        echo '   file ' . $file . ': ' . (is_file($file) ? 'presente, ' . filesize($file) . ' byte, modificato ' . date('Y-m-d H:i:s', filemtime($file)) : 'NON TROVATO') . "\n";
    }
    echo '   codice nel database: ' . strlen((string) $s->get('snippet')) . " byte\n";
}

echo "== Cache della pagina\n";
$trovati = glob(MODX_CORE_PATH . "cache/resource/web/resources/$id.cache.php") ?: [];
if (!$trovati) echo "   nessun file di cache (la pagina si rigenera alla prossima visita)\n";
foreach ($trovati as $f) {
    $c = (string) file_get_contents($f);
    echo "   $f: " . date('Y-m-d H:i:s', filemtime($f)) . ', JSON-LD ' . substr_count($c, 'application/ld+json')
       . ', tag [[aaSchema]] non elaborati ' . substr_count($c, '[[aaSchema') . "\n";
}

echo "== Esecuzione di aaSchema sulla risorsa\n";
$avvisi = [];
set_error_handler(function ($n, $m, $f, $l) use (&$avvisi) { $avvisi[] = "$m @ " . basename($f) . ":$l"; return true; });
$modx->resource = $r;
$modx->resourceIdentifier = $id;
$modx->elementCache = [];
try { $out = (string) $modx->runSnippet('aaSchema'); }
catch (\Throwable $e) { $out = ''; $avvisi[] = 'ECCEZIONE ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(); }
restore_error_handler();
echo '   output ' . strlen($out) . ' byte, JSON-LD ' . substr_count($out, 'application/ld+json') . "\n";
foreach (array_unique($avvisi) as $a) echo "   avviso: $a\n";
if ($out !== '') {
    $j = json_decode((string) preg_replace('#^.*?<script[^>]*>|</script>.*$#s', '', $out), true);
    echo '   nodi del @graph: ' . (is_array($j) ? implode(', ', array_map(fn($n) => is_array($n['@type'] ?? '') ? implode('+', $n['@type']) : ($n['@type'] ?? '?'), $j['@graph'] ?? [])) : 'JSON NON VALIDO: ' . json_last_error_msg()) . "\n";
}
