<?php
/**
 * AssetVersion — kosgroup.com e anniazzurri.it (voci KOS-3, AA-14)
 * Evento: OnWebPagePrerender
 *
 * Aggiunge "?v=<data di modifica del file>" ai CSS e JS locali richiamati da <link> e <script>.
 * Quando un file cambia, cambia anche il suo indirizzo: il browser lo riscarica subito,
 * e la cache dei CSS/JS può durare mesi (regola web "expires 180d").
 *
 * - Solo file locali che esistono sul disco (percorsi relativi, assoluti o con l'host del sito).
 * - I link che hanno già un parametro (es. custom.css?v=20260921b) restano come sono.
 * - Nessuna modifica ai chunk: vale per tutte le pagine, anche quelle che verranno create.
 */
if ($modx->event->name !== 'OnWebPagePrerender') return;

$output = &$modx->resource->_output;
if (!is_string($output) || stripos($output, '<html') === false) return;

$base  = rtrim(MODX_BASE_PATH, '/') . '/';
$host  = strtolower((string) parse_url((string) $modx->getOption('site_url'), PHP_URL_HOST));
$nudo  = function ($h) { return preg_replace('/^www\./', '', strtolower((string) $h)); };
$cache = [];

$output = preg_replace_callback(
    '#(<(?:link|script)\b[^>]*?\b(?:href|src)\s*=\s*)(["\'])([^"\'?\#]+\.(?:css|js))(\?[^"\']*)?\2#i',
    function ($m) use ($base, $host, $nudo, &$cache) {
        if (!empty($m[4])) return $m[0];                         // già con parametri
        $url = $m[3];
        $path = $url;
        if (preg_match('#^(?:https?:)?//([^/]+)(/.*)$#i', $url, $x)) {
            if ($nudo($x[1]) !== $nudo($host)) return $m[0];     // risorsa esterna
            $path = $x[2];
        }
        $file = $base . ltrim(rawurldecode($path), '/');
        if (!isset($cache[$file])) $cache[$file] = is_file($file) ? filemtime($file) : 0;
        if (!$cache[$file]) return $m[0];
        return $m[1] . $m[2] . $url . '?v=' . $cache[$file] . $m[2];
    },
    $output
);
return;
