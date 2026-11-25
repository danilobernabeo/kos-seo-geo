<?php
/**
 * Clinica Dentale Villa dei Pini su kosgroup.com — scheda nuova sotto Centri (lavoro extra, fuori dal progetto SEO/GEO).
 *
 * Uso (in SSH sul cloud, dalla radice dell'archivio del repository):
 *   php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www                        prova: controlla e mostra, non scrive
 *   php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www --scrivi               applica (ripetibile: aggiorna)
 *   (immagini di testata, card, piè di pagina e foto dell'équipe: create se mancano, vedi config.json → immagini)
 *   ... --rifai-immagini              ricrea anche le immagini che esistono già (backup e ripristino), es. dopo un cambio
 *                                     di foto di partenza in config.json
 *   ... --senza-sede                  non tocca la scheda di Villa dei Pini (link da mettere dopo il 4/12)
 *   ... --prestazioni-stage           SOLO STAGE: procede anche se Odontoiatria non ha le prestazioni attese (tabelle
 *                                     kos_* dello stage ferme al 18/10, Odontoiatria aggiunta in TuoTempo il 29/10)
 *   ... --forza                       procede anche con le voci segnate FERMO (non in produzione con segnaposto)
 *   php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www --ripristina <cartella>/ripristino.json [--scrivi]
 *
 * Cosa fa (dati in config.json, testi in elementi/; nessun id fisso: tutto dall'URI):
 *   1. controlla che lo snippet della barra abbia &branca (pacchetto kosgroup/deploy/kos-barra-branca-20261125.json) e
 *      che Odontoiatria a Villa dei Pini abbia le prestazioni attese (2): altrimenti FERMO;
 *   2. crea (o aggiorna) la risorsa /it/centri/clinica-dentale-villa-dei-pini copiando campi, template, TV e contenuto
 *      della Clinica Dentale Villalba; nel contenuto sostituisce intestazione, contatti, pulsanti, La struttura,
 *      approfondimenti, équipe e Come raggiungerci con i frammenti di elementi/, aggiunge i trattamenti in Aree di
 *      intervento e la barra di prenotazione limitata a Odontoiatria; TV str.* e indirizzo da config.json, istanza e
 *      coordinate da Villa dei Pini, servizi (TV di mappatura) solo quelli di config.json;
 *   3. aggiunge l'id della scheda alla TV idStruttura delle news indicate (blocco «Le ultime news da …»);
 *   4. scheda di Villa dei Pini Civitanova: voce «Clinica Dentale» con il link, dopo «Diagnostica e Servizi Ambulatoriali»;
 *   5. landing /it/landing/clinica-dentale-civitanova: noindex, follow; via il JSON-LD scritto a mano; prenotazione
 *      online attiva verso la sezione #prenota-online della scheda (CD_CONFIG e href dei link);
 *   6. llms.txt (se c'è, cioè in produzione): riga della clinica dopo quella di Villalba;
 *   7. svuota la cache. Backup di tutto in ~/backup-kos-seo-geo/<data>-clinica-dentale/ con ripristino.json.
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$C = json_decode((string) file_get_contents("$DIR/config.json"), true);
if (!is_array($C)) { fwrite(STDERR, "config.json non leggibile\n"); exit(1); }

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$forza = in_array('--forza', $argv, true);
$fotoProvv = in_array('--foto-provvisorie', $argv, true);
$senzaSede = in_array('--senza-sede', $argv, true);
$prestStage = in_array('--prestazioni-stage', $argv, true);
$rifai = in_array('--rifai-immagini', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;

$errori = 0;
$ferma = function (string $msg) use (&$errori, $forza) {
    echo "  ! $msg" . ($forza ? " — procedo per --forza\n" : " — FERMO\n");
    if (!$forza) $errori++;
};
$corto = fn($s, $n = 110) => mb_strlen($s = str_replace(["\r", "\n"], ['', '\n'], (string) $s)) > $n ? mb_substr($s, 0, $n) . '…' : $s;

/* ============================================================ FUNZIONI PURE (provate con --test, senza MODX) */

/** Fine (offset dopo la chiusura) dell'elemento <$tag> che si apre in $pos, contando l'annidamento. */
function fineTag(string $c, int $pos, string $tag): ?int
{
    $prof = 0;
    $off = $pos;
    while (preg_match('#<' . $tag . '\b|</' . $tag . '\s*>#i', $c, $m, PREG_OFFSET_CAPTURE, $off)) {
        $prof += str_starts_with($m[0][0], '</') ? -1 : 1;
        $off = $m[0][1] + strlen($m[0][0]);
        if ($prof === 0) return $off;
    }
    return null;
}

/** Tutti gli elementi che si aprono con la regex $apertura: [[inizio, fine], ...] (non sovrapposti). */
function elementi(string $c, string $apertura, string $tag): array
{
    $out = [];
    $off = 0;
    while (preg_match('#' . $apertura . '#i', $c, $m, PREG_OFFSET_CAPTURE, $off)) {
        $ini = $m[0][1];
        $fine = fineTag($c, $ini, $tag);
        if ($fine === null) return [];
        $out[] = [$ini, $fine];
        $off = $fine;
    }
    return $out;
}

/** Tratto della testata: [inizio, fine] tra la fine dell'apertura di #intro-pagina-gallery e il blocco del titolo. */
function trattoTestata(string $p, array $T): ?array
{
    if (!preg_match('#' . $T['dopo'] . '#i', $p, $a, PREG_OFFSET_CAPTURE)) return null;
    $ini = $a[0][1] + strlen($a[0][0]);
    if (!preg_match('#' . $T['prima_di'] . '#i', $p, $b, PREG_OFFSET_CAPTURE, $ini)) return null;
    return [$ini, $b[0][1]];
}

$leggi = function (string $rel) use ($DIR): string {
    $p = "$DIR/$rel";
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return rtrim(str_replace("\r\n", "\n", (string) file_get_contents($p)), "\n");
};

/**
 * Scheda dal modello. $parti = ['content' => contenuto, 'tv:<nome>' => valore, ...]: le sezioni delle schede stanno
 * in parte nel contenuto e in parte nelle TV (contatti_struttura, approfondimenti, come_raggiungerci...), quindi ogni
 * sostituzione cerca l'elemento ovunque. Restituisce le parti modificate.
 */
