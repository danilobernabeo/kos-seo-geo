<?php
/**
 * AA-3 / KOS-8 — PDF: link rotti nel sito e regole di redirect proposte. SOLA LETTURA (scrive solo file in ~).
 *
 * Uso:  php scripts/checks/pdf-intervento.php /www [~/pdf-mancanti-<data>.csv] [--base=https://anniazzurri.it]
 *
 * 1. Link rotti NEL SITO (indipendente dal log): tutti i link a PDF locali scritti nei contenuti delle risorse
 *    pubblicate, nei chunk, nei template e nelle TV; per ognuno verifica che il file esista (maiuscole comprese,
 *    come sul server). Link con tag MODX nel percorso (es. [[*alias]]) contati a parte: si verificano dal log.
 * 2. Redirect proposti (se si passa il CSV di pdf-mancanti.php): per i PDF richiesti dall'esterno e non più esistenti
 *      - stesso documento rinominato nella stessa cartella (somiglianza 100: maiuscole, spazi, trattini, anno);
 *        con più candidati vince quello con lo stesso anno del nome richiesto, altrimenti il più recente;
 *      - percorsi del vecchio sito: /assets/doc/CDS_<residenza>.pdf → strutture/<residenza>/Carta dei servizi.pdf,
 *        /assets/doc/pieghevole-<residenza>.pdf → strutture/<residenza>/Pieghevole informativo.pdf,
 *        /assets/doc/residenze/<residenza>/<file> → /assets/doc/strutture/<residenza>/<file>
 *        (solo se la destinazione esiste).
 *    Output: ~/pdf-redirect-<data>.conf (regole nginx da rivedere) e ~/pdf-link-rotti-<data>.csv.
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$csvIn   = (isset($argv[2]) && !str_starts_with($argv[2], '--')) ? $argv[2] : '';
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php pdf-intervento.php /www [pdf-mancanti.csv]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$modx->connect();
$db = $modx->pdo;
$p  = $modx->getOption('table_prefix', null, 'modx_');
// dominio canonico: da riga di comando site_url può valere «localhost»; si usa la base canonica del dato strutturato
$base = '';
foreach ($argv as $a) if (str_starts_with($a, '--base=')) $base = substr($a, 7);
if ($base === '') $base = (string) ($modx->getOption('aa.canonical_base') ?: $modx->getOption('kos.canonical_base') ?: $modx->getOption('site_url'));
$host = preg_replace('/^www\./', '', (string) parse_url($base, PHP_URL_HOST));
if ($host === '' || $host === 'localhost') { fwrite(STDERR, "Dominio non determinabile: passare --base=https://anniazzurri.it\n"); exit(1); }
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');
$stamp = date('Ymd-His');

/* ---------- 1. link rotti nel sito ---------- */
$fonti = [
    'risorsa'  => "SELECT id, pagetitle, content FROM {$p}site_content WHERE deleted = 0 AND published = 1 AND content LIKE '%.pdf%'",
    'chunk'    => "SELECT id, name, snippet FROM {$p}site_htmlsnippets WHERE snippet LIKE '%.pdf%'",
    'template' => "SELECT id, templatename, content FROM {$p}site_templates WHERE content LIKE '%.pdf%'",
    'TV'       => "SELECT v.contentid, c.pagetitle, v.value FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_content c ON c.id = v.contentid WHERE c.deleted = 0 AND c.published = 1 AND v.value LIKE '%.pdf%'",
];
$rotti = []; $ok = 0; $dinamici = 0;
foreach ($fonti as $tipo => $sql) {
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_NUM) as [$id, $nome, $testo]) {
        preg_match_all('#(?:href|src|data)\s*=\s*["\']([^"\']+?\.pdf)(?:[?\#][^"\']*)?["\']#i', (string) $testo, $m);
        foreach (array_unique($m[1]) as $u) {
            $u = html_entity_decode($u, ENT_QUOTES, 'UTF-8');
            if (str_contains($u, '[[')) { $dinamici++; continue; }
            if (preg_match('#^(?:https?:)?//([^/]+)(/.*)$#i', $u, $x)) {
                if (preg_replace('/^www\./', '', strtolower($x[1])) !== strtolower($host)) continue;   // PDF esterno
                $u = $x[2];
            }
            $rel = '/' . ltrim(rawurldecode($u), '/');
            if (is_file($webroot . $rel)) { $ok++; continue; }
            $rotti[$rel][] = "$tipo $id ($nome)";
        }
    }
}
$fr = fopen("$home/pdf-link-rotti-$stamp.csv", 'w');
fputcsv($fr, ['pdf', 'richiamato da', 'file simili nella cartella']);
echo "== Link a PDF nel sito: $ok funzionanti, " . count($rotti) . " rotti, $dinamici con tag MODX nel percorso (non verificati qui)\n";
foreach ($rotti as $rel => $dove) {
    $dir = dirname($webroot . $rel);
    $sim = is_dir($dir) ? array_map(fn($f) => basename($f), array_filter(glob($dir . '/*.{pdf,PDF}', GLOB_BRACE) ?: [],
        fn($f) => norma(basename($f)) === norma(basename($rel)))) : [];
    fputcsv($fr, [$rel, implode(' | ', array_unique($dove)), implode(' | ', $sim)]);
    printf("  ROTTO %s\n        in: %s%s\n", $rel, implode(', ', array_slice(array_unique($dove), 0, 4)),
        $sim ? "\n        file simile: " . implode(', ', $sim) : (is_dir($dir) ? '' : "\n        (la cartella non esiste)"));
}
fclose($fr);

