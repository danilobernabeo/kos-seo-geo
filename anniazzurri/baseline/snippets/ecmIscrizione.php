<?php
/**
 * ecmIscrizione — unico punto di ingresso del form di iscrizione agli eventi.
 *
 * Nel template della risorsa evento basta:
 *
 *     [[!ecmIscrizione]]
 *
 * Lo snippet legge le Template Variable dell'evento, compone le proprietà di
 * FormIt e lo esegue, poi restituisce il form. Non c'è nessun tag annidato
 * dentro un valore di proprietà: le regole di validazione e il blocco
 * fatturazione vengono decisi qui, in PHP, e non dal parser dei tag.
 *
 * Conseguenza pratica: la spunta su evento_pagamento accende insieme i campi
 * della fatturazione e le regole che li controllano. Non possono andare fuori
 * sincrono, che è il modo in cui il vecchio form si era riempito di codice
 * morto a furia di duplicare la chiamata da un evento all'altro.
 *
 * Template Variable attese sulla risorsa evento:
 *   evento_pagamento     checkbox 0/1
 *   evento_titolo        testo
 *   evento_sottotitolo   testo (facoltativo)
 *   evento_informativa   percorso del PDF dell'informativa privacy
 *   evento_redirect      id della risorsa di ringraziamento (facoltativo)
 *
 * Se il form sta su una risorsa separata, su quella serve solo:
 *   evento_riferimento   id della risorsa evento da cui leggere le TV
 *
 * Proprietà dello snippet, tutte facoltative, per scavalcare i valori di
 * default senza toccare il codice:
 *   &evento &titolo &sottotitolo &emailTo &emailFrom &emailFromName
 *   &redirectTo &etaMinima
 */

$risorsaCorrente = $modx->resource;
if (!$risorsaCorrente) {
    return '';
}

/* ---------------------------------------------------------------------
   A quale evento si riferisce questo form.

   Il form può stare sulla pagina dell'evento oppure su una risorsa a sé,
   linkabile da più parti. Nel secondo caso le TV restano una sola volta
   sull'evento e qui si indica soltanto a quale: niente titoli o date
   duplicati che poi divergono.

   Ordine di ricerca:
     1. proprietà &evento passata alla chiamata
     2. TV evento_riferimento della risorsa corrente
     3. la risorsa corrente stessa (form sulla pagina dell'evento)
--------------------------------------------------------------------- */
$idEvento = trim((string) $modx->getOption('evento', $scriptProperties, ''));
if ($idEvento === '') {
    $idEvento = trim((string) $risorsaCorrente->getTVValue('evento_riferimento'));
}

$risorsa = $risorsaCorrente;
if ($idEvento !== '' && (int) $idEvento !== (int) $risorsaCorrente->get('id')) {
    $evento = $modx->getObject('modResource', (int) $idEvento);
    if ($evento) {
        $risorsa = $evento;
    } else {
        $modx->log(1 /* LOG_LEVEL_ERROR */,
            '[ecmIscrizione] risorsa evento non trovata: ' . $idEvento);
    }
}

/* ---------------------------------------------------------------------
   Dati dell'evento
--------------------------------------------------------------------- */
$aPagamento   = ((string) $risorsa->getTVValue('evento_pagamento') === '1');
$titoloEvento = trim((string) $risorsa->getTVValue('evento_titolo'));
$sottotitolo  = trim((string) $risorsa->getTVValue('evento_sottotitolo'));
$dataLuogo    = trim((string) $risorsa->getTVValue('evento_data_luogo'));

/* Riferimenti della segreteria organizzativa mostrati in fondo alla mail
   all'iscritto. Cambiano da evento a evento, perché spesso la segreteria
   è quella della struttura ospitante: si indicano nella TV
   evento_segreteria, una riga per informazione. Lasciandola vuota si
   usano i riferimenti del servizio formazione. */
$segreteria = trim((string) $risorsa->getTVValue('evento_segreteria'));
if ($segreteria === '') {
    $segreteria = "KOS Care - Servizio Formazione\nformazione@kosgroup.com - tel. 0733 689411";
}
$segreteria = nl2br(htmlspecialchars($segreteria, ENT_QUOTES, 'UTF-8'));
$informativa  = trim((string) $risorsa->getTVValue('evento_informativa'));
$redirectTv   = trim((string) $risorsa->getTVValue('evento_redirect'));

