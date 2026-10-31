# Handoff – Interventi su prestazioni (TTFB), crawler, link e sitemap
**Siti:** kosgroup.com (MODX 3.2.4, MODX Cloud, server paas2.fra) · anniazzurri.it (MODX, MariaDB 10.3, MODX Cloud)
**Periodo:** 8–26 ottobre 2026 (aggiornato il 26/10) · **Scopo:** coordinare gli interventi SEO/GEO/AEO con quanto già fatto, senza annullarlo né duplicarlo.

> **Aggiornamento 26/10 (kosgroup):** MODX Cloud ha confermato con i propri log che la causa principale del peggioramento del TTFB era il **carico dei crawler sugli URL annidati** (160.000–216.000 richieste/giorno a PHP fino al 20/10, su 4 worker). Dal 20/10 quelle richieste ricevono 404 da nginx senza arrivare a PHP. Il 26/10 i **PHP worker sono passati da 4 a 8**. Ticket MODX chiuso. Dettagli in §2.

---

## 1. Stato in breve

| Tema | Stato | Note per il lavoro SEO |
|---|---|---|
| Ritardi di connessione 1–3 s (SYN scartati) | Causa trovata (client Windows senza timestamp TCP + NAT operatore). Unica soluzione: **Cloud Edge (Cloudflare)**, in attesa di approvazione marketing + IT | Riguarda soprattutto PC Windows (gli smartphone inviano i timestamp). Cloud Edge va considerato in ogni scelta su cache, header, redirect, robots, blocco bot |
| TTFB reale (CrUX, Italia) kosgroup | Peggiorato da maggio: smartphone slow TTFB 7,6% (apr) → 30,5% (ago) → 24,3% (set); p75 1,2 s → 1,9 s → 1,7 s. **Causa confermata: carico crawler** su 4 PHP worker | Interventi: 404 nginx agli URL annidati (20/10), worker 4 → 8 (26/10). Miglioramento atteso in CrUX entro 4–6 settimane (finestra 28 giorni). anniazzurri molto meglio (3–4% → 7,7%), verifica in corso |
| PHP worker kosgroup | **8** (da 4, il 26/10), 128 MB ciascuno | Le pagine non in cache impiegano 1–2 s: ogni nuova chiamata non in cache o svuotamento totale della cache pesa sui picchi |
| PHP worker anniazzurri | 4 | In attesa dei dati MODX su saturazione e URL annidati (che lì passano ancora da PHP) |
| Link relativi | **0 / 860** pagine (kosgroup), **0 / 552** (anniazzurri) | Regola: ogni nuovo link interno deve iniziare con `/` |
| URL "annidati" inventati dai crawler | kosgroup: 404 a livello nginx + Tagger disattivato su OnPageNotFound; anniazzurri: già 404 | Non reintrodurre plugin su OnPageNotFound che rispondano 200 |
| Sitemap anniazzurri | **pdoSitemap**, 448 URL, tutti 200 | Inclusione/esclusione governata dalla casella **Ricercabile** (vedi §4) |
| noindex anniazzurri | Legato alla casella **Ricercabile** in tutti gli head | Non aggiungere un secondo meccanismo noindex senza coordinarlo |
| Anteprime social kosgroup | 860/860 corrette | Sistema og:image basato su TV `infoImage` + snippet `ogImage` |
| PHP | 8.3.36 su tutti i cloud (patch PHP2Shell applicata da MODX Cloud il 21/10) | Nessuna azione |

---

## 2. Diagnosi TTFB (contesto per decisioni future)

**Meccanismo confermato (test A/B, 21–22/10):**
- Il server chiude per primo le connessioni HTTPS e le tiene in TIME_WAIT (~60 s).
- Client Windows (timestamp TCP spenti di default) dietro NAT/CGNAT (molto comune su reti mobili/FWA italiane) riusano la stessa porta → il server scarta i SYN → ritrasmissioni a 1 s, 3 s (raramente 7 s).
- Con timestamp TCP attivi sul client: **0/100** connessioni lente (mattina e sera, 4 serie); spenti: **12/100** e **7/100**. Da macchina senza NAT (Google Cloud Shell, Linux): 1/100.
- Porta 80 non affetta (sui redirect è il client a chiudere).

