<?php
/**
 * AA-17 — misura in SOLA LETTURA delle immagini nel testo delle news (campo content), per decidere se servirle
 * in WebP con un plugin su OnBeforeSaveWebPageCache.
 *
 * Uso:  php scripts/checks/aa-immagini-news.php /www [template,template,...]
 *       default: interna-articolo,albero-dei-ricordi,interna-speaker-per-un-giorno
 * Output: riepilogo a video e ~/aa-immagini-news-<data>.csv (una riga per immagine).
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-immagini-news.php /www [template,...]\n"); exit(1); }
$nomi = array_filter(array_map('trim', explode(',', $argv[2] ?? 'interna-articolo,albero-dei-ricordi,interna-speaker-per-un-giorno')));
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');

$tpl = [];
foreach ($modx->getIterator(modTemplate::class) as $t) if (in_array($t->get('templatename'), $nomi, true)) $tpl[$t->get('id')] = $t->get('templatename');
if (!$tpl) { fwrite(STDERR, "Nessun template trovato tra: " . implode(', ', $nomi) . "\n"); exit(1); }

$csv = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/aa-immagini-news-' . date('Ymd-His') . '.csv';
$fh = fopen($csv, 'w');
fputcsv($fh, ['id', 'pubblicata', 'template', 'pagetitle', 'src', 'tipo', 'kb', 'larghezza', 'altezza', 'in_picture', 'varianti_webp', 'alt']);

$t = ['risorse' => 0, 'con_img' => 0, 'img' => 0, 'locali' => 0, 'esterne' => 0, 'mancanti' => 0, 'picture' => 0,
      'byte' => 0, 'sopra100' => 0, 'sopra200' => 0, 'larghe' => 0, 'con_varianti' => 0, 'senza_alt' => 0];
$cartelle = []; $formati = [];
$q = $modx->newQuery(modResource::class, ['template:IN' => array_keys($tpl), 'deleted' => 0, 'context_key' => 'web']);
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $t['risorse']++;
    $c = (string) $r->get('content');
    if (!preg_match_all('/<img\b[^>]*>/i', $c, $m, PREG_OFFSET_CAPTURE)) continue;
    $t['con_img']++;
    foreach ($m[0] as [$tag, $pos]) {
        $t['img']++;
        $src = preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $tag, $s) ? html_entity_decode($s[1]) : '';
        $alt = preg_match('/\balt\s*=\s*"([^"]*)"/i', $tag, $a) ? $a[1] : null;
        if ($alt === null || trim($alt) === '') $t['senza_alt']++;
        // dentro un <picture>? (ultimo <picture> aperto prima dell'immagine e non ancora chiuso)
        $prima = substr($c, 0, $pos);
        $inPic = strripos($prima, '<picture') !== false && strripos($prima, '<picture') > (int) strripos($prima, '</picture>');
        if ($inPic) $t['picture']++;
        $path = preg_match('#^(https?:)?//(www\.)?anniazzurri\.it/#i', $src) ? (string) parse_url($src, PHP_URL_PATH) : $src;
        $tipo = 'esterna'; $kb = ''; $w = ''; $h = ''; $var = '';
        if (!preg_match('#^(https?:)?//#i', $path) && !str_starts_with($path, 'data:') && $path !== '' && !str_contains($path, '[[')) {
            $rel = ltrim(rawurldecode((string) preg_replace('/[?#].*$/', '', $path)), '/');
            $file = "$webroot/$rel";
            if (is_file($file)) {
                $tipo = 'locale'; $t['locali']++;
                $b = filesize($file); $t['byte'] += $b; $kb = (int) round($b / 1024);
                if ($b > 100 * 1024) $t['sopra100']++;
                if ($b > 200 * 1024) $t['sopra200']++;
                $d = @getimagesize($file);
                if ($d) { [$w, $h] = $d; if ($w > 1300) $t['larghe']++; }
                $cartelle[dirname($rel)] = ($cartelle[dirname($rel)] ?? 0) + 1;
                $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION)); $formati[$ext] = ($formati[$ext] ?? 0) + 1;
                $base = preg_replace('#^assets/#', '', $rel);
                $var = count(glob("$webroot/assets/cache-img/" . dirname($base) . '/' . glob_escape(pathinfo($base, PATHINFO_FILENAME)) . '-*.webp') ?: []);
                if ($var) $t['con_varianti']++;
            } else { $tipo = 'mancante'; $t['mancanti']++; }
        } else { $t['esterne']++; }
        fputcsv($fh, [$r->get('id'), (int) $r->get('published'), $tpl[$r->get('template')], $r->get('pagetitle'), $src, $tipo, $kb, $w, $h, $inPic ? 1 : 0, $var, $alt ?? '(manca)']);
    }
}
fclose($fh);

function glob_escape(string $s): string { return preg_replace('/([*?\[\]])/', '[$1]', $s); }

arsort($cartelle); arsort($formati);
printf("Template: %s\n", implode(', ', $tpl));
printf("Risorse: %d, con immagini nel testo: %d\n", $t['risorse'], $t['con_img']);
printf("Immagini nel testo: %d (locali %d, esterne %d, file mancanti %d, già in <picture> %d)\n", $t['img'], $t['locali'], $t['esterne'], $t['mancanti'], $t['picture']);
printf("Peso delle locali: %.1f MB, media %d KB; sopra 100 KB: %d; sopra 200 KB: %d; più larghe di 1300 px: %d\n",
    $t['byte'] / 1048576, $t['locali'] ? (int) round($t['byte'] / 1024 / $t['locali']) : 0, $t['sopra100'], $t['sopra200'], $t['larghe']);
printf("Con varianti WebP già generate: %d; senza alt: %d\n", $t['con_varianti'], $t['senza_alt']);
echo "Formati: " . implode(', ', array_map(fn($k, $v) => "$k $v", array_keys($formati), $formati)) . "\n";
echo "Cartelle principali:\n";
foreach (array_slice($cartelle, 0, 12, true) as $k => $v) printf("  %5d  %s\n", $v, $k);
echo "Dettaglio: $csv\n";
