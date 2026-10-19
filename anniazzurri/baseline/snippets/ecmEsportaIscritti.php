<?php
/**
 * ecmEsportaIscritti — esporta in Excel le iscrizioni salvate da FormIt.
 *
 * USO
 * In una risorsa NON pubblicata, con cache disattivata:
 *
 *     [[!ecmEsportaIscritti? &formName=`iscrizione-convegno-il-mantello-del-custode`]]
 *
 * Aprendo la risorsa in anteprima dal manager parte il download del file.
 * Senza &formName lo snippet mostra l'elenco dei form salvati, con il
 * numero di invii di ciascuno: serve a trovare il nome giusto.
 *
 * PROPRIETÀ
 *   &formName   nome del form da esportare (l'alias della risorsa di iscrizione)
 *   &conCodici  1 = aggiunge accanto a professione e disciplina anche le
 *               colonne con i codici ECM, necessari per l'import nella
 *               piattaforma e-learning. Default 0.
 *   &rimuoviDuplicati  0 = tiene anche gli invii identici. Default 1: gli
 *               invii che coincidono in tutti i campi tranne data e ora
 *               di invio vengono ridotti a uno, tenendo il primo.
 *   &formato    excel (default) oppure tracciato: il secondo produce il CSV
 *               per l'import degli utenti nella piattaforma e-learning,
 *               con le 51 colonne del tracciato record nell'ordine previsto.
 *   &codifica   solo per il tracciato: UTF-8 (default) o ISO-8859-1.
 *   &profileId  solo per il tracciato: valore di profile_id. Default 2.
 *   &defRoleId  solo per il tracciato: valore di def_role_id. Default 1.
 *   &prefisso   solo per il tracciato: inizio del nome del file, così
 *               si distingue a colpo d'occhio dall'esportazione Excel.
 *               Default import-formazione.
 *   &gruppo     nome di un gruppo utenti MODX a cui limitare l'accesso.
 *               Facoltativo: senza, basta essere autenticati nel manager.
 *
 * SICUREZZA
 * Il file contiene codice fiscale, data di nascita e indirizzo di tutti gli
 * iscritti. Lo snippet rifiuta chi non è autenticato nel manager, ma va
 * comunque messo in una risorsa non pubblicata: la difesa non deve
 * dipendere da un solo controllo.
 *
 * FORMATO
 * Produce un .xlsx vero, costruito con ZipArchive senza librerie esterne.
 * Tutte le celle sono testo, quindi codice fiscale, telefono e CAP non
 * perdono zeri iniziali e "+39…" non viene interpretato come formula.
 * Se ZipArchive non è disponibile ripiega su un CSV per Excel italiano.
 */

/* ---------------------------------------------------------------------
   Controllo di accesso
--------------------------------------------------------------------- */
if (!$modx->user || !$modx->user->isAuthenticated('mgr')) {
    return '<p>Accesso riservato agli utenti del pannello di gestione.</p>';
}
$gruppo = trim((string) $modx->getOption('gruppo', $scriptProperties, ''));
if ($gruppo !== '' && !$modx->user->isMember($gruppo)) {
    return '<p>Il tuo utente non ha i permessi per questa esportazione.</p>';
}

$formName  = trim((string) $modx->getOption('formName', $scriptProperties, ''));
$conCodici = ((string) $modx->getOption('conCodici', $scriptProperties, '0') === '1');
$rimuoviDuplicati = ((string) $modx->getOption('rimuoviDuplicati', $scriptProperties, '1') !== '0');
$formato   = strtolower(trim((string) $modx->getOption('formato', $scriptProperties, 'excel')));
$codifica  = strtoupper(trim((string) $modx->getOption('codifica', $scriptProperties, 'UTF-8')));
$profileId = (string) $modx->getOption('profileId', $scriptProperties, '2');
$defRoleId = (string) $modx->getOption('defRoleId', $scriptProperties, '1');
$prefisso  = (string) $modx->getOption('prefisso', $scriptProperties, 'import-formazione');

