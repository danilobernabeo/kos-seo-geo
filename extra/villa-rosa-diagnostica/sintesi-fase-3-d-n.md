# Villa Rosa Diagnostica: sintesi parziale fase 3 (analisi D–N)

Data: 22/11/2026. Stato: **bozza parziale, non ancora rivista**. Etichette: [verificato] letto da una fonte, [derivato] calcolato dai dati, [ipotesi] da confermare.

## Cosa è stato fatto e cosa no

| Analisi | Stato |
|---|---|
| D domanda locale | parziale: dati Search Console e SERP; Keyword Planner e Trends non disponibili |
| E utenti | parziale: dati GA4 caricati il 22/11 (regione, ora, pagina di arrivo, link in uscita); città ed età/genere non disponibili; letti per ora regione e link in uscita |
| F prenotazioni | non fatta: servono dati aggregati TuoTempo estratti da Danilo |
| G presenza locale | parziale: dati Yext/profilo Google; portali (MioDottore, Micuro) non ancora controllati |
| H concorrenti | parziale: SERP di 6 query; siti dei concorrenti non ancora analizzati |
| I assistenti AI | non fatta |
| L contenuti | fatta sulla scansione del 20/11 |
| M, N | non fatte (solo i vincoli già noti dal progetto) |

## G. Profilo Google (dati Yext, entità 105 Diagnostica e KCA00_DEG Villa Rosa clinica)

- [verificato] Yext ha due entità distinte a Modena: «Villa Rosa Diagnostica» (105) e «Villa Rosa» (KCA00_DEG, la casa di cura).
- [derivato] Diagnostica, azioni dal profilo: chiamate («Tap to Call») 509 a ottobre 2025, 600 a ottobre 2026 (+18%), con un massimo di 1.015 ad aprile 2026; richieste di indicazioni 195 → 237 (+22%); **clic sul sito 361 → 252 (−30%)**.
- [derivato] Impressioni su Maps stabili o in crescita (395 → 610 a ottobre); impressioni su Search 5.757 a settembre 2025, 5.582 a settembre 2026, quindi stabili fino a settembre.
- [derivato] Gli ultimi due mesi (ottobre 2026 impressioni Search 1.417, novembre parziale) crollano in modo simile su entrambe le entità: [ipotesi] ritardo o cambio di fornitura del dato, da verificare prima di leggerlo come calo reale.
- [verificato] Il profilo 105, come incollato da Danilo, ha come link «Sito web» la scheda kosgroup.com, non villarosadiagnostica.it: i 252 clic mensili non sono quindi traffico del sito della Diagnostica. Secondo Danilo il link è stato spostato sulla scheda kosgroup indicativamente a fine febbraio 2025, quando è andato online kosgroup.com [dichiarato, data approssimata, non verificata nello storico del profilo]. Il confronto ottobre 2025 → ottobre 2026 è quindi tutto successivo al cambio: il calo 361 → 252 riguarda i clic dal profilo verso la scheda kosgroup, non un passaggio di destinazione. Nei dati Yext i clic «Sito web» non mostrano discontinuità tra febbraio (342) e marzo 2025 (392). Dal marzo 2025 quei clic non compaiono più come traffico di villarosadiagnostica.it in GA4; i dati GA4 di kosgroup.com partono solo dal 30/12/2025, quindi per la scheda manca il confronto con l'anno precedente.
- Lettura [ipotesi]: il profilo Google non perde visibilità, anzi genera più telefonate; ciò che cala è il passaggio dal profilo al sito. Coerente con il calo di clic di marca sul sito. Le chiamate non dicono se diventano prenotazioni (serve F).
- La clinica (KCA00_DEG) ha un andamento diverso: chiamate in calo lento (1.067 a gennaio 2025 → 613 a ottobre 2026), clic sul sito 432 → 198 ad ottobre su ottobre.
- Non verificato: coerenza di nome, indirizzo e telefono sui portali; indirizzo confermato da Danilo il 22/11: Via Fratelli Rosselli 83 (il «Rossetti» del README era un errore, corretto).

