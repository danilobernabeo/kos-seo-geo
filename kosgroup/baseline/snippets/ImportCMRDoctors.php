<?php
/**
 * ImportCMRDoctors
 * 
 * Snippet per importare i medici del Centro Medico Riabilitativo
 */

// Funzione helper per importare un singolo medico
function importDoctor($modx, $doctorData, $struttura_id, $struttura_title) {
    try {
        $doc = $modx->newObject('modResource');
        $timestamp = time();
        
        // Aggiungiamo il link dinamico alla struttura alla fine del contenuto
        $content = $doctorData['content'];
        // Sostituiamo l'ultimo paragrafo della struttura con il link dinamico
        $content = preg_replace(
            '/<h2>Struttura<\/h2><p>Centro Medico Riabilitativo<\/p>/',
            "<h2>Struttura</h2><p><a href=\"[[~{$struttura_id}]]\" title=\"{$struttura_title}\">{$struttura_title}</a></p>",
            $content
        );
        
        $doc->fromArray([
            "pagetitle" => $doctorData['pagetitle'],
            "alias" => $doctorData['alias'],
            "content" => $content,
            "parent" => 280,
            "template" => 8,
            "published" => 1,
            "context_key" => "web",
            "richtext" => 1,
            "createdon" => $timestamp,
            "createdby" => 1,
            "editedon" => $timestamp,
            "editedby" => 1,
            "publishedon" => $timestamp,
            "publishedby" => 1
        ]);

        if (!$doc->save()) {
            throw new Exception("Failed to save: {$doctorData['pagetitle']}");
        }

        // Create TV value
        $tv = $modx->newObject("modTemplateVarResource");
        $tv->set("tmplvarid", 28);
        $tv->set("contentid", $doc->get("id"));
        $tv->set("value", $struttura_id);
        
        if (!$tv->save()) {
            throw new Exception("TV save failed: {$doctorData['pagetitle']}");
        }

        return ["success" => true, "message" => "Imported: {$doctorData['pagetitle']}"];
    } catch (Exception $e) {
        return ["success" => false, "message" => $e->getMessage()];
    }
}

