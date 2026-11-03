<?php
/**
 * KOS-8 / AA-3 — Controllo ripetibile dei PDF nuovi o modificati (passi 5-6 del brief docs/pdf/brief-pdf.md).
 *
 * Percorre i PDF del sito (webroot/assets e radice) e trova:
 *   NUOVO        file non presente in nessun manifest di scripts/pdf/pdf-metadati.php e più recente dell'ultimo manifest;
 *   MODIFICATO   file del manifest con md5 diverso da quello scritto da noi (sostituito con un nuovo caricamento);
 *   TITOLO       file indicizzabili (elenco del piano o modelli per nome file) con titolo mancante, numerico, uguale al
 *                nome del file, generico, senza la struttura «documento – struttura», oltre 65 caratteri, o con Info e XMP diversi.
 * Per ognuno propone titolo («documento – nome ufficiale della struttura», max 65) e oggetto (max 150, dalla meta description
 * della pagina che richiama il PDF: solo fatti della pagina) e segnala:
 *   - i PDF nuovi che con la regola di default (noindex) restano fuori dall'indice ma sembrano da tenere;
 *   - le copie identiche (stesso documento in più percorsi) con il canonical proposto.
 * SOLA LETTURA sui PDF e sul database. Con --scrivi scrive solo il piano JSON per pdf-metadati.php (mai i PDF).
 *
 * Uso:   php scripts/pdf/controllo-pdf-nuovi.php <webroot> <piano-pdf.json> [opzioni]
 *   --autore="Gruppo KOS"     autore proposto per le voci del piano (senza: nessun autore)
 *   --lingua=it-IT            lingua predefinita proposta (i nomi file in inglese ricevono «en»)
 *   --imposta=<file.json>     valori decisi a mano per percorso: {"/assets/doc/x.pdf": {"t":"..","d":"..","a":"..","l":".."}};
 *                             prevalgono sulla proposta e includono il file anche se non risulta nuovo
 *   --da=AAAA-MM-GG           considera nuovi i file modificati da questa data (default: data dell'ultimo manifest in ~/backup-pdf-metadati-*)
 *   --manifest=<cartella>     cartella di backup da leggere in più (ripetibile)
 *   --base=https://sito       sito pubblico da cui leggere H1 e meta description (default dal piano)
 *   --auth=utente:password    Basic Auth per lo stage (meglio la variabile d'ambiente PDF_AUTH)
 *   --senza-modx              solo file system (niente pagine collegate, niente proposte tranne --imposta)
 *   --senza-http              non legge le pagine dal sito: nome struttura dal pagetitle (da verificare), niente oggetto
 *   --scrivi [--out=<file>]   scrive il piano (default ~/piano-pdf-nuovi-AAAAMMGG.json)
 * Poi: php scripts/pdf/pdf-metadati.php <webroot> <piano> (prova), e con --scrivi.
 */
declare(strict_types=1);

use MODX\Revolution\modX;

require_once __DIR__ . '/pdf-metadati.php';            // classe Pdf, lettura di Info/XMP; non esegue il suo comando se incluso
require_once __DIR__ . '/../checks/pdf-inventario.php'; // riferimenti(), richiamaLista(): stessi criteri dell'inventario

if (PHP_SAPI !== 'cli' || realpath($argv[0] ?? '') !== realpath(__FILE__)) return;

ini_set('memory_limit', '1536M');
set_time_limit(0);
mb_internal_encoding('UTF-8');

/* ============================ funzioni ============================ */

/** Nome senza estensione, minuscolo, con separatori ridotti a uno spazio (per confronti). */
function normalizzaNome(string $s): string
{
    $s = mb_strtolower($s);
    $s = preg_replace('/\.pdf$/', '', $s) ?? $s;
    return trim((string) preg_replace('/[\s._\-]+/u', ' ', $s));
}

