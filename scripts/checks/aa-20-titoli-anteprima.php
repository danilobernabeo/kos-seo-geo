<?php
/**
 * AA-20 — anteprima in SOLA LETTURA dei <title> prima e dopo lo snippet titoloPagina, per tutte le risorse
 * pubblicate di anniazzurri.it. Non scrive nulla nel database.
 *
 * Uso (dalla cartella in cui è estratto l'archivio del repository):
 *   php scripts/checks/aa-20-titoli-anteprima.php /www
 * Scrive ~/aa-20-titoli-<AAAAMMGG>.csv (separatore ;) e stampa il riepilogo.
 *
 * Il titolo «prima» si ricava dall'espressione <title> della baseline del chunk head usato dal template;
 * il titolo «dopo» esegue shared/snippets/titoloPagina.php con i parametri del chunk nel repository.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-20-titoli-anteprima.php /www\n"); exit(1); }
$repo = dirname(__DIR__, 2);
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate};
$modx = modX::getInstance();
$modx->initialize('web');
$marchio = (string) $modx->getOption('seo.titolo_marchio', null, '') ?: 'Anni Azzurri';

// chunk/template → [espressione vecchia, parametri nuovi]
$titoloDi = function (string $file): ?string {
    if (!is_file($file)) return null;
    return preg_match('#<title>(.*?)</title>#s', file_get_contents($file), $m) ? trim($m[1]) : null;
};
$parametri = function (string $espr): array {
    preg_match_all('/&(\w+)=`([^`]*)`/', $espr, $m, PREG_SET_ORDER);
    $p = [];
    foreach ($m as $x) $p[$x[1]] = $x[2];
    return $p;
};
$vecchio = function (string $espr, modResource $r) use ($modx): string {
    $pt = (string) $r->get('pagetitle'); $lt = (string) $r->get('longtitle');
    switch (true) {
        case str_contains($espr, 'longtitle:isempty'): return ($lt !== '' ? $lt : $pt) . ' | Anni Azzurri';
        case str_contains($espr, 'contains=`Residenza Anni Azzurri`'):
            return (str_contains($pt, 'Residenza Anni Azzurri') ? str_replace('Anni Azzurri ', '', $pt) : $pt) . ' | Anni Azzurri';
        case str_starts_with($espr, '[[*longtitle]]'): return $lt . ' | Anni Azzurri';
        case str_starts_with($espr, '[[*pagetitle]] | '): return $pt . ' | Anni Azzurri';
        case str_starts_with($espr, '[[*pagetitle]] - '): return $pt . ' - ' . $modx->getOption('site_name');
        case !str_contains($espr, '[['): return $espr;
    }
    return '?? ' . $espr;
};
$snippet = "$repo/shared/snippets/titoloPagina.php";
$nuovo = function (array $p, modResource $r) use ($modx, $snippet, $marchio): string {
    $modx->resource = $r;
    $scriptProperties = $p + ['marchio' => $marchio];
    return html_entity_decode((string) (include $snippet), ENT_QUOTES | ENT_HTML5, 'UTF-8');
};

$headDi = [];   // template id → [nome, espr vecchia, espr nuova]
foreach ($modx->getIterator(modTemplate::class) as $t) {
    $c = (string) $t->get('content'); $nome = $t->get('templatename');
    if (preg_match('/<title>/', $c)) { $sorg = "templates/$nome.html"; }
    elseif (preg_match('/\[\[\$(head[\w-]*)/', $c, $m)) { $sorg = "chunks/{$m[1]}.html"; }
    else continue;
    $v = $titoloDi("$repo/anniazzurri/baseline/$sorg"); $n = $titoloDi("$repo/anniazzurri/elements/$sorg");
    if ($v === null || $n === null) continue;
    $headDi[$t->get('id')] = [$sorg, $v, $n];
}

$out = fopen(getenv('HOME') . '/aa-20-titoli-' . date('Ymd') . '.csv', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['id', 'uri', 'template', 'sorgente', 'ricercabile', 'prima', 'dopo', 'cambia', 'len dopo', 'longtitle ignorato (alias)'], ';');
$n = $cambiati = $lunghi = $alias = 0; $perSorgente = [];
$q = $modx->newQuery(modResource::class, ['published' => 1, 'deleted' => 0, 'context_key' => 'web']);
$q->sortby('id');
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $h = $headDi[$r->get('template')] ?? null;
    if (!$h) continue;
    [$sorg, $v, $nw] = $h;
    $prima = $vecchio($v, $r);
    $dopo = $nuovo($parametri($nw), $r);
    $isAlias = (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', trim((string) $r->get('longtitle')));
    $c = $prima !== $dopo;
    $n++; $cambiati += $c; $alias += $isAlias; $lunghi += mb_strlen($dopo) > 60;
    $perSorgente[$sorg][$c ? 1 : 0] = ($perSorgente[$sorg][$c ? 1 : 0] ?? 0) + 1;
    fputcsv($out, [$r->get('id'), $r->get('uri'), $r->get('template'), $sorg, (int) $r->get('searchable'), $prima, $dopo,
        $c ? 'sì' : '', mb_strlen($dopo), $isAlias ? 'sì' : ''], ';');
}
fclose($out);
echo "Risorse pubblicate con head noto: $n — titolo cambiato: $cambiati — oltre 60 caratteri dopo: $lunghi — longtitle-alias ignorati: $alias\n";
ksort($perSorgente);
foreach ($perSorgente as $s => $x) printf("  %-45s invariati %4d  cambiati %4d\n", $s, $x[0] ?? 0, $x[1] ?? 0);
echo "CSV: ~/aa-20-titoli-" . date('Ymd') . ".csv\n";