// Main import script
try {
    // Get struttura info
    $cmrQuery = $modx->newQuery("modResource");
    $cmrQuery->where([
        "parent" => 167,
        "pagetitle" => "Centro Medico Riabilitativo"
    ]);
    $cmr = $modx->getObject("modResource", $cmrQuery);
    
    if (!$cmr) {
        throw new Exception("Could not find Centro Medico Riabilitativo");
    }

    $struttura_id = $cmr->get('id');
    $struttura_title = $cmr->get('pagetitle');
    
    // Array con tutti i dottori
$doctors = [
    [
        'pagetitle' => 'Antonio Ballarino',
        'alias' => 'antonio-ballarino',
        'content' => '<h2>Unità</h2><p>Fisioterapia.<br></p><h2>Ruolo</h2><p>Fisioterapista.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Fisioterapia presso l&apos;Università degli Studi di Milano Diploma di Osteopatia (D.O.) nella metodologia <strong>BFD</strong>®.<br>(Biomeccanica Funzionale Dinamica®) Specializzazione in tecniche di terapia manuale.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Beatrice Baroni',
        'alias' => 'beatrice-baroni',
        'content' => '<h2>Unità</h2><p>Nutrizione.<br></p><h2>Ruolo</h2><p>Biologa nutrizionista.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata al corso di laurea magistrale in "Alimentazione e Nutrizione Umana" all&apos;Università degli Studi di Milano Iscritta all&apos;Ordine Nazionale dei Biologi.<br></p><h2>Aree mediche di interesse</h2><p>Nutrizione e malattie gastrointestinali, riequilibrio delle condizioni di disbiosi, disturbi dell&apos;alimentazione e della nutrizione; di terapie nutrizionali in pazienti affette da <strong>PCOS</strong>, alimentazione in gravidanza e allattamento, nell&apos;anziano, nel bambino, nelle patologie croniche e neurodegenerative, obesità, sindrome metabolica e diabete mellito Percorsi dedicati alla perdita di peso, alla prevenzione delle patologie croniche e cardiovascolari, delle sindromi metaboliche, diabete e degli stati infiammatori.<br> Alimentazione della donna durante gravidanza, allattamento e menopausa, con allergie e intolleranze, o preferenze di diete vegetariane/vegane.<br>Alimentazione dello sportivo (amatore e professionista).<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Michele Carangelo',
        'alias' => 'michele-carangelo',
        'content' => '<h2>Unità</h2><p>Fisioterapia.<br></p><h2>Ruolo</h2><p>Fisioterapista.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Fisioterapia presso l&apos;Università degli Studi di Genova, successivamente si è specializzato in terapia manuale e riabilitazione ortopedica e sportiva lavorando presso.<br>l&apos;U.C. Sampdoria.Nel 2023 ha conseguito il Diploma di Osteopatia (D.O.) presso il <strong>TCIO</strong>-Milano.</p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Gianluca Concardi',
        'alias' => 'gianluca-concardi',
        'content' => '<h2>Unità</h2><p>Medicina fisica e riabilitazione.<br></p><h2>Ruolo</h2><p>Fisiatra.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso l&apos;Università degli Studi di Milano.<br>Specializzazione in Medicina Fisica e Riabilitazione.<br></p><h2>Esperienze professionali</h2><p>Da gennaio 2022 è Direttore di Struttura Complessa del Dipartimento di Medicina Riabilitativa Intensiva ed Estensiva del P.G.R. di Cinisello Balsamo.Dal 2005 a dicembre 2021 è stato Dirigente Medico presso U.O.C. di Riabilitazione Specialistica dell&apos;Ospedale San Paolo di Milano, Responsabile dell&apos;attività ambulatoriale e Responsabile dell&apos;Ambulatorio Osteoporosi (Centro affiliato <strong>SIOMMMS</strong>).Dall&apos;anno accademico 2004/2005 è professore a contratto presso il Corso di Laurea in Fisioterapia dell&apos;Università degli Studi di Milano.<br>.</p><h2>Pubblicazioni</h2><p>Ha partecipato in qualità di Relatore a diversi Congressi inerenti le patologie del Metabolismo Osseo e la Riabilitazione in ambito sportivo.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p><h2>Incarichi in società scientifiche</h2><p>Socio Aggregato della Federazione Medico Sportiva Italiana.<br>.</p>'
    ],
    [
        'pagetitle' => 'Emanuele Cucchi',
        'alias' => 'emanuele-cucchi',
        'content' => '<h2>Unità</h2><p>Radiologia.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia e specializzato in Radiologia Diagnostica e Scienza delle Immagini, presso l&apos;Università degli Studi di Milano.<br></p><h2>Esperienze professionali</h2><p>clinico-diagnostica in Ecografia, <strong>TC</strong> toraco-addominale, colonna vertebrale ed articolare, <strong>RM</strong> toraco-addominale, colonna vertebrale ed articolare, radiologia tradizionale e mammografia.<br></p><h2>Pubblicazioni</h2><p>Autore e co-autore di oltre 45 pubblicazioni scientifiche nazionali ed internazionali.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Sara Fantoni',
        'alias' => 'sara-fantoni',
        'content' => '<h2>Unità</h2><p>Fisioterapia.<br></p><h2>Ruolo</h2><p>Fisioterapista.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata in Fisioterapia nel 2016 presso Università degli Studi di Milano.<br> Specializzata in Rieducazione Posturale Globale (metodo Souchard), <em>clinical</em> Pilates, Neurodinamica, Massoterapia e riabilitazione ortopedica.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Matteo Mariani',
        'alias' => 'matteo-mariani',
        'content' => '<h2>Unità</h2><p>Fisioterapia.<br></p><h2>Ruolo</h2><p>Fisioterapista.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato nel 2016 presso l&apos;Università degli Studi di Milano-Bicocca in Fisioterapia.<br>Specializzato in terapie fisiche, nel recupero dei disordini muscolo-scheletrici e nel recupero post infortunio negli atleti.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Cinzia Massafra',
        'alias' => 'cinzia-massafra',
        'content' => '<h2>Unità</h2><p>Endocrinologia.<br></p><h2>Ruolo</h2><p>Endocrinologa e diabetologa.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata in Medicina e Chirurgia dal (1994).<br>Specializzata in Endocrinologia e Malattie del Ricambio dal (1999).<br></p><h2>Esperienze professionali</h2><p>Lavora come specialista ambulatoriale in Diabetologia dal 2002.<br> Ha organizzato eventi pubblici educativi rivolti alla popolazione giovanile ed eventi formativi di aggiornamento scientifico tra professionisti".<br></p><h2>Aree mediche di interesse</h2><p>Svolge attività in libera professione con effettuazione di visite diabetologiche, endocrinologiche ed ecografie alla tiroide.<br>Conoscenza delle nuove tecnologie per il trattamento Diabete Mellito Tipo 1.<br>.</p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Beatrice Mazzucato',
        'alias' => 'beatrice-mazzucato',
        'content' => '<h2>Unità</h2><p>Psicologia e neuropsicologia.<br></p><h2>Ruolo</h2><p>Psicologa clinica.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata con lode in Psicologia Clinica e Neuropsicologia nel Ciclo di Vita presso l&apos;Università degli Studi di Milano-Bicocca si forma post-lauream in valutazione neuropsicologica e comportamentale dell&apos;adulto e dell&apos;anziano affetto da condizioni neurologiche presso l&apos;Istituto Neurologico Carlo Besta di Milano Psicoterapeuta in formazione presso Scuola Lombarda di Psicoterapia (<strong>SLOP</strong>).<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Andrea Moggi',
        'alias' => 'andrea-moggi',
        'content' => '<h2>Unità</h2><p>Podologia.<br></p><h2>Ruolo</h2><p>Podologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in podologia presso l&apos; Università degli studi di Milano, facoltà di Medicina e Chirurgia (2023).<br></p><h2>Esperienze professionali</h2><p>Tirocinio attivo nel reparto di podologia presso <strong>IRCCS</strong> Istituto Galeazzi - Sant&apos;Ambrogio di Milano, dove da gennaio 2024, ricopre il ruolo di Tutor di tirocinio.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Bruno Passaretti',
        'alias' => 'bruno-passaretti',
        'content' => '<h2>Unità</h2><p>Cardiologia.<br></p><h2>Ruolo</h2><p>Cardiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso l&apos;Università degli Studi di Milano.<br>Specializzazione in Cardiologia presso la Scuola di Specializzazione di Cardiologia I dell&apos;Università degli Studi di Milano.<br></p><h2>Esperienze professionali</h2><p>Ha diretto per circa dieci anni il Servizio di Cardiologia non Invasiva delle Cliniche Humanitas Gavazzeni di Bergamo e successivamente il Reparto di Cardiologia Clinica e dell&apos;<strong>UO</strong> di Riabilitazione dello stesso Ospedale; attualmente responsabile della Cardiologia di Humanitas Castelli, dal 2018 è inoltre Adjunct Professor all&apos;Humanitas University di Milano.<br></p><h2>Pubblicazioni</h2><p>Autore o coautore di circa 90 pubblicazioni e abstracts su riviste italiane e internazionali, ha organizzato congressi di ecocardiografia, ed è stato frequentemente relatore in numerosi congressi nazionali e internazionali.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p><h2>Incarichi in società scientifiche</h2><p>Fa parte della Segreteria Scientifica di Ecocardiochirurgia, gruppo formato da cardiologi, cardiochirurgi e anestesisti con lo scopo di aumentare lo scambio culturale e informativo fra le tre categorie professionali, implicate in egual misura nella cura delle patologie cardiovascolari.<br></p>'
    ],
    [
        'pagetitle' => 'Michelangelo Porretta',
        'alias' => 'michelangelo-porretta',
        'content' => '<h2>Unità</h2><p>Fisioterapia e osteopatia.<br></p><h2>Ruolo</h2><p>Fisioterapista e osteopata.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato con Lode in Fisioterapia presso l&apos;Università degli Studi di Milano.<br>Nel Novembre 2008 ha conseguito il Diploma di Osteopatia (D.O.) presso la S.O.M.A.  di Milano e nel 2023 il Master in Osteopatia Pediatrica presso <strong>TCIO</strong>-Milano .Specializzato in Riabilitazione ortopedica e neurologica, Taping Kinesiologico e Massoterapia.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Michele Quinto',
        'alias' => 'michele-quinto',
        'content' => '<h2>Unità</h2><p>Medicina Generale.<br></p><h2>Ruolo</h2><p>Chirurgo Generale convenzionato con <strong>SNN</strong> e Sanita&apos; Marittima e Aerea <strong>SASN</strong>-<strong>USMAF</strong> in Monza Direttore Medico di Nobis Assicurazioni SpA., Care Company srl, del poliambulatorio Ambulatorio Polispecialistico e Fisioterapico Pi.Emme.Gi.di Brugherio.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università degli Studi di Milano.<br></p><h2>Esperienze professionali</h2><p>Medico Perito esperto di valutazione del danno (perizie medico-legali e assicurative).<br></p><h2>Pubblicazioni</h2><p>ha pubblicato su riviste settoriali e non e coordinato il Servizio di Continuità Assistenziale della ex Azienda Sanitaria Locale di Monza e Brianza.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p><h2>Incarichi in società scientifiche</h2><p>Membro del Comitato Scientifico dell&apos;Istituto Internazionale di TeleMedicina e Rappresentante sindacale medico <strong>SNAMI</strong> nell&apos;area provinciale di Monza e .<br></p>'
    ],
    [
        'pagetitle' => 'Luca Sanavia',
        'alias' => 'luca-sanavia',
        'content' => '<h2>Unità</h2><p>Fisiatria.<br></p><h2>Ruolo</h2><p>Fisiatra.<br></p><h2>Formazione e Specializzazione</h2><p>Medico chirurgo, laureato all&apos;Università degli Studi di Milano nel 2011 sSpecializzazione in Medicina Fisica e Riabilitativa presso l&apos;Università di Pavia.<br></p><h2>Esperienze professionali</h2><p>Fino al 2015 ha lavorato come Medico di Guardia presso la clinica Villa San Giuseppe di Anzano del Parco; ha inoltre collaborato con la Croce Rossa di Como come medico ambulatoriale e con il <strong>CSI</strong> come medico di gare sportive.<br>Ha frequentato la Scuola Sowen di Agopuntura e Medicina Cinise a Milano e ottenuto il diploma di Agopuntore nel 2017.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Pietro Scamarcia',
        'alias' => 'pietro-scamarcia',
        'content' => '<h2>Unità</h2><p>Neurologia.<br></p><h2>Ruolo</h2><p>Neurologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia nel 2016 presso l&apos;università di Bologna.<br>Specializzato in Neurologia presso L&apos;università Vita-Salute San Raffaele  E14.<br></p><h2>Esperienze professionali</h2><p>Nel 2021 Ospedale di Treviglio-Caravaggio come Medico Specialista in Neurologia con attività di reparto generale e semi-intensiva (Stroke Unit), pronto soccorso ed ambulatoriale di primo e di secondo livello.<br>Dal 2023 svolge l&apos;attività di Specialista Neurologo presso il Dipartimento di Riabilitazione Neuromotoria del Polo Riabilitativo di Cinisello Balsamo (<strong>MI</strong>).<br>Nel corso degli anni ha intrapreso attività clinica e di ricerca con pubblicazioni su riviste scientifiche specializzate e partecipato in qualità di relatore a molteplici congressi nazionali ed internazionali.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ],
    [
        'pagetitle' => 'Giancarlo Sparpaglione',
        'alias' => 'giancarlo-sparpaglione',
        'content' => '<h2>Unità</h2><p>Ginecologia.<br></p><h2>Ruolo</h2><p>Ginecologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università Statale di Milano.<br>Specializzato in Ginecologia e Ostetricia nel 1983, e successivamente in Patologia della Riproduzione Umana nel 1988.<br></p><h2>Esperienze professionali</h2><p> È stato sino al 2018 Direttore dell&apos;Unità di Sala Parto dell&apos;Ospedale di Desio.<br>Attualmente è in servizio come Ginecologo presso il Policlinico di Monza.<br></p><h2>Struttura</h2><p>Centro Medico Riabilitativo</p>'
    ]
];
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($doctors as $doctor) {
        $result = importDoctor($modx, $doctor, $struttura_id, $struttura_title);
        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
        $results[] = $result['message'];
    }
    
    $summary = "Import completed.\nSuccessful imports: $successCount\nErrors: $errorCount\n\nDetails:\n";
    $summary .= implode("\n", $results);
    
    return $summary;
    
} catch (Exception $e) {
    return "Fatal error: " . $e->getMessage();
}