<?php
/**
 * AA-1 (immagini di sfondo) e AA-6 (H2 duplicati) — ispezione in SOLA LETTURA di anniazzurri.it.
 *
 * Uso (in SSH sul cloud; legge il database e scarica le pagine pubbliche, non scrive nulla):
 *   php aa-ispezione-sprint2.php /www https://anniazzurri.it [pagine per template, default 3]
 * Su uno stage protetto: AUTH='kos:password' php aa-ispezione-sprint2.php /www https://tcm7b0i8.modx.dev
 *
 * Per un campione di pagine pubblicate e ricercabili di ogni template:
 *   - struttura dei titoli H1-H3, H2 ripetuti, titoli vuoti, titoli «Guarda di più / Guarda meno»;
 *   - immagini di sfondo (attributo style, blocchi <style>, data-bg) con elemento, classe e origine probabile
 *     (chunk, template o TV che contiene la stessa espressione background-image);
 *   - <img> presenti e quante senza testo alternativo.
 * Output in ~/aa-ispezione-<data>/: riepilogo.txt, pagine.csv, sfondi.csv, titoli.txt.
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$base    = rtrim($argv[2] ?? '', '/');
$perTpl  = max(1, (int) ($argv[3] ?? 3));
if ($webroot === '' || !preg_match('#^https?://#', $base)) {
    fwrite(STDERR, "Uso: php aa-ispezione-sprint2.php /www https://anniazzurri.it [pagine per template]\n"); exit(1);
}
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modChunk, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

$out = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/aa-ispezione-' . date('Ymd-His');
mkdir($out, 0775, true);

/* ---------- origini possibili delle immagini di sfondo ---------- */
// Ogni espressione url(...) dentro un background in chunk, template e valori TV diventa un'espressione
// regolare (i tag MODX valgono «qualunque testo»): servirà a dire da dove viene ogni sfondo trovato.
$origini = [];
$raccogli = function (string $testo, string $dove) use (&$origini) {
    if (!preg_match_all('/background(?:-image)?\s*:\s*url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $testo, $m)) return;
    foreach (array_unique($m[1]) as $u) {
        $pezzi = preg_split('/\[\[.*?\]\]/s', $u);
        $re = '#' . implode('.+', array_map(fn($p) => preg_quote($p, '#'), $pezzi)) . '$#i';
        $letterale = strlen(implode('', $pezzi));
        $origini[] = ['re' => $re, 'dove' => $dove, 'espr' => $u, 'peso' => $letterale];
    }
};
foreach ($modx->getIterator(modChunk::class) as $c) $raccogli((string) $c->get('snippet'), 'chunk ' . $c->get('name'));
$TPL = [];
foreach ($modx->getIterator(modTemplate::class) as $t) {
    $TPL[$t->get('id')] = $t->get('templatename');
    $raccogli((string) $t->get('content'), 'template ' . $t->get('templatename'));
}
$tvNomi = [];
foreach ($modx->getIterator(modTemplateVar::class) as $tv) $tvNomi[$tv->get('id')] = $tv->get('name');
$origine = function (string $url, string $pagina) use (&$origini): string {
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? $url);
    $cand = array_filter($origini, fn($o) => preg_match($o['re'], $url) || preg_match($o['re'], $path));
    if (!$cand) return '';
    usort($cand, fn($a, $b) => $b['peso'] <=> $a['peso']);
    $migliore = $cand[0]['peso'];
    $dove = array_unique(array_map(fn($o) => $o['dove'], array_filter($cand, fn($o) => $o['peso'] === $migliore)));
    return implode(' | ', array_slice($dove, 0, 4)) . (count($dove) > 4 ? ' (+' . (count($dove) - 4) . ')' : '');
};

/* ---------- campione di pagine ---------- */
$q = $modx->newQuery(modResource::class, ['published' => 1, 'deleted' => 0, 'searchable' => 1, 'context_key' => 'web',
    'class_key:IN' => ['MODX\\Revolution\\modDocument', 'modDocument']]);
$q->sortby('template'); $q->sortby('editedon', 'DESC');
$campione = []; $perTemplate = [];
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $t = (int) $r->get('template');
    $perTemplate[$t] = ($perTemplate[$t] ?? 0) + 1;
    if (($perTemplate[$t]) > $perTpl) continue;
    $uri = (string) $r->get('uri');
    $campione[] = ['id' => $r->get('id'), 'tpl' => $TPL[$t] ?? "($t)", 'tplId' => $t,
        'url' => $base . '/' . ($r->get('id') == $modx->getOption('site_start') ? '' : rtrim($uri, '/'))];
}
echo "== " . count($campione) . " pagine da " . count($perTemplate) . " template. Output in $out/\n";

