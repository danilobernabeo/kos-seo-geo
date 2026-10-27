<?php
/**
 * AA-20 / KOS-21-23 — stato attuale di title, description e H1 di un elenco di URL. SOLA LETTURA.
 * Per ogni URL: risposta HTTP (redirect seguiti), <title>, meta description, H1 della pagina servita, canonical e robots,
 * più i campi della risorsa MODX corrispondente (id, template, pagetitle, longtitle, description, introtext, ricercabile).
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/checks/meta-stato.php /www anniazzurri/dati/aa-20/url-audit.txt
 *   php scripts/checks/meta-stato.php /www kosgroup/dati/kos-21-23/url-audit.txt
 * Output: ~/meta-stato-<data>.csv (separatore «;», UTF-8 con BOM) e un riepilogo a video.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$elenco = $argv[2] ?? '';
if ($webroot === '' || !is_file("$webroot/config.core.php") || !is_file($elenco)) {
    fwrite(STDERR, "Uso: php meta-stato.php /www <elenco-url.txt>\n"); exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$urls = array_values(array_filter(array_map('trim', file($elenco)), fn($u) => $u !== '' && $u[0] !== '#'));
$risorsa = $db->prepare("SELECT c.id, c.pagetitle, c.longtitle, c.description, c.introtext, c.searchable, c.published,
                                t.templatename FROM {$p}site_content c LEFT JOIN {$p}site_templates t ON t.id = c.template
                         WHERE c.uri IN (?, ?) AND c.deleted = 0 ORDER BY c.published DESC LIMIT 1");
$pulisci = fn($s) => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$len = fn($s) => mb_strlen((string) $s);

$out = getenv('HOME') . '/meta-stato-' . date('Ymd-His') . '.csv';
$h = fopen($out, 'w'); fwrite($h, "\xEF\xBB\xBF");
fputcsv($h, ['url', 'http', 'url finale', 'title', 'len title', 'description', 'len desc', 'n H1', 'H1', 'canonical', 'robots',
             'id', 'template', 'pagetitle', 'longtitle', 'description (campo)', 'introtext', 'ricercabile', 'pubblicata'], ';');
$tot = ['n' => 0, 'ok' => 0, 'titleLungo' => 0, 'descMancante' => 0, 'descLunga' => 0, 'h1No' => 0, 'h1Multi' => 0, 'redirect' => 0, 'errore' => 0];
$titoli = []; $descr = [];
foreach ($urls as $i => $u) {
    $tot['n']++;
    $ch = curl_init($u);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30, CURLOPT_USERAGENT => 'kos-seo-geo meta-stato', CURLOPT_ENCODING => '']);
    $html = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $finale = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    if ($finale !== $u) $tot['redirect']++;
    $title = $desc = $canon = $robots = ''; $h1 = [];
    if ($code === 200 && $html !== '') {
        $dom = new DOMDocument(); libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html); libxml_clear_errors();
        $xp = new DOMXPath($dom);
        $n = $xp->query('//title'); $title = $n->length ? $pulisci($n->item(0)->textContent) : '';
        $n = $xp->query('//meta[translate(@name,"DESCRIPTION","description")="description"]/@content'); $desc = $n->length ? $pulisci($n->item(0)->nodeValue) : '';
        $n = $xp->query('//link[@rel="canonical"]/@href'); $canon = $n->length ? trim($n->item(0)->nodeValue) : '';
        $n = $xp->query('//meta[@name="robots"]/@content'); $robots = $n->length ? trim($n->item(0)->nodeValue) : '';
        foreach ($xp->query('//h1') as $e) $h1[] = $pulisci($e->textContent);
        $tot['ok']++;
        if ($len($title) > 65) $tot['titleLungo']++;
        if ($desc === '') $tot['descMancante']++; elseif ($len($desc) > 155) $tot['descLunga']++;
        if (!$h1) $tot['h1No']++; elseif (count($h1) > 1) $tot['h1Multi']++;
        $titoli[mb_strtolower($title)][] = $u; if ($desc !== '') $descr[mb_strtolower($desc)][] = $u;
    } else $tot['errore']++;
    $path = trim((string) parse_url($finale ?: $u, PHP_URL_PATH), '/');
    $risorsa->execute([$path, $path . '/']);
    $r = $risorsa->fetch(PDO::FETCH_ASSOC) ?: [];
    fputcsv($h, [$u, $code, $finale === $u ? '' : $finale, $title, $len($title), $desc, $len($desc), count($h1), implode(' | ', $h1), $canon, $robots,
        $r['id'] ?? '', $r['templatename'] ?? '', $r['pagetitle'] ?? '', $r['longtitle'] ?? '', $pulisci($r['description'] ?? ''),
        mb_substr($pulisci(strip_tags((string) ($r['introtext'] ?? ''))), 0, 300), $r['searchable'] ?? '', $r['published'] ?? ''], ';');
    if (($i + 1) % 25 === 0) fwrite(STDERR, '  ' . ($i + 1) . '/' . count($urls) . "\n");
}
fclose($h);
$dupT = array_sum(array_map(fn($v) => count($v) > 1 ? count($v) : 0, $titoli));
$dupD = array_sum(array_map(fn($v) => count($v) > 1 ? count($v) : 0, $descr));
printf("== %d URL: %d con risposta 200, %d con redirect, %d errori\n", $tot['n'], $tot['ok'], $tot['redirect'], $tot['errore']);
printf("   title oltre 65 caratteri: %d · title duplicati: %d\n", $tot['titleLungo'], $dupT);
printf("   description mancanti: %d · oltre 155 caratteri: %d · duplicate: %d\n", $tot['descMancante'], $tot['descLunga'], $dupD);
printf("   H1 mancante: %d · H1 multipli: %d\n", $tot['h1No'], $tot['h1Multi']);
echo "== File: $out\n";
