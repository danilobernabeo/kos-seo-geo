<?php
/**
 * Simulazione della libreria comune del dato strutturato (shared/snippets/seoSchema.php) in PHP CLI, senza MODX.
 * Un modX finto risponde a getOption, makeUrl, getObject e getTVValue con un piccolo albero di risorse di
 * kosgroup.com (dati di prova, non reali); le impostazioni kos.* si leggono dai manifest di kosgroup/deploy/, così
 * la prova usa gli stessi valori che l'installazione scrive sul sito.
 *
 * Uso (dalla radice del repository):
 *   php scripts/test/seo-schema-simulazione.php                     # tutti i casi, con i controlli
 *   php scripts/test/seo-schema-simulazione.php --mostra=892         # stampa anche il @graph della risorsa 892
 *   php scripts/test/seo-schema-simulazione.php --lib=/percorso/seoSchema.php --json   # solo i JSON (confronto tra versioni)
 *   php scripts/test/seo-schema-simulazione.php --senza=kos-10-article.json            # senza un manifest (stato prima)
 *   php scripts/test/seo-schema-simulazione.php --vocabolario=schemaorg-current-https.jsonld
 *       controlla anche tipi e proprietà sul vocabolario di schema.org (file della release, da
 *       github.com/schemaorg/schemaorg, data/releases/<versione>/): ogni proprietà deve essere prevista per il tipo
 *
 * Controlli: un solo blocco JSON-LD con un solo @graph; @id unici, assoluti, sull'host canonico, con frammento noto;
 * ogni riferimento {"@id"} risolto nel grafo o verso una pagina del sito con frammento noto; tipi da un elenco di tipi
 * schema.org esistenti; Article/NewsArticle con i campi che Google indica (headline, image, datePublished,
 * dateModified >= datePublished, author con name e url, publisher); VideoObject con name, thumbnailUrl, uploadDate.
 * Esce con 1 se un controllo fallisce.
 */
declare(strict_types=1);

$opt = getopt('', ['lib:', 'mostra:', 'json', 'senza:', 'vocabolario:']);
$RADICE = dirname(__DIR__, 2);
$LIB = $opt['lib'] ?? "$RADICE/shared/snippets/seoSchema.php";
$SENZA = array_filter(explode(',', (string) ($opt['senza'] ?? '')));
date_default_timezone_set('America/Chicago');   // fuso del server MODX Cloud

/* ---------- immagini di prova (file veri, per getimagesize) ---------- */
$BASE = sys_get_temp_dir() . '/seo-schema-sim-' . getmypid() . '/';
@mkdir($BASE . 'assets/img/news', 0777, true);
@mkdir($BASE . 'assets/img/logo', 0777, true);
$png = function (string $f, int $w, int $h) { $i = imagecreatetruecolor($w, $h); imagepng($i, $f); imagedestroy($i); };
$png($BASE . 'assets/img/news/abbuffate.png', 1200, 675);
$png($BASE . 'assets/img/news/premio kos.png', 980, 551);   // nome con spazio: l'URL va codificato
$png($BASE . 'assets/img/logo/kos-group-logo-512.png', 512, 512);
define('MODX_BASE_PATH', $BASE);
register_shutdown_function(function () use ($BASE) { exec('rm -rf ' . escapeshellarg($BASE)); });

/* ---------- impostazioni dai manifest ---------- */
$SETTINGS = ['site_start' => 1];
foreach (['kos-9-12-13.json', 'kos-24.json', 'kos-24-attivazione.json', 'kos-10-article.json', 'kos-11.json'] as $m) {
    if (in_array($m, $SENZA, true)) continue;
    $f = "$RADICE/kosgroup/deploy/$m";
    if (!is_file($f)) continue;
    foreach (json_decode((string) file_get_contents($f), true)['settings'] ?? [] as $s) $SETTINGS[$s['key']] = $s['value'];
}
// Reparti (Villa Margherita Vicenza, 12/11): sede su una landing (template landing aggiunto alle schede struttura),
// due unità specialistiche. Sul sito i valori li scrive scripts/deploy/kos-reparti-villa-margherita.php.
if (!in_array('reparti', $SENZA, true)) {
    $SETTINGS['kos.tpl_struttura'] = ($SETTINGS['kos.tpl_struttura'] ?? '') . ',landing';
    $SETTINGS['kos.reparti'] = '1233:1500,1234:1500';
}

