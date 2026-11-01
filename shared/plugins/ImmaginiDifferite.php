<?php
/**
 * ImmaginiDifferite — KOS-2 B (tempo di caricamento): caricamento differito delle immagini fuori schermo e
 * precaricamento degli sfondi CSS che fanno da elemento LCP. Pensato per kosgroup.com (su anniazzurri.it le
 * immagini passano già dallo snippet `immagine`, che scrive loading/fetchpriority).
 * Evento: OnWebPagePrerender (come AssetVersion: vale anche per le pagine in cache e per le immagini scritte nei
 * contenuti delle risorse, che nessun chunk controlla). Costo: una scansione dell'HTML, circa 1 ms su 200 KB (misura
 * in locale: PHP 8.3, pagina di 200 KB con 1.500 <div> e 60 <img>).
 *
 * Immagini: aggiunge loading="lazy" decoding="async" agli <img> che
 *   - stanno DOPO la testata del sito (primo </header>; senza testata, dall'inizio del <body>)
 *   - e DOPO le prime N immagini raster che seguono la testata (kosseogeo.img_differita_salta, default 3: la foto
 *     di testata, la coppia desktop/mobile, la prima foto della galleria restano come sono);
 * e non tocca mai un <img> che
 *   - ha già loading, fetchpriority o un caricatore JavaScript (data-src, data-srcset, data-lazy, classi lazyload,
 *     swiper-lazy, no-lazy): chi l'ha scritto ha già deciso;
 *   - sta dentro un carosello (classi swiper*, splide*, owl-carousel*, slick*, carousel*): le slide nascoste
 *     comparirebbero vuote durante lo scorrimento;
 *   - è un pixel di tracciamento (width o height 0/1), un'immagine data:, o sta in <noscript>, <script>, commenti.
 * Le icone SVG prima della soglia non consumano il conteggio (logo, frecce, icone dei pulsanti).
 * Per escludere un'immagine a mano: loading="eager" (immagine principale: meglio fetchpriority="high").
 *
 * Precaricamento: kosseogeo.img_preload, una regola per riga (o separate da «;»):
 *   .bg-pattern=/assets/img/bg-pattern.jpg            (classe)  oppure  #intro-pagina=/assets/img/x.jpg  (id)
 *   .bg-pattern=/assets/img/bg-pattern.jpg|(min-width: 992px)          (facoltativo: attributo media)
 * Se la pagina contiene un elemento con quella classe o quell'id, aggiunge nell'head, dopo l'ultimo foglio di stile,
 *   <link rel="preload" as="image" href="..." fetchpriority="high">
 * L'URL deve essere identico a quello che il CSS risolve (altrimenti il file si scarica due volte).
 *
 * Interruttore: kosseogeo.img_differita = 0 spegne tutto il plugin (ritorno indietro senza reinstallare).
 */
if ($modx->event->name !== 'OnWebPagePrerender') return;
if ((string) $modx->getOption('kosseogeo.img_differita', null, '1') === '0') return;

$output = &$modx->resource->_output;
if (!is_string($output) || stripos($output, '<body') === false || stripos($output, '<html') === false) return;

$salta = max(0, (int) $modx->getOption('kosseogeo.img_differita_salta', null, 3));

/* ---------- 1. immagini ---------- */
$corpo = stripos($output, '<body');
$body  = substr($output, $corpo);

// Caroselli: intervalli [inizio, fine) del <body> occupati da un contenitore con classe swiper*, splide*, ecc.
$zone = [];
if (preg_match_all('~<(div|section|ul|ol|li|article|aside|figure|nav)\b[^>]*?\sclass\s*=\s*["\'][^"\']*?(?<![\w-])(?:swiper|splide|owl-carousel|slick|carousel)~i',
        $body, $cc, PREG_OFFSET_CAPTURE)) {
    foreach ($cc[0] as $i => [$_, $inizio]) {
        if ($zone && $inizio < end($zone)[1]) continue;                      // annidato in una zona già trovata
        $tag = strtolower($cc[1][$i][0]);
        $fine = strlen($body);                                               // HTML non bilanciato: fino in fondo
        $livello = 0;
        $da = $inizio;
        while (preg_match('~<(/?)' . $tag . '\b[^>]*>~i', $body, $x, PREG_OFFSET_CAPTURE, $da)) {
            $da = $x[0][1] + strlen($x[0][0]);
            if ($x[1][0] === '/') { if (--$livello === 0) { $fine = $x[0][1]; break; } }
            elseif (substr($x[0][0], -2) !== '/>') $livello++;
        }
        $zone[] = [$inizio, $fine];
    }
}
$inZona = static function (int $pos) use ($zone): bool {
    foreach ($zone as [$a, $b]) if ($pos >= $a && $pos < $b) return true;
    return false;
};
$attributi = static function (string $tag): array {
    preg_match_all('~\s([\w:-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?~', substr($tag, 4), $aa, PREG_SET_ORDER);
    $r = [];
    foreach ($aa as $x) $r[strtolower($x[1])] = html_entity_decode(($x[2] ?? '') . ($x[3] ?? '') . ($x[4] ?? ''), ENT_QUOTES, 'UTF-8');
    return $r;
};

