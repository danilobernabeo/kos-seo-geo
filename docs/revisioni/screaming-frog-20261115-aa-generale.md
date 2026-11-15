# Screaming Frog 15/11/2026 · anniazzurri.it · stato generale (SEO tecnico, contenuti, GEO, accessibilità)

Uso interno. Seconda analisi del revisore (Claude Fable 5.2) sulla scansione completa del 15/11/2026 (7.580 URL, 461 pagine HTML 200
senza i virtual tour), fuori dal perimetro delle note di consegna. Il confronto con le note è in `screaming-frog-20261115-aa.md`.
Tutte le migliorie di questo rapporto sono fuori perimetro (decisione di Danilo): per ognuna gravità, impatto e sforzo in giornate.

## Riepilogo

- **Fondamenta solide:** il server risponde in 0,10-0,13 s di mediana in ogni sezione, nessuna pagina HTML rotta, sitemap pulita, canonical coerenti, tutto il sito entro 3 clic, testo e contatti leggibili senza JavaScript.
- **Punto GEO più evidente:** gli orari. 55 residenze li dichiarano nel dato strutturato, nessuna delle 57 li scrive nel testo. Un assistente AI non li può confermare sulla pagina, e Google chiede che il markup descriva contenuto visibile.
- **Schede a due velocità:** le 57 residenze hanno nodo struttura e contatti in chiaro; 5 centri diurni, 2 hospice e gli appartamenti di Bergamo no, e i servizi senza sede (RSA Aperta, cure domiciliari) non hanno il nodo `Service` previsto dalla convenzione.
- **Contenuti:** 203 articoli su 272 sono firmati dall'ente, anche su temi sanitari; 234 articoli hanno al massimo 2 link in entrata e dipendono da `/news/`, una pagina unica da 467 KB.
- **Peso delle pagine:** il server è veloce, il browser meno. Gallerie senza caricamento differito su 32 schede, una libreria inutile su tutte le pagine, Flickity caricato due volte, 6 immagini su 10 senza dimensioni.
- **Accessibilità:** i report di Screaming Frog sono vuoti (scansione «Text Only»); restano i controlli statici su 111 sorgenti.

### Le 10 azioni a miglior rapporto beneficio/sforzo

| # | Azione | Beneficio | Sforzo (giornate) |
| --- | --- | --- | --- |
| 1 | Orari scritti nel riquadro contatti delle 55 residenze, dallo stesso campo che alimenta il JSON-LD | GEO alta: risposta verificabile a «quando posso andare in visita» | 0,5, più la conferma di KOS sul significato di 8-20 (incontro del 19/11) |
| 2 | Togliere dall'offuscamento le email di struttura: 18 email «RSA Aperta» nelle residenze, riquadri delle 8 altre schede | GEO: contatti leggibili dai crawler AI | 0,5 |
| 3 | Via gli script inutili: highlight.js (tutte le pagine, mai usato), secondo Flickity (57 schede), `defer` sugli altri | Utenti e SEO: meno JavaScript bloccante su 460 pagine | 0,5 |
| 4 | `loading="lazy"` sulle foto delle gallerie (32 schede) e sui video YouTube | Utenti: fino a 30 foto in meno al primo caricamento | 0,5 |
| 5 | Nodo struttura e riquadro contatti dal server per centri diurni, hospice e appartamenti (stessa libreria delle residenze) | GEO: 8 strutture diventano entità con indirizzo e telefono | 1, più i dati |
| 6 | `Service` per RSA Aperta, cure e assistenza domiciliare, con `provider` e `areaServed` (convenzione già scritta) | GEO: i servizi senza sede diventano entità collegate alle residenze | 1 |
| 7 | Link rotti nelle schede: 22 podcast Spreaker dei direttori, 2 app di Radio Anni Azzurri | Utenti, qualità percepita delle schede | 0,5 |
| 8 | Firma e revisione sugli articoli di salute a firma «Anni Azzurri»; togliere `utm_source=chatgpt.com` dalle fonti | E-E-A-T su contenuti sanitari | 0,5, più i nomi da KOS |
| 9 | `width` e `height` sulle immagini che non li hanno (61% nei sorgenti) | Utenti e SEO: stabilità visiva | 1 |
| 10 | News: paginazione o archivio per tema e blocco «Articoli correlati» | SEO: 234 articoli escono dalla periferia; `/news/` scende da 467 KB | 2 |

