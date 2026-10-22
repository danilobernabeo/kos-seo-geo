<?php
/**
 * aaSchema — Anni Azzurri
 * -----------------------------------------------------------------------------
 * Costruisce il JSON-LD delle pagine e il box autore visibile.
 * Un solo snippet: la logica dell'@id dell'entità persona sta in un posto solo.
 *
 * USO
 *   tutte le pagine, una sola volta (chunk footer-script*):   [[aaSchema]]
 *       il tipo si ricava dal template: articolo, pagina autore o pagina generica
 *   box autore, nel template articolo:                       [[aaSchema? &type=`box`]]
 *
 *   Sostituisce i tre JSON-LD scritti a mano nei footer (Organization, WebSite,
 *   BreadcrumbList): un solo @graph per pagina, emesso lato server (voce AA-8).
 *
 * PARAMETRI
 *   &type     auto | page | article | profile | box   (default: auto)
 *   &author   id risorsa autore; se omesso lo legge dalla TV aut.ref
 *   &pretty   1 = JSON indentato (solo per debug)
 *
 * RITORNA
 *   article/profile → il blocco <script type="application/ld+json">
 *   box             → l'HTML del box autore
 *
 * REGOLA CARDINE
 *   TV aut.ref vuota  =  contenuto della redazione  =  author è l'Organization.
 *   Nessuna Person inventata per "La Redazione".
 * -----------------------------------------------------------------------------
 */

/* --- compatibilità MODX 2 / 3 ---------------------------------------------- */
$CLS_RES = class_exists('\MODX\Revolution\modResource')
    ? \MODX\Revolution\modResource::class : 'modResource';
$CLS_TV = class_exists('\MODX\Revolution\modTemplateVar')
    ? \MODX\Revolution\modTemplateVar::class : 'modTemplateVar';
$CLS_TVR = class_exists('\MODX\Revolution\modTemplateVarResource')
    ? \MODX\Revolution\modTemplateVarResource::class : 'modTemplateVarResource';

/* --- impostazioni ----------------------------------------------------------
 * Tutte sovrascrivibili da Sistema → Impostazioni di sistema, namespace "aa".
 * Nessun valore derivato da site_url: su due siti diversi deve restare lo stesso.
 */
$cfg = function ($chiave, $default) use ($modx) {
    $v = $modx->getOption('aa.' . $chiave, null, null);
    return ($v === null || $v === '') ? $default : $v;
};

/* Base canonica: UNICA fonte della forma del dominio (con o senza www).
 * Ogni @id emesso da questo snippet ne deriva, così articoli, persone e
 * organizzazione non possono finire su host diversi — che nel grafo
 * significherebbe entità duplicate. */
$CANON = rtrim($cfg('canonical_base', 'https://anniazzurri.it'), '/');

$ORG_ID   = $cfg('org_id',     $CANON . '/#organization');
$SITE_ID  = $cfg('website_id', $CANON . '/#website');
$PARENT_ID= $cfg('parent_org_id', 'https://kosgroup.com/#organization');
$LANG     = $cfg('lang',      'it-IT');
$SLASH    = (int) $cfg('trailing_slash', 0);   // gli URL del sito sono piatti, senza slash finale
$FRAG_PAT = $cfg('frag_patologia', '#condition');
$FRAG_STR = $cfg('frag_struttura', '#struttura');   // in decisione: #struttura o #localbusiness

$csv = function ($v) {
    return array_values(array_filter(array_map('trim', explode(',', (string) $v)), 'strlen'));
};
$START = (int) $modx->getOption('site_start', null, 1);
/* Esclusioni delle briciole: la STESSA impostazione usata dallo snippet BreadCrumb
 * nel chunk breadcrumb-stripe (&exclude=`[[++aa.breadcrumb_exclude]]`), così
 * briciole visibili e BreadcrumbList hanno sempre lo stesso percorso. */