$costruisci = function (array $parti, string $testata = '') use ($C, $leggi, $ferma, $corto): array {
    // Mappa: il blocco <div id="mappa-map"> del modello così com'è (iframe, chunk o segnaposto: nessuna chiave nel
    // repository), con il nome e l'alias della clinica sostituiti; va al posto di {{MAPPA_MODELLO}} in elementi/mappa.html
    $mappaModello = '';
    foreach ($parti as $k => $p) {
        if ($el = elementi($p, '<div\b[^>]*\bid="mappa-map"', 'div')) { $mappaModello = substr($p, $el[0][0], $el[0][1] - $el[0][0]); break; }
    }
    if ($mappaModello === '') $ferma('blocco della mappa (div#mappa-map) non trovato nel modello (contenuto e TV)');
    foreach ($C['sostituzioni_testo'] as [$da, $a]) $mappaModello = str_replace($da, $a, $mappaModello);
    foreach ($C['parole_del_modello'] as $w) if (stripos($mappaModello, $w) !== false) $ferma("mappa del modello: resta «{$w}»");
    echo '  mappa dal modello: ' . $corto(preg_replace(['#key=[\w-]{12,}#', '#\s+#'], ['key=…', ' '], $mappaModello), 220) . "\n";
    $frammenti = [];                                   // segnaposto => testo: il controllo dei residui li esclude
    $segna = function (string $testo) use (&$frammenti): string {
        $k = "\x01F" . count($frammenti) . "\x01";
        $frammenti[$k] = $testo;
        return $k;
    };
    $nome = fn($k) => $k === 'content' ? 'contenuto' : 'TV ' . substr($k, 3);
    // Testata a immagine unica (come Porto Potenza) al posto della galleria del modello: stesso tratto di pagina
    if ($testata !== '') {
        $t = trattoTestata($parti['content'], $C['testata']);
        if (!$t) $ferma('testata: tratto tra #intro-pagina-gallery e il titolo non trovato nel contenuto del modello');
        else {
            $vecchio = trim(substr($parti['content'], $t[0], $t[1] - $t[0]));
            $parti['content'] = substr($parti['content'], 0, $t[0]) . "\n" . $segna($testata) . "\n" . substr($parti['content'], $t[1]);
            echo "  = testata a immagine unica (da /{$C['testata']['modello_uri']}) al posto di: " . $corto(preg_replace('#\s+#', ' ', $vecchio), 160) . "\n";
        }
    }
    foreach ($C['sostituzioni'] as $s) {
        $nuovo = $leggi($s['file']);
        $dove = [];
        foreach ($parti as $k => $p) {
            $el = elementi($p, $s['apertura'], $s['tag']);
            if (!$el) continue;
            foreach (array_reverse($el) as [$i, $f]) $p = substr($p, 0, $i) . $segna($nuovo) . substr($p, $f);
            $parti[$k] = $p;
            $dove[] = $nome($k) . (count($el) > 1 ? ' (' . count($el) . ')' : '');
        }
        if (!$dove) $ferma("{$s['nome']}: elemento non trovato né nel contenuto né nelle TV del modello");
        else echo "  = {$s['nome']}: in " . implode(', ', $dove) . " ← {$s['file']}\n";
    }
    foreach ($C['aggiunte'] as $a) {
        $trovati = [];
        foreach ($parti as $k => $p) foreach (elementi($p, $a['dentro_fine'], $a['tag']) as $e) $trovati[] = [$k, $e];
        if (count($trovati) !== 1) { $ferma("{$a['nome']}: trovati " . count($trovati) . " elementi, atteso 1"); continue; }
        [$k, [, $f]] = $trovati[0];
        $p = $parti[$k];
        $chiusura = strrpos(substr($p, 0, $f), '</');
        $parti[$k] = substr($p, 0, $chiusura) . $segna($leggi($a['file'])) . "\n                                " . substr($p, $chiusura);
        echo "  + {$a['nome']}: in " . $nome($k) . " ← {$a['file']}\n";
    }
    if (preg_match('/barraPrenotazioneDBmedicoPrestazione/', implode("\n", $parti))) $ferma('il modello richiama già la barra');
    $fatto = false;
    foreach ($C['barra_prima_di'] as $re) {
        if (preg_match('#' . $re . '#i', $parti['content'], $m, PREG_OFFSET_CAPTURE)) {
            $parti['content'] = substr($parti['content'], 0, $m[0][1]) . $segna($C['barra']) . "\n\n            " . substr($parti['content'], $m[0][1]);
            echo "  + barra di prenotazione nel contenuto, prima di «{$m[0][0]}»: {$C['barra']}\n";
            $fatto = true;
            break;
        }
    }
    if (!$fatto) $ferma('posto della barra di prenotazione non trovato nel contenuto');
    foreach ($parti as $k => $p) foreach ($C['sostituzioni_testo'] as [$da, $a]) {
        $n = substr_count($p, $da);
        if ($n) { $p = str_replace($da, $a, $p); echo "  ~ " . $nome($k) . ": «{$da}» → «{$a}» ($n)\n"; }
        $parti[$k] = $p;
    }
    // Residui del modello (fuori dai frammenti nuovi): nomi, indirizzo, telefono, persone, modulo di Villalba
    foreach ($parti as $k => $p) foreach ($C['parole_del_modello'] as $w) {
        if (preg_match_all('#.{0,50}' . preg_quote($w, '#') . '.{0,50}#isu', $p, $mm)) {
            foreach ($mm[0] as $x) $ferma($nome($k) . ": resta «{$w}»: …" . $corto(trim($x)) . '…');
        }
    }
    foreach ($parti as $k => $p) $parti[$k] = str_replace('{{MAPPA_MODELLO}}', $mappaModello, strtr($p, $frammenti));
    $tutto = implode("\n", $parti);
    // Chiamate non in cache: ammessa solo la barra (cache interna dello snippet, registro del 22/11)
    preg_match_all('/\[\[!\s*([\w.\-$]+)/', $tutto, $nc);
    foreach ($nc[1] as $n) if ($n !== 'barraPrenotazioneDBmedicoPrestazione') $ferma("chiamata non in cache [[!$n…]]");
    preg_match_all('/\[\[[^\[\]]{0,80}/', $parti['content'], $tag);
    echo '  chiamate MODX nel contenuto: ' . (implode(' | ', array_map(fn($t) => $corto($t, 60), array_unique($tag[0]))) ?: 'nessuna') . "\n";
    if (preg_match_all('/(?:href|src)="(?!\/|#|https?:|tel:|mailto:|data:|javascript:|\[\[)([^"]*)"/', $tutto, $rel)) {
        foreach (array_unique($rel[1]) as $h) echo "  i indirizzo relativo (dal modello, fuori da questo lavoro): $h\n";
    }
    if (preg_match_all('#href="//[^"]*"#', $tutto, $rel)) foreach (array_unique($rel[0]) as $h) echo "  i link senza schema (dal modello, fuori da questo lavoro): $h\n";
    return $parti;
};

