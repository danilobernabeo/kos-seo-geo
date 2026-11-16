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
- Testi originali (export Yext, set ripulito, riscritture, elenco dei nomi): sul PC di Danilo in
  `C:\Users\danil\progetti\kos-seo-geo\kos-ricerca-dati`, fuori dal repository, accesso solo di Danilo; copia temporanea
  nell'area di lavoro durante il lavoro; cancellazione il 27/11 salvo decisione diversa. Chiave del service account in una
  cartella separata (`kos-ricerca-chiavi`), revocata a fine prova.
- Alternativa, se il DPO preferisce che i testi restino su infrastruttura KOS: bucket Cloud Storage in regione UE nel
  progetto Google Cloud `kos-ricerca`, con accesso limitato a Danilo e al service account della prova, stessa cancellazione.
- Nomi di persona: 113 esclusi (kosgroup.com 110, 0,68% delle ricerche; anniazzurri.it 3, 1,3%), nessuno corrisponde
  alle pagine medico. Da dire nella scheda: nella prima tornata su Vertex (16/11) c'erano ancora 76 di questi nomi e 5
  ricerche con codici di accesso ai referti dentro la frase, esclusi lo stesso giorno (regola dei codici estesa alle
  singole parole); su Yext non è partito nulla.
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
2. **Giudizio di pertinenza** (risultato atteso tra i primi tre): su kosgroup.com circa 400 ricerche, le più frequenti
   più un campione stratificato tra frasi lunghe, refusi e luoghi; su anniazzurri.it **tutte le 320 ricerche ripulite**,
   non un campione. Giudizio assistito dall'AI, ricontrollato a mano a campione.
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
- Dal 16/11 pomeriggio: archivio `anniazzurri-schede` con le sole 64 schede delle strutture di anniazzurri.it (57
  residenze, 5 centri diurni, 2 hospice; inclusione per indirizzo esatto, niente news, pagine istituzionali né PDF) e app
  di prova `ricerca-kosgroup-schede-aa` (archivi `kosgroup-it` e `anniazzurri-schede`), per la terza forma
  dell'indicatore; l'app `ricerca-kosgroup` resta com'è (Vertex da solo). Landing `kosgroup.com/villamargherita`
  aggiunta all'archivio `kosgroup-it` (indirizzo esatto: è fuori da `/it/` e non è ancora nella sitemap). Indicizzazione
  in corso.
- Le sitemap non contengono PDF: per la prova i PDF sono caricati a mano (sezione «PDF nella ricerca»).

## Pulizia delle ricerche (16/11)

Script `scripts/ricerca/pulisci-ricerche.py`, da lanciare prima di ogni invio a Vertex o a Yext. Regole:

1. **escluse**: email, codici fiscali, telefoni, date, codici alfanumerici (parole di 5 caratteri o più con lettere e
   almeno due cifre, anche dentro la frase: codici di accesso ai referti, di prenotazione o di pratica), nomi di persona
   che non corrispondono a una pagina pubblica dei siti;
2. **tolte dal testo**: età («83 anni», «classe 1936»);
3. **riscritte a mano**: frasi con nomi, indirizzi o dettagli di vita privata, tenendo il bisogno, i refusi e la città.

**Nomi di persona.** 113 nomi di persona esclusi (kosgroup.com 110, 0,68% delle ricerche; anniazzurri.it 3, 1,3%),
nessuno corrisponde alle pagine medico. Il controllo del 16/11 pomeriggio (cognomi cercati nelle pagine pubbliche
trovate da Vertex) ha aggiunto 76 nomi ai 37 iniziali; 15 cognomi di medici con pagina o citati in pagine pubbliche
restano. La prima tornata su Vertex li conteneva ancora: vedi la scheda del DPO.

