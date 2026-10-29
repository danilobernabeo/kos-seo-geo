# Specifica del grafo e delle schede Google (stadio 7)

Danilo Bernabeo · 29 ottobre 2026 · versione 2, dopo la revisione indipendente · documento interno, non va condiviso
così com'è con KOS o con l'agenzia

Questa specifica guida il lavoro sulle schede Google Business Profile di KOS e Anni Azzurri (138 schede, stadio 7) e
tiene allineati tre oggetti che descrivono la stessa sede: la scheda Google, la pagina del sito e il dato
strutturato. Non ridefinisce il dato strutturato: per tipi, identificativi e proprietà valgono il `CLAUDE.md`
(sezione «Convenzioni del dato strutturato»), `docs/convenzione-identificativi.md` e `docs/kos-24.md`. Qui ci sono solo
le regole nuove, l'archivio delle sedi, le schede Google e i punti aperti.

Ogni regola ha accanto il modo per verificarla: una regola che non si può controllare non è una regola.

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
| Altre società operative | `Organization` | `https://kosgroup.com/it/chi-siamo/governance/dati-societari#<partita IVA>` | `parentOrganization` → KOS S.p.A. | Deciso il 29/10, da implementare |
| Struttura (sede fisica) | tipo da `CLAUDE.md` (residenze `["MedicalBusiness","MedicalOrganization"]`; sanitarie `MedicalClinic`, `Hospital`…) | `{URL scheda}#localbusiness` | `parentOrganization` → società che la gestisce; `brand` → Anni Azzurri (solo residenze) | KOS: voce 24; AA: sprint 3 (27/11) |
| Reparto o unità operativa nella stessa sede | stesso tipo della struttura | `{URL pagina unità}#department` | `department` dalla struttura; `parentOrganization` → struttura | Deciso il 29/10 |
| Servizio senza sede propria (RSA Aperta, assistenza domiciliare) | `Service` | `{URL}#service` | `provider` → struttura; `areaServed` | Sportello in produzione; RSA Aperta sprint 3 |
| Medico | `IndividualPhysician` | `{URL scheda}#medico` | `practicesAt` → struttura | KOS voce 11, sprint 3 |
| Altro professionista sanitario | `Person` con `jobTitle` | `{URL scheda}#person` | `worksFor` → struttura (`practicesAt` esiste solo su `IndividualPhysician`) | KOS voce 11, sprint 3 |