if ($titoloEvento === '') {
    $titoloEvento = $risorsa->get('pagetitle');
}
if ($informativa === '') {
    $informativa = '/assets/doc/KOS-Care_GDPR_Informativa.pdf';
}
/* Percorso assoluto dal root del sito ("/assets/doc/...") se la TV contiene un
   percorso relativo ("assets/doc/..."): un link relativo dipende da <base href>
   e i crawler che lo ignorano costruiscono URL annidati inesistenti.
   Gli URL completi (http/https o //) restano come sono. */
if (!preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $informativa)) {
    $informativa = '/' . ltrim($informativa, '/');
}

/* ---------------------------------------------------------------------
   Destinatari della notifica.
   Alcuni convegni vanno gestiti dal referente della struttura ospitante
   anziché dal servizio formazione: l'elenco si indica nella TV
   evento_notifiche, separando più indirizzi con la virgola.

   Ogni indirizzo viene validato: un refuso nella TV farebbe sparire le
   notifiche in silenzio, ed è il tipo di guasto di cui ci si accorge a
   iscrizioni già perse. Gli indirizzi non validi vengono scartati e
   annotati nel log; se non ne resta nessuno si torna al default.
--------------------------------------------------------------------- */
$emailToDefault = 'formazione@kosgroup.com';

$emailToGrezzo = trim((string) $modx->getOption('emailTo', $scriptProperties, ''));
if ($emailToGrezzo === '') {
    $emailToGrezzo = trim((string) $risorsa->getTVValue('evento_notifiche'));
}
if ($emailToGrezzo === '') {
    $emailToGrezzo = $emailToDefault;
}

$destinatari = array();
foreach (preg_split('/[;,\s]+/', $emailToGrezzo) as $indirizzo) {
    $indirizzo = trim($indirizzo);
    if ($indirizzo === '') {
        continue;
    }
    if (filter_var($indirizzo, FILTER_VALIDATE_EMAIL)) {
        $destinatari[] = $indirizzo;
    } else {
        $modx->log(1 /* LOG_LEVEL_ERROR */,
            '[ecmIscrizione] indirizzo di notifica non valido, ignorato: ' . $indirizzo);
    }
}
$ripiegoDestinatario = false;
if (empty($destinatari)) {
    $modx->log(1 /* LOG_LEVEL_ERROR */,
        '[ecmIscrizione] nessun destinatario valido: si usa ' . $emailToDefault);
    $destinatari = array($emailToDefault);
    $ripiegoDestinatario = true;
}

$emailTo = implode(',', $destinatari);
$primoDestinatario = $destinatari[0];
$emailFrom   = $modx->getOption('emailFrom', $scriptProperties, 'info@anniazzurri.it');
$emailNome   = $modx->getOption('emailFromName', $scriptProperties, 'Segreteria Organizzativa - Anni Azzurri');
$redirectTo  = $modx->getOption('redirectTo', $scriptProperties, $redirectTv);
$etaMinima   = (int) $modx->getOption('etaMinima', $scriptProperties, 16);

/* ---------------------------------------------------------------------
   Regole di validazione: base sempre, pagamento solo se serve.
   Stanno in due chunk così restano leggibili e modificabili dal manager.
--------------------------------------------------------------------- */
if (!function_exists('ecmRegoleDaChunk')) {
    function ecmRegoleDaChunk($modx, $nome)
    {
        $chunk = $modx->getObject('modChunk', array('name' => $nome));
        if (!$chunk) {
            $modx->log(1 /* LOG_LEVEL_ERROR */, '[ecmIscrizione] chunk mancante: ' . $nome);
            return '';
        }
        $testo = $chunk->getContent();
        $testo = preg_replace('/<!--.*?-->/s', '', $testo);   // commenti
        $testo = preg_replace('/\s*\R\s*/', '', $testo);      // a capo e rientri
        return trim($testo, " \t,");
    }
}

