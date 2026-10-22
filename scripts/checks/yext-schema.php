<?php
/**
 * KOS-24 / AA (schede residenza) — archivio e analisi del dato strutturato che lo script Yext Knowledge Tags
 * inserisce nelle pagine, da documentare prima di rimuoverlo. SOLA LETTURA: non scrive nel database.
 *
 * Uso (in SSH sul cloud, anche in produzione):
 *   php yext-schema.php /www https://kosgroup.com idYext
 *   php yext-schema.php /www https://anniazzurri.it data-yext-id
 *
 * Per ogni risorsa pubblicata con la TV dell'id Yext valorizzata:
 *   1. scarica la pagina pubblica e ne estrae lo script Knowledge Tags (src) e i campi data-yext-field;
 *   2. scarica lo script e ne estrae l'oggetto passato a Yext._embed(): entities[].schema è il JSON-LD
 *      che la libreria inserisce nel body (script.yext-schema-json);
 *   3. controlla il JSON-LD con le regole della convenzione del progetto.
 * Output in ~/yext-schema-<host>-<data>/: schemi.json (archivio completo), problemi.csv, riepilogo.txt.
 * Una richiesta al secondo verso il sito e una verso Yext.
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$base    = rtrim($argv[2] ?? '', '/');
$tvName  = $argv[3] ?? '';
if ($webroot === '' || !preg_match('#^https://#', $base) || $tvName === '') {
    fwrite(STDERR, "Uso: php yext-schema.php /www https://kosgroup.com idYext\n"); exit(1);
}
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

$host = parse_url($base, PHP_URL_HOST);
$out  = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/yext-schema-' . $host . '-' . date('Ymd-His');
mkdir($out, 0775, true);

/* ---------- regole ---------- */
// Tipi schema.org plausibili per una struttura; tutto il resto viene segnalato.
$TIPI_OK = ['LocalBusiness', 'MedicalBusiness', 'MedicalClinic', 'MedicalOrganization', 'Hospital', 'Physician',
    'IndividualPhysician', 'PhysiciansOffice', 'Dentist', 'DiagnosticLab', 'Pharmacy', 'Optician', 'Organization',
    'HealthAndBeautyBusiness', 'MedicalCenter', 'EmergencyService', 'HealthClub', 'ProfessionalService'];
// Valori dell'enumerazione MedicalSpecialty: sono specialità (medicalSpecialty), non tipi.
$SPECIALITA = ['Anesthesia', 'Cardiovascular', 'CommunityHealth', 'Dentistry', 'Dermatology', 'DietNutrition',
    'Emergency', 'Endocrine', 'Gastroenterologic', 'Genetic', 'Geriatric', 'Gynecologic', 'Hematologic',
    'Infectious', 'LaboratoryScience', 'Midwifery', 'Musculoskeletal', 'Neurologic', 'Nursing', 'Obstetric',
    'Oncologic', 'Optometric', 'Otolaryngologic', 'Pathology', 'Pediatric', 'PharmacySpecialty', 'Physiotherapy',
    'PlasticSurgery', 'Podiatric', 'PrimaryCare', 'Psychiatric', 'PublicHealth', 'Pulmonary', 'Radiography',
    'Renal', 'RespiratoryTherapy', 'Rheumatologic', 'SpeechPathology', 'Surgical', 'Toxicologic', 'Urologic'];
$DOMINI_EMAIL = ['kosgroup.com', 'anniazzurri.it', 'kos-care.it', 'neomesia.com'];

/* ---------- utilità ---------- */
$ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'kos-seo-geo verifica schema Yext',
    'ignore_errors' => true]]);
$get = function (string $url) use ($ctx): array {
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int) $m[1];
    return [$code, $body === false ? '' : $body];
};
// Estrae l'oggetto JSON passato a Yext._embed( ... ), rispettando stringhe e caratteri di escape.
$estraiEmbed = function (string $js): ?array {
    $p = strpos($js, 'Yext._embed(');
    if ($p === false) return null;
    $i = strpos($js, '{', $p);
    if ($i === false) return null;
    $liv = 0; $str = false; $esc = false;
    for ($j = $i, $n = strlen($js); $j < $n; $j++) {
        $c = $js[$j];
        if ($str) { if ($esc) $esc = false; elseif ($c === '\\') $esc = true; elseif ($c === '"') $str = false; continue; }
        if ($c === '"') $str = true;
        elseif ($c === '{') $liv++;
        elseif ($c === '}' && --$liv === 0) {
            $d = json_decode(substr($js, $i, $j - $i + 1), true);
            return is_array($d) ? $d : null;
        }
    }
    return null;
};
$norm = fn(string $u) => rtrim(strtolower(preg_replace('#^https?://(www\.)?#', '', $u)), '/');
$lista = fn($v) => is_array($v) && array_is_list($v) ? $v : ($v === null ? [] : [$v]);