/** Modifiche della landing su contenuto e template: [testi modificati, parti toccate]. */
$pianoLanding = function (array $dove, int $usiTpl, string $nomeTpl) use ($C, $ferma, $corto): array {
    $url = $C['landing']['url_prenota'];
    $reLd = '#[ \t]*<script type="application/ld\+json">(?:(?!</script>).)*?"MedicalClinic"(?:(?!</script>).)*</script>[ \t]*\r?\n?#s';
    $operazioni = [
        'JSON-LD scritto a mano' => fn($s) => preg_match_all($reLd, $s) === 1 ? preg_replace($reLd, '', $s) : null,
        'noindex, follow' => function ($s) use ($C) {
            if (preg_match('#<meta\s+name="robots"#i', $s)) return null;
            if (!preg_match('#<meta\b[^>]*\bname="viewport"[^>]*>#i', $s, $m, PREG_OFFSET_CAPTURE)) return null;
            $p = $m[0][1] + strlen($m[0][0]);
            return substr($s, 0, $p) . "\n" . $C['landing']['robots'] . substr($s, $p);
        },
        'prenotazione online attiva (CD_CONFIG)' => function ($s) use ($url) {
            $n = 0;
            $r = preg_replace('#"prenotaOnline"\s*:\s*\{[^{}]*\}#', '"prenotaOnline": {"attivo": true, "url": "' . $url . '"}', $s, -1, $n);
            return $n === 1 && $r !== $s ? $r : null;
        },
        'href dei link «Prenota online»' => function ($s) use ($url) {
            $n = 0;
            $r = preg_replace_callback('#<a\b[^>]*>#i', function ($m) use ($url, &$n) {
                if (!preg_match('#\bcd-prenota\b|data-cd-prenota-link#', $m[0]) || !str_contains($m[0], 'href="#"')) return $m[0];
                $n++;
                return str_replace('href="#"', 'href="' . $url . '"', $m[0]);
            }, $s);
            return $n ? $r : null;
        },
    ];
    $piano = [];
    foreach ($operazioni as $nome => $f) {
        $fatta = false;
        foreach ($dove as $d => $s) {
            $r = $f($s);
            if ($r === null) continue;
            if ($d === 'template' && $usiTpl > 1) { $ferma("landing, $nome: va fatta nel template $nomeTpl, usato da $usiTpl risorse"); $fatta = true; break; }
            $dove[$d] = $r;
            $piano[$d] = true;
            echo "  ~ $nome ($d)\n";
            $fatta = true;
            break;
        }
        if ($fatta) continue;
        $tutto = implode("\n", $dove);
        if ($nome === 'noindex, follow' && preg_match('#<meta\s+name="robots"[^>]*>#i', $tutto, $m)) echo "  = $nome: già presente «{$m[0]}»\n";
        elseif ($nome === 'JSON-LD scritto a mano' && !str_contains($tutto, '"MedicalClinic"')) echo "  = $nome: già tolto\n";
        elseif ($nome === 'prenotazione online attiva (CD_CONFIG)' && str_contains($tutto, '"attivo": true, "url": "' . $url . '"')) echo "  = $nome: già attiva\n";
        elseif ($nome === 'href dei link «Prenota online»' && str_contains($tutto, 'href="' . $url . '"')) echo "  = $nome: già fatto\n";
        else $ferma("landing, $nome: punto non trovato né nel contenuto né nel template");
    }
    $restanti = preg_match_all('#<a\b[^>]*href="\#"[^>]*>#', implode("\n", $dove), $mm);
    if ($restanti) echo "  i link con href=\"#\" rimasti: $restanti (" . implode(' ', array_map(fn($x) => $corto($x, 60), $mm[0])) . ")\n";
    return [$dove, $piano];
};

/* ---------- --test <contenuto modello> [<landing>]: prova le funzioni pure su file locali, senza MODX ---------- */
if (($argv[1] ?? '') === '--test') {
    $mod = (string) @file_get_contents($argv[2] ?? '');
    if ($mod === '') { fwrite(STDERR, "Uso: php installa.php --test <contenuto-modello.html> [<landing.html>]\n"); exit(1); }
    echo "-- Contenuto da {$argv[2]}\n";
    // file .json: {"content": ..., "tv:<nome>": ...} (contenuto e TV del modello, es. da ispeziona.php); altrimenti solo il contenuto
    $parti = str_ends_with($argv[2], '.json') ? json_decode($mod, true) : ['content' => $mod];
    $testata = (string) ($parti['testata'] ?? '');
    unset($parti['testata']);
    $parti = $costruisci($parti, $testata);
    foreach ($parti as $k => $p) if ($k !== 'content') echo "  $k: " . $corto($p, 140) . "\n";
    $c = $parti['content'];
    $out = sys_get_temp_dir() . '/clinica-dentale-contenuto.html';
    file_put_contents($out, $c);
    echo "  scritto $out (" . strlen($c) . " byte)\n";
    if (!empty($argv[3])) {
        echo "\n-- Landing da {$argv[3]}\n";
        [$d] = $pianoLanding(['contenuto' => (string) file_get_contents($argv[3])], 1, '-');
        file_put_contents(sys_get_temp_dir() . '/clinica-dentale-landing.html', $d['contenuto']);
        echo "  scritto " . sys_get_temp_dir() . "/clinica-dentale-landing.html\n";
    }
    echo $errori ? "\n== $errori problemi\n" : "\n== Nessun problema\n";
    exit($errori ? 2 : 0);
}

if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] [--senza-sede] [--rifai-immagini] [--prestazioni-stage] [--forza] | --ripristina <file> [--scrivi] | --test <modello.html> [<landing.html>]\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modTemplateVar, modTemplateVarResource, modSnippet, modResourceGroupResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

$siteUrl = (string) $modx->getOption('site_url');
// Stage o produzione dall'utente del cloud (in CLI site_url vale http://127.0.0.1/); sconosciuto = produzione
$cloud = basename(rtrim((string) (getenv('HOME') ?: ''), '/'));
$STAGE = in_array($cloud, $C['cloud_stage'], true)
    || (!in_array($cloud, $C['cloud_produzione'], true) && (str_contains($siteUrl, 'stage.') || str_contains($siteUrl, '.modx.dev')));
$tvValore = function (modResource $r, string $nome) use ($modx): string {
    $tv = $modx->getObject(modTemplateVar::class, ['name' => $nome]);
    if (!$tv) return '';
    $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id'), 'contentid' => $r->get('id')]);
    return $v ? (string) $v->get('value') : '';
};