/* ---------- scaricamento e analisi ---------- */
$auth = getenv('AUTH') ?: '';
$ctx = stream_context_create(['http' => ['timeout' => 25, 'ignore_errors' => true, 'user_agent' => 'kos-seo-geo ispezione AA-1 AA-6',
    'header' => $auth !== '' ? 'Authorization: Basic ' . base64_encode($auth) : '']]);
$pulisci = fn($s) => trim(preg_replace('/\s+/u', ' ', (string) $s));
$decorativa = fn(string $u) => (bool) preg_match('#^(https?://[^/]+)?/assets/[^/]+\.(svg|png|gif)(\?|$)#i', $u)
    || (bool) preg_match('#hubspot|facebook\.com/tr|doubleclick|virtual-tour/.+/tiles/#i', $u);

$righePag = []; $righeSfondi = []; $titoli = '';
$h2Sito = []; $sfondiTpl = []; $statTpl = [];
foreach ($campione as $k => $p) {
    echo sprintf("  [%d/%d] %s\n", $k + 1, count($campione), $p['url']);
    $html = @file_get_contents($p['url'], false, $ctx);
    sleep(1);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int) $m[1];
    if ($html === false || $code !== 200) { $righePag[] = [$p['id'], $p['url'], $p['tpl'], "HTTP $code"]; continue; }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    // Titoli
    $hs = []; $cnt = ['h1' => 0, 'h2' => 0, 'h3' => 0]; $vuoti = 0; $guarda = 0;
    foreach ($xp->query('//h1|//h2|//h3') as $h) {
        $tag = strtolower($h->nodeName); $txt = $pulisci($h->textContent);
        $cnt[$tag]++;
        if ($txt === '') $vuoti++;
        if (preg_match('/^guarda (di )?(più|piu|meno)/iu', $txt)) $guarda++;
        $hs[] = [$tag, $txt];
    }
    $testiH2 = array_map(fn($x) => mb_strtolower($x[1]), array_filter($hs, fn($x) => $x[0] === 'h2'));
    $dup = array_filter(array_count_values($testiH2), fn($n) => $n > 1);
    foreach (array_unique($testiH2) as $t) $h2Sito[$t] = ($h2Sito[$t] ?? 0) + 1;
    $titoli .= "\n## {$p['url']}  [{$p['tpl']}]\n";
    foreach ($hs as [$tag, $txt]) $titoli .= str_repeat('  ', (int) $tag[1] - 1) . strtoupper($tag) . ' ' . ($txt === '' ? '(vuoto)' : mb_substr($txt, 0, 110)) . "\n";

    // Immagini di sfondo
    $sfondi = [];
    foreach ($xp->query('//*[@style or @data-bg or @data-background or @data-src-bg]') as $el) {
        $attr = $el->getAttribute('style') . ' ' . $el->getAttribute('data-bg') . ' ' . $el->getAttribute('data-background') . ' ' . $el->getAttribute('data-src-bg');
        if (preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $attr, $m)) foreach ($m[1] as $u) $sfondi[] = [$u, $el->nodeName, $el->getAttribute('class')];
        elseif (preg_match('/^\s*(\/|https?:)\S+\.(jpe?g|png|webp|avif|gif|svg)\s*$/i', trim($el->getAttribute('data-bg') . $el->getAttribute('data-background'))))
            $sfondi[] = [trim($el->getAttribute('data-bg') . $el->getAttribute('data-background')), $el->nodeName, $el->getAttribute('class')];
    }
    foreach ($xp->query('//style') as $st) if (preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $st->textContent, $m))
        foreach ($m[1] as $u) if (!preg_match('/\.(woff2?|ttf|eot|otf)(\?|#|$)/i', $u)) $sfondi[] = [$u, 'style', '(blocco <style>)'];
    $contenuto = 0;
    foreach ($sfondi as [$u, $tag, $cls]) {
        $dec = $decorativa($u);
        if (!$dec) $contenuto++;
        $orig = $origine($u, $p['url']);
        $righeSfondi[] = [$p['id'], $p['url'], $p['tpl'], $u, $dec ? 'decorativa' : 'contenuto', $tag, $cls, $orig];
        if (!$dec) $sfondiTpl[$p['tpl']][$orig ?: '(origine non trovata: contenuto della risorsa?)'] = true;
    }

    // <img>
    $img = 0; $senzaAlt = 0;
    foreach ($xp->query('//img') as $im) { $img++; if (trim($im->getAttribute('alt')) === '') $senzaAlt++; }

    $righePag[] = [$p['id'], $p['url'], $p['tpl'], 200, $cnt['h1'], $cnt['h2'], $cnt['h3'],
        implode(' / ', array_map(fn($t, $n) => "$t ×$n", array_keys($dup), $dup)), $vuoti, $guarda, count($sfondi), $contenuto, $img, $senzaAlt];
    $s = &$statTpl[$p['tpl']];
    $s['pagine'] = ($s['pagine'] ?? 0) + 1;
    $s['h1x'] = ($s['h1x'] ?? 0) + ($cnt['h1'] !== 1 ? 1 : 0);
    $s['dup'] = ($s['dup'] ?? 0) + ($dup ? 1 : 0);
    $s['guarda'] = ($s['guarda'] ?? 0) + ($guarda ? 1 : 0);
    $s['sfondi'] = ($s['sfondi'] ?? 0) + $contenuto;
    $s['senzaAlt'] = ($s['senzaAlt'] ?? 0) + $senzaAlt;
    unset($s);
}

