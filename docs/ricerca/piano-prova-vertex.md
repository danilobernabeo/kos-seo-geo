# Sostituzione della ricerca Yext: piano della prova Vertex AI Search

Uso interno (linea C). Date della prova concordate con il referente KOS il 12/11. Approvazione di KOS alla prova ricevuta
il 16/11.

**Piano dal 16/11 (da confermare con il referente: data di messa online e fatturazione sul 2026):** sviluppo e collaudo
di KOS sullo stage entro il 18/12 (lavoro fatturabile sul 2026); messa online a metà gennaio insieme allo store locator
RetailTune; fino ad allora resta attiva la barra Yext (servizio Yext fino a fine gennaio).

## Date comunicate a KOS

| Data | Tappa |
|---|---|
| 16/11 | Avvio: motore attivato sul progetto Google Cloud di KOS |
| 16-20/11 | Preparazione: indicizzazione dei due siti, set di 300 ricerche reali ripulite dai dati personali |
| 18/11 | Scheda tecnica sul trattamento dei dati al DPO (parere entro il 25/11) |
| dal 23/11 | Test di KOS sulla pagina di prova riservata sullo stage, già nella forma definitiva |
| 26/11 | Restituzione: risultati del confronto con Yext e preventivo per la messa online |
| 26-27/11 | Valutazione di KOS |
| 30/11-11/12 | Sviluppo (se l'esito è positivo) |
| 14-15/12 | Collaudo di KOS sullo stage |
| entro il 18/12 | Messa online (interno: 16/12); la barra Yext esce dai due siti. **Dal 16/11, da confermare:** entro il 18/12 solo sviluppo e collaudo sullo stage; messa online a metà gennaio con lo store locator |

Passaggi critici per KOS: parere del DPO entro il 25/11, decisione entro il 27/11, collaudo il 14-15/12.

Scadenza interna (16/11): se mercoledì 18/11 la prova non riesce ancora a chiamare Vertex (domini dell'area di lavoro
bloccati), lo si dice a KOS insieme all'invio della scheda al DPO.

## Calendario interno

- Da venerdì 13/11, dopo l'invio della consegna dello sprint 3 (15:30-16:00): progetto Google Cloud, indicizzazione, set di
  ricerche, servizio intermedio; pagina di prova pronta verso il 19/11.
- 16-17/11: scheda per il DPO e verifiche sulla documentazione Google (funzioni disponibili in regione UE, conservazione
  delle ricerche, uso dei dati per l'addestramento, sub-responsabili).
- Settimana del 23/11: integrazione definitiva nei due siti, solo sullo stage, prima dell'approvazione (riduce lo sviluppo
  dopo il 27/11; in caso di esito negativo si perdono solo giorni di lavoro nostri).

## Scelte per la scheda del DPO (12/11, integrate il 16/11)

- Ricerche della prova: «tutte le ricerche distinte dei 12 mesi, ripulite dai dati personali» (non «300 ricerche»).
- Nella prova anche le ricerche di kosgroup.com passano dalla generazione delle risposte AI (storico già ripulito,
  nessuna risposta pubblicata).
- Regola per le domande di crisi: riquadro fisso con il 112 e i numeri di emergenza sopra i risultati, nessuna risposta
  AI (kosgroup.com 7 ricerche in 12 mesi, anniazzurri.it nessuna).

- Progetto Google Cloud di KOS, Cloud Data Processing Addendum sull'account di fatturazione (da verificare).
- Indice e servizio in regione UE; servizio intermedio su Cloud Run in UE: il browser non chiama Google direttamente.
- Il servizio intermedio toglie email, telefoni, codici fiscali, età e date prima di inoltrare la ricerca; il contenuto
  sanitario resta (è la ricerca stessa).
- Log del servizio intermedio senza il testo delle ricerche; statistiche di ricerca di Vertex conservate 30 giorni.
- GA4: evento di ricerca con il numero di risultati, senza il testo.
- «Vicino a me»: coordinate dal browser con consenso, solo per ordinare i risultati, non salvate; niente posizione da IP.
- Campionamento di input e risposte del modello da parte di Google non consentito (scelta fatta all'attivazione del
  servizio sul progetto `kos-ricerca`, 16/11).
- Risposte AI: solo su anniazzurri.it e solo per domande pratiche, con il filtro su sintomi, diagnosi, farmaci e terapie
  (decisione del 9/11); nessun addestramento sui dati.

## Dopo la risposta del referente (12/11)

- Store locator RetailTune a metà gennaio, dismissione di Yext a fine gennaio (referente KOS, 12/11): niente
  sovrapposizione con la ricerca; la richiesta a RetailTune di anticipare al 3-4/12 non serve più.
- Entro il 18/12: barra Yext tolta dai due siti; il servizio Yext resta attivo fino a fine gennaio come rete di sicurezza
  (codice della barra conservato, ripristino in pochi minuti). Prima: export dello storico delle ricerche da Yext.
  Da sistemare: `/it/risultati` di kosgroup.com reindirizzata alla nuova pagina dei risultati, spazio di 58 px riservato
  alla barra Yext nella home di anniazzurri.it (AA-4).
- Superato il 16/11 (vedi «Ricerche geografiche»). Ricerche geografiche: oggi la mappa con i filtri è dentro i risultati di Yext Answers e sparisce con la barra. La nuova
  ricerca mostra l'elenco delle strutture per distanza (coordinate delle schede, comune nella frase o posizione del
  browser) e una mappa con i pin, ripresa dal componente Google Maps delle pagine regione e provincia di anniazzurri.it
  (chiavi sotto il controllo di Danilo). Da metà gennaio, nelle ricerche geografiche il widget RetailTune al posto della
  mappa nostra, dentro la pagina dei risultati, con i filtri preimpostati dalla ricerca (comune, provincia, tipologia) e
  caricato solo per quelle ricerche; da confermare con RetailTune (domanda 19b della call del 13/11).