/* ============================================================ RIPRISTINO */
if ($fileRip !== null) {
    $R = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($R)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    if (!empty($R['creata'])) {
        $o = $modx->getObject(modResource::class, (int) $R['creata']);
        echo "  - risorsa creata {$R['creata']}" . ($o ? " /{$o->get('uri')}" : ' (già assente)') . " (con le sue TV)\n";
        if ($scrivi && $o) $o->remove();
    }
    foreach ($R['risorse'] ?? [] as $x) {
        $o = $modx->getObject(modResource::class, (int) $x['id']);
        if (!$o) { echo "  ! risorsa {$x['id']} non trovata\n"; continue; }
        echo "  ~ risorsa {$x['id']} /{$o->get('uri')}: " . implode(', ', array_merge(isset($x['content']) ? ['contenuto'] : [],
            array_keys($x['campi'] ?? []), array_map(fn($n) => "TV $n", array_keys($x['tv'] ?? [])))) . "\n";
        if (!$scrivi) continue;
        if (isset($x['content'])) $o->set('content', (string) file_get_contents($x['content']));
        foreach ($x['campi'] ?? [] as $k => $v) $o->set($k, $v);
        $o->save();
        foreach ($x['tv'] ?? [] as $n => $v) $o->setTVValue($n, $v);
    }
    foreach ($R['template'] ?? [] as $x) {
        $t = $modx->getObject(modTemplate::class, (int) $x['id']);
        echo "  ~ template {$x['id']}" . ($t ? " {$t->get('templatename')}" : ' (assente)') . ": contenuto originale\n";
        if ($scrivi && $t) { $t->set('content', (string) file_get_contents($x['file'])); $t->save(); }
    }
    foreach ($R['file_modificati'] ?? [] as $x) {
        echo "  ~ /{$x['rel']}: versione originale\n";
        if ($scrivi) copy($x['backup'], "$webroot/{$x['rel']}");
    }
    foreach (array_reverse($R['file_creati'] ?? []) as $rel) {
        echo "  - /$rel\n";
        if ($scrivi) @unlink("$webroot/$rel");
    }
    foreach (array_reverse($R['cartelle_create'] ?? []) as $rel) {
        if ($scrivi) @rmdir("$webroot/$rel");
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata. Ripristino completato.\n"; }
    exit(0);
}

/* ============================================================ 1. RISORSE DI RIFERIMENTO */
echo ($scrivi ? "== INSTALLAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — Clinica Dentale Villa dei Pini su $siteUrl"
   . " (cloud $cloud: " . ($STAGE ? 'stage' : 'PRODUZIONE') . ")\n";
$perUri = fn(string $u) => $modx->getObject(modResource::class, ['uri' => $u, 'deleted' => 0]);
$modello = $perUri($C['modello_uri']);
$sede = $perUri($C['sede_uri']);
$landing = $perUri($C['landing_uri']);
$elenco = $perUri($C['elenco_uri']);
if (!$modello) $ferma("modello /{$C['modello_uri']} non trovato");
if (!$sede) $ferma("Villa dei Pini /{$C['sede_uri']} non trovata");
if (!$modello || !$sede) exit(2);
$tplModello = $modx->getObject(modTemplate::class, $modello->get('template'));
printf("  modello  %d /%s (template %s, genitore %d, pubblicata %d, menu %s, ricercabile %d)\n", $modello->get('id'),
    $modello->get('uri'), $tplModello ? $tplModello->get('templatename') : '-', $modello->get('parent'), $modello->get('published'),
    $modello->get('hidemenu') ? 'nascosta' : 'visibile', $modello->get('searchable'));
printf("  sede     %d /%s\n", $sede->get('id'), $sede->get('uri'));
echo '  landing  ' . ($landing ? "{$landing->get('id')} /{$landing->get('uri')}" : "/{$C['landing_uri']} assente: salto la landing") . "\n";
echo '  elenco   ' . ($elenco ? "{$elenco->get('id')} /{$elenco->get('uri')} (pdoMenu su tipo_struttura: la scheda entra copiando la TV del modello)" : 'assente') . "\n";

/* ============================================================ 2. BARRA DI PRENOTAZIONE */
echo "\n-- Barra di prenotazione\n";
$sn = $modx->getObject(modSnippet::class, ['name' => 'barraPrenotazioneDBmedicoPrestazione']);
if (!$sn || !str_contains((string) $sn->get('snippet'), '$brancaFiltro')) {
    $ferma("lo snippet barraPrenotazioneDBmedicoPrestazione installato non ha &branca: installare prima kosgroup/deploy/kos-barra-branca-20261125.json");
} else echo "  snippet con &branca: sì\n";
$istanza = $tvValore($sede, 'istanza');
if ($istanza === '') $ferma('Villa dei Pini non ha la TV istanza');
else {
    $st = $modx->prepare("SELECT p.activity_id, p.title, p.typology FROM kos_prestazioni p
        WHERE p.areaid = ? AND LOWER(p.typology) = LOWER(?) AND (p.is_hidden = 0 OR p.mop_bookability = 2)
          AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)
        ORDER BY p.title");
    $st->execute([$istanza, $C['barra_branca']]);
    $prest = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "  istanza di Villa dei Pini: $istanza; prestazioni prenotabili di {$C['barra_branca']}: " . count($prest) . "\n";
    foreach ($prest as $p) echo "     {$p['activity_id']}  {$p['title']}  ({$p['typology']})\n";
    if (count($prest) !== (int) $C['barra_prestazioni_attese'] && $prestStage && $STAGE) {
        echo "  i " . count($prest) . " prestazioni invece di {$C['barra_prestazioni_attese']}: accettato per --prestazioni-stage (dati TuoTempo dello stage vecchi; la barra mostrerà «Nessuna prestazione disponibile»)\n";
    } elseif (count($prest) !== (int) $C['barra_prestazioni_attese']) {
        if ($prestStage) echo "  ! --prestazioni-stage vale solo sullo stage\n";
        $ferma(count($prest) . " prestazioni invece di {$C['barra_prestazioni_attese']}: decidere con Danilo prima di installare (filtro per prestazione?)");
    }
}

/* ============================================================ 3. CONTENUTO E TV DELLA SCHEDA */
// TV del modello da copiare (meno le escluse in config.json): passano dalle stesse sostituzioni del contenuto
$tvModello = []; $tvEscluse = [];
foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $modello->get('id')]) as $tvr) {
    $tv = $modx->getObject(modTemplateVar::class, $tvr->get('tmplvarid'));
    if (!$tv) continue;
    $n = $tv->get('name');
    foreach ($C['tv_non_copiare'] as $re) if (preg_match('#' . $re . '#', $n)) { $tvEscluse[] = $n; continue 2; }
    if (array_key_exists($n, $C['tv'])) continue;          // la scrive config.json
    $tvModello[$n] = str_replace("\r\n", "\n", (string) $tvr->get('value'));
}
echo "\n-- Contenuto e TV (dal modello {$modello->get('id')}: contenuto " . strlen((string) $modello->get('content')) . " byte, "
   . count($tvModello) . " TV da copiare; escluse: " . (implode(', ', $tvEscluse) ?: 'nessuna') . ")\n";