function norma(string $s): string {
    $s = strtolower(pathinfo($s, PATHINFO_FILENAME));
    $s = preg_replace('/(19|20)\d{2}/', '', $s);
    return preg_replace('/[^a-z0-9]+/', '', $s);
}

/* ---------- 2. redirect proposti ---------- */
if ($csvIn === '' || !is_file($csvIn)) { echo "== (redirect: passare il CSV di pdf-mancanti.php)\n"; exit(0); }
$in = fopen($csvIn, 'r');
$head = fgetcsv($in);
$regole = []; $rivedere = [];
$enc = fn(string $rel) => implode('/', array_map('rawurlencode', explode('/', $rel)));
while (($r = fgetcsv($in)) !== false) {
    $row = array_combine($head, $r);
    $rel = $row['pdf'];
    if (is_file($webroot . $rel) || isset($rotti[$rel])) continue;     // esiste di nuovo, o è un link rotto del sito
    $dest = null; $motivo = '';
    // a) stesso documento rinominato nella stessa cartella
    $dir = dirname($webroot . $rel);
    if (is_dir($dir)) {
        $cand = array_values(array_filter(glob($dir . '/*.{pdf,PDF}', GLOB_BRACE) ?: [], fn($f) => norma(basename($f)) === norma(basename($rel))));
        if ($cand) {
            preg_match('/(19|20)\d{2}/', basename($rel), $anno);
            $stesso = $anno ? array_values(array_filter($cand, fn($f) => str_contains(basename($f), $anno[0]))) : [];
            if ($stesso) $dest = $stesso[0];
            else { usort($cand, fn($a, $b) => strnatcasecmp(basename($b), basename($a))); $dest = $cand[0]; }  // il più recente
            $motivo = 'rinominato' . (count($cand) > 1 ? ' (scelto tra ' . count($cand) . ')' : '');
        }
    }
    // b) percorsi del vecchio sito
    $alias = null; $file = null;
    if (!$dest && preg_match('#^/assets/doc/CDS_(residenza-anni-azzurri-[a-z0-9-]+)\.pdf$#i', $rel, $m)) { $alias = $m[1]; $file = 'Carta dei servizi.pdf'; }
    if (!$dest && preg_match('#^/assets/doc/pieghevole-(residenza-anni-azzurri-[a-z0-9-]+)\.pdf$#i', $rel, $m)) { $alias = $m[1]; $file = 'Pieghevole informativo.pdf'; }
    if (!$dest && preg_match('#^/assets/doc/residenze/([a-z0-9-]+)/(.+\.pdf)$#i', $rel, $m)) { $alias = $m[1]; $file = $m[2]; }
    if (!$dest && $alias) {
        $d = "$webroot/assets/doc/strutture/$alias";
        foreach (is_dir($d) ? (glob("$d/*.{pdf,PDF}", GLOB_BRACE) ?: []) : [] as $f) {
            if (norma(basename($f)) === norma($file)) { $dest = $f; $motivo = 'percorso del vecchio sito'; break; }
        }
    }
    if (!$dest) continue;
    $destRel = substr($dest, strlen($webroot));
    if (str_contains($rel, '"') || str_contains($rel, '\\')) { $rivedere[] = $rel; continue; }
    $regole[] = sprintf("# %s, %s richieste dal log\nlocation = \"%s\" {\n    return 301 https://%s%s;\n}",
        $motivo, $row['richieste'], $rel, $host, $enc($destRel));
}
fclose($in);
$conf = "$home/pdf-redirect-$stamp.conf";
file_put_contents($conf, "# AA-3 / KOS-8 — redirect dei PDF spostati o rinominati, generati il " . date('d/m/Y') . " da pdf-intervento.php.\n"
    . "# DA RIVEDERE prima di incollarli nelle regole web (prima dei blocchi location esistenti).\n\n" . implode("\n\n", $regole) . "\n");
echo "== Redirect proposti: " . count($regole) . " → $conf" . ($rivedere ? " (" . count($rivedere) . " esclusi per caratteri speciali)" : '') . "\n";
echo "== Link rotti: $home/pdf-link-rotti-$stamp.csv\n";
