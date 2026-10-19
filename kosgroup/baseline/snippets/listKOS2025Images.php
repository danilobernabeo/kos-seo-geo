<?php
/**
 * listKOS2025Images Snippet per MODX 3.1
 * 
 * Mostra solo le immagini JPG della gallery con miniature, nomi file e pulsante elimina
 * Layout verticale (immagini una sotto l'altra)
 * 
 * Uso: [[!listKOS2025Images]]
 * 
 * Parametri opzionali:
 * &imagePath - Percorso cartella immagini (default: assets/img/kos2025/)
 * &showFileSize - Mostra dimensione file (default: true)
 * &thumbWidth - Larghezza miniatura in px (default: 150)
 */

// Parametri
$imagePath = $modx->getOption('imagePath', $scriptProperties, 'assets/img/kos2025/');
$showFileSize = $modx->getOption('showFileSize', $scriptProperties, true);
$thumbWidth = (int)$modx->getOption('thumbWidth', $scriptProperties, 150);

// Assicurati che il percorso termini con /
$imagePath = rtrim($imagePath, '/') . '/';

// Path completi
$imageFolder = MODX_BASE_PATH . $imagePath;
$imageUrl = MODX_BASE_URL . $imagePath;

// Verifica che la cartella esista
if (!is_dir($imageFolder)) {
    return '<div class="alert alert-warning">Cartella immagini non trovata: ' . htmlspecialchars($imagePath) . '</div>';
}

// Ottieni SOLO le immagini JPG dalla cartella
$images = glob($imageFolder . '*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);

if (empty($images)) {
    return '<div class="alert alert-info">Nessuna immagine JPG trovata nella cartella.</div>';
}

// Ordina alfabeticamente per nome file
sort($images);

// Funzione per formattare la dimensione file
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

// CSS inline per lo styling
$output = '<style>
.image-list-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.image-list-header {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.image-list-vertical {
    list-style: none;
    padding: 0;
    margin: 0;
}
.image-list-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}
.image-list-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}
.image-list-item.deleting {
    opacity: 0.5;
    pointer-events: none;
}
.image-list-thumb {
    width: ' . $thumbWidth . 'px;
    height: ' . $thumbWidth . 'px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
    margin-right: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.image-list-info {
    flex: 1;
    min-width: 0;
}
.image-list-filename {
    font-weight: 600;
    color: #212529;
    margin: 0 0 8px 0;
    word-break: break-all;
    font-size: 15px;
}
.image-list-meta {
    color: #6c757d;
    font-size: 13px;
    margin: 0;
}
.image-list-number {
    display: inline-block;
    background: #0d6efd;
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 5px;
}
.image-list-format-badge {
    display: inline-block;
    background: #198754;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}
.btn-delete-image {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    margin-left: 15px;
}
.btn-delete-image:hover {
    background: #bb2d3b;
    transform: scale(1.05);
}
.btn-delete-image:active {
    transform: scale(0.95);
}
.alert {
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
}
.alert-success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}
.alert-danger {
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}
</style>';

// JavaScript per gestire l'eliminazione
$output .= '<script>
function deleteImage(filename, baseFilename) {
    if (!confirm("Sei sicuro di voler eliminare " + filename + " e tutte le sue versioni (JPG, WebP, AVIF)?")) {
        return;
    }
    
    const listItem = document.querySelector(`[data-filename="${filename}"]`);
    listItem.classList.add("deleting");
    
    // Crea form data
    const formData = new FormData();
    formData.append("action", "deleteImage");
    formData.append("baseFilename", baseFilename);
    formData.append("imagePath", "' . addslashes($imagePath) . '");
    
    // Invia richiesta AJAX
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Rimuovi elemento con animazione
            listItem.style.transition = "all 0.3s ease";
            listItem.style.opacity = "0";
            listItem.style.transform = "translateX(-20px)";
            
            setTimeout(() => {
                listItem.remove();
                
                // Mostra messaggio successo
                showMessage("Immagine eliminata con successo: " + data.deletedFiles.join(", "), "success");
                
                // Aggiorna contatore
                updateCounter();
            }, 300);
        } else {
            listItem.classList.remove("deleting");
            showMessage("Errore: " + data.message, "danger");
        }
    })
    .catch(error => {
        listItem.classList.remove("deleting");
        showMessage("Errore di connessione: " + error, "danger");
    });
}

function showMessage(message, type) {
    const container = document.querySelector(".image-list-container");
    const alert = document.createElement("div");
    alert.className = "alert alert-" + type;
    alert.textContent = message;
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => {
        alert.style.transition = "opacity 0.3s ease";
        alert.style.opacity = "0";
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

function updateCounter() {
    const count = document.querySelectorAll(".image-list-item").length;
    const counterEl = document.querySelector(".total-count");
    if (counterEl) {
        counterEl.textContent = count;
    }
}
</script>';

// Header con conteggio
$totalCount = count($images);
$output .= '<div class="image-list-container">';
$output .= '<div class="image-list-header">';
$output .= '<h3 class="h4 mb-2">Elenco Immagini Gallery (solo JPG)</h3>';
$output .= '<p class="mb-0"><strong>Totale immagini JPG:</strong> <span class="total-count">' . $totalCount . '</span> | ';
$output .= '<strong>Percorso:</strong> ' . htmlspecialchars($imagePath) . '</p>';
$output .= '<p class="mb-0 mt-2 text-muted"><small>???? Ogni JPG ha anche versioni WebP e AVIF (totale file: ' . ($totalCount * 3) . ')</small></p>';
$output .= '</div>';

// Lista verticale
$output .= '<ul class="image-list-vertical">';

foreach ($images as $index => $imagePath) {
    $filename = basename($imagePath);
    $imageUrlFull = $imageUrl . $filename;
    $fileSize = filesize($imagePath);
    
    // Verifica esistenza versioni WebP e AVIF
    $pathInfo = pathinfo($imagePath);
    $baseFilename = $pathInfo['filename'];
    $hasWebP = file_exists($pathInfo['dirname'] . '/' . $baseFilename . '.webp');
    $hasAvif = file_exists($pathInfo['dirname'] . '/' . $baseFilename . '.avif');
    
    $output .= '<li class="image-list-item" data-filename="' . htmlspecialchars($filename) . '">';
    
    // Miniatura
    $output .= sprintf(
        '<img src="%s" alt="%s" class="image-list-thumb" loading="lazy">',
        htmlspecialchars($imageUrlFull),
        htmlspecialchars($filename)
    );
    
    // Info
    $output .= '<div class="image-list-info">';
    $output .= '<span class="image-list-number">#' . ($index + 1) . '</span>';
    $output .= '<p class="image-list-filename">' . htmlspecialchars($filename) . '</p>';
    
    if ($showFileSize) {
        $output .= '<p class="image-list-meta">';
        $output .= formatFileSize($fileSize);
        
        // Badge formati disponibili
        $output .= '<span class="image-list-format-badge">JPG</span>';
        if ($hasWebP) {
            $output .= '<span class="image-list-format-badge">WebP</span>';
        }
        if ($hasAvif) {
            $output .= '<span class="image-list-format-badge">AVIF</span>';
        }
        
        $output .= '</p>';
    }
    
    $output .= '</div>';
    
    // Pulsante elimina
    $output .= sprintf(
        '<button class="btn-delete-image" onclick="deleteImage(\'%s\', \'%s\')">Elimina</button>',
        htmlspecialchars($filename),
        htmlspecialchars($baseFilename)
    );
    
    $output .= '</li>';
}

$output .= '</ul>';
$output .= '</div>';

return $output;