/** Elenco dei difetti di un titolo (vuoto = a posto). $info e $xmp: titolo nel dizionario Info e in XMP (null = assente). */
function difettiTitolo(?string $info, ?string $xmp, string $stem, bool $comunicato): array
{
    $info = $info !== null ? trim($info) : null;
    $xmp = $xmp !== null ? trim($xmp) : null;
    $d = [];
    if (($info === null || $info === '') && ($xmp === null || $xmp === '')) return ['titolo mancante'];
    if ($info !== null && $info !== '' && $xmp !== null && $xmp !== '' && $info !== $xmp) $d[] = 'titolo in Info e in XMP diversi';
    if ($info === null || $info === '') $d[] = 'titolo assente nel dizionario Info';
    if ($xmp === null || $xmp === '') $d[] = 'titolo assente in XMP';
    $t = ($info !== null && $info !== '') ? $info : (string) $xmp;
    if (preg_match('/^[\d\s.,_\/-]+$/u', $t)) $d[] = 'titolo numerico';
    if (normalizzaNome($t) === normalizzaNome($stem)) $d[] = 'titolo uguale al nome del file';
    if (preg_match('/^(untitled|senza titolo|documento\s*\d*|document\s*\d*|microsoft\s+(word|powerpoint|excel)\b.*|layout\s*\d*|scan.*|slide\s*\d+|presentazione\d*|nuovo documento.*|copia di .*)$/iu', $t)
        || preg_match('/\.(indd|docx?|pptx?|xlsx?|pdf|rtf|odt)$/i', $t)) $d[] = 'titolo generico del programma di origine';
    if ($comunicato) {
        if (!str_starts_with($t, 'Comunicato stampa – ')) $d[] = 'senza la forma «Comunicato stampa – argomento – mese anno»';
    } elseif (!str_contains($t, ' – ')) {
        $d[] = str_contains($t, ' - ') ? 'senza struttura «documento – struttura» (trattino corto al posto del trattino lungo)' : 'senza struttura «documento – struttura»';
    }
    if (mb_strlen($t) > LIMITE_TITOLO) $d[] = 'titolo oltre ' . LIMITE_TITOLO . ' caratteri (' . mb_strlen($t) . ')';
    return array_values(array_unique($d));
}

/** Metadati attuali di un PDF: titolo/oggetto/autore (Info), lingua (catalogo), titolo XMP. Solleva eccezione se non leggibile. */
function metadatiAttuali(string $bytes): array
{
    $pdf = new Pdf($bytes);
    $tr = $pdf->trailer;
    if (str_contains($tr, '/Encrypt')) throw new RuntimeException('PDF cifrato');
    $out = ['t' => null, 'd' => null, 'a' => null, 'l' => null, 'xmp_t' => null];
    if (preg_match('#/Info\s+(\d+)\s+\d+\s+R#', $tr, $i)) {
        $o = $pdf->oggetto((int) $i[1]);
        if ($o && strncmp($o['testo'], '<<', 2) === 0) foreach (CAMPI_INFO as $k => $chiave) $out[$k] = valoreChiave($o['testo'], $chiave);
    }
    if (preg_match('#/Root\s+(\d+)\s+\d+\s+R#', $tr, $r)) {
        $cat = $pdf->oggetto((int) $r[1]);
        if ($cat && strncmp($cat['testo'], '<<', 2) === 0) {
            $out['l'] = valoreChiave($cat['testo'], 'Lang');
            if (preg_match('#/Metadata\s+(\d+)\s+\d+\s+R#', $cat['testo'], $m)) {
                $meta = $pdf->oggetto((int) $m[1]);
                if ($meta && $meta['dati'] !== null) $out['xmp_t'] = xmpValore($meta['dati'], 'dc:title');
            }
        }
    }
    return $out;
}

/** Il percorso è indicizzabile con le regole web? [sì/no, motivo]. */
function indicizzabile(string $p, array $piano, array $tieni): array
{
    if (isset($tieni[$p])) return [true, 'nell\'elenco dei PDF da tenere'];
    foreach ($piano['modelli'] ?? [] as $m) {
        if (@preg_match('#' . str_replace('#', '\\#', $m['rx']) . '#' . (!empty($m['i']) ? 'i' : ''), $p)) return [true, 'modello per nome file «' . $m['nome'] . '»'];
    }
    return [false, 'noindex per la regola di default'];
}

