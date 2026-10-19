<?php
/**
 * ImportMYODoctors
 * 
 * Snippet per importare i medici di Myolab
 */

// Funzione helper per importare un singolo medico
function importDoctor($modx, $doctorData, $struttura_id) {
    try {
        $doc = $modx->newObject('modResource');
        $timestamp = time();
        
        $cleanData = [
            "pagetitle" => stripslashes($doctorData['pagetitle']),
            "alias" => stripslashes($doctorData['alias']),
            "content" => stripslashes($doctorData['content']),  // Rimuove l'escape dagli apici
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
        ];

        $doc->fromArray($cleanData);

        if (!$doc->save()) {
            throw new Exception("Failed to save: " . $doctorData['pagetitle']);
        }

        // Create TV value
        try {
            $tv = $modx->newObject("modTemplateVarResource");
            $tv->set("tmplvarid", 28);
            $tv->set("contentid", $doc->get("id"));
            $tv->set("value", $struttura_id);
            $tv->save();
        } catch (Exception $e) {
            return [
                "success" => true, 
                "message" => "Imported: " . $doctorData['pagetitle'] . " (TV warning: " . $e->getMessage() . ")"
            ];
        }

        return ["success" => true, "message" => "Imported: " . $doctorData['pagetitle']];
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
    
    // Array con tutti i dottori
$doctors = [
[
    'pagetitle' => 'Roberto Baldoni',
    'alias' => 'roberto-baldoni',
    'content' => '<h2>Unità</h2><p>Ortopedia e Traumatologia</p><h2>Ruolo</h2><p>Ortopedico</p><h2>Formazione e Specializzazione</h2><p>Medico Chirurgo specialista in Ortopedia e Traumatologia laureato e specializzato presso l\'Università degli studi di Ancona</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Marinella Baroni',
    'alias' => 'marinella-baroni',
    'content' => '<h2>Unità</h2><p>Fisiatria</p><h2>Ruolo</h2><p>Fisiatra</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso Università degli Studi di Bologna<br>Specialista in Neurologia presso l\'università degli studi di Ancona il 18/07/1983<br>Specialista in Fisioterapia presso l\'università degli studi di Ancona il 30/06/1987</p><h2>Esperienze professionali</h2><p>Direttore Unità Operativa Medicina Fisica e Riabilitazione Area Vasta N.2 - Ospedale “A. Murri” Jesi dal 01/01/2000 AL 31/10/2018<br>Realizzatrice del “Centro di riabilitazione per le disfunzioni pelvi-perineali” presso l’’Ospedale “A. Murri” di Jesi<br>Titolare dei seguenti incarichi:<br>Direttore del Dipartimento di “Riabilitazione” Zona Territoriale n 5-Jesi dal 2002 al 2004<br>Direttore del Dipartimento “Cure adulti e anziani” Zona Territoriale n 5-Jesi dal 2005 al 2006</p><h2>Aree mediche di interesse</h2><p>Riabilitazione Neurologica<br>Riabilitazione delle disfunzioni pelvi-perineali<br>Riabilitazione ortopedico-traumatologica<br>Riabilitazione delle disfunzioni del rachide<br>Riabilitazione dei paramorfismi/dismorfismi del rachide in età evolutiva</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Cesare Boria',
    'alias' => 'cesare-boria',
    'content' => '<h2>Unità</h2><p>Cardiologia e Medicina dello Sport</p><h2>Ruolo</h2><p>Cardiochirurgo<br>Direttore Sanitario Medicina dello Sport</p><h2>Formazione e Specializzazione</h2><p>Medicina e Chirurgia (Università degli studi di Ancona, 1988)<br>Cardiologia (Università\' politecnica delle Marche, 1992)<br>Medicina dello sport (Università di Perugia, 2006)</p><h2>Esperienze professionali</h2><p>2022 ad oggi. Specialista cardiologo convenzionato con la AST ancona marche presso struttura poliambulatoriale di Sassoferrato.<br>1996-2022. Dirigente Medico 1° livello a tempo indeterminato ( o a tempo pieno) presso il reparto di Cardiologia -UTIC di Jesi.<br>1994. Assistente Medico Straordinario presso la U.S.L. 11 di Fabriano presso l\'Ospedale di Sassoferrato.<br>1992 - 1994. Assistente Medico Straordinario presso la U.S.L. 8 di Senigallia (l\'Ospedale di Ostra, l\'Ospedale di Corinaldo)</p><h2>Aree mediche di interesse</h2><p>Cardiologia (scompenso cardiaco, cardiopatoa ischemica e dislipidemie) <br>Vascolare (ecodoppler artero venoso vari distretti e carotidei)<br>Alimentazione sportiva</p><h2>Incarichi in società scientifiche</h2><p>Socio ordinario della FMSI (Federazione medico sportiva italiana) dal 1989<br>Socio ordinario SIC Sport<br>Responsabile sanitario FIGC per formazione e settore giovanile<br>Presidente della commissione regionale per l\'esame dei ricorsi avversi la certificazione di non idoneità alla pratica sportiva come da LR 33/94 e su nomina regionale su indicazione del direttore ASUR (2014-2022)</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Franco Burattini',
    'alias' => 'franco-burattini',
    'content' => '<h2>Unità</h2><p>Psicologia</p><h2>Ruolo</h2><p>Psicologo e Psicoterapeuta</p><h2>Formazione e Specializzazione</h2><p>Laurea in Sociologia presso l’Università Carlo Bò di Urbino<br>Diploma Clinico in Training Autogeno (C.I.S.S.P.A.T. Padova)<br>Specializzazione (MIUR) in Psicoterapia Sistemico/ Relazionale e Familiare (Roma)</p><h2>Esperienze professionali</h2><p>Torino - Sezione Marketing / Comunicazione Strategica FIAT<br>Jesi - Servizio Tossicodipendenze ( in collaborazione con la Neurologia/ Jesi)<br>Ancona - Consigliere e Tesoriere Ordine Psicologi Regione Marche<br>Jesi - Dipartimento Salute Mentale AV 2; direzione U.O.s. Promozione Salute Mentale e Supervisione Psicoterapia<br>Civitanova (sede Marche) - Didatta abilitato nei Corsi di Specializzazione in Psicoterapia (Istituto Europeo di Formazione, Consulenza Sistemica e Terapia Relazionale / MIUR)<br>Ancona - Revisione, ampliamento e direzione della rivista PSICOin (Ordine Psicologi Marche)<br>Jesi - Studio, realizzazione e direzione della rivista InfoMIND (DSM, AV2 )<br>Roma - Ospite RAI 1 mattina</p><h2>Aree mediche di interesse</h2><p>Psicologia Clinica: individuale, di coppia e gruppale<br>Formazioni, supervisione e consulenza: gruppi professionali e aziendali</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Antonio Capomagi',
    'alias' => 'antonio-capomagi',
    'content' => '<h2>Unità</h2><p>Chirurgia generale</p><h2>Ruolo</h2><p>Chirurgo generale</p><h2>Formazione e Specializzazione</h2><p>Medicina e Chirurgia (Università di Chieti, 1978)<br>Medicina dello Sport (Chieti, 1982)<br>Chirurgia Toracica (Ancona, 1987)<br>Chirurgia d’Urgenza (Bologna, 1992)</p><h2>Esperienze professionali</h2><p>2016 ad oggi. Consulente Chirurgia Generale e Colonproctologia (Villa Igea, Ancona)<br>2013-2015. Primario Chirurgia Generale (Villa Igea, Ancona)<br>2002-2013. Consulente Chirurgia Generale e Colonproctologia (Villa Igea, Ancona)<br>1996-2001. Primario Chirurgia Generale ed Endoscopia (Villa dei Pini, Civitanova Marche)<br>1980-1996. Aiuto chirurgo (IRCCS INRCA, Ancona)</p><h2>Aree mediche di interesse</h2><p>Colloqui psicologici individuali, di coppia e familiari<br>Chirurgia Generale<br>Chirurgia Mininvasiva/Laparoscopica<br>Chirurgia Colorettale<br>Colonproctologia</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Stefania D\'Acunto',
    'alias' => 'stefania-d-acunto',
    'content' => '<h2>Unità</h2><p>Neurologia</p><h2>Ruolo</h2><p>Neurologo</p><h2>Formazione e Specializzazione</h2><p>Medicina e Chirurgia (Università degli studi di Ancona ,1993)<br>Neurologia (Università degli Studi di Ancona,1997)</p><h2>Aree mediche di interesse</h2><p>Neurologia Generale<br>Neurogeriatria<br>Neuroriabilitazione<br>Malattie Neurodegenerative<br>Cerebrovasculopatie</p><h2>Incarichi in società scientifiche</h2><p>Visita Neurologica<br>Screening per Deterioramento Cognitivo<br>Certificazioni Neurologiche</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Luca Dei Giudici',
    'alias' => 'luca-dei-giudici',
    'content' => '<h2>Unità</h2><p>Ortopedia</p><h2>Ruolo</h2><p>Ortopedico</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso Università Politecnica delle Marche<br>Ortopedia e Traumatologia</p><h2>Esperienze professionali</h2><p>Chirurgia Artroscopica e Protesica del Ginocchio c/o ICOT Latina con il dr. Agostino Tucciarone, dal 2009 al 2012.<br>Chirurgia Artroscopica della Spalla c/o ICOT Latina con il dr. Claudio Chillemi, dal 2010 al 2012.<br>Ortopedia e Traumatologia Dell’adulto c/o Ospedali Riuniti di Ancona con il dr. Antonio Gigante, dal 2012 al 2017.<br>Chirurgia Artroscopica e Protesica della Spalla c/o Ospedale Sacro Cuore di Negrar (VR), con il dr. Paolo Avanzi, dal 2015 al 2017.<br>Chirurgia Cartilaginea e Meniscale del Ginocchio c/o Ospedale Sacro Cuore di Negrar (VR) con i dr. Vincenzo Condello e Vincenzo Madonna, dal 2015 al 2017.<br>Chirurgia Protesica del Ginocchio c/o Ospedale Sacro Cuore di Negrar (VR) con il dr. Claudio Zorzi, dal 2015 al 2017.<br>Vincitore del “Travelling Fellowship” Sigascot-AAA, ospite dell’Associazione Argentina di Artroscopia, approfondendo la chirurgia della TRAUMATOLOGIA DELLO SPORTIVO (SPALLA E GINOCCHIO) nel 2016, con i dr. Daniel Slulitell, Pablo Narbona, Tomas Villaseca, Fernando Barrera-Oro, Martin Bisso.<br>Vincitore del Premio “Best Poster Sigascot” Nel 2016, con il lavoro: Misurazioni in TC per l’allineamento patello-femorale. Studio interosservatore e di riproducibilità.<br>Vincitore del “Travelling Fellowship” Esska Travelling Fellowship Arthrex Sport Medicine 2018, Ospedale di Monaco di Baviera con il dr. Hermann O Mayr.<br>Socio Attivo di SIA, SIGASCOT, SIOT, SICOOP, ESSKA, ESSKA-ESA.</p><h2>Aree mediche di interesse</h2><p>Chirurgia Artroscopica della Spalla<br>Chirurgia Artroscopica del Ginocchio<br>Chirurgia Cartilaginea<br>Chirurgica Protesica della Spalla<br>Chirurgia Protesica del Ginocchio</p><h2>Pubblicazioni</h2><p>Autore di 19 articoli scientifici in extenso indicizzati e/o impattati (al 2018).<br><br>Autore di oltre 60 lavori congressuali (poster e presentazioni orali) per le principali manifestazioni Nazionali e Internazionali (al 2018).<br><br>Reviewer per 5 riviste ortopediche internazionali indicizzate e impattate, nonché insignito del “Certificate of Excellence” per l’anno 2017 dalla rivista americana “Arthroscopy”.<br><br>Docente E Tutor c/o la Scuola di Artroscopia della Società Italiana di Artroscopia dal 2017.</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Rolando Gagliardini',
    'alias' => 'rolando-gagliardini',
    'content' => '<h2>Unità</h2><p>Pediatria/Pneumologia</p><h2>Ruolo</h2><p>Pediatra e Pneumologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia (Università di Bologna)<br>Pediatria<br>Pneumologia</p><h2>Aree mediche di interesse</h2><p>Pneumopatie Acute e Croniche<br>Allergie Respiratorie e Alimentari<br>Asma<br>Malattie Respiratorie ricorrenti<br>Tosse Cronica/ Ricorrente<br>Rinosinusiti<br>Otiti<br>Bronchiti/ Bronchioliti/ Polmoniti<br>Orticaria<br>Dermatite Atopica<br>Patologie Gastroenterologiche/ Nutrizionali/ Alimentari Pediatriche<br>Fibrosi Cistica e malattie correlate<br>Pediatria Generale</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Claudio Gambini',
    'alias' => 'claudio-gambini',
    'content' => '<h2>Unità</h2><p>Cardiologia/Medicina dello Sport</p><h2>Ruolo</h2><p>Cardiochirurgo<br>Medico dello Sport</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia (Università degli Studi Di Bologna)<br>Cardiologia<br>Medicina dello Sport</p><h2>Esperienze professionali</h2><p>Cardiologo ospedaliero dal 1975 al 2008 (assistenza ordinaria e di terapia intensiva cardiologica). Ecocardiografia transtoracica e transesofagea, impianto pace-maker, ergometria (associata anche alla cardiologia nucleare), ecodoppler vasi epiaortici.</p><h2>Aree mediche di interesse</h2><p>Cardiologia: Ecocardiografia-Ecocolordoppler Transtoracica<br>Test Ergometrico<br>Elettrocardiogramma Dinamico sec Holter<br>Idoneità Medico Sportiva Agonistica</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Mirko Gigli',
    'alias' => 'mirko-gigli',
    'content' => '<h2>Unità</h2><p>Podologia</p><h2>Ruolo</h2><p>Podologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Podologia (Università di Medicina e Chirurgia, Firenze)<br>Master in Podolodia dello Sport (Università Cattolica del Sacro Cuore, Roma Agostino Gemelli)<br>Podologia dello Sport</p><h2>Esperienze professionali</h2><p>2017. Collaborazione Camp Scherma Jesi con Garozzo, Vezzali, Trillini, Aspromonte, Volpi e Annalisa Coltorti<br>2017. Collaborazione Camp Taekwondo Carlo Molfetta<br>2017. Collaborazione Camp Ginnastica Artistica Jury Chechi<br>2015. Collaborazione Ss.Lazio Atletica<br>2013-2014 Podologo ufficiale F.C. Juventus</p><h2>Aree mediche di interesse</h2><p>Podologia<br>Posturologia<br>Ortopedia<br>Diabetologia</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Paolo Giudici',
    'alias' => 'paolo-giudici',
    'content' => '<h2>Unità</h2><p>Ortopedia/Traumatologia/Osteopatia</p><h2>Ruolo</h2><p>Ortopedico, Traumatologo e Osteopata</p><h2>Formazione e Specializzazione</h2><p>2004 - master in osteopatia biodinamica (durata 4 anni)<br>2000 - diploma in osteopatia DO<br>1993 - specializzazione in ortopedia (università degli studi di Milano)<br>1988 l- aurea in medicina e chirurgia (università degli studi di Milano)<br>Medicina e Chirurgia Ortopedia Scoliosi e patologia vertebrale dell\'età evolutiva. Medicina manuale e Osteopatia</p><h2>Esperienze professionali</h2><p>Dal 1991 al 2014 medico specialista interno presso la Fondazione IRCCS Don Gnocchi ambulatorio scoliosi.<br>Nel 1993 borsista presso istituto ortopedico Galeazzi Milano.<br>Dal 1986 al 1989 studente interno presso istituto ortopedico G.Pini Milano Dal 2000 professore a contratto di ortopedia – cultore della materia presso i corsi di laurea in ostetricia e dal 2008, fisioterapia Università degli studi di Milano<br>Medico presso la Fondazione Don Gnocchi Onlus IRCCS</p><h2>Aree mediche di interesse</h2><p>Patologia ortopedica dell\'età evolutiva<br>Disordini muscolo-scheletrici di natura posturale con particolare riferimento alle patologie vertebrali<br>Trattamento conservativo della scoliosi e dell\'ipercifosi, e nella terapia manuale osteopatica</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Walter Grassi',
    'alias' => 'walter-grassi',
    'content' => '<h2>Unità</h2><p>Reumatologia</p><h2>Ruolo</h2><p>Reumatologo</p><h2>Formazione e Specializzazione</h2><p>Università degli Studi di Ancona<br>Reumatologia (Università degli Studi di Chieti)</p><h2>Esperienze professionali</h2><p>Dal 1997 al 2023 Direttore della Clinica Reumatologica e della Scuola di Specializzazione in Reumatologia dell’Università Politecnica delle Marche<br>Autore di oltre 400 contributi scientifici comprendenti monografie e capitoli di volumi e trattati di Reumatologia e Medicina Interna<br>“Invited Speaker” in oltre 350 Congressi nazionali ed internazionali<br>H Index 90 (Google Scholar) / 68 (Scopus)</p><h2>Aree mediche di interesse</h2><p>Malattie degenerative e infiammatorie dell’apparato locomotore<br>Malattie autoimmuni, fenomeno di Raynaud<br>Fibromialgia, dolore lombare e sindromi dolorose distrettuali<br>Ecografia articolare</p><h2>Incarichi in società scientifiche</h2><p>Socio della Società Italiana di Reumatologia<br>Direttore di corsi nazionali ed internazionali nel campo della diagnostica per immagini delle malattie dell’apparato locomotore<br>Referee delle principali riviste internazionali di Reumatologia</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Paolo Guidoni',
    'alias' => 'paolo-guidoni',
    'content' => '<h2>Unità</h2><p>Neurochirurgia</p><h2>Ruolo</h2><p>Neurochirurgo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia con lode 1993 Università di Ancona<br>Neurologia (1998)</p><h2>Esperienze professionali</h2><p>Specializzato in Neurologia, con particolare interesse nel settore della neurofisiologia, ha acquisito  competenze nell\'esecuzione delle tecniche elettromiografiche ed elettroneurografiche ed un\'ottima conoscenza dei potenziali evocati multimodali e della lettura dell\'esame elettroencefalografico, sia in veglia che in privazione e durante sonno notturno.<br>Ha acquisito, inoltre, un\'ottima capacità di infiltrazione con tossina botulinica, per il trattamento di patologie neurologiche, tramite guida EMG.<br>Ha collaborato con l\'Università di Ancona negli anni 1999/2000 con assegno di ricerca sullo studio elettrofisiologico nella malattia di Parkinson.<br>Dal 2000 collabora nell\'ambulatorio di Neurofisiologia dell\'Istituto Santo Stefano di Porto Potenza per l\'esecuzione di esami EMG/ENG, Potenziali evocati multimodali, lettura di esami EEG ed infiltrazione di tossina botulinica per il trattamento della spasticità.<br>Ha effettuato oltre 20.000 esami elettromiografici.<br>Direttore Medico del CA di Matelica nel 2000, dal 2001 è stato dipendente del Santo Stefano presso la sede di Porto Potenza in qualità di Medico di Reparto e successivamente di Referente di Reparto.<br>Dal 2019 Direttore di Struttura del centro Santo Stefano di Cagli fino ad Ottobre 2022 Dall\'Ottobre 2022 ha assunto la Direzione Medica dell\'Istituto Santo Stefano di Porto Potenza Picena.</p><h2>Aree mediche di interesse</h2><p>Neurologia <br>Neurofisiologia</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Ivano Lorenzini',
    'alias' => 'ivano-lorenzini',
    'content' => '<h2>Unità</h2><p>Gastroenterologia</p><h2>Ruolo</h2><p>Gastroenterologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia conseguita presso l\'Universita\' degli Studi di Bologna nel Novembre 1973<br>Specializzazione in Gastroenterologia ed Endoscopia Digestiva conseguita presso l\'Universita\' degli Studi di Modena nel 1978</p><h2>Esperienze professionali</h2><p>Da Febbraio 1975 ha svolto la sua attività come medico ospedaliero presso la Divisione di Gastroenterologia ed Endoscopia Digestiva dell\'Ospedale Regionale Umberto I-Torrette di Ancona ricoprendo negli anni i ruoli di assistente, dirigente di I e II livello ( direttore di struttura complessa), occupandosi in particolare di patologia epatobiliare (epatiti croniche, cirrosi, calcolosi della colecisti) di malattie infiammatorie croniche intestinali (Crohn e rettocolite ulcerosa) ed endoscopia digestiva diagnostica ed operativa.<br>Ha affinato le sue esperienze professionali all\'estero (Stati Uniti, Germania, Olanda).</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Angelica Luttichau',
    'alias' => 'angelica-luttichau',
    'content' => '<h2>Unità</h2><p>Ginecologia e ostetricia</p><h2>Ruolo</h2><p>Ginecologa e ostetrica</p><h2>Formazione e Specializzazione</h2><p>Medicina e Chirurgia (Bologna)<br>Specializzazione in Ostetricia e Ginecologia (Università di Bologna)</p><h2>Esperienze professionali</h2><p>Libero professionista</p><h2>Aree mediche di interesse</h2><p>Ostetricia e Ginecologia<br>Diagnosi Prenatale     Ecografie Ostetriche e Ginecologiche      Morfologiche di Ii Livello con Flussimetria Doppler Uteroplacentare<br>Test Combinato</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Gian Mario Micheloni',
    'alias' => 'gian-mario-micheloni',
    'content' => '<h2>Unità</h2><p>Ortopedia protesica e artroscopica di spalla e gomito</p><h2>Ruolo</h2><p>Chirurgo ortopedico</p><h2>Formazione e Specializzazione</h2><p>2010 - Medicina e Chirurgia (Università degli Studi di Verona)<br>Ortopedia e Traumatologia (Università degli studi di Verona, 2016)<br>Master universitario I livello in Traumatologia dello Sport (Università degli Studi di Bologna, 2022)</p><h2>Esperienze professionali</h2><p>Dal 2015 medico ortopedico della Nazionale Italiana di Pattinaggio di Velocità su Ghiaccio (FISG)<br>Medico Ortopedico Sassuolo Calcio (2021-2022)</p><h2>Pubblicazioni</h2><p>1. Porcellini G, Micheloni GM, Tarallo L, Paladini P, Merolla G, Catani F. Custom-made reverse shoulder arthroplasty for severe glenoid bone loss: review of the literature and our preliminary results. J Orthop Traumatol. 2021 Jan 19;22(1): 2. doi: 10.1186/s10195-020-00564-6.<br>2. Tarallo L, Merolla G, Porcellini G, Amorico MG, Micheloni G, Novi M, Di Giovine E, Catani F. Acute elbow dislocation: comparison between magnetic resonance imaging and intraoperative finding of ligament injury. Int Orthop. 2021 Jan;45(1):265-27 3. doi: 10.1007/s00264-020-04875-5. PMID: 33200244<br>3. Vecchini E, Berti M, Micheloni GM, Maluta T, Magnan B, Ricci M. Clinical and radiological results of a stemmed medial pivot revision implant in aseptic total knee revision arthroplasty. Knee. 2020 Aug;27(4):1190-1196. doi: 10.1016/j.knee.2020.05.018. Epub 2020 Jun 27.<br>4. Micheloni GM, Tarallo L, Porcellini G, Novi M, Catani F. Reinsertion of distal biceps ruptures with a single anterior approach: analysis of 14 cases using tension-slide technique and interference screw. Acta Biomed. 2020 May 30;91(4-S):183-188. doi: 10.23750/abm.v91i4-S.9551. PMID: 32555095<br>5. Giaretta S, Micheloni GM, Mazzi M, Lunardelli E, Ambrosini C, Rebeccato A. The "Ball in Basket" Technique for Tibiotalocalcaneal Fusion. Acta Biomed. 2020 May 30;91(4- S):172-178. doi: 10.23750/abm.v91i4-S.9712.PMID: 32555093<br>6. Tarallo L, Novi M, Porcellini G, Giorgini A, Micheloni G, Catani F. Surgical tips and tricks for coronal shear fractures of the elbow. Arch Orthop Trauma Surg. 2020 Jun 3. doi: 10.1007/s00402-020-03500-9. PMID: 3249511.<br>7. G. M. Micheloni, A. Rebeccato, G. Salmaso, M. Mazzi, A. Moscolo, S. Giaretta, A. Momoli. Two cases of neglected posterior fracture-dislocation of the shoulder with ipsilateral humeral shaft fracture. Acta Biomed. 2019 Dec 5;90(12-S):131-138.<br>8. S. Giaretta, M. Berti, G.M. Micheloni, A. Ceccato, F. Marangoni, A. Momoli. Early experience with the ATTUNE Total Knee Replacement System. Acta Biomed. 2019 Dec 5;90(12-S):48-53.<br>9. G. M. Micheloni, L. Tarallo, G. Porcellini, F. Catani. Comparison between conservative treatment and plate fixation for displaced middle third clavicle fracture: clinical outcomes and complications. Acta Biomed. 2019 Dec 5;90(12-S):48-53.<br>10. S. Giaretta, A. Momoli, G. Porcelli, G.M. Micheloni. Diagnosis and management of periprosthetic femoral fractures after hip arthroplasty. Injury. 2019 Feb 2. pii: S0020- 1383(19)30053-1.<br>11. S. Giaretta, A. Silvestri, A. Momoli, G.M. Micheloni. Asymmetric bilateral hip dislocation in young man: a case report. Acta Biomed. 2019 Jan 10;90(1-S):183-186.<br>12. G.M. Micheloni, G. Salmaso, M. Berti, S. Bortolato, G. Zecchinato, A. Momoli, S Giaretta. Cementless metaphyseal reverse shoulder arthroplasty: our preliminary experience. Acta Biomed. 2019 Jan 10;90(1-S):47-53 12<br>13. M. Ricci, E. Vecchini, E. Bonfante, G.M. Micheloni, M. Berti, G. Schenal, G. Zanetti, E. Sambugaro, T. Maluta, B. Magnan. A clinical and radiological study of biodegradable subacromial spacer in the treatment of massive irreparable rotator cuff tears. Acta Biomed 2017;88(4-S):75-80<br>14. C. Dall’Oca, T. Maluta, G.M. Micheloni, M. Cengarle, G. Morbioli, P. Bernardi, A. Sbarbati, D. Degl’innocenti, F. Lavini, B. Magnan. The biocompatibility of bone cements: progress in methodological approach. Eur J Histochem 2017; 61(2):2673.<br>15. A. Momoli, S. Giaretta, M. Modena, G.M. Micheloni. The painful knee after total knee arthroplasty: evaluation and managment. Acta Biomed 2017;88(2-S):60-67.<br>16. M. Ricci, G.M. Micheloni, M. Berti, F. Perusi, E. Sambugaro, E. Vecchini, B. Magnan. Clinical comparison of oral administration and viscosupplementation of hyaluronic acid (HA) in early knee osteoarthritis. Musculoskelet Surg 2017; 101(1):45-49.<br>17. E. Vecchini, G.M. Micheloni, F. Perusi, M. Scaglia, T. Maluta, F. Lavini, M. Bondi, C. Dall\'Oca, B. Magnan. Antibiotic-Loaded Spacer for Two-Stage Revision of Infected Total Knee Arthroplasty. J Knee Surg 2017; 30(3):231-237<br>18. M. Ricci, G.M. Micheloni, F. Perusi F, V.R. Corbo, E. Vecchini, B. Magnan. Use of a nonmedicated plaster in shoulder tendinopathies. Acta Biomed. 2016;87 (Suppl) 1:90-4.<br>19. T. Maluta, G.M. Micheloni, A. Sandri, D. Regis, A. Costanzo, B. Magnan. Rotational osteoplasty and bioabsorbable polylactate pin fixation in Pipkin type 2 fracture with acute osteochondral defect: a case report. Acta Biomed. 2016;87 (Suppl 1):116-21.<br>20. E. Vecchini, G.M. Micheloni, V.R. Corbo, F. Perusi, G. Dib, B. Magnan. A complication following ACL reconstruction using bioabsorbable cross-pins. Acta Biomed. 2016;87 (Suppl 1):122-6.<br>21. C. Dall’Oca, T. Maluta, F. Lavini, G.M. Micheloni, M. Bondi, B. Magnan. Augmentation of unstable pertrochanteric fractures in the osteoporotic elderly patient: operative technique for 1 or 2 head screw systems. Acta Biomed Acta Biomed 2014;85(2):59-65.<br>22. C. Dall’Oca, T. Maluta, G.M. Micheloni, T. Romeo, A. Zambito, R. Malagò, B. Magnan. Femoroacetabular impingement: biomechanical and dynamic considerations. Acta Biomed 2014;85(2):46-51.<br>23. C. Dall’Oca, T. Maluta, N. Leone, G.M. Micheloni, , B. Magnan. The treatment of proximal humeral fractures with a "Polarus" intramedullary nail. Acta Biomed 2014;85(2):25-30.<br>24. Bizzotto N, Sandri A, Trivellin G, Magnan B, Micheloni GM, Zamò A, Bernardi P, Sbarbati A, Regis D. Chromium-induced diffuse dermatitis with lymph node involvement resulting from Langerhans cell histiocytosis after metal-on-metal hip resurfacing. Br J Dermatol. 2015;172(6):1633-6.<br>25. C. Dall’Oca, T. Maluta, F. Lavini, M. Bondi, G.M. Micheloni, P. Bartolozzi. Tibial plateau fractures: compared outcomes between ARIF and ORIF. Strat Traum Limb Recon 2012;7:163-175.<br>26. B. Magnan, E. Samaila, M. Bondi, E. Vecchini, G.M. Micheloni, P. Bartolozzi. Threedimensional matrix-induced autologous chondrocytes implantation for osteochondral lesions of the talus: midterm results. Adv Orthop 2012.</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Giovanni Oteri',
    'alias' => 'giovanni-oteri',
    'content' => '<h2>Unità</h2><p>Dermatologia e venereologia</p><h2>Ruolo</h2><p>Dermatologo e Venereologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia Università "La Sapienza" Roma<br>Dermatologia<br>Venereologia</p><h2>Aree mediche di interesse</h2><p>Verruche<br>Alopecia<br>Dermatite atopica</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Nicola Pace',
    'alias' => 'nicola-pace',
    'content' => '<h2>Unità</h2><p>Ortopedia e traumatologia</p><h2>Ruolo</h2><p>Ortopedico e traumatologo</p><h2>Formazione e Specializzazione</h2><p>Laureato in medicina e chirurgia nel 1977 presso Università degli Studi di Ancona<br>Ortopedia e Traumatologia<br>Fisioterapia</p><h2>Esperienze professionali</h2><p>Dal febbraio 2015 è responsabile del reparto ortopedico della casa di cura Villa Igea-Labor di Ancona accreditata con il SSN.<br>Dal 2013 al gennaio 2015 è stato direttore del dipartimento delle specialità chirurgiche per l’Area Vasta 2.<br>Dal 1995 al gennaio 2015 è stato direttore dell’unità operativa di ortopedia e traumatologia prima della ASL5 di Jesi.<br>Dal gennaio 2005 al dicembre 2009 è stato Direttore del dipartimento chirurgico dell’ospedale di Jesi.<br>Dal settembre 2001 al 2005 è stato direttore responsabile del centro di chirurgia protesica INRCA Ancona.</p><h2>Aree mediche di interesse</h2><p>Chirurgo Ortopedico esperto In Ortopedia Pediatrica (è stato Professore in Ortopedia Pediatrica presso l\'Università degli Studi di Ancona)<br>Esperto in Chirurgia dell’arto Inferiore e in particolare nella Patologia dell’Anca, Ginocchio e Piede</p><h2>Pubblicazioni</h2><p>Myolab</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Matteo Pavan',
    'alias' => 'matteo-pavan',
    'content' => '<h2>Unità</h2><p>Ortopedia e traumatologia</p><h2>Ruolo</h2><p>Ortopedico e Traumatologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia il 4/04/2002 presso l\'Università degli Studi di Ancona.<br>Specializzazione in Ortopedia e Traumatologia presso Università degli Studi di Ancona (2007) discutendo la tesi: "Aspetti degenerativi della giunzione miotendinea del tendine d\'Achille nell\'uomo. Studio ultrastrutturale"<br>Ortopedia e traumatologia del sistema motorio<br>Chirurgia del ginocchio<br>Chirurgia dell\'anca</p><h2>Esperienze professionali</h2><p>Ex dirigente 1° livello presso Ospedali Marche Nord - U.O. Ortopedia e Traumatologia Fano / Pesaro.</p><h2>Aree mediche di interesse</h2><p>Artrosi post-traumatica<br>Cisti al ginocchio<br>Distorsione del ginocchio<br>Lesione legamento crociato anteriore<br>Lesione menisco<br>Meniscopatia<br>Patologie del ginocchio<br>Patologie dell\'anca<br>Patologie delle cartilagini<br>Plica sinoviale<br>Rottura legamenti</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Osvaldo Pirani',
    'alias' => 'osvaldo-pirani',
    'content' => '<h2>Unità</h2><p>Radiodiagnostica</p><h2>Ruolo</h2><p>Radiologo</p><h2>Formazione e Specializzazione</h2><p>Laurea Medicina e chirurgia<br>Radiologia</p><h2>Esperienze professionali</h2><p>La sua attività lavorativa si è svolta, principalmente, nell’ambito della Radiologia dell’Ospedale di Jesi (specie nella Radiologia Convenzionale -  Ecografia e Senologia, di cui è stato responsabile).<br>Dal gennaio 2001 è stato nominato Direttore del Servizio di Radiologia della ASL 5-Jesi.<br>Attualmente ricopre l’incarico di Direttore Unità Operativa Complessa di Radiologia della A.V. n. 2- ASUR Marche e di Direttore del Dipartimento dei Servizi della A.V. n.2 - ASUR Marche.</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Antonio Rencricca',
    'alias' => 'antonio-rencricca',
    'content' => '<h2>Unità</h2><p>Otorinolaringoiatria</p><h2>Ruolo</h2><p>Otorinolaringoiatra</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso Università degli Studi di Perugia.<br>Specializzazione in Otorinolaringoiatria presso Università degli Studi di Perugia</p><h2>Esperienze professionali</h2><p>Dirigente Medico U.O.C. Otorinolaringoiatria - ASUR2 Marche. Presidio Ospedaliero Senigallia (AN); tempo pieno indeterminato.<br>Dirigente Medico U.O. Otorinolaringoiatria presso Azienda Ospedaliero Universitaria “Ospedali Riuniti di Ancona”; tempo pieno determinato.<br>Dirigente Medico U.O. Otorinolaringoiatria presso Area metropolitana Ospedale di Budrio e Bellaria di Bologna; tempo pieno determinato.<br>Dirigente Medico U.O. Otorinolaringoiatria presso Ospedale Azienda Ulss N. 10 di San Dona\' di Piave (VE); tempo pieno determinato.<br>Dirigente Medico U.O. Otorinolaringoiatria presso Ospedale “Morgagni Pierantoni” Forlì (FC); incarico Libero Professionale.</p><h2>Aree mediche di interesse</h2><p>Trattamento delle complicanze della patologie infiammatorie e funzionali dell\'orecchio medio, sia in età pediatrica che nell\'adulto<br>Trattamento con tecniche endoscopiche mininvasive sia della patologia flogistica acuta e cronica, sia neoplastica maligna e benigna dei seni paranasali<br>Trattamento delle patologie flogistiche ed organiche foniatriche (paralisi cordali, insufficienze glottiche) sia con tecniche sia riempitive (vox implant) sia ablative (cordotomie posteriori con Laser C02)<br>Trattamento della patologia di confine ORL-oculistica: dacriocistorinostomie, decompressione orbitaria, trattamento degli ascessi e delle complicanze traumatiche dell\'orbita<br>Trattamento della patologia di confine ORL-endocrinologia (patologie della tiroide e delle paratiroidi)<br>Trattamento della patologia di confine ORL-neurochirurgica: trattamento delle fistole rino-liquorali per via endoscopica, accesso all\'ipofisi per via endoscopica<br>Trattamento della patologia di confine ORL-odontoiatrica (sinusiti odontogene, ascessi odontogeni del collo)<br>Trattamento della patologia di confine ORL-chirurgia estetica orecchio e naso (rinosettoplastica e otoplastica)<br>Trattamento dei disturbi respiratori del sonno, russamento ed apnee ( turbinoplastica, settoplastica, faringoplastica laterale, sleep endoscopy, terapia medica , chirurgia palatale delle “tende a pacchetto” Roman blinds)<br>Trattamento della patologia neoplastica e non dei distretti faringei, laringei, delle ghiandole salivari e del collo prevalentemente mediante l\'utilizzo dei laser (laringectomie ricostruttive, parotidectomie, scialectomie, svuotamenti latero-cervicali)<br>Trattamento delle patologie della sfera cocleo-vestibolare (M di Meniere, idropi, ipoacusieimprovvise , malattie vertiginose e disturbi dell’equilibrio)</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Carlo Rondanini',
    'alias' => 'carlo-rondanini',
    'content' => '<h2>Unità</h2><p>Angiologia</p><h2>Ruolo</h2><p>Angiologo</p><h2>Formazione e Specializzazione</h2><p>Medicina e Chirurgia<br>Gastroenterologia<br>Immunoematologia<br>Medicina Interna</p><h2>Esperienze professionali</h2><p>Dal 2013 ad oggi. Direttore F.F. della U.O. di Medicina interna Ospedale di Jesi.</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Sara Scuppa',
    'alias' => 'sara-scuppa',
    'content' => '<h2>Unità</h2><p>Ginecologia e ostetricia</p><h2>Ruolo</h2><p>Ginecologa e ostetrica</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia (Università di Bologna, 1978)<br>Ostetricia e Ginecologia (Università di Bologna, 1982)</p><h2>Esperienze professionali</h2><p>2011 ad oggi. Libero professionista.<br>1983 - 2011. Ginecologa dipendente nei servizi territoriali e ospedalieri di Jesi.</p><h2>Aree mediche di interesse</h2><p>Prevenzione, diagnosi e trattamento delle varie malattie ginecologiche<br>Ginecologia dell\'adolescenza<br>Contraccezione<br>Menopausa<br>Consulenza pre-concezionale e in gravidanza iniziale<br>Ecografie ginecologiche</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Giuseppe Sortino',
    'alias' => 'giuseppe-sortino',
    'content' => '<h2>Unità</h2><p>Urologia e andrologia</p><h2>Ruolo</h2><p>Urologo e Andrologo</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso Università degli Studi di Catania<br>Urologia</p><h2>Esperienze professionali</h2><p>Ospedale Carlo Urbani di Jesi<br>Humanitas Gavazzeni di Bergamo<br>Istituto Oncologico del Mediterraneo di Viagrande Catania</p><h2>Aree mediche di interesse</h2><p>Urologia<br>Andrologia<br>Urologia oncologica<br>Chirurgia</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
],
[
    'pagetitle' => 'Massimo Vallasciani',
    'alias' => 'massimo-vallasciani',
    'content' => '<h2>Unità</h2><p>Fisiatria</p><h2>Ruolo</h2><p>Fisiatra</p><h2>Formazione e Specializzazione</h2><p>Laurea in Medicina e Chirurgia presso l\'Università di Ancona il 04/11/1986<br>Medicina Fisica e Riabilitazione presso l\'Università di Ancona il 16/07/1989</p><h2>Esperienze professionali</h2><p>Responsabile del Laboratorio per l\'Analisi del Movimento dell\'Istituto Santo Stefano di Porto Potenza Picena dal 2006.<br>Direttore di Unità Ospedaliere di Riabilitazione Intensiva dal 1995.<br>Medico Specialista Ambulatoriale dal 1989.</p><h2>Aree mediche di interesse</h2><p>Patologie del Rachide<br>Riabilitazione Sportiva<br>Riabilitazione Neurologica<br>Riabilitazione Ortopedico-Traumatologica<br>Analisi Strumentale del Movimento</p><h2>Struttura</h2><p><a href="[[~202]]" title="Myolab">Myolab</a></p>'
]
];
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($doctors as $doctor) {
        $result = importDoctor($modx, $doctor, $struttura_id);
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