**Escluso come causa della crescita di TTFB maggio–agosto:**
- Traffico umano (GA4): agosto −24% pagine viste vs aprile, eppure mese peggiore.
- Modifiche al sito (manager log): agosto quasi nessuna modifica.
- Generazione pagine: 150–250 ms a cache calda, fino a ~1 s a cache fredda (pagine approfondimenti e medici). Rari picchi isolati 0,8–1,2 s.

**Risposta MODX Cloud (Jay Gilmore, 26/10) – causa del peggioramento confermata:**
- Fino alle 05:00 UTC del 20/10 gli URL annidati dei crawler generavano **160.000–216.000 richieste al giorno a PHP** (≈2–2,5/s continui) su **4 worker**: saturazione costante, visitatori reali in coda (smartphone e desktop).
- Dopo la regola nginx (404 senza PHP) quel traffico non arriva più a PHP ed è ulteriormente calato dal 22/10. Ora PHP gestisce 400–1.300 richieste/ora, in media meno di un worker occupato.
- Restano i **picchi**: il pool ha toccato il limite di 4 worker 21–43 volte al giorno dopo il 20/10. Caso peggiore: **24/10, 05:53–05:56 CEST, ~270 richieste in 2 minuti**, alcune pagine oltre 10 s (origine da identificare: richiesta a MODX).
- Pagine non in cache: **1–2 s** di generazione → 4 worker saturano a 2–4 richieste/s.
- Impostazioni TCP/TIME_WAIT del server: default Linux; un keep-alive più lungo non risolverebbe i SYN scartati.
- MODX consiglia **8 worker** (fatto il 26/10) per i picchi; contro il traffico automatico continuo serve **bloccare o mettere in cache prima di PHP**.
- Ticket chiuso: nulla da cambiare lato piattaforma.

**Cloud Edge (Cloudflare) – raccomandato da MODX, in attesa di approvazione marketing + IT:**
- Elimina i ritardi di connessione (i server vedono le connessioni di Cloudflare, non la NAT degli operatori).
- Cloudflare non mette in cache l'HTML di default: MODX configurerebbe le regole di cache delle pagine → molto meno carico su PHP, meno rischio di errori 50x nei picchi.
- Le regole di blocco possono passare da nginx a Cloudflare (le richieste bloccate non arrivano al server).
- Dopo l'attivazione: ripetere il test da 100 campioni con timestamp **spenti** e guardare la colonna "Connessione" (non il TTFB, che scende comunque grazie alla cache).
- Punti da chiarire prima dell'attivazione: cache dell'HTML e svuotamento al salvataggio in MODX, esclusione delle pagine con form (FormIt, reCAPTCHA), IP reale dei visitatori (CF-Connecting-IP), regole nginx esistenti, DNS, eventuale fermo.

> Implicazione SEO: se si attiva Cloud Edge, rivedere insieme header di cache, regole nginx, redirect e gestione bot/AI crawler (allow/deny), perché parte della logica si sposta su Cloudflare.

---

## 3. kosgroup.com – modifiche rilevanti

### Server (web rules nginx, pannello MODX Cloud)
- Redirect storici in **un solo passaggio** (http/www, vecchi URL, PDF spostati, approfondimenti 2024/2025); destinazione calcolata una volta, redirect eseguito nelle location; rete di sicurezza per gli script PHP della piattaforma; favicon servita senza redirect.
- Cache browser: immagini/font lunga durata, CSS/JS più breve.
- Regola anti-URL annidati (a livello server, prima di PHP):
  `if ($uri ~ "^/(it|en)/.+/(it|en)(/|$)") { return 404; }`
- Variabile `$pid` è riservata in nginx: usare nomi diversi (es. `$vecchioid`).
- Nuovi redirect: vanno inseriti **prima** dei redirect generici www/http/radice, rispettando lo schema esistente.