## Indicatori e campione della prova (16/11)

Le ricerche vengono da tutte le ricerche distinte dei 12 mesi dei due siti (export Yext: kosgroup.com 2.822 termini,
anniazzurri.it 325), ripulite dai dati personali, lanciate su Vertex e sulla ricerca Yext di oggi. Con 300 ricerche
divise tra due siti il margine d'errore sarebbe di circa ±8 punti per sito, troppo con una soglia di +15: il campione si
allarga su tre livelli, senza cambiare il calendario.

1. **Controlli automatici su tutte le ricerche distinte dei 12 mesi**: ricerche senza risultati; tempo di risposta; nome
   di struttura che porta alla sua scheda; riconoscimento dei luoghi, con i falsi allarmi sui nomi di struttura. I casi
   rari trovati (refusi, frazioni, nomi storici dei marchi) si riportano a parte.
2. **Giudizio di pertinenza su circa 400 ricerche per sito** (risultato atteso tra i primi tre): le più frequenti, più un
   campione stratificato tra frasi lunghe, refusi e luoghi. Giudizio assistito dall'AI, ricontrollato a mano a campione.
   La soglia di +15 punti si misura su questo livello.
3. **Risposte AI su tutte le ricerche da 4 parole in su dei due siti** (kosgroup.com 417 termini, 3.488 ricerche nei 12
   mesi; anniazzurri.it 91 termini, 155 ricerche), non solo su quelle del campione.

Ricerca Yext di oggi: API Content Delivery (`prod-cdn-cached.us.yextapis.com`), limite pubblicato di 100.000 richieste
all'ora; circa una ricerca al secondo, parametro `source` dedicato per riconoscere le ricerche del confronto nelle
statistiche Yext. La documentazione dell'API di ricerca dice che il traffico che non passa dalla libreria di Yext è
conteggiato sulla capacità di ricerca del contratto: le circa 3.150 ricerche del confronto valgono circa un mese e mezzo
di traffico reale. Prima di lanciarle va verificato con KOS che la capacità del contratto Yext le assorba.

### Risposte AI (16/11)

- **anniazzurri.it**: risposte su domande pratiche da un elenco chiuso di pagine (decisione del 9/11).
- **kosgroup.com, solo misurate**: stesso filtro, nessuna risposta visibile a KOS nella pagina di prova; decide KOS sui
  numeri del 26/11. La decisione del 9/11 (spente in produzione su kosgroup.com) non cambia. Nella restituzione due gruppi
  separati: tutte le ricerche e quelle da 4 parole in su (13,6% delle ricerche), il secondo è quello che decide.
- **Elenco chiuso di kosgroup.com**, leggero (mezza giornata al massimo): lavora con noi, ritiro referti, prenotazioni,
  convenzioni, contatti generali. Fuori le pagine con orari, che oggi non coincidono con le carte dei servizi in 49
  strutture (correzione in attesa di conferma di KOS, `docs/orari/orari-strutture-kos-confronto.md`), e le pagine dei
  marchi dismessi (Neomesia, Santo Stefano Riabilitazione, Polo Diagnostico, in attesa di conferma di KOS); rientrano
  quando sono allineate.
- **Domande di crisi, su entrambi i siti**: per ricerche come «pensieri suicidi», «autolesionismo» o «crisi» il filtro non
  si limita a bloccare la risposta AI: mostra un riquadro fisso sopra i risultati con il 112 e i numeri di emergenza. Nei
  12 mesi: kosgroup.com 1 termine, «autolesionismo», 7 ricerche; anniazzurri.it nessuna (conteggio su suicidio,
  autolesionismo, crisi, emergenza, aggressione, violenza, maltrattamento e simili).
