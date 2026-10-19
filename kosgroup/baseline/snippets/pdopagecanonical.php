<?php
$modx->regClientStartupHTMLBlock('<script>
document.addEventListener("DOMContentLoaded", function() {
    // Ottieni l\'URL corrente
    var currentUrl = window.location.href;
    
    // Rimuovi eventuali canonici esistenti
    var existingCanonical = document.querySelector("link[rel=\'canonical\']");
    if (existingCanonical) {
        existingCanonical.remove();
    }
    
    // Crea e aggiungi il nuovo canonical autoreferenziale
    var newCanonical = document.createElement("link");
    newCanonical.rel = "canonical";
    newCanonical.href = currentUrl;
    document.head.appendChild(newCanonical);
    
    // Gestisci il title per le pagine di paginazione
    var pageParam = new URLSearchParams(window.location.search).get("page");
    if (pageParam && pageParam > 1) {
        var currentTitle = document.title;
        document.title = "News - Pagina " + pageParam + " | Gruppo KOS";
    }
});
</script>');

return true;