**Dove stanno i testi originali** (export Yext, set ripulito, riscritture, elenco dei nomi): sul PC di Danilo, in
`C:\Users\danil\progetti\kos-seo-geo\kos-ricerca-dati`, fuori dal repository, con accesso solo di Danilo; durante il lavoro una
copia temporanea nell'area di lavoro della prova, che non è permanente. Cancellazione alla fine della prova (27/11) salvo
decisione diversa. La chiave del service account sta in una cartella separata, `kos-ricerca-chiavi`, da revocare a fine
prova. Mai in git: nel repository solo regole e conteggi.

| Sito | Termini nell'export | Esclusi | Riscritti | Ricerche distinte ripulite |
|---|---|---|---|---|
| kosgroup.com | 2.822 | 141 (110 nomi di persona, 29 codici, 1 email, 1 codice fiscale) | 2 | 2.681 |
| anniazzurri.it | 325 | 5 (3 nomi di persona, 2 frasi con indirizzi privati) | 8 | 320 |

## Script di misura (16/11)

`scripts/ricerca/misura.py`, sulle ricerche ripulite:

- `vertex`: primi 10 risultati e tempo di risposta di ogni ricerca (correzione ortografica ed espansione automatiche);
- `yext`: stesse ricerche sull'API di Yext, una al secondo, `source=CONFRONTO_VERTEX`; come risultati si contano i primi 3
  di ogni categoria nell'ordine della pagina universale, cioè quello che l'utente vede;
- `livello1`: senza risultati, tempo mediano e al 95° percentile, nome di struttura → scheda tra i primi tre (pesato per
  frequenza), luoghi riconosciuti e nomi di struttura che contengono un luogo.

Riconoscimento delle strutture (le 58 strutture kosgroup.com dell'export delle TV del 6/11, più Poliambulatorio
Villalba, Santo Stefano Casorate e Santo Stefano Mede, con i dati della struttura dal 16/11 notte, e Villa Margherita
Vicenza, la cui struttura è la landing `/villamargherita` con le due schede delle unità come reparti: vale una qualunque
delle tre; e le 57 residenze, perché la ricerca di kosgroup.com trova anche le residenze): tutti i token distintivi del nome nella ricerca, con tolleranza sui refusi; la città della struttura non serve
quando il nome ha altre parole, ma decide se c'è («villa dei pini» vale per Firenze e Civitanova, «villa dei pini
civitanova» solo per Civitanova); quando più schede rispondono allo stesso nome, ognuna conta come risultato atteso
(«villalba»: Clinica Dentale e Punto Prelievi Salus Villalba). Il nome non vale se è solo una città con più strutture
(es. Polo Geriatrico Riabilitativo Milano) o se sta dentro il nome di un comune («borgo san lorenzo» non è la Residenza San
Lorenzo). Riconoscimento dei luoghi: comuni, province e regioni italiane, esclusi i comuni che sono anche parole comuni
nelle ricerche («salve», «mese», «parenti», «posta»...). Gli esiti (ricerche e risultati) stanno con i dati della prova,
fuori dal repository.

**Livello 1, primi numeri di Vertex (16/11, provvisori: manca il confronto con Yext, che si lancia per ultimo).**

| Sito | Ricerche distinte (pesate) | Senza risultati | Tempo mediano / 95° percentile | Nome di struttura → scheda tra i primi tre |
|---|---|---|---|---|
| kosgroup.com | 2.681 (25.531) | 152 termini, 1,2% delle ricerche | 335 / 429 ms | 81,4% (348 termini, 5.638 ricerche) |
| anniazzurri.it | 320 (625) | 8 termini, 2,2% | 371 / 536 ms | 86,4% (12 termini, 22 ricerche) |

Luoghi riconosciuti: kosgroup.com 655 termini (6.489 ricerche), anniazzurri.it 39 (72); nomi di struttura che contengono
anche un luogo: 160 e 4 (lì vince il nome).