/* ---------- albero di prova ---------- */
$ts = fn(string $d) => date('Y-m-d H:i:s', strtotime($d));
$R = [
    // id => [campi, TV]
    1    => [['pagetitle' => 'Home', 'uri' => 'it', 'parent' => 253, 'template' => 'generica'], []],
    253  => [['pagetitle' => 'it', 'uri' => 'it/', 'parent' => 0, 'template' => 'generica', 'content' => '[[FirstChildRedirect]]'], []],
    28   => [['pagetitle' => 'News e media', 'uri' => 'it/news-e-media', 'parent' => 253, 'template' => 'news-media'], []],
    30   => [['pagetitle' => 'News', 'uri' => 'it/news-e-media/news', 'parent' => 28, 'template' => 'news-media'], []],
    31   => [['pagetitle' => 'Approfondimenti', 'uri' => 'it/news-e-media/approfondimenti', 'parent' => 28, 'template' => 'news-media'], []],
    32   => [['pagetitle' => 'Eventi e convegni', 'uri' => 'it/news-e-media/eventi-e-convegni', 'parent' => 28, 'template' => 'news-media'], []],
    34   => [['pagetitle' => 'Video', 'uri' => 'it/news-e-media/video', 'parent' => 28, 'template' => 'news-media'], []],
    3102 => [['pagetitle' => '2025', 'uri' => 'it/news-e-media/approfondimenti/2025/', 'parent' => 31, 'template' => 'news-media', 'published' => 0], []],
    3002 => [['pagetitle' => '2026', 'uri' => 'it/news-e-media/news/2026/', 'parent' => 30, 'template' => 'news-media', 'published' => 0], []],
    // approfondimento con struttura collegata e immagine
    892  => [['pagetitle' => 'Abbuffate: cosa sono, come riconoscerle e come affrontarle',
              'longtitle' => 'Abbuffate: sintomi, cause e strategie di cura | KOS',
              'description' => 'Cosa sono le abbuffate, come riconoscerle e quali percorsi di cura esistono.',
              'introtext' => 'Il disturbo da alimentazione incontrollata',
              'uri' => 'it/news-e-media/approfondimenti/abbuffate-cosa-sono-come-riconoscerle-e-come-affrontarle',
              'parent' => 3102, 'template' => 'informativo',
              'publishedon' => $ts('2025-03-04 10:12:00'), 'editedon' => $ts('2026-10-27 02:21:00')],
             ['infoImage' => 'assets/img/news/abbuffate.png', 'idStruttura' => '218, 999']],
    // news senza description, immagine con spazio nel nome, editedon precedente a publishedon (pubblicazione programmata)
    3105 => [['pagetitle' => 'Premio KOS per la ricerca', 'longtitle' => '',
              'introtext' => '<p>Il premio &egrave; stato <b>assegnato</b> a Milano.</p>',
              'uri' => 'it/news-e-media/news/premio-kos-per-la-ricerca', 'parent' => 3002, 'template' => 'informativo',
              'publishedon' => $ts('2026-06-10 09:00:00'), 'editedon' => $ts('2026-06-01 18:00:00')],
             ['infoImage' => 'assets/img/news/premio kos.png']],
    // evento senza immagine (il file indicato non esiste)
    3205 => [['pagetitle' => 'Settimana del cervello 2025', 'uri' => 'it/news-e-media/eventi-e-convegni/settimana-del-cervello-2025',
              'parent' => 32, 'template' => 'informativo', 'publishedon' => $ts('2025-03-01 08:00:00'), 'editedon' => 0],
             ['infoImage' => 'assets/img/news/non-esiste.jpg']],
    // video
    3405 => [['pagetitle' => 'Anoressia nervosa: cos\'è e come si riconosce', 'description' => 'Il dottor Fabio Conti spiega i segnali dell\'anoressia nervosa.',
              'uri' => 'it/news-e-media/video/anoressia-nervosa-cose-e-come-si-riconosce', 'parent' => 34, 'template' => 'video',
              'publishedon' => $ts('2025-02-20 12:00:00'), 'editedon' => $ts('2025-02-21 12:00:00')],
             ['embedCode' => 'AbCdEfGhIj0?si=xyz', 'videoData' => '2025-02-18 00:00:00', 'idStruttura' => '218']],
    // pagina col template informativo fuori dalle sezioni degli articoli
    600  => [['pagetitle' => 'Codice etico', 'uri' => 'it/chi-siamo/codice-etico', 'parent' => 253, 'template' => 'informativo',
              'publishedon' => $ts('2024-01-01 10:00:00'), 'editedon' => $ts('2024-02-01 10:00:00')], []],
    // struttura con indirizzo (nodo #localbusiness)
    218  => [['pagetitle' => 'Villa dei Pini', 'uri' => 'it/centri/villa-dei-pini-civitanova', 'parent' => 253, 'template' => 'strutture',
              'description' => 'Casa di cura neuropsichiatrica.', 'alias' => 'villa-dei-pini-civitanova'],
             ['str.via' => 'Via di prova 1', 'str.cap' => '62012', 'str.citta' => 'Civitanova Marche', 'str.provincia' => 'mc',
              'str.tipo' => 'Hospital', 'str.specialita' => 'Psychiatric', 'str.telefono' => '+390000000000']],
    // struttura senza indirizzo: nessun nodo, quindi nessuna mention
    999  => [['pagetitle' => 'Struttura senza indirizzo', 'uri' => 'it/centri/senza-indirizzo', 'parent' => 253, 'template' => 'strutture'], []],
    // medico (per la prova dell'autore con la TV aut.ref, che su kosgroup.com oggi non esiste)
    777  => [['pagetitle' => 'Fabio Conti', 'uri' => 'it/chi-siamo/qualita-delle-cure/medici/fabio-conti', 'parent' => 253, 'template' => 'medici'],
             ['ruolo' => 'Psichiatra', 'idSedeLavoro' => '218', 'infoImage' => '/assets/img/news/abbuffate.png']],
    // professionisti (KOS-11): fisioterapista (Person), neurologo con sede senza nodo, ruolo con refuso
    778  => [['pagetitle' => 'Anna Rossi', 'uri' => 'it/chi-siamo/qualita-delle-cure/medici/anna-rossi', 'parent' => 253, 'template' => 'medici',
              'description' => 'Fisioterapista a Villa dei Pini.'], ['ruolo' => 'Fisioterapista', 'idSedeLavoro' => '218']],
    779  => [['pagetitle' => 'Luca Bianchi', 'uri' => 'it/chi-siamo/qualita-delle-cure/medici/luca-bianchi', 'parent' => 253, 'template' => 'medici'],
             ['ruolo' => 'Neurologo e Neuroradiologo', 'idSedeLavoro' => '999']],
    780  => [['pagetitle' => 'Marta Verdi', 'uri' => 'it/chi-siamo/qualita-delle-cure/medici/marta-verdi', 'parent' => 253, 'template' => 'medici'],
             ['ruolo' => 'Psicologa clinica', 'idSedeLavoro' => '999']],
    // reparti: sede su landing (1500), due unità (1233, 1234), un medico dell'unità (781)
    1500 => [['pagetitle' => 'Villa Margherita Vicenza', 'uri' => 'villamargherita', 'parent' => 0, 'template' => 'landing',
              'description' => 'Casa di Cura dal 1926.'],
             ['str.via' => 'Via Costacolonna 6', 'str.cap' => '36057', 'str.citta' => 'Arcugnano', 'str.provincia' => 'VI',
              'str.tipo' => 'Hospital', 'str.specialita' => 'Neurologic,Psychiatric', 'str.telefono' => '+39044415851']],
    1233 => [['pagetitle' => 'Villa Margherita Vicenza - Riabilitazione',
              'longtitle' => 'Villa Margherita Vicenza: Centro di riabilitazione | KOS', 'uri' => 'it/centri/villa-margherita-vicenza-riabilitazione',
              'parent' => 253, 'template' => 'strutture'], []],
    1234 => [['pagetitle' => 'Villa Margherita Vicenza - Psichiatria', 'uri' => 'it/centri/villa-margherita-vicenza-psichiatria',
              'parent' => 253, 'template' => 'strutture'], []],
    781  => [['pagetitle' => 'Paolo Neri', 'uri' => 'it/chi-siamo/qualita-delle-cure/medici/paolo-neri', 'parent' => 253, 'template' => 'medici'],
             ['ruolo' => 'Neurologo', 'idSedeLavoro' => '1233']],
    3106 => [['pagetitle' => 'Anoressia: quando serve il ricovero', 'uri' => 'it/news-e-media/approfondimenti/anoressia-ricovero',
              'parent' => 3102, 'template' => 'informativo', 'publishedon' => $ts('2025-05-05 10:00:00'), 'editedon' => $ts('2025-05-06 10:00:00')],
             ['infoImage' => 'assets/img/news/abbuffate.png', 'aut.ref' => '777']],
];