/** «Nome file» → nome del documento nel titolo. */
function nomeDocumento(string $stem): string
{
    $n = normalizzaNome($stem);
    if (preg_match('/carta\s+degli\s+alloggi/', $n)) return 'Carta degli alloggi';
    if (preg_match('/^(cds|carta\s+dei\s+servizi)\b/', $n)) return str_contains($n, 'rsa aperta') ? 'Carta dei servizi RSA aperta' : 'Carta dei servizi';
    if (preg_match('/^(pieghevole|leaflet|brochure|opuscolo)(\s+informativo)?$/', $n)) return $n[0] === 'l' ? 'Leaflet' : 'Pieghevole informativo';
    if (preg_match('/guida\s+(utente|ai\s+servizi)/', $n, $m)) return 'Guida ' . ($m[1] === 'utente' ? 'utente' : 'ai servizi');
    if (preg_match('/user\s*(manual|guide)/', $n)) return 'User guide';
    return mb_strtoupper(mb_substr($n, 0, 1)) . mb_substr($n, 1);
}

function accorcia(string $t, int $max): string
{
    foreach ([['/^Centro Diurno Integrato /u', 'CDI '], ['/^Residenza /u', ''], ['/ Centro Medico Riabilitativo$/u', '']] as [$rx, $sost]) {
        if (mb_strlen($t) <= $max) break;
        $t = (string) preg_replace($rx, $sost, $t);
    }
    return $t;
}

/** Oggetto di massimo 150 caratteri: frasi intere se possibile, altrimenti taglio a parola con «…». */
function oggettoDa(string $testo): string
{
    $t = trim((string) preg_replace('/\s+/u', ' ', $testo));
    if (mb_strlen($t) <= LIMITE_OGGETTO) return $t;
    $cut = mb_substr($t, 0, LIMITE_OGGETTO);
    if (preg_match('/^(.*[.!?])\s/u', $cut . ' ', $m) && mb_strlen($m[1]) >= 60) return $m[1];
    $spazio = mb_strrpos(mb_substr($t, 0, LIMITE_OGGETTO - 1), ' ');
    return rtrim(mb_substr($t, 0, $spazio ?: LIMITE_OGGETTO - 1), " ,;:–-") . '…';
}

/** H1 e meta description di una pagina pubblica. @return array{http:int,h1:array,desc:string} */
function leggiPagina(string $url, string $auth): array
{
    static $cache = [];
    if (isset($cache[$url])) return $cache[$url];
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'kos-seo-geo controllo-pdf-nuovi', CURLOPT_ENCODING => ''];
    if ($auth !== '') $opt[CURLOPT_USERPWD] = $auth;
    curl_setopt_array($ch, $opt);
    $html = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $r = ['http' => $code, 'h1' => [], 'desc' => ''];
    if ($code === 200 && $html !== '') {
        $dom = new DOMDocument(); libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html); libxml_clear_errors();
        $xp = new DOMXPath($dom);
        $pul = fn($s) => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        foreach ($xp->query('//h1') as $e) $r['h1'][] = $pul($e->textContent);
        $n = $xp->query('//meta[translate(@name,"DESCRIPTION","description")="description"]/@content');
        $r['desc'] = $n->length ? $pul($n->item(0)->nodeValue) : '';
    }
    return $cache[$url] = $r;
}

/* ============================ argomenti ============================ */

$webroot = rtrim((string) ($argv[1] ?? ''), '/');
$pianoFile = (string) ($argv[2] ?? '');
$opt = []; $manifestExtra = [];
foreach (array_slice($argv, 3) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) {
        if ($m[1] === 'manifest') $manifestExtra[] = rtrim((string) ($m[2] ?? ''), '/'); else $opt[$m[1]] = $m[2] ?? true;
    }
}
$piano = json_decode((string) @file_get_contents($pianoFile), true);
if ($webroot === '' || !is_dir("$webroot/assets") || !is_array($piano) || empty($piano['tieni'])) {
    fwrite(STDERR, "Uso: php controllo-pdf-nuovi.php <webroot> <piano-pdf.json con 'tieni' e 'modelli'> [opzioni: vedi l'intestazione del file]\n"
        . "Il piano si rigenera con scripts/pdf/genera-piano-pdf.py (le versioni precedenti non hanno 'tieni').\n"); exit(1);
}
$sito = (string) ($piano['sito'] ?? '');
$base = rtrim((string) ($opt['base'] ?? ($sito === 'aa' ? 'https://anniazzurri.it' : 'https://kosgroup.com')), '/');
$auth = (string) ($opt['auth'] ?? getenv('PDF_AUTH') ?: '');
$autore = isset($opt['autore']) && is_string($opt['autore']) ? trim($opt['autore']) : '';
$lingua = isset($opt['lingua']) && is_string($opt['lingua']) ? trim($opt['lingua']) : 'it-IT';
$senzaModx = isset($opt['senza-modx']);
$senzaHttp = isset($opt['senza-http']) || $senzaModx;
$scrivi = isset($opt['scrivi']);
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');
$tieni = array_fill_keys($piano['tieni'], true);
$canonicalNota = $piano['canonical'] ?? [];
$titoliPiano = [];
foreach ($piano['pdf'] as $v) if (!empty($v['t'])) $titoliPiano[$v['p']] = $v['t'];
$imposta = [];
if (!empty($opt['imposta']) && is_string($opt['imposta'])) {
    $imposta = json_decode((string) @file_get_contents($opt['imposta']), true);
    if (!is_array($imposta)) { fwrite(STDERR, "File --imposta non leggibile: {$opt['imposta']}\n"); exit(1); }
}

