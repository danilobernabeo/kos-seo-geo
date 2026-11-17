# Prova della nuova ricerca dei siti: scheda per il DPO

Gruppo KOS, kosgroup.com e anniazzurri.it. Bozza del 17/11/2026, invio il 18/11/2026; parere richiesto entro il
25/11/2026. Danilo Bernabeo.

## 1. Cosa si fa

La ricerca dei due siti (oggi Yext) va sostituita entro fine gennaio 2027. La prova confronta Google Vertex AI Search
con la ricerca Yext attuale sulle ricerche che gli utenti hanno fatto negli ultimi 12 mesi, prima di decidere
(27/11/2026). Nella prova nulla viene pubblicato sui siti.

## 2. Dati usati

- **Ricerche**: tutte le ricerche distinte dei 12 mesi esportate da Yext (kosgroup.com 2.822, anniazzurri.it 325),
  **ripulite dai dati personali** prima di ogni invio a un servizio esterno: kosgroup.com 2.668, anniazzurri.it 320.
- **Pulizia**: esclusi email, codici fiscali, telefoni, date, codici alfanumerici anche dentro la frase (codici di
  accesso ai referti, di prenotazione, di pratica) e nomi di persona che non corrispondono a una pagina pubblica dei
  siti (es. i medici con la loro pagina restano); tolte le età; riscritte a mano le frasi con dettagli di vita privata,
  tenendo il bisogno. Il contenuto sanitario resta, perché è la ricerca stessa (es. «riabilitazione parkinson»).
- **Secondo controllo**: prima di ogni invio il set ripulito passa da Google Sensitive Data Protection (regione UE). I
  ritrovamenti si controllano a mano; un controllo automatico impedisce di lanciare la prova su un file non controllato.
- **Pagine indicizzate**: solo pagine pubbliche dei siti (pagine e PDF indicizzabili e collegati dal sito).

## 3. Dove stanno i dati

- **Testi originali** (esportazione Yext, set ripulito, riscritture, elenco dei nomi esclusi), proposta: **bucket Cloud
  Storage in regione UE nel progetto Google Cloud di KOS** (`kos-ricerca`), con accesso limitato a Danilo Bernabeo e al
  service account della prova; copia temporanea nell'area di lavoro solo durante l'elaborazione. **Cancellazione il
  27/11/2026**, salvo decisione diversa.
- **Oggi** i testi sono sul PC di Danilo Bernabeo, in una cartella fuori dal repository del codice e accessibile solo a
  lui, **su un disco non cifrato**: per questo la proposta è spostarli nel bucket.
- **Chiave del servizio** (service account della prova): in una cartella separata dai dati, mai nel codice né in chat;
  revocata a fine prova.
- **Alternativa**: PC di Danilo Bernabeo, solo dopo la cifratura del disco, stessa cancellazione.
- **Google Cloud**: progetto di KOS, indice e ricerca in regione UE. Campionamento di input e risposte da parte di Google
  non consentito (scelta fatta all'attivazione); nessun addestramento sui dati.

## 4. Cosa è successo nella prima tornata (16/11/2026)

- **Arrivati a Vertex prima delle correzioni**: 88 ricerche con nomi di persona e 5 ricerche con **credenziali di
  accesso a dati sanitari** (codici per scaricare i referti, scritti dagli utenti nella barra di ricerca, es. «login»
  seguito dal codice). Le regole di pulizia iniziali non le avevano riconosciute; sono state escluse lo stesso giorno e
  le regole corrette. Su Yext non era partito nulla.
- **Esclusi in tutto dopo le correzioni e il controllo automatico**: 125 nomi di persona (kosgroup.com 122, lo 0,74%
  delle ricerche; anniazzurri.it 3, l'1,3%), nessuno corrispondente a pagine dei medici; 37 non sono mai stati inviati.
- **Testi conservati da Vertex**: le app di ricerca tengono un registro delle ricerche per le statistiche, che non si
  può cancellare né spegnere su un'app esistente. Le due app di kosgroup.com che avevano ricevuto i testi sono state
  **eliminate e ricreate lo stesso giorno con le statistiche spente**; nessun evento utente è mai stato inviato.
  L'app di anniazzurri.it ha le statistiche ancora attive (non si possono spegnere su un'app esistente) perché ha
  ricevuto solo ricerche già ripulite, e il secondo controllo non ha trovato altro.
- **Log di Google Cloud**: verificati il 16/11 (3.093 voci): il testo delle ricerche è oscurato in tutte le voci.
  Punto chiuso.
- **Punto aperto**: Google non documenta per quanto tempo conserva i registri di un'app eliminata.
- **Credenziali nello storico Yext**: le stesse credenziali sono anche nello storico delle ricerche di Yext, perché gli
  utenti le scrivono nella barra. Da segnalare a KOS.

## 5. Confronto con Yext

- Un solo giro sul campione della prova (720 ricerche già ripulite e controllate), il 16/11/2026 dalle 21:06:36 alle
  21:22:59, una ricerca al secondo, **senza sessioni** (tracciamento della sessione spento) e con un'etichetta di origine
  dedicata, per escludere queste ricerche dai report Yext.
- Il giro completo su Yext (tutte le ricerche) si fa solo con la conferma di KOS sulla capacità del contratto.

## 6. Risposte generate dall'AI

- Nella prova le ricerche passano anche dalla generazione delle risposte, solo per misurarle: nessuna risposta è
  pubblicata.
- In produzione (proposta): risposte solo su anniazzurri.it, solo per domande pratiche (ricovero, costi, moduli,
  contatti), da un elenco chiuso di pagine, con un filtro che le esclude per sintomi, diagnosi, farmaci e terapie.
- Domande di crisi (es. autolesionismo): riquadro fisso con il 112 e i numeri di emergenza sopra i risultati, nessuna
  risposta generata.

## 7. Dopo la prova, se si va avanti (proposta)

- Il browser non chiama Google direttamente: un servizio intermedio in regione UE riceve la ricerca, toglie email,
  telefoni, codici fiscali, codici, età e date, e la inoltra.
- Log del servizio intermedio senza il testo delle ricerche; **statistiche di ricerca di Google spente** (le statistiche
  del sito danno già il numero di ricerche e di ricerche senza risultati, senza il testo).
- Statistiche del sito (GA4): evento di ricerca con il numero di risultati, senza il testo.
- «Vicino a me»: posizione dal browser solo con consenso, usata per ordinare i risultati e non salvata; nessuna
  posizione ricavata dall'indirizzo IP.

## 8. Da verificare o decidere

1. Cloud Data Processing Addendum di Google Cloud (accordo sul trattamento dei dati, con le clausole contrattuali tipo)
   accettato per l'account di KOS: da verificare nella console Google Cloud, pagina «Privacy e sicurezza».
2. Conservazione dei testi della prova: bucket UE nel progetto di KOS (proposta) o PC di Danilo Bernabeo con disco
   cifrato.
3. Conservazione dei registri delle app eliminate: chiedere a Google, se il DPO lo ritiene necessario.
4. Ruolo di Danilo Bernabeo nel trattamento (responsabile ai sensi dell'art. 28 GDPR o persona autorizzata da KOS), da
   inquadrare a cura del DPO.