## Metodo e limiti

- **Dati:** 66 CSV della scansione e 111 sorgenti HTML (le 57 residenze, le 8 altre schede, almeno una pagina per tipo).
- **Non disponibili:** report di accessibilità e PageSpeed (467 righe senza valori), quasi duplicati e ortografia (non calcolati), peso di CSS e JavaScript (non registrato), `lastmod` della sitemap, `testo_pagina/` (non copiato).
- **Nessuna richiesta al sito:** robots.txt e llms.txt letti dalle copie del repository (`anniazzurri/files/`).
- **Voci d'audit:** i file dell'audit non sono leggibili da qui. Conosco dal repository le voci AA 1-11, 14, 17-21; il «fuori perimetro» va riletto contro le voci 12, 13, 15 e 16.
- **Sforzo:** stima di massima del solo sviluppo, senza collaudo e rilascio, da confermare.

## Architettura e link interni

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo (giornate) |
| --- | --- | --- | --- | --- |
| Articoli e news raggiungibili quasi solo da `/news/` | 234 su 272 con al massimo 2 link unici in entrata; solo 65 ricevono un link dal testo di un altro articolo. `/news/` elenca 254 articoli in una pagina (467 KB di HTML, 9.280 parole, 273 immagini), senza paginazione né temi | Media | SEO: peso interno basso sui contenuti informativi; pagina elenco pesante | 2 (già proposta a consuntivo nel piano interno) |
| Pagine in sitemap senza link interni | 28: 18 articoli usciti dall'elenco di `/news/` e 10 pagine, es. `/le-vostre-storie`, `/servizi-personalizzati`, `/riabilitazione-specializzata-post-ictus`, `/convegno-sigot-2023` | Media | SEO: pagine che i motori trovano solo dalla sitemap | 0,5 (collegarle o toglierle dalla sitemap) |
| Articoli poco collegati alle residenze | 83 articoli su 272 hanno un link a una scheda nel testo | Media | SEO e GEO: articolo e struttura non formano un percorso | 1, più redazione |
| Altre schede poco collegate | Centri diurni e hospice con 1-2 link in entrata (es. `/centro-diurno-alzheimer-san-giuseppe`: 1); le residenze ne hanno 9 di mediana | Media | SEO | 0,5 (link dalla scheda della residenza che li ospita e dalle pagine provincia) |
| Residenze senza pagina provincia | 12 su 57 (es. Parco di Veio, Il Poggione, Valdaso, Villa Clarice); le province con una sola struttura rimandano alla regione | Bassa | SEO locale | Nessuna azione senza una decisione sui contenuti |
| `/home-en`, unica pagina in inglese | Collegata da tutte le pagine, 613 parole, fuori sitemap, senza hreflang | Bassa | SEO: pagina debole con molto peso interno | 0,5 (hreflang e sitemap, oppure `noindex`) |
| Ancore generiche | «Leggi di più» 258, «Scopri» 93, «Scopri di più» 19, «clicca qui» 3, su 49 pagine (soprattutto `/news/` e home) | Bassa | Accessibilità e SEO | 0,5 (titolo dell'articolo nell'`aria-label`) |
| Link esterni rotti | 28 con 404: 22 episodi Spreaker dei direttori su circa 20 schede (es. Conero, Dorica, Palladio), 2 app di `/radio-anni-azzurri`, `kosgroup.com/il-gruppo-kos-pubblica-il-primo-bilancio-di-sostenibilita/`. I 14 «403» di salute.gov.it sono il loro filtro anti-bot | Media | Utenti: un riquadro vuoto in una scheda su tre | 0,5 |
| Link esterni in http | 5 pagine, 10 indirizzi (es. `sstefano.it/corso-oss`, `fondazionemantovani.it`, 4 nella pagina `/privacy`) | Bassa | Utenti | 0,25 |
| `target="_blank"` senza `rel="noopener"` | 1.079 link su 460 pagine | Bassa | Sicurezza, nessun effetto SEO | 0,25 |
| Indirizzi | 4 con punteggiatura (`…stiamo-cercando-te!`, `pet-therapy-nelle-rsa,-quando-…`, `professione-oss.-passione-…`), 18 oltre 115 caratteri, 1 con suffisso `-2` | Bassa | Nessuna azione: cambiarli costa più di quanto rende | — |
| Profondità | 64 pagine a 1 clic, 361 a 2, 7 a 3 | — | Va bene | — |