/* ---------- output ---------- */
$csv = function (string $f, array $int, array $righe) {
    $h = fopen($f, 'w'); fwrite($h, "\xEF\xBB\xBF"); fputcsv($h, $int, ';'); foreach ($righe as $r) fputcsv($h, $r, ';'); fclose($h);
};
$csv("$out/pagine.csv", ['risorsa', 'pagina', 'template', 'http', 'H1', 'H2', 'H3', 'H2 ripetuti', 'titoli vuoti',
    'titoli Guarda di più/meno', 'sfondi', 'sfondi di contenuto', 'img', 'img senza alt'], $righePag);
$csv("$out/sfondi.csv", ['risorsa', 'pagina', 'template', 'immagine', 'tipo', 'elemento', 'classe', 'origine probabile'], $righeSfondi);
file_put_contents("$out/titoli.txt", $titoli);

$r = "Ispezione AA-1 / AA-6 su $base — " . date('d/m/Y H:i') . "\nPagine: " . count($campione) . " (max $perTpl per template)\n\n";
$r .= sprintf("%-38s %4s %6s %6s %7s %7s %8s\n", 'template', 'pag', 'H1≠1', 'H2dup', 'Guarda', 'sfondi', 'img-alt');
ksort($statTpl);
foreach ($statTpl as $t => $s) $r .= sprintf("%-38s %4d %6d %6d %7d %7d %8d\n", mb_substr($t, 0, 38), $s['pagine'], $s['h1x'], $s['dup'], $s['guarda'], $s['sfondi'], $s['senzaAlt']);
$r .= "\nH2 più ripetuti tra le pagine del campione (testo: pagine):\n";
arsort($h2Sito);
foreach (array_slice($h2Sito, 0, 25, true) as $t => $n) if ($n > 1) $r .= sprintf("  %3d  %s\n", $n, mb_substr($t, 0, 90));
$r .= "\nOrigini delle immagini di sfondo di contenuto, per template:\n";
ksort($sfondiTpl);
foreach ($sfondiTpl as $t => $o) $r .= "  $t\n" . implode('', array_map(fn($x) => "      $x\n", array_keys($o)));
file_put_contents("$out/riepilogo.txt", $r);
echo "\n$r\nFile: $out/{riepilogo.txt,pagine.csv,sfondi.csv,titoli.txt}\n";
