# Screaming Frog 15/11/2026 · kosgroup.com · rapporto del revisore

Uso interno. Revisione indipendente del 15/11/2026 (Claude Fable 5.2) sulla scansione completa di kosgroup.com
(8.766 URL, 914 pagine HTML 200, 9:51-14:51).

## Riepilogo

1. La scansione conferma la base degli sprint 1-3 su tutte le 914 pagine: un blocco JSON-LD, un H1, canonical uguale all'URL, nessun errore schema.org, sitemap pulita (866 URL, tutti 200), hreflang reciproci, nessuna data anteriore al 2000.
2. Unico errore di convalida: «address obbligatorio» su 308 pagine (400 riferimenti). La correzione nel codice (1b5365d) copre tutti i punti che li emettono, ma è successiva alla scansione: lo stato in produzione non è dimostrato, va chiuso con il ricontrollo delle 308 URL prima del 16/11.
3. Nota dello sprint 3, KOS 7: due affermazioni non reggono su tutte le pagine (foto delle strutture con `alt=""` su 12 pagine di riabilitazione; 17 immagini senza attributo `alt` su 2 pagine). KOS 11: la nota non cita la correzione del 15/11.
4. Da correggere senza urgenza: 3 schede struttura senza nodo non dichiarate nelle note, `brand` su 43 schede contro la decisione del 29/10, og:image predefinita su 43 pagine delle cure, dati dei reparti di Villa Margherita, tag MODX eseguiti nel commento del chunk `head`.
5. Limiti: l'estrazione `briciole_visibili` è vuota su 914 pagine (XPath non adatto a kosgroup.com), quindi briciole verificate su un campione di 47 sorgenti; `/villamargherita` e le 5 landing non sono state scansionate (non collegate, fuori sitemap).

## Metodo e limiti

- Dati: i 38 CSV già copiati, più 24 CSV e 44 sorgenti copiati dal computer di Danilo con `device_stage_files` (disponibile). Tipi e campi dal report delle triple (`contiene_dati_strutturati_report_dettagliato.csv`, 138.932 righe, 914 pagine), non da `jsonld_tipi`.
- Script: pandas sui CSV; `php scripts/test/seo-schema-simulazione.php` → «Tutti i controlli superati».
- Richieste ai siti: una sola (WebFetch su `https://kosgroup.com/villamargherita`); lo strumento non mostra il JSON-LD. Dal container il sito non è raggiungibile (403 del proxy): nessuna verifica diretta della produzione dopo le 15:40.
- Non coperti dalla scansione: `/villamargherita` (nodo `Hospital`), le 5 landing (`/it/landing/…`), robots.txt, llms.txt, redirect dei domini storici, bot AI, LCP/CrUX, contenuto dei metadati dei PDF oltre al titolo.
- Briciole visibili: campione di 47 sorgenti (almeno uno per sezione), non tutte le pagine.
- Il docx di questo rapporto non è stato generato (vincolo: nessun altro file nel repository).

## Anomalie bloccanti

### B1 · Errore «address obbligatorio»: correzione non ancora dimostrata in produzione (verifica sprint 2 e nota sprint 3)

