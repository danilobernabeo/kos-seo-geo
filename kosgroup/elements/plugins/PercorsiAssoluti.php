<?php
/**
 * PercorsiAssoluti — kosgroup.com, scansione del 26/11/2026 (N4): percorsi relativi nell'HTML.
 *
 * Il sito ha <base href="https://kosgroup.com/">: un src="assets/img/x.jpg" o un href="assets/doc/x.pdf" si risolve
 * oggi in https://kosgroup.com/assets/... e funziona, ma solo grazie al <base>. Chi lo ignora (alcuni crawler e lettori
 * di feed, anteprime, copie della pagina) cerca il file sotto il percorso della pagina e non lo trova. Nella scansione:
 * 3.006 immagini su 787 pagine e 2 link a PDF. Le fonti sono molte: valori delle TV immagine stampati così come sono
 * salvati (la sorgente media non viene applicata all'uscita: scripts/checks/tv-uscita.php), chunk e template con percorsi
 * scritti a mano, immagini inserite nei testi con TinyMCE (il media browser scrive assets/...).
 *
 * Il plugin riscrive nell'HTML in uscita i percorsi relativi alla radice in percorsi assoluti: «assets/...» → «/assets/...».
 * Con un <base href> sulla radice del sito le due forme si risolvono nello stesso indirizzo, quindi per il browser non
 * cambia nulla; senza <base>, o con un <base> che non punta alla radice, il plugin non tocca la pagina.
 *
 * Attributi: src, href, poster, action, data-src (un URL) e srcset, data-srcset (elenco di candidati), in tutti i tag,
 * compreso il tag di apertura di <script>. Riscrive solo i valori che iniziano con una lettera, una cifra o «_» e non
 * hanno uno schema (http:, mailto:, tel:, javascript:, data:...): restano come sono «/...», «//...», «#...», «?...»,
 * «./», «../», i valori vuoti e i segnaposto non risolti («[[», «{», «$»).
 * Non tocca: commenti HTML, il contenuto di <script>, <style>, <template>, <textarea>.
 *
 * Evento: OnWebPagePrerender (vale anche per le pagine in cache). Costo: una ricerca sull'HTML.
 * Interruttore: kosseogeo.percorsi_assoluti = 1 lo accende (default 0: installato non fa nulla).
 */
if ($modx->event->name !== 'OnWebPagePrerender') return;
if ((string) $modx->getOption('kosseogeo.percorsi_assoluti', null, '0') !== '1') return;
if (!$modx->resource) return;

$output = &$modx->resource->_output;
if (!is_string($output) || stripos($output, '<base ') === false) return;

// <base href> sulla radice del sito: solo così «assets/x» e «/assets/x» sono lo stesso indirizzo
$fh = stripos($output, '</head>');
if (!preg_match('~<base\b[^>]*\bhref\s*=\s*(["\'])([^"\']*)\1~i', $fh === false ? $output : substr($output, 0, $fh), $b)) return;
$percorsoBase = (string) parse_url(html_entity_decode($b[2], ENT_QUOTES, 'UTF-8'), PHP_URL_PATH);
if ($percorsoBase !== '/') return;

// valore da riscrivere: inizia con lettera/cifra/_ e non ha uno schema prima del primo «/», «?» o «#»
$relativo = static fn(string $v): bool => $v !== ''
    && preg_match('~^[A-Za-z0-9_]~', $v)
    && !preg_match('~^[A-Za-z][A-Za-z0-9+.\-]*:~', $v);

$cambiati = 0;
$riscriviTag = static function (string $tag) use ($relativo, &$cambiati): string {
    return preg_replace_callback(
        '~(\s(?:src|href|poster|action|data-src|srcset|data-srcset)\s*=\s*)(["\'])(.*?)\2~is',
        static function (array $a) use ($relativo, &$cambiati) {
            $nome = strtolower(trim(rtrim(ltrim($a[1]), "= \t\r\n")));
            $v = $a[3];
            if ($nome === 'srcset' || $nome === 'data-srcset') {
                $parti = preg_split('~(,\s*)~', $v, -1, PREG_SPLIT_DELIM_CAPTURE);
                foreach ($parti as $i => $p) {
                    if ($i % 2) continue;                                    // separatori
                    $pl = ltrim($p);
                    if ($relativo($pl)) { $parti[$i] = substr($p, 0, strlen($p) - strlen($pl)) . '/' . $pl; $cambiati++; }
                }
                return $a[1] . $a[2] . implode('', $parti) . $a[2];
            }
            $vl = ltrim($v);
            if (!$relativo($vl)) return $a[0];
            $cambiati++;
            return $a[1] . $a[2] . substr($v, 0, strlen($v) - strlen($vl)) . '/' . $vl . $a[2];
        },
        $tag
    ) ?? $tag;
};

$risultato = preg_replace_callback(
    '~<!--.*?-->|<(style|template|textarea)\b[^>]*>.*?</\1\s*>|(<script\b[^>]*>)(.*?</script\s*>)|<[A-Za-z][A-Za-z0-9-]*\b[^>]*>~is',
    static function (array $m) use ($riscriviTag) {
        if (($m[1] ?? '') !== '' || strncmp($m[0], '<!--', 4) === 0) return $m[0];   // commenti, style, template, textarea
        if (($m[2] ?? '') !== '') return $riscriviTag($m[2]) . $m[3];               // <script>: solo il tag di apertura
        return $riscriviTag($m[0]);
    },
    $output
);
if ($risultato === null) {                       // errore di PCRE (pagina enorme): pagina lasciata com'è
    $modx->log(modX::LOG_LEVEL_WARN, '[PercorsiAssoluti] ' . preg_last_error_msg() . ' su ' . $modx->resource->get('uri'));
    return;
}
if ($cambiati > 0) $output = $risultato;
