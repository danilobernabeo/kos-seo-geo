<?php
/**
 * Firma autore degli approfondimenti di anniazzurri.it (lavoro extra aa-firma-autore).
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php installa.php /www                      prova: controlla tutto e mostra cosa cambierebbe, non scrive
 *   php installa.php /www --scrivi             installa e pubblica la pagina autore (con il link da «La nostra équipe»)
 *   php installa.php /www --scrivi --non-pubblicare
 *                                              installa tutto, pagina autore NON pubblicata e nessun link dall'équipe:
 *                                              aut.ref si può già compilare, senza effetti in pagina né nel JSON-LD
 *   php installa.php /www --pubblica [--scrivi]
 *                                              dopo un'installazione con --non-pubblicare: pubblica la pagina autore
 *                                              e aggiunge il link da «La nostra équipe» (backup e ripristino propri)
 *   php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-firma-autore/ripristino.json [--scrivi]
 *
 * Cosa fa l'installazione (con backup e manifest di ripristino):
 *   1. copia files/ e la foto (foto/antonella-schiavino.jpg → destinazione del JSON) in /www, mai sovrascrivendo;
 *   2. crea la categoria «Firma autore», il template «autore», i chunk head-autore e autore-articolo-firmato
 *      e il plugin aaFirmaAutoreCache;
 *   3. crea il contenitore /autori/ (non pubblicato, nascosto dal menu, escluso dalle briciole: la sua pagina
 *      non esiste, le briciole vanno Home → autore) e aggiunge il suo id ad aa.breadcrumb_exclude;
 *   4. crea le TV aut.* sul template autore e aut.ref sul template interna-articolo;
 *   5. crea la pagina autore dal JSON (campi, TV, testo delle sezioni 2-5) e, se pubblicata, il link
 *      da «La nostra équipe»;
 *   6. svuota la cache.
 * La pagina si pubblica solo se aut.consenso = 1 nel JSON (gate di pubblicazione).
 * Le firme sugli articoli si applicano con firme.php; firma e box nel template articolo arrivano con
 * anniazzurri/deploy/aa-firma-autore.json (elementi del progetto).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$NOME = 'firma-autore';
$CATEGORIA = 'Firma autore';
$TPL_AUTORE = 'autore';
$TPL_ARTICOLO = 'interna-articolo';
$TPL_STRUTTURE = ['rsa', 'centro-diurno'];      // schede struttura proponibili in aut.affiliazione
$CONTENITORE = 'autori';
$EQUIPE = 'la-nostra-equipe';
$CHUNK = ['head-autore' => 'Head della pagina autore',
          'autore-articolo-firmato' => 'Voce dell\'elenco «Articoli firmati» della pagina autore (un chunk: un @INLINE con i backtick della data spezzerebbe la chiamata)'];
$TV_SORGENTE_DA = 'newsImage';                  // la TV immagine usa la stessa media source
$PLUGIN_EVENTI = ['OnDocFormSave', 'OnDocPublished', 'OnDocUnPublished', 'OnResourceDelete', 'OnResourceUndelete'];

/* Testo aggiunto in fondo a «La nostra équipe» ({LINK} = [[~id]] della pagina autore). */
$EQUIPE_BLOCCO = <<<'HTML'

  <div stripe-text="" class="container mb-5" data-firma-autore="">
    <div class="row">
      <div class="col-lg-8">
        <hr class="mt-0">
        <h3 class="text-primary">Chi firma i nostri approfondimenti</h3>
        <p class="mb-0">Molti approfondimenti del sito sull'assistenza in residenza sono firmati da <a href="{LINK}">Antonella Schiavino</a>, infermiera e Referente Qualità ed Accreditamento del Gruppo KOS Care. Nella sua scheda il percorso professionale, la pubblicazione scientifica e l'elenco degli articoli firmati.</p>
      </div>
    </div>
  </div>
HTML;