$ESCLUDI = array_map('intval', $csv($cfg('breadcrumb_exclude', '2,4,48,54,55,68,78,85,93,97,98,100,109,156')));
/* Template per tipo di pagina (nomi, separati da virgola). */
$TPL_ARTICOLO   = $csv($cfg('tpl_article',    'interna-articolo'));
$TPL_PROFILO    = $csv($cfg('tpl_profile',    'autore'));
$TPL_COLLEZIONE = $csv($cfg('tpl_collection', 'regione,provincia,residenze,news,le-nostre-storie-indice'));

/* --- helper ---------------------------------------------------------------- */

/** URL assoluto di una risorsa, riportato alla forma canonica del dominio. */
$url = function ($id) use ($modx, $SLASH, $CANON) {
    // makeUrl con scheme 'full' restituisce GIÀ l'URL assoluto: non anteporre site_url.
    $u = $modx->makeUrl((int) $id, '', '', 'full');
    if (!$u) return '';
    // site_url può avere una forma di host diversa da quella canonica (www / non www):
    // la sostituiamo sempre, altrimenti gli @id degli articoli divergono da quello
    // dell'organizzazione e nel grafo nascono due siti distinti.
    $u = preg_replace('#^https?://[^/]+#i', $CANON, $u);
    return $SLASH ? rtrim($u, '/') . '/' : rtrim($u, '/');
};

/** Righe non vuote da una textarea. */
$righe = function ($v) {
    $r = preg_split('/\r\n|\r|\n/', (string) $v);
    return array_values(array_filter(array_map('trim', $r), 'strlen'));
};

/** Valore TV pulito. */
$tv = function ($res, $nome) {
    if (!$res) return '';
    $v = $res->getTVValue($nome);
    return is_string($v) ? trim($v) : $v;
};

/** Testo da un campo richtext: niente tag, niente spazi doppi. */
$testo = function ($html) {
    $t = strip_tags((string) $html);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $t));
};

/** ImageObject da un percorso TV, con dimensioni reali se il file è leggibile. */
$immagine = function ($percorso) use ($modx, $CANON) {
    $percorso = trim((string) $percorso);
    if ($percorso === '') return null;

    $abs = preg_match('#^https?://#i', $percorso)
        ? $percorso
        : $CANON . '/' . ltrim($percorso, '/');

    $img = ['@type' => 'ImageObject', 'url' => $abs];

    if (!preg_match('#^https?://#i', $percorso)) {
        $file = MODX_BASE_PATH . ltrim($percorso, '/');
        if (is_readable($file) && ($d = @getimagesize($file))) {
            $img['width']  = (int) $d[0];
            $img['height'] = (int) $d[1];
        }
    }
    return $img;
};

/** Data ISO 8601 con fuso. In MODX 3 get('publishedon'/'editedon') restituisce la data
 *  già formattata ("2025-03-04 10:12:00"), non il timestamp: si accettano entrambi. */
$data = function ($v) use ($cfg) {
    if ($v === null || $v === '' || $v === '0' || $v === 0) return null;
    // La stringa formattata da MODX è nel fuso del server (MODX Cloud: America/Chicago):
    // si interpreta in quel fuso e si emette nel fuso del sito, così l'offset è +01:00/+02:00.
    $ts = is_numeric($v) ? (int) $v : strtotime((string) $v);
    if ($ts === false || $ts <= 0) return null;
    $d = new DateTime('@' . $ts);
    $d->setTimezone(new DateTimeZone($cfg('timezone', 'Europe/Rome')));
    return $d->format('c');
};

/** Toglie ricorsivamente null, stringhe vuote e array vuoti. */
$pulisci = function ($a) use (&$pulisci) {
    if (!is_array($a)) return $a;
    $out = [];
    foreach ($a as $k => $v) {
        if (is_array($v)) $v = $pulisci($v);
        if ($v === null || $v === '' || $v === []) continue;
        $out[$k] = $v;
    }
    return $out;
};

/* --- nodo Organization ------------------------------------------------------
 * Emesso per intero su ogni pagina: è anche il valore di author sui contenuti
 * della redazione, quindi deve essere risolvibile senza seguire altri @id.
 */
