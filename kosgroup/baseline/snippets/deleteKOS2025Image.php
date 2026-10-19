<?php
/**
 * deleteKOS2025Image Snippet per MODX 3.1
 * 
 * Gestisce l'eliminazione delle immagini (JPG, WebP, AVIF)
 * Questo snippet deve essere chiamato all'inizio della pagina
 * 
 * Uso: [[!deleteKOS2025Image]]
 */

// Verifica che sia una richiesta POST con action=deleteImage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deleteImage') {
    
    // Verifica che l'utente sia loggato e abbia permessi
    if (!$modx->user->isAuthenticated('mgr')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Non autorizzato. Devi essere un amministratore.'
        ]);
        exit;
    }
    
    $baseFilename = $_POST['baseFilename'] ?? '';
    $imagePath = $_POST['imagePath'] ?? 'assets/img/kos2025/';
    
    if (empty($baseFilename)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Nome file non specificato.'
        ]);
        exit;
    }
    
    // Sicurezza: impedisci path traversal
    if (strpos($baseFilename, '..') !== false || strpos($baseFilename, '/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Nome file non valido.'
        ]);
        exit;
    }
    
    // Path completo
    $imagePath = rtrim($imagePath, '/') . '/';
    $imageFolder = MODX_BASE_PATH . $imagePath;
    
    // Estensioni da eliminare
    $extensions = ['jpg', 'jpeg', 'webp', 'avif', 'JPG', 'JPEG'];
    $deletedFiles = [];
    $errors = [];
    
    foreach ($extensions as $ext) {
        $filePath = $imageFolder . $baseFilename . '.' . $ext;
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $deletedFiles[] = $baseFilename . '.' . $ext;
            } else {
                $errors[] = 'Impossibile eliminare ' . $baseFilename . '.' . $ext;
            }
        }
    }
    
    // Risposta JSON
    header('Content-Type: application/json');
    
    if (count($deletedFiles) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'File eliminati con successo',
            'deletedFiles' => $deletedFiles,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nessun file trovato da eliminare',
            'errors' => $errors
        ]);
    }
    
    exit;
}

return ''; // Non restituisce nulla in condizioni normali