/* ---------------------------------------------------------------------
   Modello FormIt: il nome della classe cambia fra le versioni.
--------------------------------------------------------------------- */
$classe = '';
if (class_exists('\\Sterc\\FormIt\\Model\\FormItForm')) {
    $classe = 'Sterc\\FormIt\\Model\\FormItForm';           // FormIt 5, MODX 3
} else {
    $percorso = $modx->getOption('formit.core_path', null,
        $modx->getOption('core_path') . 'components/formit/') . 'model/';
    $modx->addPackage('formit', $percorso);
    if ($modx->loadClass('FormItForm')) {
        $classe = 'FormItForm';                               // FormIt 4
    }
}
if ($classe === '') {
    $modx->log(1, '[ecmEsportaIscritti] modello FormItForm non trovato');
    return '<p>Impossibile leggere i dati di FormIt: modello non trovato.</p>';
}

/* ---------------------------------------------------------------------
   Senza formName: elenco dei form disponibili.
--------------------------------------------------------------------- */
if ($formName === '') {
    $conteggi = array();
    foreach ($modx->getIterator($classe) as $f) {
        $nome = (string) $f->get('form');
        $conteggi[$nome] = isset($conteggi[$nome]) ? $conteggi[$nome] + 1 : 1;
    }
    if (empty($conteggi)) {
        return '<p>Nessun invio salvato da FormIt.</p>';
    }
    ksort($conteggi);
    $html = '<p>Indica il form da esportare con <code>&amp;formName</code>. Form disponibili:</p><ul>';
    foreach ($conteggi as $nome => $n) {
        $html .= '<li><code>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</code> — '
               . $n . ' invii</li>';
    }
    return $html . '</ul>';
}

/* ---------------------------------------------------------------------
   Etichette di professioni e discipline, lette dai chunk delle opzioni:
   la stessa fonte usata dal form, così i nomi coincidono sempre.
--------------------------------------------------------------------- */
$mappaDaChunk = function ($modx, $nome) {
        $mappa = array();
        $chunk = $modx->getObject('modChunk', array('name' => $nome));
        if (!$chunk) {
            $modx->log(1, '[ecmEsportaIscritti] chunk mancante: ' . $nome);
            return $mappa;
        }
        if (preg_match_all('/<option value="(\d+)"[^>]*>(.*?)<\/option>/s',
                $chunk->getContent(), $m, PREG_SET_ORDER)) {
            foreach ($m as $r) {
                $mappa[$r[1]] = html_entity_decode(trim(strip_tags($r[2])), ENT_QUOTES, 'UTF-8');
            }
        }
        return $mappa;
};
$professioni = $mappaDaChunk($modx, 'ecmOptProfessioni');
$discipline  = $mappaDaChunk($modx, 'ecmOptSpecializzazioni');

$statiOccupazionali = array(
    'D' => 'Dipendente', 'L' => 'Libero professionista',
    'C' => 'Convenzionato', 'P' => 'Privo di occupazione',
);
$intestatari = array('self' => 'Partecipante', 'other' => 'Altro soggetto');

/* ---------------------------------------------------------------------
   Colonne, nell'ordine in cui compaiono nel file.
   Quelle che iniziano con _ sono calcolate; le altre sono campi del form.
--------------------------------------------------------------------- */
$colonne = array(
    '_data'           => 'Data iscrizione',
    'surname'         => 'Cognome',
    'name'            => 'Nome',
    'fiscalcode'      => 'Codice fiscale',
    'birthdate'       => 'Data di nascita',
    'birthcity'       => 'Luogo di nascita',
    'birthprov'       => 'Provincia di nascita',
    'birthstate'      => 'Nazione di nascita',
    'address'         => 'Indirizzo',
    'cap'             => 'CAP',
    'city'            => 'Città',
    'province'        => 'Provincia',
    'state'           => 'Nazione',
    'tel1'            => 'Telefono',
    'email'           => 'Email',
    '_cod_prof'       => 'Codice professione',
    '_professione'    => 'Professione',
    '_cod_disc'       => 'Codice disciplina',
    '_disciplina'     => 'Disciplina',
    '_status'         => 'Stato occupazionale',
    'org_id'          => 'Ente di appartenenza',
    'reclutato'       => 'Reclutato',
    'org_reclutatore' => 'Ente reclutatore',
    'richiesta_fattura' => 'Richiesta fattura',
    '_intestatario'   => 'Intestatario fattura',
    'fnominativo'     => 'Ragione sociale fattura',
    'findirizzo'      => 'Indirizzo fattura',
    'fcap'            => 'CAP fattura',
    'fcomune'         => 'Comune fattura',
    'fprovincia'      => 'Provincia fattura',
    'fcodice_fiscale' => 'Codice fiscale fattura',
    'fpartita_iva'    => 'Partita IVA fattura',
    'fself_piva'      => 'Partita IVA partecipante',
    'fsid'            => 'SDI / PEC fattura',
    'fself_sid'       => 'SDI / PEC partecipante',
    'allegato_nome'   => 'Ricevuta allegata',
    'privacy'         => 'Informativa privacy',
    'consenso'        => 'Consenso comunicazioni',
    'note'            => 'Note',
);
if (!$conCodici) {
    unset($colonne['_cod_prof'], $colonne['_cod_disc']);
}