/* ============================ manifest ============================ */

$cartelleManifest = array_merge(glob($home . '/backup-pdf-metadati-*', GLOB_ONLYDIR) ?: [], $manifestExtra);
$noto = [];   // percorso → ['dopo' => md5, 'prima' => md5, 'cartella' => ...]
$ultimo = 0;
foreach ($cartelleManifest as $c) {
    $m = json_decode((string) @file_get_contents($c . '/manifest.json'), true);
    if (!is_array($m)) continue;
    foreach ([$m['creato'] ?? null, $m['aggiornato'] ?? null] as $d) if ($d && ($t = strtotime((string) $d))) $ultimo = max($ultimo, $t);
    foreach ($m['file'] ?? [] as $f) $noto[$f['p']] = ['dopo' => $f['md5_dopo'], 'prima' => $f['md5_prima'], 'cartella' => $c];
}
if (!empty($opt['da']) && is_string($opt['da'])) $ultimo = (int) strtotime($opt['da']);
echo '== Sito ' . ($sito ?: '?') . ", webroot $webroot\n";
echo '== Manifest letti: ' . count($cartelleManifest) . ', file noti: ' . count($noto) . ', riferimento: ' . ($ultimo ? date('Y-m-d H:i', $ultimo) : '(nessuno: tutti i file non noti sono nuovi)') . "\n";

/* ============================ file system ============================ */

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$webroot/assets", FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) if ($f->isFile() && strtolower($f->getExtension()) === 'pdf') $files[substr($f->getPathname(), strlen($webroot))] = $f->getPathname();
foreach (glob("$webroot/*.{pdf,PDF}", GLOB_BRACE) ?: [] as $f) $files['/' . basename($f)] = $f;
uksort($files, 'strnatcasecmp');

$md5 = []; $md5Originale = []; $stato = [];
foreach ($files as $rel => $path) {
    $h = md5_file($path);
    $md5[$rel] = $h;
    $n = $noto[$rel] ?? null;
    $md5Originale[$rel] = ($n && $n['dopo'] === $h) ? $n['prima'] : $h;   // dopo la nostra scrittura il file cambia: si confronta l'originale
    if ($n) $stato[$rel] = $n['dopo'] === $h ? 'noto' : 'MODIFICATO';
    elseif ($ultimo === 0 || (int) filemtime($path) > $ultimo) $stato[$rel] = 'NUOVO';
    else $stato[$rel] = 'storico';
}
foreach (array_keys($imposta) as $p) if (isset($files[$p]) && $stato[$p] !== 'NUOVO' && $stato[$p] !== 'MODIFICATO') $stato[$p] = 'IMPOSTATO';
foreach (array_keys($imposta) as $p) if (!isset($files[$p])) echo "ATTENZIONE: --imposta cita $p, che non esiste sul server\n";

/* ============================ difetti dei titoli ============================ */

