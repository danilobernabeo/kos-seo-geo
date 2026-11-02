# Nota di consegna: gestione dei PDF (KOS-8 / AA-3)

Rilascio del 1/11/2026, prima sugli stage e poi in produzione. Brief: `docs/pdf/brief-pdf.md`. Classificazione: `kosgroup/dati/voce-24/classificazione-pdf-20261101-v2.xlsx` (il testo dei PDF e i CSV restano fuori da git).

## Cosa è stato fatto

| | anniazzurri.it | kosgroup.com |
|---|---|---|
| PDF classificati | 408 | 678 |
| Da tenere (indicizzabili) | 192 | 245 |
| Esclusi (`X-Robots-Tag: noindex`) | 216 | 433 |
| Copie identiche con `Link rel="canonical"` | 25 | 15 |
| Titoli riscritti nei metadati (Info `/Title` + XMP `dc:title`) | 186 | 242 (di cui 49 comunicati stampa) |
| Saltati (tabella interna del PDF rotta) | 5 | 2 |
| Redirect 301 aggiunti | 2 (+1 in attesa) | 6 |

- **Titoli:** «documento – nome ufficiale della struttura» (H1 della scheda), massimo 65 caratteri, nessun duplicato tra PDF diversi. Comunicati: «Comunicato stampa – argomento – mese anno», con la data dalla TV `comunicatoData` e l'argomento troncato a parola intera.
- **Metadati:** aggiornamento incrementale (i byte originali restano un prefisso identico del file), con backup e manifest in `~/backup-pdf-metadati-20261101` di ciascun cloud (`/paas/c0095`, `/paas/c0072`) e ripristino con `--ripristina`. Script: `scripts/pdf/pdf-metadati.php`.
- **Regole web:** una sezione marcata INIZIO/FINE (`<sito>/files/web-rules/pdf-noindex.conf`). Default `noindex`; eccezioni per i PDF da tenere e per nome file (carta dei servizi/alloggi, pieghevole/leaflet, guida utente/user manual/guida ai servizi, preparazioni, comunicati). Nessun PDF classificato ESCLUDI corrisponde ai modelli, a parte due di Beata Corte (già in 301), con eccezione `noindex` esplicita. Su AA la sezione sostituisce il vecchio blocco `location ~* \.pdf$` e mantiene `expires 1d`. Ripristino: togliere tutto da INIZIO a FINE.
- **Generazione:** `scripts/pdf/titoli-pdf.py` (titoli e foglio), `genera-piano-pdf.py` (piano e regole), `scripts/web-rules/stage-da-produzione.py` (regole complete di stage e produzione).

## Evidenza (1/11/2026)

- Stage KOS e AA: sei `curl -I`, uno per caso, con l'esito atteso (nessun `X-Robots-Tag` sul PDF da tenere, `noindex` sull'escluso, `Link` canonical sulla copia) e gli header di sicurezza presenti.
- Produzione: gli stessi controlli su entrambi i siti, `200` sulle home e redirect esistenti invariati (Villa Olga → Idice). I PDF riscritti hanno `Last-Modified` del 1/11.

## Evidenza (2/11/2026)

Collaudi in produzione, `curl -I` dal PC di Danilo, dopo l'aggiornamento della sezione PDF di anniazzurri.it (regole di produzione ricontrollate riga per riga con il repository: unica differenza la riga del canonical di San Sisto 2, poi aggiunta; nessun blocco `.pdf` regex doppio) e di kosgroup.com (sezione identica al repository).