// Colonne che non hanno senso vuote: se nessun iscritto le ha compilate,
// spariscono. Evita undici colonne di fatturazione vuote negli eventi gratuiti.
$facoltative = array(
    'richiesta_fattura', '_intestatario', 'fnominativo', 'findirizzo', 'fcap',
    'fcomune', 'fprovincia', 'fcodice_fiscale', 'fpartita_iva', 'fself_piva',
    'fsid', 'fself_sid', 'allegato_nome', 'org_reclutatore',
);

// Campi tecnici o già rappresentati da una colonna calcolata.
$esclusi = array(
    'nospam', 'nospam:blank', 'website', 'subject', 'birthdate_iso',
    'ecm_prof_id', 'ecm_disc_id', 'ecm_prof_label', 'ecm_disc_label',
    'status', 'status_label', 'prof_altro', 'fintestatario',
    'recaptcha-token', 'recaptcha-action', 'g-recaptcha-response',
);

/* ---------------------------------------------------------------------
   Lettura e decifratura degli invii
--------------------------------------------------------------------- */
$criteri = $modx->newQuery($classe, array('form' => $formName));
$criteri->sortby('date', 'ASC');

// FormIt codifica i caratteri speciali alla ricezione e li salva così:
// l'apostrofo di D'ALPAOS arriva come D&#039;ALPAOS. Nelle mail il client
// lo interpreta, nel foglio di calcolo resterebbe la codifica grezza.
// Si decodifica fino a stabilità, con un tetto, per coprire anche
// eventuali valori codificati due volte.
$pulisci = function ($v) {
    $v = (string) $v;
    for ($i = 0; $i < 3; $i++) {
        $d = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($d === $v) {
            break;
        }
        $v = $d;
    }
    return $v;
};

$righe = array();
$extra = array();          // campi non previsti: finiscono in fondo, niente si perde
$nonLeggibili = 0;
$totaleInvii = 0;