/* TV: nome => [tipo, etichetta, descrizione, proprietà di input, elementi]. */
$TV_AUTORE = [
    'aut.given'           => ['text', 'Nome', 'Person.givenName', [], ''],
    'aut.family'          => ['text', 'Cognome', 'Person.familyName; con il nome compone Person.name', [], ''],
    'aut.honorific'       => ['text', 'Titolo', 'Vuoto, oppure Dott., Dott.ssa, Prof. (Person.honorificPrefix, solo se valorizzato). Solo se il titolo è conferito da un titolo di studio', [], ''],
    'aut.jobTitle'        => ['text', 'Ruolo', 'Person.jobTitle; in pagina e nella firma degli articoli', [], ''],
    'aut.bio'             => ['richtext', 'Biografia breve', '3-4 righe in terza persona: Person.description e box a fondo articolo. Niente numeri che invecchiano («dal 2000», non «26 anni»)', [], ''],
    'aut.foto'            => ['image', 'Foto', 'Quadrata, 1000x1000: Person.image (dimensioni lette dal file), pagina autore e box', [], ''],
    'aut.professione'     => ['text', 'Professione', 'Occupation.name (Person.hasOccupation)', [], ''],
    'aut.professione_cat' => ['text', 'Categoria professionale', 'Occupation.occupationalCategory', [], ''],
    'aut.knowsAbout'      => ['textarea', 'Aree di competenza', 'Una per riga: Person.knowsAbout', [], ''],
    'aut.albo_nome'       => ['text', 'Albo (nome)', 'Nome esteso dell\'ordine. Con il numero: identifier, credenziale e memberOf (se uno dei due è vuoto non esce nessuno dei tre)', [], ''],
    'aut.albo_numero'     => ['text', 'Albo (numero)', 'Numero di iscrizione', [], ''],
    'aut.credential'      => ['textarea', 'Titoli di studio', 'Una riga per titolo, anno facoltativo dopo «|»: hasCredential (degree)', [], ''],
    'aut.sameAs'          => ['textarea', 'Profili esterni', 'Un URL per riga (Person.sameAs). Il DOI della pubblicazione si aggiunge da solo. Solo profili che confermano la scheda', [], ''],
    'aut.affiliazione'    => ['resourcelist', 'Struttura in cui opera', 'Scheda struttura: Person.affiliation verso {URL scheda}#localbusiness. Vuoto finché non è confermata', 'STRUTTURE', ''],
    'aut.pub_titolo'      => ['text', 'Pubblicazione: titolo', 'subjectOf (ScholarlyArticle)', [], ''],
    'aut.pub_doi'         => ['text', 'Pubblicazione: DOI', 'Senza prefisso https://doi.org/', [], ''],
    'aut.pub_url'         => ['text', 'Pubblicazione: URL', 'URL dell\'abstract', [], ''],
    'aut.pub_data'        => ['text', 'Pubblicazione: data', 'AAAA-MM-GG', [], ''],
    'aut.consenso'        => ['checkbox', 'Consenso alla pubblicazione', 'Consenso scritto a pubblicare biografia, numero d\'albo e profili. Senza la spunta la pagina non va pubblicata', [], 'Consenso scritto ottenuto==1'],
];
$TV_REF = ['resourcelist', 'Autore', 'Pagina autore che firma l\'articolo. Vuoto = Redazione Anni Azzurri (nessuna firma in pagina, author = organizzazione). Solo articoli in cui l\'esperienza dell\'autore è la fonte (extra/aa-firma-autore/README.md)', 'AUTORI', ''];

/* ---------- argomenti e MODX ---------- */

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$nonPubblicare = in_array('--non-pubblicare', $argv, true);
$soloPubblica = in_array('--pubblica', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] [--non-pubblicare] | --pubblica [--scrivi] | --ripristina <file> [--scrivi]\n");
    exit(1);
}
$leggi = function (string $rel) use ($DIR): string {
    $p = "$DIR/$rel";
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return (string) file_get_contents($p);
};
$J = json_decode($leggi('autore-antonella-schiavino.json'), true);
if (!is_array($J) || empty($J['risorsa']['alias']) || !isset($J['tv'])) { fwrite(STDERR, "autore-antonella-schiavino.json non valido\n"); exit(1); }

require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modDocument, modTemplate, modChunk, modPlugin, modPluginEvent, modCategory, modSystemSetting,
    modTemplateVar, modTemplateVarTemplate, modTemplateVarResource};