$parti = ['content' => str_replace("\r\n", "\n", (string) $modello->get('content'))];
foreach ($tvModello as $n => $v) $parti["tv:$n"] = $v;
$testata = ''; $tvTestata = [];
$pp = $perUri($C['testata']['modello_uri']);
if (!$pp) $ferma("testata: /{$C['testata']['modello_uri']} non trovata");
else {
    $ppParti = ['content' => (string) $pp->get('content')];
    foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $pp->get('id')]) as $tvr) {
        $tv = $modx->getObject(modTemplateVar::class, $tvr->get('tmplvarid'));
        if ($tv) $ppParti['tv:' . $tv->get('name')] = (string) $tvr->get('value');
    }
    $t = trattoTestata(str_replace("\r\n", "\n", $ppParti['content']), $C['testata']);
    if ($t) $testata = trim(substr(str_replace("\r\n", "\n", $ppParti['content']), $t[0], $t[1] - $t[0]));
    if ($testata === '') $ferma("testata: tratto tra #intro-pagina-gallery e il titolo non trovato nel contenuto di /{$C['testata']['modello_uri']}");
    else {
        echo "\n  testata di /{$C['testata']['modello_uri']} nel database: " . $corto(preg_replace('#\s+#', ' ', $testata), 300) . "\n";
        // TV richiamate dalla testata (es. immagine di testata): valori di Porto Potenza con alias e nome della scheda nuova
        preg_match_all('/\[\[\*([\w.\-]+)/', $testata, $rif);
        foreach (array_unique($rif[1]) as $n) {
            if (in_array($n, ['alias', 'pagetitle', 'longtitle', 'id', 'uri', 'introtext', 'description'], true)) continue;
            $v = str_replace("\r\n", "\n", (string) ($ppParti["tv:$n"] ?? ''));
            foreach ($C['testata']['sostituzioni_testo'] as [$da, $a]) $v = str_replace($da, $a, $v);
            $tvTestata[$n] = $v;
            echo "  TV $n della testata: «" . $corto($v, 120) . "» (da Porto Potenza)\n";
            if (preg_match('/Porto Potenza|porto-potenza|Santo Stefano/i', $v, $mm)) $ferma("testata, TV $n: resta «{$mm[0]}»");
        }
        // Chunk della testata (imgSingola: alt = titolo della pagina, comune a tutte le schede): il suo markup si copia nella
        // scheda, così l'alt descrittivo vale solo qui e il chunk comune non si tocca
        foreach ($C['testata']['chunk_da_copiare'] ?? [] as $nc) {
            $tag = "[[\$$nc]]";
            if (!str_contains($testata, $tag)) continue;
            $ch = $modx->getObject(modChunk::class, ['name' => $nc]);
            if (!$ch) { $ferma("testata: chunk $nc non trovato"); continue; }
            $testata = str_replace($tag, trim(str_replace("\r\n", "\n", (string) $ch->get('snippet'))), $testata);
            echo "  testata: chunk $nc copiato nella scheda (alt descrittivo al posto del titolo della pagina)\n";
        }
        if (preg_match_all('/\[\[\$([\w.\-]+)/', $testata, $chRest)) echo '  i chunk rimasti nella testata: ' . implode(', ', array_unique($chRest[1])) . "\n";
        foreach ($C['testata']['sostituzioni_testo'] as [$da, $a]) $testata = str_replace($da, $a, $testata);
        $testata = preg_replace('/\balt="[^"]*"/', 'alt="' . htmlspecialchars($C['testata']['alt'], ENT_QUOTES) . '"', $testata);
        if (preg_match('/Porto Potenza|porto-potenza|Santo Stefano/i', $testata, $mm)) $ferma("testata: resta «{$mm[0]}»");        echo "  testata per la scheda nuova: " . $corto(preg_replace('#\s+#', ' ', $testata), 300) . "\n";
    }
}
$parti = $costruisci($parti, $testata);
$c = $parti['content'];
foreach ($tvEscluse as $n) if (preg_match('/\[\[\*' . preg_quote($n, '/') . '\b/', implode("\n", $parti))) $ferma("la TV esclusa $n è richiamata nel contenuto o in una TV: la pagina la mostrerebbe vuota");/* {{uri:…}} nei frammenti: id della risorsa (link [[~id]], registro del 25/11); la scheda nuova si risolve dopo la creazione */
$risolvi = function (string $s, ?int $idNuova = null) use ($perUri, $ferma, $C): string {
    return preg_replace_callback('/\{\{uri:([^}]+)\}\}/', function ($m) use ($perUri, $ferma, $idNuova, $C) {
        if ($m[1] === "it/centri/{$C['campi']['alias']}" && $idNuova !== null) return (string) $idNuova;
        if ($r = $perUri($m[1])) return (string) $r->get('id');
        if ($idNuova === null && $m[1] === "it/centri/{$C['campi']['alias']}") return $m[0];
        $ferma("link a /{$m[1]}: risorsa non trovata");
        return $m[0];
    }, $s);
};
foreach ($parti as $k => $p) $parti[$k] = $risolvi($p);
$c = $parti['content'];
preg_match_all('/\[\[~(\d+)\]\]/', $c, $ln);
echo '  link [[~id]] nel contenuto: ' . implode(', ', array_map(fn($i) => $i . ' /' . (($r = $modx->getObject(modResource::class, (int) $i)) ? $r->get('uri') : '?'), array_unique($ln[1]))) . "\n";
$fileProva = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/clinica-dentale-contenuto.html';
file_put_contents($fileProva, implode("\n\n", array_map(fn($k, $p) => "<!-- ===== $k ===== -->\n$p", array_keys($parti), $parti)));
echo "  contenuto costruito in $fileProva (" . strlen($c) . " byte)\n";
$segnaposto = preg_match_all('/\[DA COMPLETARE[^\]]*\]/u', implode("\n", $parti), $sp);
if ($segnaposto) {
    echo "  i segnaposto da completare: $segnaposto\n";
    foreach ($sp[0] as $x) echo "     $x\n";
    if (!$STAGE) { echo "  ! in produzione non si installa una scheda con segnaposto (nemmeno con --forza) — FERMO\n"; $errori++; }
}

/* ============================================================ 4. CAMPI E TV */
echo "\n-- Campi e TV\n";
$esistente = $modx->getObject(modResource::class, ['parent' => $modello->get('parent'), 'alias' => $C['campi']['alias'], 'deleted' => 0]);
$altro = $modx->getObject(modResource::class, ['alias' => $C['campi']['alias'], 'deleted' => 0, 'context_key' => $modello->get('context_key')]);
if ($altro && (!$esistente || $altro->get('id') !== $esistente->get('id'))) $ferma("alias {$C['campi']['alias']} già usato da {$altro->get('id')} /{$altro->get('uri')}");
echo $esistente ? "  la scheda esiste già (id {$esistente->get('id')}): AGGIORNAMENTO, con backup dello stato attuale\n" : "  scheda nuova\n";

$campi = $modello->toArray('', true);
foreach (['id', 'uri', 'createdon', 'createdby', 'editedon', 'editedby', 'publishedon', 'publishedby', 'deleted',
          'deletedon', 'deletedby', 'pub_date', 'unpub_date'] as $k) unset($campi[$k]);
$campi['uri_override'] = 0;
$campi = array_merge($campi, $C['campi'], ['content' => $c]);
if (!$esistente) {
    $campi['menuindex'] = (int) $modello->get('menuindex') + 1;
} else {
    $campi['menuindex'] = (int) $esistente->get('menuindex');
}
foreach (['pagetitle', 'longtitle', 'description', 'introtext', 'alias'] as $k) printf("  %-12s %s\n", $k, $corto($campi[$k], 170));
printf("  %-12s published %d, searchable %d, hidemenu %d, cacheable %d, richtext %d, menuindex %d, template %d\n", 'impostazioni',
    $campi['published'], $campi['searchable'], $campi['hidemenu'], $campi['cacheable'], $campi['richtext'], $campi['menuindex'], $campi['template']);
