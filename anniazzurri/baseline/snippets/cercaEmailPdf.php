<?php
if (!$modx->user->isAuthenticated('mgr')) {
    return 'Accesso negato.';
}

set_time_limit(120);
ini_set('memory_limit', '256M');

$pdf_dir = MODX_BASE_PATH . 'assets/doc';
$email1  = 'koscare@kosgroup.com';
$email2  = 'info@kosgroup.com';

$risultati1 = [];
$risultati2 = [];
$contatore  = 0;
$errori     = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $pdf_dir,
        RecursiveDirectoryIterator::SKIP_DOTS
    )
);

foreach ($iterator as $file) {
    if (strtolower($file->getExtension()) !== 'pdf') continue;
    $contatore++;

    $percorso = $file->getPathname();
    $testo = @file_get_contents($percorso);

    if ($testo === false) {
        $errori[] = $percorso;
        continue;
    }

    $testo = preg_replace('/[^\x20-\x7E]/', ' ', $testo);

    if (stripos($testo, $email1) !== false) {
        $risultati1[] = str_replace(MODX_BASE_PATH, '/', $percorso);
    }

    if (stripos($testo, $email2) !== false) {
        $risultati2[] = str_replace(MODX_BASE_PATH, '/', $percorso);
    }
}

$out  = '<style>
    .cerca-pdf { font-family: monospace; padding: 1rem; }
    .cerca-pdf h3 { color: #1a5c6b; margin-top: 1.5rem; }
    .cerca-pdf li { margin-bottom: .4rem; }
    .trovato { color: green; }
    .niente { color: gray; }
    .errore { color: red; }
</style>';

$out .= '<div class="cerca-pdf">';
$out .= '<h2>Ricerca email nei PDF</h2>';
$out .= '<p>Directory: <code>' . htmlspecialchars($pdf_dir) . '</code></p>';
$out .= '<p>PDF analizzati: <strong>' . $contatore . '</strong></p>';

if (!empty($errori)) {
    $out .= '<p class="errore">File non leggibili: ' . count($errori) . '</p>';
}

$out .= '<h3>' . $email1 . '</h3>';
if (empty($risultati1)) {
    $out .= '<p class="niente">Nessun risultato trovato.</p>';
} else {
    $out .= '<p>Trovato in <strong>' . count($risultati1) . '</strong> file:</p>';
    $out .= '<ul>';
    foreach ($risultati1 as $r) {
        $out .= '<li class="trovato">' . htmlspecialchars($r) . '</li>';
    }
    $out .= '</ul>';
}

$out .= '<h3>' . $email2 . '</h3>';
if (empty($risultati2)) {
    $out .= '<p class="niente">Nessun risultato trovato.</p>';
} else {
    $out .= '<p>Trovato in <strong>' . count($risultati2) . '</strong> file:</p>';
    $out .= '<ul>';
    foreach ($risultati2 as $r) {
        $out .= '<li class="trovato">' . htmlspecialchars($r) . '</li>';
    }
    $out .= '</ul>';
}

$out .= '</div>';

return $out;