$org = function () use ($cfg, $csv, $ORG_ID, $PARENT_ID, $CANON, $pulisci) {

    // Logo con @id proprio, URL assoluto (il footer storico aveva una doppia barra).
    $logo = null;
    if ($l = $cfg('org_logo', '/assets/logo-anni.svg')) {
        $logo = [
            '@type' => 'ImageObject',
            '@id'   => $CANON . '/#logo',
            'url'   => preg_match('#^https?://#i', $l) ? $l : $CANON . '/' . ltrim($l, '/'),
        ];
    }
    // Profili social ufficiali (impostazione aa.org_sameAs, separati da virgola).
    // Mai kosgroup.com: è un'altra società, collegata con parentOrganization.
    $social = $csv($cfg('org_sameAs', ''));

    // KOS Care S.r.l. e KOS S.p.A. sono soggetti giuridici distinti, con partite
    // IVA diverse: il legame è di controllo, non di identità. Quindi
    // parentOrganization, MAI sameAs — che le dichiarerebbe la stessa entità.
    $parent = ['@id' => $PARENT_ID];
    if ($nome = $cfg('parent_org_name', 'KOS S.p.A.')) {
        $parent['@type']     = 'Organization';
        $parent['name']      = $nome;
        $parent['legalName'] = $nome;
        $parent['vatID']     = $cfg('parent_org_vat', 'IT10674520159');
        $parent['url']       = $cfg('parent_org_url', 'https://kosgroup.com/it');
    }

    return $pulisci([
        '@type'              => 'Organization',
        '@id'                => $ORG_ID,
        'name'               => $cfg('org_name',  'Anni Azzurri'),
        'legalName'          => $cfg('org_legal', 'KOS Care S.r.l.'),
        'vatID'              => $cfg('org_vat',   'IT01148190547'),
        'url'                => $cfg('org_url',   $CANON . '/'),
        'logo'               => $logo,
        // image: foto rappresentativa (aa.org_image) o, in mancanza, il logo per riferimento.
        'image'              => ($img = $cfg('org_image', ''))
                                    ? (preg_match('#^https?://#i', $img) ? $img : $CANON . '/' . ltrim($img, '/'))
                                    : ($logo ? ['@id' => $logo['@id']] : null),
        'telephone'          => $cfg('org_phone', ''),
        'sameAs'             => $social,
        'parentOrganization' => $parent,
        'address'            => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $cfg('org_street', 'Via Durini, 9'),
            'postalCode'      => $cfg('org_zip',    '20122'),
            'addressLocality' => $cfg('org_city',   'Milano'),
            'addressCountry'  => 'IT',
        ],
    ]);
};

/* --- nodo Person ------------------------------------------------------------
 * Restituisce null se la risorsa autore non esiste o non è pubblicata:
 * il chiamante ricade sull'Organization.
 */