$regole = ecmRegoleDaChunk($modx, 'ecmValidateBase');
if ($aPagamento) {
    $extra = ecmRegoleDaChunk($modx, 'ecmValidatePagamento');
    if ($extra !== '') {
        $regole .= ',' . $extra;
    }
}

/* ---------------------------------------------------------------------
   Esecuzione di FormIt.
   Va eseguito prima di rendere il form, perché è lui a impostare i
   placeholder fi.* con i valori inseriti e gli errori da mostrare.
--------------------------------------------------------------------- */
/* ---------------------------------------------------------------------
   Oggetto della conferma al partecipante.
   Questo snippet gira sulla stessa richiesta dell'invio, quindi i valori
   inviati sono già in $_POST: il cognome si può comporre qui, senza
   dipendere da come FormItAutoResponder tratta i placeholder.
--------------------------------------------------------------------- */
$fiarSubject = 'Richiesta di iscrizione - ' . $titoloEvento;
$cognomePost = isset($_POST['surname']) ? trim(strip_tags((string) $_POST['surname'])) : '';
$nomePost    = isset($_POST['name']) ? trim(strip_tags((string) $_POST['name'])) : '';
$personaPost = trim($cognomePost . ' ' . $nomePost);
if ($personaPost !== '') {
    $fiarSubject .= ' - ' . $personaPost;
}

/* ---------------------------------------------------------------------
   Hook antispam.
   Vanno in testa alla catena: se l'invio è respinto non deve essere né
   salvato né spedito.

   recaptchav3 è attivo per impostazione predefinita; si spegne con
   &recaptcha=`0` mentre si collauda il form.

   L'hook spam (Stop Forum Spam) è invece disattivato: ragiona anche per
   indirizzo IP, e gli iscritti che escono dalla rete di un ospedale
   condividono un solo IP pubblico. Un falso positivo lì costa
   un'iscrizione persa. Si attiva con &spamCheck=`1` se un domani lo
   spam diventasse un problema reale.
--------------------------------------------------------------------- */
$hooks = array();
if ((string) $modx->getOption('recaptcha', $scriptProperties, 1) === '1') {
    $hooks[] = 'recaptchav3';
}
if ((string) $modx->getOption('spamCheck', $scriptProperties, 0) === '1') {
    $hooks[] = 'spam';
}
$hooks = array_merge($hooks, array(
    'ecmPreparaDati', 'FormItSaveForm', 'email', 'FormItAutoResponder', 'redirect'
));

/* Reply-To della notifica: l'indirizzo dell'iscritto, così chi legge in
   ufficio risponde a lui e non alla casella mittente. Va passato come
   indirizzo vero, non come nome di campo: FormIt non lo risolve. Il
   valore è validato perché finisce in un'intestazione di posta. */
$emailPost = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$replyTo = filter_var($emailPost, FILTER_VALIDATE_EMAIL) ? $emailPost : $primoDestinatario;

$proprieta = array(
    'hooks'            => implode(',', $hooks),
    'formName'         => $risorsa->get('alias'),
    'formEncrypt'      => $modx->getOption('formEncrypt', $scriptProperties, 1),
    'customValidators' => 'ecmRichiestoSe,ecmValidatori',
    'validate'         => $regole,
    'validationErrorMessage' => 'Controlla i campi segnalati.',
    'spamEmailFields'  => 'email',

    'emailTpl'         => 'ecmEmailAdmin',
    'emailTo'          => $emailTo,
    'emailFrom'        => $emailFrom,
    'emailFromName'    => $emailNome,
    'emailReplyTo'     => $replyTo,
    'emailUseFieldForSubject' => 1, // l'oggetto lo compone ecmPreparaDati

    'fiarTpl'          => 'ecmEmailAutoresponder',
    'fiarToField'      => 'email',
    'fiarSubject'      => $fiarSubject,
    // Deve essere lo stesso mittente autorizzato dal server SMTP: il
    // partecipante risponde comunque al servizio formazione grazie al
    // Reply-To qui sotto.
    'fiarFrom'         => $emailFrom,
    'fiarFromName'     => $emailNome,
    // Un solo indirizzo: FormIt passa fiarReplyTo a PHPMailer come indirizzo
    // singolo, e una lista separata da virgole fa fallire la validazione
    // lasciando la mail senza Reply-To. Per far arrivare le risposte a più
    // persone serve una casella condivisa: vedi Punti aperti nel README.
    'fiarReplyTo'      => $primoDestinatario,

    // Disponibili nei due template di posta. Per impostazione predefinita
    // il dettaglio nella conferma è lo stesso sottotitolo mostrato sulla
    // pagina: si scavalca con &dettagliEvento se nella mail serve altro.
    'nomeEvento'       => $titoloEvento,
    'segreteria'       => $modx->getOption('segreteria', $scriptProperties, $segreteria),
    'avvisoDestinatario' => $ripiegoDestinatario
        ? 'Questa notifica è arrivata qui perché per l\'evento non risulta configurato alcun destinatario valido: '
          . 'il campo "Destinatari notifiche" della risorsa è vuoto o contiene indirizzi errati. '
          . 'Chi gestisce il sito dovrebbe correggerlo e inoltrare intanto questa iscrizione a chi di competenza.'
        : '',
    'dataLuogo'        => $modx->getOption('dataLuogo', $scriptProperties, $dataLuogo),
    'dettagliEvento'   => $modx->getOption('dettagliEvento', $scriptProperties, $sottotitolo),
);

