# Diagnostica Villa Rosa: sintesi della fase 2 (traffico, funnel di prenotazione, misurazione, verifiche tecniche)

21/11/2026 · Danilo Bernabeo · per la revisione nella chat principale

## Stato e fonti

- **Flussi attivati il 20/11/2026** (dettaglio in `docs/riferimenti/google-cloud-kos.md`):
  - GA4 → BigQuery: proprietà 317279845, collegamento creato; il dataset `analytics_317279845` compare con la prima esportazione giornaliera (non ancora presente il 21/11 alle 8:30).
  - Search Console, esportazione in blocco: `searchconsole_gsc_vrd` (europe-west3), parte dal 20/11, senza storico.
  - Storico Search Console dall'API: `searchconsole_vrd`, 8/7/2025-19/11/2026 (ultimo giorno con dati il 18/11).
  - Storico GA4 dalla Data API: `ga4storico_vrd`, 1/6/2022-19/11/2026 (54 mesi, dati aggregati, nessun identificativo di utente).
- **Altre fonti in sola lettura:** GA4 e Search Console di kosgroup.com (`analytics_333083624`, `searchconsole_kos`, dal 30/12/2025 per GA4), Google Ads (`google_ads_kos`, dati dal 1/11/2025), il container GTM (bozza «workspace 7», esportata il 20/11/2026), PageSpeed Insights (5 pagine, mobile), prove da PowerShell e rendering con Chrome (5 pagine) del 21/11/2026.
- **Dati reali di utenti sulle prestazioni (Core Web Vitals) assenti** per tutte le pagine e per l'origine: il traffico non basta.
- **Cosa non è ancora stato fatto:** TuoTempo (prenotazioni), scheda Google/Yext, log del server, backlink, rapporto «Link» di Search Console, controllo del banner e dei cookie sul comportamento reale del sito.
- Etichette: **[verificato]** letto nei dati o nei file, **[derivato]** calcolato da dati verificati, **[ipotesi]** da confermare.

## Il traffico: due gradini di calo

**Serie.** Sessioni GA4 del sito (evento `session_start`: la somma delle sessioni per pagina conta più volte le sessioni con più pagine e dà circa il 30% in più nel 2025-26, il 54% in più a marzo 2023) e clic/impressioni di Search Console. [verificato]

| Mese | Sessioni GA4 (`session_start`) | Clic Search Console | Impressioni Search Console |
| --- | --- | --- | --- |
| Ott 2025 | 3.006 | 2.102 | 56.563 |
| Nov 2025 (picco impressioni) | 3.111 | 2.137 | 65.262 |
| Gen 2026 | 2.503 | 1.747 | 58.475 |
| Mar 2026 | 2.685 | 1.881 | 61.510 |
| Ago 2026 | 1.267 | 914 | 25.630 |
| Ott 2026 | 1.612 | 1.068 | 26.035 |

- Giugno 2022-agosto 2025: da circa 2.100 a 3.760 sessioni al mese; massimo a marzo 2023 (3.757). [verificato]
- Confronto con lo stesso mese dell'anno prima (sessioni GA4) [derivato]:
  - luglio-novembre 2025: da +5% a -8%;
  - dicembre 2025-marzo 2026: da -17% a -29%;
  - luglio-ottobre 2026: da -38% a -49%.
- Il calo di dicembre è stagionale (visibile nel 2022, 2023, 2024); il rimbalzo di gennaio 2026 è però più debole (+13% su dicembre contro +32-40% negli anni prima). [derivato]
- Il rapporto tra clic di Search Console e sessioni GA4 è stabile da settembre 2025 a ottobre 2026 (0,66-0,72; luglio-agosto 2025 0,54-0,66): il calo è reale e non un effetto della misura. [derivato]
- Da settembre 2026 le sessioni sono stabili intorno a 1.600 al mese. [verificato]

**Scomposizione (Search Console, settembre-ottobre 2025 contro 2026).** [verificato]

