<?php
/**
 * Parco di Veio (lavoro extra): foto delle gallerie e immagine del footer, poliambulatorio (kosgroup.com) e RSA (anniazzurri.it).
 *
 * Uso sul cloud, dalla cartella del pacchetto:
 *   php installa.php /www kos                 prova: elenca cosa cambierebbe
 *   php installa.php /www kos --scrivi        copia i file, con backup
 *   php installa.php /www --ripristina <cartella backup>/ripristino.json --scrivi
 *
 * Copia <sito>/files/** in <webroot>/** (stesso percorso). Le cartelle elencate in <sito>/svuota.txt (una per riga,
 * percorso sotto il webroot) vengono allineate al pacchetto: i file che il pacchetto non contiene si spostano nel
 * backup (serve per le gallerie che mostrano tutte le immagini della cartella).
 * Backup: ~/backup-kos-seo-geo/<data>-parco-di-veio/ con ripristino.json (rimette i file sostituiti o spostati e
 * toglie quelli nuovi).
 */
$args = array_slice($argv, 1);
$scrivi = in_array('--scrivi', $args, true);
$webroot = rtrim($args[0] ?? '', '/');
if ($webroot === '' || !is_dir($webroot)) exit("Uso: php installa.php /www kos|aa [--scrivi]\n");
$home = getenv('HOME') ?: '.';
// MODX del sito (per la cache e per il controllo delle citazioni), caricato solo se serve
$mx = function () use ($webroot) {
    static $m = null;
    if ($m === null && is_file("$webroot/config.core.php")) {
        require "$webroot/config.core.php";
        require MODX_CORE_PATH . 'vendor/autoload.php';
        $m = \MODX\Revolution\modX::getInstance();
        $m->initialize('mgr');
    }
    return $m;
};
// cache delle pagine: lo snippet `immagine` rigenera le varianti (stesso nome, file più recente) solo quando la pagina
// si rigenera, quindi si svuota a ogni scrittura
$svuotaCache = function () use ($mx) {
    if ($m = $mx()) { $m->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
};

// ---------- ripristino ----------
$i = array_search('--ripristina', $args, true);
if ($i !== false) {
    $json = $args[$i + 1] ?? '';
    $r = json_decode((string) @file_get_contents($json), true);
    if (!$r) exit("File di ripristino illeggibile: $json\n");
    $dir = dirname($json);
    echo $scrivi ? "== RIPRISTINO\n" : "== PROVA RIPRISTINO (aggiungere --scrivi)\n";
    foreach ($r['nuovi'] as $rel) {
        echo "  - $rel (aggiunto dal pacchetto)\n";
        if ($scrivi) @unlink("$webroot/$rel");
    }
    foreach ($r['salvati'] as $rel) {
        echo "  < $rel\n";
        if ($scrivi) { @mkdir(dirname("$webroot/$rel"), 0775, true); copy("$dir/files/$rel", "$webroot/$rel"); }
    }
    if ($scrivi) $svuotaCache();
    echo $scrivi ? "== Ripristinato.\n" : "== Prova riuscita.\n";
    exit;
}

$sito = $args[1] ?? '';
if (!in_array($sito, ['kos', 'aa'], true)) exit("Indicare il sito: kos oppure aa\n");
$src = __DIR__ . "/$sito/files";
if (!is_dir($src)) exit("Nessun file per $sito\n");

// ---------- cosa cambia ----------
$pacchetto = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) if ($f->isFile()) $pacchetto[] = substr($f->getPathname(), strlen($src) + 1);
sort($pacchetto);

$copia = []; $nuovi = []; $sposta = [];
foreach ($pacchetto as $rel) {
    $dest = "$webroot/$rel";
    if (!is_file($dest)) { $copia[] = $rel; $nuovi[] = $rel; }
    elseif (md5_file($dest) !== md5_file("$src/$rel")) $copia[] = $rel;
}
$svuota = is_file(__DIR__ . "/$sito/svuota.txt")
    ? array_filter(array_map('trim', file(__DIR__ . "/$sito/svuota.txt")), fn($l) => $l !== '' && $l[0] !== '#') : [];
foreach ($svuota as $cartella) {
    $cartella = trim($cartella, '/');
    foreach (glob("$webroot/$cartella/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG}", GLOB_BRACE) ?: [] as $f) {   // solo immagini
        $rel = "$cartella/" . basename($f);
        if (is_file($f) && !in_array($rel, $pacchetto, true)) $sposta[] = $rel;
    }
}

// file che escono dalla cartella: qualche contenuto, TV, chunk o template li cita ancora per nome?
$citati = [];
if ($sposta && ($modx = $mx())) {
    $p = $modx->getOption('table_prefix');
    $dove = ["{$p}site_content" => ['id', 'content'], "{$p}site_tmplvar_contentvalues" => ['contentid', 'value'],
             "{$p}site_htmlsnippets" => ['name', 'snippet'], "{$p}site_templates" => ['templatename', 'content']];
    foreach ($sposta as $rel) {
        $nome = basename($rel);
        foreach ($dove as $tab => [$chiave, $campo]) {
            $q = $modx->prepare("SELECT `$chiave` FROM `$tab` WHERE `$campo` LIKE ?");
            $q->execute(['%' . dirname($rel) . '/' . $nome . '%']);
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $v) $citati[] = "$nome in $tab ($chiave $v)";
        }
    }
}

echo $scrivi ? "== AGGIORNAMENTO Parco di Veio ($sito)\n" : "== PROVA Parco di Veio ($sito) (aggiungere --scrivi)\n";
foreach ($citati as $c) echo "  ! citato: $c\n";
foreach ($copia as $rel) echo (in_array($rel, $nuovi, true) ? '  + ' : '  ~ ') . "/$rel (" . round(filesize("$src/$rel") / 1024) . " KB)\n";
foreach ($sposta as $rel) echo "  - /$rel (spostato nel backup)\n";
if (!$copia && !$sposta) exit("== Tutto già allineato.\n");
if ($citati && !in_array('--forza', $args, true))
    exit($scrivi ? "== Fermato: alcuni file da spostare sono ancora citati (sopra). Correggere, oppure --forza.\n"
                 : "== Attenzione: file da spostare ancora citati (sopra). Con --scrivi serve anche --forza.\n");
if (!$scrivi) exit("== Prova riuscita. Rilanciare con --scrivi.\n");

// ---------- backup e scrittura ----------
$bk = "$home/backup-kos-seo-geo/" . date('Ymd-His') . '-parco-di-veio';
$salvati = [];
foreach (array_merge(array_diff($copia, $nuovi), $sposta) as $rel) {
    @mkdir(dirname("$bk/files/$rel"), 0775, true);
    if (!copy("$webroot/$rel", "$bk/files/$rel")) exit("Backup non riuscito: $rel\n");
    $salvati[] = $rel;
}
@mkdir($bk, 0775, true);
file_put_contents("$bk/ripristino.json", json_encode(['sito' => $sito, 'salvati' => $salvati, 'nuovi' => $nuovi],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
foreach ($copia as $rel) {
    @mkdir(dirname("$webroot/$rel"), 0775, true);
    if (!copy("$src/$rel", "$webroot/$rel")) exit("Copia non riuscita: $rel (ripristino: $bk/ripristino.json)\n");
}
foreach ($sposta as $rel) unlink("$webroot/$rel");
$svuotaCache();
echo "== Aggiornato. Ripristino: php installa.php $webroot --ripristina $bk/ripristino.json --scrivi\n";
