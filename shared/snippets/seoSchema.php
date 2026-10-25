<?php
/**
 * seoSchema — libreria comune del dato strutturato (kosgroup.com, anniazzurri.it)
 * -----------------------------------------------------------------------------
 * Costruisce il JSON-LD delle pagine (un solo @graph, lato server), le briciole
 * visibili e il box autore. Nata da aaSchema (anniazzurri.it, voce AA-8) e resa
 * comune per kosgroup.com (voci KOS-9, KOS-12, KOS-13). Installata con il nome del
 * sito (kosSchema, in futuro anche aaSchema) e con la proprietà predefinita
 * &prefisso che sceglie le impostazioni di sistema: kos.* oppure aa.*.
 *
 * USO
 *   tutte le pagine, una sola volta (chunk scripts / footer-script*):  [[kosSchema]]
 *       il tipo si ricava dal template: articolo, pagina autore o pagina generica
 *   briciole visibili (chunk breadcrumb, snippet myBreadcrumb):        [[kosSchema? &type=`briciole`]]
 *   box autore, nel template articolo:                                 [[kosSchema? &type=`box`]]
 *
 * PARAMETRI
 *   &prefisso  prefisso delle impostazioni di sistema (default: aa)
 *   &type      auto | page | article | profile | box | briciole   (default: auto)
 *   &author    id risorsa autore; se omesso lo legge dalla TV aut.ref
 *   &pretty    1 = JSON indentato (solo per debug)
 *
 * STRUTTURE (voce 24 KosGroup; anniazzurri.it dallo sprint 3)
 *   Sulle pagine dei template <prefisso>.tpl_struttura la pagina ha come mainEntity il nodo
 *   {URL scheda}#localbusiness, costruito dalle TV anagrafiche str.* (importate da Yext/RetailTune);
 *   sulle landing (<prefisso>.tpl_landing) la pagina è «about» la struttura indicata in lnd.struttura,
 *   con i servizi della landing (lnd.servizi). Acceso solo con <prefisso>.struttura_attiva = 1: va
 *   attivato nello stesso rilascio in cui si toglie lo script Yext che descrive la stessa struttura.
 *
 * REGOLE
 *   - Briciole visibili e BreadcrumbList escono dalla STESSA funzione: stesso percorso.
 *   - TV aut.ref vuota = contenuto della redazione = author è l'Organization.
 *     Nessuna Person inventata per "La Redazione".
 *   - Ogni @id deriva da <prefisso>.canonical_base (convenzione degli identificativi).
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
 * Tutte sovrascrivibili da Sistema → Impostazioni di sistema, con il prefisso del
 * sito (aa.* su anniazzurri.it, kos.* su kosgroup.com). I valori predefiniti qui
 * sotto sono quelli di anniazzurri.it: kosgroup.com li imposta tutti nel manifest.
 * Nessun valore derivato da site_url: su due siti diversi deve restare lo stesso.
 */
