<?php
/**
 * CompanyInfo
 *
 * Restituisce il blocco HTML con i dati societari in base all’ID della risorsa.
 * ▸ 1 casi specifico (ognuno può coprire molti ID)
 * ▸ Fallback per qualunque altro ID
 *
 * Modifica solo l’array $cases.
 *
 * @var modX $modx
 */

$id = (int)$modx->resource->get('id');

/* --------------------------------------------------------------------------
 * 1) DEFINISCI I TUOI GRUPPI
 *    Ogni chiave dell’array rappresenta un “caso” e contiene:
 *      - 'ids'  → array degli ID che ricadono nel caso
 *      - 'html' → stringa con il markup da restituire
 * -------------------------------------------------------------------------- */
$cases = [

    'ABITARE_IL_TEMPO' => [
        'ids'  => [327,328],
        'html' => '
            © ABITARE IL TEMPO S.r.l. [[!copyYears]] - Via San Francesco, snc 60025 Loreto (AN)<br>Capitale sociale Euro 100.826,00 i.v. Reg. Imp. 01590980429 Rea 153563<br>Direzione e Coordinamento Art. 2497 c.c. KOS S.p.A.'
    ],
];

/* --------------------------------------------------------------------------
 * 2) LOGICA DI SELEZIONE
 * -------------------------------------------------------------------------- */
$html = '';

// scorri i casi e restituisci il primo che contiene l’ID corrente
foreach ($cases as $case) {
    if (in_array($id, $case['ids'], true)) {
        $html = $case['html'];
        break;
    }
}

// Fallback se l’ID non rientra in nessun gruppo
if ($html === '') {
    $html = '
        © KOS Care S.r.l. [[!copyYears]] - Sede Legale: Via Durini, 9 - 20122 Milano - tel. 02 6713281<br>Sede Operativa: Via Aprutina, 194 - 62018 Porto Potenza Picena (MC)<br>Capitale sociale: € 2.550.000 i.v. - Reg. impr. C.F. / P. IVA 01148190547 - R.E.A. di Milano n°2124636<br>Direz. e Coord. art 2497 C.C. KOS S.p.A.';
}

return $html;