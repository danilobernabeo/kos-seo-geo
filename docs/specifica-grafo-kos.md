# Specifica del grafo e delle schede Google (stadio 7)

Danilo Bernabeo · 29 ottobre 2026 · documento interno, non va condiviso così com'è con KOS o con l'agenzia

Questa specifica guida il lavoro sulle schede Google Business Profile di KOS e Anni Azzurri (138 schede, stadio 7) e
tiene allineati tre oggetti che descrivono la stessa sede: la scheda Google, la pagina del sito e il dato
strutturato. Non ridefinisce il dato strutturato: per tipi, identificativi e proprietà valgono il `CLAUDE.md`
(sezione «Convenzioni del dato strutturato»), `docs/convenzione-identificativi.md` e `docs/kos-24.md`. Qui ci sono solo
le regole nuove, l'archivio delle sedi, le schede Google e i punti aperti.

Ogni regola ha accanto il modo per verificarla (colonna o riga «Verifica»): una regola che non si può controllare
non è una regola.

## 1. Perché il grafo, in una frase

Il dato strutturato non serve a comparire nelle risposte AI di Google, che non lo richiedono
([guida di Google, 16/09/2026](https://developers.google.com/search/docs/fundamentals/ai-optimization-guide)). Serve
ai risultati arricchiti e al pannello informativo (documentato da Google), e alla coerenza tra sito, schede Google e
directory, che verifichiamo noi. Per le ricerche «vicino a me» decide la scheda Google: per questo la scheda corretta,
alimentata dallo stesso archivio del grafo, è la parte che conta di più. Da non promettere: presenza nelle risposte
AI «grazie allo schema», percentuali di traffico AI, punteggi di visibilità.

## 2. Entità e relazioni

| Entità | Tipo schema.org | Identificativo | Si collega a | Stato |
| --- | --- | --- | --- | --- |
| KOS S.p.A. | `Organization` | `https://kosgroup.com/#organization` | — (controllante: CIR S.p.A., non nel grafo) | In produzione (KOS 9) |
| KOS Care S.r.l. | `Organization` | `https://anniazzurri.it/#organization` | `parentOrganization` → KOS S.p.A. | In produzione (AA-8) |
| Altre società del gruppo (Sanatrix Gestioni, Sanatrix, Jesilab, Abitare il Tempo, Fidia, KOS Servizi) | `Organization` | `https://kosgroup.com/it/chi-siamo/governance/dati-societari#<partita IVA>` | `parentOrganization` → KOS S.p.A. | Deciso il 29/10, da implementare |
| Struttura (sede fisica) | tipo da `CLAUDE.md` (residenze `["MedicalBusiness","MedicalOrganization"]`; sanitarie `MedicalClinic`, `Hospital`…) | `{URL scheda}#localbusiness` | `parentOrganization` → società che la gestisce; `brand` → Anni Azzurri (solo residenze) | KOS: voce 24 (2-6/11); AA: sprint 3 |
| Reparto o unità operativa nella stessa sede | stesso tipo della struttura | `{URL pagina unità}#department` | `department` dalla struttura; `parentOrganization` → struttura | Deciso il 29/10 (Villa Margherita Vicenza) |
| Servizio senza sede propria (RSA Aperta, assistenza domiciliare) | `Service` | `{URL}#service` | `provider` → struttura; `areaServed` | Sportello in produzione; RSA Aperta sprint 3 |
| Medico | `IndividualPhysician` | `{URL scheda}#medico` | `practicesAt` → struttura | KOS voce 11, sprint 3 |
| Altro professionista sanitario | `Person` con `jobTitle` | `{URL scheda}#person` | `practicesAt` → struttura | KOS voce 11, sprint 3 |

Regole delle relazioni:

- **Struttura → società.** Ogni struttura risale alla società che la gestisce, non per forza all'ente del sito. Esempio:
  la Residenza Abitare il Tempo risale ad Abitare il Tempo S.r.l., non a KOS Care. Serve la tabella struttura →
  società (punto aperto 1). Fino ad allora il collegamento resta all'ente del sito.
- **Marchi.** L'unico marchio attivo è Anni Azzurri: `brand` solo sulle residenze. Nessun altro marchio nel grafo né
  nella TV `str.marchio` di kosgroup.com, che si svuota.
- **Mai `sameAs` tra soggetti giuridici diversi**; `sameAs` solo verso profili esterni ufficiali dello stesso soggetto.
- **Niente indirizzo, niente nodo struttura** (regola già nella libreria).

Verifica: per ogni pagina struttura, il `@graph` servito contiene un solo nodo `#localbusiness`, il suo
`parentOrganization` è una delle società della pagina dei dati societari, e `brand` c'è solo su anniazzurri.it.

## 3. Identificativi: cosa non cambia e cosa si aggiunge

Gli identificativi già pubblicati non cambiano: sono basati sugli indirizzi del sito, non sui codici di Yext o
RetailTune, e restano gli stessi con la migrazione. Si aggiungono, in una nuova versione della convenzione da
consegnare con lo sprint che li usa:

1. `#department` per i reparti nella stessa sede.
2. Le società del gruppo: indirizzo della pagina dei dati societari più la partita IVA come frammento. È l'unico caso
   in cui il frammento non è fisso per tipo: un'unica pagina descrive nove società.

Verifica: nessun `@id` già presente nell'export del 29/10 (`scripts/collaudo/verifica-sprint-1.mjs`) cambia dopo i
rilasci; i nuovi frammenti compaiono solo sulle pagine previste.

## 4. Archivio unico delle sedi

**RetailTune è l'archivio** delle sedi (contratto in firma, decisione presa). Da RetailTune derivano tre cose:

| Uscita | Come | Chi |
| --- | --- | --- |
| Scheda Google | Sincronizzazione di RetailTune: nome, indirizzo, telefono, sito, categorie, orari e orari speciali, attributi, foto, descrizione, link di prenotazione; RetailTune gestisce anche le risposte alle recensioni | RetailTune, su dati validati da KOS |
| Pagina del sito | Import nelle TV `str.*` di MODX (elenco in `docs/kos-24.md`), una volta prima della migrazione dall'export Yext corretto, poi ogni giorno da RetailTune | Progetto |
| Dato strutturato | Letto dalle stesse TV della pagina, quindi pagina e markup non possono divergere | Progetto (libreria comune) |

Regole:

- **Un solo punto di modifica.** Un dato della sede si corregge in RetailTune, mai a mano su Google né nelle TV. Le
  modifiche fatte a mano su Google vengono sovrascritte o, peggio, restano diverse dal sito.
- **Codice sede.** Ogni sede ha un codice stabile, lo stesso in RetailTune, nella scheda Google («Codice negozio»)
  e nella TV della pagina. Oggi nei 6 gruppi Google ci sono 92 codici `KCA00_…`, 18 numerici, 18 liberi («Temporary 1»,
  «Villalba», «BGOMAN», «RAACampiBisenzio»…) e 10 vuoti: vanno normalizzati prima della migrazione, con il codice che
  usa RetailTune (punto aperto 3).
- **Confronto a tre** (sito, RetailTune, export Google) dopo ogni import e ogni trimestre; le differenze si correggono
  in RetailTune.

Verifica: l'export Google e la tabella delle TV hanno lo stesso codice sede su ogni riga; il confronto a tre non trova
differenze su nome, indirizzo, telefono, sito e orari.

## 5. Schede Google

### Account e gruppi

Proprietario: KOS. Sei gruppi di sedi (export del 26/10 in `kosgroup/dati/voce-24/gbp/`): Anni Azzurri (60),
Centri ambulatoriali (15), Extra Yext (26), Poliambulatori (14), Psichiatria (9), Riabilitazione (14).

### Nome, indirizzo, telefono, sito

| Campo | Regola | Fonte |
| --- | --- | --- |
| Nome | Il nome reale usato sull'insegna e nei documenti, senza parole chiave aggiunte («RSA», città, servizi) | [Linee guida per la rappresentazione](https://support.google.com/business/answer/3038177) |
| Telefono | Solo numeri ufficiali della sede, gli stessi del sito e della carta dei servizi; niente numeri di tracciamento | Linee guida; regola del progetto |
| Sito | L'URL canonico della pagina della sede, con i parametri UTM `utm_source=google&utm_medium=organic&utm_campaign=gbp` | Decisione del 29/10, da applicare con la migrazione |
| Indirizzo | Uguale a quello della pagina e del markup | Archivio |

Verifica: nessun nome dell'export contiene parole che non sono nel nome ufficiale; ogni campo «Sito» punta a
`kosgroup.com` o `anniazzurri.it` (oggi nel gruppo Extra Yext 5 schede puntano altrove e una non ha il sito; nei Poliambulatori una punta altrove) e la pagina risponde 200 con canonical uguale all'URL senza parametri.

### Categorie: residenze Anni Azzurri

Decise il 29/10, da validare con PWC. Valgono per tutte le residenze insieme: le linee guida chiedono la stessa
categoria per le sedi di una catena che offrono lo stesso servizio, e tutte le residenze sono RSA o equivalenti
(carte dei servizi, `anniazzurri/dati/accreditamento-residenze-2026-10-29.xlsx`).

| Categoria | Ruolo | Dove |
| --- | --- | --- |
| Casa di riposo | Principale (oggi è «Casa di cura» su 57 residenze) | Tutte |
| Casa di cura, Centro anziani, Assistenza agli anziani | Aggiuntive | Tutte |
| Centro diurno per adulti | Aggiuntiva | Solo con centro diurno integrato |
| Centro di riabilitazione | Aggiuntiva | Solo con unità di riabilitazione (PGR Milano, PGR Cinisello, Mirasole, Rezzato, Dorica…) |
| Casa di cura per lungodegenti | Aggiuntiva | Solo con cure intermedie o post-acuzie (Villa Reale, San Luca) |
| Servizio di assistenza domiciliare | Aggiuntiva | Solo con RSA Aperta |
| Geriatra, Servizio di salute mentale | Da togliere | Oggi su 51 e 42 residenze |

Motivazioni e fonti:

- Concorrenti verificati su Google Maps il 29/10: Korian/Clariane (8 schede), Sereni Orizzonti (9 su 10), Gruppo La Villa
  (8), Gheron usano «Casa di riposo»; Korian ha come aggiuntive «Casa di cura», «Centro anziani», «Assistenza agli
  anziani». Nelle ricerche «RSA Milano» e «casa di riposo Torino» 19 risultati su 20 hanno «Casa di riposo».
- Elenco delle categorie disponibili letto nel selettore di Google Business Profile il 29/10 (senza salvare): non
  esistono categorie per Alzheimer, demenza, residenza assistita, hospice o cure palliative come struttura.
  Nucleo Alzheimer e stati vegetativi si raccontano nella descrizione e nella pagina, non nelle categorie.
- «Geriatra» e simili sono categorie per singoli medici: su Mirasole Google classifica la residenza come «doctor».
- «Casa di riposo» compare due volte nel selettore: sono due categorie diverse con lo stesso nome italiano. Quale
  usare lo conferma RetailTune, che lavora con il codice della categoria (punto aperto 4).
- Anomalie da correggere comunque: Valdaso (principale «Centro di riabilitazione»), Conero («Servizio di salute
  mentale»), Borgomanero («Casa di riposo» senza aggiuntive).

Misura: posizione nelle ricerche «RSA <città>» e «casa di riposo <città>» per un campione di 10 residenze, prima del
cambio e dopo 4-6 settimane, confrontata con le strutture KOS non toccate.

Verifica: nell'export Google tutte le residenze hanno la stessa principale e le aggiuntive previste dalla tabella;
nessuna ha «Geriatra» o «Servizio di salute mentale».

### Categorie: strutture KOS

Stesse regole (principale specifica, poche aggiuntive per reparti o servizi reali, mai categorie per singoli
professionisti), da applicare gruppo per gruppo dopo la migrazione. Dall'export del 26/10 sono da rivedere almeno:

- categorie per professionisti su strutture: «Pediatra» (13), «Psicologo» (11), «Logopedista» (10) nei Centri
  ambulatoriali; «Fisiatra», «Fisioterapista» nella Riabilitazione; «Psichiatra» in Psichiatria ed Extra Yext;
- categorie estranee: «Sala biliardi» su 2 Centri ambulatoriali, «Automazione e robotica» in Extra Yext.

Verifica: come per le residenze, gruppo per gruppo.

### Reparti, servizi e professionisti

- **Reparti nella stessa sede**: una sola scheda, salvo che il reparto abbia ingresso, nome e gestione propri (regola
  dei reparti delle linee guida). Villa Margherita Vicenza: una scheda, due unità operative come `#department` nel
  grafo.
- **Servizi senza sede propria** (RSA Aperta, assistenza domiciliare): nessuna scheda; al massimo la categoria
  aggiuntiva sulla residenza che li eroga.
- **Professionisti**: nessuna scheda per singolo medico creata dal progetto. Se KOS le vuole, è una decisione a parte
  (punto aperto 5).

## 6. Regole fisse

| Regola | Dove vale | Verifica |
| --- | --- | --- |
| Solo numeri di telefono ufficiali della sede | Scheda, pagina, markup, risposte | Confronto a tre |
| Comunicazione sanitaria solo informativa, senza elementi promozionali o suggestivi (L. 145/2018, art. 1 c. 525; Codice deontologico artt. 55-56) | Descrizione della scheda, post, pagine | Revisione dei testi prima della pubblicazione |
| Nelle risposte alle recensioni mai dati sanitari, mai confermare che l'autore è un paziente, mai citare cure | Risposte (gestite da RetailTune) | Campione mensile delle risposte |
| Richiesta di recensione uguale per tutti, senza incentivi né filtri sui soddisfatti | Processi di KOS | Istruzioni scritte al personale |
| Il markup descrive solo ciò che è scritto nella pagina | Sito | Rich Results Test sul codice |
| Nessuna modifica a mano su Google | Schede | Il registro delle modifiche non ha righe senza origine RetailTune |

## 7. Registro delle modifiche alle schede

Ogni modifica a una scheda, fatta dal progetto o chiesta a RetailTune, entra in un registro (`kosgroup/dati/gbp/registro.csv`):

| Campo | Contenuto |
| --- | --- |
| data | AAAA-MM-GG |
| codice_sede | Codice negozio |
| scheda | Nome della scheda |
| campo | Nome, categoria principale, categorie aggiuntive, sito, telefono, orari, descrizione… |
| prima | Valore prima |
| dopo | Valore dopo |
| motivo | Regola di questa specifica o richiesta di KOS |
| richiesto_da / eseguito_da | Persona o RetailTune |
| verifica | Data e esito del controllo sull'export successivo |

## 8. Stato

| Parte | Stato |
| --- | --- |
| Enti KOS S.p.A. e KOS Care, briciole, sito | In produzione |
| Libreria del nodo struttura (KOS voce 24) | In produzione spenta; si accende con l'import 2-6/11 |
| Dato strutturato delle residenze AA | Sprint 3 (27/11) |
| Medici e professionisti KOS | Sprint 3 (voce 11); widget per medico dopo l'esportazione dal gestionale verso TuoTempo |
| Società del gruppo e `#department` | Deciso il 29/10; implementazione con la tabella struttura → società |
| Archivio RetailTune e sincronizzazione delle schede | In avvio (contratto in firma) |
| Categorie delle residenze | Decise il 29/10, da validare con PWC; si applicano con la migrazione |
| Categorie delle strutture KOS, codici sede, UTM, registro | Previsti con la migrazione |

## 9. Punti aperti

1. **Tabella struttura → società** che gestisce ciascuna struttura (la sta chiedendo Danilo). Senza, il
   `parentOrganization` resta all'ente del sito.
2. **Validazione delle categorie delle residenze con PWC.**
3. **Codice sede di RetailTune**: quale usano per agganciare le schede; normalizzazione dei 46 codici non uniformi o
   vuoti.
4. **Quale delle due «Casa di riposo»** (e delle due «Servizio di assistenza domiciliare») usare: codice della
   categoria da RetailTune, e quale usa Korian.
5. **Schede dei singoli medici**: KOS le vuole? Le linee guida le ammettono, ma moltiplicano le schede da curare.
6. **Ingresso, nome e gestione dei centri diurni e degli hospice nelle residenze**: decidono se meritano una scheda
   propria (oggi Beati Coniugi Martin e San Giorgio ce l'hanno).

## 10. Riferimenti

- Google: [linee guida per la rappresentazione](https://support.google.com/business/answer/3038177),
  [scelta della categoria](https://support.google.com/business/answer/7249669), [dato strutturato LocalBusiness](https://developers.google.com/search/docs/appearance/structured-data/local-business).
- Progetto: `CLAUDE.md`, `docs/convenzione-identificativi.md`, `docs/kos-24.md`, `docs/piano-sprint-1.md` (decisioni del 29/10),
  `kosgroup/dati/voce-24/gbp/` (export delle schede), `anniazzurri/dati/accreditamento-residenze-2026-10-29.xlsx`.
