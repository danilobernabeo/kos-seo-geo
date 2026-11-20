# Diagnostica Villa Rosa: piano delle analisi per il restyling di villarosadiagnostica.it

20/11/2026 · Danilo Bernabeo

## Obiettivo

Con i dati del sito, di Google e delle prenotazioni, rispondere a cinque domande prima di proporre il restyling:

1. **Il patrimonio.** Che cosa porta oggi utenti e prenotazioni al sito, e cosa non va perso nel cambio di nome e di sede?
2. **La domanda.** Quali esami cerca la popolazione di Modena e provincia, con quali parole, e quanto spazio c'è per le novità (risonanza, TAC)?
3. **La concorrenza.** Come si presentano i concorrenti su Google, sulla mappa e negli assistenti AI, e dove la Diagnostica può distinguersi?
4. **Il sito.** Cosa manca al sito, tecnicamente e nei contenuti, perché un utente trovi l'esame, capisca come farlo (convenzione o privato, preparazione, tempi) e prenoti?
5. **La misura.** Come misureremo l'effetto del lancio e del restyling?

Ogni analisi qui sotto indica le fonti, il metodo, il risultato e la decisione che alimenta. La proposta finale (paragrafo 14) mette insieme i risultati.

## 1. Fonti dei dati e preparazione

| Fonte | Cosa serve | Come | Note |
| --- | --- | --- | --- |
| GA4 di villarosadiagnostica.it | eventi giornalieri da oggi in poi | collegamento a BigQuery, progetto `kos-analytics-482819`, europe-west3, esportazione giornaliera | parte dal giorno del collegamento, senza storico |
| GA4, storico | pagine, sorgenti, città, fasce d'età, dispositivi, eventi, mese per mese | API di GA4 (Data API), caricamento in BigQuery | lo storico disponibile dipende dalla conservazione impostata nella proprietà |
| Search Console | ricerche, pagine, posizioni, paesi e dispositivi | esportazione in blocco verso BigQuery (dataset `searchconsole_vrd`) più lo storico di 16 mesi dall'API | anche la scheda di kosgroup.com (`/it/centri/villa-rosa-diagnostica`) è nei dati già esportati |
| GA4 e Search Console di kosgroup.com | traffico e ricerche della scheda della Diagnostica e della clinica psichiatrica Villa Rosa | dataset già in BigQuery | per il rapporto tra i due siti e per la confusione tra i due «Villa Rosa» |
| TuoTempo | prenotazioni online per prestazione, data, canale e, se c'è, comune del paziente; disponibilità delle agende | export dall'area amministrativa, oppure dall'IT di KOS | solo dati aggregati, nessun dato personale |
| Prenotazioni al telefono e allo sportello | volumi per prestazione e per mese | dalla direzione o dal CUP della struttura | servono a pesare il canale online sul totale |
| Scheda Google (oggi gestita con Yext) | ricerche, visualizzazioni, clic al sito, chiamate, indicazioni, recensioni | rapporti Yext e Business Profile Performance API | storico e recensioni da proteggere nel trasloco |
| Google Ads | campagne, parole chiave, ricerche reali, costi, conversioni | dataset `google_ads_kos` se la Diagnostica è inclusa, altrimenti l'account | da verificare se ci sono campagne attive per Villa Rosa |
| Keyword Planner e Google Trends | volumi e stagionalità per esame più località | account Google Ads | |
| Dati di Chrome (CrUX) e PageSpeed | prestazioni reali e di laboratorio | API di CrUX e di PageSpeed | i dati reali ci sono solo se il traffico basta |
| Scansione del sito | pagine, link, redirect, metadati, immagini, dato strutturato | Screaming Frog, come per i due siti del progetto | anche la scansione dei siti dei concorrenti |
| Log del server | accessi dei crawler, compresi i bot AI | log di MODX Cloud | come fatto per AA e KOS |
| Piano marketing di gennaio 2027 | ipotesi su domanda, pubblico, concorrenti, calendario | documento del referente | da confermare o correggere con i dati |

**Risultato:** un dataset per sito in BigQuery e una cartella `dati/` con le estrazioni, senza dati personali.

## 2. Analisi A — Il patrimonio digitale attuale