/* ---------- MODX finto ---------- */
final class TemplateFinto { public function __construct(private string $n) {} public function get($k) { return $k === 'templatename' ? $this->n : null; } }
final class RisorsaFinta {
    public function __construct(public int $id, private array $f, private array $tv) {}
    public function get($k) {
        $def = ['id' => $this->id, 'published' => 1, 'deleted' => 0, 'class_key' => 'MODX\\Revolution\\modDocument', 'content' => '',
                'longtitle' => '', 'menutitle' => '', 'description' => '', 'introtext' => '', 'publishedon' => 0, 'editedon' => 0,
                'createdon' => 0, 'alias' => basename((string) ($this->f['uri'] ?? ''))];
        return $this->f[$k] ?? $def[$k] ?? null;
    }
    public function getTVValue($n) { return $this->tv[$n] ?? null; }
    public function getOne($rel) { return $rel === 'Template' ? new TemplateFinto((string) ($this->f['template'] ?? '')) : null; }
}
final class ModxFinto {
    public $resource = null;
    public function __construct(private array $s, private array $r) {}
    public function getOption($k, $opts = null, $default = null) {
        if (is_array($opts) && array_key_exists($k, $opts)) return $opts[$k];
        return array_key_exists($k, $this->s) ? $this->s[$k] : $default;
    }
    public function makeUrl($id, $ctx = '', $args = '', $scheme = -1) {
        return isset($this->r[$id]) ? 'https://www.kosgroup.com/' . $this->r[$id]->get('uri') : '';
    }
    public function getObject($cls, $crit) { return is_numeric($crit) ? ($this->r[(int) $crit] ?? null) : null; }
    public function getCollection($cls, $q) { return []; }
    public function prepare($sql) { throw new RuntimeException('database assente nella simulazione'); }
}
$OGG = [];
foreach ($R as $id => [$f, $tv]) $OGG[$id] = new RisorsaFinta($id, $f, $tv);
$modx = new ModxFinto($SETTINGS, $OGG);