use MODX\Revolution\Sources\modMediaSourceElement;
$modx = modX::getInstance();
$modx->initialize('mgr');
$errori = 0;
$ferma = function (string $msg) use (&$errori) { echo "  ! $msg — FERMO\n"; $errori++; };
$web = fn(array $c) => $modx->getObject(modResource::class, $c + ['context_key' => 'web', 'deleted' => 0]);
$cartellaBackup = function (string $suffisso = '') use ($NOME): string {
    $bk = rtrim((string) (getenv('HOME') ?: __DIR__), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . "-$NOME$suffisso";
    mkdir($bk, 0775, true);
    return $bk;
};

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['contenuti'] ?? [] as $x) {
        if (!is_file($x['file'])) { echo "  ! backup mancante: {$x['file']} — FERMO\n"; exit(2); }
    }
    foreach ($r['contenuti'] ?? [] as $x) {
        echo "  ~ risorsa {$x['id']}: contenuto originale\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, $x['id']))) { $o->set('content', file_get_contents($x['file'])); $o->save(); }
    }
    foreach ($r['pubblicate'] ?? [] as $id) {
        echo "  ~ risorsa $id: non pubblicata\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, (int) $id))) { $o->set('published', 0); $o->set('publishedon', 0); $o->save(); }
    }
    foreach (array_reverse($r['risorse_create'] ?? []) as $id) {
        $o = $modx->getObject(modResource::class, (int) $id);
        echo "  - risorsa $id" . ($o ? " /{$o->get('uri')}" : ' (già assente)') . "\n";
        if ($scrivi && $o) {
            foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => (int) $id]) as $v) $v->remove();
            $o->remove();
        }
    }
    foreach ($r['impostazioni'] ?? [] as $k => $vecchio) {
        echo "  ~ impostazione $k = «{$vecchio}»\n";
        if ($scrivi && ($s = $modx->getObject(modSystemSetting::class, ['key' => $k]))) { $s->set('value', $vecchio); $s->save(); }
    }
    foreach ($r['tv'] ?? [] as $nome) {
        $tv = $modx->getObject(modTemplateVar::class, ['name' => $nome]);
        $n = $tv ? $modx->getCount(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id')]) : 0;
        echo "  - TV $nome" . ($n ? " (con $n valori: aut.ref compilate? usare prima firme.php --ripristina)" : '') . "\n";
        if ($scrivi && $tv) {
            if ($n && $bkDir = dirname($fileRip)) {
                $valori = [];
                foreach ($modx->getIterator(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id')]) as $v) $valori[$v->get('contentid')] = $v->get('value');
                file_put_contents("$bkDir/valori-$nome.json", json_encode($valori, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            foreach ([modTemplateVarResource::class, modTemplateVarTemplate::class] as $cl) {
                foreach ($modx->getIterator($cl, ['tmplvarid' => $tv->get('id')]) as $o) $o->remove();
            }
            foreach ($modx->getIterator(modMediaSourceElement::class, ['object' => $tv->get('id'), 'object_class' => modTemplateVar::class]) as $o) $o->remove();
            $tv->remove();
        }
    }
    foreach ($r['elementi'] ?? [] as $x) {
        echo "  - {$x['type']} {$x['name']}\n";
        if (!$scrivi) continue;
        if ($x['type'] === 'template' && ($o = $modx->getObject(modTemplate::class, ['templatename' => $x['name']]))) $o->remove();
        if ($x['type'] === 'chunk' && ($o = $modx->getObject(modChunk::class, ['name' => $x['name']]))) $o->remove();
        if ($x['type'] === 'plugin' && ($o = $modx->getObject(modPlugin::class, ['name' => $x['name']]))) {
            foreach ($modx->getIterator(modPluginEvent::class, ['pluginid' => $o->get('id')]) as $ev) $ev->remove();
            $o->remove();
        }
    }
    if (!empty($r['categoria'])) {
        echo "  - categoria {$r['categoria']}\n";
        if ($scrivi && ($c = $modx->getObject(modCategory::class, ['category' => $r['categoria']]))) $c->remove();
    }
    foreach ($r['file_creati'] ?? [] as $f) {
        echo "  - file /$f\n";
        if ($scrivi) @unlink("$webroot/$f");
    }
    foreach (array_reverse($r['cartelle_create'] ?? []) as $d) {
        echo "  - cartella /$d (se vuota)\n";
        if ($scrivi) @rmdir("$webroot/$d");
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
    exit(0);
}

/* ---------- pubblicazione: pagina autore + link dall'équipe ---------- */

/** Controlli per pubblicare la pagina autore; ritorna [pagina, équipe, nuovo contenuto équipe] o null. */
$pianoPubblicazione = function (?modResource $pagina, bool $giaCreata) use ($modx, $web, $J, $EQUIPE, $EQUIPE_BLOCCO, $ferma) {
    if ((int) ($J['tv']['aut.consenso'] ?? 0) !== 1) { $ferma('aut.consenso non è 1 nel JSON: la pagina non si pubblica'); return null; }
    $equipe = $web(['alias' => $EQUIPE]);
    if (!$equipe) { $ferma("pagina /$EQUIPE non trovata"); return null; }
    $c = (string) $equipe->get('content');
    $idPagina = $pagina ? (int) $pagina->get('id') : 0;
    if (str_contains($c, 'data-firma-autore')) { $ferma("/$EQUIPE contiene già il blocco della firma autore"); return null; }
    echo "  ~ /$EQUIPE (risorsa {$equipe->get('id')}): in fondo il blocco «Chi firma i nostri approfondimenti», link a "
        . ($giaCreata ? "[[~$idPagina]]" : 'la pagina autore') . "\n";
    return [$equipe, $c];
};
$pubblica = function (modResource $pagina, modResource $equipe, string $vecchio, string $bk, array &$rip, callable $salvaRip) use ($EQUIPE_BLOCCO) {
    $pagina->set('published', 1);
    $pagina->set('publishedon', time());
    $pagina->set('publishedby', 1);
    $pagina->save();
    $rip['pubblicate'][] = (int) $pagina->get('id');
    $salvaRip();
    echo "  ~ pubblicata /{$pagina->get('uri')}\n";
    file_put_contents("$bk/risorsa-{$equipe->get('id')}.html", $vecchio);
    $equipe->set('content', rtrim($vecchio) . "\n" . str_replace('{LINK}', '[[~' . $pagina->get('id') . ']]', $EQUIPE_BLOCCO) . "\n");
    $equipe->save();
    $rip['contenuti'][] = ['id' => (int) $equipe->get('id'), 'file' => "$bk/risorsa-{$equipe->get('id')}.html"];
    $salvaRip();
    echo "  ~ /{$equipe->get('uri')}: link alla pagina autore\n";
};

if ($soloPubblica) {
    echo ($scrivi ? "== PUBBLICAZIONE" : "== PROVA DI PUBBLICAZIONE (aggiungere --scrivi)") . " — pagina autore\n";
    $cont = $web(['alias' => $CONTENITORE, 'parent' => 0]);
    $pagina = $cont ? $web(['alias' => $J['risorsa']['alias'], 'parent' => (int) $cont->get('id')]) : null;
    if (!$pagina) { $ferma('pagina autore non trovata: prima l\'installazione'); }
    elseif ($pagina->get('published')) { $ferma("/{$pagina->get('uri')} è già pubblicata"); }
    $piano = $pagina ? $pianoPubblicazione($pagina, true) : null;
    if ($errori || !$piano) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
    if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi.\n"; exit(0); }
    $bk = $cartellaBackup('-pubblica');
    $rip = ['pubblicate' => [], 'contenuti' => []];
    $salvaRip = function () use (&$rip, $bk) { file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); };
    $pubblica($pagina, $piano[0], $piano[1], $bk, $rip, $salvaRip);
    $modx->cacheManager->refresh();
    echo "== Cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
    exit(0);
}

/* ---------- installazione: controlli ---------- */

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — firma autore"
    . ($nonPubblicare ? ' (pagina autore NON pubblicata)' : '') . "\n";

echo "\n-- Elementi\n";
if ($modx->getObject(modCategory::class, ['category' => $CATEGORIA])) $ferma("categoria «{$CATEGORIA}» esiste già (già installato?)");
else echo "  + categoria «{$CATEGORIA}»\n";
if ($modx->getObject(modTemplate::class, ['templatename' => $TPL_AUTORE])) $ferma("template $TPL_AUTORE esiste già");
else echo "  + template $TPL_AUTORE\n";
foreach ($CHUNK as $c => $d) {
    if ($modx->getObject(modChunk::class, ['name' => $c])) $ferma("chunk $c esiste già");
    else echo "  + chunk $c\n";
}
if ($modx->getObject(modPlugin::class, ['name' => 'aaFirmaAutoreCache'])) $ferma('plugin aaFirmaAutoreCache esiste già');
else echo "  + plugin aaFirmaAutoreCache (" . implode(', ', $PLUGIN_EVENTI) . ")\n";
$tplArticolo = $modx->getObject(modTemplate::class, ['templatename' => $TPL_ARTICOLO]);
if (!$tplArticolo) $ferma("template $TPL_ARTICOLO non trovato");
$tplStrutture = [];
foreach ($TPL_STRUTTURE as $n) {
    if ($t = $modx->getObject(modTemplate::class, ['templatename' => $n])) $tplStrutture[] = (int) $t->get('id');
    else echo "  i template $n non trovato: escluso da aut.affiliazione\n";
}
foreach (['immagine', 'pdoResources', 'BreadCrumb', 'titoloPagina', 'Canonical', 'ogImage', 'aaSchema'] as $s) {
    if (!$modx->getObject(\MODX\Revolution\modSnippet::class, ['name' => $s])) $ferma("snippet $s non installato (serve al template autore)");
}
foreach (['GTM_body', 'GTM_head', 'menu-sticky', 'menu-navbar', 'menu-side-navbar', 'breadcrumb-stripe', 'footer', 'footer-script'] as $c) {
    if (!$modx->getObject(modChunk::class, ['name' => $c])) $ferma("chunk $c non trovato (serve al template autore)");
}

echo "\n-- TV\n";
foreach (array_merge(array_keys($TV_AUTORE), ['aut.ref']) as $n) {
    if ($modx->getObject(modTemplateVar::class, ['name' => $n])) $ferma("TV $n esiste già");
}
echo '  + ' . count($TV_AUTORE) . " TV aut.* sul template $TPL_AUTORE\n";
echo "  + TV aut.ref sul template $TPL_ARTICOLO" . ($tplArticolo ? " ({$tplArticolo->get('id')}): " . $modx->getCount(modResource::class, ['template' => $tplArticolo->get('id'), 'deleted' => 0]) . ' risorse, tutte alla redazione finché non si compila' : '') . "\n";
echo '  + aut.affiliazione: schede dei template ' . implode(', ', $tplStrutture) . " (resta vuota)\n";
$tvSorgente = $modx->getObject(modTemplateVar::class, ['name' => $TV_SORGENTE_DA]);
$sorgenti = $tvSorgente ? iterator_to_array($modx->getIterator(modMediaSourceElement::class, ['object' => $tvSorgente->get('id'), 'object_class' => modTemplateVar::class]), false) : [];
echo "    media source di aut.foto copiata da $TV_SORGENTE_DA: " . (count($sorgenti) ? count($sorgenti) . ' collegamento/i' : 'nessuno (sorgente predefinita)') . "\n";

echo "\n-- Pagine\n";
$R = $J['risorsa'];
if ($web(['alias' => $CONTENITORE, 'parent' => 0])) $ferma("contenitore /$CONTENITORE/ esiste già");
else echo "  + contenitore /$CONTENITORE/ (non pubblicato, nascosto dal menu, non ricercabile)\n";
if ($modx->getObject(modResource::class, ['uri' => $R['uri'], 'context_key' => 'web'])) $ferma("una risorsa con uri {$R['uri']} esiste già");
$escl = (string) $modx->getOption('aa.breadcrumb_exclude');
if (!$modx->getObject(modSystemSetting::class, ['key' => 'aa.breadcrumb_exclude'])) $ferma('impostazione aa.breadcrumb_exclude non trovata');
else echo "  ~ aa.breadcrumb_exclude: «{$escl}» + id del contenitore\n";

// Contenuto: le sezioni 2-5 di contenuto_visibile (l'intestazione la scrive il template, la 6 la genera).
preg_match_all('#<section class="scheda-autore__(bio|competenze|formazione|pubblicazioni)">.*?</section>#s', (string) $J['contenuto_visibile'], $mm);
$contenuto = implode("\n\n", array_map(fn($s) => preg_replace('/^  /m', '', $s), $mm[0])) . "\n";
if (count($mm[0]) !== 4) $ferma('contenuto_visibile: attese 4 sezioni (bio, competenze, formazione, pubblicazioni), trovate ' . count($mm[0]));
$introtext = preg_match('#<p class="scheda-autore__albo">(.*?)</p>#s', (string) $J['contenuto_visibile'], $m) ? trim($m[1]) : '';
if ($introtext === '') $ferma('contenuto_visibile: riga dell\'albo non trovata');
$pubblicare = !$nonPubblicare && (int) $R['published'] === 1;
printf("  + /%s «%s» (template %s, %s, ricercabile %d, nascosta dal menu %d)\n", $R['uri'], $R['pagetitle'], $TPL_AUTORE,
    $pubblicare ? 'pubblicata' : 'NON pubblicata', (int) $R['searchable'], (int) $R['hidemenu']);
echo "    riassunto (riga dell'albo): $introtext\n";
echo '    contenuto: ' . strlen($contenuto) . " byte, sezioni " . implode(', ', $mm[1]) . "\n";
foreach ($J['tv'] as $n => $v) {
    if (!isset($TV_AUTORE[$n])) { $ferma("TV $n del JSON non prevista"); continue; }
    if ((string) $v !== '') echo "    $n = " . mb_strimwidth(str_replace("\n", ' | ', (string) $v), 0, 110, '…') . "\n";
}
$piano = $pubblicare ? $pianoPubblicazione(null, false) : null;

echo "\n-- File statici\n";
$copie = [];
$sorg = "$DIR/files";
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS)) as $f) {
    $copie[str_replace('\\', '/', substr($f->getPathname(), strlen($sorg) + 1))] = $f->getPathname();
}
$copie[ltrim((string) $J['foto']['destinazione_sul_sito'], '/')] = "$DIR/" . $J['foto']['file'];
if (ltrim((string) $J['tv']['aut.foto'], '/') !== ltrim((string) $J['foto']['destinazione_sul_sito'], '/')) $ferma('aut.foto e foto.destinazione_sul_sito del JSON non coincidono');
foreach ($copie as $rel => $src) {
    if (!is_file($src)) { $ferma("sorgente mancante: $src"); unset($copie[$rel]); continue; }
    if (is_file("$webroot/$rel")) {
        if (md5_file("$webroot/$rel") === md5_file($src)) { echo "  = /$rel già presente\n"; unset($copie[$rel]); continue; }
        $ferma("/$rel esiste con un contenuto diverso (non si sovrascrive)");
        continue;
    }
    $d = @getimagesize($src);
    echo "  + /$rel" . ($d ? " ({$d[0]}×{$d[1]})" : '') . "\n";
}

if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi" . ($nonPubblicare ? ' --non-pubblicare' : '') . ".\n"; exit(0); }

/* ---------- scrittura ---------- */

$bk = $cartellaBackup();
$rip = ['risorse_create' => [], 'pubblicate' => [], 'contenuti' => [], 'impostazioni' => [], 'tv' => [], 'elementi' => [],
        'categoria' => '', 'file_creati' => [], 'cartelle_create' => []];
$salvaRip = function () use (&$rip, $bk) {
    file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
};
echo "\n-- Scrittura (backup in $bk)\n";

foreach ($copie as $rel => $src) {
    $cartella = dirname($rel);
    $mancanti = [];
    for ($d = $cartella; $d !== '.' && $d !== '' && !is_dir("$webroot/$d"); $d = dirname($d)) array_unshift($mancanti, $d);
    foreach ($mancanti as $d) { mkdir("$webroot/$d", 0775); $rip['cartelle_create'][] = $d; echo "  + cartella /$d\n"; }
    copy($src, "$webroot/$rel");
    $rip['file_creati'][] = $rel;
    echo "  + /$rel\n";
}
$salvaRip();

$cat = $modx->newObject(modCategory::class);
$cat->set('category', $CATEGORIA);
$cat->save();
$catId = (int) $cat->get('id');
$rip['categoria'] = $CATEGORIA;
$salvaRip();

$descr = 'Firma autore degli approfondimenti (extra/aa-firma-autore)';
$tpl = $modx->newObject(modTemplate::class);
$tpl->fromArray(['templatename' => $TPL_AUTORE, 'description' => "Pagina autore: $descr", 'category' => $catId, 'icon' => '']);
$tpl->setContent($leggi("elementi/templates/$TPL_AUTORE.html"));
if (!$tpl->save()) { echo "  ! template: salvataggio fallito — FERMO\n"; exit(2); }
$rip['elementi'][] = ['type' => 'template', 'name' => $TPL_AUTORE];
$salvaRip();
echo "  + template $TPL_AUTORE ({$tpl->get('id')})\n";

foreach ($CHUNK as $c => $d) {
    $ch = $modx->newObject(modChunk::class);
    $ch->fromArray(['name' => $c, 'description' => "$d: $descr", 'category' => $catId]);
    $ch->setContent($leggi("elementi/chunks/$c.html"));
    $ch->save();
    $rip['elementi'][] = ['type' => 'chunk', 'name' => $c];
    $salvaRip();
    echo "  + chunk $c\n";
}

$pl = $modx->newObject(modPlugin::class);
$pl->fromArray(['name' => 'aaFirmaAutoreCache', 'description' => "Svuotamento mirato della cache: $descr", 'category' => $catId, 'disabled' => 0]);
$pl->setContent(trim(preg_replace('/^\s*<\?php\s*/', '', $leggi('elementi/plugins/aaFirmaAutoreCache.php'))));
$pl->save();
foreach ($PLUGIN_EVENTI as $ev) {
    $pe = $modx->newObject(modPluginEvent::class);
    $pe->fromArray(['pluginid' => $pl->get('id'), 'event' => $ev, 'priority' => 0, 'propertyset' => 0], '', true, true);
    $pe->save();
}
$rip['elementi'][] = ['type' => 'plugin', 'name' => 'aaFirmaAutoreCache'];
$salvaRip();
echo "  + plugin aaFirmaAutoreCache\n";

$cont = $modx->newObject(modDocument::class);
$cont->fromArray(['pagetitle' => 'Autori', 'alias' => $CONTENITORE, 'parent' => 0, 'template' => 0, 'isfolder' => 1,
    'published' => 0, 'hidemenu' => 1, 'searchable' => 0, 'richtext' => 0, 'cacheable' => 1, 'context_key' => 'web',
    'content' => '', 'description' => 'Contenitore delle pagine autore (extra/aa-firma-autore): non pubblicato, escluso dalle briciole.',
    'createdon' => time(), 'createdby' => 1]);
if (!$cont->save()) { echo "  ! contenitore: salvataggio fallito — FERMO\n"; exit(2); }
if ((string) $cont->get('uri') === '') { $cont->set('uri', $cont->getAliasPath($CONTENITORE)); $cont->save(); }
$rip['risorse_create'][] = (int) $cont->get('id');
$salvaRip();
echo "  + contenitore {$cont->get('id')} /{$cont->get('uri')}\n";

$s = $modx->getObject(modSystemSetting::class, ['key' => 'aa.breadcrumb_exclude']);
$rip['impostazioni']['aa.breadcrumb_exclude'] = (string) $s->get('value');
$salvaRip();
$s->set('value', trim((string) $s->get('value') . ',' . $cont->get('id'), ','));
$s->save();
echo "  ~ aa.breadcrumb_exclude = {$s->get('value')}\n";

/* TV */
$proprieta = function (string $tipo, $speciale) use ($cont, $tpl, $tplStrutture): array {
    $p = ['allowBlank' => 'true'];
    if ($speciale === 'AUTORI') {
        $p += ['showNone' => 'true', 'parents' => (string) $cont->get('id'), 'depth' => '1', 'includeParent' => 'false',
               'limitRelatedContext' => 'false', 'where' => json_encode([['template' => (int) $tpl->get('id')]]), 'limit' => '0'];
    } elseif ($speciale === 'STRUTTURE') {
        $p += ['showNone' => 'true', 'parents' => '', 'depth' => '10', 'includeParent' => 'true',
               'limitRelatedContext' => 'false', 'where' => json_encode([['template:IN' => $tplStrutture]]), 'limit' => '0'];
    }
    return $p;
};
$creaTv = function (string $nome, array $d, int $templateId, int $rank) use ($modx, $catId, $proprieta, $sorgenti, &$rip, $salvaRip) {
    [$tipo, $etichetta, $descrizione, $speciale, $elementi] = $d;
    $tv = $modx->newObject(modTemplateVar::class);
    $tv->fromArray(['name' => $nome, 'caption' => $etichetta, 'description' => $descrizione, 'type' => $tipo,
                    'category' => $catId, 'display' => 'default', 'default_text' => '', 'elements' => $elementi, 'rank' => $rank]);
    $tv->set('input_properties', $proprieta($tipo, $speciale));
    $tv->set('output_properties', []);
    if (!$tv->save()) { echo "  ! TV $nome: salvataggio fallito\n"; return null; }
    $rip['tv'][] = $nome;
    $salvaRip();
    $l = $modx->newObject(modTemplateVarTemplate::class);
    $l->fromArray(['tmplvarid' => $tv->get('id'), 'templateid' => $templateId, 'rank' => $rank], '', true, true);
    $l->save();
    if ($tipo === 'image') {
        foreach ($sorgenti as $s) {
            $ns = $modx->newObject(modMediaSourceElement::class);
            $ns->fromArray(['source' => $s->get('source'), 'object_class' => modTemplateVar::class,
                            'object' => $tv->get('id'), 'context_key' => $s->get('context_key')], '', true, true);
            $ns->save();
        }
    }
    return $tv;
};
$tvObj = [];
$rank = 0;
foreach ($TV_AUTORE as $n => $d) {
    if (!($tvObj[$n] = $creaTv($n, $d, (int) $tpl->get('id'), $rank++))) $errori++;
}
if (!$creaTv('aut.ref', $TV_REF, (int) $tplArticolo->get('id'), 50)) $errori++;
echo '  + ' . count($rip['tv']) . " TV\n";
if ($errori) { echo "== $errori problemi: mi fermo. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n"; exit(2); }

/* Pagina autore */
$pagina = $modx->newObject(modDocument::class);
$pagina->fromArray(['pagetitle' => $R['pagetitle'], 'longtitle' => $R['longtitle'], 'description' => $R['description'],
    'alias' => $R['alias'], 'introtext' => $introtext, 'content' => $contenuto, 'parent' => (int) $cont->get('id'),
    'template' => (int) $tpl->get('id'), 'published' => 0, 'hidemenu' => (int) $R['hidemenu'], 'searchable' => (int) $R['searchable'],
    'richtext' => 1, 'cacheable' => 1, 'isfolder' => 0, 'context_key' => 'web', 'createdon' => time(), 'createdby' => 1,
    // Sul sito use_alias_path è spento (URL piatti): /autori/antonella-schiavino si ottiene solo con l'URI fissato
    // («Congela URI»). È la chiave dell'entità (@id): non va più cambiato.
    'uri_override' => 1, 'uri' => $R['uri']]);
if (!$pagina->save()) { echo "  ! pagina autore: salvataggio fallito — FERMO\n"; exit(2); }
$rip['risorse_create'][] = (int) $pagina->get('id');
$salvaRip();
if ($pagina->get('uri') !== $R['uri']) { echo "  ! uri {$pagina->get('uri')} diverso da quello atteso {$R['uri']} — FERMO\n"; exit(2); }
foreach ($J['tv'] as $n => $v) {
    if ((string) $v === '') continue;
    $tvObj[$n]->setValue($pagina->get('id'), (string) $v);
    $tvObj[$n]->save();
}
echo "  + pagina autore {$pagina->get('id')} /{$pagina->get('uri')}\n";

if ($pubblicare && $piano) $pubblica($pagina, $piano[0], $piano[1], $bk, $rip, $salvaRip);

$modx->cacheManager->refresh();
echo "== Cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
if (!$pubblicare) echo "== Pagina autore non pubblicata: quando si decide, php installa.php /www --pubblica (poi --scrivi)\n";
exit(0);