foreach ($modx->getIterator($classe, $criteri) as $invio) {
    $totaleInvii++;
    $grezzo = (string) $invio->get('values');
    if ($invio->get('encrypted') && method_exists($invio, 'decrypt')) {
        $grezzo = (string) $invio->decrypt($grezzo);
    }
    $v = json_decode($grezzo, true);
    if (!is_array($v)) {
        $nonLeggibili++;
        continue;
    }

    $data = $invio->get('date');
    $ts = is_numeric($data) ? (int) $data : strtotime((string) $data);
    $riga = array('_data' => $ts ? date('d/m/Y H:i', $ts) : (string) $data);

    $codProf = isset($v['ecm_prof_id']) ? (string) $v['ecm_prof_id'] : '';
    $codDisc = isset($v['ecm_disc_id']) ? (string) $v['ecm_disc_id'] : '';

    $prof = isset($professioni[$codProf]) ? $professioni[$codProf] : $codProf;
    if ($codProf === '0' && !empty($v['prof_altro'])) {
        $prof = 'Altro: ' . trim($pulisci($v['prof_altro']));
    }
    $riga['_cod_prof']    = $codProf;
    $riga['_professione'] = $prof;
    $riga['_cod_disc']    = $codDisc;
    $riga['_disciplina']  = isset($discipline[$codDisc]) ? $discipline[$codDisc] : $codDisc;

    $st = isset($v['status']) ? (string) $v['status'] : '';
    $riga['_status'] = isset($statiOccupazionali[$st]) ? $statiOccupazionali[$st] : $st;

    $int = isset($v['fintestatario']) ? (string) $v['fintestatario'] : '';
    $riga['_intestatario'] = isset($intestatari[$int]) ? $intestatari[$int] : $int;

    foreach ($v as $campo => $valore) {
        if (in_array($campo, $esclusi, true) || is_array($valore)) {
            continue;
        }
        $riga[$campo] = $pulisci($valore);
        if (!isset($colonne[$campo]) && !isset($extra[$campo])) {
            $extra[$campo] = $campo;
        }
    }
    // Valori originali già decodificati: al tracciato servono i codici
    // (status, ecm_prof_id…) e non le etichette usate nel foglio Excel.
    $grezzi = array();
    foreach ($v as $campo => $valore) {
        if (!is_array($valore)) {
            $grezzi[$campo] = $pulisci($valore);
        }
    }
    $riga['_v'] = $grezzi;

    $righe[] = $riga;
}

/* ---------------------------------------------------------------------
   Duplicati.
   Due invii sono duplicati quando coincidono in tutti i campi tranne data
   e ora di invio, che differiscono sempre: è il caso tipico del doppio
   clic o dell'utente che reinvia pensando che il primo non sia partito.
   Si tiene il primo in ordine di tempo. Un campo vuoto e un campo assente
   contano come uguali; gli spazi all'inizio e alla fine non contano.
   Due invii della stessa persona con anche un solo dato diverso — un
   telefono corretto, per esempio — restano entrambi: lì serve un giudizio.
--------------------------------------------------------------------- */
$duplicatiRimossi = 0;
if ($rimuoviDuplicati) {
    $visti = array();
    $uniche = array();
    foreach ($righe as $r) {
        $impronta = $r;
        unset($impronta['_data'], $impronta['_v']);
        $impronta = array_filter(array_map('trim', $impronta), 'strlen');
        ksort($impronta);
        $chiave = md5(serialize($impronta));
        if (isset($visti[$chiave])) {
            $duplicatiRimossi++;
            continue;
        }
        $visti[$chiave] = true;
        $uniche[] = $r;
    }
    $righe = $uniche;
}

if ($nonLeggibili > 0) {
    $modx->log(1, '[ecmEsportaIscritti] ' . $nonLeggibili
        . ' invii non leggibili nel form ' . $formName . ' (decifratura non riuscita?)');
}
if (empty($righe)) {
    return '<p>Nessuna iscrizione leggibile per il form <code>'
         . htmlspecialchars($formName, ENT_QUOTES, 'UTF-8') . '</code>.'
         . ($nonLeggibili ? ' ' . $nonLeggibili . ' invii non decifrati: vedi il log errori.' : '')
         . '</p>';
}

foreach ($facoltative as $c) {
    $usata = false;
    foreach ($righe as $r) {
        if (isset($r[$c]) && trim($r[$c]) !== '') { $usata = true; break; }
    }
    if (!$usata) {
        unset($colonne[$c]);
    }
}
$colonne = array_merge($colonne, $extra);

/* ---------------------------------------------------------------------
   Costruzione della tabella
--------------------------------------------------------------------- */
$tabella = array(array_values($colonne));
foreach ($righe as $r) {
    $linea = array();
    foreach (array_keys($colonne) as $c) {
        $linea[] = isset($r[$c]) ? $r[$c] : '';
    }
    $tabella[] = $linea;
}

$base = preg_replace('/[^a-z0-9-]+/', '-', strtolower($formName));
$base = 'iscritti-' . trim($base, '-') . '-' . date('Ymd-Hi');

while (ob_get_level() > 0) {
    ob_end_clean();
}

