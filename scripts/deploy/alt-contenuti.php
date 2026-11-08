<?php
/**
 * alt-contenuti.php — KOS-7 / AA-9: testo alternativo delle immagini scritte nel campo content delle risorse, da un
 * file di regole nel repository (es. kosgroup/dati/alt/regole-contenuti.json):
 *
 *   {"regole": [
 *     {"src": "/assets/img/medico-thumb.svg", "alt": ""},                          // tutte le pagine
 *     {"src": "/assets/img/img-faq.jpg", "alt": "Infermiera con un paziente", "id": 1234, "sostituisci_vuoto": true},
 *     {"src": "/assets/img/img-faq.jpg", "alt": "Nurse with a patient", "uri_prefisso": "en/", "sostituisci_vuoto": true}
 *   ]}
 *
 * Per ogni <img> del contenuto il cui src (senza host, slash iniziale e parametri) è quello della regola, e solo nella
 * risorsa "id" o nelle risorse il cui uri inizia con "uri_prefisso" (lingua: it/, en/) se indicati: se l'alt manca lo aggiunge; se è vuoto lo sostituisce solo con "sostituisci_vuoto"; un alt
 * già scritto non si tocca mai. Le regole più specifiche (con id) vincono su quelle generali.
 *
 * Uso (in SSH, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/alt-contenuti.php /www <regole.json>            # prova: elenca pagine e immagini
 *   php scripts/deploy/alt-contenuti.php /www <regole.json> --scrivi
 *   php scripts/deploy/alt-contenuti.php /www --ripristina=<file ripristino.json> [--scrivi]
 * Prima di scrivere: tabella <prefisso>site_content_bak_<AAAAMMGG_HHMM> (id, content) e ripristino.json in
 * ~/backup-kos-seo-geo/<data>-alt-contenuti/. Ripristino solo delle pagine non modificate dopo (salvo --forza).
 * Alla fine svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php alt-contenuti.php /www <regole.json> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-alt-contenuti';

/* ---------------------------------------------------------------- ripristino */
if (isset($opt['ripristina'])) {
    $rip = json_decode((string) file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($rip)) { fwrite(STDERR, "File di ripristino non leggibile\n"); exit(1); }
    $fatte = 0; $fermi = 0;
    foreach ($rip['pagine'] as $p) {
        $r = $modx->getObject(modResource::class, (int) $p['id']);
        if (!$r) { echo "MANCANTE  {$p['id']}\n"; continue; }
        if (sha1((string) $r->get('content')) !== $p['sha1_dopo'] && !isset($opt['forza'])) { echo "FERMO     {$p['id']} modificata dopo (--forza per rimettere comunque)\n"; $fermi++; continue; }
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " {$p['id']} {$r->get('uri')}\n";
        if ($scrivi) { $r->set('content', $p['prima']); $r->save(); $fatte++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo "== " . ($scrivi ? "$fatte ripristinate" : 'prova') . ", $fermi fermi\n";
    exit(0);
}

$file = $pos[1] ?? '';
$regole = is_file($file) ? (json_decode((string) file_get_contents($file), true)['regole'] ?? null) : null;
if (!is_array($regole) || !$regole) { fwrite(STDERR, "Regole non leggibili: $file\n"); exit(1); }
$norm = fn($s) => '/' . ltrim((string) preg_replace(['#^https?://[^/]+#i', '/[?#].*$/'], '', html_entity_decode((string) $s)), '/');
foreach ($regole as &$g) { $g['src'] = $norm($g['src']); $g['id'] = (int) ($g['id'] ?? 0); $g['uri_prefisso'] = (string) ($g['uri_prefisso'] ?? ''); }
unset($g);
$peso = fn($g) => ($g['id'] > 0 ? 2 : 0) + ($g['uri_prefisso'] !== '' ? 1 : 0);
usort($regole, fn($a, $b) => $peso($b) <=> $peso($a));   // prima le regole più specifiche

$trovate = [];   // src delle regole incontrati nei contenuti (anche se già a posto)
$converti = function (string $t, int $id, string $uri = '') use ($regole, $norm, &$trovate): array {
    $n = [];
    $nuovo = preg_replace_callback('~<img\b[^>]*>~i', function ($m) use ($regole, $norm, $id, $uri, &$n, &$trovate) {
        $tag = $m[0];
        if (!preg_match('~\bsrc\s*=\s*(["\'])(.*?)\1~is', $tag, $s)) return $tag;
        $src = $norm($s[2]);
        foreach ($regole as $g) {
            if ($g['src'] !== $src || ($g['id'] && $g['id'] !== $id)) continue;
            // «it/» vale anche per la home della lingua, il cui uri è «it» o «it/»
            if ($g['uri_prefisso'] !== '' && strpos(rtrim(ltrim($uri, '/'), '/') . '/', $g['uri_prefisso']) !== 0) continue;
            $trovate[$g['src'] . '|' . $g['uri_prefisso']] = true;
            $alt = htmlspecialchars((string) $g['alt'], ENT_QUOTES, 'UTF-8');
            if (!preg_match('~\balt\s*=\s*(["\'])(.*?)\1~is', $tag, $a)) {
                $n[] = "$src: alt aggiunto «{$g['alt']}»";
                return preg_replace('~^<img\b~i', '<img alt="' . $alt . '"', $tag);
            }
            if (trim($a[2]) === '' && !empty($g['sostituisci_vuoto']) && $g['alt'] !== '') {
                $n[] = "$src: alt vuoto sostituito «{$g['alt']}»";
                return str_replace($a[0], 'alt="' . $alt . '"', $tag);
            }
            return $tag;   // alt già scritto: non si tocca
        }
        return $tag;
    }, $t);
    return [$nuovo, $n];
};

$pagine = []; $tot = 0;
$q = $modx->newQuery(modResource::class, ['deleted' => 0, 'content:LIKE' => '%<img%']);
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    [$nuovo, $n] = $converti((string) $r->get('content'), (int) $r->get('id'), (string) $r->get('uri'));
    if (!$n || $nuovo === (string) $r->get('content')) continue;
    echo sprintf("%-5d %-60s %d immagini\n", $r->get('id'), $r->get('uri'), count($n));
    foreach (array_count_values($n) as $riga => $k) echo "        $k × $riga\n";
    $pagine[] = [$r, $nuovo]; $tot += count($n);
}
echo "== " . count($pagine) . " pagine, $tot immagini\n";
$assenti = array_filter($regole, fn($g) => !isset($trovate[$g['src'] . '|' . $g['uri_prefisso']]));
if ($assenti) echo "   Regole senza immagini nei contenuti (immagine in un template o chunk, o già tolta): " . count($assenti) . "\n"
    . implode('', array_map(fn($g) => "     {$g['uri_prefisso']}{$g['src']}\n", $assenti));
if (!$scrivi) { echo "Prova: nessuna scrittura. Rilanciare con --scrivi.\n"; exit(0); }
if (!$pagine) exit(0);

@mkdir($bk, 0700, true);
$pref = $modx->getOption('table_prefix', null, 'modx_');
$tab = $pref . 'site_content_bak_' . date('Ymd_Hi');
$ids = implode(',', array_map(fn($p) => (int) $p[0]->get('id'), $pagine));
$modx->exec("CREATE TABLE IF NOT EXISTS `$tab` AS SELECT id, content FROM `{$pref}site_content` WHERE id IN ($ids)");
$rip = ['creato' => date('c'), 'tabella' => $tab, 'pagine' => []];
foreach ($pagine as [$r, $nuovo]) {
    $prima = (string) $r->get('content');
    $r->set('content', $nuovo);
    if (!$r->save()) { echo "ERRORE salvataggio {$r->get('id')}\n"; continue; }
    $rip['pagine'][] = ['id' => (int) $r->get('id'), 'uri' => $r->get('uri'), 'prima' => $prima, 'sha1_dopo' => sha1($nuovo)];
}
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
$modx->cacheManager->refresh();
echo "== Scritte " . count($rip['pagine']) . " pagine, cache svuotata. Backup: tabella $tab e $bk/ripristino.json\n";
echo "   Ripristino: php scripts/deploy/alt-contenuti.php /www --ripristina=$bk/ripristino.json --scrivi\n";