Le società della [pagina dei dati societari](https://kosgroup.com/it/chi-siamo/governance/dati-societari) sono nove:
KOS S.p.A. (10674520159), KOS Care (01148190547), Sanatrix Gestioni (01490070438), Jesilab (02220610428), Abitare il Tempo
(01590980429, numero di registro imprese: la pagina non indica una partita IVA distinta), KOS Servizi (01704930682),
Fidia (01348940436), Sanatrix (P.IVA 00625250436) e Ospedale di Suzzara, in liquidazione (04482440965), che non gestisce
strutture e resta fuori dal grafo. Il frammento è la partita IVA di 11 cifre, senza prefisso `IT`; la proprietà `vatID`
resta con il prefisso, come oggi.

Regole delle relazioni:

- **Struttura → società.** Ogni struttura risale alla società che la gestisce, non per forza all'ente del sito. Esempio:
  la Residenza Abitare il Tempo risale ad Abitare il Tempo S.r.l., non a KOS Care. Serve la tabella struttura →
  società (punto aperto 1), che entra in una TV `str.societa` (partita IVA). La libreria oggi scrive
  `parentOrganization` fisso verso l'ente del sito (`shared/snippets/seoSchema.php`, nodo struttura): va cambiata per
  leggere la TV. Fino ad allora il collegamento resta all'ente del sito.
- **Marchi.** Dal 29/10 (decisione di KOS riferita da Danilo) l'unico marchio attivo è Anni Azzurri: `brand` è un
  riferimento all'ente `https://anniazzurri.it/#organization`, solo sulle residenze. Santo Stefano e Neomesia restano
  nei nomi propri delle strutture, ma non sono più marchi: la TV `str.marchio` di kosgroup.com si svuota e
  `docs/kos-24.md` si aggiorna nello stesso commit dell'implementazione.
- **Mai `sameAs` tra soggetti giuridici diversi**; `sameAs` solo verso profili esterni ufficiali dello stesso soggetto.
- **Niente indirizzo, niente nodo struttura** (regola già nella libreria).

Verifica: per ogni pagina struttura il `@graph` servito contiene un solo nodo `#localbusiness`; il suo
`parentOrganization` è una delle società elencate sopra; `brand` c'è solo su anniazzurri.it; nessun `Person` ha
`practicesAt`.

## 3. Identificativi: cosa non cambia e cosa si aggiunge

Gli identificativi già pubblicati non cambiano: sono basati sugli indirizzi del sito, non sui codici di Yext o
RetailTune, e restano gli stessi con la migrazione. Si aggiungono, in una nuova versione della convenzione da
consegnare con lo sprint che li usa:

1. `#department` per i reparti nella stessa sede. Il nome del reparto è «{nome della sede} {nome del reparto}», come
   chiede Google per i reparti.
2. Le società del gruppo, con la partita IVA come frammento (§2). È l'unico caso in cui il frammento non è fisso per
   tipo: un'unica pagina descrive più società.

**Villa Margherita Vicenza** (una scheda Google, due unità operative). La scheda punta a `/it/villa-margherita`; sul sito
le unità sono `/it/centri/villa-margherita-vicenza-riabilitazione` e `/it/centri/villa-margherita-vicenza-psichiatria`.
Il nodo struttura sta sulla pagina a cui punta la scheda; le due unità sono `#department` sulle loro pagine. Prima
dell'implementazione va verificato che `/it/villa-margherita` sia una pagina vera (200, canonical su sé stessa) e non
un reindirizzamento: se non lo è, la scheda va fatta puntare alla pagina della riabilitazione, che porta il nodo
struttura.

Verifica: uno script esporta per ogni pagina delle sitemap le coppie `url, @type, @id` del `@graph` servito
(`evidenze/<data>/id.csv`); il confronto con l'esportazione di riferimento, da produrre prima del primo rilascio che
tocca il grafo, non mostra `@id` cambiati, e i nuovi frammenti compaiono solo sulle pagine previste. Lo script va
aggiunto a `scripts/collaudo/` (oggi `verifica-sprint-1.mjs` controlla solo gli `@id` di ente e briciole).

## 4. Archivio unico delle sedi

**RetailTune è l'archivio** delle sedi (contratto in firma, decisione presa). Da RetailTune derivano tre cose:

| Uscita | Come | Chi |
| --- | --- | --- |
| Scheda Google | Sincronizzazione di RetailTune: nome, indirizzo, telefono, sito, categorie, orari e orari speciali, attributi, foto, descrizione, link di prenotazione; RetailTune gestisce anche le risposte alle recensioni | RetailTune, su dati validati da KOS |
| Pagina del sito | Import nelle TV `str.*` di MODX (elenco in `docs/kos-24.md`, più `str.codice` e `str.societa`): una volta prima della migrazione dall'export Yext corretto, poi dall'API di RetailTune con un'importazione notturna | Progetto (canale e responsabile: punto aperto 7) |
| Dato strutturato | Letto dalle stesse TV della pagina, quindi pagina e markup non possono divergere | Progetto (libreria comune) |

Se l'importazione notturna fallisce, le TV restano come sono e l'errore arriva per email; la pagina non si svuota mai.

**anniazzurri.it fino al 27/11.** Le TV anagrafiche su anniazzurri.it non esistono ancora e il nodo struttura delle
residenze arriva con lo sprint 3. Fino ad allora il lato «sito» del confronto a tre sono i dati visibili nella pagina
della residenza (indirizzo, telefono, email).

Regole:

- **Un solo punto di modifica.** Un dato della sede si corregge in RetailTune, mai a mano su Google né nelle TV.
- **URL senza parametri nell'archivio.** In RetailTune il sito è l'URL canonico senza parametri; i parametri UTM li
  aggiunge RetailTune solo nella sincronizzazione verso Google. Così le TV e il markup restano puliti.
- **Nome ufficiale.** Il nome della sede è `str.nome` (RetailTune → TV); la scheda Google usa lo stesso, carattere per
  carattere.
- **Codice sede.** Ogni sede ha un codice stabile, lo stesso in RetailTune, nella scheda Google («Codice negozio») e
  nella TV `str.codice`. Negli export del 26/10 ci sono 92 codici `KCA00_…`, 18 numerici, 18 liberi («Temporary 1»,
  «Villalba», «BGOMAN», «RAACampiBisenzio»…) e 10 vuoti. Vanno normalizzati una sola volta, con il codice che usa
  RetailTune (punto aperto 3), con un caricamento massivo dal pannello di Google Business Profile in una data fissata con
  RetailTune, fuori dalla finestra di passaggio, registrato nel registro con origine «migrazione». È l'unica eccezione
  alla regola «nessuna modifica a mano su Google».
- **Confronto a tre** (sito, RetailTune, export Google) dopo ogni importazione e ogni trimestre; le differenze si
  correggono in RetailTune. Prima del confronto i valori si normalizzano: telefono in formato internazionale
  (+39…) senza spazi; abbreviazioni dell'indirizzo sciolte («V.», «V.le», «P.zza»); URL senza parametri e senza barra
  finale.

Verifica: dopo la normalizzazione il confronto a tre non trova differenze su nome, indirizzo, telefono, sito e orari, e
ogni riga ha lo stesso codice sede nei tre sistemi.

## 5. Schede Google

### Account e gruppi

Sei gruppi di sedi (export del 26/10 in `kosgroup/dati/voce-24/gbp/`): Anni Azzurri (60), Centri ambulatoriali (15),
Extra Yext (26), Poliambulatori (14), Psichiatria (9), Riabilitazione (14). Danilo indica KOS come proprietario;
`docs/kos-24.md` riporta però che i profili sono collegati all'organizzazione Google di Yext, e nell'account di Danilo
il gruppo Anni Azzurri non compare. Il trasferimento della proprietà di tutti i gruppi a un account KOS va verificato
nel pannello **prima** che Yext venga dismesso (punto aperto 2): senza, si perde l'accesso alle schede.

### Schede da sistemare prima della migrazione

Dagli export del 26/10. La decisione su ciascuna spetta a KOS; quella indicata è la proposta.

| Scheda | Problema | Proposta |
| --- | --- | --- |
| Residenza Anni Azzurri La Margherita | Residenza chiusa (confermato il 19/10), scheda ancora pubblicata | Segnare come chiusa definitivamente, dopo aver esportato le recensioni |
| Residenza Anni Azzurri Villalba | Come sopra | Come sopra |
| Residenza Anni Azzurri Villa Olga | La pagina ha il canonical verso Idice | Verificare con KOS se la sede esiste; se no, chiusa come sopra |
| Villalba (codice «Villalba») e Poliambulatorio Villalba | Due schede, stesso URL | Una sola scheda (richiesta di unione dei duplicati a Google, non cancellazione) |
| Poliambulatorio Centro Cardinal Ferrari e Centro Cardinal Ferrari | Due schede, stesso URL | Una sola, salvo che il poliambulatorio abbia ingresso e orari propri: allora pagina propria e URL proprio |
| Polo Diagnostico Villa dei Pini e Villa dei Pini Civitanova Marche | Due schede, stesso URL | Come sopra |
| Anni Azzurri a Casa Monza e Anni Azzurri a Casa Milano | Due schede con area servita per lo stesso servizio, stesso URL | Una sola scheda con area servita (Monza e Milano), indirizzo nascosto |
| Residenze Anni Azzurri (ufficio aziendale), KOS Servizi S.c.ar.l. | Schede di uffici, non di sedi per il pubblico | Tenerle solo se l'ufficio riceve il pubblico; sito con l'URL canonico, non `http://www.` |
| Istituto Neuroscienze Neomesia | Sospesa, sito su neomesia.com | Capire con KOS il motivo della sospensione prima di qualsiasi modifica |
| Villamare Neomesia, Fisioclinics, Punto prelievi Centro Ambulatoriale Civitanova Marche, CSOx Centro Stress Ossidativo, Clinica Dentale Villa dei Pini | Sito su domini storici o esterni (neomesia.com, polodiagnostico.it, centrostressossidativo.it, laclinicadentale.com) | Nuovo sito = pagina corrispondente su kosgroup.com, da aggiornare **prima** dei reindirizzamenti dei domini storici (le linee guida vietano URL che reindirizzano) |
| Casa Sanfré | Sito vuoto | Pagina su kosgroup.com, se esiste; altrimenti punto aperto |
| Hospice San Giorgio a Colonica, Hospice Beati Coniugi Martin | Categoria principale «Casa di riposo» e «Ospedale» | Categoria coerente con l'hospice (punto aperto 4) |
| Centro Santo Stefano Cagli | Non pubblicata | Completare la verifica |

### Nome, indirizzo, telefono, sito

| Campo | Regola | Fonte |
| --- | --- | --- |
| Nome | Il nome ufficiale (`str.nome`), senza parole chiave aggiunte («RSA», città, servizi) | [Linee guida per la rappresentazione](https://support.google.com/business/answer/3038177) |
| Telefono | Solo numeri ufficiali della sede, gli stessi del sito e della carta dei servizi; niente numeri di tracciamento | Linee guida; regola del progetto |
| Sito | L'URL canonico della pagina della sede; nella scheda RetailTune aggiunge `utm_source=google&utm_medium=organic&utm_campaign=gbp` | Proposta a KOS del 29/10 |
| Indirizzo | Uguale a quello della pagina e del markup | Archivio |

Con `utm_medium=organic` il traffico dalle schede resta dentro «Organic Search» in GA4, distinguibile per campagna: è
una scelta voluta, da confermare con KOS insieme agli UTM.

Verifica: il nome di ogni scheda è uguale a `str.nome`; ogni campo «Sito» punta a `kosgroup.com` o `anniazzurri.it`, a una
pagina che risponde 200 con canonical uguale all'URL senza parametri.

### Categorie: residenze Anni Azzurri

Decise il 29/10, da validare con PWC **sui codici delle categorie**, non sui nomi italiani: nel selettore alcuni nomi
compaiono due volte e indicano categorie diverse («Casa di riposo», «Servizio di assistenza domiciliare»), e le
traduzioni non dicono abbastanza («Centro anziani» in inglese è un centro sociale per anziani). Il codice di ogni
categoria lo fornisce RetailTune (punto aperto 4).

Valgono per tutte le residenze insieme, in deroga alla proposta del piano (test su un sottogruppo): le linee guida
chiedono la stessa categoria per le sedi di una catena che offrono lo stesso servizio, e tutte le 57 residenze aperte sono
socio-sanitarie (carte dei servizi, `anniazzurri/dati/accreditamento-residenze-2026-10-29.xlsx`: 57 pagine; Ducale 2-3 e
PGR Milano raccolgono due unità ciascuna). Le residenze chiuse (tabella sopra) sono escluse.

| Categoria | Ruolo | Dove |
| --- | --- | --- |
| Casa di riposo (il codice che corrisponde a una struttura con assistenza sanitaria) | Principale; oggi è «Casa di cura» su 57 residenze | Tutte |
| Casa di cura | Aggiuntiva, solo se è un codice diverso dalla principale | Tutte |
| Assistenza agli anziani | Aggiuntiva, se il codice corrisponde a un servizio di assistenza agli anziani | Tutte |
| Centro anziani | Da togliere se il codice corrisponde a un centro sociale per anziani (oggi su 54) | — |
| Centro diurno per adulti | Aggiuntiva | Solo con centro diurno integrato |
| Centro di riabilitazione | Aggiuntiva | Solo con unità di riabilitazione (PGR Milano, PGR Cinisello, Mirasole, Rezzato, Dorica…) |
| Casa di cura per lungodegenti | Aggiuntiva | Solo con cure intermedie o post-acuzie (Villa Reale, San Luca) |
| Servizio di assistenza domiciliare | Aggiuntiva | Solo con RSA Aperta |
| Geriatra, Servizio di salute mentale | Da togliere | Oggi su 51 e 42 residenze |

Categoria Google e tipo schema.org seguono criteri diversi e non devono coincidere: la categoria si sceglie per ciò che
cercano le famiglie e per la regola delle catene, il tipo schema.org per l'autorizzazione della struttura
(`MedicalBusiness`, socio-sanitaria). Non è una contraddizione.

Motivazioni e fonti:

- Concorrenti verificati su Google Maps il 29/10: Korian/Clariane (8 schede), Sereni Orizzonti (9 su 10), Gruppo La Villa
  (8), Gheron usano «Casa di riposo» come principale. Nelle ricerche «RSA Milano» e «casa di riposo Torino» 19 risultati
  su 20 hanno «Casa di riposo». Le aggiuntive di Korian («Casa di cura», «Centro anziani», «Assistenza agli anziani») sono
  un indizio, non una motivazione: ogni aggiuntiva deve corrispondere a un servizio reale.
- Elenco delle categorie letto nel selettore di Google Business Profile il 29/10 (senza salvare), cercando «anzian»,
  «riposo», «cura», «assist», «residenz», «alzheimer», «demenz», «diurn», «riabilit», «domicil», «hospice», «palliat»,
  «disabil»: nessuna voce italiana per Alzheimer, demenza, residenza assistita o hospice come struttura. Nel catalogo
  globale esistono però categorie come hospice e assisted living facility: va controllato per codice con RetailTune se
  hanno un'etichetta italiana diversa.
- «Geriatra» e simili sono categorie per singoli medici: su Mirasole Google classifica la residenza come «doctor».
- Anomalie: Valdaso (principale «Centro di riabilitazione»), Conero («Servizio di salute mentale»), Borgomanero («Casa di
  riposo» senza aggiuntive).

Quando e come:

- Dopo la finestra di passaggio da Yext a RetailTune, durante la quale non si modificano i profili
  (`docs/piano-sprint-1.md`).
- A passi distanziati di 1-2 settimane, per non cambiare tutto insieme su 138 schede (molte modifiche simultanee possono
  portare a revisioni o sospensioni): prima i codici sede, poi i siti e gli UTM, poi le categorie.
- Misura: posizione nelle ricerche «RSA <città>» e «casa di riposo <città>» per 10 residenze, prima del cambio e dopo 4-6
  settimane. Il termine di confronto sono i concorrenti nelle stesse ricerche, non le strutture KOS (che in quelle
  ricerche non compaiono). Tornare indietro sulla categoria è possibile, ma la storia del posizionamento non si
  recupera: per questo la misura va preparata prima del cambio.

Verifica: nell'export Google tutte le residenze aperte hanno la stessa principale (stesso codice) e solo le aggiuntive
previste dalla tabella; nessuna ha «Geriatra» o «Servizio di salute mentale».

### Categorie: strutture KOS

Stesse regole (principale specifica, poche aggiuntive per reparti o servizi reali, mai categorie per singoli
professionisti), gruppo per gruppo, con gli stessi passi e la stessa misura. Dall'export del 26/10 sono da rivedere
almeno:

- categorie per professionisti su strutture: «Pediatra» (13), «Psicologo» (11), «Logopedista» (10) nei Centri
  ambulatoriali; «Fisiatra», «Fisioterapista» nella Riabilitazione; «Psichiatra» in Psichiatria ed Extra Yext;
- categorie estranee: «Sala biliardi» su 2 Centri ambulatoriali, «Automazione e robotica» in Extra Yext.

Verifica: come per le residenze, gruppo per gruppo.

### Reparti, servizi e professionisti

- **Reparti nella stessa sede**: una sola scheda, salvo che il reparto abbia ingresso, nome e gestione propri (regola
  dei reparti delle linee guida).
- **Servizi senza sede propria** (RSA Aperta, assistenza domiciliare): al massimo una scheda con area servita e
  indirizzo nascosto, come prevedono le linee guida; altrimenti la categoria aggiuntiva sulla residenza che li eroga.
- **Professionisti**: nessuna scheda per singolo medico creata dal progetto. Se KOS le vuole, è una decisione a parte
  (punto aperto 5).

## 6. Regole fisse

| Regola | Dove vale | Verifica |
| --- | --- | --- |
| Solo numeri di telefono ufficiali della sede | Scheda, pagina, markup, risposte | Confronto a tre |
| Comunicazione sanitaria solo informativa, senza elementi promozionali o suggestivi (L. 145/2018, art. 1 c. 525; Codice deontologico artt. 55-56) | Descrizione della scheda, post, pagine | Revisione dei testi prima della pubblicazione |
| Nelle risposte alle recensioni mai dati sanitari, mai confermare che l'autore è un paziente, mai citare cure | Risposte (gestite da RetailTune) | Campione mensile di 20 risposte |
| Richiesta di recensione uguale per tutti, senza incentivi né filtri sui soddisfatti | Processi di KOS | Istruzioni scritte al personale |
| Il markup descrive solo ciò che è scritto nella pagina | Sito | Rich Results Test sul codice |
| Nessuna modifica a mano su Google (eccezione: codici sede, §4) | Schede | Ogni trimestre: differenze tra export Google e dati RetailTune; ogni differenza senza riga nel registro è una modifica a mano. La colonna «Aggiornamenti Google» dell'export segnala anche le modifiche proposte da Google |

## 7. Registro delle modifiche alle schede

Ogni modifica a una scheda, fatta dal progetto o chiesta a RetailTune, entra in un registro unico per i due siti,
accanto agli export: `kosgroup/dati/voce-24/gbp/registro.csv`.

| Campo | Contenuto |
| --- | --- |
| data | AAAA-MM-GG |
| codice_sede | Codice negozio |
| scheda | Nome della scheda |
| campo | Nome, categoria principale, categorie aggiuntive, sito, telefono, orari, descrizione… |
| prima | Valore prima |
| dopo | Valore dopo |
| motivo | Regola di questa specifica o richiesta di KOS |
| richiesto_da | Persona |
| eseguito_da | Persona o RetailTune |
| origine | RetailTune, migrazione, Google |
| verifica | Data e esito del controllo sull'export successivo |

## 8. Stato

| Parte | Stato |
| --- | --- |
| Enti KOS S.p.A. e KOS Care, briciole, sito | In produzione |
| Libreria del nodo struttura (KOS voce 24) | In produzione spenta; si accende con l'import 2-6/11 |
| Dato strutturato delle residenze AA | Sprint 3 (27/11) |
| Medici e professionisti KOS | Sprint 3 (voce 11); widget per medico dopo l'esportazione dal gestionale verso TuoTempo |
| Società del gruppo, `#department`, `brand` come riferimento | Deciso il 29/10; implementazione con la tabella struttura → società |
| Archivio RetailTune e sincronizzazione delle schede | In avvio (contratto in firma) |
| Categorie delle residenze | Decise il 29/10, da validare con PWC sui codici |
| UTM | Proposti a KOS il 29/10 |
| Categorie KOS, codici sede, schede da sistemare, registro | Previsti con la migrazione, a passi |

## 9. Punti aperti

1. **Tabella struttura → società** che gestisce ciascuna struttura (la sta chiedendo Danilo).
2. **Proprietà dei profili**: trasferimento di tutti i gruppi da Yext a un account KOS, verificato nel pannello prima
   della dismissione di Yext.
3. **Codice sede di RetailTune**: quale usano per agganciare le schede, e data del caricamento massivo.
4. **Codici delle categorie** da RetailTune: le due «Casa di riposo», le due «Servizio di assistenza domiciliare»,
   «Casa di cura», «Centro anziani», «Assistenza agli anziani», hospice; quale usa Korian.
5. **Schede dei singoli medici**: KOS le vuole?
6. **Schede da sistemare** (tabella del §5): decisione di KOS su ciascuna.
7. **Importazione notturna da RetailTune**: canale (API), chi la gestisce, dove gira.
8. **Validazione delle categorie con PWC** e conferma degli UTM con KOS.

## 10. Riferimenti

- Google: [linee guida per la rappresentazione](https://support.google.com/business/answer/3038177),
  [scelta della categoria](https://support.google.com/business/answer/7249669), [dato strutturato LocalBusiness](https://developers.google.com/search/docs/appearance/structured-data/local-business);
  schema.org: [IndividualPhysician](https://schema.org/IndividualPhysician), [practicesAt](https://schema.org/practicesAt),
  [worksFor](https://schema.org/worksFor).
- Progetto: `CLAUDE.md`, `docs/convenzione-identificativi.md`, `docs/kos-24.md`, `docs/piano-sprint-1.md` (decisioni del 29/10),
  `kosgroup/dati/voce-24/gbp/` (export delle schede), `anniazzurri/dati/accreditamento-residenze-2026-10-29.xlsx`.
- Revisione indipendente del 29/10 (modello Fable 5.1, senza il contesto della discussione): 25 rilievi, tutti
  recepiti in questa versione salvo dove indicato nel §11.

## 11. Rilievi della revisione non recepiti o recepiti in parte

- **Categorie «tutte insieme» contro il test su un sottogruppo del piano**: recepito come deroga motivata (regola
  delle catene), non come ritorno al campione; il piano interno si aggiorna di conseguenza.
- **«Centro anziani» e «Assistenza agli anziani»**: non tolte d'ufficio; si decide sui codici delle categorie.
