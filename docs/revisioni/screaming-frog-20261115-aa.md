# Screaming Frog 15/11/2026 · anniazzurri.it · rapporto del revisore

Uso interno. Revisione indipendente del 15/11/2026 (Claude Fable 5.2) sulla scansione completa di anniazzurri.it
(7.580 URL, 484 HTML interne di cui 467 con risposta 200, 409 PDF; 15:12-19:32). Lo stato generale fuori dagli sprint è in
`screaming-frog-20261115-aa-generale.md`.

## Riepilogo

1. Il dato strutturato degli sprint 1 e 3 regge su tutte le pagine: 0 errori schema.org e Google, 57 residenze su 57 con nodo completo e uniforme, 14 pagine provincia con `ItemList`, briciole JSON-LD uguali a quelle visibili, nessun riferimento tipizzato senza indirizzo, nessuna data prima del 2000.
2. Smentita la frase «nessuna immagine sopra 200 KB» (AA 17, ripetuta nelle precisazioni da inviare il 16/11): 118 immagini sopra soglia, tra cui 8 dell'elenco dell'audit e 30 varianti WebP servite ai computer.
3. `/home-en`, collegata dal menu e dal piè di pagina di tutte le pagine, ha ancora 3 blocchi JSON-LD scritti a mano: la nota dello sprint 1 li rimandava allo sprint 3, la nota dello sprint 3 non ne parla.
4. Da correggere senza urgenza (11 punti): un file del CMS nella sitemap dei PDF, link interni e briciole verso indirizzi con redirect (piè di pagina di 460 pagine, 41 briciole), 3 pagine fuori sitemap senza `noindex`, email ancora offuscate su 18 residenze e 8 altre schede, 19 articoli con `dateModified` precedente a `datePublished`.
5. Limiti: nessuna richiesta ai siti è stata possibile (WebFetch rifiutato), quindi tutto è allo stato della scansione; due estrazioni della configurazione non funzionano (`email_offuscate`, `jsonld_brand`).

## Metodo e limiti

