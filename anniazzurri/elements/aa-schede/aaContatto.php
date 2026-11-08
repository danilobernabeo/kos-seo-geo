<?php
/**
 * aaContatto — anniazzurri.it, voci AA-2/5/11/21 (schede residenza, sprint 3)
 * -----------------------------------------------------------------------------
 * Un dato del box contatti della scheda, scritto dal server dalle TV anagrafiche str.* (le stesse del dato
 * strutturato), al posto dei campi riempiti nel browser dallo script Yext Knowledge Tags (data-yext-field).
 * I nomi dei campi sono quelli di Yext, così la conversione dei chunk e dei contenuti è uno a uno.
 *
 * USO (in cache, mai [[!...]]):
 *   [[aaContatto? &campo=`name`]]                       nome ufficiale (str.nome, altrimenti pagetitle)
 *   [[aaContatto? &campo=`address1`]] / `zip` / `city` / `state`
 *   [[aaContatto? &campo=`email` &classe=`...`]]        <a href="mailto:…">…</a>
 *   [[aaContatto? &campo=`phone` &classe=`...`]]        <a href="tel:+39…">031 7769601</a>
 *   [[aaContatto? &campo=`faxPhone`]]                   numero del fax, testo
 *   [[aaContatto? &campo=`c_emailDirettore` &classe=`...`]]  email del direttore (TV emailDirettore)
 *
 * PARAMETRI
 *   &campo   nome del campo (sopra)
 *   &classe  classi dell'elemento (come nel markup originale)
 *   &id      risorsa da cui leggere (default: la pagina corrente)
 *
 * Le email escono con «@» scritta &#64;: il plugin EMO non le trasforma in JavaScript, quindi restano leggibili dai
 * crawler degli assistenti AI che non eseguono JavaScript (voce 21 dell'audit); il browser le mostra normalmente.
 * Campo vuoto = stringa vuota (come faceva Yext).
 */
$campo  = (string) $modx->getOption('campo', $scriptProperties, '');
$classe = trim((string) $modx->getOption('classe', $scriptProperties, ''));
$idRis  = (int) $modx->getOption('id', $scriptProperties, 0);
$res = $idRis ? $modx->getObject(\MODX\Revolution\modResource::class, $idRis) : $modx->resource;
if (!$res) return '';

/* Pagina senza dati propri (es. «Argento Vivo», sottopagina del Melograno con lo stesso id Yext): i dati vengono dalla
 * scheda che ha lo stesso data-yext-id e l'indirizzo compilato. Il dato strutturato invece esce solo sulla scheda. */
$v0 = $res->getTVValue('str.via');
if (!is_string($v0) || trim($v0) === '') {
    $yid = trim((string) $res->getTVValue('data-yext-id'));
    $tvY = $yid !== '' ? $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => 'data-yext-id']) : null;
    if ($tvY) {
        $q = $modx->newQuery(\MODX\Revolution\modTemplateVarResource::class,
            ['tmplvarid' => $tvY->get('id'), 'value' => $yid, 'contentid:!=' => $res->get('id')]);
        foreach ($modx->getIterator(\MODX\Revolution\modTemplateVarResource::class, $q) as $legame) {
            $altra = $modx->getObject(\MODX\Revolution\modResource::class, ['id' => $legame->get('contentid'), 'published' => 1, 'deleted' => 0]);
            if ($altra && trim((string) $altra->getTVValue('str.via')) !== '') { $res = $altra; break; }
        }
    }
}

$e   = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$tv  = function ($nome) use ($res) { $v = $res->getTVValue($nome); return is_string($v) ? trim($v) : ''; };
$cls = $classe !== '' ? ' class="' . $e($classe) . '"' : '';
$mail = function ($indirizzo) use ($e, $cls) {
    $indirizzo = strtolower(trim((string) $indirizzo));
    if (!preg_match('/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/', $indirizzo)) return '';
    $v = str_replace('@', '&#64;', $e($indirizzo));
    return '<a' . $cls . ' href="mailto:' . $v . '">' . $v . '</a>';
};
/* Numero nazionale leggibile: prefisso + numero. Prefissi a 2 cifre (02, 06), a 3 cifre dall'elenco dei distretti
 * a tre cifre, gli altri a 4; cellulari 3xx + resto. */
$leggibile = function ($n) {
    $n = preg_replace('/\D/', '', (string) $n);
    if (strpos($n, '39') === 0 && strlen($n) > 10) $n = substr($n, 2);
    if ($n === '') return '';
    if ($n[0] === '3') return substr($n, 0, 3) . ' ' . substr($n, 3);
    if (in_array(substr($n, 0, 2), ['02', '06'], true)) return substr($n, 0, 2) . ' ' . substr($n, 2);
    $tre = ['010', '011', '015', '019', '030', '031', '035', '039', '040', '041', '045', '049', '050', '051', '055',
            '059', '070', '071', '075', '079', '080', '081', '085', '089', '090', '091', '095', '099'];
    $p = in_array(substr($n, 0, 3), $tre, true) ? 3 : 4;
    return substr($n, 0, $p) . ' ' . substr($n, $p);
};

switch ($campo) {
    case 'name':
    case 'locationName':
        return $e($tv('str.nome') ?: $res->get('pagetitle'));
    case 'address1':
        return $e($tv('str.via'));
    case 'zip':
        return $e($tv('str.cap'));
    case 'city':
        return $e($tv('str.citta'));
    case 'state':
        return $e(strtoupper($tv('str.provincia')));
    case 'email':
        return $mail($tv('str.email'));
    case 'c_emailDirettore':
        return $mail($tv('emailDirettore'));
    case 'phone':
        $n = preg_replace('/[^\d+]/', '', $tv('str.telefono'));
        if ($n === '') return '';
        $tel = strpos($n, '+') === 0 ? $n : '+39' . $n;
        return '<a' . $cls . ' href="tel:' . $e($tel) . '">' . $e($leggibile($n)) . '</a>';
    case 'faxPhone':
        return $e($leggibile($tv('str.fax')));
}
return '';
