<?php
// Ottieni il parametro typologyTitle
$typology = $modx->getOption('typology', $scriptProperties);

if (empty($typology)) {
    return 'Parametro typology mancante';
}

try {
    // Query per selezionare le prestazioni della tipologia specificata
    $query = $modx->prepare("
        SELECT DISTINCT activityTitle 
        FROM prestazioni_tuotempo 
        WHERE typologyTitle = ? 
        ORDER BY activityTitle ASC
    ");
    
    $query->execute([$typology]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        return 'Nessuna prestazione trovata per questa tipologia';
    }
    
    // Lista di parole da non capitalizzare
    $exceptions = ['di', 'a', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra', 'e', 'ed', 'o', 'dei', 'il', 'lo', 'la', 'i', 'gli', 'le', 'del', 'dello', 'della', 'dell', 'al', 'allo', 'alla', 'ai', 'agli', 'alle'];
    
    $output = '<div id="box-dettagli" data-aos="fade-up">
        <div class="row box-dettaglio" id="servizi-prestazioni">
            <div class="col box-dettaglio-txt">
                <h3>Servizi e prestazioni</h3>
            </div>
            <div class="box-dettaglio-content">
                <ul class="lista-con-icone lista-icone-check">';
    
    foreach ($results as $result) {
        // Converti il titolo in minuscolo e dividi in parole
        $words = explode(' ', mb_strtolower($result['activityTitle']));
        
        // Capitalizza ogni parola se non è nell'elenco delle eccezioni
        $formattedWords = array_map(function($word) use ($exceptions) {
            return in_array($word, $exceptions) ? $word : ucfirst($word);
        }, $words);
        
        // Capitalizza sempre la prima parola
        $formattedWords[0] = ucfirst($formattedWords[0]);
        
        // Unisci le parole e crea l'elemento della lista
        $formattedTitle = implode(' ', $formattedWords);
        $output .= "<li>{$formattedTitle}</li>";
    }
    
    $output .= '</ul>
            </div>
        </div>
    </div>';
    
    return $output;
    
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Errore nella query delle prestazioni: ' . $e->getMessage());
    return 'Si è verificato un errore durante il recupero delle prestazioni';
}