$inPiano = array_fill_keys(array_column($piano['pdf'], 'p'), true);
$candidati = [];   // percorso → ['stato','difetti','meta','errore','indicizzabile','motivo']
$illeggibili = []; $attesa = 0;
foreach ($files as $rel => $path) {
    $s = $stato[$rel];
    $ind = indicizzabile($rel, $piano, $tieni);
    $nuovoOMod = in_array($s, ['NUOVO', 'MODIFICATO', 'IMPOSTATO'], true);
    // i file già scritti da noi e invariati hanno il titolo che abbiamo scritto; i vecchi non indicizzabili non interessano
    if (!$nuovoOMod && (!$ind[0] || $s === 'noto')) continue;
    $comunicato = $sito === 'kos' && str_starts_with($rel, '/assets/doc/comunicati/');
    $meta = null; $errore = '';
    try { $meta = metadatiAttuali((string) file_get_contents($path)); } catch (Throwable $e) { $errore = $e->getMessage(); }
    if ($meta === null) {
        if ($nuovoOMod) $candidati[$rel] = ['stato' => $s, 'difetti' => ['non leggibile: ' . $errore], 'meta' => null, 'ind' => $ind];
        else $illeggibili[$rel] = $errore;
        continue;
    }
    $difetti = difettiTitolo($meta['t'], $meta['xmp_t'], pathinfo($rel, PATHINFO_FILENAME), $comunicato);
    if (!$nuovoOMod) {
        if (!$difetti) continue;
        if (!isset($inPiano[$rel])) { $attesa++; continue; }   // da tenere ma fuori dal piano (in attesa di KOS): non si propone
        $s = 'TITOLO';
    }
    $candidati[$rel] = ['stato' => $s, 'difetti' => $difetti, 'meta' => $meta, 'ind' => $ind];
}

/* ============================ pagine che richiamano i PDF (MODX) ============================ */

$pagine = [];   // percorso → [id → pagina]
if (!$senzaModx && $candidati) {
    require "$webroot/config.core.php";
    require MODX_CORE_PATH . 'vendor/autoload.php';
    $modx = modX::getInstance();
    $modx->initialize('mgr');
    $modx->connect();
    $db = $modx->pdo;
    $p = $modx->getOption('table_prefix', null, 'modx_');
    $chunks = []; $tpl = [];
    foreach ($db->query("SELECT name, snippet FROM {$p}site_htmlsnippets", PDO::FETCH_NUM) as [$n, $c]) $chunks[$n] = (string) $c;
    foreach ($db->query("SELECT id, templatename, content FROM {$p}site_templates", PDO::FETCH_NUM) as [$id, $n, $c]) $tpl[(int) $id] = [$n, (string) $c];
    $res = [];
    foreach ($db->query("SELECT id, context_key, uri, alias, parent, published, template, pagetitle, description, introtext, content FROM {$p}site_content WHERE deleted = 0", PDO::FETCH_ASSOC) as $r) $res[(int) $r['id']] = $r;
    $voce = fn(int $id, string $via) => ['id' => $id, 'uri' => (string) $res[$id]['uri'], 'alias' => (string) $res[$id]['alias'], 'pagetitle' => (string) $res[$id]['pagetitle'],
        'description' => (string) $res[$id]['description'], 'introtext' => (string) $res[$id]['introtext'], 'pubblicata' => (bool) $res[$id]['published'], 'web' => $res[$id]['context_key'] === 'web', 'via' => $via];
    $voluti = array_fill_keys(array_keys($candidati), true);
    foreach ($res as $id => $r) foreach (riferimenti((string) $r['content']) as $f) if (isset($voluti[$f])) $pagine[$f][$id] = $voce($id, 'link nel contenuto');
    foreach ($db->query("SELECT contentid, tmplvarid, value FROM {$p}site_tmplvar_contentvalues WHERE value LIKE '%.pdf%'", PDO::FETCH_NUM) as [$cid, $tid, $v]) {
        if (!isset($res[(int) $cid])) continue;
        foreach (riferimenti((string) $v) as $f) if (isset($voluti[$f])) $pagine[$f][(int) $cid] = $voce((int) $cid, 'link in una TV');
    }
    $cartelle = [];
    foreach ($res as $id => $r) {
        $testi = (string) $r['content'] . (isset($tpl[(int) $r['template']]) ? "\n" . $tpl[(int) $r['template']][1] : '');
        $input = [];
        if (richiamaLista($testi, 'docList', $chunks, [], $input)) {
            foreach (array_unique($input) as $i) {
                $dir = trim(str_replace('[[*alias]]', (string) $r['alias'], $i), '/');
                if (!str_contains($dir, '[[')) $cartelle[$dir][$id] = true;
            }
        }
        if (richiamaLista($testi, 'listLda', $chunks) && isset($res[(int) $r['parent']])) $cartelle['assets/doc/strutture/' . $res[(int) $r['parent']]['alias'] . '/' . $r['alias']][$id] = true;
    }
    foreach (array_keys($candidati) as $rel) {
        $dir = ltrim(dirname($rel), '/');
        foreach (array_keys($cartelle[$dir] ?? []) as $id) $pagine[$rel][$id] ??= $voce($id, 'elenco automatico della cartella');
        // scheda della cartella: /assets/doc/strutture/<alias>/...
        if (preg_match('#^/assets/doc/strutture/([^/]+)/#', $rel, $m)) {
            foreach ($res as $id => $r) if ($r['alias'] === $m[1] && $r['context_key'] === 'web') $pagine[$rel][$id] ??= $voce($id, 'scheda con lo stesso alias della cartella');
        }
    }
}