/* ---------------------------------------------------------------------
   Tracciato record per l'import nella piattaforma e-learning.

   Scelte, tutte deliberate:
   - nessun delimitatore di testo attorno ai valori, come nel tracciato di
     esempio: un import semplice che divide sul punto e virgola troverebbe
     le virgolette dentro i dati. Per poterlo fare senza rischi, dai valori
     vengono tolti punti e virgola e a capo, che sfaserebbero le colonne;
   - nessuna protezione contro le formule, al contrario del file Excel: in
     un file per l'import il prefisso finirebbe dentro il dato, per esempio
     nel numero di telefono "+39…";
   - apostrofi e virgolette tipografici ricondotti a quelli semplici: i
     telefoni li inseriscono da soli, e in ISO-8859-1 non esistono;
   - UTF-8 senza BOM: il BOM finirebbe incollato al nome della prima colonna.
--------------------------------------------------------------------- */
if ($formato === 'tracciato') {
    $colonneTracciato = array(
        'user_id','login','pass','pass_changed','pass_mustchangeat','lseccode','name','surname',
        'profile_id','qualific_id','def_role_id','org_id','org_bill_id','blocked','lastaccess',
        'useragent','netip','sessionid','lastaction','lastaction_time','accesses','fiscalcode',
        'email','address','city','lang','province','state','cap','tel1','tel2','birthcity',
        'birthprov','birthstate','birthdate','workindate','occupation','kos_internal','kos_regref',
        'ecm_prof_id','ecm_disc_id','kos_dirstrut','teacher_knowledge','teacher_docs','status',
        'temp_teacher','reclutato','org_reclutatore','note','privacy','consenso',
    );

    $valoreTracciato = function ($x) {
        $x = (string) $x;
        $x = str_replace(
            array("\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}", "\u{00A0}"),
            array("'", "'", '"', '"', '-', '-', ' '), $x);
        $x = str_replace(';', ',', $x);
        $x = preg_replace('/[\r\n\t]+/', ' ', $x);
        return trim(preg_replace('/ {2,}/', ' ', $x));
    };

    $linee = array(implode(';', $colonneTracciato));
    foreach ($righe as $r) {
        $v = $r['_v'];
        $prof = isset($v['ecm_prof_id']) ? (string) $v['ecm_prof_id'] : '';

        // Occupazione in chiaro per le professioni che non hanno un codice
        // Agenas: Assistente sociale con la sua etichetta, Altro con il
        // testo scritto dall'iscritto.
        $occupazione = '';
        if ($prof === '1000') {
            $occupazione = isset($professioni['1000']) ? $professioni['1000'] : 'Assistente sociale';
        } elseif ($prof === '0') {
            $occupazione = !empty($v['prof_altro']) ? $v['prof_altro'] : 'Altro';
        }

        // Data di nascita in gg/mm/aaaa anche per eventuali invii salvati
        // prima della normalizzazione.
        $nascita = isset($v['birthdate']) ? $v['birthdate'] : '';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nascita, $m)) {
            $nascita = $m[3] . '/' . $m[2] . '/' . $m[1];
        }

        $email = isset($v['email']) ? $v['email'] : '';
        $valori = array(
            'login'       => $email,
            'profile_id'  => $profileId,
            'def_role_id' => $defRoleId,
            'birthdate'   => $nascita,
            'occupation'  => $occupazione,
        );
        foreach (array('name','surname','org_id','fiscalcode','email','address','city','province',
                       'state','cap','tel1','birthcity','birthprov','birthstate','ecm_prof_id',
                       'ecm_disc_id','status','reclutato','org_reclutatore','note','privacy',
                       'consenso') as $campo) {
            if (!isset($valori[$campo])) {
                $valori[$campo] = isset($v[$campo]) ? $v[$campo] : '';
            }
        }

        $linea = array();
        foreach ($colonneTracciato as $c) {
            $linea[] = isset($valori[$c]) ? $valoreTracciato($valori[$c]) : '';
        }
        $linee[] = implode(';', $linea);
    }

    $contenuto = implode("\r\n", $linee) . "\r\n";
    if ($codifica !== 'UTF-8') {
        $convertito = @iconv('UTF-8', $codifica . '//TRANSLIT', $contenuto);
        if ($convertito === false) {
            $modx->log(1, '[ecmEsportaIscritti] conversione in ' . $codifica . ' non riuscita: uso UTF-8');
            $codifica = 'UTF-8';
        } else {
            $contenuto = $convertito;
        }
    }

    $slugPrefisso = trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($prefisso)), '-');
    $nomeTracciato = ($slugPrefisso !== '' ? $slugPrefisso : 'import-formazione')
                   . '-' . substr($base, strlen('iscritti-')) . '.csv';
    header('Content-Type: text/csv; charset=' . $codifica);
    header('Content-Disposition: attachment; filename="' . $nomeTracciato . '"');
    header('Content-Length: ' . strlen($contenuto));
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    echo $contenuto;
    exit;
}

