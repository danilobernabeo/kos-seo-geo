# Nuova ricerca interna dei siti: scheda tecnica per la valutazione privacy

Gruppo KOS, kosgroup.com e anniazzurri.it. 18/11/2026, Danilo Bernabeo. Parere richiesto entro il 25/11/2026.

## 1. Scopo

La ricerca interna dei due siti, oggi fornita da Yext, va sostituita entro fine gennaio 2027. La soluzione proposta usa
Google Vertex AI Search nel progetto Google Cloud di KOS. Questa scheda descrive il sistema che andrebbe in produzione,
i dati che tratta e le misure previste, più un breve resoconto della fase di test appena conclusa. La decisione sulla
soluzione è prevista per il 27/11/2026.

## 2. Come funziona

```
Browser dell'utente ──► Sito (MODX Cloud) ──► Servizio intermedio ──► Vertex AI Search
   testo della ricerca      pagina risultati      Cloud Run, regione UE      indice e ricerca, regione UE
                                                   toglie i dati personali    solo pagine pubbliche dei siti
```

- L'utente scrive una ricerca nella barra del sito. Il browser la invia al **servizio intermedio**, non direttamente a
  Google.
- Il servizio intermedio toglie dal testo email, numeri di telefono, codici fiscali, codici alfanumerici (es. codici di
  accesso ai referti o di prenotazione), età e date, poi inoltra la ricerca a Vertex AI Search e restituisce i risultati
  alla pagina.
- **L'indice contiene solo contenuti pubblici dei siti**: pagine e PDF indicizzabili e collegati, letti dalle sitemap.
  Nessun dato di pazienti, ospiti o dipendenti entra nell'indice.
- L'unico dato dell'utente che entra nel sistema è il **testo della ricerca**, già ripulito. Il contenuto sanitario resta,
  perché è la ricerca stessa (es. «riabilitazione parkinson»).

## 3. Componenti e ambienti

| Componente | Dove | Note |
| --- | --- | --- |
| Siti kosgroup.com e anniazzurri.it | MODX Cloud (hosting attuale) | Pagina dei risultati; nessun dato della ricerca salvato |
| Servizio intermedio | Google Cloud Run, regione UE, progetto `kos-ricerca` | Pulizia del testo, limite giornaliero di ricerche; log senza il testo delle ricerche |
| Vertex AI Search (Discovery Engine) | Regione UE, stesso progetto | Un indice per sito; statistiche di ricerca di Google spente; campionamento di input e risposte da parte di Google non consentito; nessun addestramento sui dati |
| Credenziali | Account di servizio dedicato | Chiave custodita come segreto del servizio intermedio, mai nel codice dei siti |
| Statistiche del sito (GA4) | Account GA4 di KOS, già in uso | Evento di ricerca con il numero di risultati, **senza il testo** |

Progetto Google Cloud e fatturazione sono intestati a KOS. Accesso alla console: Danilo Bernabeo e gli amministratori
indicati da KOS.

## 4. Misure sui dati

- **Minimizzazione**: pulizia del testo nel servizio intermedio prima di Google; nessun identificativo dell'utente
  inviato a Google (nessun cookie, nessun account, nessun evento utente).
- **Nessuna conservazione del testo da parte nostra**: i log del servizio intermedio registrano tempo e numero di
  risultati, non il testo. Le statistiche di ricerca di Google, che conserverebbero il testo, sono spente; i log di
  Google Cloud registrano la richiesta con il testo oscurato (verificato il 16/11).
- **Risposte generate dall'AI**: solo su anniazzurri.it e solo per domande pratiche (ricovero, costi, moduli, contatti),
  da un elenco chiuso di pagine, escluse le domande su sintomi, diagnosi, farmaci e terapie. Su kosgroup.com nessuna
  risposta generata, salvo diversa decisione di KOS.
- **Domande di crisi** (es. autolesionismo): riquadro fisso con il 112 e i numeri di emergenza sopra i risultati, nessuna
  risposta generata.
- **«Vicino a me»**: posizione dal browser solo con il consenso dell'utente, usata per ordinare i risultati e non
  salvata; nessuna posizione ricavata dall'indirizzo IP.

La mappa delle strutture (store locator RetailTune) è un componente separato, con la sua documentazione già inviata da
RetailTune: la ricerca interna non le passa dati.

## 5. Inquadramento

KOS è titolare del trattamento; Google è responsabile del trattamento secondo il **Cloud Data Processing Addendum** di
Google Cloud, con le clausole contrattuali tipo.

## 6. Fase di test (12-18/11/2026)

Abbiamo confrontato Vertex AI Search con la ricerca Yext sulle ricerche reali degli ultimi 12 mesi, esportate da Yext
e ripulite dai dati personali (kosgroup.com 2.668 ricerche distinte, anniazzurri.it 320), con un secondo controllo
automatico di Google Sensitive Data Protection (regione UE) prima di ogni invio. Nulla è stato pubblicato sui siti.

Il 16/11, prima che le regole di pulizia fossero completate, sono arrivate a Vertex 88 ricerche con nomi di persona e
5 con codici di accesso ai referti scritti dagli utenti nella barra. Lo stesso giorno le regole sono state corrette e
le app di ricerca che le avevano ricevute sono state eliminate e ricreate con le statistiche spente. Google non
documenta per quanto conserva i registri di un'app eliminata.

I testi della prova (esportazione Yext e set ripulito) sono conservati sul PC di Danilo Bernabeo, in una cartella
fuori dal repository del codice, con disco cifrato (BitLocker, XTS-AES 128, protezione attiva; verifica del 18/11), e
saranno **cancellati il 27/11/2026**, salvo diversa indicazione. In alternativa, se preferite che restino su
infrastruttura KOS, possono essere spostati in un bucket Cloud Storage in regione UE nel progetto di KOS, con accesso
limitato.

## 7. Punti per il DPO

1. Conferma che il Cloud Data Processing Addendum risulti accettato per l'account Google Cloud di KOS (lo verifico
   nella console, pagina «Privacy e sicurezza», e ne invio l'evidenza).
2. Conferma della conservazione dei testi della prova fino al 27/11 (PC con disco cifrato o bucket UE, punto 6).
3. Se ritenete necessario chiedere a Google la durata di conservazione dei registri delle app eliminate.
4. Per KOS, fuori dalla nuova ricerca: gli stessi codici di accesso ai referti sono anche nello storico delle ricerche di
   Yext, perché gli utenti li scrivono nella barra di ricerca attuale.