Link del piè di pagina e briciole verso indirizzi con redirect: nel rapporto sugli sprint (C4, C5).

## Contenuti e E-E-A-T

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo (giornate) |
| --- | --- | --- | --- | --- |
| Articoli sanitari firmati dall'ente | 203 articoli con `author` = «Anni Azzurri», tra cui temi clinici (`/vaccino-pneumococco-per-anziani-a-chi-serve-e-quando`, `/creatinina-alta-negli-anziani-cause-rischi-e-rimedi`); 69 firmati da una persona con qualifiche e pagina autore. Nessun `reviewedBy` | Alta | E-E-A-T: motori e assistenti AI pesano la firma su contenuti di salute | 0,5 (la firma autore esiste già), più i revisori da KOS |
| Fonti con `utm_source=chatgpt.com` | 2 link nell'articolo sul vaccino pneumococco (ISS, Ministero della Salute) | Media | Reputazione: chi legge il sorgente vede da dove vengono le fonti | 0,1, più un controllo redazionale del testo |
| `dateModified` aggiornata senza modifiche al testo | 89 articoli con data di novembre 2026 per il solo aggiornamento di title e description | Bassa | GEO: segnale di freschezza non reale | 0,5 (campo «ultima revisione del testo» separato) |
| Contenuti datati | 83 articoli pubblicati nel 2022-2023; pagine come `/prevenzione-covid` | Media | SEO e GEO | Redazione di KOS; 0,5 per l'elenco ordinato per traffico |
| Pagine con poco testo | `/galleria-fotografica-convegno-anni-azzurri-2025` 51 parole, `/le-vostre-storie` 236, `/cure-e-assistenza-domiciliare` 267, `/residenze` 275; pagine provincia 330-347 | Bassa | SEO | Redazione |
| Title molto corti | 7 sotto 30 caratteri: «Oh Anni Azzurri», «Anni Azzurri per te», «Radio Anni Azzurri», «Perché Anni Azzurri» | Bassa | SEO | 0,25 (script dei title già esistente) |
| Dati della scheda non uniformi | Posti letto citati nel testo di 15 residenze su 57; retta indicata su 24 | Media | GEO: domande frequenti senza risposta su due schede su tre | Dati di KOS; 0,5 per il campo |
| Refuso in una mappa | `/appartamenti-anziani-bergamo`: la mappa cerca «Via Colognola Aai Colli» | Bassa | Utenti | 0,1 |