## H. Concorrenti nelle SERP di Modena (browser di Danilo, personalizzato, desktop)

Query e primi risultati organici rilevanti (host):

| Query | Villa Rosa nei risultati | Concorrenti ricorrenti |
|---|---|---|
| elettromiografia modena | villarosadiagnostica.it in 2ª posizione organica; blocco locale con scheda «Da Villa Rosa Diagnostica» | cdcfogliani.it, poliambulatoriopcm.it, MioDottore, Hesperia, Doctolib |
| radiografia modena | kosgroup.com 3ª, villarosadiagnostica.it 8ª | Fogliani, centrodimedicina.com, ptmodo.it, Policlinico, Affidea |
| risonanza magnetica aperta modena | kosgroup.com 7ª, villarosadiagnostica.it 8ª; scheda «Villa Rosa» nel blocco locale | centrodimedicina.com, Carpi, Fogliani, Affidea |
| centro diagnostico modena | **kosgroup.com 1ª**, villarosadiagnostica.it non nei primi 10 | Affidea, Alliance Medical, Laboratorio Test, Centro Analisi Modenese |
| cone beam modena | villarosadiagnostica.it 2ª e 10ª, kosgroup.com 6ª | Clinica Cantoni, Affidea, studi odontoiatrici |
| risonanza magnetica / tac / ecografia modena | rilevate nella fase precedente | vedi sintesi-fase-2 |

- [ipotesi] Osservato una volta, con SERP personalizzata da desktop: per «centro diagnostico modena» compare la scheda di kosgroup.com in 1ª posizione e non il sito della Diagnostica. Da riverificare con rilevazioni non personalizzate prima di dire che la scheda lavora come pagina principale per la domanda generica.
- [verificato come osservazione singola] Sulle query per esame la Diagnostica è presente ma in concorrenza con almeno 4–5 attori fissi (Fogliani, Affidea, Centro di Medicina, Alliance Medical/Centro Alfa) e con i portali di prenotazione (MioDottore, Doctolib, elty).
- [verificato] Per «cone beam» la Diagnostica occupa due posizioni: è il tema dove è più forte.
- Limite: SERP personalizzate (account di Danilo, sede Modena), una sola rilevazione, solo desktop.

## L. Contenuti del sito (scansione del 20/11/2026, 40 URL)

- [verificato] Una sola pagina prenotazione (`/prenota-online`, 1.098 parole) con 135 link TuoTempo, ma solo 3 link interni in entrata: la pagina che porta le prenotazioni è poco collegata.
- [verificato] Pagine esame ben fornite: risonanza (1.107 parole), odontoiatrica (719), TMS (869 e una seconda versione con 3.171 parole e un solo link in entrata, probabile duplicato da unificare).
- [verificato] Pagine sottili: 21 pagine sotto 300 parole (una è il 404), tra cui ecocolordoppler (265), la maggior parte dei profili medici (da 49 a 223 parole) e la psichiatria/psicoterapia (173 e 212).
- [verificato] `/antonella-rosi` risponde 404 ma è in sitemap e linkata da 3 pagine.
- [verificato] Due pagine quasi omonime: `/ecocolordoppler` e `/ecocolordoppler-vascolare`; `/medici/` con la barra finale.
- [verificato] Meta description a 15 caratteri su gran parte delle pagine (valore predefinito), assente su una; title di 31–80 caratteri.
- [verificato] Nessuna pagina dedicata a TAC, ecografia per distretto, né agli esami annunciati per la nuova sede. [ipotesi] Va confrontata con l'elenco esami del piano marketing prima di proporre le nuove pagine.

## E. Utenti (dati GA4 caricati il 22/11 in `ga4storico_vrd`, 1/1/2024-21/11/2026)