$P = preg_replace('/[^a-z0-9_]/i', '', (string) $modx->getOption('prefisso', $scriptProperties, 'aa')) ?: 'aa';
$cfg = function ($chiave, $default) use ($modx, $P) {
    $v = $modx->getOption($P . '.' . $chiave, null, null);
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
/* Slash finale: 0 = tolto (anniazzurri.it, URL piatti), 1 = aggiunto,
 * 2 = come lo genera MODX (kosgroup.com: cartelle con "/", pagine senza). */
$SLASH    = (int) $cfg('trailing_slash', 0);
$FRAG_PAT = $cfg('frag_patologia', '#condition');
$FRAG_STR = $cfg('frag_struttura', '#localbusiness');   // convenzione del 20/10/2026 (audit AA, slide 29-30)

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

/* Lingue (kosgroup.com): "radice:lingua:home" separati da virgola, es.
 * "253:it-IT:1,385:en-GB:990". La radice è la cartella di lingua (it, en), la home è
 * la pagina che le briciole mettono in testa. Vuoto (anniazzurri.it): una lingua, home = site_start. */
$LINGUE = [];
foreach ($csv($cfg('lingue', '')) as $voce) {
    $p = array_map('trim', explode(':', $voce));
    if (count($p) === 3) $LINGUE[(int) $p[0]] = ['lang' => $p[1], 'home' => (int) $p[2]];
}
/* Briciole (regole del sito):
 * - breadcrumb_titolo: "pagetitle" = pagetitle per i livelli intermedi (anniazzurri.it, come lo
 *   snippet BreadCrumb); "menutitle" = menutitle o pagetitle per tutti i livelli (kosgroup.com,
 *   come il menu: «Strutture» per /it/centri);
 * - breadcrumb_solo_pubblicate: 1 = si saltano i livelli non pubblicati, i link simbolici/web e le
 *   cartelle con FirstChildRedirect, che non sono pagine da linkare (kosgroup.com: cartelle degli anni,
 *   «Chi siamo»); i livelli nascosti dal menu ma pubblicati restano e sono cliccabili;
 * - breadcrumb_home: etichetta della home (vuoto = pagetitle della home);
 * - breadcrumb_templates: template che mostrano le briciole; la BreadcrumbList esce solo lì e sulle
 *   pagine che le richiamano nel contenuto (myBreadcrumb o chunk breadcrumb). Vuoto = tutte le pagine (anniazzurri.it). */
$BC_TITOLO   = $cfg('breadcrumb_titolo', 'pagetitle');
$BC_SOLO_PUB = (int) $cfg('breadcrumb_solo_pubblicate', 0);
$BC_HOME     = (string) $cfg('breadcrumb_home', '');
$BC_TPL      = $csv($cfg('breadcrumb_templates', ''));

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
    if ($SLASH === 2) return $u;
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
$org = function ($lingua = '') use ($cfg, $csv, $ORG_ID, $PARENT_ID, $CANON, $pulisci, $P) {
    // Nome dell'ente nella lingua della pagina (kosgroup.com: «Gruppo KOS» in italiano,
    // «KOS Group» in inglese); l'altra forma va in alternateName. Stesso @id: l'entità è una.
    $nome = $cfg('org_name', 'Anni Azzurri');
    $altro = $cfg('org_alternate', '');
    if ($lingua !== '' && strpos($lingua, 'it') !== 0 && ($nomeEn = $cfg('org_name_en', ''))) {
        $altro = $nome;
        $nome = $nomeEn;
    }

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
    // Su kosgroup.com la controllante (CIR S.p.A.) non ha un @id nostro: parent_org_id vuoto,
    // nodo descritto per nome. parent_org_name = "-" toglie il collegamento.
    $parent = null;
    $nomeP = $cfg('parent_org_name', 'KOS S.p.A.');
    if ($nomeP !== '-') {
        $parent = array_filter([
            '@id'       => $PARENT_ID === '-' ? null : $PARENT_ID,
            '@type'     => 'Organization',
            'name'      => $nomeP,
            'legalName' => $cfg('parent_org_legal', $nomeP),
            'vatID'     => $cfg('parent_org_vat', $P === 'aa' ? 'IT10674520159' : ''),
            'url'       => $cfg('parent_org_url', $P === 'aa' ? 'https://kosgroup.com/it' : ''),
        ]);
    }
    // Società controllate con un proprio sito e @id (kosgroup.com → KOS Care su anniazzurri.it).
    $figlie = array_map(function ($id) { return ['@id' => $id]; }, $csv($cfg('sub_org_ids', '')));

    return $pulisci([
        '@type'              => 'Organization',
        '@id'                => $ORG_ID,
        'name'               => $nome,
        'alternateName'      => $altro,
        'legalName'          => $cfg('org_legal', 'KOS Care S.r.l.'),
        'vatID'              => $cfg('org_vat',   'IT01148190547'),
        'taxID'              => $cfg('org_taxid', ''),
        'url'                => $cfg('org_url',   $CANON . '/'),
        'subOrganization'    => $figlie,
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

/* --- lingua e home della pagina ---------------------------------------------
 * anniazzurri.it: una lingua, home = site_start. kosgroup.com: la cartella di lingua
 * (it / en) tra gli antenati decide lingua e home (impostazione <prefisso>.lingue).
 */
$linguaDi = function ($res) use ($modx, $CLS_RES, $LINGUE, $LANG, $START) {
    if (!$LINGUE) return ['lang' => $LANG, 'home' => $START, 'radice' => 0];
    $id = (int) $res->get('id');
    $rid = $id;
    for ($giri = 0; $rid > 0 && $giri < 100; $giri++) {
        if (isset($LINGUE[$rid])) return $LINGUE[$rid] + ['radice' => $rid];
        $r = ($rid === $id) ? $res : $modx->getObject($CLS_RES, $rid);
        if (!$r) break;
        $rid = (int) $r->get('parent');
    }
    // Pagine fuori dalle cartelle di lingua (landing alla radice): prima lingua.
    foreach ($LINGUE as $radice => $l) return $l + ['radice' => $radice];
};

/** URL della home: su anniazzurri.it la radice con slash, altrove l'URL della pagina home. */
$urlHome = function ($homeId) use ($url, $LINGUE, $START, $CANON) {
    return (!$LINGUE && $homeId === $START) ? $CANON . '/' : $url($homeId);
};

/* --- briciole di pane -------------------------------------------------------
 * UNA funzione per briciole visibili (&type=`briciole`) e BreadcrumbList: stesso percorso.
 * - si risale l'albero MODX fino alla radice (su anniazzurri.it gli URL sono piatti, l'albero no);
 * - in testa la home della lingua; si saltano home, cartelle di lingua, site_start e le risorse
 *   in <prefisso>.breadcrumb_exclude;
 * - con breadcrumb_solo_pubblicate = 1 si saltano anche i livelli intermedi che non sono pagine
 *   da linkare (non pubblicati, link web/simbolici, cartelle con FirstChildRedirect);
 *   altrimenti restano tutti, come fa lo snippet BreadCrumb per default (anniazzurri.it);
 * - titoli secondo breadcrumb_titolo; URL assoluti nella forma canonica;
 * - sulla home (e sulle cartelle di lingua) nessuna briciola.
 */
$briciole = function ($res) use ($modx, $url, $urlHome, $linguaDi, $CLS_RES, $START, $ESCLUDI, $LINGUE,
                                 $BC_TITOLO, $BC_SOLO_PUB, $BC_HOME) {
    $id = (int) $res->get('id');
    $L = $linguaDi($res);
    if ($id === $L['home'] || $id === $START || isset($LINGUE[$id])) return [];

    $saltaIntermedio = function ($r) use ($BC_SOLO_PUB) {
        if (!$BC_SOLO_PUB) return false;
        return !$r->get('published') || $r->get('deleted')
            || strpos((string) $r->get('class_key'), 'modDocument') === false
            || stripos((string) $r->get('content'), 'FirstChildRedirect') !== false;
    };

    $catena = [];
    $rid = $id;
    for ($giri = 0; $rid > 0 && $giri < 100; $giri++) {
        $r = ($rid === $id) ? $res : $modx->getObject($CLS_RES, $rid);
        if (!$r) break;
        $fuori = $rid === $START || $rid === $L['home'] || isset($LINGUE[$rid]) || in_array($rid, $ESCLUDI, true)
              || ($rid !== $id && $saltaIntermedio($r));
        if (!$fuori) array_unshift($catena, $r);
        $rid = (int) $r->get('parent');
    }

    $voci = [];
    if ($home = $modx->getObject($CLS_RES, $L['home'])) {
        $voci[] = ['name' => $BC_HOME !== '' ? $BC_HOME : $home->get('pagetitle'), 'item' => $urlHome($L['home'])];
    }
    foreach ($catena as $r) {
        $corrente = ((int) $r->get('id') === $id);
        $voci[] = [
            'name' => ($corrente || $BC_TITOLO === 'menutitle')
                        ? ($r->get('menutitle') ?: $r->get('pagetitle'))
                        : $r->get('pagetitle'),
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

/* La pagina mostra le briciole? Solo lì esce la BreadcrumbList, che deve essere coerente con
 * quelle visibili (audit KosGroup, slide 29). Vuoto = tutte le pagine. */
$mostraBriciole = function ($res) use ($BC_TPL) {
    if (!$BC_TPL) return true;
    $tpl = $res->getOne('Template');
    if ($tpl && in_array($tpl->get('templatename'), $BC_TPL, true)) return true;
    $c = (string) $res->get('content');
    // Nel contenuto: snippet myBreadcrumb, chunk [[$breadcrumb]] (es. news, pagine generica-en) o chiamata diretta.
    return stripos($c, 'myBreadcrumb') !== false || stripos($c, 'type=`briciole`') !== false
        || preg_match('/\[\[\$breadcrumb(?![\w.-])/i', $c) === 1;
};

/* Nodo BreadcrumbList, o null se la pagina non mostra briciole o ha meno di due livelli. */
$nodoBriciole = function ($res, $pagina) use ($briciole, $mostraBriciole) {
    if (!$mostraBriciole($res)) return null;
    $l = $briciole($res);
    return count($l) < 2 ? null : [
        '@type'           => 'BreadcrumbList',
        '@id'             => $pagina . '#breadcrumb',
        'itemListElement' => $l,
    ];
};

/* Nodo WebSite. */
$sito = function ($lingua = null) use ($cfg, $CANON, $SITE_ID, $ORG_ID, $LANG, $LINGUE) {
    // Il sito è una sola entità: con più lingue le dichiara tutte, uguale su ogni pagina.
    if ($LINGUE) $lingua = array_values(array_unique(array_column($LINGUE, 'lang')));
    return [
        '@type'      => 'WebSite',
        '@id'        => $SITE_ID,
        'url'        => $cfg('website_url', $cfg('org_url', $CANON . '/')),
        'name'       => $cfg('org_name', 'Anni Azzurri'),
        'inLanguage' => $lingua ?: $LANG,
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

/* --- nodo struttura (#localbusiness) ----------------------------------------
 * Dati dalle TV str.* della scheda; nessun dato inventato: un campo vuoto non esce, e senza indirizzo (str.via)
 * il nodo non esce affatto.
 * Tipi e specialità solo da elenchi noti di schema.org (un valore sconosciuto si scarta:
 * l'errore di Yext era usare le specialità come tipo).
 */
$STR_TPL = $csv($cfg('tpl_struttura', ''));
$LND_TPL = $csv($cfg('tpl_landing', ''));
$STR_ON  = (int) $cfg('struttura_attiva', 0);
$TIPI_STRUTTURA = ['MedicalClinic', 'Hospital', 'DiagnosticLab', 'Dentist', 'Physiotherapy', 'MedicalOrganization',
    'MedicalBusiness', 'LocalBusiness', 'Pharmacy', 'Optician'];
$SPECIALITA = ['Anesthesia', 'Cardiovascular', 'CommunityHealth', 'Dentistry', 'Dermatology', 'DietNutrition',
    'Emergency', 'Endocrine', 'Gastroenterologic', 'Genetic', 'Geriatric', 'Gynecologic', 'Hematologic', 'Infectious',
    'LaboratoryScience', 'Midwifery', 'Musculoskeletal', 'Neurologic', 'Nursing', 'Obstetric', 'Oncologic',
    'Optometric', 'Otolaryngologic', 'Pathology', 'Pediatric', 'PharmacySpecialty', 'Physiotherapy', 'PlasticSurgery',
    'Podiatric', 'PrimaryCare', 'Psychiatric', 'PublicHealth', 'Pulmonary', 'Radiography', 'Renal',
    'RespiratoryTherapy', 'Rheumatologic', 'SpeechPathology', 'Surgical', 'Toxicologic', 'Urologic'];
$GIORNI = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday',
    'Sa' => 'Saturday', 'Su' => 'Sunday'];

/** "Mo-Fr 08:00-13:00,14:00-18:00" → OpeningHoursSpecification (una per fascia). */
$orari = function ($testo) use ($righe, $GIORNI) {
    $out = [];
    $chiavi = array_keys($GIORNI);
    foreach ($righe($testo) as $r) {
        if (!preg_match('/^([A-Za-z,\- ]+?)\s+(\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}(?:\s*,\s*\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2})*)$/', $r, $m)) continue;
        $giorni = [];
        foreach (preg_split('/\s*,\s*/', trim($m[1])) as $g) {
            $g = preg_split('/\s*-\s*/', $g);
            $a = array_search(ucfirst(strtolower($g[0])), $chiavi, true);
            $b = isset($g[1]) ? array_search(ucfirst(strtolower($g[1])), $chiavi, true) : $a;
            if ($a === false || $b === false || $b < $a) continue;
            for ($i = $a; $i <= $b; $i++) $giorni[] = 'https://schema.org/' . $GIORNI[$chiavi[$i]];
        }
        if (!$giorni) continue;
        foreach (preg_split('/\s*,\s*/', $m[2]) as $fascia) {
            [$da, $a2] = array_map(fn($x) => sprintf('%05s', trim($x)), explode('-', $fascia));
            $out[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array_values(array_unique($giorni)),
                      'opens' => $da, 'closes' => $a2];
        }
    }
    return $out;
};
/** "2026-12-25 chiuso" | "2026-12-24 08:00-12:00" | "2026-08-10/2026-08-21 chiuso". Chiuso = opens e closes 00:00. */
$orariSpeciali = function ($testo) use ($righe) {
    $out = [];
    foreach ($righe($testo) as $r) {
        if (!preg_match('#^(\d{4}-\d{2}-\d{2})(?:/(\d{4}-\d{2}-\d{2}))?\s+(chiuso|closed|(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2}))$#i', $r, $m)) continue;
        $chiuso = !isset($m[4]) || $m[4] === '';
        $out[] = ['@type' => 'OpeningHoursSpecification', 'validFrom' => $m[1], 'validThrough' => ($m[2] ?? '') ?: $m[1],
                  'opens' => $chiuso ? '00:00' : sprintf('%05s', $m[4]), 'closes' => $chiuso ? '00:00' : sprintf('%05s', $m[5])];
    }
    return $out;
};
/** Prima immagine esistente tra TV e percorsi di ripiego (stessa regola dello snippet ogImage). */
$immagineStruttura = function ($r) use ($tv, $immagine, $cfg) {
    $cand = [$tv($r, 'infoImage')];
    foreach (explode('|', (string) $cfg('struttura_immagini', 'assets/img/header/{alias}.jpg|assets/img/strutture/{alias}/')) as $p) {
        $cand[] = str_replace('{alias}', (string) $r->get('alias'), trim($p));
    }
    foreach ($cand as $c) {
        $c = ltrim(trim((string) $c), '/');
        if ($c === '' || preg_match('#^https?://#i', $c)) continue;
        $f = MODX_BASE_PATH . $c;
        if (substr($c, -1) === '/') {
            $file = is_dir($f) ? (glob($f . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG}', GLOB_BRACE) ?: []) : [];
            sort($file);
            if ($file) return $immagine($c . basename($file[0]));
        } elseif (is_file($f)) {
            return $immagine($c);
        }
    }
    return null;
};
/** Servizi della landing: «Tipo|Nome|Descrizione» per riga. */
$servizi = function ($testo) use ($righe) {
    $out = [];
    foreach ($righe($testo) as $r) {
        $p = array_map('trim', explode('|', $r, 3));
        if (count($p) < 2 || !in_array($p[0], ['MedicalTest', 'MedicalProcedure', 'MedicalTherapy', 'MedicalCondition'], true)) continue;
        $out[] = array_filter(['@type' => $p[0], 'name' => $p[1], 'description' => $p[2] ?? '']);
    }
    return $out;
};
/* Servizi dal sito: le pagine di «Servizi e cure» indicate nelle TV di mappatura della scheda (impostazione
 * <prefisso>.struttura_tv_servizi). Tipo e specialità dalla TV srv.schema della pagina del servizio
 * («MedicalTherapy|Physiotherapy,Neurologic»); se vuota, dal ramo del sito (trattamenti → terapia,
 * psichiatria → condizione trattata, altrimenti prestazione). Ogni voce porta l'URL della sua pagina. */
$TV_SERVIZI = $csv($cfg('struttura_tv_servizi', ''));
$serviziSito = function ($r) use ($modx, $CLS_RES, $tv, $csv, $url, $TV_SERVIZI) {
    $voci = []; $spec = [];
    if (!$TV_SERVIZI) return [$voci, $spec];
    $visti = [];
    foreach ($TV_SERVIZI as $nome) {
        foreach (array_filter(array_map('intval', preg_split('/[^\d]+/', (string) $tv($r, $nome)))) as $id) {
            if (isset($visti[$id])) continue;
            $visti[$id] = true;
            $s = $modx->getObject($CLS_RES, $id);
            if (!$s || !$s->get('published') || $s->get('deleted')) continue;
            $u = (string) $s->get('uri');
            $def = strpos($u, '/trattamenti/') !== false ? 'MedicalTherapy'
                 : (strpos($u, '/psichiatria/') !== false ? 'MedicalCondition'
                 : (strpos($u, '/riabilitazione/') !== false ? 'MedicalTherapy' : 'MedicalProcedure'));
            $p = array_map('trim', explode('|', (string) $tv($s, 'srv.schema'), 2));
            if (($p[0] ?? '') === '-') continue;   // pagina da non dichiarare
            $tipo = in_array($p[0] ?? '', ['MedicalTherapy', 'MedicalTest', 'MedicalProcedure', 'MedicalCondition'], true) ? $p[0] : $def;
            $spec = array_merge($spec, $csv($p[1] ?? ''));
            $voci[] = ['@type' => $tipo, 'name' => $s->get('pagetitle'), 'url' => $url($id)];
        }
    }
    return [$voci, array_values(array_unique($spec))];
};
/* Prenotazione online dal portale TuoTempo (kosgroup.com). La TV <prefisso>.prenotazione_tv (default istanza) contiene
 * l'areaid TuoTempo della sede: compilata = prenotazione attiva (è la stessa che accende la barra di prenotazione).
 *   - azione «Prenota online» della struttura: portale filtrato sulla sede;
 *   - catalogo «Prestazioni prenotabili online» (hasOfferCatalog), diviso per branca, con il link diretto
 *     sede + prestazione; stesse regole della barra: sede non nascosta, prestazione non nascosta o prenotabile in
 *     differita, almeno un medico (tabelle kos_strutture, kos_prestazioni, kos_medici_prestazioni).
 * Ogni link porta kos_referrer = <prefisso>.prenotazione_referrer (default dato-strutturato) per il funnel.
 * Le tabelle si aggiornano dalla sincronizzazione TuoTempo; il JSON-LD è nella cache della pagina e si rinnova con lei. */
$PREN_PORTALE = (string) $cfg('prenotazione_portale', '');
$PREN_TV      = (string) $cfg('prenotazione_tv', 'istanza');
$PREN_REF     = (string) $cfg('prenotazione_referrer', 'dato-strutturato');
$prenotazione = function ($r) use ($modx, $tv, $PREN_PORTALE, $PREN_TV, $PREN_REF) {
    $area = trim((string) $tv($r, $PREN_TV));
    if ($PREN_PORTALE === '' || $area === '') return [null, null];
    $link = function (array $q) use ($PREN_PORTALE, $PREN_REF) {
        return $PREN_PORTALE . (strpos($PREN_PORTALE, '?') === false ? '?' : '&')
             . http_build_query($q + ['kos_referrer' => $PREN_REF], '', '&', PHP_QUERY_RFC3986);
    };
    $catalogo = null;
    try {
        $st = $modx->prepare("SELECT 1 FROM kos_strutture WHERE areaid = ? AND is_hidden = 0 LIMIT 1");
        $st->execute([$area]);
        if (!$st->fetchColumn()) return [null, null];   // sede nascosta: la barra non la mostra, il dato neppure
        $st = $modx->prepare("SELECT p.activity_id, p.title, p.typology FROM kos_prestazioni p
                              WHERE p.areaid = ? AND (p.is_hidden = 0 OR p.mop_bookability = 2)
                                AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)
                              ORDER BY p.typology, p.title");
        $st->execute([$area]);
        $branche = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $branche[(string) $p['typology']][] = [
                '@type'       => 'Offer',
                'itemOffered' => ['@type' => 'Service', 'name' => trim((string) $p['title'])],
                // la branca è il nome dell'OfferCatalog che contiene l'offerta: niente `category` ripetuta
                'url'         => $link(['activityid' => $p['activity_id'], 'areaid' => $area]),
            ];
        }
        if ($branche) {
            $catalogo = ['@type' => 'OfferCatalog', 'name' => 'Prestazioni prenotabili online', 'itemListElement' => []];
            foreach ($branche as $b => $offerte) {
                $catalogo['itemListElement'][] = ['@type' => 'OfferCatalog', 'name' => $b, 'itemListElement' => $offerte];
            }
        }
    } catch (\Throwable $e) {
        // tabelle della prenotazione assenti (es. anniazzurri.it): solo il link della sede
    }
    return [$link(['areaid' => $area]), $catalogo];
};
$nodoStruttura = function ($r, $extraServizi = []) use (
    $url, $tv, $servizi, $serviziSito, $prenotazione, $righe, $testo, $csv, $pulisci, $orari, $orariSpeciali, $immagineStruttura,
    $TIPI_STRUTTURA, $SPECIALITA, $FRAG_STR, $ORG_ID
) {
    $pagina = $url($r->get('id'));
    if ($pagina === '') return null;
    // Senza indirizzo non è un'attività locale descrivibile (Google richiede l'indirizzo): niente nodo, la pagina
    // resta una WebPage. Vale anche per le pagine di servizio con il template delle schede (es. Ritiro referti).
    if ($tv($r, 'str.via') === '' || $tv($r, 'str.via') === null) return null;
    $tipi = array_values(array_intersect($csv($tv($r, 'str.tipo')), $TIPI_STRUTTURA)) ?: ['MedicalClinic'];
    // Indirizzo, coordinate, orari e mappa sono proprietà dei luoghi e delle attività locali: un tipo che non lo è
    // (DiagnosticLab e MedicalOrganization sono solo organizzazioni) si affianca a MedicalBusiness, altrimenti il
    // validatore li segnala come proprietà sconosciute e Google non riconosce l'attività locale.
    if (!array_intersect($tipi, ['MedicalClinic', 'Hospital', 'Dentist', 'Physiotherapy', 'MedicalBusiness', 'LocalBusiness', 'Pharmacy', 'Optician'])) {
        $tipi[] = 'MedicalBusiness';
    }
    $via = $tv($r, 'str.via');
    $lat = str_replace(',', '.', (string) $tv($r, 'str.lat'));
    $lng = str_replace(',', '.', (string) $tv($r, 'str.lng'));
    $place = $tv($r, 'str.place_id');
    $nome = $tv($r, 'str.nome') ?: $r->get('pagetitle');
    $marchio = $tv($r, 'str.marchio');
    // Prenotazione: portale TuoTempo della sede se attiva (TV istanza), altrimenti il link di str.prenotazione (Yext)
    [$prenotaSede, $catalogo] = $prenotazione($r);
    $prenota = $prenotaSede ?: (string) $tv($r, 'str.prenotazione');
    // Servizi della scheda (str.servizi) più quelli della landing, senza doppioni. Ogni voce va nella proprietà che
    // schema.org prevede per il tipo: availableService (MedicalClinic, Hospital), availableTest (DiagnosticLab);
    // le condizioni trattate e ciò che il tipo non ammette vanno in knowsAbout (valido per ogni organizzazione).
    // Fonte dei servizi: le associazioni della scheda sul sito (decisione del 25/10); str.servizi (filtri Yext) solo
    // se la scheda non ne ha.
    [$dalSito, $specSito] = $serviziSito($r);
    $voci = [];
    foreach (array_merge($dalSito ?: $servizi($tv($r, 'str.servizi')), $extraServizi) as $v) $voci[mb_strtolower($v['name'])] = $v;
    $offerti = []; $test = []; $sa = [];
    $conServizi = (bool) array_intersect($tipi, ['MedicalClinic', 'Hospital']);
    foreach ($voci as $v) {
        if ($v['@type'] !== 'MedicalCondition' && $conServizi) $offerti[] = $v;
        elseif ($v['@type'] === 'MedicalTest' && in_array('DiagnosticLab', $tipi, true)) $test[] = $v;
        else $sa[] = array_filter(['@type' => $v['@type'], 'name' => $v['name'], 'url' => $v['url'] ?? null]);
    }
    return $pulisci([
        '@type'       => count($tipi) === 1 ? $tipi[0] : $tipi,
        '@id'         => $pagina . $FRAG_STR,
        'name'        => $nome,
        'url'         => $pagina,
        'description' => $testo($r->get('description') ?: $r->get('introtext')),
        'image'       => $immagineStruttura($r),
        'telephone'   => $tv($r, 'str.telefono'),
        'email'       => $tv($r, 'str.email'),
        'address'     => $via === '' ? null : [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $via,
            'postalCode'      => $tv($r, 'str.cap'),
            'addressLocality' => $tv($r, 'str.citta'),
            'addressRegion'   => strtoupper((string) $tv($r, 'str.provincia')),
            'addressCountry'  => 'IT',
        ],
        'geo'         => (is_numeric($lat) && is_numeric($lng))
                            ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng] : null,
        'hasMap'      => $place ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($nome)
                            . '&query_place_id=' . rawurlencode($place) : null,
        'openingHoursSpecification'        => $orari($tv($r, 'str.orari')),
        'specialOpeningHoursSpecification' => $orariSpeciali($tv($r, 'str.orari_speciali')),
        'medicalSpecialty' => array_values(array_intersect(array_unique(array_merge($csv($tv($r, 'str.specialita')), $specSito)), $SPECIALITA)),
        'brand'       => $marchio ? ['@type' => 'Brand', 'name' => $marchio] : null,
        'hasOfferCatalog'  => $catalogo,
        'availableService' => $offerti,
        'availableTest'    => $test,
        'knowsAbout'       => $sa,
        // Prenotazione online: azione dichiarata, utile agli assistenti AI che rimandano a «dove si prenota».
        'potentialAction' => preg_match('#^https://#', $prenota) ? [
            '@type'  => 'ReserveAction',
            'name'   => 'Prenota online',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $prenota,
                         'actionPlatform' => ['https://schema.org/DesktopWebPlatform', 'https://schema.org/MobileWebPlatform']],
        ] : null,
        'sameAs'      => array_values(array_filter($righe($tv($r, 'str.sameAs')), fn($u) => preg_match('#^https://#', $u))),
        'parentOrganization' => ['@id' => $ORG_ID],
    ]);
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

$L = $linguaDi($res);
$LANG = $L['lang'];   // lingua della pagina (kosgroup.com: it-IT o en-GB)
$paginaCorrente = $url($res->get('id'));
if (!$LINGUE && (int) $res->get('id') === $START) $paginaCorrente = $CANON . '/';

/* ---------------------------------------------------------- briciole visibili
 * Stesso markup delle briciole storiche di kosgroup.com (#breadcrumbs, separatore |,
 * .bc-attivo sulla pagina corrente), in un <nav> con etichetta per i lettori di schermo.
 * Link a percorso assoluto (/it/...), come link_tag_scheme = abs. */
if ($type === 'briciole') {
    $l = $briciole($res);
    if (count($l) < 2) return '';
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $etichetta = strpos($LANG, 'en') === 0 ? 'Breadcrumb' : 'Percorso di navigazione';
    $pezzi = [];
    foreach ($l as $i => $v) {
        $ultimo = ($i === count($l) - 1);
        $percorso = (string) parse_url($v['item'], PHP_URL_PATH);
        $pezzi[] = $ultimo
            ? '<span class="bc-attivo" aria-current="page">' . $e($v['name']) . '</span>'
            : '<a href="' . $e($percorso) . '">' . $e($v['name']) . '</a>';
    }
    return '<nav class="col" id="breadcrumbs" aria-label="' . $etichetta . '">' . implode('<span>|</span>', $pezzi) . '</nav>';
}

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
        $sito($LANG),
        $org($LANG),
    ];
    return $json($grafo);
}

/* --------------------------------------------------------- pagina generica */
if ($type === 'page') {
    $tpl = $res->getOne('Template');
    $nomeTpl = $tpl ? $tpl->get('templatename') : '';
    $home = ((int) $res->get('id') === $L['home']);
    $br = $nodoBriciole($res, $paginaCorrente);

    // Scheda struttura: la pagina descrive la struttura. Landing: la pagina parla di una struttura.
    $struttura = null; $rel = null;
    if ($STR_ON && in_array($nomeTpl, $STR_TPL, true)) {
        $struttura = $nodoStruttura($res);
        $rel = 'mainEntity';
    } elseif ($STR_ON && in_array($nomeTpl, $LND_TPL, true) && ($sid = (int) $tv($res, 'lnd.struttura'))) {
        $sr = $modx->getObject($CLS_RES, $sid);
        if ($sr && $sr->get('published') && !$sr->get('deleted')) {
            $struttura = $nodoStruttura($sr, $servizi($tv($res, 'lnd.servizi')));
            $rel = 'about';
        }
    }

    $pagina = $pulisci([
        '@type'       => in_array($nomeTpl, $TPL_COLLEZIONE, true) ? 'CollectionPage' : 'WebPage',
        '@id'         => $paginaCorrente . '#webpage',
        'url'         => $paginaCorrente,
        'name'        => $res->get('longtitle') ?: $res->get('pagetitle'),
        'description' => $res->get('description'),
        'inLanguage'  => $LANG,
        'isPartOf'    => ['@id' => $SITE_ID],
        'about'       => $home ? ['@id' => $ORG_ID] : (($struttura && $rel === 'about') ? ['@id' => $struttura['@id']] : null),
        'mainEntity'  => ($struttura && $rel === 'mainEntity') ? ['@id' => $struttura['@id']] : null,
        'breadcrumb'  => $br ? ['@id' => $paginaCorrente . '#breadcrumb'] : null,
        'dateModified'=> $data($res->get('editedon') ?: $res->get('publishedon')),
    ]);
    // Il nodo struttura per intero, anche sulle landing: i crawler AI raramente seguono un @id.
    return $json([$pagina, $struttura, $br, $sito($LANG), $org($LANG)]);
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
$grafo[] = $sito($LANG);
$grafo[] = $org($LANG);

return $json($grafo);
