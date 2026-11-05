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
 * WebP (KOS-2 C, interruttore kosseogeo.img_webp, default 0): ogni <img> con un JPG o PNG del sito che ha già le
 * varianti della libreria comune (assets/cache-img, generate da scripts/immagini/ottimizza-immagini.php --solo-varianti
 * o dal plugin ImmaginiCaricamento) viene racchiuso in
 *   <picture style="display:contents"><source type="image/webp" srcset="…-480.webp 480w, …" sizes="…"><img …invariato…></picture>
 * display:contents: il <picture> non crea un riquadro proprio, l'<img> si impagina come prima (flex, griglie, larghezze).
 * Restano da collaudare solo i selettori CSS con «>» verso l'immagine (es. «.box > img»), che il nuovo genitore interrompe.
 * L'<img> resta identico (src, classi, attributi): i browser senza WebP e gli script che lo cercano lo trovano come prima.
 * sizes: quello dell'<img> se c'è, altrimenti «(max-width: Wpx) 100vw, Wpx» con W = larghezza del file (al massimo 2400):
 * il browser sceglie la variante vicina alla dimensione mostrata, mai più grande dell'originale.
 * Non tocca: immagini già dentro un <picture> o con srcset, data-src e simili, SVG/GIF/esterne, immagini senza varianti,
 * e (salvo kosseogeo.img_webp_caroselli = 1) quelle dentro un carosello. Le varianti trovate restano in cache MODX
 * (kosseogeo/webp) fino a quando cambia il file: nessuna lettura delle immagini a ogni richiesta.
 * Richiede core/components/kosseogeo/Immagini.php; senza, la parte WebP non fa nulla.
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

/* WebP: libreria comune e varianti già generate (cache MODX per file e data di modifica) */
$webp = null;
if ((string) $modx->getOption('kosseogeo.img_webp', null, '0') === '1' && is_file(MODX_CORE_PATH . 'components/kosseogeo/Immagini.php')) {
    require_once MODX_CORE_PATH . 'components/kosseogeo/Immagini.php';
    $lib = new \KosSeoGeo\Immagini(MODX_BASE_PATH, ['solo_webp' => (string) $modx->getOption('kosseogeo.img_solo_webp', null, '0') === '1']);
    $caroselli = (string) $modx->getOption('kosseogeo.img_webp_caroselli', null, '0') === '1';
    $memo = [];
    $webp = function (string $t, int $pos, array $a) use ($lib, $modx, $inZona, $caroselli, &$memo): string {
        $src = $a['src'] ?? '';
        if ($src === '' || !preg_match('/\.(?:jpe?g|png)(?:[?#]|$)/i', $src)) return $t;
        foreach (['srcset', 'data-src', 'data-srcset', 'data-lazy', 'data-lazy-src'] as $k) if (isset($a[$k])) return $t;
        if (!$caroselli && $inZona($pos)) return $t;
        if (preg_match('#^(?:https?:)?//#i', $src)) {
            $host = parse_url((strpos($src, '//') === 0 ? 'https:' : '') . $src, PHP_URL_HOST);
            if (strcasecmp((string) $host, (string) parse_url($modx->getOption('site_url'), PHP_URL_HOST)) !== 0) return $t;
        }
        $file = $lib->percorso(preg_replace('/[?#].*$/', '', $src));
        if ($file === null) return $t;
        if (!isset($memo[$file])) {
            $chiave = 'webp/' . md5($file);
            $c = $modx->cacheManager->get($chiave, ['cache_key' => 'kosseogeo']);
            $mt = @filemtime($file);
            if (!is_array($c) || ($c['mt'] ?? 0) !== $mt) {
                $v = array_values(array_filter($lib->varianti($file, false), fn($x) => is_file($x['webp'])));
                $c = ['mt' => $mt, 'set' => $v ? implode(', ', array_map(fn($x) => $lib->url($x['webp']) . ' ' . $x['w'] . 'w', $v)) : '',
                      'w' => $v ? end($v)['w'] : 0];
                $modx->cacheManager->set($chiave, $c, 0, ['cache_key' => 'kosseogeo']);
            }
            $memo[$file] = $c;
        }
        $c = $memo[$file];
        if ($c['set'] === '') return $t;
        $e = static fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $sizes = $a['sizes'] ?? '(max-width: ' . $c['w'] . 'px) 100vw, ' . $c['w'] . 'px';
        return '<picture style="display:contents"><source type="image/webp" srcset="' . $e($c['set']) . '" sizes="' . $e($sizes) . '">' . $t . '</picture>';
    };
}
$inPicture = 0;

$nuovo = preg_replace_callback(
    '~<!--.*?-->|<(script|style|noscript|template|textarea)\b[^>]*>.*?</\1\s*>|</header\s*>|</?picture\b[^>]*>|<img\b[^>]*>~is',
    function ($m) use (&$dopoTestata, &$contate, &$inPicture, $salta, $inZona, $attributi, $webp) {
        [$t, $pos] = $m[0];
        if ($t[1] === '!' || ($m[1][0] ?? '') !== '') return $t;            // commento, script, noscript...
        if (stripos($t, '<picture') === 0) { $inPicture++; return $t; }
        if (stripos($t, '</picture') === 0) { $inPicture = max(0, $inPicture - 1); return $t; }
        if ($t[1] === '/') { $dopoTestata = true; return $t; }               // </header>
        $a = $attributi($t);
        // WebP sul tag così com'è (prima o dopo l'eventuale loading="lazy" qui sotto: l'<img> interno è lo stesso)
        $avvolgi = ($webp && !$inPicture) ? fn(string $x) => $webp($x, $pos, $a) : fn(string $x) => $x;
        return $avvolgi((function () use ($t, $pos, $a, &$dopoTestata, &$contate, $salta, $inZona) {
        if (!$dopoTestata) return $t;
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
        })());
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
        ? preg_match('~\sclass\s*=\s*(["\'])(?:(?!\1).)*?(?<![\w-])' . $q . '(?![\w-])~is', $pulito, $mm, PREG_OFFSET_CAPTURE)
        : preg_match('~\sid\s*=\s*(["\'])' . $q . '\1~i', $pulito, $mm, PREG_OFFSET_CAPTURE);
    if (!$trovato) continue;
    // Solo se lo sfondo viene prima della prima immagine del contenuto (dopo la testata): altrimenti l'elemento più
    // grande in alto è un'<img> e il precaricamento ad alta priorità le ruberebbe banda (collaudo stage 1/11:
    // visione-valori-missione, cura-delle-persone, news-e-media peggiorate).
    $dopoHeader = stripos($pulito, '</header>');
    if ($dopoHeader !== false && preg_match('~<img\b(?![^>]*\.svg)~i', $pulito, $im, PREG_OFFSET_CAPTURE, $dopoHeader)
        && $im[0][1] < $mm[0][1]) continue;
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