## GEO: leggibilità per i sistemi AI

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo (giornate) |
| --- | --- | --- | --- | --- |
| Orari solo nel dato strutturato | 55 residenze con `openingHoursSpecification` (34 con 8:00-20:00 tutti i giorni); nel testo delle 57 schede nessun orario. Solo i 2 hospice hanno la sezione «Orari di visita» | Alta | GEO: dato non verificabile sulla pagina; markup che non descrive contenuto visibile | 0,5 (riga nel riquadro contatti); gli orari di visita da RetailTune sono già decisi (10/11) |
| Email offuscate | Direttore su 57 residenze (deciso); in più 18 email «RSA Aperta» nel contenuto e tutte le email delle 8 altre schede. Senza JavaScript si legge «Turn on Javascript!» | Media | GEO: contatto non leggibile | 0,5 |
| Altre schede senza nodo | 5 centri diurni, 2 hospice, appartamenti di Bergamo: solo `WebPage`, riquadro contatti vecchio | Media | GEO: strutture con indirizzo proprio non descritte | 1, più i dati |
| Servizi senza sede senza `Service` | `/rsa-aperta`, `/rsa-aperta-lombardia`, `/cure-e-assistenza-domiciliare`, le due pagine di assistenza domiciliare: solo `WebPage` | Media | GEO: la convenzione li prevede, oggi non esistono come entità | 1 |
| Servizi della residenza solo nel testo | Le schede descrivono servizi sanitari, nuclei e comfort; il nodo non ha `availableService` né catalogo | Media | GEO: «quale RSA ha un nucleo Alzheimer a…» non ha un dato da leggere | 2 (campi strutturati per servizio, come le prestazioni di kosgroup.com) |
| Elenchi senza `ItemList` | 8 pagine regione e `/residenze`: `CollectionPage` senza elenco; `/residenze` senza `BreadcrumbList` | Bassa | GEO | 0,5 (stessa funzione delle province) |
| Video senza `VideoObject` | Video YouTube della residenza su 15 schede su 57; 47 pagine con YouTube nel sito | Bassa | GEO e risultati video | 1 (tipo nuovo nella libreria comune) |
| Convegni senza `Event` | `/convegno-parkinson-realta-prospettive`, `/il-mantello-del-custode` | Bassa | Risultati avanzati | 0,5 |
| Fax nel dato ma non nella pagina | 48 residenze | Bassa | Coerenza | 0,1 (toglierlo dal nodo o mostrarlo) |
| Telefono dell'ente | «800 131 851» senza `+39`; nel sito è scritto in due modi (110 «800 131 851», 1 «800.131.851») | Bassa | Coerenza | 0,1 |
| Testo senza JavaScript | Schede: contatti, servizi e tariffe nell'HTML (823-1.250 parole). Home: contatori già nel sorgente (8, +50, +6500, +4000). Regioni: residenze elencate nell'HTML, la mappa è in più | — | Va bene | — |
| Crawler AI e llms.txt | robots.txt con il solo gruppo `*`; nessun `noindex` né `X-Robots-Tag` sulle pagine. llms.txt (131 righe): 57 residenze, centri diurni, hospice, servizi, guide | — | Va bene. Da rigenerare quando cambiano le schede | — |