- **Restituzione**: numeri per sito (risposte utili, bloccate dal filtro, a rischio) e 10-15 esempi veri per sito,
  soprattutto quelli a rischio.

Soglia del «migliora abbastanza» (decisa il 16/11, fissata prima di vedere i risultati e riportata nella restituzione del
26/11). Vertex supera la prova se valgono tutte:

1. risultato atteso tra i primi tre, pesato per frequenza: +15 punti percentuali rispetto a Yext, oppure almeno metà del
   divario dal 100% recuperata (se Yext parte già alto);
2. meno ricerche senza risultati su entrambi i siti;
3. nessun peggioramento sulle ricerche per nome di struttura;
4. anniazzurri.it (circa 53 ricerche al mese) valutato anche sul campione non pesato, con le domande lunghe in evidenza.

Se la soglia non è raggiunta si passa al piano B, Site Search 360.

## Ricerche geografiche (16/11)

La distanza non la calcola Vertex: la pagina dei risultati riconosce il luogo (tabella comuni, province, regioni con
coordinate) ed eventualmente il servizio, e mostra in testa le strutture vicine; sotto, i risultati di Vertex.

- **Nessuna fase intermedia (16/11):** con la messa online a metà gennaio il widget RetailTune c'è dal primo giorno, già
  filtrato su coordinate e raggio (ed eventuale servizio), caricato solo per le ricerche con un luogo. Parametri,
  comportamento senza strutture nel raggio e caricamento su richiesta da confermare con RetailTune.
- **Elenco testuale per distanza sempre presente**, sotto il widget o al suo posto senza consenso: il widget è in categoria
  Cookiebot «marketing» (vale anche per i motori AI). Ogni struttura ha la distanza e il link «Indicazioni» al suo profilo
  Google Maps.
- **Regola di riserva:** se il widget RetailTune non è pronto, la ricerca va online lo stesso con il solo elenco e il widget
  si aggiunge dopo; la data non dipende da RetailTune (tra metà e fine gennaio circa due settimane di margine prima della
  dismissione di Yext).
- **Mappa con i pin: non si fa.** Se il referente confermasse la messa online al 18/12, si salta comunque (elenco con
  distanza e «Indicazioni»); la mappa diventa un'opzione separata nel preventivo del 26/11.
- Il nome della struttura vince sul luogo: se la ricerca corrisponde al nome di una struttura (dalle TV) si mostra la
  scheda, l'elenco o il widget solo come aggiunta (Casa Pero, Casa Sanremo, Santo Stefano, Arco).
- Zone senza strutture: raggio che si allarga fino ad almeno una struttura, con la frase «La struttura più vicina a … è a …».

## Configurazione di Google Cloud

- Progetto sull'account di fatturazione KOS; Discovery Engine API; indice in regione UE con indicizzazione avanzata, che
  richiede i due domini verificati in Search Console dall'account che crea gli archivi.
- Limite alle ricerche: la quota delle ricerche è un limite di sistema non modificabile (UE 300/min), quindi il tetto
  giornaliero sta nel servizio intermedio (istanze massime di Cloud Run e contatore giornaliero); avviso di budget a 20 € al mese, «solo avvisi» (il limite di spesa
  applicato di Google Cloud non copre la Discovery Engine API, verifica del 16/11).
  Quote ridotte il 16/11 (verificate): Search lite e Regional Search lite a 0 (accesso con chiave API non usato), completamento delle query (semplice, avanzato e regionale) a 60/min, risposte
  AI lasciate a 10/min.
- Service account con chiave JSON: la usa solo il servizio intermedio (Cloud Run, in un segreto del servizio), mai nel
  codice del sito né nel repository; durante la prova anche gli script di misura, con la chiave fuori dal repository.

## Stato della configurazione (16/11)

- Archivi dati in regione `eu`, indicizzazione avanzata, domini verificati, aggiornamento solo da sitemap: `kosgroup-it`
  (`kosgroup.com/it/*`, escluso `/it/risultati*`) e `anniazzurri` (`anniazzurri.it/*`, escluso `/cerca*`). Sitemap dei
  siti inviate (kosgroup.com 847 pagine italiane, anniazzurri.it 475); la sitemap dei PDF esclusi di anniazzurri.it non va
  mai inviata.
- App `ricerca-kosgroup` e `ricerca-anniazzurri`, edizione Enterprise con funzioni LLM.
- Le sitemap non contengono PDF: per ora la ricerca trova solo pagine. Da decidere se aggiungere le carte dei servizi.

## Da chiarire con KOS

- Contratto Yext: data di scadenza e se si può disdire per singolo servizio (ricerca Answers separata da profili e
  store locator) o solo per intero. Nella mail del 12/11: «togliere la ricerca dal contratto Yext».
