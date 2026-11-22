# Villa Rosa Diagnostica: sintesi parziale fase 3 (analisi D–N)

Data: 22/11/2026. Stato: **bozza parziale, non ancora rivista**. Etichette: [verificato] letto da una fonte, [derivato] calcolato dai dati, [ipotesi] da confermare.

## Cosa è stato fatto e cosa no

| Analisi | Stato |
|---|---|
| D domanda locale | parziale: dati Search Console e SERP; Keyword Planner e Trends non disponibili |
| E utenti | parziale: dati GA4 caricati il 22/11 (regione, ora, pagina di arrivo, link in uscita); città ed età/genere non disponibili; letti per ora regione e link in uscita |
| F prenotazioni | non fatta: servono dati aggregati TuoTempo estratti da Danilo |
| G presenza locale | parziale: dati Yext/profilo Google e prima ricerca sui portali (Micuro, QSalute, disabili.com, MioDottore, Doctolib); non fatta la verifica diretta delle schede |
| H concorrenti | parziale: SERP di 6 query; siti dei concorrenti non ancora analizzati |
| I assistenti AI | parziale: 3 domande in AI Mode di Google e 2 in ChatGPT (senza accesso) e 2 in Gemini (con l'account di Danilo); Perplexity non usato |
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

### Portali e elenchi (ricerca web del 22/11, schede non aperte una per una)

- [osservato] **Micuro** ha la scheda «Villa Rosa Neomesia di Modena - KOS», «Casa di Cura privata accreditata SSN», Via F.lli Rosselli 83, aggiornata il 17/7/2026, senza valutazioni: la sede è descritta come casa di cura, non come centro diagnostico.
- [osservato] **QSalute** ha la scheda «Villa Rosa di Modena» con 3,8 su 15 recensioni (di cui 8 da 5 stelle); una recensione di diagnostica per immagini (radiografia a un figlio, scelta per il costo inferiore ad altre cliniche) conferma che le recensioni riguardano anche la Diagnostica, mescolate con quelle della casa di cura.
- [osservato] **disabili.com** elenca «Casa di Cura Villa Rosa» Via Fratelli Rosselli 83, «privata accreditata», con il telefono 059 303034 (il centralino, non il numero della diagnostica 059 306335).
- [non verificato] **MioDottore** e **Doctolib**: la ricerca non ha restituito una scheda della Diagnostica (compaiono altre strutture di Modena con prezzi, per esempio un'ecografia addome completo a 127 € da Affidea); l'assenza va verificata aprendo i portali.
- [ipotesi] La Diagnostica e la casa di cura condividono indirizzo, nome e talvolta telefono nei portali: le recensioni e i dati si mescolano e la valutazione di Google (4,8) non si ritrova sulle altre piattaforme (QSalute 3,8). Per il restyling conta distinguere i due soggetti nei dati pubblici.

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

## I. Assistenti AI (parziale: AI Mode di Google, 22/11/2026, 3 domande)

Metodo: AI Mode di Google (`udm=50`) dal browser di Danilo, personalizzato, Modena, desktop; una sola esecuzione per domanda. Perplexity richiede l'accesso e non l'ho usato; ChatGPT e Gemini non sono stati provati. Il campione è piccolo: serve per osservare come viene descritta la Diagnostica, non per misurarne la frequenza.

- [osservato] «dove fare una risonanza magnetica aperta a Modena»: la Diagnostica compare, con indirizzo corretto (Via Fratelli Rosselli 83), risonanza 1,5 Tesla, attenzione ai pazienti ansiosi o claustrofobici, privato e convenzione SSN, con fonte villarosadiagnostica.it. Compare accanto ad Affidea, Fogliani e Centro di Medicina (Carpi); la risposta distingue le macchine «aperte» (Affidea, Carpi) da quelle «semiaperte» (Fogliani, Diagnostica).
- [osservato] «Villa Rosa Diagnostica Modena orari e come prenotare»: orari corretti (lun-ven 7:30-19:30, sab 7:30-19:00), segreterie, due telefoni (059 306335 diagnostica, 059 303034 centralino), fonti villarosadiagnostica.it, kosgroup.com e MioDottore. Le informazioni coincidono con quelle di `/informazioni-utili`, tranne due punti: la risposta dice «prenotazione online solo regime privato» (il sito non usa questa categoria) e cita gli sportelli CUP (il sito cita solo le farmacie secondo l'AUSL).
- [verificato] Il sito stesso riporta **due email diverse**: `diagnostica.villarosa@kosgroup.com` (intestazione) e `diagnostica@villarosa.it` (sezione Servizi diagnostici); l'AI ha ripreso la seconda, del dominio `villarosa.it` (posta attiva, sito morto, decisione pendente). Da uniformare, anche per il dato strutturato.
- [osservato] «dove fare una elettromiografia a Modena»: la Diagnostica compare tra i poliambulatori privati e convenzionati, con indirizzo corretto e la dicitura «consegna immediata del referto» (da verificare con la pagina del sito). Concorrenti citati con prezzi indicativi presi dai portali (Poliambulatorio C.A.F. da circa 125 €, Coliseum Center 175-195 € per l'esame doppio) e con prenotazione tramite Doctolib/MioDottore; per la Diagnostica nessun prezzo.
- [ipotesi] Le fonti citate dall'AI sono sia villarosadiagnostica.it sia kosgroup.com: i due domini sono letti insieme. I contenuti delle pagine del sito (orari, contatti, pagina esame) vengono ripresi quasi alla lettera: l'accuratezza dell'AI dipende dall'accuratezza e dalla coerenza di quelle pagine (due email, due telefoni).
- [osservato] **ChatGPT** (22/11, nella sessione del browser risultava senza accesso, con ricerca web attiva; una esecuzione per domanda):
  - «Dove posso fare una risonanza magnetica aperta a Modena?»: la Diagnostica **non compare**; risposta con Affidea Modena Medica (risonanza articolare aperta) e Policlinico di Modena.
  - «Dove posso fare una elettromiografia a Modena?»: la Diagnostica compare **al primo posto** («anche SSN», Via Fratelli Rosselli 83, EMG semplice e a quattro arti, referto al termine dell'esame), seguita da Coliseum Center (100-175 €), Poliambulatorio C.A.F. (100-125 €) e Centro Alfa; per i concorrenti ci sono prezzi indicativi, per la Diagnostica no.
  - Quindi, per la risonanza, ciò che AI Mode di Google dice (Diagnostica presente) e ciò che dice ChatGPT (assente) differisce: l'esito dipende dall'assistente e dalla domanda.
- [osservato] **Gemini** (22/11, con l'account Google di Danilo, che risulta localizzato a Milano «in base ai luoghi di lavoro»: possibile distorsione; una esecuzione per domanda): la Diagnostica compare in entrambe le risposte, con indirizzo corretto, orari corretti e **valutazione 4,8 stelle**, la più alta tra i centri elencati (Fogliani 4,1, Policlinico 3,9, Coliseum 3,7, Affidea e Centro di Medicina 3,6, C.A.F. 3,5). Per l'elettromiografia è la prima citata; per la risonanza è tra i centri diagnostici ma non è descritta come «aperta». Gemini la definisce «accreditato» e convenzionato con il Servizio Sanitario Regionale: [non verificato] se l'accreditamento è corretto per la sede.
- [derivato] Sintesi dei tre assistenti: sulla risonanza aperta la Diagnostica compare in 2 su 3 (non in ChatGPT); sull'elettromiografia compare in 3 su 3, in prima posizione in ChatGPT e Gemini. Con una sola esecuzione per domanda e sessioni non anonime non si può dire altro.
- Prossimi passi: aggiungere domande su TMS, TAC, ecografia e confronto tra centri; ripetere in momenti diversi (una sola esecuzione per domanda non basta a parlare di frequenza).

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
- [verificato] **Google Ads non spiega la Lombardia del 2025** (verifica fatta da Danilo con l'MCP di Google Ads, account 8545219698, sola lettura):
  - Da gennaio a ottobre 2025 nessuna campagna ha mandato clic a villarosadiagnostica.it né a `/it/centri/villa-rosa-diagnostica`. In settembre-ottobre 2025 era attiva una sola campagna con «Polo Diagnostico» (Emilia-Romagna; landing kosgroup.com): 237 e 233 clic, di cui circa 165 dalle Marche e 18-33 dalla Lombardia.
  - Le campagne lombarde Polo Diagnostico (Cinisello, Bollate) puntavano a polodiagnostico.it e a landing di kosgroup.com e sono finite il 30/6 e il 7/9/2025.
  - Le due campagne Villa Rosa (Brand dal 18/11/2025, Unbranded dal 25/11/2025, raggio 30 km da Modena) mandano tutto a `kosgroup.com/it/centri/villa-rosa-diagnostica`, mai al sito della Diagnostica. Brand: 652 clic a settembre 2026 e 555 a ottobre (10 e 47 conversioni); Unbranded: 97 e 84 clic, 0 conversioni.
  - [derivato] Nel sito della Diagnostica la maggior parte delle sessioni di settembre-ottobre 2025 è organica (5.793 su 8.000 circa) e con la stessa quota di sessioni con interazione (0,61) del 2026 (0,59): non è traffico automatico evidente. Il traffico lombardo del 2025 era quindi in gran parte organico. [ipotesi] Può trattarsi di ricerche «villa rosa» di altre strutture omonime o di altre query non locali; i dati non permettono di distinguere. Il calo del 52% dalla Lombardia è soprattutto un calo organico.
  - [derivato] Il canale «AI Assistant» compare nel 2026 con 17 sessioni (0 nel 2025): piccolo, ma è la prima traccia di traffico da assistenti AI (da usare nell'analisi I).
  - Limiti: la cronologia delle modifiche dal 1/6/2025 non è leggibile via API (solo gli ultimi 30 giorni): per le date dei cambi di URL e di località serve l'interfaccia di Google Ads. Il budget delle due campagne Villa Rosa risulta modificato il 21/11/2026, il giorno prima delle verifiche. Impostazioni e budget sono quelli attuali.
- [derivato] **Pagine di atterraggio** (sessioni, settembre-ottobre 2025 → 2026): home 1.896 → 985 (−48%); risonanza 686 → 418 (−39%); TMS (due pagine) 972 → 252 (−74%); radiologia convenzionale 168 → 25 (−85%); ecografia 90 → 25 (−72%); «il centro» 179 → 45 (−75%). Restano stabili: visita psichiatrica 153 → 143, neurologica 90 → 91, prenota-online 113 → 107. Il calo è quindi concentrato sulle pagine di esami diagnostici e sulla TMS, non su tutto il sito. [ipotesi] Per la TMS pesa il calo delle query non di marca già visto in sintesi-fase-2; per radiologia ed ecografia da verificare in Search Console per pagina.
- [derivato] **Ora del giorno**: il calo è uniforme su tutte le ore (circa −45%/−50% a ogni ora); l'orario di punta resta 9-17. Nessuno spostamento di orario da leggere.
- Non ricavabili: città (sempre «(not set)») ed età/genere (nessun dato).

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