/* ============================ proposte ============================ */

$proposte = [];   // percorso → ['t','d','a','l','fonte','note']
$titoliUsati = [];
foreach ($candidati as $rel => $c) {
    $stem = pathinfo($rel, PATHINFO_FILENAME);
    $pr = ['t' => null, 'd' => null, 'a' => null, 'l' => null, 'fonte' => '', 'note' => []];
    $pg = array_filter($pagine[$rel] ?? [], fn($x) => $x['web'] && $x['pubblicata']);
    // pagina di riferimento: prima la scheda con alias uguale alla cartella, poi l'unica pagina che richiama il PDF
    $scelta = null;
    if (preg_match('#^/assets/doc/strutture/([^/]+)/#', $rel, $m)) foreach ($pg as $x) if ($x['alias'] === $m[1]) { $scelta = $x; break; }
    if (!$scelta && count($pg) === 1) $scelta = reset($pg);
    if (!$scelta && count($pg) > 1) $pr['note'][] = 'richiamato da ' . count($pg) . ' pagine (' . implode(', ', array_map(fn($x) => '/' . ltrim($x['uri'], '/'), array_slice($pg, 0, 3))) . '): scegliere la struttura a mano con --imposta';
    if (!$scelta && !$pg) $pr['note'][] = 'nessuna pagina pubblicata richiama il PDF: scegliere la struttura a mano con --imposta';
    $nome = null; $descr = '';
    if ($scelta) {
        $pr['fonte'] = '/' . ltrim($scelta['uri'], '/') . ' (' . $scelta['via'] . ')';
        $url = $base . '/' . ltrim($scelta['uri'], '/');
        if (!$senzaHttp) {
            $pagina = leggiPagina($url, $auth);
            if ($pagina['http'] === 200 && count($pagina['h1']) >= 1) {
                $nome = $pagina['h1'][0];
                if (count($pagina['h1']) > 1) $pr['note'][] = 'la pagina ha ' . count($pagina['h1']) . ' H1: usato il primo';
                $descr = $pagina['desc'];
            } else {
                $pr['note'][] = "pagina $url non leggibile (HTTP {$pagina['http']}): nome dal pagetitle, da verificare";
            }
        }
        if ($nome === null) { $nome = $scelta['pagetitle']; $pr['note'][] = 'nome struttura dal pagetitle, non dall\'H1: da verificare'; }
        if ($descr === '') $descr = $scelta['description'] !== '' ? $scelta['description'] : $scelta['introtext'];
    }
    if ($nome !== null && $nome !== '') {
        $doc = nomeDocumento($stem);
        $pr['t'] = accorcia($doc . ' – ' . $nome, LIMITE_TITOLO);
        if (mb_strlen($pr['t']) > LIMITE_TITOLO) $pr['note'][] = 'titolo oltre ' . LIMITE_TITOLO . ' caratteri (' . mb_strlen($pr['t']) . '): accorciare con --imposta';
    }
    if ($descr !== '') $pr['d'] = oggettoDa(strip_tags($descr));
    if ($autore !== '') $pr['a'] = $autore;
    $pr['l'] = preg_match('/english|\beng\b|user[ _-]*(guide|manual)|leaflet|patients/i', $stem) ? 'en' : $lingua;
    // valori decisi a mano
    foreach ($imposta[$rel] ?? [] as $k => $v) if (in_array($k, ['t', 'd', 'a', 'l'], true) && $v !== '' && $v !== null) { $pr[$k] = $v; $pr['fonte'] = 'impostato a mano (--imposta)'; }
    if (isset($imposta[$rel])) $pr['note'] = array_values(array_filter($pr['note'], fn($n) => !str_contains($n, 'con --imposta') && !str_contains($n, 'da verificare')));
    $proposte[$rel] = $pr;
}