$esegui = function (int $id, array $props = []) use ($modx, $LIB) {
    $modx->resource = $modx->getObject('modResource', $id);
    $scriptProperties = $props + ['prefisso' => 'kos', 'pretty' => 1];
    return (function () use ($modx, $scriptProperties, $LIB) { return include $LIB; })();
};

/* ---------- controlli ---------- */
$TIPI = ['WebPage', 'CollectionPage', 'MedicalWebPage', 'ProfilePage', 'WebSite', 'Organization', 'ImageObject', 'PostalAddress',
    'BreadcrumbList', 'ListItem', 'Article', 'NewsArticle', 'BlogPosting', 'Person', 'IndividualPhysician', 'VideoObject',
    'MedicalClinic', 'Hospital', 'MedicalBusiness', 'MedicalOrganization', 'LocalBusiness', 'DiagnosticLab', 'GeoCoordinates',
    'OpeningHoursSpecification', 'Brand', 'EntryPoint', 'ReserveAction', 'MedicalDevice', 'Occupation', 'PropertyValue',
    'EducationalOccupationalCredential', 'ScholarlyArticle', 'MedicalAudience', 'OfferCatalog', 'Offer', 'Service',
    'MedicalTherapy', 'MedicalTest', 'MedicalProcedure', 'MedicalCondition'];
