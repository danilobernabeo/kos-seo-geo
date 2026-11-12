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

## Controllo ripetibile per i PDF nuovi (passi 5-6, 3/11/2026)

- **`scripts/pdf/pdf-metadati.php`** scrive ora anche oggetto (`/Subject` + `dc:description`), autore (`/Author` + `dc:creator`) e lingua (`/Lang` del catalogo + `dc:language`), con lo stesso aggiornamento incrementale, verifica del file riletto, backup e ripristino. Nel piano le voci hanno `t`, `d`, `a`, `l` (tutti facoltativi, oltre a `default`); un piano con il solo titolo produce byte identici alla versione precedente (provato su 8 PDF, 3 dei quali con struttura diversa). Il backup tiene sempre il primo originale di un percorso: una riscrittura dello stesso file non lo sovrascrive, e un file sostituito con un nuovo caricamento ha una sua copia `<nome>.<md5>.orig`; il ripristino riporta indietro la voce più recente.
- **`scripts/pdf/controllo-pdf-nuovi.php`** (nuovo, sola lettura sui PDF e sul database) percorre `assets/` e la radice e segnala: file **nuovi** (non nei manifest e più recenti dell'ultimo), **modificati** (md5 diverso da quello scritto da noi, cioè sostituiti) e file indicizzabili con **titolo** mancante, numerico, uguale al nome del file, generico, senza la forma «documento – struttura» (anche col trattino corto), oltre 65 caratteri o diverso tra Info e XMP. Per ognuno propone titolo (nome ufficiale = H1 della pagina che richiama il PDF, letta dal sito) e oggetto (meta description della pagina, massimo 150 caratteri, da rivedere); segnala i PDF nuovi in `noindex` per la regola di default che sembrano da tenere e le copie identiche con il canonical proposto. Usa gli stessi criteri di indicizzazione delle regole web (elenco e modelli per nome file, esportati da `genera-piano-pdf.py` in `piano-pdf.json`) e gli stessi criteri di collegamento dell'inventario. Prova senza opzioni; con `--scrivi` scrive solo il piano JSON per `pdf-metadati.php`, mai i PDF. I valori decisi a mano stanno in `<sito>/dati/pdf/imposta-pdf.json` (prevalgono sul foglio e sopravvivono alle rigenerazioni).
- **Primo caso, `/assets/doc/pieghevole-aurora.pdf` (KOS), in sostituzione:** il file nuovo (pieghevole del servizio Aurora, 2 pagine) non aveva titolo; nel piano del 1/11 il vecchio aveva «Pieghevole aurora – KOS». Valori decisi: titolo «Pieghevole Aurora, Case Management – Centro Medico Riabilitativo» (64 caratteri), oggetto (145 caratteri), autore «Gruppo KOS», lingua `it-IT` (il file la ha già nel catalogo, che quindi non viene riscritto). Collaudo locale su una copia: il controllo lo segnala come MODIFICATO, `pdf-metadati.php` scrive i quattro campi (Info, XMP e catalogo riletti, `qpdf --check` senza errori, `pdftotext` regolare) e un secondo controllo non lo segnala più. Rilasciato il 3/11 su stage (backup in `/paas/c0044/backup-pdf-metadati-20261103`) e produzione (`/paas/c0095/backup-pdf-metadati-20261103`): la prova ha mostrato i quattro campi, la scrittura ha riletto il file, la prova successiva dice «GIÀ A POSTO». In produzione `curl -I` risponde `200` senza `X-Robots-Tag` (resta indicizzabile), `Content-Length` 823.728 e `Last-Modified` del 3/11 alle 15:28 (UTC).
- Effetto noto dell'aggiornamento incrementale: un file ottimizzato per il web (linearizzato, come questo) perde la visualizzazione rapida; per un pieghevole di 800 KB è irrilevante.
- Primo giro del controllo (3/11): su stage (690 PDF, in sola lettura) segnala solo l'Aurora; in produzione (691 PDF) l'Aurora e un curriculum caricato il 2/11 dal modulo di candidatura nella cartella `/assets/doc/cv/`. Quest'ultimo è già `noindex, nofollow` dal blocco della cartella; la cartella però contiene dati personali, quindi il controllo non la esamina più e non la elenca (compare solo il numero dei file). Il resto è invariato dal 1/11: 2 PDF non leggibili (già noti) e 3 da tenere in attesa della conferma di KOS.
- Il controllo non ricostruisce i PDF con la tabella interna rotta (sono elencati a parte) e non sostituisce la decisione su un PDF nuovo in `noindex`: la segnala.

## Carte dei servizi, edizione novembre 2026 (AA, 4/11/2026)

- **Consegna:** 22 PDF forniti da KOS (Emilia-Romagna, Lombardia, Piemonte), tutti con testo estraibile. 20 carte sostituiscono allo stesso percorso quelle già pubblicate (i link delle pagine non cambiano): CDI Biancospino, CSRR Orchidea, Ducale 1, 2 e 3, Idice, Il Giardino, Villa dei Ciliegi, Villa Pineta e le 11 RSA del Piemonte. Il file dell'Hospice Beati Coniugi Martin è un pieghevole di 4 pagine, non una carta: aggiunto come `hospice-beati-coniugi-martin/Pieghevole informativo.pdf` (la carta dei servizi resta), elencato da sé fra i documenti della pagina (snippet `docList`) e indicizzabile per nome file. La carta di Villa Olga non si pubblica: la pagina della residenza è in 301 verso Idice (decisione del 4/11).
- **Strumenti:** `scripts/pdf/sostituisci-pdf.php` (prova, `--scrivi` con backup e `ripristino.json`, `--ripristina`) con l'abbinamento `anniazzurri/dati/pdf/carte-servizi-2026-11.json` (file consegnato → percorso, titolo, oggetto, autore, lingua), poi `pdf-metadati.php` con lo stesso file come piano. Titolo «Carta dei servizi – <struttura>», oggetto dalla copertina (es. «Carta dei servizi e regolamento interno della Casa Residenza per Anziani Anni Azzurri Ducale 1 di Modena, edizione novembre 2026.»), autore «Anni Azzurri», lingua `it-IT`. Corretti anche nel piano generale quattro titoli: Idice (senza «(Idice)»), Ducale 2 e 3 (senza «Ducale 2-3»), CSRR Orchidea.
- **Collaudo locale:** sostituzione, metadati in Info e XMP, `qpdf --check` senza errori, identificazione PDF/A-1b (`pdfaid`) mantenuta, flusso XMP non compresso; ripristino verificato (il pieghevole nuovo viene rimosso).
- **Stage (4/11):** 21 file sostituiti, metadati 21 scritti e poi «già a posto». `curl -I`: `200` senza `X-Robots-Tag`, Volpiano 3.257.104 byte, pieghevole 1.043.080. Pieghevole visibile nei documenti della pagina dell'hospice.
- **Produzione (4/11):** 21 file sostituiti, metadati 21 scritti e poi «già a posto». `curl -I` con gli stessi valori dello stage, `Last-Modified` del 4/11 alle 15:11 (UTC). Backup della sostituzione `/paas/c0072/backup-kos-seo-geo/20261104-091101-pdf-sostituiti`, dei metadati `/paas/c0072/backup-pdf-metadati-20261104`.
- **Ripristino:** `php scripts/pdf/sostituisci-pdf.php /www --ripristina=/paas/c0072/backup-kos-seo-geo/20261104-091101-pdf-sostituiti --scrivi --forza` (rimette le carte precedenti, già con i loro metadati, e toglie il pieghevole; `--forza` perché i metadati hanno modificato i file dopo la sostituzione). Stage: stesso comando con `/paas/c0025/backup-kos-seo-geo/20261104-090602-pdf-sostituiti`.

## Carte dei servizi e documenti dell'8/11/2026 (AA e KOS)

- **Consegna:** 12 PDF forniti da KOS. AA: carte dei servizi edizione novembre 2026 delle 7 RSA delle Marche (Abitare il Tempo, Casa Argento, Conero, Dorica, San Giuseppe, Santa Maria in Chienti, Valdaso) e di Idice (già sostituita il 4/11; riproposta). KOS: carta dei servizi del Poliambulatorio Parco di Veio (versione di giugno 2026) e pieghevole A5, stesso percorso; locandina dell'Open Day di neurochirurgia del 20/11 al Poliambulatorio Valdaso, nuovo file `poliambulatorio-valdaso/open-day-neurochirurgia-20-novembre-2026.pdf` (elencato da `docList` come «Open day neurochirurgia 20 novembre 2026», `noindex` per regola predefinita).
- **Sospeso:** «Customer Satisfaction 2025» del Centro Ambulatoriale di Macerata: in copertina lo stesso anno del file già pubblicato (`customer-satisfaction-2025.pdf`); nome del file da decidere quando KOS conferma l'anno (Danilo lo segnala).
- **Strumenti:** `sostituisci-pdf.php` e `pdf-metadati.php` con `anniazzurri/dati/pdf/carte-servizi-2026-11-08.json` e `kosgroup/dati/pdf/carte-servizi-2026-11-08.json` (titoli «Carta dei servizi – Residenza Anni Azzurri <nome>», oggetto con città ed edizione; KOS autore «Gruppo KOS»). Corretto in `sostituisci-pdf.php` il motivo dei sospesi (chiave `motivo`).
- **Stage e produzione (8/11):** AA e KOS sostituiti e con metadati. Produzione KOS: 3 sostituiti, 3 metadati scritti, backup metadati `/paas/c0095/backup-pdf-metadati-20261108`, backup della sostituzione in `~/backup-kos-seo-geo/20261108-*-pdf-sostituiti`. `curl -I`: carta Parco di Veio 200, 664.556 byte; pieghevole 200, 1.295.779 byte; locandina 200 con `X-Robots-Tag: noindex`, 297.195 byte; carta AA Conero 200, 3.289.399 byte, senza `X-Robots-Tag`.
- **Ripristino:** `php scripts/pdf/sostituisci-pdf.php /www --ripristina=<cartella del backup della sostituzione> --scrivi --forza` sul cloud interessato.

## PPSC 2026 di Villa Jolanda (KOS, 12/11/2026)

- **Consegna:** «Piano programma per la sicurezza delle cure 2026 – Rendicontazione annuale anno 2025» della Clinica Villa
  Jolanda, scansione di 12 pagine senza testo. Sostituisce `villa-jolanda/ppsc.pdf` allo stesso percorso (link della
  pagina invariato; il PDF resta escluso dall'indice come prima, regola predefinita).
- **Lavorazione:** testo riconosciuto con OCR (italiano) sotto l'immagine della scansione, che resta quella originale;
  tolta la 12ª pagina, bianca (il documento dichiara 11 pagine). 647.624 byte, `qpdf --check` senza errori. Abbinamento
  `kosgroup/dati/pdf/ppsc-villa-jolanda-2026-11-12.json` (titolo «Piano programma per la sicurezza delle cure 2026 –
  Villa Jolanda», autore «Gruppo KOS», `it-IT`).
- **Da segnalare a KOS (contenuto, il file si pubblica com'è):** il sommario elenca ancora «Rendicontazione annuale anno
  2023» e «PPSC anno 2024», la data di redazione in prima pagina è 31/03/2025, il titolo della sezione 5 dice «anno 2025»
  mentre il testo parla del 2026.
- **Stage e produzione (12/11):** sostituito e con metadati; `curl -I` su entrambi: `200`, 649.951 byte, `X-Robots-Tag:
  noindex`, `Last-Modified` del 12/11 (produzione 11:04 UTC). Backup dei metadati `/paas/c0095/backup-pdf-metadati-20261112`
  (stage `/paas/c0044/...`), della sostituzione in `~/backup-kos-seo-geo/20261112-*-pdf-sostituiti`.
- **Ripristino:** `php scripts/pdf/sostituisci-pdf.php /www --ripristina=<cartella del backup della sostituzione> --scrivi --forza`.
- **CAR Macerata (risposta di KOS del 12/11):** funzionigramma invariato, `user-manual` da tenere: nessun intervento.

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
- Biarella, uscita dal gruppo: 410 sulla carta dei servizi, sul pieghevole, sulla pagina della residenza e sul vecchio indirizzo `/rsa-biarella` (stage e produzione il 5/11).

## Da fare

- Pieghevole Senior Living e `residenza-anni-azzurri-san-sisto-2/Pieghevole informativo alloggi protetti.pdf`: se il nuovo sostituisce il vecchio, confrontare i testi e decidere canonical o `noindex` sul vecchio (come per la carta degli alloggi).
- Header `Permissions-Policy` di kosgroup.com (preesistente): contiene `geolocation=(self "https://cdn.yextapis.com")`; da togliere o sostituire a fine migrazione da Yext (KOS-6).

- Ricostruire i 7 PDF con la tabella interna rotta (fuori voce, a consuntivo).
- Rilasciare il piano dell'Aurora (stage, poi produzione) e lanciare `controllo-pdf-nuovi.php` in prova su entrambi i siti di produzione per vedere cosa è cambiato dal 1/11; da rilanciare dopo ogni caricamento di PDF (o a cadenza settimanale).
- Pulizia dei file di lavoro sui cloud: `~/pdfmeta*`, `~/pdf-h1*`, `~/meta-stato-*`, `~/prova-pdf-*`.

## Locandina «Il mantello del custode» (AA, 6/11/2026)

- File sostituito da Danilo allo stesso percorso (`/assets/doc/il-mantello-del-custode-locandina.pdf`, evento dell'11/11 all'Hospice Beati Coniugi Martin). Caricato dopo il piano del 1/11, quindi senza metadati nostri.
- Metadati con `pdf-metadati.php` e `anniazzurri/dati/pdf/locandina-mantello-2026-11.json`: titolo «Locandina Il mantello del custode – Hospice Beati Coniugi Martin», oggetto (147 caratteri), autore «Anni Azzurri», `it-IT`; stessi valori in `anniazzurri/dati/pdf/imposta-pdf.json` per i controlli successivi. Produzione 6/11 alle 14:26: 1 scritto; backup `/paas/c0072/backup-pdf-metadati-20261106`.
- Resta `noindex` (regola predefinita: le locandine non sono tra i PDF da tenere; la pagina dell'evento è indicizzata). `curl -I`: 200, `X-Robots-Tag: noindex`, 2.492.618 byte.

## Aggiornamento dell'11/11: pieghevole del Poliambulatorio Parco di Veio

Versione definitiva ESE_3 di KOS al posto di quella dell'8/11, stesso percorso (`/assets/doc/strutture/poliambulatorio-parco-di-veio/pieghevole-informativo.pdf`), con titolo, oggetto, autore e lingua. Stage e produzione l'11/11: `curl -I` 200, `Content-Length` 1.296.461, `Last-Modified` 11/11 15:21 (UTC), nessun `X-Robots-Tag`. Piano: `kosgroup/dati/pdf/pieghevole-parco-di-veio-2026-11-11.json`.