- **Pagine:** 308 (148 professionisti, 160 tra articoli, eventi e video), 400 riferimenti: 288 `MedicalClinic`, 110 `Hospital`, 2 `Dentist`. Per predicato: `mentions` 252, `practicesAt` 99, `worksFor` 49.
- **Esempi:** `/it/chi-siamo/qualita-delle-cure/medici/stefano-albanelli` (esempio della nota sprint 3), `/it/chi-siamo/qualita-delle-cure/medici/querci-martina`, `/it/news-e-media/approfondimenti/sensibilita-dentale-cause-sintomi-e-rimedi-piu-comuni`.
- **Evidenza:** `report_riepilogo_errori_dati_strutturati.csv` (Google Local Business Listing, Errore, `address` required: 203 + 110 + 2 URL); triple: nodo riferito con `@type`, `@id`, `name`, `url` e nessun `address`. L'elenco `docs/revisioni/screaming-frog/ricontrollo-riferimenti-20261115.txt` coincide con le 308 URL in errore (0 differenze).
- **Codice (verificato):** tutti i riferimenti tipizzati alle strutture passano da una sola funzione, `$riferimentiStrutture` (`shared/snippets/seoSchema.php`, righe 788-822), usata da `mentions` di articoli e video (righe 1297 e 1338) e da `practicesAt`/`worksFor` (righe 901-904): ora emette sempre `address` (stessi campi del nodo completo) e `telephone`. Nella scansione non esistono altri nodi di tipo attività locale senza `address` (conteggio sui tipi: 288 + 110 + 2 = i 400 dell'errore). Non toccati dalla correzione e senza obbligo di indirizzo: `department` (`MedicalOrganization`), `parentOrganization` del reparto (solo `@id`, nodo completo nel grafo), `affiliation` dell'autore (solo `@id`), `about` delle landing (solo `@id`).
- **Cosa resta aperto:** (a) la scansione è precedente al rilascio; (b) il JSON-LD è in cache di pagina: se la cache delle risorse non è stata svuotata dopo il rilascio, le pagine servono ancora il grafo vecchio; (c) dopo la correzione restano sui riferimenti gli avvisi facoltativi `geo`, `openingHoursSpecification`, `priceRange` (oggi 203 + 110 + 2 URL), non errori.
- **Correzione proposta:** prima dell'invio delle precisazioni del 16/11, scansione in modalità elenco delle 308 URL del file di ricontrollo: attesi 0 errori Google. Solo dopo ha valore la frase delle precisazioni «Dal 15 novembre il riferimento riporta anche indirizzo e telefono».

### B2 · Nota sprint 3, KOS 7: testi alternativi non uniformi (prima dell'invio della nota)

- **Pagine:** 12 pagine `/it/servizi-e-cure/riabilitazione/…` con 49 foto di strutture negli elenchi con `alt=""`; 2 pagine con 17 immagini senza attributo `alt`.
- **Esempi:** `/it/servizi-e-cure/riabilitazione/riabilitazione-neurologica` (40 foto), `/it/servizi-e-cure/riabilitazione/riabilitazione-ortopedica` (40), `/it/servizi-e-cure/riabilitazione/riabilitazione-estensiva-e-generale-geriatrica` (sorgente: `<img src="/assets/img/strutture/istituto-santo-stefano.jpg" alt="" loading="lazy">`); senza attributo: `/it/centri/villa-dei-pini-civitanova` (14 × `medico-thumb.svg`), `/it/news-e-media/progetti-speciali/2025/settimana-del-cervello-2025` (3).
- **Evidenza:** `link_in_entrata_a_immagini_senza_testo_alt.csv` (destinazioni `/img/strutture/`), `link_in_entrata_a_immagini_senza_attributo_alt.csv` (17 righe), colonna `img_senza_alt 1` (14 e 3; 0 sulle altre 912 pagine).
- **Causa probabile:** il pattern «foto negli elenchi con il nome della struttura» è stato applicato al template delle cure di psichiatria (lì `alt="Beata Corte"`), non a quello della riabilitazione; i segnaposto dei medici di Villa dei Pini Civitanova sono in un blocco di contenuto scritto a mano.
- **Correzione proposta:** stesso pattern nel chunk degli elenchi delle cure di riabilitazione; `alt=""` sui 14 segnaposto e alt sulle 3 immagini del progetto speciale; in alternativa precisare la nota. Rientra in KOS 7.

## Anomalie da correggere

### C1 · Tre schede struttura senza nodo, non dichiarate nelle note (KOS 24)

- **Pagine:** 3 su 63 pagine `/it/centri/<scheda>`: `/it/centri/poliambulatorio-villalba` (856 parole, 70 link in entrata), `/it/centri/santo-stefano-casorate`, `/it/centri/santo-stefano-mede`. Hanno solo `WebPage` e briciole.
- **Evidenza:** triple: nessun soggetto con `@id` `…#localbusiness`. Le altre: 58 con nodo proprio, 2 unità di Villa Margherita (`#department`).
- **Causa:** nessun dato anagrafico importato (due entità Yext sulla stessa scheda per Villalba, nessuna entità per le altre due: `docs/kos-24.md`); regola «senza indirizzo niente nodo».
- **Correzione proposta:** compilare le TV `str.*` dai dati del sito (come per Valdaso) oppure dichiararlo nelle precisazioni del 16/11: chi verifica la voce 24 su tutte le schede trova 3 pagine senza `LocalBusiness`. Nel perimetro.

### C2 · `brand` sulle schede di kosgroup.com, contro la decisione del 29/10

- **Pagine:** 43 schede su 58 con `brand` `{"@type":"Brand","name":…}`: Santo Stefano Riabilitazione 22, Neomesia 14, Polo Diagnostico 4, Myolab, Fidia, Villa dei Pini.
- **Esempi:** `/it/centri/casa-villa-maura` (Neomesia), `/it/centri/centro-cardinal-ferrari` (Santo Stefano Riabilitazione), `/it/centri/punto-prelievi-biemme` (Polo Diagnostico).
- **Evidenza:** triple `brand` → nodo `Brand`; codice `seoSchema.php` riga 966 (TV `str.marchio`).
- **Decisione non rispettata:** CLAUDE.md e `docs/specifica-grafo-kos.md` («`brand` solo sulle residenze, come riferimento a `https://anniazzurri.it/#organization`»; «la TV `str.marchio` di kosgroup.com si svuota»).
- **Correzione proposta:** svuotare `str.marchio` sulle schede di kosgroup.com (script con backup) dopo la verifica dello sprint 2, perché tocca `kosSchema`.

### C3 · og:image predefinita su pagine delle cure che hanno una testata propria

- **Pagine:** 43 (30 su 31 di `/it/servizi-e-cure/psichiatria/…`, 13 trattamenti di `/it/servizi-e-cure/riabilitazione/trattamenti/…`). Verificato sul sorgente di un caso; per gli altri dedotto dal template.
- **Esempi:** `/it/servizi-e-cure/psichiatria/trattamenti/psicoterapeutico-approccio-cognitivo-comportamentale` (testata `/assets/img/servizi-e-cure/…__.jpg`, og:image `kos-condivisione-social.jpg`), `/it/servizi-e-cure/psichiatria/psicosi`, `/it/servizi-e-cure/riabilitazione/trattamenti/gestione-del-dolore`.
- **Evidenza:** `interni_tutti.csv`, colonna `og_image 1` (172 pagine con l'immagine predefinita; le altre sono pagine istituzionali, elenchi, 18 professionisti senza foto, 8 articoli senza immagine).
- **Causa probabile:** la testata di questi template non viene da `infoImage` né da `assets/img/header/<alias>.jpg`, i due percorsi che `ogImage` prova.
- **Correzione proposta:** `&ogLegacy` con il percorso `assets/img/servizi-e-cure/[[*alias]]__.jpg` nel chunk head di quei template. Miglioria nel consolidamento degli head.

### C4 · Villa Margherita Vicenza: dati dei reparti e pagina della sede

- **Pagine:** 2 unità, più la landing non scansionata; 55 pagine citano `https://kosgroup.com/villamargherita` nel JSON-LD.
- **Evidenza e rilievi:**
  - `knowsAbout` dei due `#department` quasi identici: 26 voci comuni su 27 (psichiatria) e 28 (riabilitazione); psichiatria elenca «Riabilitazione mielolesioni», «Rieducazione delle Menomazioni Vescico-Sfinteriche»; «Disturbo ossessivo compulsivo» e «Disturbo post traumatico da stress» stanno solo sulla riabilitazione. Nessun `medicalSpecialty` sui due reparti.
  - `description` del nodo `Hospital`: «Chiama lo 0444 043322», mentre `telephone` è `+39044415851`.
  - Avviso Google su 2 pagine: «`department` is recommended to be of type `LocalBusiness`» (`report_errori_dati_strutturati.csv`), non citato nella nota sprint 3.
  - `/villamargherita` non è nella sitemap (866 URL, `sitemap_url_nella_sitemap.csv`) e nessuna pagina la collega (`/it/villa-margherita` punta ancora alle due unità): il registro (12/11) riporta «produzione 13/11» per la sitemap. WebFetch del 15/11: 200, canonical su sé stessa, title «… — KOS», nessun menu, visibile solo lo 0444 043322 (da verificare sul sorgente: il registro dice che la pagina mostra anche il centralino).
- **Causa probabile:** servizi collegati uguali sulle due pagine delle unità; descrizione della landing scritta per la campagna.
- **Correzione proposta:** servizi distinti per unità (contenuto, KOS); togliere il numero dalla descrizione del nodo; allineare il registro; aggiungere `/villamargherita` e una landing all'elenco della prossima scansione.

### C5 · Estrazione `briciole_visibili` vuota su tutte le pagine

- **Pagine:** 914 su 914 (`estrazione_personalizzata_briciole_visibili.csv`, colonna vuota).
- **Causa:** l'XPath `//*[contains(@class,"breadcrumb-item")]` vale per anniazzurri.it; kosgroup.com usa `<nav id="breadcrumbs">` con `<a>` e `<span class="bc-attivo">`.
- **Effetto:** il confronto della tabella «Copertura» (KOS 12-13) non è avvenuto. Campione di 47 sorgenti: 43 con nomi e URL identici tra visibili e `BreadcrumbList`, 4 senza briciole né `BreadcrumbList` (`/it`, `/en`, `/it/strutture`, settimana del cervello).
- **Correzione proposta:** in `docs/verifica-screaming-frog.md` un XPath per sito (`//nav[@id="breadcrumbs"]` per kosgroup.com) e ricontrollo nella prossima leggera.

### C6 · Tag MODX eseguiti nel commento del chunk `head`

- **Pagine:** tutte (47 sorgenti su 47).
- **Evidenza:** `kosgroup/elements/chunks/head.html`, righe 4-8: il commento contiene `[[*ruolo]]` e `[[!...]]`; nel sorgente servito diventano «es. ; default introtext» e «nessuna chiamata .».
- **Regola non rispettata:** «MODX esegue i tag anche dentro i commenti HTML: per disattivare un tag si usa `[[- ...]]`»; `[[!...]]` è una chiamata non in cache nell'head di ogni pagina. Il commento («consolidamento 2026: KOS-2, voce 24») è anche un dettaglio interno visibile a chi apre il sorgente.
- **Correzione proposta:** spostare il commento in `[[- … ]]`. Tocca l'head (sprint 2): dopo il 20/11 o come correzione concordata.

### C7 · Altri rilievi da correggere

| Rilievo | Pagine | Esempi ed evidenza | Correzione |
| --- | --- | --- | --- |
| Link interni relativi veri (oltre ai falsi positivi) | 1 pagina, 6 link | `/it/news-e-media/approfondimenti/sclerosi-multipla-tra-ricerca-cura-e-riabilitazione` → `…/fisioterapista`, `…/logopedista`, `/it/centri/centro-cardinal-ferrari` (`relativo_al_percorso_link_in_uscita.csv`) | `href="/it/…"` nel contenuto |
| Pagine in sitemap senza link interni | 9 | 5 news di Villa dei Pini (`/it/news-e-media/news/2026/avviata-la-chirurgia-oculistica-a-villa-pini`…), 2 progetti speciali, `/it/parla-con-noi`, `/it/villa-dei-pini` (`pagine_orfane.csv`) | Capire perché le 5 news non escono negli elenchi; link o esclusione per le altre |
| Pagina povera | 1 | `/it/villa-dei-pini`: 38 parole, due riquadri, orfana | Testo come `/it/villa-margherita` o redirect |
| Medici senza `medicalSpecialty` | 2 su 99 | `/medici/rodolfo-calorio`, `/medici/domenico-corrado-ali` | Ruolo senza corrispondenza: da precisare nella nota |
| Articoli senza `image` (og:image predefinita) | 8 | `/it/news-e-media/news/riolo-terme`, `/it/news-e-media/approfondimenti/fisiatria`, `…/il-medico-risponde` (avviso Google Article) | File dell'immagine mancante: ricaricare |
| Scheda con azione di prenotazione senza catalogo | 1 | `/it/centri/poliambulatorio-parco-di-veio` (`potentialAction` sì, `hasOfferCatalog` no; senza specialità, servizi, `sameAs`) | Import delle prestazioni TuoTempo |

## Anomalie minori

- **Title senza marchio o con «| Kos»:** 5 pagine (`/it/mappa-del-sito`, `…/news/2026/giornata-mondiale-dell-alzheimer-…`, `…/approfondimenti/neurodivergenza-…`; «| Kos» su `/it/centri/villa-dei-pini-civitanova/check-up-prevenzione` e `/it/strutture/punti-prelievo/check-up-analisi`). Fonte `titoli_di_pagina_tutti.csv`.
- **Description:** 4 sotto 70 caratteri; `/it/news-e-media/approfondimenti/assistente-sociale` ha una description in forma di title («… | KOS»).
- **`dateModified` assente:** 19 pagine `/it/convenzioni/…` (mai modificate).
- **PDF:** 1 indicizzabile con titolo «Microsoft Word - 1 - COMITATI OSPITI_AA_31.01.24» (`/assets/doc/comunicati/2024/01-comitati-ospiti-aa.pdf`); 6 nomi di file con spazi o lettere accentate; 17 PDF con link a `http://www.kosgroup.com/` e 2 a `https://www.kosgroup.com/privacy/` (301); `istruzioni-per-il-primo-accesso-al-dossier-sanitario.pdf` punta a `/ritiro-referti` (404).
- **Immagini interne senza risposta («connessione rifiutata»), da ricontrollare:** `/assets/img/img-competenze-mb-2.jpg`, `/assets/img/news/storie-di-donne-premiati-…-concorso-letterario.jpg` (usata anche come `image` nel JSON-LD), `/assets/cache-img/img/approfondimenti/check-up-perche-e-importante-farlo-800.webp`.
- **Link esterni:** `/it/ritiro-referti` (895 link in entrata) punta a `services.kosservizi.com/referti/AlcPaz` e `/AlcCons`, che rispondono 500 al crawler (da verificare con un browser); 16 esterni 404 e 2 400, quasi tutti dentro PDF.
- **Alt vuoto su immagini di contenuto:** 34 immagini delle tecnologie nell'elenco `/it/chi-siamo/qualita-delle-cure/tecnologia-e-innovazione`.
- **`Cache-Control: no-store, no-cache` e cookie `PHPSESSID` su tutte le 914 pagine HTML:** da rivedere prima di Cloud Edge (nessuna pagina sarebbe messa in cache). Fuori dalle voci, miglioria.
- **60 URL interni bloccati da robots.txt** (`?formId=…` di `parla-con-noi`, `richiesta-informazioni`, `informazioni-per-il-ricovero`): previsto dalla regola dello sprint 1.
- **Tempo di risposta:** mediana 0,24 s, 75° percentile 1,18 s, 104 pagine oltre 1,5 s (massimo 2,04 s): andamento a due gruppi, compatibile con pagine non ancora in cache.

## Fatti già noti: esito della verifica

| Fatto | Esito |
| --- | --- |
| 1 · 308 pagine con «address obbligatorio» | Confermati i numeri (148 + 160). La correzione copre tutti i punti del codice; nessun altro nodo scoperto. Manca la prova in produzione: vedi B1 |
| 2 · Valdaso letto prima dell'aggiornamento | Confermato: letto alle 10:07; nella scansione senza email, specialità e servizi. Da rileggere nel ricontrollo |
| 3 · «Link relativi» e `<base href>` | Confermato sul campione (59 email in entità HTML, 2 template JavaScript) e sugli 822 link a `https://kosgroup.com/`. In più 6 link relativi veri: vedi C7 |
| 4 · Minori già annotati | Confermati: 7 URL interni in 301, 1 immagine 404, 2 «Lorem ipsum» (FAQ 1 e 4 di KOS Academy), 46 pagine `?page=N` fuori sitemap, 47 immagini sopra 200 KB (massimo 1,8 MB, 7 foto di medici). Nota: `/it/centri/beata-corte` è collegata da 5 pagine di trattamenti di psichiatria (riquadro «Beata Corte»), al contrario di quanto scrive il registro il 12/11 |

## Affermazioni delle note smentite o imprecise

| Nota | Citazione | Dato reale |
| --- | --- | --- |
| Sprint 3, KOS 7 | «foto negli elenchi con il nome della struttura» | Falso su 12 pagine di riabilitazione (49 foto con `alt=""`): B2 |
| Sprint 3, KOS 7 | «338 segnaposto dei medici … marcati come decorativi (alt vuoto)» | 14 segnaposto di Villa dei Pini Civitanova non hanno l'attributo `alt` |
| Sprint 3, KOS 11 | «Rich Results Test su …/stefano-albanelli: Breadcrumb, Attività locali, Società» | Alla scansione la pagina aveva l'errore `address` sul riferimento `practicesAt`; la nota non cita la correzione del 15/11 (il registro la prevede) |
| Sprint 3, KOS 11 | «medici `IndividualPhysician` `{URL}#medico` con specialità» | 97 su 99; 2 senza `medicalSpecialty` |
| Sprint 3, Villa Margherita | Nessun cenno agli avvisi | Avviso Google su `department` nelle 2 unità |
| Sprint 2, KOS 24 | «con indirizzo, telefono, email, coordinate, orari, specialità, servizi e prestazioni prenotabili» | Su 58: indirizzo, telefono, coordinate, orari 58; email 50; specialità 50 (già precisato); servizi 48; prestazioni prenotabili 16 |
| Sprint 2, KOS 24 | «Nodo struttura su 58 schede» | Vero, ma le schede pubblicate sono 63: 3 senza nodo non dichiarate (C1) |
| Sprint 2, KOS 21-23 | «Marchio « | KOS» una sola volta nel title» | Mai due volte; 3 title senza marchio e 2 con «| Kos» |
| Sprint 2, KOS 8 | «543 PDF collegati» (6/11) | 602 PDF collegati il 15/11, tutti 200: 219 indicizzabili, 15 copie con canonical, 368 noindex (entro i 245 e 433 dichiarati) |
| Precisazioni sprint 2 | «Dal 15 novembre il riferimento riporta anche indirizzo e telefono» | Non verificabile con questa scansione: B1 |
| Registro, 12/11 | Sitemap con `/villamargherita` «produzione 13/11» | Assente dalla sitemap del 15/11 |

## Risulta corretto

- **Base:** 914 pagine con un blocco JSON-LD, un H1, un title e una description; nessun microdato o RDFa; nessuna entità HTML, tag MODX o URL http nei valori del JSON-LD; `@id` nella forma canonica.
- **Canonical e direttive:** canonical uguale all'URL su 914 pagine (elenchi paginati con il solo `?page=N`); nessun noindex sulle pagine HTML.
- **Sitemap:** 866 URL, tutti 200 e indicizzabili, nessun redirect, 404 o parametro.
- **Hreflang:** 19 coppie it/en reciproche, con sé stesse e `x-default`, destinazioni tutte 200.
- **Title e description:** nessun title ripetuto; un solo title oltre 65 caratteri (afasia e disartria, 67: scostamento dichiarato); description uniche (fuori dalle pagine `?page=N`) e tutte entro 160.
- **Ente (KOS 9):** `https://kosgroup.com/#organization` su tutte le pagine, KOS S.p.A., partita IVA, codice fiscale, `subOrganization` verso KOS Care, `parentOrganization` CIR, mai `sameAs` verso anniazzurri.it; «KOS Group» sulle 19 pagine inglesi.
- **Briciole (KOS 12-13):** `BreadcrumbList` su 905 pagine, posizioni consecutive, tutti gli `item` 200; assente sulle 9 pagine senza briciole; campione di 47 sorgenti senza differenze.
- **Date:** `dateModified` tra 2017 e 2026, `datePublished` tra 2016 e 2026, mai `dateModified` anteriore a `datePublished`, nessun 1970.
- **Schede (KOS 24):** 58 nodi con `@id` uguale all'URL della scheda (`MedicalClinic` 50, `DiagnosticLab` + `MedicalBusiness` 5, `Dentist` 2, `Hospital` 1), `mainEntity` della pagina, `parentOrganization` verso l'ente; CAP, province e coordinate plausibili, orari coerenti; solo l'avviso `priceRange`. Centro Cardinal Ferrari: 106 prestazioni in 8 branche, come nella nota. Nessuno script Yext nell'HTML.
- **Riferimenti alle strutture:** tipo, nome e URL sempre uguali al nodo della scheda; nessun riferimento a strutture senza nodo.
- **Professionisti (KOS 11):** 150 pagine, 99 `IndividualPhysician` `#medico` e 51 `Person` `#person`; indirizzo e telefono del medico uguali a quelli della sede su 99 pagine; 2 `Person` legati all'ente; Villa Margherita: 8 medici e 21 altri professionisti verso `https://kosgroup.com/villamargherita#localbusiness`; `#department` solo sulle 2 unità, con la sede `Hospital` per intero.
- **Articoli e video (KOS 10):** 234 `Article`, 145 `NewsArticle`, 21 `VideoObject` con campi uniformi.
- **PDF (KOS 8):** 602 tutti 200; nessuna copia con canonical e noindex insieme; destinazioni dei canonical 200; titolo presente su tutti gli indicizzabili, 233 su 234 nella forma «documento – struttura».
- **Sprint 1:** CSS e JS (30) con `?v=`; `max-age=15552000` su 4.183 immagini e 30 CSS/JS; `http://www.kosgroup.com/` → 301 diretto a `/it`.
- **KOS 7 e KOS 2 (esempi delle note):** galleria «Centro Cardinal Ferrari – foto 1 di 9» (9 foto); `la-nostra-storia` con 21 sorgenti WebP e 32 immagini `loading="lazy"`.
- **Ricerche nel codice:** 0 occorrenze di `utm_`, `gclid=`, `gad_source=`, stage, `modx.dev`, `[[`, `pagetitle]]`, `yextapis.com`, `data-yext-field`.