$lt = mb_strlen($campi['longtitle']); $ld = mb_strlen($campi['description']);
if ($lt > 65 || substr_count($campi['longtitle'], '| KOS') !== 1) $ferma("title di $lt caratteri o senza « | KOS» una volta");
if ($ld > 160) $ferma("description di $ld caratteri");
echo "  title $lt caratteri, description $ld\n";

// TV: quelle del modello (trasformate come il contenuto); poi config.json, poi quelle prese da Villa dei Pini, poi i servizi
$tvNuove = [];
foreach ($tvModello as $n => $v) $tvNuove[$n] = [$parti["tv:$n"] === $v ? 'modello' : 'modello, modificata', $parti["tv:$n"]];
foreach ($tvTestata as $n => $v) $tvNuove[$n] = ['Porto Potenza', $v];
foreach ($C['tv'] as $n => $v) $tvNuove[$n] = ['config', (string) $v];
foreach ($C['tv_dalla_sede'] as $n) $tvNuove[$n] = ['Villa dei Pini', $tvValore($sede, $n)];
$csv = fn($s) => array_values(array_filter(array_map('trim', explode(',', (string) $s)), 'strlen'));
$idServizi = [];
foreach ($C['servizi_uri'] as $u) {
    $s = $perUri($u);
    if (!$s) $ferma("servizio /$u non trovato"); else $idServizi[(int) $s->get('id')] = $u;
}
$tvMappa = $csv($modx->getOption('kos.struttura_tv_servizi'));
$messi = [];
foreach ($tvMappa as $n) {
    if (!isset($tvNuove[$n]) || $tvNuove[$n][1] === '') continue;
    $ids = array_filter(array_map('intval', preg_split('/[^\d]+/', $tvNuove[$n][1])));
    $tieni = array_values(array_filter($ids, fn($i) => isset($idServizi[$i])));
    $tvNuove[$n] = ['servizi', implode(',', $tieni)];
    foreach ($tieni as $i) $messi[$i] = true;
}
foreach ($idServizi as $i => $u) if (!isset($messi[$i])) $ferma("servizio /$u non è nelle TV di mappatura del modello (" . implode(',', $tvMappa) . ')');
ksort($tvNuove);
foreach ($tvNuove as $n => [$da, $v]) {
    printf("  TV %-22s %-21s %s\n", $n, "($da)", $corto($v, 90));
}
foreach (['str.lat', 'str.lng', 'istanza'] as $n) if (($tvNuove[$n][1] ?? '') === '') $ferma("TV $n vuota");
$tvEsiste = fn(string $n) => (bool) $modx->getCount(modTemplateVar::class, ['name' => $n]);
foreach ($tvNuove as $n => [, $v]) if ($v !== '' && !$tvEsiste($n)) $ferma("TV $n inesistente sul sito");
foreach ($tvNuove as $n => [, $v]) {
    if ($v === '' || !($tvo = $modx->getObject(modTemplateVar::class, ['name' => $n]))) continue;
    if (!$modx->getCount(modTemplateVarTemplate::class, ['tmplvarid' => $tvo->get('id'), 'templateid' => (int) $campi['template']])) {
        echo "  i TV $n non assegnata al template {$campi['template']}: il valore si scrive comunque, ma non compare nel pannello\n";
    }
}

/* ============================================================ 5. NEWS */
echo "\n-- News («Le ultime news da …», TV idStruttura)\n";
$news = [];
foreach ($C['news_uri'] as $u) {
    $r = $perUri($u);
    if (!$r) { echo "  i /$u assente: salto\n"; continue; }
    $v = $tvValore($r, 'idStruttura');
    $news[] = [$r, $v];
    echo "  {$r->get('id')} /$u: idStruttura «{$v}»" . ($esistente && in_array((string) $esistente->get('id'), array_map('trim', explode(',', $v)), true) ? " (id della scheda già presente)\n" : " + id della scheda\n");
}

/* ============================================================ 6. VILLA DEI PINI */
echo "\n-- Villa dei Pini Civitanova ({$sede->get('id')}): voce «Clinica Dentale»\n";
$sedeParti = ['content' => (string) $sede->get('content')];
foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $sede->get('id')]) as $tvr) {
    $tv = $modx->getObject(modTemplateVar::class, $tvr->get('tmplvarid'));
    if ($tv) $sedeParti['tv:' . $tv->get('name')] = (string) $tvr->get('value');
}
$sedeNuovo = null;
$cercata = $C['sede_voce']['dopo_voce_con'];
$conVoce = array_keys(array_filter($sedeParti, fn($p) => str_contains($p, $cercata)));
if ($senzaSede) echo "  saltata per --senza-sede (la scheda di Villa dei Pini non si tocca)\n";
elseif ($conGia = array_keys(array_filter($sedeParti, fn($p) => str_contains($p, 'faqClinicaDentale')))) {
    // Voce già presente: si confronta con elementi/ (link risolto con l'id della scheda esistente) e si sostituisce se diversa
    $k = $conGia[0];
    $ps = str_replace("\r\n", "\n", $sedeParti[$k]);
    $ini = strrpos(substr($ps, 0, strpos($ps, 'faqClinicaDentale')), '<div class="faq-singola"');
    $fine = $ini === false ? null : fineTag($ps, $ini, 'div');
    $voce = trim($leggi($C['sede_voce']['file']));
    if (count($conGia) !== 1 || $fine === null) $ferma('voce «Clinica Dentale» già presente ma non riconosciuta (riquadro faq-singola)');
    elseif (!$esistente) $ferma('voce «Clinica Dentale» presente su Villa dei Pini ma la scheda non esiste: ripristinare prima la voce');
    elseif (substr($ps, $ini, $fine - $ini) === $risolvi($voce, (int) $esistente->get('id'))) echo "  già presente e uguale: invariata\n";
    else {
        $sedeParti[$k] = $ps;
        $sedeNuovo = [$k, $fine, $voce, $ini];
        echo "  ~ già presente in " . ($k === 'content' ? 'contenuto' : 'TV ' . substr($k, 3)) . ": testo aggiornato da {$C['sede_voce']['file']}\n";
    }
}
elseif (count($conVoce) !== 1 || substr_count($sedeParti[$conVoce[0]], $cercata) !== 1) {
    $ferma("voce «{$cercata}» trovata in " . (implode(', ', $conVoce) ?: 'nessuna parte') . ": attesa una volta sola");
} else {
    $k = $conVoce[0];
    $ps = $sedeParti[$k];
    $ini = strrpos(substr($ps, 0, strpos($ps, $cercata)), '<div class="faq-singola"');
    $fine = $ini === false ? null : fineTag($ps, $ini, 'div');
    if ($fine === null) $ferma("voce «{$cercata}»: riquadro faq-singola non riconosciuto");
    else {
        $sedeNuovo = [$k, $fine, $leggi($C['sede_voce']['file'])];
        echo "  + in " . ($k === 'content' ? 'contenuto' : 'TV ' . substr($k, 3)) . ", dopo la voce «" . strip_tags($cercata) . "»\n";
    }
}