- **Dati:** 66 CSV e 111 sorgenti HTML copiati dal computer di Danilo con `device_stage_files` (le 57 schede residenza, le altre 8 schede, almeno una pagina per tipo). Tipi e campi dal report delle triple (`contiene_dati_strutturati_report_dettagliato.csv`, 78.028 righe, 461 pagine), non da `jsonld_tipi`.
- **Richieste ai siti:** nessuna. L'unico tentativo (WebFetch su un'immagine) è stato rifiutato dallo strumento; lo shell non raggiunge i siti. Le anomalie sono quindi allo stato delle 15:12-19:32 del 15/11.
- **Controlli sui sorgenti e non sui CSV:** scheda contatti, email offuscate, gerarchia dei titoli (l'export H2 contiene solo i primi due H2 per pagina), `loading="lazy"`.
- **Estrazioni da correggere in `docs/verifica-screaming-frog.md`:** `email_offuscate` è vuota su 467 pagine (l'XPath con «Function Value» non restituisce nulla); `jsonld_brand` non può trovare il marchio, che è un riferimento `@id` senza `name`.
- **Non coperti:** robots.txt e llms.txt (letti dal repository), `lastmod` delle sitemap, Rich Results Test, CLS (AA 4), bot AI, contenuto dei 356 sorgenti non copiati, file dell'audit (non leggibili da qui).
- Il docx di questo rapporto non è stato generato (vincolo: nessun altro file nel repository).

## Anomalie bloccanti

### B1 · AA 17: «nessuna immagine sopra 200 KB» non è vero (verifica dello sprint 2 dal 16/11)

- **Numeri:** 118 immagini sopra 200 KB (56 JPEG, 32 PNG, 30 WebP), richiamate da 69 pagine.
- **Tre casi distinti:**
  - 8 mappe regionali dell'elenco dell'audit ancora tra 235 e 264 KB, usate così come sono in `/residenze` (`<img src="assets/img/lombardia-m.png">`, 8 immagini, 1.983 KB in tutto);
  - 30 varianti WebP sopra soglia, su 42 pagine tra cui 14 schede residenza: sono quelle che un computer scarica davvero (`sizes` fino a 1600 px);
  - varianti PNG di riserva più pesanti dell'originale: `liguria-m-480.png` 548 KB contro 257 KB dell'originale, `group-9-800.png` 1.324 KB in home. Le riceve solo un browser senza WebP.
- **Esempi:** `/residenza-anni-azzurri-idice` (`Idice_16-1600.webp`, 542 KB), `/residenza-anni-azzurri-castagnola` e `/rsa-in-liguria` (`…castagnola-1500.webp`, 343 KB), `/residenza-anni-azzurri-villa-san-vincenzo` (328 KB).
- **Evidenza:** `immagini_oltre_200_kb.csv`; `tutti_i_link_in_uscita.csv` (percorso `picture/source` o `picture/img`); `anniazzurri/dati/aa-17-immagini-pesanti.txt`.
- **Affermazioni toccate:** nota sprint 2, «Come verificare» di AA 17; precisazioni del 16/11 («AA 17: nessuna immagine sopra 200 KB»). L'agenzia verifica dalla scheda Rete di un computer: su Idice trova 542 KB.
- **Causa:** il ridimensionamento non ha un tetto di peso sulle larghezze grandi e rigenera i PNG senza ottimizzarli; le 8 mappe sono state ridotte ma non sotto soglia e la pagina `/residenze` non passa da `<picture>`.
- **Correzione:** qualità più bassa (o larghezza massima 1200) sulle 30 varianti WebP; le 8 mappe in WebP dentro `<picture>` anche su `/residenze`; variante servita solo se pesa meno dell'originale (regola già usata su kosgroup.com). Se non si rilascia prima del 16/11, correggere la frase delle precisazioni: «le 101 immagini dell'allegato ridotte da 51,4 a 13,2 MB; restano sopra 200 KB 8 mappe regionali e 30 varianti WebP delle larghezze maggiori, in correzione».

### B2 · `/home-en`: tre blocchi JSON-LD scritti a mano (nota dello sprint 3)

- **Pagine:** 1, ma collegata da 455 pagine nel menu e 459 nel piè di pagina.
- **Evidenza:** `jsonld_blocchi` = 3; sorgente: `Organization` senza `@id` e con logo `https://anniazzurri.it//assets/logo-anni.svg`, `WebSite` senza nome, `BreadcrumbList` con una sola voce di nome «home_en». La pagina è fuori sitemap, senza `noindex` e senza hreflang.
- **Affermazioni toccate:** nota sprint 1, «Sprint 3: … blocchi JSON-LD residui delle due pagine di lavoro (fuori sitemap) dentro il grafo unico»; la nota dello sprint 3 non li cita. Regola del progetto: un solo `@graph`, niente dato strutturato scritto a mano.
- **Causa:** template con head proprio, non passato alla libreria `aaSchema`.
- **Correzione:** `aaSchema` anche su questo template (ente per `@id`, niente briciole se non si vedono) oppure una riga nella nota dello sprint 3 che la dichiara aperta. Con lei `/azzurramente`, che non ha nessun JSON-LD.

## Da correggere

| # | Voce | Anomalia | Pagine ed esempi | Causa | Correzione |
| --- | --- | --- | --- | --- | --- |
| C1 | AA 3 | Un file del CMS nella sitemap dei PDF esclusi | `/core/vendor/james-heinrich/phpthumb/images/phpThumb.com.pdf` (403), riga 220 di `anniazzurri/files/sitemap/sitemap-pdf-noindex.xml`: 217 indirizzi per 216 PDF | L'elenco percorre tutta la radice del sito | Togliere la riga e limitare lo script a `/assets/` |
| C2 | AA 3 | 5 PDF indicizzabili con il titolo di Word, 1 con «Carta Intestata KOS» | Abitare il Tempo (3 richieste di ricovero), Dorica (2 moduli): «Microsoft Word - …» contro il titolo del piano; `…/santa-maria-in-chienti/Organizzazione.pdf` | File probabilmente sostituiti dopo la riscrittura (da verificare con `controllo-pdf-nuovi.php`) | Riscrivere i metadati |
| C3 | AA 3 | I 192 indicizzabili non sono più quelli del piano | 2 PDF del piano non collegati da nessuna pagina (`/assets/doc/anniazzurri-a-casa-servizio-di-assistenza-domiciliare.pdf`, `/assets/doc/cure-domiciliari-anniazzurri.pdf`); 2 nuovi (`Anni Azzurri Senior Living.pdf`, pieghevole dell'Hospice Beati Coniugi Martin) | Documenti cambiati dopo il 3/11 | Aggiornare `piano-pdf.json`; decidere `noindex` o rimozione dei 2 senza collegamenti |
| C4 | AA 8 | Briciole (visibili e JSON-LD) con livello intermedio verso un indirizzo con redirect | 41 pagine: `/soluzioni/` (17, es. `/cure-intermedie`), `/chi-siamo/` (13, es. `/kos-academy`), `/sos-famiglia` (7), `/tariffe-e-convenzioni` (4) | Contenitori FirstChildRedirect | Nella funzione comune usare l'indirizzo di arrivo del contenitore, in entrambe le briciole |
| C5 | Regole sui link | Link interni verso redirect in tutte le pagine | Piè di pagina: `/notizie-ed-eventi` (460 pagine, catena 301 → `/news` → `/news/`), `/domande-frequenti` (459). Contenuto: `/cure-domiciliari` (4 pagine, home compresa), `/soluzioni/` (2), `/le-nostre-case-in-lombardia` (1) | Indirizzi vecchi nei chunk | Indirizzi finali nei chunk del piè di pagina e della home |
| C6 | Regola «Ricercabile» | 3 pagine fuori sitemap ma indicizzabili | `/home-en`, `/azzurramente`, `/iscrizione-convegno-parkinson-realta-prospettive`: 200, nessun `noindex` | Head propri senza la riga `[[*searchable:is=…]]`, o esclusione dalla sitemap per altra via | Stessa riga negli head, oppure rimetterle in sitemap |
| C7 | AA 21 | Seconda email offuscata nel contenuto | 18 residenze: 15 con «RSA Aperta … Scrivi a», 3 con «scrivere all'indirizzo email» (es. Rezzato, Navigli, Villa Clarice) | Email scritte nel contenuto, offuscate da EMO | Escluderle dall'offuscamento o precisare la nota (vedi «Affermazioni smentite») |
| C8 | AA 2, AA 21 | Le 8 schede non residenza hanno ancora il riquadro vecchio | 5 centri diurni, 2 hospice, appartamenti di Bergamo: nessun nodo struttura, email solo offuscate. `/centro-diurno-integrato-anni-azzurri-biancospino` ha ancora 7 `data-yext-field` (dati di Il Giardino, `href="tel:0522 953888"` con lo spazio) | Fuori dalle 57 schede dello sprint 3 | Dichiararlo nella nota; togliere il markup Yext da Biancospino |
| C9 | Dato strutturato | `dateModified` precedente a `datePublished` | 19 articoli, es. `/dimissioni-protette-cosa-sono-e-come-funzionano` (31/7 contro 10/8/2026), `/insonnia-negli-anziani-cause-e-rimedi` | Pubblicazione programmata dopo l'ultima modifica | Emettere il maggiore tra le due date |
| C10 | AA 8 | `BreadcrumbList` senza briciole visibili | `/iscrizione-convegno-parkinson-realta-prospettive`: 3 voci nel JSON-LD («Anni Azzurri», «iscrizione eventi», titolo), nessuna briciola nella pagina | Template del modulo di iscrizione senza lo snippet `BreadCrumb`; contenitore «iscrizione eventi» non in `aa.breadcrumb_exclude` | Aggiungere le briciole visibili o togliere il `BreadcrumbList`; escludere il contenitore |
| C11 | Regole sui link | Link interno con `utm_` e link rotto | `/residenza-anni-azzurri-pgr-cinisello` → `/homecare-academy?utm_source=…` (banner, come quello tolto da kosgroup.com il 12/11); `/sportello-sociale-orientamento-per-assistenza-anziani` → `telto:800131851` (404) | Banner e refuso nel contenuto | Togliere i parametri; `tel:800131851` |

## Minori

- **Titoli (AA 6):** il riquadro contatti nuovo ha `<h5>Contatti</h5>` su 57 residenze e sulle altre schede: gerarchia H1 › H5 › H2, mentre la nota dello sprint 2 dice che i titoli del blocco contatti sono diventati paragrafi.
- **Link relativi (regola del progetto):** 3 link nel contenuto (`/rsa-aperta-lombardia`, `/giornata-nazionale-delloss`, `/il-mantello-del-custode-dare-voce-alle-scelte-dare-senso-alla-cura`) e immagini con `src="assets/img/…"`; funzionano grazie a `<base href>`.
- **`priceRange`:** forme diverse («Da 97 €», «Da 105,00 €», «64,86 €» senza «Da»); su Quarto d'Altino «Da 78,5 €» non compare nel testo della scheda (da verificare).
- **Ente:** `telephone` «800 131 851» senza prefisso internazionale, mentre le residenze usano `+39…`.
- **PDF:** stesso file con due indirizzi (`…Domanda unica per l'inserimento.pdf` con `'` e con `%27`); 68 carte dei servizi contengono link `http://anniazzurri.it/…` (301).
- **H1 uguali:** `/melograno-argento-vivo` e la scheda Melograno; `/convegno-parkinson-realta-prospettive` e la pagina di iscrizione.
- **Una mancata risposta:** `…/rsa/residenza-anni-azzurri-beato-angelico-1500.webp`, «Connessione rifiutata» alle 15:36 (7 pagine la richiamano); le altre varianti rispondono. Da ricontrollare.
- **Avvisi Google facoltativi:** `priceRange` su 33 schede (atteso), orari su 2 (Il Poggione, Sestri Ponente, atteso); `image` e `dateModified` su 70 `ScholarlyArticle` (pubblicazione dell'autrice, lavoro extra).

## Affermazioni delle note smentite o da precisare

| Nota | Affermazione | Riscontro |
| --- | --- | --- |
| Sprint 2 e precisazioni, AA 17 | «nessuna sopra 200 KB» | Falso: B1 |
| Sprint 1, passaggi successivi | Blocchi JSON-LD residui nel grafo unico con lo sprint 3 | Non fatto su `/home-en`, non dichiarato: B2 |
| Sprint 3, AA 21 | «l'email del direttore resta offuscata» | Da precisare: su 18 schede è offuscata anche un'email nel contenuto (C7) |
| Sprint 3, AA 2 | «markup Yext nei contenuti di 37 schede convertito» | Resta su Biancospino (C8) |
| Sprint 2, AA 3 | «186 titoli riscritti» nella forma «documento – struttura» | 5 PDF indicizzabili hanno oggi il titolo di Word (C2) |
| Sprint 2, AA 6 | Titoli del blocco contatti diventati paragrafi | Il riquadro dello sprint 3 ha un `<h5>` |
| Sprint 2, AA 1 | «le altre con caricamento differito» | Vero per elenchi, testate e news; le gallerie di 32 schede non hanno `loading="lazy"` (fuori dalla voce, vedi rapporto generale) |

## Cosa risulta corretto

- **Base:** 459 pagine su 461 con un solo JSON-LD e `@graph` (eccezioni: `/home-en`, `/azzurramente`); un H1 per pagina; canonical uguale all'URL, senza barra finale tranne home e `/news/` (eccezione già nota); la pagina con `utm_` ha il canonical pulito.
- **Convalida:** 0 errori schema.org e Google su 461 pagine; nessun riferimento tipizzato a una struttura senza `address` (57 `mainEntity` e 45 voci di elenco, tutte con indirizzo); `brand` e `parentOrganization` solo per `@id`.
- **Residenze (AA 2, 5, 11):** 57 su 57 con `@id` = canonical + `#localbusiness`, `["MedicalBusiness","MedicalOrganization"]`, `Geriatric`, `brand` e `parentOrganization` verso `https://anniazzurri.it/#organization`, indirizzo, coordinate, telefono `+39…`, email, immagine; orari su 55, fax su 48, `priceRange` su 24. Nessuna stringa «yext» nei 57 sorgenti.
- **Scheda contatti (AA 21):** 57 su 57 con indirizzo, telefono ed email uguali al nodo, email in chiaro, una sola email offuscata nel riquadro.
- **Province (AA 10):** 14 pagine, 45 residenze, ogni voce con nome, URL, immagine, indirizzo; `priceRange` uguale alla scheda; 5 pagine con almeno tre voci (Milano 11, Genova 6, Torino 5, Ancona 3, Modena 3).
- **Briciole (AA 8):** stessi nomi e stessi livelli su 457 pagine; nessuna briciola in home. Eccezioni: C4 (indirizzi con redirect), C10, `/home-en` (B2); `/residenze` mostra la sola voce «Anni Azzurri» e non ha `BreadcrumbList`.
- **Ente:** KOS Care S.r.l., partita IVA, `parentOrganization` verso `https://kosgroup.com/#organization`; nessun `sameAs` verso kosgroup.com.
- **PDF (AA 3):** 192 senza `noindex`, 216 con `X-Robots-Tag: noindex`, 25 con `Link rel="canonical"` uguali al piano, tutti verso un PDF indicizzabile; nessun PDF indicizzabile senza titolo.
- **Sitemap:** 457 pagine, tutte 200 e indicizzabili; `/alzheimer-cura-e-assistenza` ha `noindex, follow` ed è fuori sitemap.
- **Title e description (AA 20):** nessuno mancante o ripetuto; 6 title tra 66 e 70 caratteri, come dichiarato; description tutte entro 160; marchio mai doppio.
- **Titoli (AA 6, 18):** nessun «Guarda meno / Guarda di più» nel testo di un titolo, nessun H2 ripetuto nei 111 sorgenti.
- **Alt (AA 9):** nessuna `<img>` senza attributo `alt` su 467 pagine.
- **Date:** nessuna prima del 2000 né futura.
- **Ricerche nel codice:** 0 per `gclid`, `gad_source`, stage, `modx.dev`, `[[`, `pagetitle]]`, `yextapis.com`, «Lorem ipsum».
