# Sostituzione della ricerca Yext: piano della prova Vertex AI Search

Uso interno (linea C). Date concordate con il referente KOS il 12/11; obiettivo: messa online entro il 18/12 (budget 2026).
Approvazione di KOS alla prova ricevuta il 16/11.

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
| entro il 18/12 | Messa online (interno: 16/12); la barra Yext esce dai due siti |

Passaggi critici per KOS: parere del DPO entro il 25/11, decisione entro il 27/11, collaudo il 14-15/12.

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
- Risposte AI: solo su anniazzurri.it e solo per domande pratiche, con il filtro su sintomi, diagnosi, farmaci e terapie
  (decisione del 9/11); nessun addestramento sui dati.

## Dopo la risposta del referente (12/11)

- Store locator RetailTune a metà gennaio, dismissione di Yext a fine gennaio (referente KOS, 12/11): niente
  sovrapposizione con la ricerca; la richiesta a RetailTune di anticipare al 3-4/12 non serve più.
- Entro il 18/12: barra Yext tolta dai due siti; il servizio Yext resta attivo fino a fine gennaio come rete di sicurezza
  (codice della barra conservato, ripristino in pochi minuti). Prima: export dello storico delle ricerche da Yext.
  Da sistemare: `/it/risultati` di kosgroup.com reindirizzata alla nuova pagina dei risultati, spazio di 58 px riservato
  alla barra Yext nella home di anniazzurri.it (AA-4).
- Ricerche geografiche: oggi la mappa con i filtri è dentro i risultati di Yext Answers e sparisce con la barra. La nuova
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

Soglia del «migliora abbastanza» (proposta del 16/11, da confermare): risultato atteso tra i primi tre in almeno 15 punti
percentuali di ricerche in più rispetto a Yext, pesate per frequenza, e meno ricerche senza risultati su entrambi i siti.
Se Vertex non la raggiunge, si ripete la misura con Site Search 360.

## Ricerche geografiche (16/11)

La distanza non la calcola Vertex: la pagina dei risultati riconosce il luogo (tabella comuni, province, regioni con
coordinate) ed eventualmente il servizio, e mostra in testa le strutture vicine; sotto, i risultati di Vertex.

- **Fase intermedia, dal 18/12 a metà gennaio:** elenco testuale delle strutture per distanza (coordinate delle schede) e
  mappa con i pin dal componente Google Maps delle pagine regione e provincia di anniazzurri.it. Da decidere se la mappa
  resta o si salta (resta solo l'elenco).
- **Da metà gennaio:** widget RetailTune al posto della mappa, già filtrato su coordinate e raggio (ed eventuale servizio),
  caricato solo per le ricerche con un luogo. Parametri, comportamento senza strutture nel raggio e caricamento su
  richiesta da confermare con RetailTune.
- In entrambe le fasi l'elenco testuale resta sempre: il widget RetailTune è in categoria Cookiebot «marketing» e senza
  consenso non compare (vale anche per i motori AI).
- Il nome della struttura vince sul luogo: se la ricerca corrisponde al nome di una struttura (dalle TV) si mostra la
  scheda, l'elenco o il widget solo come aggiunta (Casa Pero, Casa Sanremo, Santo Stefano, Arco).
- Zone senza strutture: raggio che si allarga fino ad almeno una struttura, con la frase «La struttura più vicina a … è a …».

## Configurazione di Google Cloud

- Progetto sull'account di fatturazione KOS; Discovery Engine API; indice in regione UE con indicizzazione avanzata, che
  richiede i due domini verificati in Search Console dall'account che crea gli archivi.
- Limite giornaliero alle ricerche e avviso di budget.
- Service account con chiave JSON: la usa solo il servizio intermedio (Cloud Run, in un segreto del servizio), mai nel
  codice del sito né nel repository; durante la prova anche gli script di misura, con la chiave fuori dal repository.

## Da chiarire con KOS

- Contratto Yext: data di scadenza e se si può disdire per singolo servizio (ricerca Answers separata da profili e
  store locator) o solo per intero. Nella mail del 12/11: «togliere la ricerca dal contratto Yext».
