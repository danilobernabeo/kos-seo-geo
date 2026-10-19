<?php
// Imposta l'URL e i parametri per la richiesta cURL
$url = 'https://lavoraconnoi.kosgroup.com/custom_kos/ws/job';
$token = '[TOKEN RIMOSSO DALLA BASELINE]';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
    'Cookie: lang=it_IT'
]);

$response = curl_exec($ch);
curl_close($ch);

// Rimuove il campo `description` dal JSON
$response = preg_replace('/"description":\s*".*?",\s*/', '', $response);

// Decodifica il JSON
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    return "Errore nella decodifica JSON: " . json_last_error_msg();
}

// Controlla se ci sono dati validi
if (!empty($data)) {
    // Svuota la tabella
    $modx->exec("TRUNCATE TABLE kos_jobs");

    // Prepara la query di inserimento
    $stmt = $modx->prepare("INSERT INTO kos_jobs (id, url, url_apply, employer, employer_id, title, skill, city, country, region, role, sector, date, latitudine, longitudine, degree, det_degree, emp_type, net_salary, work_disposal, career, percent_job, custom_sector, custom_profile, custom_percent, custom_disposal, custom_protected_cat, custom_contract) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data as $job) {
        // Inserisce i valori dai dati JSON senza `description`
        $stmt->execute([
            $job['id'], $job['url'], $job['url_apply'], $job['employer'], 
            $job['employer_id'], $job['title'], $job['skill'], $job['city'],
            $job['country'], $job['region'], $job['role'], $job['sector'],
            $job['date'], $job['latitudine'], $job['longitudine'], $job['degree'],
            $job['det_degree'], $job['emp_type'], $job['net_salary'], 
            $job['work_disposal'], $job['career'], $job['percent_job'], 
            $job['custom_sector'], $job['custom_profile'], $job['custom_percent'], 
            $job['custom_disposal'], $job['custom_protected_cat'], $job['custom_contract']
        ]);
    }

    return "Importazione completata con successo.";
} else {
    return "Nessun dato trovato.";
}