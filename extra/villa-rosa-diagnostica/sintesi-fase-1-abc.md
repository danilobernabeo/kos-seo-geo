# Diagnostica Villa Rosa: sintesi della fase 1 (analisi A, B, C da scansione)

20/11/2026 · Danilo Bernabeo · per la revisione nella chat principale

## Stato e limiti

- **Fonte unica di questa fase:** scansione completa di https://villarosadiagnostica.it fatta il 20/11/2026 con Screaming Frog (10:19-10:30, 0,5 URL/s, 1 thread, configurazione `vrd-completa`). 302 URL incontrati: 120 interni (40 HTML, 22 JavaScript, 18 CSS, 31 immagini, 9 PDF) e 182 esterni. Nessun URL bloccato da robots.txt.
- **Non ancora disponibili:** i flussi verso BigQuery (GA4 e Search Console di `vrd`) non sono attivi, quindi **nessun dato di traffico, ricerche, posizioni o conversioni**. Le analisi A e C sono per ora solo la parte «strutturale»: i risultati sul traffico restano da fare.
- **Non verificato dalla scansione:** prestazioni reali (PageSpeed non eseguito), accessibilità (controllo automatico non eseguito), contenuto del contenitore GTM, robots.txt, redirect delle varianti `http`/`www`, pagine generate da JavaScript (la scansione ha letto l'HTML del server).
- Le esportazioni complete (890 file, CSV, sorgenti e testi delle pagine) sono sul PC di Danilo in `vrd-export/`, **fuori dal repository**. In `dati/` c'è solo l'inventario delle 40 pagine HTML (`scansione-20261120-pagine.csv`), senza testi.
- Ogni voce è marcata **[verificato]** (letta nei dati della scansione o nel sorgente salvato) o **[dedotto]** (ipotesi da confermare).

## Analisi B: stato tecnico del sito

**Struttura e indicizzazione**

- 39 pagine HTML rispondono 200 e sono indicizzabili; 1 risponde 404 (`/antonella-rosi`, a livello 3). Le altre stanno a uno o due clic dalla home (home a livello 0, 34 pagine a livello 1, 4 a livello 2). [verificato]
- La sitemap ha 49 voci: le 39 pagine, la pagina in 404 e 9 PDF. Una voce in 404 e 9 PDF in sitemap, tra cui il Codice Etico e il Modello organizzativo di KOS Care (documenti del gruppo, non della diagnostica) e moduli di consenso. [verificato]
- Il 404 `/antonella-rosi` è linkato 3 volte dalla pagina della visita neurologica (la pagina di una professionista tolta e il link rimasto). [verificato]
- `/medici/` è l'unica pagina con la barra finale nell'indirizzo; le altre no. [verificato]
- Nessuna pagina ha il tag `canonical`, nessuna ha meta robots, nessuna ha hreflang. [verificato]

**Dato strutturato e anteprime social**

- **Nessun JSON-LD** su nessuna delle 39 pagine: il validatore di Screaming Frog dà 0 tipi e il conteggio dei blocchi `application/ld+json` è 0 su tutte. [verificato sul codice del server] Non è escluso che un tag in GTM li inserisca via JavaScript: da controllare con il Rich Results Test e con la scansione JavaScript. [dedotto]
- **Nessun `og:` e nessuna X Card** su nessuna pagina (39 su 39 senza titolo, descrizione e immagine di condivisione). [verificato]

**Titoli, descrizioni, titoli H1/H2**

- Titolo: tutti nel formato «Nome pagina - Villa Rosa Diagnostica»; 4 oltre 65 caratteri (massimo 80); 2 pagine con lo stesso titolo (`/ecocolordoppler` e `/ecocolordoppler-vascolare`). [verificato]
- Meta description: **2 mancanti** (`/prenota-online`, `/attività-ambulatoriali-specialistiche`); **le 16 pagine dei medici hanno come descrizione una sola parola di 10-16 caratteri** («radiologia», «radiologia-body», «neuroradiologia», «elettromiografia», «ecocolordoppler»; una è scritta «neuroradiologio»): sembra l'etichetta della specialità e non una descrizione; 9 pagine oltre 160 caratteri. [verificato]
- **14 pagine di servizio hanno lo stesso H1** «Prestazioni Attività sanitarie finalizzate alla salute della persona e alla diagnosi precoce» (il nome dell'esame sta in un H2). 25 H1 superano i 70 caratteri. [verificato]
- Contenuto: mediana 265 parole per pagina; 15 pagine sotto 200 parole e 5 sotto 100 (soprattutto le schede dei medici); 36 documenti su 48 (pagine e PDF) classificati «molto difficili» da leggere dallo strumento (indicatore grezzo, nato per l'inglese: il giudizio sulla leggibilità è da non prendere alla lettera). [verificato il conteggio; il giudizio è dedotto]

**Pagine in concorrenza tra loro**

- `/ecocolordoppler` (4 link in entrata) e `/ecocolordoppler-vascolare` (57) hanno lo stesso titolo. [verificato]
- `/stimolazione-magnetica-transcranica` (869 parole, 43 link in entrata) e `/stimolazione-magnetica-transcranica-(tms)` (3.171 parole, 1 link in entrata): due pagine sullo stesso argomento. [verificato] Quale sia quella «giusta» è da decidere. [dedotto]

**Link**

- 152 link verso `app.tuotempo.com` (151 con testo «PRENOTA»): 136 prenotazioni con identificativo di prestazione distinto, 135 sulla sola pagina `/prenota-online`. Il sito ha già un collegamento diretto per esame. [verificato]
- Ogni pagina ha un link alla scheda di kosgroup.com (`/it/centri/villa-rosa-diagnostica`, 39 + 1 nel testo) e uno nel piè di pagina a `https://kosgroup.com/`, che risponde 301 verso `/it`. [verificato]
- Link rotti o da correggere in uscita: app iOS «MyPoloDiagnostico» (404 su Apple) in 2 pagine; link a `neomesia.com/villa-rosa` («Clinica Specialistica Psichiatrica») nella pagina Il centro, in 301; link a `sstefano.it/societa-trasparente` in 301; nel PDF della dichiarazione di accessibilità l'indirizzo scritto `villarosadiagnistica.it` (senza risposta). [verificato]

**Sicurezza e intestazioni**

- Tutti gli URL sono HTTPS; nessun contenuto misto. [verificato]
- Sui 120 URL interni mancano HSTS (119) e `X-Content-Type-Options` (119) e una Referrer-Policy sicura (119); la Content-Security-Policy manca su 48 URL e `X-Frame-Options` su 39 pagine HTML, secondo il conteggio di Screaming Frog (la base di calcolo di questi due ultimi dati non è stata riverificata riga per riga). [verificato per HSTS, X-Content-Type-Options e Referrer-Policy; gli altri due da riscontrare]
- Un cookie di sessione `PHPSESSID` viene emesso su tutte le 39 pagine HTML, prima di qualsiasi consenso. [verificato; se sia tecnico o analitico è da chiarire]
- Cache: l'HTML esce con `no-store, no-cache, must-revalidate` e scadenza 1981 (nessuna cache); le risorse statiche hanno `max-age=604800` (7 giorni) e compressione gzip. [verificato]
- Server nginx, HTTP/1.1. Tempo medio di risposta dell'HTML 0,32 s (massimo 3,25 s, `/neuroradiologia`, un picco singolo da ripetere). [verificato; il picco è dedotto]

**Immagini e file**

- 31 immagini, 1.362 kB in tutto, nessuna sopra 200 kB. Per Screaming Frog 30 su 31 non hanno gli attributi larghezza e altezza e 1 non ha il testo alternativo; le estrazioni personalizzate per WebP e caricamento differito danno 0 su tutte le pagine. [verificato; la scelta delle espressioni di estrazione non è stata riletta]
- 9 PDF: 7 sopra 200 kB, 3 senza titolo. I PDF di preparazione agli esami (ecografia e RX dell'addome) risultano modificati il 12/03/2026; il Codice Etico e il Modello organizzativo del 12/02/2019. [verificato]

**Piattaforma**

- Modello di pagina basato su un tema di terze parti (Bootstrap, jQuery, slider Revolution, `base href` assoluto) con 22 JavaScript e 18 CSS interni. La versione di MODX, di PHP e dei componenti non si vede dalla scansione. [verificato sul sorgente; il resto da leggere sul cloud]

## Analisi A: il patrimonio (solo struttura, senza traffico)

- Le pagine più linkate dall'interno sono la home (91 link), Risonanza magnetica (71), Radiologia convenzionale (63), Ecografica (58), Ecocolordoppler vascolare e Odontoiatrica (57). [verificato] Sono quelle da non spostare senza redirect.
- Le pagine meno raggiunte: la scheda TMS con «(tms)» (1 link), `/prenota-online` (3), alcune schede dei medici (3-4). [verificato]
- Il **traffico organico, le ricerche, le pagine che portano conversioni e i link in ingresso non sono stati analizzati**: servono Search Console e GA4 (flussi non ancora attivi). Il confronto con le 1.640 visite al mese dell'agenzia resta da fare.

## Analisi C: misura (inventario dal codice, senza il contenitore)

- **GTM:** contenitore `GTM-P5XL4PT` caricato nella testata di ogni pagina. Nessun `dataLayer.push` né attributo di tracciamento nel codice: gli eventi, se esistono, sono configurati dentro il contenitore (da esportare). [verificato nel codice; il resto non verificato]
- **Consenso:** lo script di Cookiebot (`cd.js`, la dichiarazione) è presente solo nella pagina Privacy; il banner non si vede nel codice della home. Può essere caricato da GTM: **da verificare** prima di fidarsi dei dati GA4. [non verificato]
- **Prenotazioni:** il collegamento a TuoTempo avviene con un indirizzo fisso (`app.tuotempo.com/mop/search_activity.php`, con base dati e area della sede) senza parametri di campagna: la sorgente della visita si perde a meno che TuoTempo o GTM non gestiscano il collegamento tra domini. [verificato; la conseguenza è dedotta]
- **Telefono:** due numeri, 059 303034 (servizi ambulatoriali) e 059 306335 (servizi diagnostici); nessun numero dedicato al sito; i link `tel:` sono presenti in tutte le pagine. Il clic sul telefono si può misurare solo se GTM lo registra: da verificare. [verificato per i numeri; la misura non verificata]
- **Parametri UTM per le mail di dicembre:** i link verso TuoTempo non li prevedono; il piano di misura deve decidere dove si leggono (pagine di arrivo sul sito, non l'app di TuoTempo). [dedotto]

## Incongruenze nei contenuti attuali (da chiarire con la struttura)

1. **Orari diversi su tre pagine.** Home: lun-ven 7:30-19:30, sab 7:30-19:00. Informazioni utili: ambulatoriali lun-sab 8:30-19:30, diagnostici lun-ven 9:30-16:30 e sab 8:30-13:00. Pagina Risonanza: ambulatoriali 8:00-19:30, diagnostici lun-ven 9:00-18:30 e sab 8:30-13:00. [verificato] Gli orari devono essere uno solo, uguale anche alla scheda Google e a RetailTune.
2. **Due indirizzi email** nel sito: `diagnostica.villarosa@kosgroup.com` (testata) e `diagnostica@villarosa.it` (pagina Informazioni utili, dominio diverso). [verificato] Da capire se `villarosa.it` è di KOS.
3. **Avviso di sospensione sulla home:** dal 31 agosto sono sospesi RX, ortopantomografia, teleradiografia, Dentalscan e Cone Beam «a causa dei lavori del nuovo Polo». [verificato] Il piano marketing li elenca tra i servizi «già presenti»: serve la data di riattivazione.
4. **Offerta oggi sul sito e offerta del piano.** Il sito presenta anche Stimolazione Magnetica Transcranica (TMS, in primo piano nella home «per il trattamento di patologie psichiatriche e neurologiche»), visita psichiatrica, colloqui di psicoterapia, visita neurologica e visita cardiologica; il piano non le cita. [verificato] Non è deciso se restano con il nuovo nome.
5. **«Villa Rosa» e la psichiatria.** Il piano segnala l'associazione con la clinica psichiatrica come punto di attenzione; il sito stesso mette TMS e psichiatria in evidenza e linka la scheda psichiatrica di Neomesia. [verificato] Che questo rafforzi la confusione è un'ipotesi da misurare con le ricerche, non un dato. [dedotto]
6. **Indirizzo.** Il sito indica Via Fratelli Rosselli 83, 41125 Modena; il briefing (README) cita «Via Fratelli Rossetti / via Asiago» per la nuova palazzina. [verificato nel sito] Può essere un errore di scrittura del piano marketing: da confermare l'esatto indirizzo prima di scriverlo nel dato strutturato.
7. **Nome del marchio.** Il sito usa «Villa Rosa Diagnostica» nei titoli e «Poliambulatorio Specialistico Diagnostico (Accreditato) Villa Rosa» nei testi; il nuovo nome è «Diagnostica Villa Rosa». [verificato]

## Cosa serve alla chat principale

**Decisioni**

- Quale pagina TMS tenere e la regola sui doppioni (ecocolordoppler, TMS).
- Se TMS, psichiatria e psicoterapia restano sul sito con il nuovo nome (cambia la strategia sul nome e sull'associazione con la psichiatria).
- Se i PDF del gruppo (Codice Etico, Modello organizzativo) devono restare nella sitemap della diagnostica.

**Da chiedere alla struttura o a KOS**

- Orari ufficiali e unica email; data di riattivazione dei servizi sospesi; indirizzo esatto della nuova sede; proprietà di `villarosa.it`; pagina e ruolo di `/antonella-rosi`.

**Da fare con me**

- Attivare i flussi BigQuery (GA4, poi Search Console) un passo alla volta.
- Esportare e leggere il contenitore GTM-P5XL4PT (eventi, consenso).
- Aprire la scansione JavaScript su una pagina per tipo e prendere PageSpeed.
- Ripetere la scansione di `/neuroradiologia` (picco di 3,2 s) e leggere `robots.txt` e le varianti `http` e `www`.

## Priorità emerse (primo elenco, da validare)

1. Dato strutturato assente: nuova struttura con `@id` stabili e `parentOrganization` verso KOS S.p.A. (regole del progetto).
2. Meta description, H1 e titoli: correggere il valore sbagliato delle descrizioni (16 pagine) e l'H1 generico (14 pagine).
3. Anteprime social assenti su tutte le pagine.
4. Pagina in 404 in sitemap, doppioni, link rotti in uscita.
5. Orari, email e nome unificati prima del 7/1 (stesso giorno su sito, scheda Google, kosgroup.com e RetailTune).
6. Misura: verificare GTM, consenso e collegamento con TuoTempo prima della mail di metà dicembre.
7. Intestazioni di sicurezza e cache: interventi tecnici a basso rischio, sul server.