### MODX
- **Impostazione** `link_tag_scheme = abs` → `[[~id]]` genera `/it/...`.
- **Wayfinder / pdoMenu / pdoCrumbs**: `&scheme=`abs`` su **ogni** chiamata (chunk, template, contenuti, valori e default delle TV – es. TV `modale`). Questi snippet non leggono `link_tag_scheme`.
- **Placeholder**: sempre `href="/[[+uri]]"` (`[[+uri]]` non ha la barra iniziale).
- **Snippet corretti** (link e immagini assoluti, `makeUrl($id,'','',$modx->getOption('link_tag_scheme'))`): Approfondimenti2, HeaderNews, myBreadcrumb, getMedici, getMediciStrutturaOrdered, CustomSitemap, VideoCarousel, CustomStructures, contenutiStruttura (+ filtro `FIND_IN_SET` sugli ID struttura).
- **Plugin Tagger**: evento **OnPageNotFound disattivato** (serviva la home con 200 a qualsiasi URL sotto una pagina esistente).
- **Open Graph**: chunk head, head-en, head-informativo, head-paginazione, head-strutture (snippet `ogImage`), nuovi head-medici, head-video, head-specializzazioni; template medici (8), video (10), specializzazioni (3) puntano ai nuovi head; immagine di ripiego `assets/img/kos-condivisione-social.jpg`.
- **Comunicati** (template 9): snippet `comunicatoRedirect` → 301 al PDF; 50 comunicati esclusi dalla sitemap (`searchable=0`), insieme a 6 approfondimenti spostati, 3 weblink, radici it/ en/, sitemap.xml.
- **footer-landing**: link Termini/Privacy con `[[~514]]`/`[[~513]]`, anno con `[[!copyYears]]`.

### Analisi già fatta sugli head (consolidamento NON ancora eseguito – da coordinare)
13 chunk `head*`, nucleo identico. Problemi noti:
- `[[*noindex:is=`1`...]]` presente **solo** in `head`: pagine con altri head (informativo, paginazione, strutture, landing, en) ignorano la TV noindex.
- `head-paginazione` **senza Canonical**.
- `custom.css` con versioni diverse (20260921b / 20260915 / 20260628b) → proposta impostazione unica `kos_css_version`.
- **hreflang errati**: pagine IT dichiarano tutte come traduzione EN la risorsa 990; pagine EN con `translationId` vuota producono hreflang `it` con href vuoto; backtick annidati. Da correggere in un punto solo (chunk head-hreflang-it / -en) insieme all'agenzia SEO.
- Font Awesome caricato due volte.
- Chiamate non in cache in head: `[[!Canonical]]` (da verificare), 4 in `head`, 6 in `menu-en`. FormIt nelle landing `contactLandingForm_*` (normale).
- `head-formazione`, `head-strutture-test` inutilizzati; `head-1065/1108/1114/1118/1133` sono schema JSON-LD delle landing (non head).

> Qualsiasi intervento SEO sugli head (schema.org, hreflang, meta) conviene farlo **dentro il consolidamento**, non chunk per chunk.

---

## 4. anniazzurri.it – modifiche rilevanti

### Server (web rules)
- Gzip a livello server (vale anche per le pagine MODX).
- CSP "wizard" report-uri **disattivata** (generava decine di richieste esterne a visita).
- Cache: immagini (anche webp/avif) 30 gg, CSS/JS 7 gg, PDF 1 g, senza perdere gli header di sicurezza.
- Redirect legacy in un passaggio; `/home` → 301 `/`.
- URL annidati: MODX risponde già 404 (pagina 404 pesante ~82 KB; se i log mostrassero volumi alti → regola nginx con 404 leggero).