- **Traffico organico reale** mese per mese degli ultimi 16 mesi, da confrontare con le 1.640 visite al mese stimate dall'agenzia. Divisione tra ricerche con il nome («villa rosa modena», «villa rosa diagnostica») e senza il nome («risonanza magnetica modena»).
- **Pagine che portano traffico e conversioni.** Per ciascuna: visite organiche, ricerche principali, clic su telefono e prenotazione, link in ingresso. Diventa l'elenco delle pagine da conservare o da reindirizzare con cura.
- **Ricerche su cui il sito è quasi in prima pagina** (posizioni 5-20 con molte impressioni): sono le opportunità più rapide.
- **Link in ingresso:** Search Console, più una verifica con uno strumento di backlink se disponibile. Si cercano i link da portali sanitari, ASL, medici e associazioni.
- **La scheda di kosgroup.com:** traffico (20.388 pagine viste dal 30/12/2025), ricerche che la fanno comparire, passaggi verso il sito indipendente.

**Decisioni che alimenta:** la mappa dei redirect, le pagine da non toccare, le priorità dei contenuti.

## 3. Analisi B — Lo stato tecnico del sito

- **Scansione completa:**
  - risposte (200, 3xx, 4xx);
  - catene di redirect;
  - pagine orfane e duplicate;
  - title, description, H1 e H2;
  - immagini senza testo alternativo o troppo pesanti;
  - PDF (indicizzati, titolo, aggiornamento);
  - link rotti, interni ed esterni.
- **Indicizzazione:**
  - copertura in Search Console;
  - sitemap e robots.txt;
  - forma canonica (www o no, slash finale), canonical;
  - pagine escluse e perché.
- **Prestazioni:** dati reali di Chrome se disponibili, Lighthouse da mobile su ogni modello di pagina, peso e numero di risorse, font, script di terze parti (chat, mappe, Yext).
- **Dato strutturato:**
  - cosa c'è oggi, e se viene da Yext;
  - errori nel Rich Results Test;
  - confronto con il grafo che proporremo: ente, sedi, servizi, medici, briciole.
- **Anteprime social:** `og:title`, `og:description`, `og:image` e dimensioni dell'immagine su ogni modello di pagina.
- **Accessibilità:** contrasto, ordine dei titoli, moduli, navigazione da tastiera, testi alternativi. Si controlla su un campione di pagine con strumenti automatici e una prova a mano.
- **Privacy e sicurezza:**
  - banner dei cookie e Consent Mode di GA4;
  - script caricati prima del consenso;
  - HTTPS e intestazioni di sicurezza;
  - moduli di contatto (informativa, destinatari).
- **Piattaforma:**
  - versione di MODX, PHP e componenti;
  - modelli e chunk;
  - quanto il codice è riusabile;
  - stato del cloud (backup, cloud di sviluppo).
  Da qui la decisione tra restyling dell'esistente e ricostruzione.
- **Crawler AI:** accessi nei log, blocchi nel robots.txt o nel firewall.

**Decisioni che alimenta:** l'elenco degli interventi tecnici, la scelta tra restyling e ricostruzione, i modelli di pagina.

## 4. Analisi C — Misurazione e conversioni

- **Inventario degli eventi GA4:**
  - clic sul telefono, sulla mail e sulle indicazioni;
  - clic sulla prenotazione TuoTempo;
  - invio dei moduli;
  - download dei PDF.

  Si verifica cosa è tracciato, cosa manca e cosa è contato due volte.
- **Prenotazioni online:** se il passaggio a TuoTempo conserva la sorgente (parametri, collegamento tra domini), e se si può contare la prenotazione completata e non solo il clic.
- **Chiamate:** se esiste un numero dedicato o un tracciamento delle chiamate. Senza, il telefono, che è il canale principale per un pubblico oltre i 60 anni, resta invisibile nei dati.
- **Consenso:** quale quota di utenti GA4 vede davvero, con il Consent Mode, e come stimare il resto.
- **Attribuzione:** come distinguere mail, campagne, ricerca organica, scheda Google e passaparola, ad esempio con i parametri UTM nelle mail di dicembre e nei QR code di volantini e locandine.

**Decisioni che alimenta:** il piano di misura del lancio, che va attivato prima della mail di metà dicembre.

## 5. Analisi D — La domanda di ricerca locale

