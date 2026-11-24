# Nuova ricerca interna dei siti: scheda tecnica per la valutazione privacy

Gruppo KOS, kosgroup.com e anniazzurri.it. 24/11/2026, Danilo Bernabeo. Parere richiesto entro il 25/11/2026.

## 1. Scopo

La ricerca interna dei due siti, oggi fornita da Yext, va sostituita entro fine gennaio 2027. La soluzione proposta usa
Google Vertex AI Search nel progetto Google Cloud di KOS. Questa scheda descrive il sistema che andrebbe in produzione,
i dati che tratta e le misure previste, più un breve resoconto della fase di test appena conclusa. La decisione sulla
soluzione è prevista per il 27/11/2026.

## 2. Come funziona

```
Browser dell'utente ──► Sito (MODX Cloud) ──► Servizio intermedio ──► Vertex AI Search
   testo della ricerca      pagina risultati      Cloud Run, regione UE      indice e ricerca, regione UE
                                                   toglie contatti e codici   solo pagine pubbliche dei siti
```

- L'utente scrive una ricerca nella barra del sito. Il browser la invia al **servizio intermedio**, non direttamente a
  Google, nel corpo della richiesta e non nell'indirizzo, così i log di accesso di Cloud Run non la registrano.
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

Il progetto Google Cloud `kos-ricerca` è sull'account Google di KOS marketing.kos2019@gmail.com («KOS group») ed è
collegato all'account di fatturazione «Kos Care». Accesso alla console: Danilo Bernabeo e gli
amministratori indicati da KOS.

## 4. Misure sui dati

- **Minimizzazione**: pulizia del testo nel servizio intermedio prima di Google; nessun identificativo dell'utente
  inviato a Google (nessun cookie, nessun account, nessun evento utente).
- **Nessuna conservazione del testo da parte nostra**: i log del servizio intermedio registrano tempo e numero di
  risultati, non il testo. Le statistiche di ricerca di Google, che conserverebbero il testo, sono spente sulle app di
  produzione: si spengono solo alla creazione dell'app, quindi le app di produzione nascono così. I log di Google Cloud
  registrano la richiesta con il testo oscurato (verificato il 16/11).
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

KOS è titolare del trattamento; Google opera come responsabile secondo il Cloud Data Processing Addendum di Google
Cloud, con le clausole contrattuali tipo, confermato nella console prima della messa in produzione (paragrafo 8, punto 1).

## 6. Fase di test (12-18/11/2026)

Abbiamo confrontato Vertex AI Search con la ricerca Yext sulle ricerche reali degli ultimi 12 mesi, esportate da Yext
e ripulite dai dati personali (kosgroup.com 2.668 ricerche distinte, anniazzurri.it 320), con un secondo controllo
automatico di Google Sensitive Data Protection (regione UE) prima di ogni invio. Nulla è stato pubblicato sui siti.

**Risultato del confronto.** La ricerca di Google trova la pagina giusta molto più spesso di quella attuale. Sul
campione giudicato (kosgroup.com 400 ricerche, anniazzurri.it tutte le 320, pesate per frequenza), la pagina attesa è
tra i primi tre risultati:

| Sito | Yext oggi | Vertex AI Search | Soluzione proposta |
| --- | --- | --- | --- |
| kosgroup.com | 66,1% | 81,6% | 94,7% |
| anniazzurri.it | 30,7% | 81,8% | 88,2% |

La soluzione proposta aggiunge a Vertex il riconoscimento dei nomi delle strutture e dei luoghi nella pagina dei
risultati: da solo Vertex è più debole di Yext sulle ricerche per luogo, che la pagina risolve
mostrando in testa le strutture più vicine. I numeri definitivi sono nella restituzione del 26/11.

Il 16/11, prima che le regole di pulizia fossero completate, sono arrivate a Vertex 88 ricerche con nomi di persona e
5 con codici di accesso ai referti scritti dagli utenti nella barra. Lo stesso giorno le regole sono state corrette e
le app di ricerca che le avevano ricevute sono state eliminate e ricreate con le statistiche spente. Google non
documenta per quanto conserva i registri di un'app eliminata. L'app di prova di anniazzurri.it, che ha ricevuto solo
ricerche già ripulite, ha le statistiche attive; in produzione sarà sostituita da un'app nuova con le statistiche
spente.

I testi della prova (esportazione Yext e set ripulito) sono conservati sul PC di Danilo Bernabeo, in una cartella
fuori dal repository del codice, con disco cifrato (BitLocker, XTS-AES 128, protezione attiva; verifica del 18/11), e
saranno **cancellati il 27/11/2026**, salvo diversa indicazione. In alternativa, se preferite che restino su
infrastruttura KOS, possono essere spostati in un bucket Cloud Storage in regione UE nel progetto di KOS, con accesso
limitato.

## 7. Attivazione in produzione: cosa serve

- **Già fatto**: progetto `kos-ricerca`, fatturazione attiva, indici nella regione UE (`eu`), che per Google garantisce
  la conservazione dei dati nell'UE.
- **Nella console, pagina «Privacy e sicurezza»** (proprietario del progetto): accettazione del Cloud Data Processing
  Addendum e contatto del DPO, con nome completo, indirizzo, numero di telefono ed email. La dichiarazione GDPR, il
  rappresentante locale e l'autorità di controllo richiesti nella stessa pagina riguardano solo chi ha l'indirizzo di
  fatturazione fuori da Europa, Medio Oriente e Africa: per KOS non servono.
- **Alla messa in produzione**: servizio intermedio su Cloud Run in regione UE (proposta Milano, `europe-west8`), con la
  chiave come segreto del servizio; app di ricerca nuove create con le statistiche spente; limite giornaliero di ricerche
  e avviso di budget.
- **Dati e addestramento**: Google non usa i dati per addestrare o rifinire i modelli senza autorizzazione del cliente
  (condizioni specifiche dei servizi, sezione 17).

## 8. Punti per il DPO

1. Termini sulla protezione dei dati di Google Cloud. Prima della messa in produzione, il proprietario del progetto
   conferma nella console (Privacy e sicurezza) i termini sul trattamento dei dati di Google Cloud, indicando che si
   applica il GDPR e i contatti del DPO. È un'operazione di pochi minuti: se ci indicate i contatti da inserire, la
   prepariamo noi e la conferma resta a voi.
2. Società di riferimento. Indicare quale società del gruppo va considerata titolare per la ricerca di kosgroup.com e
   per quella di anniazzurri.it (il progetto Google Cloud è fatturato a Kos Care).
3. Conferma della conservazione dei testi della prova fino al 27/11 (PC con disco cifrato o bucket UE, paragrafo 6).
4. Se ritenete necessario chiedere a Google la durata di conservazione dei registri delle app eliminate.
5. Per KOS, fuori dalla nuova ricerca: gli stessi codici di accesso ai referti sono anche nello storico delle ricerche di
   Yext, perché gli utenti li scrivono nella barra di ricerca attuale.
   Nell'esportazione Yext di kosgroup.com degli ultimi 12 mesi: 122 ricerche distinte con nomi di persona (191 ricerche),
   quasi sempre il solo cognome o nome e cognome; 29 con codici alfanumerici (46 ricerche), tra cui i 5 codici di
   accesso ai referti. Su anniazzurri.it 3 ricerche con nomi di persona. Esempi in forma mascherata (X = lettera,
   9 = cifra; nessun testo originale esce dall'archivio della prova): «login XXX99», «referti … 999XX9X9»,
   «XXXXX:XXX999», «[cognome]», «[nome] [cognome]».