$FRAMMENTI = ['#organization', '#website', '#webpage', '#breadcrumb', '#article', '#person', '#medico', '#localbusiness',
    '#logo', '#video', '#condition', '#device', '#service', '#department'];
$errori = 0;
$ko = function (string $m) use (&$errori) { $errori++; echo "    KO $m\n"; };

/* vocabolario di schema.org (facoltativo): classi con i supertipi, proprietà con i tipi previsti */
$SUP = []; $PROP = [];
if (!empty($opt['vocabolario'])) {
    $ids = fn($x) => $x === null ? [] : (isset($x['@id']) ? [$x['@id']] : array_column($x, '@id'));
    foreach (json_decode((string) file_get_contents($opt['vocabolario']), true)['@graph'] ?? [] as $n) {
        $id = str_replace('schema:', '', (string) $n['@id']);
        $t = (array) $n['@type'];
        if (in_array('rdfs:Class', $t, true)) $SUP[$id] = str_replace('schema:', '', $ids($n['rdfs:subClassOf'] ?? null));
        if (in_array('rdf:Property', $t, true)) $PROP[$id] = str_replace('schema:', '', $ids($n['schema:domainIncludes'] ?? null));
    }
    if (!$SUP) { fwrite(STDERR, "Vocabolario non leggibile\n"); exit(1); }
}
$antenati = function (string $t) use (&$antenati, $SUP) {
    $out = [$t => true];
    foreach ($SUP[$t] ?? [] as $s) $out += $antenati($s);
    return $out;
};
$vocabolario = function ($x) use (&$vocabolario, $SUP, $PROP, $antenati, $ko) {
    if (!$SUP || !is_array($x)) return;
    if (isset($x['@type'])) {
        $a = [];
        foreach ((array) $x['@type'] as $t) { if (!isset($SUP[$t])) $ko("tipo inesistente su schema.org: $t"); $a += $antenati($t); }
        foreach ($x as $k => $v) {
            if (!is_string($k) || $k[0] === '@') continue;
            if (!isset($PROP[$k])) $ko("proprietà inesistente su schema.org: $k");
            elseif (!array_intersect($PROP[$k], array_keys($a))) $ko("$k non prevista per " . implode('+', (array) $x['@type']));
        }
    }
    foreach ($x as $v) $vocabolario($v);
};
$isoOk = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $d);