/* ---------- risorse con l'id Yext ---------- */
$tv = $modx->getObject(modTemplateVar::class, ['name' => $tvName]);
if (!$tv) { fwrite(STDERR, "TV $tvName non trovata.\n"); exit(1); }
$q = $modx->newQuery(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id'), 'value:!=' => '']);
$righe = [];
foreach ($modx->getIterator(modTemplateVarResource::class, $q) as $v) {
    $r = $modx->getObject(modResource::class, $v->get('contentid'));
    if (!$r || !$r->get('published') || $r->get('deleted')) continue;
    $righe[] = ['id' => $r->get('id'), 'pagetitle' => (string) $r->get('pagetitle'),
        'url' => $base . '/' . ltrim((string) $r->get('uri'), '/'), 'yext' => trim((string) $v->get('value'))];
}
usort($righe, fn($a, $b) => strcmp($a['url'], $b['url']));
echo "== " . count($righe) . " pagine pubblicate con $tvName valorizzata. Output in $out/\n";

/* ---------- scansione ---------- */
$archivio = []; $problemi = []; $conteggio = []; $idVisti = [];
$segnala = function (array $r, string $codice, string $testo) use (&$problemi, &$conteggio) {
    $problemi[] = [$r['id'], $r['url'], $r['yext'], $codice, $testo];
    $conteggio[$codice] = ($conteggio[$codice] ?? 0) + 1;
};

foreach ($righe as $k => $r) {
    echo sprintf("  [%d/%d] %s (%s)\n", $k + 1, count($righe), $r['url'], $r['yext']);
    [$cod, $html] = $get($r['url']);
    sleep(1);
    $voce = ['risorsa' => $r['id'], 'pagina' => $r['url'], 'pagetitle' => $r['pagetitle'], 'idYext' => $r['yext'],
        'http' => $cod, 'script' => null, 'campi_visibili_yext' => [], 'schema' => null];
    if ($cod !== 200) { $segnala($r, 'pagina-non-200', "la pagina risponde $cod"); $archivio[] = $voce; continue; }

    if (preg_match('#<script[^>]+src="(https://knowledgetags\.[^"]+)"#i', $html, $m)) $voce['script'] = html_entity_decode($m[1]);
    preg_match_all('#data-yext-field="([^"]+)"#i', $html, $m);
    $voce['campi_visibili_yext'] = array_values(array_unique($m[1]));
    if ($voce['campi_visibili_yext']) $segnala($r, 'campi-visibili-da-yext',
        'la pagina mostra campi riempiti da Yext nel browser (' . implode(', ', $voce['campi_visibili_yext']) . '): togliendo lo script restano vuoti');
    if (!$voce['script']) { $segnala($r, 'script-assente', 'TV valorizzata ma nessuno script Knowledge Tags nella pagina'); $archivio[] = $voce; continue; }

    [$cj, $js] = $get($voce['script']);
    sleep(1);
    $emb = $cj === 200 ? $estraiEmbed($js) : null;
    $ent = null;
    foreach ($emb['entities'] ?? [] as $e) if (($e['entityId'] ?? '') === $r['yext']) $ent = $e;
    if (!$ent) { $segnala($r, 'entita-non-trovata', "Yext non restituisce l'entità (HTTP $cj)"); $archivio[] = $voce; continue; }
    $s = $ent['schema'] ?? null;
    $voce['schema'] = $s;
    $archivio[] = $voce;
    if (!is_array($s)) { $segnala($r, 'schema-assente', "l'entità Yext non ha JSON-LD"); continue; }

    // Identità
    $id = (string) ($s['@id'] ?? '');
    if ($id === '') $segnala($r, 'id-assente', 'manca @id');
    elseif (!preg_match('#^https?://#', $id)) $segnala($r, 'id-relativo', "@id «{$id}» non è un URL: i lettori lo risolvono in modi diversi (il Rich Results Test lo mostra come http://example-test.site/{$id}); convenzione: {URL scheda}#localbusiness");
    elseif (!str_starts_with($norm($id), $norm($base))) $segnala($r, 'id-estraneo', "@id fuori dal sito: $id (convenzione: {URL scheda}#localbusiness)");
    if ($id !== '') { if (isset($idVisti[$id])) $segnala($r, 'id-duplicato', "@id già usato da {$idVisti[$id]}"); else $idVisti[$id] = $r['url']; }

    // Tipo
    foreach ($lista($s['@type'] ?? null) as $t) {
        if (in_array($t, $SPECIALITA, true)) $segnala($r, 'specialita-come-tipo', "«$t» è una specialità (medicalSpecialty), non un tipo");
        elseif ($t === 'NursingHome') $segnala($r, 'tipo-inesistente', '«NursingHome» non esiste in schema.org');
        elseif (!in_array($t, $TIPI_OK, true)) $segnala($r, 'tipo-da-verificare', "tipo «$t» non previsto per una struttura");
    }
    if (!$lista($s['@type'] ?? null)) $segnala($r, 'tipo-assente', 'manca @type');

    // Legami
    if (empty($s['parentOrganization']) && empty($s['branchOf'])) $segnala($r, 'nessun-legame-ente', "nessun parentOrganization verso l'ente del sito ({$base}/#organization)");
    foreach ($lista($s['brand'] ?? null) as $b) { $bn = trim((string) ($b['name'] ?? '')); if ($bn === '' || $bn === '0') $segnala($r, 'brand-non-valido', "brand con nome «{$bn}»"); }
    foreach ($lista($s['sameAs'] ?? null) as $u) if (preg_match('#(location\.com|yext|yextpages)#i', (string) $u)) $segnala($r, 'sameas-yext', "sameAs verso una pagina Yext: $u");

    // URL e nome
    $url = (string) ($s['url'] ?? '');
    if ($url === '') $segnala($r, 'url-assente', 'manca url');
    elseif ($norm($url) !== $norm($r['url'])) $segnala($r, 'url-diverso', "url $url diverso dalla pagina che lo contiene");
    if (preg_match('#modx\.dev|stage\.#i', $url)) $segnala($r, 'url-di-prova', "url verso un ambiente di prova: $url");
    $nome = trim((string) ($s['name'] ?? ''));
    if ($nome === '') $segnala($r, 'nome-assente', 'manca name');

    // Contatti e indirizzo
    $tel = (string) ($s['telephone'] ?? '');
    if ($tel === '') $segnala($r, 'telefono-assente', 'manca telephone');
    elseif (!preg_match('#^\+\d{6,15}$#', preg_replace('#[\s.-]#', '', $tel))) $segnala($r, 'telefono-formato', "telefono non in formato internazionale: $tel");
    $mail = strtolower((string) ($s['email'] ?? ''));
    if ($mail !== '') {
        $dom = substr(strrchr($mail, '@') ?: '', 1);
        if (!in_array($dom, $DOMINI_EMAIL, true)) $segnala($r, 'email-dominio', "email con dominio non del gruppo: $mail");
    }
    $a = $s['address'] ?? [];
    foreach (['streetAddress', 'postalCode', 'addressLocality'] as $f) if (empty($a[$f])) $segnala($r, 'indirizzo-incompleto', "manca address.$f");
    if (empty($a['addressCountry'])) $segnala($r, 'paese-assente', 'manca address.addressCountry');
    if (empty($s['geo']['latitude']) || empty($s['geo']['longitude'])) $segnala($r, 'geo-assente', 'mancano le coordinate');

    // Orari
    $ore = $lista($s['openingHoursSpecification'] ?? null);
    if (!$ore) $segnala($r, 'orari-assenti', 'mancano gli orari');
    else {
        $fasce = array_unique(array_map(fn($o) => ($o['opens'] ?? '') . '-' . ($o['closes'] ?? ''), $ore));
        $giorni = array_unique(array_merge(...array_map(fn($o) => $lista($o['dayOfWeek'] ?? null), $ore)));
        $senzaOre = array_filter($ore, fn($o) => !isset($o['opens']) && !isset($o['closes']));
        if ($senzaOre) $segnala($r, 'giorni-senza-orario', count($senzaOre) . ' giorni senza opens/closes (giorno di chiusura non dichiarato in modo valido)');
        if (count($giorni) === 7 && count($fasce) === 1) $segnala($r, 'orari-uguali-7-giorni', 'stesso orario tutti i giorni, domenica compresa (' . reset($fasce) . '): da verificare');
    }

    // Immagini ospitate da Yext: spariscono con la fine del contratto
    foreach (['image', 'logo'] as $f) foreach ($lista($s[$f] ?? null) as $u)
        if (preg_match('#mktgcdn\.com|yext#i', is_array($u) ? (string) ($u['url'] ?? '') : (string) $u)) { $segnala($r, 'immagine-su-cdn-yext', "$f ospitato sul CDN di Yext"); break; }
}

/* ---------- output ---------- */
file_put_contents("$out/schemi.json", json_encode($archivio, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$f = fopen("$out/problemi.csv", 'w');
fputcsv($f, ['risorsa', 'pagina', 'idYext', 'codice', 'descrizione'], ';');
foreach ($problemi as $p) fputcsv($f, $p, ';');
fclose($f);
arsort($conteggio);
$pagineConProblemi = count(array_unique(array_column($problemi, 1)));
$rie = "Dato strutturato Yext Knowledge Tags su $host — " . date('d/m/Y H:i') . "\n"
    . "Pagine esaminate: " . count($righe) . "; con almeno un problema: $pagineConProblemi; JSON-LD archiviati: "
    . count(array_filter($archivio, fn($v) => $v['schema'])) . "\n\nProblemi per tipo (occorrenze):\n";
foreach ($conteggio as $c => $n) $rie .= sprintf("  %-26s %5d\n", $c, $n);
$tipi = [];
foreach ($archivio as $v) if ($v['schema']) { $t = implode('+', $lista($v['schema']['@type'] ?? null)); $tipi[$t] = ($tipi[$t] ?? 0) + 1; }
arsort($tipi);
$rie .= "\n@type usati da Yext:\n";
foreach ($tipi as $t => $n) $rie .= sprintf("  %-40s %5d\n", $t, $n);
file_put_contents("$out/riepilogo.txt", $rie);
echo "\n$rie\nFile: $out/{schemi.json,problemi.csv,riepilogo.txt}\n";