## Tecnico

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo (giornate) |
| --- | --- | --- | --- | --- |
| Tempo di risposta | Mediana 0,10-0,13 s in ogni sezione, 90° percentile sotto 0,2 s; 4 news vecchie sopra 1,1 s (prima richiesta non in cache) | — | Va bene | — |
| Gallerie senza caricamento differito | 32 schede su 57 con più di 3 foto di galleria senza `loading="lazy"` (Idice: 35 immagini, 3 differite), con varianti fino a 1600 px | Alta | Utenti e SEO: peso al primo caricamento | 0,5 |
| Script inutili o doppi | highlight.js su 107 sorgenti su 110, mai usato; Flickity due volte (unpkg e cdnjs) su 57 schede e 41 altre pagine; quasi nessuno script con `defer`; widget Spreaker su 321 pagine | Media | Utenti: JavaScript bloccante, 6 domini di terze parti su una scheda | 0,5 |
| Immagini senza dimensioni | 3.371 immagini segnalate; nei sorgenti 2.199 `<img>` su 3.602 senza `width` e `height` | Media | Stabilità visiva (collegata alla voce 4) | 1 |
| Immagini pesanti | 118 sopra 200 KB; PNG ridimensionati più pesanti dell'originale | Media | Vedi rapporto sugli sprint, B1 | — |
| Video YouTube senza caricamento differito | 15 schede su 57 e 5 altre pagine del campione | Bassa | Utenti | 0,25 (`loading="lazy"` o anteprima cliccabile) |
| Intestazioni delle pagine HTML | `Cache-Control: no-store, no-cache`, `Pragma: no-cache`, cookie `PHPSESSID` di 7 giorni a ogni visitatore anonimo, nessun `ETag` | Media | Impedisce la cache del browser per avanti/indietro e quella di Cloud Edge | 0,5 di analisi (come su kosgroup.com) |
| Cache delle risorse | Immagini, CSS e JS 180 giorni; PDF 1 giorno | — | Va bene | — |
| Sicurezza | HSTS, `X-Frame-Options`, `nosniff`, `Referrer-Policy` presenti; manca `Content-Security-Policy`; 63 pagine con script `//js-eu1.hsforms.net` senza protocollo | Bassa | Sicurezza | 1-2 per una CSP in sola segnalazione |
| PDF | 409 file, 568 MB; il più pesante 6,5 MB (`Kos-Care_Bilancio_2024.pdf`) | Bassa | Banda | Nessuna azione |
| Redirect interni | 15, una catena (`/notizie-ed-eventi` → `/news` → `/news/`) | Bassa | Vedi rapporto sugli sprint, C5 | — |
| Soft 404 e sitemap | Nessuna pagina 200 senza contenuto; 457 pagine in sitemap, tutte 200 | — | Va bene | — |

## Accessibilità

I report di Screaming Frog sono vuoti: servono il rendering JavaScript e axe (scansione «JavaScript» sul campione, 0,5 giornate). Controlli statici su 111 sorgenti:

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo (giornate) |
| --- | --- | --- | --- | --- |
| Testi alternativi delle icone | 1.391 `alt="icona …"` («icona busta», «icona facebook»): rumore per chi usa uno screen reader; nei link fatti di sola icona serve la destinazione («Facebook») | Media | Accessibilità | 0,25 (chunk di testata e piè di pagina) |
| Mappe senza titolo | `<iframe>` di Google Maps senza `title` su 23 schede su 57 e 3 altre pagine del campione | Media | Accessibilità | 0,1 |
| Salto di livello nei titoli | `<h5>Contatti</h5>` subito dopo l'H1 su 57 residenze e sulle altre schede | Bassa | Accessibilità | 0,1 (vedi rapporto sugli sprint) |
| Testo di riserva in inglese | «Turn on Javascript!» al posto delle email offuscate | Bassa | Utenti senza JavaScript | 0,1 |
| Foto dell'autrice | `alt=""` su 69 articoli; il link è nascosto agli screen reader e il nome è accanto | — | Accettabile | — |
| Basi | `lang` corretto (`it`, `en` su `/home-en`), link «Vai al contenuto principale», viewport senza blocco dello zoom, nessuna `<img>` senza `alt`, video con `title` | — | Va bene | — |
| Non verificabili da qui | Contrasto, ordine del focus, etichette del modulo HubSpot (scritto da JavaScript), widget di terze parti su `/azzurramente` | — | Da coprire con la scansione JavaScript | 0,5 |

## Cosa va bene

- Server veloce e stabile in tutte le sezioni; compressione gzip ovunque.
- Nessun errore 4xx o 5xx tra le pagine HTML collegate, tranne un refuso (`telto:`).
- Sitemap coerente con le pagine indicizzabili; canonical su tutte le pagine; nessun contenuto misto.
- Dato strutturato valido su 461 pagine; residenze, province, ente e autrice collegati per `@id`.
- Contatti delle 57 residenze nell'HTML del server, uguali al dato strutturato.
- Cache lunga con versione automatica su immagini, CSS e JavaScript.
- 192 carte dei servizi e pieghevoli indicizzabili con titolo: la fonte più completa su servizi e tariffe.