$persona = function ($idAutore) use (
    $modx, $CLS_RES, $url, $tv, $righe, $testo, $immagine, $pulisci, $ORG_ID, $FRAG_STR
) {
    $idAutore = (int) $idAutore;
    if ($idAutore <= 0) return null;

    $a = $modx->getObject($CLS_RES, $idAutore);
    if (!$a || !$a->get('published') || $a->get('deleted')) return null;

    $pagina = $url($idAutore);
    if ($pagina === '') return null;

    $nome = trim($tv($a, 'aut.given') . ' ' . $tv($a, 'aut.family'));
    if ($nome === '') $nome = $a->get('pagetitle');

    $p = [
        '@type'       => 'Person',
        '@id'         => $pagina . '#person',
        'name'        => $nome,
        'givenName'   => $tv($a, 'aut.given'),
        'familyName'  => $tv($a, 'aut.family'),
        'url'         => $pagina,
        'jobTitle'    => $tv($a, 'aut.jobTitle'),
        'description' => $testo($tv($a, 'aut.bio')),
        'worksFor'    => ['@id' => $ORG_ID],
        'image'       => $immagine($tv($a, 'aut.foto')),
    ];

    // honorificPrefix solo se valorizzato: su Antonella Schiavino resta vuoto.
    if ($h = $tv($a, 'aut.honorific')) $p['honorificPrefix'] = $h;

    if ($prof = $tv($a, 'aut.professione')) {
        $p['hasOccupation'] = [
            '@type' => 'Occupation',
            'name'  => $prof,
            'occupationalCategory' => $tv($a, 'aut.professione_cat'),
        ];
    }

    if ($k = $righe($tv($a, 'aut.knowsAbout'))) $p['knowsAbout'] = $k;

    // Struttura di afferenza: aggancia la persona all'entità residenza.
    if ($aff = (int) $tv($a, 'aut.affiliazione')) {
        if ($u = $url($aff)) $p['affiliation'] = ['@id' => $u . $FRAG_STR];
    }

    // Iscrizione all'albo: identificativo verificabile, più la credenziale.
    $albo = $tv($a, 'aut.albo_nome');
    $num  = $tv($a, 'aut.albo_numero');
    if ($albo !== '' && $num !== '') {
        $p['identifier'] = [
            '@type'      => 'PropertyValue',
            'propertyID' => $albo,
            'value'      => $num,
        ];
        $p['hasCredential'][] = [
            '@type'              => 'EducationalOccupationalCredential',
            'credentialCategory' => 'ProfessionalCertification',
            'name'               => 'Iscrizione ' . $albo . ', n. ' . $num,
            'identifier'         => $num,
            'recognizedBy'       => ['@type' => 'Organization', 'name' => $albo],
        ];
        $p['memberOf'] = ['@type' => 'Organization', 'name' => $albo];
    }

    // Titoli di studio: una riga per titolo, con anno facoltativo dopo "|".
    foreach ($righe($tv($a, 'aut.credential')) as $c) {
        $parti = array_map('trim', explode('|', $c, 2));
        $p['hasCredential'][] = [
            '@type'              => 'EducationalOccupationalCredential',
            'credentialCategory' => 'degree',
            'name'               => $parti[0],
            'dateCreated'        => $parti[1] ?? null,
        ];
    }

    $sameAs = $righe($tv($a, 'aut.sameAs'));

    // Pubblicazione scientifica: è la credenziale più forte, va anche in sameAs.
    $doi = $tv($a, 'aut.pub_doi');
    if ($titolo = $tv($a, 'aut.pub_titolo')) {
        $pub = [
            '@type'         => 'ScholarlyArticle',
            'name'          => $titolo,
            'datePublished' => $tv($a, 'aut.pub_data'),
            'url'           => $tv($a, 'aut.pub_url'),
            'author'        => ['@id' => $pagina . '#person'],
        ];
        if ($doi !== '') {
            $pub['@id'] = 'https://doi.org/' . $doi;
            $pub['identifier'] = ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $doi];
            $sameAs[] = 'https://doi.org/' . $doi;
        }
        $p['subjectOf'] = $pub;
    }

    if ($sameAs) $p['sameAs'] = array_values(array_unique($sameAs));

    return $pulisci($p);
};

/* --- briciole di pane -------------------------------------------------------
 * Stesse regole dello snippet BreadCrumb del chunk breadcrumb-stripe:
 * - si risale l'albero MODX fino alla radice (gli URL sono piatti, l'albero no);
 * - si saltano la home (aggiunta in testa) e le risorse in aa.breadcrumb_exclude;
 * - risorse nascoste o non pubblicate restano, come fa BreadCrumb per default;
 * - pagetitle per home e livelli intermedi, menutitle (o pagetitle) per la pagina corrente;
 * - URL assoluti nella forma canonica.
 * Sulla home non si emette nessuna BreadcrumbList.
 */