/* ============================================================ 7. LANDING */
$landingPiano = []; $dove = []; $tl = null;
if ($landing) {
    echo "\n-- Landing {$landing->get('id')} /{$landing->get('uri')}\n";
    $tl = $modx->getObject(modTemplate::class, $landing->get('template'));
    $usiTpl = $tl ? $modx->getCount(modResource::class, ['template' => $tl->get('id'), 'deleted' => 0]) : 0;
    echo '  template ' . ($tl ? "{$tl->get('id')} {$tl->get('templatename')} (usato da $usiTpl risorse)" : 'vuoto') . "\n";
    $dove = ['contenuto' => (string) $landing->get('content')];
    if ($tl) $dove['template'] = (string) $tl->get('content');
    [$dove, $landingPiano] = $pianoLanding($dove, (int) $usiTpl, $tl ? (string) $tl->get('templatename') : '-');
}

/* ============================================================ 8. LLMS.TXT */
$llmsNuovo = null;
if (is_file("$webroot/llms.txt")) {
    $l = (string) file_get_contents("$webroot/llms.txt");
    if (str_contains($l, $C['llms']['riga'])) echo "\n-- llms.txt: riga già presente\n";
    else {
        $righe = explode("\n", $l);
        $dopo = null;
        foreach ($righe as $i => $r) if (str_contains($r, $C['llms']['dopo_riga_con'])) $dopo = $i;
        if ($dopo === null) $ferma('llms.txt: riga di Villalba non trovata');
        else { array_splice($righe, $dopo + 1, 0, [$C['llms']['riga']]); $llmsNuovo = implode("\n", $righe); echo "\n-- llms.txt: + {$C['llms']['riga']}\n"; }
    }
} else echo "\n-- llms.txt assente (stage): niente da fare\n";

/* ============================================================ 9. IMMAGINI */
// Testata (come Porto Potenza), card dell'elenco e piè di pagina: dalla foto reale di Villa dei Pini (galleria della 218),
// alle dimensioni delle immagini corrispondenti indicate in «come»; senza «come» la foto si copia alle sue dimensioni
// (foto di gruppo dell'équipe). Si creano se mancano; con --rifai-immagini si ricreano anche se ci sono (con backup).
echo "\n-- Immagini (config.json → immagini)\n";
$foto = []; $previste = [];
if (!class_exists('Imagick') && !function_exists('imagecreatefromjpeg')) $ferma('né Imagick né GD disponibili');
foreach ($C['immagini'] as $v) {
    if (is_file("$webroot/{$v['a']}") && !$rifai) { echo "  = /{$v['a']} esiste già (" . filesize("$webroot/{$v['a']}") . " byte): non la tocco (--rifai-immagini per ricrearla)\n"; continue; }
    if (!is_file("$webroot/{$v['da']}")) { $ferma("/{$v['da']} assente"); continue; }
    $d = @getimagesize(isset($v['come']) ? "$webroot/{$v['come']}" : "$webroot/{$v['da']}");
    if (!$d) { $ferma('/' . ($v['come'] ?? $v['da']) . " assente o non leggibile: dimensioni di /{$v['a']} sconosciute"); continue; }
    [$w, $h] = $d;
    $foto[] = [$v['da'], $v['a'], (int) $w, (int) $h];
    $previste[$v['a']] = true;
    echo '  ' . (is_file("$webroot/{$v['a']}") ? '~' : '+') . " /{$v['a']} ({$w}×{$h}, " . (isset($v['come']) ? "come /{$v['come']}" : 'copia') . ") da /{$v['da']}\n";
}
// Nessuna immagine rotta: card dell'elenco delle cliniche e immagini della scheda nel contenuto e nelle TV
$servono = ["assets/img/strutture/{$C['campi']['alias']}.jpg" => 'card di /' . $C['elenco_uri']];
$tuttoScheda = implode("\n", $parti) . "\n" . implode("\n", array_column($tvNuove, 1));
if (preg_match_all('#assets/img/[\w/.-]*' . preg_quote($C['campi']['alias'], '#') . '[\w/.-]*\.(?:jpe?g|png|webp)#', $tuttoScheda, $im)) {
    foreach (array_unique($im[0]) as $p) $servono[$p] = 'immagine della scheda';
}
if (str_contains($tuttoScheda, '/header/[[*alias]]')) {
    $servono["assets/img/header/{$C['campi']['alias']}.jpg"] = 'testata';
    $servono["assets/img/header/{$C['campi']['alias']}-mobile.jpg"] = 'testata, telefono';
}
foreach ($servono as $p => $cosa) {
    $ok = is_file("$webroot/$p") || isset($previste[$p]);
    echo '  ' . ($ok ? '=' : '!') . " /$p ($cosa): " . (is_file("$webroot/$p") ? 'c\'è' : (isset($previste[$p]) ? 'da creare' : 'MANCA')) . "\n";
    if (!$ok) $ferma("manca /$p ($cosa)");
}
if ($fotoProvv) echo "  i --foto-provvisorie non serve più: le immagini si creano sempre se mancano\n";
// Nessun rimando alla foto di testata della landing (generata o di repertorio: non è una foto della sede)
if (preg_match('#landing/clinica-dentale-civitanova/hero#', $tuttoScheda . json_encode($C['immagini'], JSON_UNESCAPED_SLASHES))) $ferma('la scheda usa ancora la foto di testata della landing');

/* ============================================================ SCRITTURA */
if ($errori) { echo "\n== $errori problemi (FERMO): niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova completata senza problemi. Per applicare: stesso comando con --scrivi\n"; exit(0); }