if ($redirectTo !== '') {
    $proprieta['redirectTo'] = $redirectTo;
}

/* ---------------------------------------------------------------------
   Qualunque altra proprietà passata alla chiamata viene inoltrata a
   FormIt così com'è. Senza questo, scrivere &emailBCC o &emailCC nella
   chiamata non avrebbe alcun effetto e non se ne avrebbe segnalazione:
   la proprietà resterebbe in $scriptProperties di questo snippet senza
   mai raggiungere FormIt.

   Le chiavi elencate qui sotto sono consumate dallo snippet e non hanno
   significato per FormIt, quindi non vengono inoltrate.
--------------------------------------------------------------------- */
$soloSnippet = array(
    'evento', 'titolo', 'sottotitolo', 'preTitolo', 'dataLuogo',
    'dettagliEvento', 'etaMinima', 'recaptcha', 'spamCheck',
);
foreach ($scriptProperties as $chiave => $valore) {
    if (!in_array($chiave, $soloSnippet, true)) {
        $proprieta[$chiave] = $valore;
    }
}

// L'inoltro qui sopra rimetterebbe in gioco un &emailTo grezzo, saltando
// la validazione degli indirizzi: si riafferma il valore già ripulito.
$proprieta['emailTo'] = $emailTo;
$proprieta['emailReplyTo'] = $replyTo;

// Serve a ecmPreparaDati per comporre l'oggetto della notifica: l'hook
// gira su questa richiesta ma non sa da quale risorsa vengono le TV.
$modx->setPlaceholder('ecmNomeEvento', $titoloEvento);

$modx->runSnippet('FormIt', $proprieta);

/* ---------------------------------------------------------------------
   Rendering del form.
   Il blocco fatturazione viene passato come contenuto grezzo: i tag che
   contiene li risolve il parser sull'output di questo snippet.
--------------------------------------------------------------------- */
$bloccoFatturazione = '';
if ($aPagamento) {
    $chunk = $modx->getObject('modChunk', array('name' => 'ecmBloccoFatturazione'));
    if ($chunk) {
        $bloccoFatturazione = $chunk->getContent();
    } else {
        $modx->log(1 /* LOG_LEVEL_ERROR */, '[ecmIscrizione] chunk ecmBloccoFatturazione mancante');
    }
}

return $modx->getChunk('ecmFormIscrizione', array(
    'preTitolo'          => $modx->getOption('preTitolo', $scriptProperties, 'Modulo di iscrizione'),
    'titolo'             => $modx->getOption('titolo', $scriptProperties, $titoloEvento),
    'sottotitolo'        => $modx->getOption('sottotitolo', $scriptProperties, $sottotitolo),
    'dataLuogo'          => $modx->getOption('dataLuogo', $scriptProperties, $dataLuogo),
    'informativa'        => $informativa,
    'dataMax'            => date('Y-m-d', strtotime('-' . $etaMinima . ' years')),
    'bloccoFatturazione' => $bloccoFatturazione,
));