$briciole = function ($res) use ($modx, $url, $CLS_RES, $START, $ESCLUDI, $CANON) {
    $id = (int) $res->get('id');
    if ($id === $START) return [];

    $catena = [];
    $rid = $id;
    for ($giri = 0; $rid > 0 && $giri < 100; $giri++) {
        $r = ($rid === $id) ? $res : $modx->getObject($CLS_RES, $rid);
        if (!$r) break;
        if ($rid !== $START && !in_array($rid, $ESCLUDI, true)) array_unshift($catena, $r);
        $rid = (int) $r->get('parent');
    }

    $voci = [];
    if ($home = $modx->getObject($CLS_RES, $START)) {
        $voci[] = ['name' => $home->get('pagetitle'), 'item' => $CANON . '/'];
    }
    foreach ($catena as $r) {
        $corrente = ((int) $r->get('id') === $id);
        $voci[] = [
            'name' => $corrente ? ($r->get('menutitle') ?: $r->get('pagetitle')) : $r->get('pagetitle'),
            'item' => $url($r->get('id')),
        ];
    }

    $lista = [];
    foreach ($voci as $i => $v) {
        $lista[] = array_filter([
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $v['name'],
            'item'     => $v['item'],
        ], function ($x) { return $x !== null && $x !== ''; });
    }
    return $lista;
};

/* Nodo BreadcrumbList, o null se non ci sono almeno due livelli. */
$nodoBriciole = function ($res, $pagina) use ($briciole) {
    $l = $briciole($res);
    return count($l) < 2 ? null : [
        '@type'           => 'BreadcrumbList',
        '@id'             => $pagina . '#breadcrumb',
        'itemListElement' => $l,
    ];
};

/* Nodo WebSite. */
$sito = function () use ($cfg, $CANON, $SITE_ID, $ORG_ID, $LANG) {
    return [
        '@type'      => 'WebSite',
        '@id'        => $SITE_ID,
        'url'        => $cfg('org_url', $CANON . '/'),
        'name'       => $cfg('org_name', 'Anni Azzurri'),
        'inLanguage' => $LANG,
        'publisher'  => ['@id' => $ORG_ID],
    ];
};

/* --- riferimenti a patologie e strutture ------------------------------------ */
$riferimenti = function ($csv, $frammento) use ($url) {
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', (string) $csv)), 'strlen') as $id) {
        if ($u = $url((int) $id)) $out[] = ['@id' => $u . $frammento];
    }
    return $out;
};

/* =============================================================================
 * ESECUZIONE
 * ========================================================================== */

$type   = strtolower((string) $modx->getOption('type', $scriptProperties, 'auto'));
$pretty = (int) $modx->getOption('pretty', $scriptProperties, 0);
$res    = $modx->resource;
if (!$res) return '';

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
if ($pretty) $flags |= JSON_PRETTY_PRINT;

$paginaCorrente = $url($res->get('id'));
if ((int) $res->get('id') === $START) $paginaCorrente = $CANON . '/';

if ($type === 'auto') {
    $tpl = $res->getOne('Template');
    $nomeTpl = $tpl ? $tpl->get('templatename') : '';
    $type = in_array($nomeTpl, $TPL_ARTICOLO, true) ? 'article'
          : (in_array($nomeTpl, $TPL_PROFILO, true) ? 'profile' : 'page');
}
$json = function ($grafo) use ($flags) {
    return '<script type="application/ld+json">'
         . json_encode(['@context' => 'https://schema.org', '@graph' => array_values(array_filter($grafo))], $flags)
         . '</script>';
};

/* --- autore della pagina corrente ------------------------------------------ */
$idAutore = (int) $modx->getOption('author', $scriptProperties, $tv($res, 'aut.ref'));
$autore   = $persona($idAutore);

