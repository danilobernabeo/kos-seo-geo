<?php
// Crea uno snippet chiamato 'getRSAStructures'
$filePath = MODX_BASE_PATH . 'assets/js/rsa.js';
$output = '';
if (file_exists($filePath)) {
    // Leggi il contenuto del file
    $jsonContent = file_get_contents($filePath);
    
    // Decodifica il JSON
    $structures = json_decode($jsonContent, true);
    
    // Aggiungi campo di ricerca con ID unico
    $output .= '
         <div class="col-12 d-flex align-items-center mb-4">
         	<label for="rsaStruttureInput" class="me-2 mb-0">Cerca:</label>
	        <input class="form-control" 
	               type="text" 
	               id="rsaStruttureInput" 
	               name="rsaStruttureInput" 
	               placeholder="Cerca una struttura per nome o indirizzo" 
	               title="Cerca una struttura">
    	</div>';
    // Aggiungi il div per il messaggio "nessun risultato" con ID unico
    $output .= '<div id="rsaNoResults" class="col-12 text-center" style="display: none;">
        <p>Nessuna corrispondenza trovata</p>
    </div>';
    if ($structures && is_array($structures)) {
        foreach ($structures as $structure) {
            $output .= '
            <div class="col-12 col-md-4 col-lg-3 struttura-card">
                <a href="' . $structure['link_sito'] . '" title="' . htmlspecialchars($structure['nome_struttura']) . '" target="_blank" rel="noopener" class="struttura-container">
                    <div class="struttura-img">
                        <img src="' . $structure['img'] . '" alt="' . htmlspecialchars($structure['nome_struttura']) . '">
                    </div>
                    <div class="struttura-content mb-3 mb-md-4">
                        <h4>' . htmlspecialchars($structure['nome_struttura']) . '</h4>
                        <p>' . htmlspecialchars($structure['indirizzo']) . '</p>
                    </div>
                </a>
            </div>';
        }
    }
    
    // Aggiungi lo script di ricerca aggiornato con protezioni
    $output .= '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Collega l\'evento keyup all\'input di ricerca RSA
        var rsaInput = document.getElementById("rsaStruttureInput");
        if (rsaInput) {
            rsaInput.addEventListener("keyup", cercaRSAStrutture);
        }
        
        // Definizione della funzione cercaRSAStrutture con controlli di sicurezza
        function cercaRSAStrutture() {
            var input = document.getElementById("rsaStruttureInput");
            var noResults = document.getElementById("rsaNoResults");
            
            // Verifica che gli elementi esistano prima di procedere
            if (!input || !noResults) {
                console.log("Elementi di ricerca RSA non trovati");
                return; // Esci dalla funzione se gli elementi non esistono
            }
            
            var filter = input.value.toUpperCase();
            var cards = document.getElementsByClassName("struttura-card");
            var visibleCards = 0;
            
            // Verifica che cards sia una collezione valida
            if (cards && cards.length > 0) {
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    if (card) {
                        // Utilizzo di querySelector per maggiore robustezza
                        var title = card.querySelector("h4");
                        var address = card.querySelector("p");
                        
                        // Verifica che gli elementi esistano prima di accedere al testo
                        var txtValue = title ? (title.textContent || title.innerText) : "";
                        var addressValue = address ? (address.textContent || address.innerText) : "";
                        
                        if (txtValue.toUpperCase().indexOf(filter) > -1 || addressValue.toUpperCase().indexOf(filter) > -1) {
                            card.style.display = "";
                            visibleCards++;
                        } else {
                            card.style.display = "none";
                        }
                    }
                }
            }
            
            // Mostra/nascondi il messaggio "nessun risultato" solo se l\'elemento esiste
            if (noResults) {
                noResults.style.display = visibleCards === 0 ? "block" : "none";
            }
        }
        
        // Esegui una ricerca iniziale (se c\'è già un valore nell\'input)
        if (rsaInput && rsaInput.value.trim() !== "") {
            cercaRSAStrutture();
        }
    });
    </script>';
}
return $output;