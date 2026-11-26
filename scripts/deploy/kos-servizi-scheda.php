<?php
/**
 * kos-servizi-scheda.php — aggiorna i servizi di UNA scheda struttura di kosgroup.com su richiesta di KOS:
 * associazioni con le pagine «Servizi e cure» (in entrambe le direzioni, quindi anche il dato strutturato
 * availableService) e voci dell'elenco visibile «Aree di intervento» (ul dopo id="interventi-terapeutici").
 * Prova, scrittura con backup, ripristino.
 *
 * Uso (SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/kos-servizi-scheda.php /www --scheda=it/centri/<alias> \
 *       [--togli=<uri servizio>,...] [--aggiungi=<uri servizio>,...] \
 *       [--lista-togli=Voce,Voce] [--lista-aggiungi=Voce,Voce] [--scrivi]
 *   php scripts/deploy/kos-servizi-scheda.php /www --ripristina=<cartella di backup> [--scrivi]
 * Gli uri dei servizi sono quelli di MODX senza slash iniziale (it/servizi-e-cure/...); le voci dell'elenco si
 * confrontano senza maiuscole e spazi. Nei comandi lanciati da PowerShell → ssh niente virgolette doppie: le voci
 * con spazi si scrivono con il trattino basso (Scienza_della_Nutrizione), che lo script converte in spazio.
 *
 * Regole:
 *   - scheda → servizio: l'id del servizio si toglie da tutte le TV map* della scheda; si aggiunge nella TV della
 *     scheda che contiene già più servizi della stessa sezione (es. mapAreeRiabilitazione per gli ambulatori di
 *     San Martino), altrimenti nella predefinita della sezione;
 *   - servizio → scheda: l'id della scheda si toglie o si aggiunge in mapServiziStrutture della pagina del servizio;
 *   - elenco visibile: cercato nel contenuto e nelle TV della scheda; FERMO se non c'è o se c'è più di una volta, se
 *     una voce da togliere manca o una da aggiungere c'è già; le voci restano in ordine alfabetico;
 *   - FERMO se un servizio da aggiungere non esiste o non è pubblicato.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$opt = []; $pos = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
$scrivi = isset($opt['scrivi']);
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-servizi-scheda.php /www --scheda=it/centri/<alias> ... [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;
$fermo = function (string $m) { fwrite(STDERR, "FERMO: $m\n"); exit(2); };
$lista = fn($v) => array_values(array_filter(array_map(fn($x) => trim(str_replace('_', ' ', $x)), explode(",", (string) $v))));
$ids = fn(string $v) => array_values(array_unique(array_filter(array_map('intval', preg_split('/[^\d]+/', $v)))));
$norm = fn(string $s) => mb_strtolower(preg_replace('/\s+/u', ' ', trim(html_entity_decode(strip_tags($s), ENT_QUOTES, 'UTF-8'))));
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');

// ---------- ripristino -----------------------------------------------------------------------------------------------
if (isset($opt['ripristina'])) {
    $f = rtrim((string) $opt['ripristina'], '/') . '/ripristino.json';
    if (!is_file($f)) $fermo("manca $f");
    $rip = json_decode((string) file_get_contents($f), true);
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $f\n";
    foreach ($rip['risorse'] as $r) {
        $res = $modx->getObject(modResource::class, (int) $r['id']);
        if (!$res) { echo "  ! risorsa {$r['id']} non trovata\n"; continue; }
        echo "  {$r['id']} {$res->get('pagetitle')}: " . implode(', ', array_merge(isset($r['content']) ? ['contenuto'] : [], array_keys($r['tv'] ?? []))) . "\n";
        if (!$scrivi) continue;
        if (isset($r['content'])) { $res->set('content', $r['content']); $res->save(); }
        foreach ($r['tv'] ?? [] as $tv => $v) $res->setTVValue($tv, $v);
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Ripristinato. Cache svuotata.\n"; }
    exit(0);
}

// ---------- dati -----------------------------------------------------------------------------------------------------
$TV_SCHEDA = ['mapAreeRiabilitazione', 'mapAreePsichiatria', 'mapAreeMedica', 'mapAreeChirurgica', 'mapAreeDiagnostica',
              'mapInterventiRiabilitazione', 'mapInterventiPsichiatria', 'mapInterventiDiagnostica'];
$PREDEFINITA = ['riabilitazione/trattamenti' => 'mapInterventiRiabilitazione', 'psichiatria/trattamenti' => 'mapInterventiPsichiatria',
                'riabilitazione' => 'mapAreeRiabilitazione', 'psichiatria' => 'mapAreePsichiatria',
                'ambulatori-e-medicina-specialistica' => 'mapAreeMedica'];
$sezione = function (string $uri): string {
    $x = explode('/', trim($uri, '/'));
    return ($x[2] ?? '') . (in_array('trattamenti', $x, true) ? '/trattamenti' : '');
};
$uriScheda = trim((string) ($opt['scheda'] ?? ''), '/');
$scheda = $uriScheda !== '' ? $modx->getObject(modResource::class, ['uri' => $uriScheda, 'deleted' => 0]) : null;
if (!$scheda) $fermo("scheda non trovata: --scheda=$uriScheda");
$sid = (int) $scheda->get('id');
$tplId = (int) $scheda->get('template');

$tvOk = [];
foreach (array_merge($TV_SCHEDA, ['mapServiziStrutture']) as $n) {
    $tv = $modx->getObject(modTemplateVar::class, ['name' => $n]);
    if (!$tv) continue;
    foreach ($modx->getIterator(modTemplateVarTemplate::class, ['tmplvarid' => $tv->get('id')]) as $l) $tvOk[$n][(int) $l->get('templateid')] = true;
}
$servizio = function (string $uri) use ($modx, $fermo) {
    $r = $modx->getObject(modResource::class, ['uri' => trim($uri, '/'), 'deleted' => 0]);
    if (!$r) $fermo("servizio non trovato: $uri");
    return $r;
};
$togli = array_map($servizio, $lista($opt['togli'] ?? ''));
$aggiungi = array_map($servizio, $lista($opt['aggiungi'] ?? ''));
foreach ($aggiungi as $r) if (!$r->get('published')) $fermo("servizio non pubblicato: {$r->get('uri')}");

$prima = ['content' => (string) $scheda->get('content'), 'tv' => []];
foreach ($TV_SCHEDA as $n) if (!empty($tvOk[$n][$tplId])) $prima['tv'][$n] = (string) $scheda->getTVValue($n);
$dopo = $prima;
$righe = [];
$servPrima = []; $servDopo = [];

// scheda → servizio
foreach ($togli as $r) {
    $trovata = false;
    foreach ($dopo['tv'] as $n => $v) {
        $l = $ids($v);
        if (in_array((int) $r->get('id'), $l, true)) {
            $dopo['tv'][$n] = implode(',', array_values(array_diff($l, [(int) $r->get('id')])));
            $righe[] = "  scheda: − {$r->get('pagetitle')} da $n";
            $trovata = true;
        }
    }
    if (!$trovata) $righe[] = "  scheda: {$r->get('pagetitle')} non era associata (nessuna TV)";
}
foreach ($aggiungi as $r) {
    $rid = (int) $r->get('id');
    $gia = false;
    foreach ($dopo['tv'] as $n => $v) if (in_array($rid, $ids($v), true)) { $gia = true; $righe[] = "  scheda: {$r->get('pagetitle')} già in $n"; }
    if ($gia) continue;
    $sez = $sezione($r->get('uri'));
    $conta = [];
    foreach ($dopo['tv'] as $n => $v) foreach ($ids($v) as $x) {
        $u = (string) $db->query("SELECT uri FROM {$p}site_content WHERE id = " . (int) $x)->fetchColumn();
        if ($u !== '' && $sezione($u) === $sez) $conta[$n] = ($conta[$n] ?? 0) + 1;
    }
    arsort($conta);
    $n = $conta ? array_key_first($conta) : ($PREDEFINITA[$sez] ?? null);
    if (!$n || !isset($dopo['tv'][$n])) $fermo("nessuna TV della scheda adatta per {$r->get('pagetitle')} (sezione $sez)");
    $dopo['tv'][$n] = implode(',', array_merge($ids($dopo['tv'][$n]), [$rid]));
    $righe[] = "  scheda: + {$r->get('pagetitle')} in $n";
}
// servizio → scheda
foreach (array_merge(array_map(fn($r) => [$r, '-'], $togli), array_map(fn($r) => [$r, '+'], $aggiungi)) as [$r, $segno]) {
    $rid = (int) $r->get('id');
    $v = (string) $r->getTVValue('mapServiziStrutture');
    $l = $ids($v);
    $nuovo = $segno === '-' ? array_values(array_diff($l, [$sid])) : array_values(array_unique(array_merge($l, [$sid])));
    if ($nuovo === $l) { $righe[] = "  {$r->get('pagetitle')}: mapServiziStrutture già " . ($segno === '-' ? 'senza' : 'con') . " la scheda"; continue; }
    $servPrima[$rid] = $v; $servDopo[$rid] = implode(',', $nuovo);
    $righe[] = "  {$r->get('pagetitle')}: mapServiziStrutture " . ($segno === '-' ? '− ' : '+ ') . $scheda->get('pagetitle');
}

// elenco visibile «Aree di intervento»
$lt = $lista($opt['lista-togli'] ?? ''); $la = $lista($opt['lista-aggiungi'] ?? '');
if ($lt || $la) {
    $re = '~(id="interventi-terapeutici".*?<ul[^>]*>)(.*?)(\s*</ul>)~s';
    $dove = [];
    if (preg_match_all($re, $dopo['content'], $m) ) $dove['content'] = count($m[0]);
    $tvTutte = [];
    foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid' => $tplId]) as $l) {
        $tv = $modx->getObject(modTemplateVar::class, (int) $l->get('tmplvarid'));
        if (!$tv) continue;
        $v = (string) $scheda->getTVValue($tv->get('name'));
        if (preg_match_all($re, $v, $m)) { $dove[$tv->get('name')] = count($m[0]); $tvTutte[$tv->get('name')] = $v; }
    }
    if (array_sum($dove) !== 1) $fermo('elenco «Aree di intervento» trovato ' . array_sum($dove) . ' volte (' . json_encode($dove) . '): atteso 1');
    $campo = array_key_first($dove);
    $testo = $campo === 'content' ? $dopo['content'] : $tvTutte[$campo];
    preg_match($re, $testo, $m);
    preg_match_all('~^(\s*)<li>(.*?)</li>~m', $m[2], $li);
    $rientro = $li[1][0] ?? "\n                                ";
    $voci = $li[2];
    $chiavi = array_map($norm, $voci);
    foreach ($lt as $x) {
        $k = array_search($norm($x), $chiavi, true);
        if ($k === false) $fermo("voce da togliere non presente nell'elenco: $x");
        unset($voci[$k], $chiavi[$k]);
        $righe[] = "  elenco ($campo): − $x";
    }
    foreach ($la as $x) {
        if (in_array($norm($x), $chiavi, true)) $fermo("voce da aggiungere già presente: $x");
        $voci[] = htmlspecialchars($x, ENT_QUOTES | ENT_HTML5, 'UTF-8'); $chiavi[] = $norm($x);
        $righe[] = "  elenco ($campo): + $x";
    }
    $coll = class_exists('Collator') ? new Collator('it_IT') : null;
    usort($voci, fn($a, $b) => $coll ? $coll->compare($norm($a), $norm($b)) : strcmp($norm($a), $norm($b)));
    $nuovaLista = implode('', array_map(fn($v) => "\n" . ltrim($rientro, "\r\n") . "<li>$v</li>", $voci));
    $testoNuovo = preg_replace($re, '${1}' . str_replace('$', '\$', $nuovaLista) . '${3}', $testo, 1);
    if ($campo === 'content') $dopo['content'] = $testoNuovo;
    else { $prima['tv'][$campo] = $testo; $dopo['tv'][$campo] = $testoNuovo; }
    $righe[] = "  elenco dopo: " . implode(', ', array_map(fn($v) => html_entity_decode($v), $voci));
}

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — {$scheda->get('pagetitle')} (id $sid, /$uriScheda)\n";
echo implode("\n", $righe) . "\n";
if (!$scrivi) exit(0);

$stamp = date('Ymd-His');
$bk = "$home/backup-kos-seo-geo/$stamp-servizi-scheda";
mkdir($bk, 0775, true);
$rip = ['nome' => "ripristino servizi scheda /$uriScheda $stamp", 'risorse' => [['id' => $sid, 'content' => $prima['content'], 'tv' => $prima['tv']]]];
foreach ($servPrima as $rid => $v) $rip['risorse'][] = ['id' => $rid, 'tv' => ['mapServiziStrutture' => $v]];
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
if ($dopo['content'] !== $prima['content']) { $scheda->set('content', $dopo['content']); $scheda->save(); }
foreach ($dopo['tv'] as $n => $v) if ($v !== ($prima['tv'][$n] ?? null)) $scheda->setTVValue($n, $v);
foreach ($servDopo as $rid => $v) $modx->getObject(modResource::class, $rid)->setTVValue('mapServiziStrutture', $v);
$modx->cacheManager->refresh();
echo "== Scritto. Cache svuotata. Backup e ripristino in $bk/ (--ripristina=$bk --scrivi)\n";