$dopoTestata = !preg_match('~</header\s*>~i', $body);                    // senza testata: dall'inizio del <body>
$contate = 0;                                                            // immagini raster dopo la testata

$nuovo = preg_replace_callback(
    '~<!--.*?-->|<(script|style|noscript|template|textarea)\b[^>]*>.*?</\1\s*>|</header\s*>|<img\b[^>]*>~is',
    function ($m) use (&$dopoTestata, &$contate, $salta, $inZona, $attributi) {
        [$t, $pos] = $m[0];
        if ($t[1] === '!' || ($m[1][0] ?? '') !== '') return $t;            // commento, script, noscript...
        if ($t[1] === '/') { $dopoTestata = true; return $t; }               // </header>
        if (!$dopoTestata) return $t;
        $a = $attributi($t);
        $src = $a['src'] ?? '';
        if (!preg_match('/\.svg(?:[?#]|$)/i', $src)) $contate++;
        if ($contate <= $salta) return $t;                                   // prime immagini dopo la testata
        if ($src === '' || stripos($src, 'data:') === 0) return $t;
        foreach (['loading', 'fetchpriority', 'data-src', 'data-srcset', 'data-lazy', 'data-lazy-src'] as $k) {
            if (isset($a[$k])) return $t;                                    // già deciso da chi l'ha scritto
        }
        if (preg_match('/(?<![\w-])(?:lazyload|lazy|swiper-lazy|no-lazy)(?![\w-])/i', $a['class'] ?? '')) return $t;
        foreach (['width', 'height'] as $k) {
            if (isset($a[$k]) && in_array(trim($a[$k]), ['0', '1', '0px', '1px'], true)) return $t;   // pixel di tracciamento
        }
        if ($inZona($pos)) return $t;                                        // dentro un carosello
        $aggiunta = ' loading="lazy"' . (isset($a['decoding']) ? '' : ' decoding="async"');
        return preg_replace('~\s*(/?)>$~', $aggiunta . '$1>', $t, 1);
    },
    $body, -1, $n, PREG_OFFSET_CAPTURE
);
if ($nuovo === null) {                       // errore di PCRE (pagina enorme): pagina lasciata com'è
    $modx->log(modX::LOG_LEVEL_WARN, '[ImmaginiDifferite] ' . preg_last_error_msg() . ' su ' . $modx->resource->get('uri'));
    return;
}
$output = substr($output, 0, $corpo) . $nuovo;

/* ---------- 2. precaricamento degli sfondi CSS ---------- */
$regole = trim((string) $modx->getOption('kosseogeo.img_preload', null, ''));
if ($regole === '') return;
$fineHead = stripos($output, '</head>');
if ($fineHead === false) return;
$pulito = preg_replace('~<!--.*?-->|<script\b[^>]*>.*?</script\s*>~is', '', substr($output, $fineHead));
$link = [];
foreach (preg_split('/[\r\n;]+/', $regole) as $r) {
    if (!preg_match('/^\s*([.#])([\w-]+)\s*=\s*([^|\s]+)\s*(?:\|\s*(.+?))?\s*$/', $r, $x)) continue;
    [, $tipo, $nome, $url, $media] = $x + [4 => ''];
    if (stripos($pulito, $nome) === false) continue;
    $q = preg_quote($nome, '~');
    $trovato = $tipo === '.'
        ? preg_match('~\sclass\s*=\s*(["\'])(?:(?!\1).)*?(?<![\w-])' . $q . '(?![\w-])~is', $pulito)
        : preg_match('~\sid\s*=\s*(["\'])' . $q . '\1~i', $pulito);
    if (!$trovato) continue;
    if (preg_match('~<link\b[^>]*rel\s*=\s*["\']preload["\'][^>]*href\s*=\s*["\']' . preg_quote($url, '~') . '["\']~i', substr($output, 0, $fineHead))) continue;
    $e = static fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $link[$url] = '<link rel="preload" as="image" href="' . $e($url) . '" fetchpriority="high"' . ($media !== '' ? ' media="' . $e($media) . '"' : '') . '>';
}
if (!$link) return;
$head = substr($output, 0, $fineHead);
$ins = "\n  " . implode("\n  ", $link);
// dopo l'ultimo foglio di stile dell'head (il CSS che blocca il rendering parte per primo), altrimenti prima di </head>
if (preg_match_all('~<link\b[^>]*rel\s*=\s*["\']stylesheet["\'][^>]*>~i', $head, $ss, PREG_OFFSET_CAPTURE)) {
    $ultimo = end($ss[0]);
    $pos = $ultimo[1] + strlen($ultimo[0]);
    $output = substr($output, 0, $pos) . $ins . substr($output, $pos);
} else {
    $output = $head . $ins . "\n" . substr($output, $fineHead);
}
return;
