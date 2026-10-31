# Istruzioni per la chat SEO/GEO/AEO e il flusso git – kosgroup.com e anniazzurri.it
Aggiornate al 31/10/2026. Questo file è il punto di partenza: gli altri file della cartella `monitor` sono citati sotto.

Contesto: dal 19 al 31 ottobre, in un'altra chat dedicata a prestazioni (TTFB) e crawler, sono stati fatti interventi
su database, web rules e codice dei due siti. Parte è già in produzione (fatta direttamente sul database o nel pannello
MODX Cloud), parte è pronta e deve passare dal flusso git (stage → produzione). Dettaglio completo e motivazioni:
`handoff-ttfb-kosgroup-anniazzurri.md`.

---

## 1. Da rilasciare con il flusso git (file pronti in `per-git/kosgroup/`)

Leggere `per-git/kosgroup/LEGGIMI.md`. Sostituire il contenuto degli elementi con i file corrispondenti:

| File | Elemento MODX kosgroup | Perché |
|---|---|---|
| `template-informativo.html` | template `informativo` (id 7, 221 pagine: news e approfondimenti) | tolte 2 chiamate non in cache eseguite a OGNI visita (`[[!pdoField]]` dentro un commento HTML, che MODX esegue comunque; `[[!DisplayResourceTags]]` → in cache); `Avatar.jpg` con barra iniziale |
| `listingNewsLast3.html` | chunk `listingNewsLast3` (articoli correlati) | `pdoResources` e `buildWhereExclude` in cache (prima `[[!...]]` a ogni visita); tolto `&includeContent` inutile |
| `kosplayer-banner.html` | chunk `kosplayer-banner` | corretto `<<img` (HTML non valido); immagini con barra iniziale |
| `listingComunicati.html` | chunk `listingComunicati` (id 96) | `href="/[[+tv.comunicatoPdf]]"`: i PDF dei comunicati erano link relativi (verificato: i 50 valori della TV sono tutti `assets/...`) |
| `documenti-ambulatorio-cardio-metabolico.html` | chunk id 209 | 5 PDF e 10 icone con barra iniziale |

Effetto atteso: approfondimento a cache calda da 33 a ~5-8 query (misurato sul test: 33 query / ~170-190 ms contro 5 / ~100 ms
del template medici). Nessun cambiamento visivo, salvo l'HTML corretto del banner.

### Anniazzurri – pagina `/news/` (da applicare nel repository di anniazzurri)
Oggi ~3 s a ogni visita e 465 KB: due `[[!getResources]]` non in cache che caricano anche il testo completo di 256 articoli.

Template `news` (id 23), sostituire la chiamata con:
```
[[getResources?
    &parents=`8`
    &limit=`1`
    &where=`[[buildWhereExclude]]`
    &tpl=`news-top`
    &includeTVs=`infoImage,newsImage`
    &sortby=`{"publishedon":"DESC"}`
]]
```
Chunk `news-list`, sostituire la chiamata con:
```
[[getResources?
    &parents=`8`
    &limit=`256`
    &offset=`1`
    &where=`[[buildWhereExclude]]`
    &tpl=`news-list-item`
    &includeTVs=`infoImage,newsImage`
    &sortby=`{"publishedon":"DESC"}`
]]
```
Note: `news-top` e `news-list-item` usano `[[+tv.newsImage]]`, che oggi NON viene caricata (`&includeTVs` aveva solo
`infoImage`). Prima del rilascio verificare se oggi le card mostrano immagini: se sì, controllare lo snippet `immagine`
prima di aggiungere `newsImage`. Misura prima/dopo:
```
1..2 | % { curl.exe -s -o NUL -w "TTFB %{time_starttransfer}s %{size_download} B`n" https://anniazzurri.it/news/ }
```
Da decidere lato SEO: paginare l'elenco (oggi 256 notizie in una pagina).

---

## 2. Già in produzione: da integrare nel repository (altrimenti un rilascio li sovrascrive)

I contenuti delle risorse NON passano dal flusso git, quindi le correzioni ai contenuti sono al sicuro. Vanno invece
allineati nel repository, esportandoli dalla produzione, gli elementi modificati direttamente:

**kosgroup**
- chunk: menu (`menu`), footer, `breadcrumb`, `menu-tab-*`, `aree-di-intervento-*`, `listingConvezioniLast3`, `footer-landing`;
- snippet: `HeaderNews`, `getMedici`, `getMediciStrutturaOrdered`, `CustomSitemap`, `VideoCarousel`, `CustomStructures`,
  `contenutiStruttura`, `Approfondimenti2`, `ogImage`, `comunicatoRedirect`;
- template 3, 8, 9, 10; TV `modale` e valori predefiniti delle TV;
- impostazione `link_tag_scheme = abs`; plugin Tagger con evento OnPageNotFound disattivato.

**anniazzurri**
- impostazione `link_tag_scheme = abs`;
- chunk: `menu-navbar`, `menu-side-navbar`, `footer`, `footer-cure-domiciliari`, `residenze-*`, `strutture-rsa-*`,
  `contactBox-*`, 284, 643 e i 26 chunk head con la riga
  `[[*searchable:is=`0`:then=`<meta name="robots" content="noindex, follow">`]]`;
- template: 13 `interna-menu-*`, 1, 30, 57, `recruiting`;
- snippet: `docList`, `ecmIscrizione`;
- risorsa `sitemap.xml` (pdoSitemap con `"searchable":1` nel `&where`).

**Web rules nginx** (pannello MODX Cloud, non nel deploy): anniazzurri ha la regola nuova del 31/10 per gli URL annidati,
copia completa in `anniazzurri-web-rules-20261031.conf`. kosgroup ha la regola `if ($uri ~ "^/(it|en)/.+/(it|en)(/|$)") { return 404; }`.
Se le web rules sono versionate, aggiornarne la copia nel repository.

**Già fatto il 31/10 in produzione (solo contenuti, nessuna azione git)**: 39 risorse kosgroup con link relativi ai PDF
corretti; `telto:` → `tel:`; nota `ftn3` → `#ftn3`; link "prenota online" con spazio iniziale (risorsa 233, TV approfondimenti);
email senza `mailto:` (Poliambulatorio San Martino, TV contatti_struttura). Backup: `modx_site_content_bak_20261031`,
`modx_site_tmplvar_contentvalues_bak_20261031`.

