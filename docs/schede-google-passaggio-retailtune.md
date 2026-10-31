# Schede Google: nomi, siti e categorie nel passaggio a RetailTune

Danilo Bernabeo · 2 novembre 2026

Con la migrazione da Yext a RetailTune, nomi, siti e categorie delle 138 schede Google di KOS e Anni Azzurri passano a
un nuovo strumento, che da quel momento li sincronizza. Questo documento fissa le regole per quei tre dati prima che
RetailTune inizi a scriverli. Tiene conto del documento «Local Ottimizzazioni e Linee Guida» del 27 ottobre.

## 1. Il passaggio non cambia niente; i cambiamenti vengono dopo, uno alla volta

| Fase | Quando | Cosa |
| --- | --- | --- |
| Prima | Fino al passaggio | Correzione degli errori che oggi portano le persone nel posto sbagliato (siti che reindirizzano altrove, schede di strutture chiuse), con Yext, che alimenta le schede fino al passaggio |
| Passaggio | Giornata del cambio e due settimane successive | I dati passano senza modifiche: nomi, codici negozio, categorie e siti restano come sono. Nessuna modifica alle schede in quelle due settimane |
| Dopo | A passi distanziati di una o due settimane | Prima i siti con i parametri UTM, poi le categorie delle residenze, poi quelle delle strutture KOS |

Il motivo dei passi: ogni modifica passa una revisione di Google e una nuova categoria può richiedere una nuova verifica
dell'attività. Cambiando una cosa per volta si limitano le verifiche e ogni effetto si attribuisce a un solo cambiamento.
Le posizioni nelle ricerche sono misurate prima e dopo ogni passo.

## 2. Nomi delle schede: restano quelli attuali

Il nome della scheda è il nome reale della struttura, lo stesso che compare sull'insegna, nella pagina del sito e nel dato
strutturato. Non si aggiungono parole chiave come «RSA», la città o i servizi.

Perché:

- **Le regole di Google lo vietano.** Le [linee guida per la rappresentazione](https://support.google.com/business/answer/3038177)
  chiedono il nome usato nel mondo reale ed escludono esplicitamente parole chiave, località e servizi aggiunti.
- **Il rischio è su tutta la catena.** Le schede di un gruppo con nomi modificati possono essere sospese insieme. Su 60
  residenze il danno sarebbe molto più grande di qualsiasi guadagno di posizione.
- **Coerenza tra le fonti.** Google confronta il nome della scheda con quello del sito e delle directory. Un nome uguale
  dappertutto rafforza l'identità della struttura; un nome diverso la indebolisce.
- **«RSA» ha un significato preciso.** Indica un'autorizzazione e un regime di accreditamento che va verificato struttura
  per struttura.

Le parole chiave hanno altri posti, consentiti e utili: la categoria (sezione 4), i servizi della scheda, la descrizione
informativa e il titolo della pagina del sito.

## 3. Sito della scheda: l'URL canonico, con parametri UTM aggiunti solo verso Google

| Dato | Regola |
| --- | --- |
| Sito nell'archivio RetailTune | L'URL canonico della pagina della struttura su kosgroup.com o anniazzurri.it, che risponde senza reindirizzamenti |
| Sito pubblicato nella scheda | Lo stesso URL con i parametri aggiunti da RetailTune nella sincronizzazione: `?utm_source=google&utm_medium=organic&utm_campaign=gbp` |
| Link per appuntamenti e prenotazioni | La pagina di prenotazione della struttura, senza reindirizzamenti né parametri di sessione |

Con `utm_medium=organic` le visite dalle schede restano nel canale «Organic Search» di Google Analytics e si distinguono
dalla campagna `gbp`. Tenendo i parametri fuori dall'archivio, le pagine del sito e il dato strutturato restano sull'URL
pulito.

**Da confermare:** i valori dei parametri, se KOS o l'agenzia usano già una convenzione diversa.

## 4. Categorie

Le regole sono tre:
- la categoria principale descrive la struttura, non i professionisti che ci lavorano;
- le categorie aggiuntive corrispondono a servizi o reparti che esistono davvero;
- le strutture di una catena che offrono lo stesso servizio hanno la stessa categoria principale.

Le categorie si indicano per codice, non per nome: in italiano alcuni nomi compaiono due volte e indicano categorie
diverse (per esempio «Casa di riposo»). I codici li conferma RetailTune prima del passaggio. RetailTune mantiene le
categorie principali attuali e non ne cambia nessuna senza accordo scritto.

### Residenze Anni Azzurri (57 residenze aperte)

| Categoria | Ruolo | Dove |
| --- | --- | --- |
| Casa di riposo | Principale (oggi «Casa di cura») | Tutte |
| Casa di cura | Aggiuntiva | Tutte |
| Assistenza agli anziani | Aggiuntiva | Tutte |
| Centro diurno per adulti | Aggiuntiva | Solo con centro diurno integrato |
| Centro di riabilitazione | Aggiuntiva | Solo con unità di riabilitazione |
| Casa di cura per lungodegenti | Aggiuntiva | Solo con cure intermedie o post-acuzie |
| Servizio di assistenza domiciliare | Aggiuntiva | Solo dove la residenza eroga RSA Aperta |
| Geriatra, Servizio di salute mentale | Da togliere | Oggi su 51 e 42 residenze |
| Centro anziani | Da togliere, se il codice indica un centro sociale per anziani | Oggi su 54 residenze |

Motivazioni:
- I concorrenti diretti (Korian, Sereni Orizzonti, Gruppo La Villa, Gheron) usano «Casa di riposo» come principale. Nelle
  ricerche «RSA Milano» e «casa di riposo Torino», 19 risultati su 20 hanno questa categoria.
- «Geriatra» è la categoria di un singolo medico: su una residenza porta Google a classificarla come studio medico.
- Il selettore italiano non ha categorie per Alzheimer, demenza, residenza assistita o hospice. Se esistono nel catalogo
  globale con un codice diverso, RetailTune lo verifica.

### Strutture KOS

| Gruppo | Principale proposta | Aggiuntive | Da togliere |
| --- | --- | --- | --- |
| Centri e ospedali di riabilitazione | Centro di riabilitazione; «Ospedale» o «Ospedale specializzato» dove la struttura è un ospedale (Villa Pineta, Villa dei Pini Civitanova, Venerabile Marcucci) | Clinica di fisioterapia, se c'è un ambulatorio; Clinica per la salute mentale, se c'è un reparto psichiatrico; Centro diagnostico, se aperto al pubblico | Fisiatra, Fisioterapista |
| Centri ambulatoriali Santo Stefano | Centro di riabilitazione (oggi «Centro medico» su 10 schede) | Clinica di fisioterapia | Pediatra, Psicologo, Logopedista, Audiologo, Centro anziani |
| Cliniche psichiatriche | Clinica per la salute mentale (già su 7 schede su 9) | Nessuna, salvo servizi aperti al pubblico | Psichiatra |
| Comunità terapeutiche psichiatriche e per minori | Servizio di salute mentale (oggi «Psichiatra» su 10 schede) | Nessuna | Psichiatra |
| Poliambulatori | Centro medico (oggi su 2; altre principali: Ospedale, Laboratorio medico, Clinica medica) | Centro diagnostico, Laboratorio medico, Clinica di fisioterapia, se il servizio c'è | Banca del sangue, Automazione e robotica |
| Punti prelievo e laboratori | Laboratorio medico | Servizio di analisi del sangue | Banca del sangue |
| Diagnostica per immagini | Centro di diagnostica per immagini o Centro diagnostico | Laboratorio di radiologia | — |
| Cliniche dentali | Clinica dentale | Dentista, Ortodontista, Endodontista solo se la specialità è erogata | — |
| Medicina dello sport (Myolab, Fidia) | Clinica di medicina dello sport | Clinica di fisioterapia | Sala biliardi, Pediatra |
| Hospice | Da decidere per codice con RetailTune: in italiano non c'è una categoria per l'hospice come struttura | — | Casa di riposo, Ospedale come principale |

Le categorie per professionista come «Psichiatra», «Pediatra» e «Fisiatra» descrivono un singolo medico. Sulla scheda di
una struttura fanno classificare l'intera sede come studio medico, e la allontanano dalle ricerche per tipo di struttura.

## 5. Cosa chiediamo

- **A KOS:** conferma delle regole delle sezioni 2 e 3, e dei valori UTM.
- **All'agenzia:** un riscontro sulle categorie della sezione 4 entro la call del 4 novembre, in particolare sui centri
  ambulatoriali e sulle comunità terapeutiche, dove la proposta cambia la categoria principale.
- **A RetailTune:** i codici di tutte le categorie indicate, prima dell'import.
