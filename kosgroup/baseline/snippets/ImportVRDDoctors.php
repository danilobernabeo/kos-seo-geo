<?php
/**
 * ImportVRDDoctors
 * 
 * Snippet per importare i medici del Villa Rosa Diagnostica
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
            '/<h2>Struttura<\/h2><p>Villa Rosa Diagnostica<\/p>/',
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
        "pagetitle" => "Villa Rosa Diagnostica"
    ]);
    $cmr = $modx->getObject("modResource", $cmrQuery);
    
    if (!$cmr) {
        throw new Exception("Could not find Villa Rosa Diagnostica");
    }

    $struttura_id = $cmr->get('id');
    $struttura_title = $cmr->get('pagetitle');
    
    // Array con tutti i dottori
$doctors = [
    [
        'pagetitle' => 'Dario Colasanto',
        'alias' => 'dario-colasanto',
        'content' => '<h2>Unità</h2><p>Radiologia.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università degli Studi di Bari - specializzato in Radiodiagnostica presso l&apos;Università di Modena.<br></p><h2>Esperienze professionali</h2><p>Libero professionista.<br></p><h2>Aree mediche di interesse</h2><p>Radiologia tradizionale, Ecografia, Risonanza Magnetica Body e Muscolo-Scheletrica.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Biagio Cotticelli',
        'alias' => 'biagio-cotticelli',
        'content' => '<h2>Unità</h2><p>Neurologia.<br></p><h2>Ruolo</h2><p>Neurologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso l&apos;Università degli Studi di Bologna.<br> Neurologia, Radiodiagnostica.<br></p><h2>Esperienze professionali</h2><p>Oggi.<br>Medico Neuroradiologo Libero Professionista presso il Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa.<br>2010 ad oggi.<br>Attività di consulenza neuroradiologica, attualmente in essere presso diverse Case di Cura di Reggio Emilia e Modena nonché Poliambulatori.<br>2008 - 2010.<br>Responsabile della Struttura Semplice di Neuroradiologia dell&apos;Ospedale di Carpi, afferente al Dipartimento Integrato di Neuroscienze.<br>3 Gennaio 1997 - 1 Aprile 1997.<br>Ha ricoperto un incarico, in qualità di Aiuto Corresponsabile Ospedaliero a tempo pieno, presso il Servizio di Neuroradiologia del Policlinico di Modena; presso lo stesso Servizio, dal 7 Aprile 1997 ha stipulato un contratto libero-professionale al quale ha rinunciato il 10 Maggio 1997.<br></p><h2>Aree mediche di interesse</h2><p>Neuroradiologia, Risonanza Magnetica per patologie cerebrali e spinali.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Gianni De Berti',
        'alias' => 'gianni-de-berti',
        'content' => '<h2>Unità</h2><p>Neurologia e Neuroradiologia.<br></p><h2>Ruolo</h2><p>Neurologo e Neuroradiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università degli Studi di Verona.<br>Specializzazione in Neurologia Clinica e in Radiodiagnostica, presso l&apos;Università di Modena.<br></p><h2>Esperienze professionali</h2><p>2012 ad oggi.<br>Medico Neuroradiologo Libero Professionista presso il Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa.<br>2012 ad oggi.<br>Consulente Libero Professionista Neuroradiologica presso Casa di Cura Villa Verde, <strong>RE</strong> e presso Medica San Giovanni in Persiceto (<strong>BO</strong>).<br>2006 - 2011.<br>Responsabile Struttura Semplice di Neuroradiologia ad indirizzo Interventistico presso l&apos;<strong>ASMN</strong> (<strong>RE</strong>).<br>2004 – 2006.<br>Titolare Alta Specialità in Neuroradiologia Interventistica.<br>2001 – 2011.<br>Dirigente Medico I livello - Arcispedale Santa Maria Nuova di Reggio Emilia.<br>1998 - 1999.<br>Stage - Barrow Neurological Institute di Phoenix (<strong>AZ</strong>, <strong>USA</strong>).<br>1997 - 2001.<br>Dirigente Medico – Servizio di Neuroradiologia del Policlinico di Modena.<br></p><h2>Aree mediche di interesse</h2><p>Neuroradiologia, Risonanza Magnetica per patologie cerebrali e spinali.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Massimo Maggi',
        'alias' => 'massimo-maggi',
        'content' => '<h2>Unità</h2><p>Neuroradiologia.<br></p><h2>Ruolo</h2><p>Neuroradiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia.<br>Specializzato in Radiodiagnostica presso l&apos;Università Politecnica delle Marche.<br></p><h2>Esperienze professionali</h2><p>Oggi.<br>Medico Neuroradiologo Libero Professionista presso il Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa.<br>2008 - 2016.<br>Dirigente Medico – S.S. di Neuroradiologia dell&apos;Azienda Ospedaliera Santa Maria Nuova di Reggio Emilia.<br>2009 – 2016.<br>Consulente Neuroradiologo per L&apos;<strong>AUSL</strong> di Reggio Emilia.<br>2007 – 2008.<br>Attività Libero professionale presso S.S. di Neuroradiologia dell&apos;Azienda Ospedaliera Santa Maria Nuova di Reggio Emilia.</p><h2>Aree mediche di interesse</h2><p>Neuroradiologia, Risonanza Magnetica per Patologie cerebrale e spinale.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Mauro Marchetti',
        'alias' => 'mauro-marchetti',
        'content' => '<h2>Unità</h2><p>Radiologia.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia e specializzato in Radiologia Diagnostica presso l&apos;Università degli Studi di Perugia.<br></p><h2>Esperienze professionali</h2><p>Oggi.<br>Dal 2016 Responsabile del Servizio di Radiologia dell&apos;Ospedale Villa Rosa di Modena.<br>2010 - 2019.<br>Responsabile del Servizio di Radiologia di Villa Verde (<strong>RE</strong>).<br>2000 - 2010.<br>Dirigente Medico, Direttore <strong>AUSL</strong> Reggio Emilia.<br>1997 - 2001.<br>Vice Presidente del gruppo regionale della Società Italiana di Radiologia Medica della Regione Emilia Romagna.<br>1983 - 2000.<br>Insegnante Università di Modena nelle scuole di specializzazione di nefrologia, radiologia, chirurgia vascolare.<br>1986 - 1994.<br>Aiuto corresponsabile ospedaliero di Radiodiagnostica presso l&apos;Usl 16 di Modena.<br>1977 - 1986.<br>Assistente di Radiodiagnostica presso gli Istituti ospedalieri Policlinico di Modena.<br>1975 - 1977.<br>Assistente di Radiodiagnostica presso il 1° Servizio di Radiodiagnostica dell&apos;Arcispedale S. Maria Nuova di Reggio Emilia.</p><h2>Aree mediche di interesse</h2><p>Radiologia Diagnostica.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Carlo Pagannone',
        'alias' => 'carlo-pagannone',
        'content' => '<h2>Unità</h2><p>Radiologia.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università di Roma "La Sapienza" e specializzato in Radiodiagnostica presso l&apos;Università di Modena.<br></p><h2>Esperienze professionali</h2><p>2016 ad oggi.<br>Attività libero professionale di consulente radiologo presso istituti privati dell&apos;Emilia Romagna convenzionati con il <strong>SSN</strong>.<br></p><h2>Aree mediche di interesse</h2><p>Radiologia tradizionale (Rx), Risonanza Magnetica "body" e muscolo-scheletrica, Ecografia (Addome, Tiroide, Parti Molli e Muscolo-Scheletrica).<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Gianluca Rampini',
        'alias' => 'gianluca-rampini',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia e specializzato in Radiodiagnostica presso l&apos;Universita&apos; di Modena.<br></p><h2>Esperienze professionali</h2><p>2016 ad oggi.<br>Attività libero professionale di Consulente Radiologo presso istituti privati dell&apos;Emilia Romagna convenzionati con il Servizio Sanitario Nazionale.<br></p><h2>Aree mediche di interesse</h2><p>Radiologia Convenzionale (Rx), Risonanza magnetica (<strong>RM</strong>) body e muscolo-scheletrica, Ecografia Addome superiore (fegato, pancreas, vie biliari), Ecografia Reni, Ecografia Pelvi Maschile e Femminile, Ecografia Tiroide, Ecografia Parti Molli e Muscolo-scheletrica.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Donatella Roncaglia',
        'alias' => 'donatella-roncaglia',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologa.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia c/o Università degli Studi di Modena e Reggio Emilia.<br>Specializzazione in Radiodiagnostica presso l&apos;Università degli Studi di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>2022 ad oggi.<br>Libero professionista Medico Radiologo presso Casa di cura Fogliani (<strong>MO</strong>), Villaverde (<strong>RE</strong>) e Villarosa (<strong>MO</strong>).<br>2021 - 2024.<br>Consigliere della sezione di Radiologia Muscoloscheletrica (<strong>SIRM</strong> società italiana di radiologia medica ed interventistica).<br>Dicembre 2006 – Maggio 2022.<br>Dirigente Medico Radiologo presso l&apos;Ospedale di Baggiovara (<strong>OCSAE</strong> Modena).<br>Gennaio2003 - Dicembre 2006.<br>Dirigente Medico Radiologo presso l&apos;Ospedale di Carpi (<strong>USL</strong> di Modena).<br></p><h2>Aree mediche di interesse</h2><p>Radiologia Convenzionale (Rx), Risonanza magnetica (<strong>RM</strong>) body.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Arcangela Rozza',
        'alias' => 'arcangela-rozza',
        'content' => '<h2>Unità</h2><p>Chirurgia vascolare.<br></p><h2>Ruolo</h2><p>Chirrugo vascolare.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata in Medicina e Chirurgia presso l&apos;Università degli Studi di Padova Specializzata in Chirurgia Vascolare presso l&apos;Università degli Studi di Modena.<br></p><h2>Esperienze professionali</h2><p>1998 ad oggi.<br>Attività di Consulenza e Diagnostica Vascolare presso Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa di Modena.<br>1998 ad oggi.<br>Chirurgia Vascolare arteriosa e venosa presso vari Ospedali accreditati.<br>2003 ad oggi.<br>Direttore Sanitario e Chirurgo Vascolare presso Poliambulatorio Privato <strong>CDB</strong>, Bazzano (<strong>BO</strong>).<br>2002 ad oggi.<br>Attività Libero Professionale di Consulenza e Diagnostica Vascolare, Terapia Flebologica, Laserterapia e Scleroterapia presso diversi Poliambulatori Privati e/o Privati Accreditati di Modena e Bologna e relative provincie.<br>1992 – 1997.<br>Attività di Collaborazione di Sala Operatoria e ambulatoriale, presso la Cattedra di Chirurgia Vascolare del Policlinico Universitario di Modena.<br></p><h2>Aree mediche di interesse</h2><p>Ecocolordoppler Vascolare Venoso ed Arterioso: arti superiori, arti inferiori, carotidi e vertebrali, aorta addominale, arterie iliache.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Nicola Santini',
        'alias' => 'nicola-santini',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia c/o Università degli Studi di Modena e Reggio Emilia.<br>Specializzazione in Radiodiagnostica.<br></p><h2>Esperienze professionali</h2><p>Ottobre 2018 - Gennaio 2024.<br>Dipendente dell&apos;Ospedale di Sassuolo s.p.a.<br>come Medico Radiologo.<br>In tale periodo è stato tra i medici di riferimento per U.S. Sassuolo Calcio ed ha partecipato settimanalmente a team multidisciplinari in ambito Uro-oncologico.<br>Agosto 2017 - Ottobre 2018.<br>Attività da Libero Professionista presso Villa Verde (Reggio Emilia), Villa Rosa (Modena) e Bo.Di.<br>- Terme San Petronio-Antalgik (Bologna).<br>Durante periodi di formazione e successivo ha partecipato come Docente a congressi/corsi di formazione, con particolare focus sulla patologia uro-genitale.<br>Pubblicazione di due articoli scientifici su argomenti riguardanti la patologia Oncologica Prostatica.<br></p><h2>Aree mediche di interesse</h2><p>Radiologia Convenzionale (Rx), Risonanza magnetica (<strong>RM</strong>) body, Ecografia, <strong>TC</strong>.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Riccardo Scaglioni',
        'alias' => 'riccardo-scaglioni',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologo ed ecografista.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia e specializzato in Radiodiagnostica presso l&apos;Università di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>2018 - 2021.<br>Specialista Radiologo presso l&apos;Azienda Ospedaliera Universitaria di Modena, sede Ospedale Civile di Baggiovara.<br>2016 - 2018.<br>Specialista Radiologo presso l&apos;Azienda Ospedaliera Universitaria di Modena, sede Policlinico di Modena.<br>2016.<br>Specialista Radiologo presso l&apos;Azienda Arcispedale Santa Maria Nuova (<strong>RE</strong>).<br>2016.<br>Specialista Radiologo presso Casa di Cura Villa Bianca (<strong>TN</strong>).<br></p><h2>Aree mediche di interesse</h2><p>Radiologia tradizionale (Rx), Ecografia, Risonanza Magnetica "body" e Muscolo-Scheletrica Imaging integrato Toraco-Addominale e della patologia Capo-Collo.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Roberto Suozzi',
        'alias' => 'roberto-suozzi',
        'content' => '<h2>Unità</h2><p>Neurologia.<br></p><h2>Ruolo</h2><p>Neurologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia, specializzato in Neurologia presso l&apos;Università di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>2002 ad oggi.<br>Attività libero-professionale per Esami Elettromiografici presso il Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa.<br>2013 – 2014.<br>Attività libero-professionale per esami elettromiografici e visite neurologiche presso il Poliambulatorio Privato Città di Carpi di Fossoli di Carpi (<strong>MO</strong>).<br>2009 - 2016.<br>Attività libero-professionale per esami elettromiografici e visite neurologiche presso Casa di Cura Prof. Fogliani di Modena.<br>2007 – 2015.<br>Attività libero-professionale per esami elettromiografici e visite neurologiche presso il Centro Medico Gonzaga di Guastalla (<strong>RE</strong>).<br>2006 – 2013.<br>Attività libero-professionale per esami elettromiografici e visite neurologiche presso Hospital Piccole Figlie di Parma.<br>2002 – 2005.<br>Attività libero-professionale per attività di guardia neurologica e visite neurologiche preso Policlinico di Modena.<br></p><h2>Aree mediche di interesse</h2><p>Malattie Neuromuscolari, Elettromiografia.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Elena Vezzelli',
        'alias' => 'elena-vezzelli',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologia.<br></p><h2>Formazione e Specializzazione</h2><p>Laureata in Medicina e Chirurgia presso l&apos;Universita&apos; di Modena e Reggio Emilia.<br>Specializzata con lode in Radiodiagnostica presso l&apos;Ateneo di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>2016 ad oggi.<br>Attività di Medico Radiologo libero professionista presso strutture sanitarie private dell&apos;Emilia Romagna convenzionate con il servizio Sanitario Nazionale.<br></p><h2>Aree mediche di interesse</h2><p>Radiologia Convenzionale (<strong>RX</strong>), Risonanza Magnetica Body, Ecografia Body.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Gabriele Zelent',
        'alias' => 'gabriele-zelent',
        'content' => '<h2>Unità</h2><p>Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università di Modena e Reggio Emilia, e specializzato in Radiodiagnostica presso l&apos;Università di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>2017 Libero professionista presso la Neuroradiologia dell&apos;Azienda <strong>USL</strong> di Modena (Ospedale Civile S. Agostino Estense di Baggiovara) 2017 - 2021 Dirigente medico di Neuroradiologia presso l&apos;Azienda <strong>USL</strong> di Modena.2018 - 2021 Docente a contratto presso il dipartimento di Scienze Mediche e Chirurgiche materno-infantili e dell&apos;adulto, Università degli Studi di Modena e Reggio Emilia, corso di laurea <strong>TSRM</strong>.<br></p><h2>Aree mediche di interesse</h2><p>Neuroradiologia, Risonanza Magnetica per patologie cerebrali e spinali.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
    ],
    [
        'pagetitle' => 'Paolo Zonari',
        'alias' => 'paolo-zonari',
        'content' => '<h2>Unità</h2><p>Neurologia/Radiodiagnostica.<br></p><h2>Ruolo</h2><p>Neurologo e radiologo.<br></p><h2>Formazione e Specializzazione</h2><p>Laureato in Medicina e Chirurgia presso l&apos;Università degli Studi di Bologna.<br>Primo Diploma di Specializzazione in Neurologia, presso la Clinica Neurologica dell&apos;Università degli Studi di Modena e Reggio Emilia.<br>Secondo Diploma di Specializzazione in Radiodiagnostica presso l&apos;Istituto di Radiologia dell&apos;Università degli Studi di Modena e Reggio Emilia.<br></p><h2>Esperienze professionali</h2><p>Oggi.<br>Medico Neuroradiologo Libero Professionista presso il Poliambulatorio Diagnostico Specialistico Accreditato Villa Rosa.<br>2010 ad oggi.<br>Attività di consulenza neuroradiologica, attualmente in essere presso diverse Case di Cura di Reggio Emilia e Modena nonché Poliambulatori.<br>2008 - 2010.<br>Responsabile della Struttura Semplice di Neuroradiologia dell&apos;Ospedale di Carpi, afferente al Dipartimento Integrato di Neuroscienze.<br>3 Gennaio 1997 - 1 Aprile 1997.<br>Ha ricoperto un incarico, in qualità di Aiuto Corresponsabile Ospedaliero a tempo pieno, presso il Servizio di Neuroradiologia del Policlinico di Modena; presso lo stesso Servizio, dal 7 Aprile 1997 ha stipulato un contratto libero-professionale al quale ha rinunciato il 10 Maggio 1997.<br></p><h2>Aree mediche di interesse</h2><p>Neuroradiologia, Risonanza Magnetica per patologie cerebrali e spinali.<br></p><h2>Struttura</h2><p>Villa Rosa Diagnostica</p>'
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