/* ---------------------------------------------------------------- box autore */
if ($type === 'box') {
    $nomeOrg = $cfg('org_name', 'Anni Azzurri');

    // Redazione: nessuna foto, nessuna credenziale, solo l'attribuzione.
    if (!$autore) {
        return '<div class="box-autore box-autore--redazione">'
             . '<p class="box-autore__firma">A cura della Redazione ' . htmlspecialchars($nomeOrg, ENT_QUOTES, 'UTF-8') . '</p>'
             . '</div>';
    }

    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $a = $modx->getObject($CLS_RES, $idAutore);

    $h  = '<div class="box-autore">';
    if (!empty($autore['image']['url'])) {
        $h .= '<img class="box-autore__foto" src="' . $e($autore['image']['url']) . '"'
            . ' alt="' . $e($autore['name']) . '" width="120" height="120" loading="lazy">';
    }
    $h .= '<div class="box-autore__testo">';
    $h .= '<p class="box-autore__nome"><a href="' . $e($autore['url']) . '">' . $e($autore['name']) . '</a></p>';
    if (!empty($autore['jobTitle'])) {
        $h .= '<p class="box-autore__ruolo">' . $e($autore['jobTitle']) . '</p>';
    }
    if (!empty($autore['description'])) {
        $h .= '<p class="box-autore__bio">' . $e($autore['description']) . '</p>';
    }
    if (!empty($autore['identifier']['value'])) {
        $h .= '<p class="box-autore__albo">' . $e($autore['identifier']['propertyID'])
            . ' — n. ' . $e($autore['identifier']['value']) . '</p>';
    }
    if (!empty($autore['sameAs'])) {
        $h .= '<ul class="box-autore__link">';
        foreach ((array) $autore['sameAs'] as $u) {
            $etichetta = (strpos($u, 'doi.org') !== false) ? 'Pubblicazione scientifica'
                      : ((strpos($u, 'linkedin') !== false) ? 'Profilo LinkedIn' : 'Profilo');
            $h .= '<li><a href="' . $e($u) . '" rel="noopener">' . $etichetta . '</a></li>';
        }
        $h .= '</ul>';
    }
    $h .= '<p class="box-autore__profilo"><a href="' . $e($autore['url']) . '">Scheda completa</a></p>';
    $h .= '</div></div>';

    // Revisione clinica, se presente.
    if ($revisore = $persona((int) $tv($res, 'rev.ref'))) {
        $quando = $tv($res, 'rev.data');
        $h .= '<p class="box-revisione">Revisione clinica a cura di '
            . '<a href="' . $e($revisore['url']) . '">' . $e($revisore['name']) . '</a>'
            . ($revisore['jobTitle'] ? ', ' . $e($revisore['jobTitle']) : '')
            . ($quando ? ' — ' . $e($quando) : '') . '</p>';
    }

    return $h;
}

/* ------------------------------------------------------------ pagina autore */
if ($type === 'profile') {
    $io = $persona($res->get('id'));
    if (!$io) return '';

    // Articoli firmati: risorse la cui TV aut.ref vale l'id di questa pagina.
    $firmati = [];
    if ($tvRef = $modx->getObject($CLS_TV, ['name' => 'aut.ref'])) {
        $legami = $modx->getCollection($CLS_TVR, [
            'tmplvarid' => $tvRef->get('id'),
            'value'     => (string) $res->get('id'),
        ]);
        $ids = [];
        foreach ($legami as $l) $ids[] = (int) $l->get('contentid');

        if ($ids) {
            $q = $modx->newQuery($CLS_RES);
            $q->where(['id:IN' => $ids, 'published' => 1, 'deleted' => 0]);
            $q->sortby('publishedon', 'DESC');
            foreach ($modx->getCollection($CLS_RES, $q) as $r) {
                if ($u = $url($r->get('id'))) $firmati[] = ['@id' => $u . '#article'];
            }
        }
    }

    $grafo = [
        $pulisci([
            '@type'      => 'ProfilePage',
            '@id'        => $paginaCorrente . '#webpage',
            'url'        => $paginaCorrente,
            'name'       => $res->get('longtitle') ?: $res->get('pagetitle'),
            'inLanguage' => $LANG,
            'mainEntity' => ['@id' => $paginaCorrente . '#person'],
            'hasPart'    => $firmati,
            'isPartOf'   => ['@id' => $SITE_ID],
            'breadcrumb' => ['@id' => $paginaCorrente . '#breadcrumb'],
        ]),
        $io,
        $nodoBriciole($res, $paginaCorrente),
        $sito(),
        $org(),
    ];
    return $json($grafo);
}