- Lo script è stato lanciato in scrittura (non solo in prova) su tutto il periodo: tabelle `storico_geo`, `storico_orario`, `storico_arrivo`, `storico_uscite` popolate; `storico_utenti` vuota.
- [verificato] **Città**: la dimensione è «(not set)» nel 100% delle sessioni in tutti i mesi: l'analisi per città non è possibile in questa proprietà. La regione è valorizzata (non assegnata in meno del 10% delle sessioni).
- [verificato] **Età e genere**: nessun dato (la tabella `storico_utenti` ha 0 righe), probabilmente perché Google Signals non è attivo o per le soglie di privacy. Da non ripetere senza prima verificare l'impostazione.
- [derivato] **Regione** (sessioni, settembre-ottobre 2025 → 2026, sito della Diagnostica): Emilia-Romagna 2.424 → 1.624 (−33%); Lombardia 1.245 → 599 (−52%); Veneto 413 → 138 (−67%); Lazio 251 → 113; Puglia 104 → 22. L'Emilia-Romagna passa da circa il 39% a circa il 50% delle sessioni. Il calo è quindi più forte fuori regione. [ipotesi] Per un centro diagnostico di Modena l'alto numero di sessioni dalla Lombardia nel 2025 (oltre un quinto del totale) è anomalo: potrebbe venire da campagne, da traffico non locale o da traffico automatico. Non si può dire senza incrociare regione e canale (non presenti nella stessa tabella).
- [derivato] **Clic sui link in uscita** (evento `click`, settembre-ottobre): verso `app.tuotempo.com` 500 (2024) → 102 (2025) → 45 (2026); verso `kosgroup.com` 2 → 458 → 229. La somma dei due è 502 → 560 → 274: il percorso di prenotazione è passato dal collegamento diretto a TuoTempo al passaggio dalla scheda kosgroup, e il volume complessivo del 2026 è circa la metà di quello del 2025 (−51%), come le sessioni (−48%). [ipotesi] I link verso kosgroup.com includono anche link non di prenotazione (menu, scheda): il conteggio non è un conteggio di prenotazioni.
- [verificato] Google Ads, dataset `google_ads_kos` in BigQuery: i dati partono dal 1/11/2025, quindi non dicono nulla su settembre-ottobre 2025. Le tre campagne Search della Diagnostica (Brand, Unbranded, Polo Diagnostico) compaiono da novembre 2025; la campagna Brand fa 555-875 clic al mese nel 2026. Non si sa se ci fossero campagne prima: non spiegano la Lombardia 2025. Da chiedere a Danilo (accesso a Google Ads): clic per regione di ottobre 2025 e ottobre 2026 e date di avvio delle campagne.
- Da fare: ora del giorno e pagine di atterraggio (dati già in BigQuery); città ed età non ricavabili.

## D. Domanda locale (parziale)

- Dati Search Console già in sintesi-fase-2 e sintesi-marca-tre-pagine: la domanda di marca è la parte più grande e quella che cala; la domanda per esame (risonanza, elettromiografia, cone beam) tiene meno bene dopo aprile 2026.
- Mancano volumi di ricerca esterni; non si stimano.

## M, N: vincoli già noti

- [verificato] Il dato strutturato resta un unico `@graph` emesso lato server dalla libreria comune; nessun rilascio in produzione prima della consegna dello sprint 4.
- [ipotesi] Quale pagina sia la principale per Google nelle query generiche (scheda kosgroup, sito della Diagnostica, o le due con ruoli distinti) è una scelta di KOS/marketing: la proposta presenterà le alternative.
- Da chiedere a KOS: chi gestisce il profilo Google, destino di villarosa.it, uso dei dati TuoTempo, regole su pubblicità sanitaria e consenso.

## Prossimi passi

1. Primo lancio dello script GA4 con --prova (E) e richiesta dei dati TuoTempo aggregati (F): serve Danilo, richieste al minimo fino al 26/11 (rilascio sprint 4).
2. Portali e coerenza dei dati di sede (G), siti dei concorrenti (H), assistenti AI (I).
3. Revisione indipendente della bozza, poi il riepilogo per la chat principale.