$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-clinica-dentale';
@mkdir($bk, 0775, true);
$R = ['creata' => null, 'risorse' => [], 'template' => [], 'file_creati' => [], 'cartelle_create' => [], 'file_modificati' => []];
$salvaR = fn() => file_put_contents("$bk/ripristino.json", json_encode($R, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$fotografa = function (modResource $r, array $tvNomi, array $campiNomi = []) use ($bk, $tvValore) {
    $f = "$bk/risorsa-{$r->get('id')}.html";
    file_put_contents($f, (string) $r->get('content'));
    $x = ['id' => (int) $r->get('id'), 'content' => $f, 'campi' => [], 'tv' => []];
    foreach ($campiNomi as $k) $x['campi'][$k] = $r->get($k);
    foreach ($tvNomi as $n) $x['tv'][$n] = $tvValore($r, $n);
    return $x;
};
echo "\n== Backup in $bk\n";

// Scheda
if ($esistente) {
    $R['risorse'][] = $fotografa($esistente, array_keys($tvNuove), array_keys(array_diff_key($campi, ['content' => 1])));
    $salvaR();
    $o = $esistente;
    unset($campi['menuindex']);
    $o->fromArray($campi);
} else {
    $o = $modx->newObject($modello->get('class_key') ?: modResource::class);
    $o->fromArray($campi);
    $o->set('createdon', time());
    $o->set('createdby', 1);
    if ($campi['published']) { $o->set('publishedon', time()); $o->set('publishedby', 1); }
}
if (!$o->save()) { echo "  ! scheda: salvataggio fallito\n"; exit(2); }
if ((string) $o->get('uri') === '') { $o->set('uri', $o->getAliasPath($o->get('alias'))); $o->save(); }
$ID = (int) $o->get('id');
if (!$esistente) {
    $R['creata'] = $ID; $salvaR();
    foreach ($modx->getIterator(modResourceGroupResource::class, ['document' => $modello->get('id')]) as $g) {
        $ng = $modx->newObject(modResourceGroupResource::class);
        $ng->fromArray(['document_group' => $g->get('document_group'), 'document' => $ID]);
        $ng->save();
    }
}
foreach ($tvNuove as $n => [, $v]) {
    if (!$tvEsiste($n) || ($v === '' && !$esistente)) continue;     // TV assente o vuota su una scheda nuova: niente da scrivere
    if (!$o->setTVValue($n, $v)) { echo "  ! TV $n non scritta\n"; $errori++; }
}
echo "  " . ($esistente ? '~' : '+') . " scheda $ID /{$o->get('uri')}\n";

// News
foreach ($news as [$r, $v]) {
    $ids = $csv(str_replace(' ', '', $v));
    if (in_array((string) $ID, $ids, true)) { echo "  = news {$r->get('id')}: id già presente\n"; continue; }
    $R['risorse'][] = ['id' => (int) $r->get('id'), 'tv' => ['idStruttura' => $v]]; $salvaR();
    $r->setTVValue('idStruttura', implode(',', array_merge($ids, [(string) $ID])));
    echo "  ~ news {$r->get('id')}: idStruttura «" . implode(',', array_merge($ids, [(string) $ID])) . "»\n";
}

// Villa dei Pini
if ($sedeNuovo) {
    [$k, $p, $testo] = $sedeNuovo;
    $da = $sedeNuovo[3] ?? $p;                          // voce già presente: si sostituisce il riquadro [da, p)
    $ps = $sedeParti[$k];
    $nuovoTesto = substr($ps, 0, $da) . $risolvi($testo, $ID) . substr($ps, $p);
    if ($k === 'content') {
        $R['risorse'][] = $fotografa($sede, []); $salvaR();
        $sede->set('content', $nuovoTesto);
        $sede->save();
    } else {
        $R['risorse'][] = ['id' => (int) $sede->get('id'), 'tv' => [substr($k, 3) => $ps]]; $salvaR();
        $sede->setTVValue(substr($k, 3), $nuovoTesto);
    }
    echo "  ~ Villa dei Pini {$sede->get('id')} (" . ($k === 'content' ? 'contenuto' : 'TV ' . substr($k, 3)) . "): voce «Clinica Dentale» con il link a [[~$ID]]\n";
}
// Landing
if ($landing && $landingPiano) {
    if (!empty($landingPiano['contenuto'])) {
        $R['risorse'][] = $fotografa($landing, []); $salvaR();
        $landing->set('content', $dove['contenuto']);
        $landing->save();
        echo "  ~ landing {$landing->get('id')}: contenuto\n";
    }
    if (!empty($landingPiano['template'])) {
        $f = "$bk/template-{$tl->get('id')}.html";
        file_put_contents($f, (string) $tl->get('content'));
        $R['template'][] = ['id' => (int) $tl->get('id'), 'file' => $f]; $salvaR();
        $tl->set('content', $dove['template']);
        $tl->save();
        echo "  ~ landing: template {$tl->get('templatename')}\n";
    }
}

// llms.txt
if ($llmsNuovo !== null) {
    copy("$webroot/llms.txt", "$bk/llms.txt");
    $R['file_modificati'][] = ['rel' => 'llms.txt', 'backup' => "$bk/llms.txt"]; $salvaR();
    file_put_contents("$webroot/llms.txt", $llmsNuovo);
    echo "  ~ /llms.txt\n";
}

// Immagini: ritaglio centrato, JPEG progressivo, qualità decrescente fino al tetto; copia semplice se le dimensioni sono
// quelle della foto di partenza e il peso sta nel tetto. Le immagini già presenti (--rifai-immagini) vanno nel backup.
foreach ($foto as [$da, $a, $w, $h]) {
    $cartella = dirname("$webroot/$a");
    if (!is_dir($cartella)) { mkdir($cartella, 0775, true); $R['cartelle_create'][] = dirname($a); }
    $eraLi = is_file("$webroot/$a");
    if ($eraLi) {
        $b = "$bk/immagini/" . str_replace('/', '__', $a);
        @mkdir("$bk/immagini", 0775, true);
        copy("$webroot/$a", $b);
        $R['file_modificati'][] = ['rel' => $a, 'backup' => $b]; $salvaR();
    }
    $dd = getimagesize("$webroot/$da");
    if ($dd && (int) $dd[0] === $w && (int) $dd[1] === $h && filesize("$webroot/$da") <= (int) $C['tetto_byte']) {
        copy("$webroot/$da", "$webroot/$a");
        if (!$eraLi) { $R['file_creati'][] = $a; $salvaR(); }
        printf("  %s /%s %d×%d, %d byte (copia)\n", $eraLi ? '~' : '+', $a, $w, $h, filesize("$webroot/$a"));
        continue;
    }
    foreach ([82, 76, 70, 64, 58, 52] as $q) {
        if (class_exists('Imagick')) {
            $im = new Imagick("$webroot/$da");
            $im->cropThumbnailImage($w, $h);
            $im->stripImage();
            $im->setImageFormat('jpeg');
            $im->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            $im->setImageCompressionQuality($q);
            $im->writeImage("$webroot/$a");
        } else {
            $src = imagecreatefromjpeg("$webroot/$da");
            $sw = imagesx($src); $sh = imagesy($src);
            $scala = max($w / $sw, $h / $sh);
            $cw = (int) round($w / $scala); $ch = (int) round($h / $scala);
            $dst = imagecreatetruecolor($w, $h);
            imagecopyresampled($dst, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);
            imageinterlace($dst, true);
            imagejpeg($dst, "$webroot/$a", $q);
        }
        clearstatcache();
        if (filesize("$webroot/$a") <= (int) $C['tetto_byte']) break;
    }
    if (!$eraLi) { $R['file_creati'][] = $a; $salvaR(); }
    printf("  %s /%s %d×%d, %d byte (qualità %d)\n", $eraLi ? '~' : '+', $a, $w, $h, filesize("$webroot/$a"), $q);
    if (filesize("$webroot/$a") > (int) $C['tetto_byte']) { echo "  ! /$a oltre il tetto di {$C['tetto_byte']} byte\n"; $errori++; }
}

$salvaR();
$modx->cacheManager->refresh();
echo "== Cache svuotata.\n";
echo "== Scheda: {$siteUrl}{$o->get('uri')}\n";
echo "== Ripristino: php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