- **Elenco degli esami e delle visite.** Si parte dall'offerta reale, più le novità: risonanza (neurologica, muscoloscheletrica, body), TAC, cone beam e OPT, RX, ecografie, ecocolordoppler, elettromiografie, nuove visite ambulatoriali.
- **Volumi di ricerca**, con Keyword Planner e Search Console, per ogni esame combinato con:
  - località (Modena, provincia, comuni vicini);
  - modalità (convenzionata, privata, senza impegnativa, costo, prezzo, tempi di attesa, aperto il sabato);
  - parti del corpo («risonanza ginocchio», «TAC addome»).
- **Intento:** ricerche informative (preparazione, durata, «fa male», differenza tra RM e TAC) e ricerche per prenotare. Le prime chiedono pagine che spiegano, le seconde pagine che portano alla prenotazione.
- **Stagionalità** con Google Trends e lo storico di Search Console.
- **Domande reali:** le domande frequenti che Google mostra nei risultati, le ricerche correlate e le domande ricorrenti al telefono, se la struttura le raccoglie.
- **Confronto con il piano marketing:** se gli esami indicati come prioritari corrispondono alla domanda reale.

**Decisioni che alimenta:** le pagine da creare e l'ordine, i titoli, le parole chiave delle campagne.

## 6. Analisi E — Chi sono gli utenti

- **Geografia:** comuni di provenienza da GA4 e, se disponibili, dalle prenotazioni. Disegnano il bacino reale, da confrontare con Modena città.
- **Età:** fasce d'età in GA4, se i segnali di Google sono attivi. Servono a verificare l'ipotesi di un'utenza oltre i 60 anni contro un'età media in città di 48.
- **Dispositivi e orari:** telefono o computer, e in quali orari si visita e si prenota. Orientano la grafica (pulsanti, leggibilità) e gli orari del telefono.
- **Percorsi:**
  - pagine di ingresso, pagine visitate prima di chiamare o prenotare;
  - uscite;
  - ricerche interne al sito, se c'è una barra di ricerca.
- **Nuovi utenti e utenti di ritorno:** pesano il patrimonio dei pazienti già noti, a cui il piano dedica la mail di dicembre.

**Decisioni che alimenta:** grafica e navigazione, il tono dei testi, i comuni per le pagine locali e le campagne.

## 7. Analisi F — Prenotazioni e offerta

- **Prenotazioni per esame e per canale** (online, telefono, sportello) negli ultimi 12 mesi: quali esami pesano e quanto l'online cresce.
- **Convenzione SSN e privato** per esame: dove la struttura è competitiva sul privato (prezzo, tempi) e dove l'utente arriva con l'impegnativa.
- **Tempi di attesa** per esame, da confrontare con quelli rilevati dal piano per i concorrenti (Hesperia 16 giorni e 160 € per la TAC addome; Affidea 1 giorno; Fogliani 2 giorni e 212 €).
- **Domanda contro offerta:** esami molto cercati online ma poco prenotabili online, e il contrario.
- **Listino e preparazioni:** quali informazioni esistono già (listino privato, fogli di preparazione) e possono diventare pagine.

**Decisioni che alimenta:** quali esami mettere in evidenza, se pubblicare prezzi e tempi, il collegamento corretto a TuoTempo per ogni pagina.

## 8. Analisi G — Identità e presenza locale

- **Scheda Google della Diagnostica:**
  - nome, categorie, orari, foto, attributi;
  - prenotazione collegata;
  - domande e risposte;
  - recensioni: numero, voto, andamento, temi ricorrenti, risposte.
- **Trasloco della scheda.** Come preservare storico e recensioni con il nuovo indirizzo (verifica delle regole di Google sullo spostamento). C'è anche la scelta da fare: una scheda o due, visto che una risonanza resta nella vecchia sede.
- **Distinzione dai due «Villa Rosa».** Si verifica come Google e gli assistenti AI distinguono la Diagnostica dalla clinica psichiatrica Villa Rosa:
  - ricerche comuni alle due schede di kosgroup.com;
  - pannello di Google;
  - immagini e recensioni che finiscono sulla scheda sbagliata.

  Con il nuovo nome «Diagnostica Villa Rosa» la separazione va resa netta: nomi, categorie, dato strutturato e link.
- **L'omonimo di Sassuolo.** Si verifica se il Centro Medico Kos compare nelle ricerche «kos modena» o «centro medico kos» e se ci sono confusioni nelle recensioni o negli assistenti AI.
- **Coerenza dei dati** (nome, indirizzo, telefono, orari) su:
  - sito, kosgroup.com e scheda Google;
  - Yext e, dal cambio, RetailTune;
  - portali sanitari (MioDottore, Doveecomemicuro, Paginegialle e simili), Apple e Bing.

  L'elenco delle schede da aggiornare il 7/1 nasce da qui.

