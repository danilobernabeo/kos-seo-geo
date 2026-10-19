<?php
/**
 * ecmValidatori — validatori personalizzati per FormIt 5.x / MODX 3.x
 *
 * Un solo snippet che espone più regole, scelte con il parametro.
 * Registrarlo una volta in &customValidators e richiamarlo così:
 *
 *   fiscalcode:ecmValidatori=^codiceFiscale^
 *   fself_piva:ecmValidatori=^partitaIva^
 *   fsid:ecmValidatori=^destinatarioSdi^
 *   allegato:ecmValidatori=^allegato:5:pdf,jpg,jpeg,png^
 *
 * Ogni regola accetta un campo vuoto: l'obbligatorietà si dichiara a
 * parte con :required oppure con ecmRichiestoSe. Questo permette di
 * combinare le due cose senza duplicare messaggi.
 */

$regola = (string) $param;
$argomenti = array();
if (strpos($regola, ':') !== false) {
    $pezzi = explode(':', $regola);
    $regola = array_shift($pezzi);
    $argomenti = $pezzi;
}

$val = is_array($value) ? '' : trim((string) $value);

switch ($regola) {

    /* ---------------------------------------------------------------
       Codice fiscale: formato e carattere di controllo.
       Il controllo del check digit intercetta gli errori di battitura,
       che sui codici fiscali sono la causa più frequente di scarti
       nell'attribuzione dei crediti.
    --------------------------------------------------------------- */
    case 'codiceFiscale':
        if ($val === '') return true;
        $cf = strtoupper(preg_replace('/\s+/', '', $val));

        if (!preg_match('/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/', $cf)) {
            return 'Il codice fiscale non è nel formato corretto (16 caratteri).';
        }

        $dispari = array(
            '0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,
            'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,
            'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,
            'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23,
        );

        $somma = 0;
        for ($i = 0; $i < 15; $i++) {
            $c = $cf[$i];
            if (($i % 2) === 0) {            // posizione dispari contando da 1
                $somma += $dispari[$c];
            } else {                          // posizione pari
                $somma += ctype_digit($c) ? (int) $c : (ord($c) - 65);
            }
        }

        if (chr(65 + ($somma % 26)) !== $cf[15]) {
            return 'Il codice fiscale non è valido: controlla di averlo digitato correttamente.';
        }
        return true;

    /* ---------------------------------------------------------------
       Partita IVA: 11 cifre con controllo di Luhn secondo DPR 605/1973.
    --------------------------------------------------------------- */
    case 'partitaIva':
        if ($val === '') return true;
        $piva = preg_replace('/\D/', '', $val);
        if (strlen($piva) !== 11) {
            return 'La partita IVA deve essere di 11 cifre.';
        }
        $somma = 0;
        for ($i = 0; $i < 11; $i++) {
            $n = (int) $piva[$i];
            if (($i % 2) === 1) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $somma += $n;
        }
        if (($somma % 10) !== 0) {
            return 'La partita IVA non è valida: controlla di averla digitata correttamente.';
        }
        return true;

    /* ---------------------------------------------------------------
       Codice destinatario SDI (7 caratteri) oppure PEC.
    --------------------------------------------------------------- */
    case 'destinatarioSdi':
        if ($val === '') return true;
        if (preg_match('/^[A-Za-z0-9]{6,7}$/', $val)) return true;
        if (filter_var($val, FILTER_VALIDATE_EMAIL)) return true;
        return 'Indica un codice destinatario di 7 caratteri oppure un indirizzo PEC.';

    /* ---------------------------------------------------------------
       Allegato: tipo e dimensione. Il file non viene salvato su disco,
       resta nella cartella temporanea finché l'hook email lo allega.
       Argomenti: dimensione massima in MB, estensioni ammesse.
    --------------------------------------------------------------- */
    case 'allegato':
        $maxMb = isset($argomenti[0]) ? (float) $argomenti[0] : 5;
        $ammesse = isset($argomenti[1])
            ? array_map('strtolower', array_map('trim', explode(',', $argomenti[1])))
            : array('pdf', 'jpg', 'jpeg', 'png');

        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return true; // vuoto: se ne occupa :required
        }

        $file = $_FILES[$key];

        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return 'Il file supera la dimensione massima consentita dal server.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Non è stato possibile caricare il file. Riprova.';
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return 'Il file supera ' . rtrim(rtrim(number_format($maxMb, 1, ',', ''), '0'), ',') . ' MB.';
        }

        $est = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($est, $ammesse, true)) {
            return 'Formati ammessi: ' . strtoupper(implode(', ', $ammesse)) . '.';
        }

        // Verifica del contenuto reale, non della sola estensione.
        $mimeAmmessi = array('application/pdf', 'image/jpeg', 'image/png');
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
        if ($mime !== '' && !in_array($mime, $mimeAmmessi, true)) {
            return 'Il contenuto del file non corrisponde a un PDF o a un\'immagine.';
        }
        return true;

    /* ---------------------------------------------------------------
       CAP: cinque cifre.
    --------------------------------------------------------------- */
    case 'cap':
        if ($val === '') return true;
        return preg_match('/^[0-9]{5}$/', $val) ? true : 'Il CAP deve essere di 5 cifre.';

    /* ---------------------------------------------------------------
       Data di nascita: accetta sia aaaa-mm-gg (input type=date) sia
       gg/mm/aaaa (compilazione manuale), rifiuta date future e non
       plausibili. Il controllo lato server serve perché il browser
       non impedisce l'invio di un POST costruito a mano.
       Argomenti: età minima, età massima.
    --------------------------------------------------------------- */
    case 'dataNascita':
        if ($val === '') return true;
        $etaMin = isset($argomenti[0]) ? (int) $argomenti[0] : 16;
        $etaMax = isset($argomenti[1]) ? (int) $argomenti[1] : 110;

        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $val, $m)) {
            $g = (int) $m[1]; $me = (int) $m[2]; $a = (int) $m[3];
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m)) {
            $a = (int) $m[1]; $me = (int) $m[2]; $g = (int) $m[3];
        } else {
            return 'Indica la data nel formato gg/mm/aaaa.';
        }

        if (!checkdate($me, $g, $a)) {
            return 'La data indicata non esiste.';
        }

        $nascita = new \DateTime(sprintf('%04d-%02d-%02d', $a, $me, $g));
        $oggi = new \DateTime('today');
        if ($nascita > $oggi) {
            return 'La data di nascita non può essere nel futuro.';
        }
        $eta = (int) $nascita->diff($oggi)->y;
        if ($eta < $etaMin || $eta > $etaMax) {
            return 'Controlla la data di nascita: l\'anno indicato non sembra corretto.';
        }
        return true;
}

$modx->log(1 /* LOG_LEVEL_ERROR */, '[ecmValidatori] regola sconosciuta: ' . $regola);
return true;