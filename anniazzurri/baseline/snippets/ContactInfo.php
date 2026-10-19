<?php
/**
 * ContactInfo
 *
 * Restituisce il blocco HTML con il bottone dei contatti in base all’ID della risorsa.
 * Modifica solo l’array $cases.
 *
 * @var modX $modx
 */

$id = (int)$modx->resource->get('id');

/* --------------------------------------------------------------------------
 * 1) DEFINIZIONE DEI GRUPPI
 *    Ogni chiave dell’array rappresenta un “caso” e contiene:
 *      - 'ids'  → array degli ID che ricadono nel caso
 *      - 'html' → stringa con il markup da restituire
 * -------------------------------------------------------------------------- */
$cases = [

    'NOFORMHUBSPOT' => [
        'ids'  => [],   // ID che rimandano alla pagina di contatti generica
        'html' => '[[$contactButton]]'
    ],
];

/* --------------------------------------------------------------------------
 * 2) LOGICA DI SELEZIONE
 * -------------------------------------------------------------------------- */
$html = '';

// scorre i casi e restituisce il primo che contiene l’ID corrente
foreach ($cases as $case) {
    if (in_array($id, $case['ids'], true)) {
        $html = $case['html'];
        break;
    }
}

// Fallback se l’ID non rientra in nessun gruppo
if ($html === '') {
    $html = '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">VUOI ESSERE RICONTATTATO?</a>';
}

return $html;