**Decisioni che alimenta:** la strategia per la scheda Google, l'elenco delle directory da aggiornare, il nome nel dato strutturato e su kosgroup.com e RetailTune.

## 9. Analisi H — I concorrenti

- **Elenco.** Si parte dai tre del piano (Hesperia, Affidea Modena Medica, Ospedale Fogliani) e si allarga a chi compare davvero nei risultati per gli esami principali, sia nei risultati organici sia nella mappa.
- **Posizioni sulla mappa:** una griglia di rilevazione su Modena e sui comuni del bacino per 10-15 ricerche chiave (ad esempio «risonanza magnetica», «TAC», «ecografia», «cone beam»), con la posizione della Diagnostica e dei concorrenti punto per punto.
- **Posizioni organiche** per le stesse ricerche, con le pagine che si posizionano (pagina per esame o generica).
- **Siti dei concorrenti:**
  - struttura del sito e pagine per esame;
  - prezzi e tempi pubblicati;
  - prenotazione online;
  - preparazione agli esami e medici presentati;
  - velocità, dato strutturato, anteprime social.
- **Schede Google dei concorrenti:** recensioni (numero, voto, ritmo), categorie e foto.

**Decisioni che alimenta:** come posizionare la Diagnostica (tecnologia, tempi, convenzione, comfort), cosa pubblicare per distinguersi, gli obiettivi realistici di posizionamento.

## 10. Analisi I — Gli assistenti AI

- **Domande reali.** Si prepara un insieme di circa 30 domande da utenti di Modena:
  - «dove fare una risonanza magnetica a Modena»;
  - «TAC convenzionata Modena tempi»;
  - «risonanza aperta o chiusa Modena»;
  - «centro diagnostico Modena prenotazione online»;
  - e simili.
- **Assistenti:** le domande si pongono a ChatGPT, Gemini, Perplexity e alle AI Overviews di Google, in due rilevazioni (prima e dopo il lancio).
- **Cosa si registra:**
  - chi viene citato;
  - da quali fonti (siti, portali, recensioni);
  - quali informazioni sulla Diagnostica sono sbagliate o vecchie, come l'indirizzo, gli esami disponibili, la confusione con la psichiatria o con l'omonimo.