/* ============================ copie identiche ============================ */

$gruppi = [];
foreach ($md5Originale as $rel => $h) $gruppi[$h][] = $rel;
$copie = [];
foreach (array_keys($candidati) as $rel) {
    $altre = array_values(array_diff($gruppi[$md5Originale[$rel]] ?? [], [$rel]));
    if ($altre) $copie[$rel] = $altre;
}

/* ============================ rapporto ============================ */

$kb = fn(string $path) => number_format((int) filesize($path) / 1024, 0, ',', '.') . ' KB';
$piani = [];
$cont = ['NUOVO' => 0, 'MODIFICATO' => 0, 'IMPOSTATO' => 0, 'TITOLO' => 0];
$noindexDaTenere = [];
$positivi = '/pieghevole|brochure|leaflet|opuscolo|carta|guida|manuale|manual|programma|catalogo|listino|tariff|servizi|presentazione|prestazioni|attivit|regolamento|informativ/i';
$negativi = '/modulo|consenso|contratto|scheda|questionario|delega|richiesta|autorizzaz|privacy|curriculum|anagraf|domanda|liberatoria|dichiarazione|fattura|ricevuta|bozza|prova|test/i';
foreach ($candidati as $rel => $c) {
    $pr = $proposte[$rel];
    $cont[$c['stato']] = ($cont[$c['stato']] ?? 0) + 1;
    echo "\n" . str_pad($c['stato'], 11) . $rel . '  (' . $kb($files[$rel]) . ', ' . date('Y-m-d', (int) filemtime($files[$rel])) . ")\n";
    echo '   indicizzabile: ' . ($c['ind'][0] ? 'sì' : 'NO') . ' — ' . $c['ind'][1] . "\n";
    if ($c['meta'] !== null) {
        $m = $c['meta'];
        echo '   attuale: titolo «' . ($m['t'] ?? '') . '»' . ($m['xmp_t'] !== null && $m['xmp_t'] !== $m['t'] ? ', XMP «' . $m['xmp_t'] . '»' : '') . ', oggetto ' . ($m['d'] ? '«' . mb_substr($m['d'], 0, 60) . '…»' : '(vuoto)') . ', autore ' . ($m['a'] ?: '(vuoto)') . ', lingua ' . ($m['l'] ?: '(non indicata)') . "\n";
    }
    if ($c['difetti']) echo '   problemi: ' . implode('; ', $c['difetti']) . "\n";
    if ($pr['fonte'] !== '') echo '   pagina: ' . $pr['fonte'] . "\n";
    if ($pr['t'] !== null) echo '   proposta titolo: ' . $pr['t'] . '  [' . mb_strlen($pr['t']) . " car.]\n";
    if ($pr['d'] !== null) echo '   proposta oggetto: ' . $pr['d'] . '  [' . mb_strlen($pr['d']) . " car.]\n";
    echo '   proposta autore/lingua: ' . ($pr['a'] ?: '(nessun autore: usare --autore)') . ' / ' . $pr['l'] . "\n";
    foreach ($pr['note'] as $n) echo "   nota: $n\n";
    if (isset($copie[$rel])) {
        // la copia più recente rimanda alla più vecchia (quella già indicizzata o già nell'elenco ha la precedenza)
        $gruppo = array_merge([$rel], $copie[$rel]);
        usort($gruppo, fn($a, $b) => [isset($tieni[$b]) <=> isset($tieni[$a]), filemtime($files[$a]), $a] <=> [0, filemtime($files[$b]), $b]);
        $origine = $gruppo[0];
        echo '   COPIE IDENTICHE: ' . implode(', ', $copie[$rel]) . "\n";
        echo '   → proposta: ' . ($origine === $rel ? 'questo è il file di riferimento: le altre copie hanno il canonical verso di lui'
            : 'Link rel="canonical" di questo file verso ' . $origine) . (isset($canonicalNota[$rel]) ? ' (già con canonical verso ' . $canonicalNota[$rel] . ')' : '') . "\n";
    }
    if (!$c['ind'][0]) {
        $segnali = [];
        if (preg_match($positivi, pathinfo($rel, PATHINFO_FILENAME))) $segnali[] = 'nome del file tipico di un documento da tenere';
        if (!empty($pagine[$rel])) $segnali[] = 'richiamato da ' . count($pagine[$rel]) . ' pagina/e del sito';
        if ($segnali && !preg_match($negativi, pathinfo($rel, PATHINFO_FILENAME))) {
            $noindexDaTenere[] = $rel;
            echo '   ⚠ NOINDEX PER DEFAULT MA FORSE DA TENERE (' . implode('; ', $segnali) . "): rinominarlo con un nome che segue un modello (carta dei servizi, pieghevole, guida utente) oppure decidere TIENI e aggiungerlo al foglio\n";
        }
    }
    // voce del piano: solo per i file indicizzabili con titolo valido
    if ($c['ind'][0] && $c['meta'] !== null || ($c['ind'][0] && isset($imposta[$rel]))) {
        $titolo = $pr['t'];
        $motivoNo = '';
        if ($titolo === null || $titolo === '') $motivoNo = 'nessun titolo proposto';
        elseif (mb_strlen($titolo) > LIMITE_TITOLO) $motivoNo = 'titolo oltre ' . LIMITE_TITOLO . ' caratteri';
        else {
            $dup = array_search($titolo, $titoliUsati, true);
            if ($dup !== false) $motivoNo = "titolo uguale a quello proposto per $dup";
            else foreach ($titoliPiano as $pp => $tt) if ($tt === $titolo && $pp !== $rel) { $motivoNo = "titolo uguale a quello di $pp"; break; }
        }
        if ($motivoNo === '') {
            $titoliUsati[$rel] = $titolo;
            $v = ['p' => $rel, 't' => $titolo];
            if ($pr['d'] !== null && mb_strlen($pr['d']) <= LIMITE_OGGETTO) $v['d'] = $pr['d'];
            if ($pr['a']) $v['a'] = $pr['a'];
            if ($pr['l']) $v['l'] = $pr['l'];
            $piani[] = $v;
        } else {
            echo "   NON NEL PIANO: $motivoNo\n";
        }
    } elseif ($c['ind'][0] && $c['meta'] === null) {
        echo "   NON NEL PIANO: file non leggibile (struttura PDF da ricostruire)\n";
    }
}