| Tipo di ricerca | Clic 2025 | Clic 2026 | Impressioni 2025 | Impressioni 2026 |
| --- | --- | --- | --- | --- |
| Con «villa rosa» | 1.425 | 536 | 16.831 | 10.918 |
| Altre ricerche | 435 | 155 | 39.479 | 11.083 |

(la riga «villa rosa» include anche «villarosa» scritto attaccato; le query anonimizzate non sono incluse: i totali del sito sono più alti)

- Con «villa rosa» il tasso di clic passa dall'8,5% al 4,9%.
- «villa rosa modena»: 712 → 275 clic; «villa rosa»: 185 → 30 clic e impressioni da 1.769 a 237 (-87%, in gran parte tra giugno e agosto 2026).
- Pagine con più clic persi: home 940 → 400; risonanza magnetica 277 → 114; TMS 185 → 23; il centro 122 → 10. Impressioni di `/radiologia-convenzionale` da 13.726 a 492 e di `/prenota-online` da 10.372 a 457.
- Crolli mensili sulle pagine di prenotazione e di servizio tra dicembre 2025 e gennaio 2026: `/prenota-online` da 4.534 a 1.453 impressioni; `/radiologia-convenzionale` da 5.632 a 1.844; `/risonanza-magnetica` da 6.284 a 3.811.

**Anomalie nei dati GA4.** [verificato]

- Visualizzazioni gonfiate in alcuni mesi: agosto 2023 (11.892 viste con 3.621 sessioni), gennaio e marzo 2024, agosto 2024 (14.765); «Unassigned» senza sessioni compare anche a settembre, ottobre e dicembre 2023, giugno e novembre 2024, gennaio, maggio e agosto 2025. Per agosto 2024: 10.868 viste nel canale «Unassigned» con 11 sessioni, cioè visualizzazioni senza sessione, probabilmente automatiche. Vanno escluse dai calcoli sulle pagine viste.
- Le sessioni con interazione passano dal 35-40% (settembre 2022-agosto 2023; 42-52% a giugno-agosto 2022) al 55-70% da settembre 2023: salto brusco compatibile con un cambio di misurazione (il consenso è una delle ipotesi), non del comportamento. I due periodi non sono confrontabili. [ipotesi sulla causa]
- Se il consenso fosse richiesto prima delle statistiche, GA4 vedrebbe solo chi accetta e le sessioni sarebbero un minimo; il container però concede le statistiche di default (vedi misurazione): quanto GA4 misuri non è stabilito. [ipotesi, da provare nel browser]
- Solo eventi automatici (`page_view`, `session_start`, `first_visit`, `user_engagement`, `scroll`, `click` in uscita, `file_download`; `view_search_results` 2 volte, da host `webcache.googleusercontent.com`; nei dati compaiono anche host `translate.goog` e webcache, non filtrati): nessun evento di prenotazione, telefono o email sul sito. [verificato]

## Il funnel di prenotazione e il rapporto con kosgroup.com