### Link (tutti assoluti, 0/552)
- `link_tag_scheme` da -1 ad **abs**.
- `&scheme=`abs`` su Wayfinder/pdoMenu/pdoCrumbs: chunk menu-navbar, menu-side-navbar, footer, residenze-*, strutture-rsa-*; 13 template `interna-menu-*`; pagine regione/provincia (risorse 11–99).
- Barra iniziale su `[[+uri]]`, `[[+alias]]`, `[[*alias]]#ancora`, `[[*uri]]`; link scritti a mano corretti in 16 chunk, 4 template, ~80 risorse.
- Snippet **docList** riscritto (PDF delle residenze: href assoluti e codificati `rawurlencode`).
- Snippet **ecmIscrizione**: informativa con percorso assoluto.
- Template 30: `href="/[[*programma]]"`.

### Sitemap (risorsa `sitemap.xml`)
```
[[!pdoSitemap?
  &context=`web`
  &showHidden=`1`
  &resources=`-3,-4,-5,-6,-7,-156,-236`
  &where=`{"searchable":1,"template:NOT IN":[0,25,29,31,35,37,41,43,44,47,48,49,50,52,53,54,56,58,61],"uri:NOT IN":["grazie-per-aver-inviato-la-tua-candidatura","grazie-per-la-richiesta","registrazione","richiesta-ricevuta"]}`
  &cache=`1`
  &cacheTime=`3600`
]]
```
- `&templates` negativo **non funziona** in pdoSitemap: le esclusioni per template stanno in `&where`.
- Esclusi: contenitori FirstChildRedirect (solo loro, non i figli), landing `rsa-landing*` (29, 41, 43, 44, 47, 48, 49, 50, 52, 53, 56), recruiting/report (37, ha già `noindex,nofollow` fisso), oss-landing (35), wellmakers (58), landing-assistenza-domiciliare-v2 (31), fkt-ad-landing (54), yext-search/cerca (25), formIscrizione (61), template 0, pagine di ringraziamento/registrazione.
- 448 URL (6 pagine in approvazione escluse con "Ricercabile" off). Inclusi gli articoli nascosti dal menu tenuti per traffico storico (es. `alzheimer-cura-e-assistenza`).
- `robots.txt`: `Sitemap: https://anniazzurri.it/sitemap.xml`, `Disallow: /*?query=` e `/*&query=`.
- **Da fare:** reinvio in Search Console e verifica copertura a 2–4 settimane.

