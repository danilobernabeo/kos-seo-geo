<?php
if (!$modx->user->isAuthenticated('mgr')) {
    return 'Accesso negato.';
}

set_time_limit(120);
ini_set('memory_limit', '256M');

$pdf_dir = MODX_BASE_PATH . 'assets/doc';

// Prendi il primo PDF trovato come test
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pdf_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$test_file = null;
foreach ($iterator as $file) {
    if (strtolower($file->getExtension()) === 'pdf') {
        $test_file = $file->getPathname();
        break;
    }
}

$out = '<div style="font-family:monospace; padding:1rem;">';
$out .= '<h2>Debug estrazione testo PDF</h2>';
$out .= '<p>File di test: <code>' . htmlspecialchars($test_file) . '</code></p>';

// Test 1 - file_get_contents grezzo
$testo_grezzo = @file_get_contents($test_file);
$testo_pulito = preg_replace('/[^\x20-\x7E]/', ' ', $testo_grezzo);
$out .= '<h3>Test 1 - Testo estratto (primi 1000 caratteri)</h3>';
$out .= '<pre style="background:#f5f5f5; padding:1rem; overflow:auto;">' 
     . htmlspecialchars(substr($testo_pulito, 0, 1000)) 
     . '</pre>';

// Test 2 - cerca @kosgroup nel grezzo
$out .= '<h3>Test 2 - Occorrenze di "@kosgroup" nel testo estratto</h3>';
preg_match_all('/\S*@kosgroup\S*/i', $testo_pulito, $matches);
if (!empty($matches[0])) {
    $out .= '<ul>';
    foreach ($matches[0] as $m) {
        $out .= '<li style="color:green">' . htmlspecialchars($m) . '</li>';
    }
    $out .= '</ul>';
} else {
    $out .= '<p style="color:red">Nessuna occorrenza trovata — il testo è probabilmente compresso.</p>';
}

// Test 3 - verifica se exec è disponibile
$out .= '<h3>Test 3 - exec() disponibile?</h3>';
if (function_exists('exec')) {
    $test_exec = [];
    exec('echo test', $test_exec);
    $out .= '<p style="color:green">Sì — output: ' . implode('', $test_exec) . '</p>';
} else {
    $out .= '<p style="color:red">No — exec() è disabilitato.</p>';
}

// Test 4 - verifica se pdftotext è disponibile via exec
$out .= '<h3>Test 4 - pdftotext disponibile?</h3>';
if (function_exists('exec')) {
    $which = [];
    exec('which pdftotext', $which);
    if (!empty($which)) {
        $out .= '<p style="color:green">Trovato in: ' . implode('', $which) . '</p>';
    } else {
        $out .= '<p style="color:red">Non trovato.</p>';
    }
}

$out .= '</div>';
return $out;