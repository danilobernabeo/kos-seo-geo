<?php
/**
 * LinkFrammenti — kosgroup.com, scansione del 24/11/2026, anomalia 3: link con il solo frammento.
 *
 * Con <base href="https://kosgroup.com/"> un link scritto href="#testo-articolo" si risolve in
 * https://kosgroup.com/#testo-articolo: i crawler lo contano come link alla home (301 verso /it; 835 link da circa
 * 590 pagine nella scansione: «LEGGI ARTICOLO», «Trova struttura», «Come accedere», «Indicazioni», note a piè di
 * pagina). Per i visitatori il clic lo intercetta già main.js, che fa scorrere la pagina.
 *
 * Il plugin riscrive nell'HTML in uscita href="#id" in href="/percorso-della-pagina#id" (stesso documento: il browser
 * scorre senza ricaricare; main.js aggiornato riconosce entrambe le forme). Vale per template, chunk, snippet e
 * contenuti delle risorse, senza toccarli uno per uno.
 *
 * Non tocca:
 *   - href="#" (pulsanti «Cerca» e «Prenota ONLINE»: restano così per il Conversion Linker di GTM);
 *   - i link dei componenti Bootstrap e simili (data-toggle, data-bs-toggle, data-target, data-bs-target, role=tab/button),
 *     che leggono l'id dall'href;
 *   - frammenti che non sono un id semplice (es. ${...} nei modelli JavaScript) e tutto ciò che sta in <script>,
 *     <style>, <noscript>, <template> e nei commenti HTML;
 *   - le pagine senza <base href> (lì #id si risolve già sulla pagina).
 * Percorso: URL della risorsa (makeUrl, schema abs = /...), più ?page=N sulle pagine di elenco paginate. Nessun valore
 * della richiesta finisce nell'HTML se non il numero di pagina.
 * Evento: OnWebPagePrerender. Costo: una ricerca nell'HTML, saltata se la pagina non ha href="#.
 * Interruttore: kosseogeo.link_frammenti = 0 spegne il plugin.
 */
if ($modx->event->name !== 'OnWebPagePrerender') return;
if ((string) $modx->getOption('kosseogeo.link_frammenti', null, '1') === '0') return;
if (!$modx->resource) return;

$output = &$modx->resource->_output;
if (!is_string($output) || strpos($output, 'href="#') === false || stripos($output, '<base ') === false) return;

$url = (string) $modx->makeUrl($modx->resource->get('id'), '', '', 'abs');
if ($url === '' || $url[0] !== '/') return;
$pagina = $_GET['page'] ?? '';
if (is_string($pagina) && preg_match('/^[1-9][0-9]{0,4}$/', $pagina) && $pagina !== '1') {
    $url .= (strpos($url, '?') === false ? '?' : '&') . 'page=' . $pagina;
}
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

// Una sola passata: i blocchi da lasciare intatti (commenti, script, style, noscript, template) si restituiscono
// come sono; nei tag <a> con href="#id" si riscrive l'href.
$cambiati = 0;
$risultato = preg_replace_callback(
    '~<!--.*?-->|<(script|style|noscript|template)\b[^>]*>.*?</\1\s*>|<a\b[^>]*\bhref="#[^"]*"[^>]*>~is',
    static function (array $m) use ($url, &$cambiati) {
        $tag = $m[0];
        if (strncasecmp($tag, '<a', 2) !== 0) return $tag;
        if (preg_match('~\b(data-(?:bs-)?(?:toggle|target)|role\s*=\s*"(?:tab|button)")~i', $tag)) return $tag;
        return preg_replace_callback('~\bhref="#([A-Za-z_][A-Za-z0-9_:.\-]*)"~', static function (array $h) use ($url, &$cambiati) {
            $cambiati++;
            return 'href="' . $url . '#' . $h[1] . '"';
        }, $tag, 1);
    },
    $output
);
if (is_string($risultato) && $cambiati > 0) $output = $risultato;