/* ---------------------------------------------------------------------
   Ripiego CSV se ZipArchive non c'è.
--------------------------------------------------------------------- */
if (!class_exists('\\ZipArchive')) {
    $modx->log(1, '[ecmEsportaIscritti] ZipArchive non disponibile: esportazione in CSV');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $base . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF";                                   // BOM: accenti corretti in Excel
    $out = fopen('php://output', 'w');
    foreach ($tabella as $linea) {
        foreach ($linea as $i => $cella) {
            // Excel interpreta come formula ciò che inizia con = + - @:
            // "+39 335…" diventerebbe un calcolo, e un valore ostile un comando.
            if ($cella !== '' && strpos("=+-@\t\r", $cella[0]) !== false) {
                $linea[$i] = "'" . $cella;
            }
        }
        fputcsv($out, $linea, ';');                        // separatore dell'Excel italiano
    }
    fclose($out);
    exit;
}

/* ---------------------------------------------------------------------
   XLSX costruito a mano.
   Celle inlineStr: sempre testo, mai formule, nessuna tabella di stringhe
   condivise da gestire.
--------------------------------------------------------------------- */
// Funzioni anonime e non dichiarate: in MODX 3 lo snippet gira nel
// namespace MODX\Revolution, dove function_exists() non vede le funzioni
// dichiarate qui e una seconda esecuzione darebbe errore di ridichiarazione.
$colonnaXlsx = function ($n) {
    $s = '';
    for ($n++; $n > 0; $n = intdiv($n - 1, 26)) {
        $s = chr(65 + ($n - 1) % 26) . $s;
    }
    return $s;
};
$testoXlsx = function ($v) {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $v);
    return htmlspecialchars($v, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
};

// Secondo foglio: documenta l'esportazione, così chi apre il file sa
// se sono state tolte righe e perché.
$utente = ($modx->user && method_exists($modx->user, 'get')) ? (string) $modx->user->get('username') : '';
$informazioni = array(
    array('Voce', 'Valore'),
    array('Form', $formName),
    array('Esportato il', date('d/m/Y H:i')),
    array('Esportato da', $utente),
    array('Invii salvati', (string) $totaleInvii),
    array('Invii non leggibili', (string) $nonLeggibili),
    array('Duplicati rimossi', $rimuoviDuplicati ? (string) $duplicatiRimossi : 'rimozione disattivata'),
    array('Iscrizioni nel foglio', (string) count($righe)),
    array('', ''),
    array('Criterio duplicati', 'Invii identici in tutti i campi tranne data e ora di invio. Si tiene il primo.'),
    array('Invii non leggibili', 'Invii che non è stato possibile decifrare: il dettaglio è nel log errori di MODX.'),
);

$fogli = array(
    array('nome' => 'Iscritti', 'dati' => $tabella, 'filtro' => true),
    array('nome' => 'Informazioni', 'dati' => $informazioni, 'filtro' => false),
);

$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
$nsR = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
$nsPkg = 'http://schemas.openxmlformats.org/package/2006/relationships';
$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";

