<?php
/**
 * titoloPagina — testo del <title> della pagina, comune a anniazzurri.it e kosgroup.com (AA-20, ottobre 2026).
 *
 * Chiamata SEMPRE in cache (senza «!»): il risultato fa parte della cache della pagina.
 *
 * 1. Testo di base: il longtitle se compilato, altrimenti &predefinito, altrimenti il pagetitle
 *    (al pagetitle si applica &cerca → &sostituisci, es. «Residenza Anni Azzurri Idice» → «Residenza Idice»).
 *    Un longtitle che sembra un alias (solo minuscole, cifre e trattini: sulle landing è il nome della cartella
 *    delle immagini) non viene mai usato come titolo.
 * 2. Marchio: si toglie un eventuale « | Marchio» (o « - Marchio», « – Marchio») finale e lo si aggiunge una
 *    volta sola, separato da « | ». Se il marchio compare già nel testo («Il mondo Anni Azzurri») non si aggiunge.
 *    Marchio da &marchio, altrimenti dall'impostazione seo.titolo_marchio; vuoto = nessun marchio.
 *
 * Parametri:
 *   &longtitle=`0`    ignora il longtitle (template in cui il longtitle ha un altro uso)
 *   &predefinito=`…`  testo da usare quando il longtitle è vuoto, al posto del pagetitle
 *   &cerca=`…` &sostituisci=`…`  sostituzione sul pagetitle (non sul longtitle)
 *   &marchio=`…`      marchio da aggiungere (`-` = nessuno)
 *
 * Esempi:
 *   <title>[[titoloPagina]]</title>
 *   <title>[[titoloPagina? &cerca=`Residenza Anni Azzurri ` &sostituisci=`Residenza `]]</title>
 *   <title>[[titoloPagina? &longtitle=`0`]]</title>
 */
$res = $modx->resource ?? null;
if (!$res) return '';

$opz = function ($k, $def = '') use ($modx, $scriptProperties) {
    return (string) $modx->getOption($k, $scriptProperties, $def);
};
$spazi = function ($s) { return trim((string) preg_replace('/\s+/u', ' ', (string) $s)); };

$usaLong = $opz('longtitle', '1') !== '0';
$long    = $spazi($res->get('longtitle'));
if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $long)) $long = '';   // alias, non un titolo

$testo = $usaLong ? $long : '';
if ($testo === '') $testo = $spazi($opz('predefinito'));
if ($testo === '') {
    $testo = $spazi($res->get('pagetitle'));
    $cerca = $opz('cerca');
    if ($cerca !== '' && mb_stripos($testo, trim($cerca)) !== false) {
        $testo = $spazi(str_ireplace($cerca, $opz('sostituisci'), $testo));
    }
}

$marchio = $opz('marchio', (string) $modx->getOption('seo.titolo_marchio', null, ''));
$marchio = $marchio === '-' ? '' : $spazi($marchio);
if ($marchio !== '') {
    $q = preg_quote($marchio, '/');
    $testo = $spazi(preg_replace('/(?:\s*[|\x{2013}\x{2014}-]\s*' . $q . ')+\s*$/iu', '', $testo));
    if ($testo === '') $testo = $marchio;
    elseif (mb_stripos($testo, $marchio) === false) $testo .= ' | ' . $marchio;
}

return htmlspecialchars($testo, ENT_NOQUOTES | ENT_HTML5, 'UTF-8', false);
