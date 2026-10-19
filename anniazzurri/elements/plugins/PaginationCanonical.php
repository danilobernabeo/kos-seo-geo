<?php
/**
 * PaginationCanonical — anniazzurri.it
 * Evento: OnWebPagePrerender
 *
 * Pagine con elenco paginato (pdoPage, parametro "page"):
 * - pagina 1 (o ?page=1): canonical sulla pagina senza parametri;
 * - pagina N > 1: canonical su se stessa, "?page=N", senza altri parametri
 *   (utm, gclid... non entrano mai nel canonical);
 * - titolo "... - Pagina N" dalla pagina 2 in poi, per evitare titoli duplicati.
 * Le pagine successive restano indicizzabili: è la pratica raccomandata da Google
 * (niente canonical verso la pagina 1, che nasconderebbe gli articoli più vecchi).
 *
 * Base canonica dall'impostazione aa.canonical_base (stessa di aaSchema).
 * Sostituisce la versione disattivata, che usava host e query string della richiesta.
 */
if ($modx->event->name !== 'OnWebPagePrerender') return;

// id risorsa => parametro di pagina e formato del titolo
$pagine = [
    8 => ['param' => 'page', 'titolo' => '%s - Pagina %d'],   // News
];

$id = (int) $modx->resource->get('id');
if (!isset($pagine[$id])) return;
$conf = $pagine[$id];

$n = isset($_GET[$conf['param']]) ? (int) $_GET[$conf['param']] : 1;
if ($n < 1) $n = 1;

$base = rtrim($modx->getOption('aa.canonical_base', null, 'https://anniazzurri.it'), '/');
$uri  = $modx->resource->get('uri');               // es. "news/"
$url  = $base . '/' . ltrim($uri, '/');
if ($n > 1) $url .= '?' . $conf['param'] . '=' . $n;

$output = &$modx->resource->_output;
$tag = '<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />';

// un solo canonical: toglie quelli presenti e inserisce il nuovo prima di </head>
$output = preg_replace('/<link\b[^>]*\brel=["\']canonical["\'][^>]*>\s*/i', '', $output);
$output = preg_replace('/<\/head>/i', $tag . "\n</head>", $output, 1);

if ($n > 1) {
    $output = preg_replace_callback('/<title>(.*?)<\/title>/is', function ($m) use ($conf, $n) {
        $t = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        // "News | Anni Azzurri" -> "News - Pagina 3 | Anni Azzurri"
        $pos = strrpos($t, ' | ');
        $nuovo = $pos === false ? sprintf($conf['titolo'], $t, $n)
                                : sprintf($conf['titolo'], substr($t, 0, $pos), $n) . substr($t, $pos);
        return '<title>' . htmlspecialchars($nuovo, ENT_QUOTES, 'UTF-8') . '</title>';
    }, $output, 1);
}
return;