- AA, carta degli alloggi nel nuovo percorso (`/assets/doc/strutture/appartamenti-anziani-bergamo/`): `200`, senza `X-Robots-Tag` e senza `Link`; `Last-Modified` del 1/11 (metadati riscritti).
- AA, carta degli alloggi di San Sisto 2: `200` con `Link: <https://anniazzurri.it/assets/doc/strutture/appartamenti-anziani-bergamo/Carta%20degli%20alloggi.pdf>; rel="canonical"`, senza `X-Robots-Tag`.
- AA, pieghevole `Anni Azzurri Senior Living.pdf` (stessa cartella): `200` senza `X-Robots-Tag` e senza `Link`.
- KOS, carta dei servizi (casa-villa-maura): `200` senza `X-Robots-Tag`, `Last-Modified` del 1/11. PDF escluso (casa-villa-maura/contratto.pdf): `200` con `X-Robots-Tag: noindex`, file non modificato. Comunicato stampa 2026: `200` senza `X-Robots-Tag`.
- KOS, cartella `/assets/doc/cv/`: elenco `403` con `X-Robots-Tag: noindex, nofollow`; file della cartella `200` con `noindex, nofollow` e header di sicurezza.
- Il blocco anti-bot della piattaforma («Security Check», `403`) che la sera del 1/11 fermava `curl.exe` su entrambi i siti non si è ripresentato: nessuna ripercussione sui test.

## Decisioni e note

- Carta degli alloggi di San Sisto 2: stessa carta di quella di Senior Living (36 pagine, testo uguale salvo «Revisione di giugno 2025» contro «gennaio 2026»). La copia di San Sisto 2 ha `Link rel="canonical"` verso `/assets/doc/strutture/appartamenti-anziani-bergamo/Carta degli alloggi.pdf` (decisione del 1/11, applicata e collaudata il 2/11). Valutare di sostituire il file di San Sisto 2 con la revisione di gennaio 2026.
- Cartella dei curriculum `/assets/doc/cv/` (dati personali), solo KOS: blocco `location ^~` con `X-Robots-Tag: noindex, nofollow` e header di sicurezza. I file restano raggiungibili con il link diretto (la pagina riservata li apre), l'elenco della cartella resta 403. Collaudato il 1/11 su stage e produzione: file 200 con `noindex, nofollow` e header di sicurezza, cartella 403.
- Pieghevole «Anni Azzurri Senior Living» (AA), caricato direttamente in produzione il 2/11 nella cartella `appartamenti-anziani-bergamo`, dopo l'inventario del 1/11: non è nel piano dei metadati. Titolo e descrizione impostati da Danilo prima del caricamento («Pieghevole informativo - Anni Azzurri Senior Living»; con il trattino corto, a differenza del trattino lungo usato negli altri PDF). Indicizzabile per decisione del 2/11 con un modello per nome file nel generatore (`/anni azzurri senior living*.pdf`), valido per qualsiasi cartella; il modello compare anche nella sezione KOS ma non ha effetto. I `dc:title` delle immagini incorporate («Maps_SanSisto-SENIOR», «Senior Living logo») sono metadati delle immagini di InDesign, non del documento: lasciati invariati.
- Strutture uscite dal gruppo: Beata Corte è già in 301 verso Corte Orero (regole di produzione), quindi i suoi PDF non sono serviti e figurano ESCLUDI.
- «Il team», «La nostra équipe», «Organizzazione» e tirocini: invariati, in attesa della conferma di KOS (call del 3/11).
- Open Day odontoiatrico del 16/11/2026: da tenere fino all'evento, poi ESCLUDI.
- Redirect della carta dei servizi di Biarella: commentato, da attivare solo se la pagina della residenza risponde 200.

## Da fare

- Pieghevole Senior Living e `residenza-anni-azzurri-san-sisto-2/Pieghevole informativo alloggi protetti.pdf`: se il nuovo sostituisce il vecchio, confrontare i testi e decidere canonical o `noindex` sul vecchio (come per la carta degli alloggi).
- Header `Permissions-Policy` di kosgroup.com (preesistente): contiene `geolocation=(self "https://cdn.yextapis.com")`; da togliere o sostituire a fine migrazione da Yext (KOS-6).

- Ricostruire i 7 PDF con la tabella interna rotta (fuori voce, a consuntivo).
- Controllo ripetibile per i PDF nuovi (passi 5-6 del brief).
- Pulizia dei file di lavoro sui cloud: `~/pdfmeta*`, `~/pdf-h1*`, `~/meta-stato-*`, `~/prova-pdf-*`.