- **Due strade diverse dalla stessa home.** Il pulsante grande «Prenota online» e un secondo link della home puntano a `/prenota-online` (pagina del sito con 135 link diretti a TuoTempo); la voce «Prenota Online» del menu, presente su tutte le 39 pagine, punta alla scheda su kosgroup.com (`/it/centri/villa-rosa-diagnostica#prenota-online`). «Informazioni utili» ha un terzo link a `/prenota-online`. Nessun altro link interno la raggiunge. [verificato nell'HTML]
- `/prenota-online` risponde 200 ed è raggiunta da circa 10-30 sessioni al mese da traffico diretto, con visualizzazioni stabili da settembre 2025 (89 a settembre-ottobre 2025, 91 a novembre, 85 a ottobre 2026); da ricerca Google 33-72 sessioni al mese nel 2026. Non risultano link esterni rilevanti (canale «Referral»: 1 sessione in 17 mesi); i backlink veri non sono verificati. [verificato per GA4]
- **Link diretti a TuoTempo sul sito:** 135 su `/prenota-online`, 11 sulla pagina odontoiatrica, 4 su ecocolordoppler vascolare, 2 su elettromiografia; risonanza, ecografia, RX, radiologia body e neuroradiologia non ne hanno. [verificato]
- **La scheda su kosgroup.com** (sessioni al mese da gennaio 2026) [verificato]:
  - Google Ads (campagne a pagamento): circa 440-570;
  - ricerca Google: circa 200-420;
  - rinvio dal sito della Diagnostica: 169 a marzo-aprile, 73 a ottobre; è circa il 3-7% delle sessioni del sito;
  - clic sul telefono circa 150 al mese in primavera, 31-36 a ottobre-novembre; email 22-42 in primavera e 8-12 a ottobre-novembre.
- **Evento `click_tuotempo`** sulla scheda solo dal 15/10/2026: 154 a ottobre (17 giorni; 35 da utenti arrivati dal sito), 209 a novembre in 19 giorni (54 dal sito). Prima non esisteva. Sul sito della Diagnostica non è mai esistito alcun evento verso TuoTempo. [verificato]
- **Google Ads:** le campagne «PWC - [S] - Villa Rosa - Brand» e «- Unbranded» hanno come pagina di destinazione la scheda su kosgroup.com. La prima ha la prima impressione il 18/11/2025, la seconda il 25/11/2025 (i dati di Ads partono dal 1/11/2025, quindi sono avvii veri). La campagna di marca porta 744 clic a dicembre, 875 a gennaio e circa 650 al mese fino a ottobre; il costo passa da circa 280 euro al mese a circa 90 da febbraio. [verificato]
- **Data dello spostamento della prenotazione:** le visualizzazioni di `/prenota-online` crollano tra il 25 e il 26/6/2025 (da 15-30 al giorno a 2-10; la pagina era già in calo da febbraio: 726 viste a febbraio, 412 a giugno 2025; poi 112 a luglio, 42 ad agosto, poi circa 90) e le sessioni da ricerca Google passano da 175 a 87 a 23. [verificato] Compatibile con il cambio della voce di menu verso la scheda su kosgroup.com [ipotesi: confermare sul log del manager MODX]. La stima del referente (novembre-dicembre 2025) è quindi probabilmente tardiva. Il GA4 di kosgroup.com parte il 30/12/2025 e non permette di datarla.

## Cosa dicono i dati sulle cause del calo

- **Tre eventi distinti e datati**, da non fondere:
  1. 26/6/2025: crollo delle visite a `/prenota-online` (prenotazione spostata sulla scheda, ipotesi).
  2. 18 e 25/11/2025: avvio delle campagne Ads verso la scheda. Con «villa rosa» le impressioni restano stabili e i clic calano: compatibile con annunci sopra il risultato naturale. [ipotesi]
  3. 7/1/2026: in un solo giorno le impressioni di `/medici/` passano da 34-154 al giorno (6/1 festivo: 44) a 1-7, `/prenota-online` da 146 a 27, `/radiologia-convenzionale` da 178 a 65, mentre le impressioni della home salgono (332-346 il 7-8/1, 279 il 9/1, contro 123-229 nei giorni prima) e le impressioni totali del sito a gennaio crescono: coerente con la perdita dei link di sezione. Le impressioni perse sono soprattutto su ricerche di marca, con quasi zero clic (posizione di `/radiologia-convenzionale` da 3,6 a 6,2). Compatibile con la perdita dei link di sezione («sitelink») sotto il risultato di marca. [ipotesi: verificare la pagina dei risultati] Ads non riduce le impressioni organiche; la scheda su kosgroup.com compete invece con il sito nei risultati naturali.
- Le campagne non bastano a spiegare il calo: le ricerche non di marca perdono impressioni (39.479 → 11.083, settembre-ottobre 2025 contro 2026) e la campagna di marca non incide su di esse; il calo di clic sulla marca è circa 445 al mese tra i due periodi. La campagna Unbranded ha avuto 530 clic e 372 euro a dicembre 2025, poi circa 90 clic e 61 euro al mese. [ipotesi sostenuta dai dati]
- Il secondo gradino (primavera-estate 2026; «villa rosa» da sola cala del 75% tra giugno e agosto in impressioni, da 583 a 145; i clic scendono da 44 a 16 e la posizione media passa da 6,1 a 3,5: spariscono le impressioni in posizioni basse) non è spiegato da questi fatti: le campagne sono stabili da febbraio. [ipotesi: serve vedere come cambia la pagina dei risultati di Google per «villa rosa modena» e la scheda Google dell'attività]
- Non esclusi: stagionalità, posizionamento, un effetto delle risposte generate da Google, un cambio di ricerca di marca. [ipotesi]

## Misurazione: il contenitore GTM-P5XL4PT (bozza workspace 7)

Da confermare sulla versione pubblicata. [verificato sul file]

- **Tag:** GA4 (`G-NYPJMGS60Q`, coincide con lo stream della proprietà 317279845), Cookiebot, aggiornamento del Consenso, Microsoft Clarity, widget di accessibilità Sienna (codice caricato da un dominio terzo), un vecchio tag Universal Analytics (`UA-267643-37`) in pausa. Nessun tag di evento: né prenotazioni, né telefono, né email, né moduli (nel sito non ci sono moduli).
- **Consenso** (`defaultConsentStatistics = granted`, `waitForUpdate = 0`): il template Cookiebot imposta `statistics` su «granted» di default, e il tag di aggiornamento forza `analytics_storage = granted`. Se è così nella versione pubblicata, GA4 parte senza consenso. [derivato dal container; da verificare sul comportamento del sito]
- **Clarity** (registrazione di sessioni) parte su tutte le pagine senza condizioni di consenso nel container. [verificato nel container; da verificare se Clarity applica il consenso di Cookiebot]
- **Sienna** viene caricato da `website-widgets.pages.dev`, non versionato: codice esterno che può cambiare. [verificato]
- **Riscontro:** in Tempo reale arrivano `page_view`, `session_start`, `user_engagement`, `click`, `scroll` sulla proprietà 317279845: il tag funziona. [verificato]

## Consenso e pagina dei risultati: verifiche del 21/11/2026 (pomeriggio)

Fatte nel browser integrato di Claude sul computer di Danilo (profilo persistente, Google con l'account di Danilo, località sull'area di Modena: non è una navigazione anonima e i risultati sono personalizzati).

- **Consenso, prima di qualsiasi scelta sul banner** (banner visibile, `Cookiebot.hasResponse = false`, consenso `statistics = false`) [verificato]:
  - parte il tag GA4 e invia `page_view` a `region1.google-analytics.com/g/collect` con `gcs=G101` (stato del consenso: archiviazione pubblicitaria negata, archiviazione per l'analisi concessa), con un identificativo cliente nuovo (la sua data coincide con la visita) e i cookie `_ga` e `_ga_NYPJMGS60Q` creati;
  - Clarity carica il suo script e invia quattro richieste a `b.clarity.ms/collect`; non risultano cookie di Clarity (`_clck`, `_clsk`) leggibili da JavaScript; se Clarity applichi il consenso con una modalità senza cookie non è verificato;
  - viene caricato anche il widget di accessibilità da `website-widgets.pages.dev`.
  - Conferma del comportamento dedotto dal container: le statistiche partono senza consenso. Cookie impostati dal server (non leggibili da JavaScript) non verificati. Un solo test, un solo browser.
- **Pagina dei risultati di Google, ricerca da computer** [verificato]:
  - «villa rosa modena»: in cima un annuncio di kosgroup.com («Villa Rosa Modena», con i collegamenti «Villa Rosa Prenotazioni Online» e «Prenota la tua radiografia»); poi due schede di luogo: «Villa Rosa» (clinica per la salute mentale, 3,3/5 su 124 recensioni) e «Villa Rosa Diagnostica» (centro diagnostico, 4,8/5 su 37 recensioni), entrambe in Via Fratelli Rosselli 83; poi la risposta generata da Google, che tratta le due strutture insieme e riporta orari, telefoni ed email letti dal sito e da kosgroup.com; poi i risultati naturali: scheda della clinica su kosgroup.com, home del sito, scheda della Diagnostica su kosgroup.com e altri.
  - «villa rosa»: dominata da omonimi (località, alberghi, una cantina); la clinica compare tra i risultati di kosgroup.com; nella parte letta il sito della Diagnostica non compare.
  - «villa rosa diagnostica»: home del sito al primo posto, scheda su kosgroup.com al secondo, `/risonanza-magnetica` al terzo, pannello della scheda Google con 4,8/5 (37 recensioni), telefono 059 306335 e «Chiude alle ore 19» (il sito dichiara 19:30), pulsanti «Sito web» e «Indicazioni». Il pulsante «Sito web» porta alla scheda su kosgroup.com (`/it/centri/villa-rosa-diagnostica`), non al sito. Omonimi in rilievo: un centro di dialisi con lo stesso nome ad Abruzzo e la scheda del portale MioDottore. Nella ricerca la home non mostra link di sezione.
  - L'indirizzo della sede è Via Fratelli Rosselli 83 (non «Rossetti» come nel README): i dati del sito, di Google e di kosgroup.com coincidono. Il «Rossetti» del piano marketing va riverificato.
  - Non verificati: ricerca da telefono, navigazione anonima, schermate non salvate nel repository, categorie secondarie e dati interni della scheda Google.

## Verifiche tecniche del 21/11/2026

- **Varianti dell'indirizzo:** `http://`, `http://www` e `https://www` rispondono 301 verso `https://villarosadiagnostica.it/` in un solo passaggio; indirizzi inesistenti rispondono 404 con la pagina completa del sito; `/index.php` risponde 200, doppione della home senza canonical. [verificato]
- **File alla radice:** `robots.txt`, `sitemap.xml` e `llms.txt` rispondono tutti 404 (`llms.txt` è solo facoltativo). Senza `robots.txt` non ci sono restrizioni né indicazione di una sitemap; non esiste una sitemap al percorso standard. [verificato] Chiude il punto lasciato aperto nella sintesi della fase 1.
- **Tempi del server** (dieci richieste da un PC in Italia): primo byte tra 0,16 e 0,21 secondi su home e Neuroradiologia. Il picco di 3,2 secondi della scansione del 20/11 non si è ripresentato. [verificato]
- **PageSpeed Insights** (mobile, un test per pagina, laboratorio):

| Pagina | Prestazioni | Accessibilità | Buone pratiche | SEO | LCP | CLS |
| --- | --- | --- | --- | --- | --- | --- |
| Home | 54 | 95 | 77 | 100 | 9,1 s | 0,31 |
| Risonanza | 72 | 98 | 77 | 100 | 8,2 s | 0 |
| Pagina di un medico | 63 | 100 | 77 | 100 | 5,0 s | 0,28 |
| Prenota online | 80 | 95 | 73 | 91 | 3,4 s | 0 |
| Informazioni utili | 80 | 98 | 77 | 100 | 3,5 s | 0 |

  - Primo byte in pochi millisecondi nel test non rallentato: il ritardo è nel caricamento (stile e script bloccanti, stima di risparmio circa 1,2-1,7 s) e nel peso (1,1-1,8 MB).
  - Spostamenti del layout sopra la soglia sulla home e sulle pagine dei medici, dovuti a immagini senza larghezza e altezza.
  - `/prenota-online`: errore JavaScript `SyntaxError: Unexpected token '/'` (due volte) e meta description mancante.
  - Su tutte le pagine: ascoltatore `unload` obsoleto; ordine dei titoli non corretto su quasi tutte (la pagina del medico lo supera); contrasto su home e prenota.
- **Rendering JavaScript** (Chrome, 5 pagine): ogni pagina guadagna alcune centinaia di parole (stima iniziale circa 300; un secondo conteggio dà circa 1.270, costante sulle 5 pagine: il numero dipende dal metodo), tutte del banner dei cookie (elenco dei cookie, compresi quelli di Clarity) e del widget di accessibilità; titoli, testi e link dei contenuti restano identici; nessun JSON-LD nemmeno dopo l'esecuzione. Nessun contenuto del sito dipende da JavaScript. [verificato]

## Decisioni e richieste

1. **Dove vive la prenotazione:** una sola strada, e di conseguenza il destino di `/prenota-online` (301 verso la nuova destinazione o ripresa nel sito), dei suoi 135 link TuoTempo, dei pulsanti della home e delle campagne Ads (che oggi puntano alla scheda su kosgroup.com). Riguarda chi gestisce le campagne e la scheda: va portata nella chat principale.
2. **Misurazione prima del restyling:** eventi per i clic su TuoTempo, telefono ed email (come già fatto sulla scheda dall'ottobre 2026). Statistiche concesse di default e Clarity (registrazione di sessioni) senza consenso su un sito sanitario sono un tema di conformità da portare al DPO, non solo una verifica. Correggere il consenso ridurrà le sessioni misurate: va fatto e annotato prima del lancio del 7/1/2027, altrimenti la serie si rompe. Rimozione del tag Universal Analytics. Il calo dei clic sul telefono sulla scheda (circa 150 in primavera, 31-36 a ottobre-novembre) coincide con la comparsa di `click_tuotempo`: verificare se è cambiata la scheda o il tag prima di leggerlo come calo reale.
3. **Contenuti incoerenti** (dalla fase 1): orari e telefoni diversi nelle pagine, indirizzi email, avviso di sospensione su due pagine.
4. **File mancanti:** `robots.txt` e sitemap (e decisione sui crawler dell'intelligenza artificiale). `llms.txt` non è una mancanza: Google non lo usa, al massimo facoltativo.
5. **Dominio `villarosa.it`:** posta attiva, sito morto con redirect a un server che non risponde; decisione su redirect e caselle da chiedere al referente (verifiche DNS e curl del 20/11/2026: MX attivo su un provider di posta, A del dominio con 302 verso www, www che non risponde sulla porta 80).

## Aperto

- Conferma sul log del manager MODX delle date dei cambi di menu (26/6/2025) e dell'eventuale redirect di `/prenota-online`; verifica della pagina dei risultati di Google per «villa rosa modena» (link di sezione, evento del 7/1/2026) e della scheda Google (secondo gradino del calo).
- Metodo dei conteggi della scheda su kosgroup.com (sessioni da Ads e da ricerca, clic `click_tuotempo` «dal sito»): i valori dipendono dalla sorgente usata; un secondo calcolo dà Ads 485-622 (846 a gennaio), organico 332-458, clic dal sito 24 e 41. Da fissare.
- File `varianti.txt` e `ttfb.txt` non conservati tra i dati di appoggio: le verifiche del 21/11 sono riproducibili da PowerShell.
- Cookie impostati dal server e comportamento di Clarity prima del consenso (le richieste partono, ma non risulta se Clarity applichi un proprio consenso senza cookie).
- Conferma dell'ente giuridico (KOS S.p.A. o KOS Care) per il dato strutturato e dei dati di contatto.
- TuoTempo (prenotazioni), Yext e scheda Google, log del server e dei crawler AI, backlink.
- Confronto delle campagne di Ads con il calo organico di marca, con più mesi di dati dopo il 20/11.