Su kosgroup.com l'81,4% si divide in due: **schede kosgroup.com 99,6%** (4.609 ricerche) e **residenze Anni Azzurri 0%**
(1.029 ricerche, es. «residenza anni azzurri volpiano»): l'app `ricerca-kosgroup` indicizza solo kosgroup.com.
**Decisione del 16/11:** riconoscimento dei nomi dalle TV con la scheda in testa (per le residenze, link alla scheda su
anniazzurri.it) **più** un archivio con le sole schede delle strutture di anniazzurri.it collegato all'app di
kosgroup.com, per coprire anche ricerche come «rsa volpiano» o «casa di riposo torino» che il riconoscimento dei nomi non
vede. Nella restituzione l'indicatore in tre forme: Vertex da solo, con il riconoscimento, con il riconoscimento più
l'archivio delle schede AA (`livello1` e `livello2` le calcolano quando ci sono gli esiti di `vertex --variante
schede-aa`).

## Livello 2: risultati attesi (16/11)

Script `scripts/ricerca/livello2.py` (`campione`, `misura`). Campione: kosgroup.com 400 ricerche (le 250 più frequenti,
77% delle ricerche dei 12 mesi, più 60 frasi da 4 parole in su, 45 con refusi e 45 con un luogo, estratte con seme fisso);
anniazzurri.it tutte le 320.

Risultati attesi:

- **nome di struttura**: la scheda (o le schede omonime), come nel livello 1;
- **luogo** (solo ricerche per luogo pure, es. «rsa a milano»): le schede delle strutture della stessa provincia o
  regione e, su anniazzurri.it, la pagina «RSA in provincia di …»; senza strutture, l'elenco delle strutture;
- **tutte le altre**: giudizio assistito dall'AI, ricontrollato a mano sui casi mancati. Criteri: prestazione o
  specialità → la sua pagina di servizio; professione → pagine «Lavorare in KOS» (più la pagina di servizio quando la
  parola indica anche la prestazione, es. fisioterapista); referti, accesso, registrazione → ritiro referti / prenota
  online; moduli e domande d'ingresso (AA) → documentazione e moduli; orari di visita, reclami, camera mortuaria (AA) →
  carte dei servizi; domande lunghe delle famiglie → la pagina del servizio che risponde al bisogno; coda lunga senza
  pagina propria → pagina che contiene il termine. Ricerche senza una pagina giusta sul sito (strutture di altri gruppi,
  testi incomprensibili) escono dal calcolo: kosgroup.com 22, anniazzurri.it 37.

I giudizi contengono i testi delle ricerche: stanno con i dati della prova, fuori dal repository.

**Primi numeri di Vertex (16/11, provvisori)**, risultato atteso tra i primi tre:

| Sito | Pesato | Non pesato | Da 4 parole in su (pesato) | Struttura / luogo / altro (pesato) |
|---|---|---|---|---|
| kosgroup.com, Vertex da solo | 82,2% | 66,7% | 73,0% | 85,1 / 71,0 / 86,4 |
| kosgroup.com, con riconoscimento | 85,8% | 77,0% | 99,5% | 100 / 71,0 / 86,4 |
| anniazzurri.it, Vertex da solo | 85,4% | 80,6% | 86,1% | 86,4 / 74,5 / 86,6 |
| anniazzurri.it, con riconoscimento | 85,9% | 80,9% | 88,2% | 100 / 74,5 / 86,6 |

La categoria «luogo» misura solo l'elenco dei risultati: l'elenco delle strutture vicine della pagina dei risultati si
valuta a parte (riconoscimento dei luoghi, livello 1). Su kosgroup.com Vertex manca soprattutto le professioni (mette
in testa gli approfondimenti «infermiere», «oss» invece delle posizioni aperte) e la ricerca «servizi» (carte dei
servizi in PDF): correggibili con controlli di posizionamento.

## PDF nella ricerca (16/11)

Carte dei servizi e pieghevoli commerciali sono indicizzati anche nella prova. **Regola: un PDF entra se è indicizzabile
(nessun `X-Robots-Tag: noindex`) e collegato da almeno una pagina pubblicata; i PDF orfani escono.** Pagine pubblicate =
pagine delle sitemap dei siti (anniazzurri.it 475, kosgroup.com 866), lette il 16/11. Elenco in
`docs/ricerca/pdf-prova-2026-11-16.csv`, ricavato dalla regola e non scritto a mano.

- **anniazzurri.it**: 321 PDF collegati da `/assets/`; 192 nella ricerca, 127 in `noindex`, 2 link rotti (404) sulla pagina
  `/documentazione` (questionari di ammissione ASL-MB, già noti). Rispetto al primo caricamento della mattina: usciti 2 PDF
  orfani (`anniazzurri-a-casa-servizio-di-assistenza-domiciliare.pdf`, `cure-domiciliari-anniazzurri.pdf`), esclusi
  dall'archivio con una regola di esclusione; entrati `Organizzazione.pdf` di Santa Maria in Chienti e il pieghevole
  Anni Azzurri Senior Living; ricaricati i 5 moduli di richiesta ricovero ricostruiti il 16/11 (stesso indirizzo, titolo
  nuovo).
- **kosgroup.com**: carte di `kosgroup/dati/orari/carte-dei-servizi.txt` (53, senza la carta Fisiosport del CAR Macerata,
  unità chiusa, PDF tolto il 10/11) più pieghevoli, leaflet e brochure collegati: 78, tutti indicizzabili e collegati.
- Il pieghevole Anni Azzurri Senior Living e il pieghevole informativo dell'Hospice Beati Coniugi Martin sono
  indicizzabili per la regola web (carte, guide e pieghevoli per nome file, più una riga per Senior Living): restano nella
  ricerca. La nota dello sprint 2 che li dava in `noindex` è stata corretta (commit 777afe6).
- **Senza testo** (scansioni): kosgroup.com 5 carte dei punti prelievi (Biemme, Delta, Gamma, Salus Villalba, Selemar),
  da segnalare a KOS proponendo l'OCR da parte nostra (stesso documento con testo leggibile, come per le guide del CAR
  Macerata il 10/11), solo con il loro consenso; anniazzurri.it 3 moduli di ricovero e l'infografica Alzheimer, che restano
  come sono. **Senza titolo**: nessuno.
- **Caricamento nella prova**: manuale (`recrawlUris`); sull'archivio `kosgroup-it` aggiunto il percorso
  `kosgroup.com/assets/doc/*`, perché l'archivio includeva solo `/it/*`; PDF usciti esclusi con regole di esclusione
  esatte.
- **Nei risultati i PDF non superano le pagine**: per il nome di una struttura vince la scheda, la carta compare sotto con
  l'etichetta PDF e il nome della struttura. Nella prova si contano le ricerche che trovano il risultato giusto solo grazie
  a un PDF.
- **Risposte AI**: le carte di anniazzurri.it entrano nell'elenco chiuso; quelle di kosgroup.com no, finché gli orari delle
  pagine non sono allineati alle carte (`docs/orari/orari-strutture-kos-confronto.md`).

## Sviluppo dopo la decisione (appunti)

- Sitemap dei PDF indicizzabili e collegati per sito, generata dalla stessa regola, inviata a Vertex: l'indice si aggiorna
  da solo quando una carta viene sostituita o un PDF resta orfano (nella prova il caricamento è manuale).

## Ordine delle ricerche della prova

Lo script lancia prima le ricerche su Vertex e per ultime quelle su Yext, in attesa che KOS confermi la capacità di ricerca
del contratto Yext (richiesta di Danilo del 16/11).

## Da chiarire con KOS

- Contratto Yext: data di scadenza e se si può disdire per singolo servizio (ricerca Answers separata da profili e
  store locator) o solo per intero. Nella mail del 12/11: «togliere la ricerca dal contratto Yext».