$controlla = function (int $id, string $html, array $atteso) use ($ko, $TIPI, $FRAMMENTI, $isoOk, $vocabolario) {
    if (substr_count($html, 'application/ld+json') !== 1) { $ko('blocchi JSON-LD: ' . substr_count($html, 'application/ld+json')); return null; }
    $j = json_decode(preg_replace('#^.*?<script type="application/ld\+json">(.*)</script>.*$#s', '$1', $html), true);
    if (!is_array($j) || ($j['@context'] ?? '') !== 'https://schema.org' || !isset($j['@graph'])) { $ko('JSON non valido o senza @graph'); return null; }
    $g = $j['@graph'];
    $perId = [];
    foreach ($g as $n) {
        if (!isset($n['@id'])) { $ko('nodo senza @id: ' . json_encode($n['@type'] ?? '?')); continue; }
        if (isset($perId[$n['@id']])) $ko('@id duplicato ' . $n['@id']);
        $perId[$n['@id']] = $n;
    }
    // nodi descritti dentro altri nodi (es. il logo dentro Organization)
    $annidati = function ($x) use (&$annidati, &$perId) {
        if (!is_array($x)) return;
        if (isset($x['@id']) && count($x) > 1 && !isset($perId[$x['@id']])) $perId[$x['@id']] = $x;
        foreach ($x as $v) $annidati($v);
    };
    $annidati($g);
    // tipi e @id in tutto l'albero
    $giro = function ($x) use (&$giro, $ko, $TIPI, $FRAMMENTI, $perId) {
        if (!is_array($x)) return;
        if (isset($x['@type'])) foreach ((array) $x['@type'] as $t) if (!in_array($t, $TIPI, true)) $ko("tipo sconosciuto $t");
        if (isset($x['@id'])) {
            $id = $x['@id'];
            if (!preg_match('#^https://(kosgroup\.com|anniazzurri\.it)/#', $id)) { if (!str_starts_with($id, 'https://doi.org/')) $ko("@id fuori dall'host canonico: $id"); }
            elseif (!in_array(strrchr($id, '#') ?: '-', $FRAMMENTI, true) && !preg_match('/#\d{11}$/', $id)) $ko("frammento sconosciuto: $id");
            if (count($x) === 1 && !isset($perId[$id]) && !preg_match('/#(localbusiness|medico|person|organization|article|device|condition)$/', $id)) $ko("riferimento non risolto: $id");
        }
        foreach ($x as $v) $giro($v);
    };
    $giro($g);
    $vocabolario($g);
    $tipi = array_map(fn($n) => implode('+', (array) ($n['@type'] ?? [])), $g);
    if ($tipi !== $atteso['tipi']) $ko('tipi ' . implode(',', $tipi) . ' invece di ' . implode(',', $atteso['tipi']));

    foreach ($g as $n) {
        $t = (array) ($n['@type'] ?? []);
        if (array_intersect($t, ['Article', 'NewsArticle', 'BlogPosting'])) {
            foreach (['headline', 'datePublished', 'dateModified', 'author', 'publisher', 'mainEntityOfPage', 'inLanguage'] as $c)
                if (empty($n[$c])) $ko("articolo senza $c");
            if (mb_strlen((string) ($n['headline'] ?? '')) > 110) $ko('headline oltre 110 caratteri');
            if (str_contains((string) ($n['headline'] ?? ''), '| KOS')) $ko('headline con il marchio del title');
            if (!$isoOk($n['datePublished'] ?? null) || !$isoOk($n['dateModified'] ?? null)) $ko('date non ISO 8601 con fuso');
            elseif (strtotime($n['dateModified']) < strtotime($n['datePublished'])) $ko('dateModified precedente a datePublished');
            $a = $n['author'] ?? [];
            if (empty($a['name']) || empty($a['url'])) $ko('author senza name o url');
            if (isset($a['@id']) && !isset($perId[$a['@id']])) $ko('author non descritto nel grafo');
            if (($n['publisher']['@id'] ?? '') === '' || !isset($perId[$n['publisher']['@id']])) $ko('publisher non descritto nel grafo');
            if (!empty($n['image'])) {
                if (!preg_match('#^https://kosgroup\.com/[^ ]+$#', (string) ($n['image']['url'] ?? ''))) $ko('image.url non assoluto o con spazi');
                if (($n['image']['width'] ?? 0) * ($n['image']['height'] ?? 0) < 50000) $ko('immagine sotto i 50.000 pixel');
            }
            if (($atteso['immagine'] ?? true) && empty($n['image'])) $ko('articolo senza image');
        }
        if (in_array('VideoObject', $t, true)) {
            foreach (['name', 'thumbnailUrl', 'uploadDate', 'embedUrl', 'description'] as $c) if (empty($n[$c])) $ko("video senza $c");
            if (!$isoOk($n['uploadDate'] ?? null)) $ko('uploadDate non ISO 8601 con fuso');
            if (!preg_match('#^https://www\.youtube\.com/embed/[A-Za-z0-9_-]{11}$#', (string) ($n['embedUrl'] ?? ''))) $ko('embedUrl non valido');
        }
    }
    foreach ($atteso['contiene'] ?? [] as $s) if (!str_contains(json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $s)) $ko("manca $s");
    foreach ($atteso['non_contiene'] ?? [] as $s) if (str_contains(json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $s)) $ko("non deve contenere $s");
    return $j;
};

