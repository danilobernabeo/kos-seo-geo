<?php
/**
 * CompanyInfo
 *
 * Restituisce il blocco HTML con i dati societari in base all’ID della risorsa.
 * ▸ 4 casi specifici (ognuno può coprire molti ID)
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

    'JESILAB' => [
        'ids'  => [202],   // metti i tuoi ID
        'html' => '
            © JESILAB S.r.l. [[!copyYears]] - Via Don Minzoni 29/A - 60035 Jesi (AN)<br>C.F. P.IVA e Registro Imprese Ancona n° 02220610428 - Rea Ancona n° 170349 - Società a socio unico - Capitale Sociale. € 80.000 i.v.<br>Direzione e Coordinamento Art. 2497 c.c. KOS S.p.A.'
    ],

    'SANATRIX_GESTIONI' => [
        'ids'  => [208,209,210,211,212,218],
        'html' => '
            © SANATRIX GESTIONI S.r.l. [[!copyYears]] - Viale dei Pini, 31 - 62012 Civitanova Marche (MC) - Tel.+39 0733 7861 • Fax +39 0733 7863316 - info@casadicuravillapini.it<br>Capitale Sociale € 300.000,00 i.v. - R.E.A. MC 156700 Reg. Impr. - C.F. e P.I. 01490070438<br>Direzione e Coordinamento art. 2497 cc: KOS S.p.A.'
    ],

    'ABITARE_IL_TEMPO' => [
        'ids'  => [168],
        'html' => '
            © ABITARE IL TEMPO S.r.l. [[!copyYears]] - Via San Francesco, snc 60025 Loreto (AN)<br>Capitale sociale Euro 100.826,00 i.v. Reg. Imp. 01590980429 Rea 153563<br>Direzione e Coordinamento Art. 2497 c.c. KOS S.p.A.'
    ],

    'FIDIA' => [
        'ids'  => [199],
        'html' => '
            © FIDIA S.r.l. [[!copyYears]] - Viale dei Pini, 31 62012 Civitanova Marche (MC)<br>C.F. e P.I. 01348940436 Capitale sociale Euro 10.200,00 i.v.<br>Direzione e Coordinamento Art. 2497 c.c. KOS S.p.A.'
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
        © KOS S.p.A. [[!copyYears]] - C.F. 02058910874, P.I. 10674520159 - R.E.A. di Milano n. 1400104 - Capitale sociale € 8.565.211,70 i-v<br>Direzione Coordinamento (art. 2497 C.C): CIR SpA';
}

return $html;