// Un foglio: larghezze stimate sul contenuto, intestazione in grassetto e
// bloccata, filtro facoltativo.
$foglioXml = function ($dati, $filtro) use ($colonnaXlsx, $testoXlsx, $ns, $xml) {
    $nCol = count($dati[0]);
    $nRig = count($dati);
    $area = 'A1:' . $colonnaXlsx($nCol - 1) . $nRig;

    $cols = '';
    for ($i = 0; $i < $nCol; $i++) {
        $max = 8;
        foreach ($dati as $linea) {
            $max = max($max, mb_strlen((string) $linea[$i], 'UTF-8'));
        }
        $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="'
               . min($max + 2, 80) . '" customWidth="1"/>';
    }

    $righeXml = '';
    foreach ($dati as $r => $linea) {
        $righeXml .= '<row r="' . ($r + 1) . '">';
        foreach ($linea as $i => $cella) {
            $righeXml .= '<c r="' . $colonnaXlsx($i) . ($r + 1) . '" t="inlineStr"'
                       . ($r === 0 ? ' s="1"' : '') . '><is><t xml:space="preserve">'
                       . $testoXlsx($cella) . '</t></is></c>';
        }
        $righeXml .= '</row>';
    }

    return $xml . '<worksheet xmlns="' . $ns . '">'
        . '<dimension ref="' . $area . '"/>'
        . '<sheetViews><sheetView workbookViewId="0">'
        . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
        . '</sheetView></sheetViews>'
        . '<cols>' . $cols . '</cols>'
        . '<sheetData>' . $righeXml . '</sheetData>'
        . ($filtro ? '<autoFilter ref="' . $area . '"/>' : '')
        . '</worksheet>';
};

$tipi = '';
$foglisXml = '';
$relazioni = '';
$nomiDefiniti = '';
$parti = array();
foreach ($fogli as $i => $f) {
    $n = $i + 1;
    $parti['xl/worksheets/sheet' . $n . '.xml'] = $foglioXml($f['dati'], $f['filtro']);
    $tipi .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" '
           . 'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    $foglisXml .= '<sheet name="' . $f['nome'] . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
    $relazioni .= '<Relationship Id="rId' . $n . '" Type="' . $nsR . '/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
    if ($f['filtro']) {
        $nomiDefiniti .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">'
            . "'" . $f['nome'] . "'!\$A\$1:\$" . $colonnaXlsx(count($f['dati'][0]) - 1) . '$' . count($f['dati'])
            . '</definedName>';
    }
}
$idStili = 'rId' . (count($fogli) + 1);

$parti['[Content_Types].xml'] = $xml
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . $tipi
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

$parti['_rels/.rels'] = $xml
    . '<Relationships xmlns="' . $nsPkg . '">'
    . '<Relationship Id="rId1" Type="' . $nsR . '/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$parti['xl/workbook.xml'] = $xml
    . '<workbook xmlns="' . $ns . '" xmlns:r="' . $nsR . '">'
    . '<sheets>' . $foglisXml . '</sheets>'
    . ($nomiDefiniti !== '' ? '<definedNames>' . $nomiDefiniti . '</definedNames>' : '')
    . '</workbook>';

$parti['xl/_rels/workbook.xml.rels'] = $xml
    . '<Relationships xmlns="' . $nsPkg . '">'
    . $relazioni
    . '<Relationship Id="' . $idStili . '" Type="' . $nsR . '/styles" Target="styles.xml"/>'
    . '</Relationships>';

$parti['xl/styles.xml'] = $xml
    . '<styleSheet xmlns="' . $ns . '">'
    . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
    . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
    . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
    . '<fill><patternFill patternType="gray125"/></fill></fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
    . '</styleSheet>';

// Il file temporaneo contiene dati personali: esiste solo il tempo
// dell'invio e viene cancellato subito dopo.
$tmp = tempnam(sys_get_temp_dir(), 'ecm');
$zip = new \ZipArchive();
if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
    @unlink($tmp);
    $modx->log(1, '[ecmEsportaIscritti] impossibile creare il file xlsx');
    return '<p>Impossibile creare il file. Vedi il log errori.</p>';
}
foreach ($parti as $nome => $contenuto) {
    $zip->addFromString($nome, $contenuto);
}
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
readfile($tmp);
@unlink($tmp);
exit;