---

## 3. Regole per ogni modifica futura

1. Link interni sempre assoluti (`/…`): Wayfinder/pdoMenu/pdoCrumbs con `&scheme=`abs``; `href="/[[+uri]]"`;
   `makeUrl()` con schema `abs`; anche `kosSchema` (briciole). Immagini con `src="/assets/..."`.
2. Nessun plugin che risponda 200 a URL inesistenti.
3. Niente `[[!...]]` in head, menu, elenchi e template se non indispensabile (form FormIt sì): ogni chiamata non in cache
   gira a ogni visita. Attenzione: MODX esegue i tag anche dentro i commenti HTML `<!-- -->`; per commentarli usare `[[-...]]`.
4. Su anniazzurri la casella **"Ricercabile"** governa sitemap e noindex; ogni nuovo chunk head deve avere la riga del noindex.
5. Prima di aprire/chiudere bot AI in robots.txt, coordinarsi: il carico dei crawler sugli URL inventati è stato la causa
   confermata (da MODX) del peggioramento del TTFB tra maggio e settembre.
6. Prima di UPDATE massivi: tabella di backup. Dopo ogni modifica: svuotare la cache.

---

## 4. Verifiche dopo ogni rilascio

Script nella cartella `monitor` (usare queste copie, non versioni più vecchie):
```
cd C:\Users\danil\progetti\kosgroup\monitor
powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1
powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1 -Sito https://anniazzurri.it -Elenco .\uri-anniazzurri.csv
powershell -ExecutionPolicy Bypass -File .\controlla-og-image.ps1 -Sito https://kosgroup.com
powershell -ExecutionPolicy Bypass -File .\controlla-og-image.ps1 -Sito https://anniazzurri.it
powershell -ExecutionPolicy Bypass -File .\test-annidati-anniazzurri.ps1
```
Attesi: link relativi 0 (kosgroup: oggi 2 pagine, che vanno a 0 con il rilascio di `listingComunicati` e
`documenti-ambulatorio-cardio-metabolico`); anteprime social tutte OK; test annidati tutti OK (il 301 su `/chi-siamo/` è atteso).

Query sul database di produzione (e, se utile, di stage): `verifica-kosgroup.sql`, `verifica-anniazzurri.sql`.
Ogni riga deve avere trovato = atteso; una riga diversa indica una modifica persa o cambiata.

---

## 5. Decisioni SEO aperte

- **anniazzurri**: 11 pagine `rsa-in-provincia-di-{ascoli-piceno, asti, como, fermo, grosseto, imperia, pesaro-urbino,
  reggio-emilia, roma, rovigo, vercelli}/` rispondono 200 con contenuto vuoto (0 byte), template 0, fuori sitemap.
  Per quelle con strutture del gruppo (es. Roma/Parco di Veio, Grosseto/Il Poggione, Imperia/Sanremo, Reggio Emilia/Il Giardino):
  completarle con il template `provincia` (46); per le altre: 301 alla pagina regione.
- **anniazzurri**: paginazione di `/news/` (256 notizie in una pagina).
- **kosgroup**: le email ora sono offuscate con entità HTML (protezione anti-spam): verificare che resti così intenzionalmente.
- **Cloud Edge (Cloudflare)**: rimandato (serve approvazione marketing e IT). Non obbliga a bloccare i bot AI: si può
  attivare solo la cache. Risolverebbe anche i ritardi di connessione dei visitatori Windows dietro NAT degli operatori.

---

## 6. Tag Web Vitals in GTM (31/10)
Pronto in `gtm-web-vitals/` (leggere `gtm-web-vitals/LEGGIMI.md`). Non passa dal repository: si importa in Tag Manager
con "Unisci" nei container `GTM-5HCR54JT` (kosgroup, base v66) e `GTM-THMLNFX` (anniazzurri, base v76).
Aggiunge 2 tag, 2 attivatori e 11 variabili, senza toccare quelli esistenti; invia a GA4 l'evento `web_vitals`
(TTFB con scomposizione DNS / connessione / server, LCP, INP, CLS) e quindi a BigQuery (`analytics_333083624` e `analytics_250584060`).
Se si modificano i container in parallelo per la SEO, importare il tag nella stessa area di lavoro o pubblicare in sequenza,
per non creare conflitti di versione. Analisi: `gtm-web-vitals/web-vitals-bigquery.sql`.
