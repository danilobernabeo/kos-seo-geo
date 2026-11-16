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

## Scelte per la scheda del DPO (12/11)

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

## Indicatori della prova

Su circa 300 ricerche reali ripulite dai dati personali, lanciate su Vertex e sulla ricerca Yext di oggi, ciascuna pesata
per quante volte è stata fatta:

1. ricerche senza risultati;
2. risultato atteso tra i primi tre;
3. tempo di risposta;
4. riconoscimento dei luoghi (aggiunto il 16/11): luoghi riconosciuti correttamente e falsi allarmi sui nomi di struttura.

Risposte AI (solo anniazzurri.it): attingono a un elenco chiuso di pagine pratiche; la prova misura anche l'effetto dei
filtri sulle domande cliniche.

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
  giornaliero sta nel servizio intermedio (istanze massime di Cloud Run e contatore giornaliero); avviso di budget a 20 €.
  Quote ridotte il 16/11: Search lite a 0 (accesso con chiave API non usato), completamento delle query a 60/min, risposte
  AI lasciate a 10/min.
- Service account con chiave JSON: la usa solo il servizio intermedio (Cloud Run, in un segreto del servizio), mai nel
  codice del sito né nel repository; durante la prova anche gli script di misura, con la chiave fuori dal repository.

## Da chiarire con KOS

- Contratto Yext: data di scadenza e se si può disdire per singolo servizio (ricerca Answers separata da profili e
  store locator) o solo per intero. Nella mail del 12/11: «togliere la ricerca dal contratto Yext».