/* ============================ riepilogo e piano ============================ */

echo "\n== Riepilogo: " . count($files) . ' PDF sul server; da controllare ' . count($candidati)
    . " (nuovi {$cont['NUOVO']}, modificati {$cont['MODIFICATO']}, impostati {$cont['IMPOSTATO']}, titolo da correggere {$cont['TITOLO']})\n";
echo '== Nel piano: ' . count($piani) . '; noindex per default ma forse da tenere: ' . count($noindexDaTenere) . '; con copie identiche: ' . count($copie) . "\n";
if ($attesa) echo "== Da tenere con titolo da correggere ma fuori dal piano (in attesa di conferma KOS): $attesa\n";
if ($illeggibili) { echo '== Non leggibili (tabella interna rotta o cifrati), da ricostruire a parte: ' . count($illeggibili) . "\n"; foreach ($illeggibili as $p => $e) echo "   $p — $e\n"; }
if (!$candidati) echo "== Nessun PDF nuovo, modificato o con titolo da correggere.\n";

if ($scrivi) {
    $out = isset($opt['out']) && is_string($opt['out']) ? $opt['out'] : $home . '/piano-pdf-nuovi-' . date('Ymd') . '.json';
    if (!$piani) { echo "== Nessuna voce da scrivere nel piano.\n"; exit(0); }
    file_put_contents($out, json_encode(['sito' => $sito, 'generato' => date('Y-m-d'), 'origine' => 'scripts/pdf/controllo-pdf-nuovi.php', 'pdf' => $piani],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    echo "== Piano scritto: $out (" . count($piani) . " voci). Prossimo passo: php scripts/pdf/pdf-metadati.php $webroot $out   (prova), poi con --scrivi\n";
} else {
    echo "== PROVA: non ho scritto nulla. Con --scrivi scrivo il piano JSON per pdf-metadati.php (mai i PDF).\n";
}