**Decisioni che alimenta:** i contenuti che gli assistenti devono poter leggere (testo nell'HTML, dato strutturato, risposte chiare), le fonti esterne da curare.

## 11. Analisi L — I contenuti

- **Inventario** delle pagine attuali: argomento, data di aggiornamento, lunghezza, chi firma o rivede, immagini, collegamento alla prenotazione.
- **Confronto con l'offerta reale:**
  - esami senza pagina;
  - pagine di esami non più offerti;
  - informazioni vecchie (indirizzo, orari, macchinari).
- **Qualità sanitaria:** per ogni esame, se ci sono le informazioni che servono all'utente e che Google premia nei temi di salute:
  - cos'è e a cosa serve;
  - preparazione e durata;
  - controindicazioni (pacemaker, gravidanza, claustrofobia);
  - convenzione o privato;
  - come prenotare e cosa portare;
  - quale medico referta.

  Si verifica anche se c'è un responsabile sanitario indicato e se i testi sono rivisti da un medico.
- **Pagine della sede:**
  - indirizzo e ingresso della nuova palazzina;
  - cosa resta nella vecchia sede;
  - parcheggi, mezzi pubblici, accessibilità;
  - foto degli spazi.
- **Medici e professionisti:** chi c'è oggi sul sito, e cosa servirebbe per pagine dei medici coerenti con il progetto KOS (`IndividualPhysician` con `practicesAt`).

**Decisioni che alimenta:** la nuova struttura del sito, i modelli di pagina, il piano editoriale e cosa chiedere alla struttura (testi, foto, revisione medica).

## 12. Analisi M — Il rapporto con kosgroup.com e RetailTune

- **Ricerche in cui compaiono tutte e due le pagine** (sito e scheda di kosgroup.com), e quale delle due vince.
- **Ruolo delle due pagine.** Proposta: il sito come riferimento locale completo, la scheda di kosgroup.com come presentazione nel gruppo con un rimando evidente, e il link «sito web» della scheda Google verso il sito.
- **Dato strutturato tra i due domini.**
  - Una sola entità per sede, con `@id` stabile, che i due siti richiamano.
  - `parentOrganization` verso `https://kosgroup.com/#organization`.
  - Niente `sameAs` tra entità diverse.
  - La nuova palazzina come sede con il proprio indirizzo.
- **RetailTune:** i due punti sulla mappa (vecchia e nuova sede), il nome nuovo e il link al sito o alla scheda, da allineare il giorno dell'apertura.

**Decisioni che alimenta:** il ruolo dei due siti, il dato strutturato, gli aggiornamenti coordinati del 7/1.

## 13. Analisi N — I vincoli

- **Pubblicità sanitaria.** Cosa si può pubblicare su prezzi, tempi, macchinari e confronti: da verificare con KOS prima di proporre pagine con prezzi o messaggi promozionali.
- **Privacy:**
  - informative dei moduli e consenso ai cookie;
  - uso dei contatti per la mail di dicembre. Il piano indica 3.626 indirizzi potenzialmente validi su 14.409, da verificare anche nei consensi.
- **Marchio KOS:** i limiti entro cui l'immagine grafica può essere diversa.
- **Accessibilità:** il livello da raggiungere.

## 14. La proposta finale

Sarà un documento per il referente marketing con:

1. **Lo stato dell'arte**, in sintesi, con i numeri di partenza (traffico, ricerche, posizioni, prenotazioni, recensioni, prestazioni del sito).
2. **Le opportunità**, ordinate per valore e sforzo.
3. **La nuova struttura del sito** e i modelli di pagina: esame, sede, medico, notizia, prenotazione.
4. **La specifica tecnica:** dato strutturato, anteprime social, prestazioni, accessibilità, misura.
5. **Il piano di migrazione:** redirect, scheda Google, directory, kosgroup.com e RetailTune.
6. **Il piano di misura**, con gli indicatori e il cruscotto prima e dopo il lancio.
7. **Tempi, fasi e costi**, separando ciò che serve per l'apertura del 7/1 da ciò che segue.

## Calendario

| Quando | Cosa |
| --- | --- |
| Da subito | Fase di raccolta: collegamenti a BigQuery, richiesta dei dati TuoTempo, delle prenotazioni e della scheda Google |
| Fine novembre | Analisi A, B e C: patrimonio, stato tecnico, misura. Le loro conclusioni servono comunque per il 7/1 |
| Inizio-metà dicembre | Analisi D-I: domanda, utenti, prenotazioni, presenza locale, concorrenti, assistenti AI |
| Metà dicembre | Tracciamento pronto per la mail ai contatti |
| Dicembre | Analisi L-N, proposta e interventi per l'apertura: pagine della nuova sede e delle novità, scheda Google, directory |
| 7/1/2027 | Apertura: aggiornamenti coordinati su sito, scheda Google, kosgroup.com e RetailTune |
| Gennaio-febbraio | Restyling completo secondo la proposta; misura del lancio e delle campagne; seconda rilevazione sugli assistenti AI |

Le giornate di lavoro si incastrano con lo sprint 4 del progetto (consegna 4/12, verifica 7-11/12, correzioni 14-18/12), che ha la precedenza.

## Da chiedere o decidere

Stato al 20/11:

1. **Scheda Google:** una sola, quella esistente, spostata al nuovo indirizzo. Niente scheda a parte per la risonanza che resta nella vecchia sede. Resta aperto, lato struttura, come indicare quell'ingresso: va detto nella pagina della sede e nella scheda.
2. **Nome nuovo su kosgroup.com e RetailTune:** da cambiare nello stesso giorno del sito.
3. **Prenotazioni:** Danilo consulta il backend di TuoTempo, web e telefono, con il massimo dettaglio. Nel repository entrano solo dati aggregati per esame, canale, mese ed eventualmente comune, senza nomi né codici fiscali.
4. **Prezzi e tempi di attesa:** da chiedere alla struttura, insieme ai vincoli di KOS sulla pubblicità sanitaria.
5. **Testi e foto:** disponibili dopo la metà di dicembre. Le pagine per il 7/1 si preparano prima, con modelli e contenuti provvisori, e si completano appena arrivano.
6. **Numero dedicato al sito:** difficile, Danilo chiede comunque. In alternativa si misurano i clic sul numero (già possibile con GA4) e si confrontano con le prenotazioni telefoniche di TuoTempo.