### noindex legato a "Ricercabile"
Inserito subito dopo `</title>` in 26 chunk head (1 head, 314 head-home, 318, 333, 341, 345, 359, 430–438, 445, 446, 464, 466, 483, 623, 662, 664, 669, 670) e nei template 1 (BaseTemplate) e 57 (convegnoAA):
```
[[*searchable:is=`0`:then=`<meta name="robots" content="noindex, follow">`]]
```
- Template 37 (recruiting) escluso: ha già `noindex,nofollow`.
- Chunk 661 `albero-svg` escluso (il suo `<title>` è dell'SVG).
- **Regola redazionale:** togliere "Ricercabile" = fuori dalla sitemap **e** noindex.
- Se si creano nuovi chunk head, va aggiunta la stessa riga.

### Altro
- og:image corretta sulle 128 pagine residenze; immagine og predefinita Anni Azzurri.
- Pagine canoniche: verificato `<link rel="canonical">` self-referencing (es. alzheimer-cura-e-assistenza).

---

## 5. Regole da rispettare negli interventi SEO/GEO/AEO

1. **Link interni sempre assoluti** (`/...`): in chunk, template, TV, snippet (`makeUrl` con `link_tag_scheme`), tpl `@INLINE`, testo degli editor. Ogni chiamata Wayfinder/pdo* con `&scheme=`abs``.
2. **Nessun plugin/snippet che risponda 200 a URL inesistenti** (OnPageNotFound).
3. **Evitare nuove chiamate non in cache `[[!...]]` negli head e nei menu**: pesano su ogni pagina e sul TTFB. Schema JSON-LD statico o con dati di risorsa → in cache. Con pagine non in cache da 1–2 s, ogni chiamata in più riduce la capacità nei picchi; preferire salvataggi mirati agli svuotamenti totali della cache (a ottobre 94).
4. **anniazzurri:** esclusioni da sitemap/indicizzazione tramite "Ricercabile" o template di landing, non con nuove eccezioni nella chiamata.
5. **kosgroup:** hreflang, noindex coerente e Canonical su tutti gli head da sistemare nel consolidamento degli head.
6. **Robots / AI crawler:** il 64% delle richieste nei log (ottobre) era di crawler su URL annidati (OAI-SearchBot e proxy residenziali): è stata la **causa confermata** del peggioramento del TTFB. Prima di aprire/chiudere bot AI in robots.txt, proporre `llms.txt` o limitare un crawler (es. quello del picco del 24/10), coordinarsi: incide sia sulla visibilità GEO sia sul carico server. Le regole di blocco vanno in nginx oggi, in Cloudflare se si attiva Cloud Edge.
7. **Prima di UPDATE massivi** su tabelle MODX: tabella di backup (`CREATE TABLE ..._bak_YYYYMMDD AS SELECT * ...`).
8. **Dopo ogni modifica** a chunk/template/impostazioni: svuotare la cache; la sitemap anniazzurri ha cache di 1 ora.

---

## 6. Strumenti e verifiche disponibili

- `controlla-link-relativi.ps1 -Sito <url> [-Elenco uri.csv]` – link relativi su sitemap o su tutte le pagine del DB; con `-Elenco` produce anche le pagine fuori sitemap.
- `controlla-og-image.ps1` – anteprime social.
- `monitor-notte.ps1` (+ `misura-ttfb.ps1`) – 100 campioni kosgroup vs Google (DNS, connessione, TLS, server, TTFB).
- Test sitemap (XML valido, conteggio, duplicati, pattern esclusi, tutti 200) – script PowerShell già usato il 21/10.
- BigQuery (progetto `kos-analytics-482819`): `chrome-ux-report.materialized.country_summary` per TTFB/CWV reali per origine (`country_code='it'`); GA4 `analytics_333083624.events_*` per traffico.

## 7. Backup presenti
- kosgroup: `modx_site_htmlsnippets_bak_20261020` (+ b, c), `modx_site_content_bak_20261020`, `modx_site_templates_bak_20261020`, `modx_site_tmplvar_contentvalues_bak_20261020`, `modx_site_tmplvars_bak_20261020`.
- anniazzurri: `modx_system_settings_bak_20261020`; `modx_site_htmlsnippets`, `modx_site_templates`, `modx_site_content` `_bak_20261021` / `_b` / `_c` (htmlsnippets e templates); `modx_site_tmplvar_contentvalues_bak_20261021b`.

## 8. Aperto / prossimi passi
- In attesa da MODX (follow-up del 26/10): user agent / IP del picco del 24/10 su kosgroup; stessa analisi sul pool di anniazzurri (saturazioni, richieste a PHP, quota di URL annidati / 404). In base all'esito: regola nginx 404 per URL a più livelli su anniazzurri (gli URL delle pagine sono tutti a un livello; esclusi /assets/, /manager/, /connectors/…) e/o worker da 4 a 8.
- Decisione su Cloud Edge (Cloudflare) per kosgroup e anniazzurri: richiede approvazione marketing + IT.
- Monitoraggio TTFB: PageSpeed Insights / API CrUX (finestra 28 giorni aggiornata ogni giorno) una volta a settimana; a fine novembre la finestra conterrà solo giorni successivi a 20/10 e 26/10. Dataset BigQuery di novembre a metà dicembre.
- Pagine kosgroup lente a cache fredda (1–2 s: approfondimenti, medici): ottimizzazione consigliata, riduce la saturazione nei picchi.
- Consolidamento head kosgroup (hreflang, noindex, Canonical, CSS unico) con agenzia SEO.
- Search Console anniazzurri: reinvio sitemap, controllo indicizzazione.
- CrUX ottobre (metà novembre) e novembre (metà dicembre).
- Ottimizzazione facoltativa: pagine kosgroup a ~1 s a cache fredda (approfondimenti, medici).
- Sessioni GA4 "smart tv" anomale a settembre (~2.900, 1 pagina ciascuna): possibile traffico automatico.
