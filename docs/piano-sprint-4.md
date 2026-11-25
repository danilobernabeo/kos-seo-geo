# Piano dello sprint 4 (23-27 novembre 2026)

Documento interno (Danilo e Claude). Consegna sprint 4: **venerdì 27/11** (anticipata dal 4/12, registro 24/11); verifica di Cerved/ProWeb da confermare (calendario: 7-11/12); chiusura 14-18/12.

## Voci

| Sito | Voce dell'audit | Perimetro |
|---|---|---|
| kosgroup.com | 15 · Rendering pagina rallentato – CSS e JS (`defer`/`async`, CSS critico) | Nel perimetro |
| kosgroup.com | 25 · Minificare CSS (con l'unione dei fogli) | Nel perimetro |
| kosgroup.com | 16 · Ridurre CSS e JS non utilizzati | **Fuori perimetro, da consuntivare** |
| anniazzurri.it | 13 · Font display | Nel perimetro |
| anniazzurri.it | 22 · Minimizzare CSS | Nel perimetro |
| anniazzurri.it | 12 · Rendering pagina rallentato – CSS | **Fuori calendario, da consuntivare** |
| anniazzurri.it | 15-16 · Ridurre CSS e JS non utilizzati | **Fuori perimetro, da consuntivare** |

Tutte le attività svolte vanno nella nota di consegna, con le voci fuori perimetro indicate come tali (decisione del 22/11).

## Stato di partenza (produzione, 22/11)

- kosgroup.com: 10 fogli di stile separati (`bootstrap.min`, `all.min`, `fontawesome`, `default`, `nice-select`, `aos`,
  `swiper.min`, `photoswipe`, `style`, `custom`); 13 script locali senza `defer` più il widget di accessibilità;
  caratteri di testo (Satoshi, Manrope) già `font-display: swap`, Font Awesome senza.
- anniazzurri.it: 3 fogli (`custom.css`, `app.css`, `bootstrap-icons.min.css`); 9 script senza `defer` (jQuery, Bootstrap,
  Flickity e highlight.js da CDN, moduli e tracciamento HubSpot, widget di accessibilità, `app.js`, EMO); Open Sans e
  LobstersBlush `font-display: auto`, icomoon e bootstrap-icons `block`.

## Vincoli

- Tutto in produzione entro **mercoledì 25/11** (registro 24/11); giovedì 26/11, giorno prima della consegna, nessun rilascio in produzione. Dal 27/11 al 4/12 nessuna modifica a CSS e head di anniazzurri.it: la
  verifica dello sprint 3 (30/11-4/12) controlla AA-4, la cui correzione sta in `custom.css` e `kos-seo-geo.css`.
- Il risultato di AA-4 (stabilità visiva) non deve peggiorare: controllo del CLS nel collaudo.
- Test A/B della chat HubSpot (AA-4) in corso fino al 13/12: la velocità del sito cambia allo stesso modo su computer e
  telefono, la regola di decisione confronta le fasi al netto della variazione da telefono. Data del rilascio annotata in
  `docs/aa-4.md`.
- Elementi modificati dalla loro versione in `<sito>/baseline/`; installazione con pacchetto e ripristino.

## Calendario

| Giorno | Lavoro |
|---|---|
| Lunedì 23 | Misura del «prima» su un campione per template (due siti); inventario di CSS e JS per template; AA 13 sullo stage |
| Martedì 24 | Unione e minificazione dei CSS (KOS 25, AA 22) con un passaggio ripetibile dal repository; `defer` degli script (KOS 15, AA 12) |
| Mercoledì 25 | CSS critico e non utilizzati (KOS 16, AA 15-16); collaudo visivo stage/produzione a 390 e 1366 px; CLS |
| Giovedì 26 | Produzione; misura del «dopo»; verifica di dato strutturato, title, canonical invariati |
| Venerdì 27 | Riserva, nessun rilascio; bozza della nota dello sprint 4 |

## Rilascio (22/11, anticipato)

In produzione il 22/11 alle 17:22, dopo il confronto dei fogli sulle pagine di produzione (`scripts/css/confronta-css-*.py`)
e due collaudi visivi stage/produzione (il primo ha trovato l'errore delle facce di Open Sans, corretto in 8243313).
- kosgroup.com: `kos-15-25-css`, backup `/paas/c0095/backup-kos-seo-geo/20261122-102214/` (ripristino:
  `installa-elementi.php /www <cartella>/ripristino.json --scrivi`). Verifica: head con il solo `kos.min.css` (200, 485 KB,
  `max-age=15552000`), un solo JSON-LD.
- anniazzurri.it: `aa-13-22-css`, backup `/paas/c0072/backup-kos-seo-geo/20261122-102222/`. Verifica: `app.css` minificato
  con le facce di ripiego, nessun `@import` da jsdelivr, un solo JSON-LD.

Restano per dopo:
- `head-rsa` carica ancora `/dist/css/bootstrap-icons.min.css`, ora già dentro `app.css` (doppione in cache): da togliere
  dopo il 4/12, perché `head-rsa` è tra gli elementi della verifica dello sprint 3;
- schede dei centri KOS: foglio `tom-select@2.3.1` da jsdelivr (l'indirizzo esce in entità HTML perché il plugin
  ObfuscateEmail scambia «@2.3.1» per un'email; il browser lo legge comunque) e `lite-yt-embed.css`: dentro il template
  `strutture`, in carico alla chat prestazioni fino all'arrivo dei suoi file;
- home dei due siti: `answers.css` di Yext, che sparisce con la sostituzione della ricerca (linea C).

## Misure dopo il rilascio (22/11)

Laboratorio (`misura-lcp.mjs`, 3 caricamenti, mediane; `docs/evidenze/sprint4-prima/` e `sprint4-dopo/`). Il «dopo» di
kosgroup.com è delle 17:42, con la linea più lenta (TTFB mediano 248 → 333 ms): il confronto per orari diversi non basta.

| Sito, profilo | Prima visualizzazione − TTFB (mediana) prima → dopo | CLS massimo prima → dopo |
|---|---|---|
| anniazzurri.it mobile | 2.738 → 2.615 ms | 0,004 → 0,001 |
| anniazzurri.it desktop | 412 → 315 ms | 0,009 → 0,019 |
| kosgroup.com desktop | 367 → 268 ms | 0,041 → 0,049 (Prenota online, già prima) |
| kosgroup.com mobile | 2.407 → 2.602 ms | 0,173 → 0,148 (Prenota online, già prima) |

Il peggioramento di kosgroup.com mobile si concentra sulle pagine con sfondo `bg-pattern` e cambia molto da un caricamento
all'altro; per separare l'effetto dei fogli dalla rete, confronto A/B alternato nello stesso momento
(`scripts/css/ab-fcp-kos.py`: pagina di produzione con i 10 fogli originali contro la pagina com'è, 4 caricamenti per
variante, mobile 4G lenta, CPU 4x, terze parti bloccate):

| Pagina | Vecchio → nuovo (FCP − TTFB, mediana) |
|---|---|
| Home | 1.068 → 913 ms |
| Strutture | 1.073 → 889 ms |
| La nostra storia | 2.211 → 1.659 ms |
| Il management | 2.238 → 2.016 ms |
| Perché lavorare in KOS | 2.443 → 2.339 ms |
| Prenota online | 2.043 → 1.934 ms |
| Villa dei Pini Civitanova | 2.333 → 2.659 ms (*) |

(*) Le schede dei centri caricano anche `tom-select.css` da jsdelivr, che blocca la prima visualizzazione; nell'ambiente
di prova jsdelivr non è raggiungibile e il tempo del rifiuto diventa il limite (valori del «nuovo» tutti tra 2.610 e
2.676 ms). Da segnalare alla chat prestazioni: `tom-select` in locale (template `strutture`), è proprio la voce KOS 15.
Dati reali: Web Vitals sui visitatori (75° percentile) da rileggere a 2-3 settimane dal rilascio.

## KOS 2 — schede dei centri in cache (22/11, dalla chat prestazioni)

Causa (Web Vitals reali, tempo server p75 della sezione centri 764 ms contro 180-260 ms del resto del sito): chiamate
`[[!...]]` nei contenuti e nelle TV delle schede (template 2 e 4) e della pagina dei medici, rieseguite a ogni visita.
- `scripts/deploy/kos-centri-cache.php`: in cache Wayfinder (59), pdoMenu (2), getResources (2), getMedici e
  getMediciStrutturaOrdered (6), myBreadcrumb (1), galleryStruttura (29); 31 contenuti e 48 valori TV su 59 risorse.
  Restano non in cache `barraPrenotazioneDBmedicoPrestazione` e `mostraFino` (dipende dalla data).
- Snippet `barraPrenotazioneDBmedicoPrestazione` (pacchetto `kos-2-centri-cache`): HTML della barra in cache per scheda
  (`kosseogeo.barra_cache_ttl`, predefinito 3600 s; 0 = come prima). La produzione era cambiata dopo il 19/10 (tolta la
  variabile JavaScript `isAutoSelection`, 6 righe): baseline aggiornata alla versione di produzione e cache applicata su
  quella (b086bce).
- Stage 22/11: collaudi KOS 24 63/63 e KOS 11 150/150; TTFB mediano (5 misure, 19:52) produzione prima → stage dopo:
  Villa dei Pini Civitanova 714 → 308 ms, Myolab 597 → 304, Villa Margherita Vicenza Riabilitazione 666 → 287, Villa Rosa
  Diagnostica 540 → 311, Centro Medico Riabilitativo 599 → 290, pagina dei medici 722 → 302; pagine non toccate uguali
  (Ritiro referti 256/282, Strutture 329/335).
- Produzione 22/11 alle 20:04: backup `/paas/c0095/backup-kos-seo-geo/20261122-130358-centri-cache/` (contenuti:
  `kos-centri-cache.php /www --ripristina=<cartella>/ripristino.json --scrivi`; tabelle
  `modx_site_content_bak_20261122c`, `modx_site_tmplvar_contentvalues_bak_20261122c`) e `20261122-130359` (snippet:
  `installa-elementi.php /www <cartella>/ripristino.json --scrivi`).
- Verifica in produzione (22/11, 20:04-20:13): collaudi KOS 24 63/63 e KOS 11 150/150 (99 `IndividualPhysician`, 51
  `Person`), esiti identici riga per riga a quelli dello stage; TTFB mediano prima (19:52) → dopo (20:04): Villa dei Pini
  Civitanova 714 → 304 ms, Myolab 597 → 297, Villa Margherita Vicenza Riabilitazione 666 → 295, Villa Rosa Diagnostica
  540 → 274, Centro Medico Riabilitativo 599 → 281, pagina dei medici 722 → 258, Villa Pineta 321 → 293; pagine non
  toccate Ritiro referti 256 → 292, Strutture 329 → 325. Effetto sui Web Vitals reali: controllo della chat prestazioni
  in BigQuery una settimana dopo.
- Snippet della barra sullo stage: controllato il 23/11 con la prova di `kos-2-centri-cache.json`, già uguale alla versione di produzione (nessun riallineamento necessario).
- Aggiornamento delle barre (23/11, deciso da Danilo): le tabelle `kos_*` si aggiornano una volta al giorno (crontab:
  `cron_syncPrestazioni` 6:00, `cron_syncBase_v3` 6:05, `cron_syncMappatura_v2` 6:10, `cron_syncMediciInvisibili` 6:15,
  `update_prestazioni_tt` 7:00), quindi le barre si rigenerano una volta al giorno: `kosseogeo.barra_cache_ttl` = 90000
  (25 ore, un'ora di margine) e `scripts/deploy/kos-svuota-barre.php` (copia in `~/kos-cron/`) accodato con `;` al comando
  delle 7:00, registro in `~/kos-cron/svuota-barre.log`; crontab precedente in `~/crontab.bak`. Modifiche a mano agli
  abbinamenti durante il giorno: svuotare la cache dal manager. Medici e altre risorse salvati dal manager svuotano la
  cache da soli; importazioni dirette nel database e foto caricate dal gestore file no.

