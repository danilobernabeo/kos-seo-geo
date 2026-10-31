# Coordinamento con il lavoro su prestazioni e crawler (kosgroup.com e anniazzurri.it) – 31/10/2026

In un'altra chat, dal 19 al 31 ottobre, sono stati fatti interventi **direttamente sui database e sulle web rules di produzione**, fuori dal flusso git. Prima del prossimo rilascio vanno integrati nel repository, altrimenti il deploy rischia di sovrascriverli. Dettagli completi in `handoff-ttfb-kosgroup-anniazzurri.md`.

## 1. Allineare il repository alla produzione (priorità)
Esportare dalla produzione e confrontare con il repository almeno questi elementi:

**kosgroup**
- chunk: menu, footer, breadcrumb, `menu-tab-*`, `aree-di-intervento-*`, `listingConvezioniLast3`, `footer-landing`, head con og;
- snippet: `HeaderNews`, `getMedici`, `getMediciStrutturaOrdered`, `CustomSitemap`, `VideoCarousel`, `CustomStructures`, `contenutiStruttura`, `Approfondimenti2`, `ogImage`, `comunicatoRedirect`;
- template 3, 8, 9, 10;
- TV `modale` e valori predefiniti delle TV;
- impostazione `link_tag_scheme = abs`;
- plugin Tagger con evento OnPageNotFound **disattivato**.

**anniazzurri**
- impostazione `link_tag_scheme = abs`;
- chunk: `menu-navbar`, `menu-side-navbar`, `footer`, `footer-cure-domiciliari`, `residenze-*`, `strutture-rsa-*`, `contactBox-*`, 284, 643, e i **26 chunk head** con la riga
  `[[*searchable:is=`0`:then=`<meta name="robots" content="noindex, follow">`]]`;
- template: 13 `interna-menu-*`, 1, 30, 57, `recruiting`;
- snippet: `docList`, `ecmIscrizione`;
- risorse: pagine regione e provincia, `sitemap.xml` (ora pdoSitemap).

Web rules nginx: modificate nel pannello MODX Cloud su entrambi i siti. Su anniazzurri c'è una regola nuova del 31/10 per gli URL annidati (`anniazzurri-web-rules-20261031.conf`): tenerne copia nel repository se ci sono anche le web rules.

## 2. Regole per ogni modifica futura
- Link interni sempre assoluti (`/…`): ogni chiamata Wayfinder, pdoMenu, pdoCrumbs con `&scheme=`abs``; `href="/[[+uri]]"`; `makeUrl()` con schema `abs`.
- Nessun plugin che risponda 200 a URL inesistenti.
- Niente chiamate non in cache `[[!…]]` in head, menu ed elenchi se non indispensabili: le pagine non in cache impiegano 1–2 s e saturano i worker PHP nei picchi (confermato da MODX: causa principale del peggioramento del TTFB).
- Su anniazzurri la casella **"Ricercabile"** governa sitemap e noindex; ogni nuovo chunk head deve contenere la riga del noindex.
- Prima di aprire o chiudere bot AI in robots.txt, coordinarsi: il carico dei crawler incide sul TTFB.

## 3. Da verificare dopo ogni rilascio
- **kosSchema** (ora genera le briciole: myBreadcrumb delega a lui) deve produrre link **assoluti**.
- `controlla-link-relativi.ps1` → atteso 0 su ~860 (kosgroup) e 0 su ~552 con `-Elenco` (anniazzurri).
- `controlla-og-image.ps1` → atteso ~860 su ~860.
- `verifica-kosgroup.sql` e `verifica-anniazzurri.sql` sul DB di produzione → tutte le righe trovato = atteso.
- `test-annidati-anniazzurri.ps1` → tutti OK (il 301 su `/chi-siamo/` è atteso: redirect del contenitore).

## 4. Da applicare tramite git (pronto, non ancora in produzione)
Pagina `/news/` di anniazzurri (oggi ~3 s a ogni visita, 465 KB).

Template `news` (23):
```
[[getResources? &parents=`8` &limit=`1` &where=`[[buildWhereExclude]]` &tpl=`news-top` &includeTVs=`infoImage,newsImage` &sortby=`{"publishedon":"DESC"}`]]
```
Chunk `news-list`:
```
[[getResources? &parents=`8` &limit=`256` &offset=`1` &where=`[[buildWhereExclude]]` &tpl=`news-list-item` &includeTVs=`infoImage,newsImage` &sortby=`{"publishedon":"DESC"}`]]
```
Cosa cambia: chiamate in cache (senza `!`), niente `&includeContent`, TV `newsImage` aggiunta (i tpl la usano ma oggi non viene caricata). Prima di applicare verificare se oggi le immagini delle card si vedono; se sì, controllare lo snippet `immagine`.

Misura prima e dopo:
```
1..2 | % { curl.exe -s -o NUL -w "TTFB %{time_starttransfer}s %{size_download} B`n" https://anniazzurri.it/news/ }
```
Da decidere lato SEO: paginare l'elenco (oggi 256 notizie in una pagina).

## 5. Decisioni SEO aperte
- **anniazzurri**: 11 pagine `rsa-in-provincia-di-{ascoli-piceno, asti, como, fermo, grosseto, imperia, pesaro-urbino, reggio-emilia, roma, rovigo, vercelli}/` rispondono **200 con contenuto vuoto (0 byte)**, template 0. Per quelle con strutture del gruppo: completarle con il template `provincia` (46); per le altre: 301 alla pagina regione.
- **kosgroup**: consolidamento head (hreflang, noindex coerente, Canonical su `head-paginazione`, CSS unico).
- **Cloud Edge (Cloudflare)**: rimandato. Se riconsiderato, non obbliga a bloccare i bot AI: si può attivare solo la cache.