/* --------------------------------------------------------- pagina generica */
if ($type === 'page') {
    $tpl = $res->getOne('Template');
    $nomeTpl = $tpl ? $tpl->get('templatename') : '';
    $home = ((int) $res->get('id') === $START);
    $br = $nodoBriciole($res, $paginaCorrente);

    $pagina = $pulisci([
        '@type'       => in_array($nomeTpl, $TPL_COLLEZIONE, true) ? 'CollectionPage' : 'WebPage',
        '@id'         => $paginaCorrente . '#webpage',
        'url'         => $paginaCorrente,
        'name'        => $res->get('longtitle') ?: $res->get('pagetitle'),
        'description' => $res->get('description'),
        'inLanguage'  => $LANG,
        'isPartOf'    => ['@id' => $SITE_ID],
        'about'       => $home ? ['@id' => $ORG_ID] : null,
        'breadcrumb'  => $br ? ['@id' => $paginaCorrente . '#breadcrumb'] : null,
        'dateModified'=> $data($res->get('editedon') ?: $res->get('publishedon')),
    ]);
    return $json([$pagina, $br, $sito(), $org()]);
}

/* ---------------------------------------------------------------- articolo */

$sanitario = (int) $tv($res, 'art.medical') === 1;
$revisore  = $persona((int) $tv($res, 'rev.ref'));

$articolo = [
    '@type'            => 'Article',
    '@id'              => $paginaCorrente . '#article',
    'isPartOf'         => ['@id' => $paginaCorrente . '#webpage'],
    'mainEntityOfPage' => ['@id' => $paginaCorrente . '#webpage'],
    'headline'         => $res->get('longtitle') ?: $res->get('pagetitle'),
    'description'      => $res->get('description') ?: $res->get('introtext'),
    'image'            => $immagine($tv($res, 'art.immagine')),
    'datePublished'    => $data($res->get('publishedon')),
    'dateModified'     => $data($res->get('editedon') ?: $res->get('publishedon')),
    'inLanguage'       => $LANG,
    // Qui sta la regola: niente autore assegnato = firma dell'organizzazione.
    'author'           => ['@id' => $autore ? $autore['@id'] : $ORG_ID],
    'publisher'        => ['@id' => $ORG_ID],
    'about'            => $riferimenti($tv($res, 'art.about'), $FRAG_PAT),
    'mentions'         => $riferimenti($tv($res, 'art.mentions'), $FRAG_STR),
];
if ($revisore) $articolo['reviewedBy'] = ['@id' => $revisore['@id']];

$pagina = [
    '@type'      => $sanitario ? 'MedicalWebPage' : 'WebPage',
    '@id'        => $paginaCorrente . '#webpage',
    'url'        => $paginaCorrente,
    'name'       => $res->get('longtitle') ?: $res->get('pagetitle'),
    'inLanguage' => $LANG,
    'isPartOf'   => ['@id' => $SITE_ID],
];
if ($nodoBr = $nodoBriciole($res, $paginaCorrente)) $pagina['breadcrumb'] = ['@id' => $paginaCorrente . '#breadcrumb'];
if ($sanitario) {
    $pagina['audience'] = ['@type' => 'MedicalAudience', 'audienceType' => 'Patient'];
    if ($rev = $tv($res, 'rev.data')) $pagina['lastReviewed'] = $rev;
}

$grafo = [$pulisci($articolo), $pulisci($pagina)];

// Il nodo Person va ripetuto per intero: i crawler degli assistenti AI
// raramente seguono un @id verso un'altra pagina.
if ($autore)  $grafo[] = $autore;
if ($revisore) $grafo[] = $revisore;

$grafo[] = $nodoBr;
$grafo[] = $sito();
$grafo[] = $org();

return $json($grafo);