/* ---------- casi ---------- */
$CASI = [
    892  => ['nome' => 'approfondimento (Article, struttura collegata con indirizzo e una senza)',
             'tipi' => ['Article', 'WebPage', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"articleSection":"Approfondimenti"', '"mentions":[{"@type":"Hospital","@id":"https://kosgroup.com/it/centri/villa-dei-pini-civitanova#localbusiness"',
                            '"headline":"Abbuffate: cosa sono, come riconoscerle e come affrontarle"', '"width":1200'],
             'non_contiene' => ['senza-indirizzo#localbusiness', 'www.kosgroup.com']],
    3105 => ['nome' => 'news (NewsArticle, description dall\'introtext, immagine con spazio nel nome, pubblicazione programmata)',
             'tipi' => ['NewsArticle', 'WebPage', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"articleSection":"News"', 'premio%20kos.png', '"description":"Il premio è stato assegnato a Milano."']],
    3205 => ['nome' => 'evento senza immagine esistente', 'immagine' => false,
             'tipi' => ['Article', 'WebPage', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"articleSection":"Eventi e convegni"'], 'non_contiene' => ['non-esiste.jpg']],
    3106 => ['nome' => 'approfondimento con autore medico (TV aut.ref, oggi assente su kosgroup.com)',
             'tipi' => ['Article', 'WebPage', 'IndividualPhysician', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"author":{"@type":"IndividualPhysician","@id":"https://kosgroup.com/it/chi-siamo/qualita-delle-cure/medici/fabio-conti#medico"']],
    3405 => ['nome' => 'video (VideoObject)',
             'tipi' => ['WebPage', 'VideoObject', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"embedUrl":"https://www.youtube.com/embed/AbCdEfGhIj0"', '"uploadDate":"2025-02-18T00:00:00+01:00"']],
    600  => ['nome' => 'template informativo fuori dalle sezioni: resta WebPage',
             'tipi' => ['WebPage', 'BreadcrumbList', 'WebSite', 'Organization']],
    218  => ['nome' => 'scheda struttura (invariata)',
             'tipi' => ['WebPage', 'Hospital', 'BreadcrumbList', 'WebSite', 'Organization']],
    777  => ['nome' => 'scheda medico (IndividualPhysician, specialità dal ruolo, practicesAt verso la struttura)',
             'tipi' => ['WebPage', 'IndividualPhysician', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"mainEntity":{"@id":"https://kosgroup.com/it/chi-siamo/qualita-delle-cure/medici/fabio-conti#medico"}',
                            '"medicalSpecialty":["Psychiatric"]', '"address":{"@type":"PostalAddress","streetAddress":"Via di prova 1"', '"telephone":"+390000000000"', '"practicesAt":[{"@type":"Hospital","@id":"https://kosgroup.com/it/centri/villa-dei-pini-civitanova#localbusiness"'],
             'non_contiene' => ['"jobTitle"', '"worksFor"']],
    778  => ['nome' => 'scheda fisioterapista (Person, jobTitle, worksFor verso la struttura)',
             'tipi' => ['WebPage', 'Person', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['anna-rossi#person', '"jobTitle":"Fisioterapista"', '"worksFor":[{"@type":"Hospital"', '"description":"Fisioterapista a Villa dei Pini."'],
             'non_contiene' => ['practicesAt', 'IndividualPhysician']],
    779  => ['nome' => 'scheda medico con sede senza nodo: Person (niente IndividualPhysician senza indirizzo)',
             'tipi' => ['WebPage', 'Person', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['luca-bianchi#person', '"jobTitle":"Neurologo e Neuroradiologo"', '"worksFor":{"@id":"https://kosgroup.com/#organization"}'],
             'non_contiene' => ['practicesAt', 'IndividualPhysician']],
    780  => ['nome' => 'scheda psicologa con sede senza nodo (Person, worksFor verso l\'ente)',
             'tipi' => ['WebPage', 'Person', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"worksFor":{"@id":"https://kosgroup.com/#organization"}']],
    1500 => ['nome' => 'sede su landing (Hospital con department verso le due unità)',
             'tipi' => ['WebPage', 'Hospital', 'WebSite', 'Organization'],
             'contiene' => ['"@id":"https://kosgroup.com/villamargherita#localbusiness"', '"mainEntity":{"@id":"https://kosgroup.com/villamargherita#localbusiness"}',
                            '"department":[{"@type":"MedicalOrganization","@id":"https://kosgroup.com/it/centri/villa-margherita-vicenza-riabilitazione#department"',
                            'villa-margherita-vicenza-psichiatria#department', '"name":"Villa Margherita Vicenza: Centro di riabilitazione","url"'],
             'non_contiene' => ['| KOS"']],
    1233 => ['nome' => 'unità specialistica (MedicalOrganization #department, sede per intero)',
             'tipi' => ['WebPage', 'MedicalOrganization', 'Hospital', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"mainEntity":{"@id":"https://kosgroup.com/it/centri/villa-margherita-vicenza-riabilitazione#department"}',
                            '"parentOrganization":{"@id":"https://kosgroup.com/villamargherita#localbusiness"}', '"telephone":"+39044415851"',
                            '"name":"Villa Margherita Vicenza: Centro di riabilitazione"'],
             'non_contiene' => ['villa-margherita-vicenza-riabilitazione#localbusiness']],
    781  => ['nome' => 'medico di un\'unità: IndividualPhysician con practicesAt e indirizzo della sede',
             'tipi' => ['WebPage', 'IndividualPhysician', 'BreadcrumbList', 'WebSite', 'Organization'],
             'contiene' => ['"practicesAt":[{"@type":"Hospital","@id":"https://kosgroup.com/villamargherita#localbusiness"', '"streetAddress":"Via Costacolonna 6"'],
             'non_contiene' => ['"worksFor"']],
    1    => ['nome' => 'home (invariata)', 'tipi' => ['WebPage', 'WebSite', 'Organization']],
];

$soloJson = isset($opt['json']);
foreach ($CASI as $id => $a) {
    $html = (string) $esegui($id);
    if ($soloJson) { echo "== $id\n$html\n"; continue; }
    echo "== $id {$a['nome']}\n";
    $j = $controlla($id, $html, $a);
    if ($j && (string) ($opt['mostra'] ?? '') === (string) $id) echo json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}
// il box autore e le briciole non cambiano: stessa funzione
if (!$soloJson) {
    $b = (string) $esegui(892, ['type' => 'briciole']);
    echo "== 892 briciole visibili\n";
    if (!str_contains($b, '<a href="/it/news-e-media/approfondimenti">Approfondimenti</a>')) $ko('briciole visibili diverse dal previsto: ' . $b);
    echo $errori ? "\n$errori controlli falliti\n" : "\nTutti i controlli superati\n";
}
exit($errori ? 1 : 0);
