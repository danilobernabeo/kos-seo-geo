# Analisi MioDottore per il Gruppo KOS

Danilo Bernabeo · 25 novembre 2026

## Sintesi

KOS è presente su MioDottore in modo parziale e non curato: 10 pagine attive su 18 strutture, quasi tutte con recapiti incompleti o superati, e solo 2 con il link a kosgroup.com. Il primo intervento utile è una correzione delle pagine, non un'integrazione tecnica.

- **Rapporto tra le società.** TuoTempo è nel gruppo Docplanner dal 2019. Il collegamento con MioDottore è il servizio Visibility di TuoTempo: le prenotazioni di MioDottore entrano nell'agenda TuoTempo. Quali dati anagrafici passano non è documentato.
- **Copertura.** 15 pagine su 18 strutture; 10 attive con 186 professionisti, 632 prestazioni e 2.733 recensioni; 5 vuote; 3 assenti (Valdaso, Gamma, Salus).
- **Sincronizzazione.** Descrizioni, foto, titoli e recapiti si modificano solo dentro MioDottore. L'API di Docplanner copre prestazioni, prezzi e agende, non i contenuti.
- **SEO/GEO.** Nessun sameAs verso MioDottore per ora. Tre telefoni diversi dal sito o malformati, tre link a domini storici, cinque nomi diversi da quelli ufficiali. Nelle ricerche con il nome della struttura le schede di kosgroup.com ricevono molti clic (fino a circa 15.800 in un anno per Villa dei Pini); da MioDottore verso il sito sono arrivate 3 visite in undici mesi. Il sorpasso nelle ricerche «prestazione + città» resta da misurare.
- **Da decidere con KOS.** Cosa copre il contratto, chi gestisce l'account, se la richiesta di recensioni è attiva nelle strutture psichiatriche e con quale ruolo Docplanner tratta i dati di chi prenota su MioDottore.
- **Proposta.** Subito le correzioni urgenti (sotto); poi correzione iniziale a cura di MioDottore e manutenzione dall'account della struttura (§3). Nessuno sviluppo tecnico per ora.

Limiti del lavoro: medici e prestazioni attesi per struttura non sono disponibili; gli orari non compaiono su MioDottore; l'abbinamento alle schede del sito è dedotto da nome e indirizzo; le posizioni nei risultati sono misurate solo per kosgroup.com e solo per le ricerche con il nome della struttura (§4).

## Correzioni urgenti

Queste differenze sbagliano l'informazione al paziente oggi, indipendentemente da ogni scelta sul canale. Si chiedono a chi gestisce l'account MioDottore, con i dati del sito:

- **Telefoni**: Villa dei Pini Civitanova (scritto +39 39 0733 7861, prefisso doppio), Villa San Giuseppe (031 6340515, sul sito 031 6340555), Villa Rosa Diagnostica (riporta 059 303034, che è il numero di Villa Rosa).
- **Link al sito**: polodiagnostico.it, villasangiuseppecdc.it e casadicuravillapini.it sono domini storici; il link va alla scheda della struttura su kosgroup.com. Villa dei Pini è la più urgente: dal 26/11 sul sito c'è anche la pagina della Clinica Dentale.
- **Pagine superate**: Villamare Neomesia (struttura trasferita a Ville di Nozzano) e Casa di Cura S.Alessandro - Istituto Neuroscienze Neomesia (nome superato).

## 1. TuoTempo e Docplanner/MioDottore

TuoTempo è una società del gruppo Docplanner dal 2019 e il collegamento con MioDottore è un suo prodotto, «Visibility». Le fonti pubbliche documentano il passaggio delle prenotazioni da MioDottore a TuoTempo; non documentano quali dati anagrafici passano. I contenuti dei profili (descrizioni, foto, titoli, indirizzi) non hanno un canale API pubblico.

### Proprietà

- L'ingresso di TuoTempo nel gruppo è stato annunciato il 29 ottobre 2019 ([Sanità Digitale, che riporta la nota ufficiale](https://sanita-digitale.com/cura/tuotempo-entra-in-gruppo-docplanner)). La stampa estera parla di acquisizione ([EU-Startups](https://www.eu-startups.com/2021/11/warsaw-based-digital-healthcare-unicorn-docplanner-acquires-munich-based-jameda/), [Saúde Business](https://www.saudebusiness.com/artigos/doctoralia-anuncia-aquisio-da-italiana-tuotempo-para-gesto-de-pacientes-em-clnicas-e-hospitais/)).
- TuoTempo ha mantenuto nome e offerta. Oggi Docplanner la vende come «CRM per pazienti per ospedali e grandi centri» accanto a MioDottore e GipoNext ([pro.miodottore.it](https://pro.miodottore.it/piani/miodottore-phone)).
- Non verificato: quota acquisita e data di chiusura. Il comunicato originale di Docplanner non è stato trovato: la nota ufficiale è citata dalla stampa.
- Già nel 2019 la nota indicava l'accesso a MioDottore per i clienti TuoTempo come uno degli scopi dell'operazione.

### Integrazione nativa: il servizio Visibility

Fonte: [tuotempo.it/prodotti/visibility](https://www.tuotempo.it/prodotti/visibility).

- La struttura che usa TuoTempo ottiene un profilo generale su MioDottore; i profili dei singoli medici si attivano a parte.
- Le prenotazioni fatte su MioDottore entrano nell'agenda di TuoTempo in tempo reale.
- TuoTempo può inviare ai pazienti la richiesta di recensione; le recensioni compaiono su MioDottore.
- La pagina non dice chi crea e aggiorna i profili, né il prezzo («formula vantaggiosa»).

### Quali dati passano e in quale direzione

- Le **prenotazioni** passano nei due sensi, in tempo reale; prestazioni, prezzi e agende vanno dal gestionale a MioDottore.
- **Nome, recapiti, descrizioni, foto e recensioni** esistono solo dentro MioDottore: nessuna API li modifica.
- **Quali dati anagrafici** dei pazienti passano non è documentato: va chiesto a TuoTempo (§5).

Il dettaglio, dato per dato, è nell'appendice.

## 2. Copertura delle 18 strutture

Su 18 strutture prenotabili in TuoTempo, 15 hanno una pagina su MioDottore: 10 con medici e prestazioni, 5 vuote. Tre non risultano. Consultazione del 25/11/2026, una pagina alla volta, dalla ricerca interna del sito.

Nelle 10 pagine attive ci sono in tutto 186 professionisti, 632 prestazioni e 2.733 recensioni.

| Struttura (TuoTempo)                                | Pagina MioDottore                                                                                                                                          | Professionisti | Prestazioni | Branche in TuoTempo | Nome, indirizzo, telefono rispetto al sito                                                                                            |
| --- | --- | --- | --- | --- | --- |
| Centro Cardinal Ferrari, Fontanellato               | [Centro Cardinal Ferrari](https://www.miodottore.it/strutture/centro-cardinal-ferrari)                                                                     | 4              | 28          | 8                   | Nome e indirizzo uguali; telefono e sito assenti                                                                                      |
| Centro Medico Riabilitativo, Cinisello Balsamo      | [Polo Geriatrico Riabilitativo - Cinisello](https://www.miodottore.it/strutture/polo-geriatrico-riabilitativo-cinisello)                                   | 0              | 0           | 19                  | Pagina vuota; stesso indirizzo, nome di un'altra unità                                                                                |
| Myolab, Jesi                                        | [Myolab](https://www.miodottore.it/strutture/myolab)                                                                                                       | 33             | 143         | 18                  | Coerente; sito = scheda di kosgroup.com                                                                                               |
| Poliambulatorio San Martino, Bollate                | [Poliambulatorio San Martino](https://www.miodottore.it/strutture/poliambulatorio-san-martino-2)                                                           | 26             | 90          | 17                  | Nome e indirizzo uguali; telefono e sito assenti                                                                                      |
| Poliambulatorio Santo Stefano, Porto Potenza Picena | [Poliambulatorio Santo Stefano](https://www.miodottore.it/strutture/poliambulatorio-santo-stefano)                                                         | 17             | 107         | 18                  | Coerente; sito = polodiagnostico.it/prenota (dominio storico)                                                                         |
| Poliambulatorio Valdaso, Campofilone                | Non trovata                                                                                                                                                |                |             | 14                  |                                                                                                                                       |
| Poliambulatorio Villalba, Macerata                  | [Poliambulatorio Villalba](https://www.miodottore.it/strutture/poliambulatorio-villalba)                                                                   | 34             | 49          | 22                  | Nome uguale; CAP e città ripetuti nell'indirizzo; telefono e sito assenti                                                             |
| Punto Prelievi Gamma, Fano                          | Non trovata                                                                                                                                                |                |             | 2                   |                                                                                                                                       |
| Punto Prelievi Salus, Macerata                      | Non trovata                                                                                                                                                |                |             | 1                   |                                                                                                                                       |
| Villa Margherita, Arcugnano                         | [Casa di Cura Privata Villa Margherita - Arcugnano](https://www.miodottore.it/strutture/casa-di-cura-villa-margherita)                                     | 1              | 6           | 4                   | Nome diverso; indirizzo uguale; telefono e sito assenti                                                                               |
| Villa Rosa, Modena                                  | [Villa Rosa Diagnostica](https://www.miodottore.it/strutture/villa-rosa-diagnostica)                                                                       | 2              | 62          | 10                  | Telefono 059 303034, che sul sito è di Villa Rosa; Villa Rosa Diagnostica ha 059 306335                                               |
| Villa San Giuseppe, Anzano del Parco                | [Poliambulatorio Villa San Giuseppe](https://www.miodottore.it/strutture/poliambulatorio-villa-san-giuseppe)                                               | 18             | 60          | 17                  | Telefono 031 6340515 (sito: 031 6340555); sito = villasangiuseppecdc.it (dominio storico)                                             |
| Villa Sant'Alessandro, Roma                         | [Casa di Cura S.Alessandro - Istituto Neuroscienze Neomesia](https://www.miodottore.it/strutture/casa-di-cura-s-alessandro-istituto-neuroscienze-neomesia) | 0              | 0           | 7                   | Pagina vuota; nome superato                                                                                                           |
| Villa dei Pini, Civitanova Marche                   | [Casa di Cura "Villa dei Pini" CIVITANOVA MARCHE](https://www.miodottore.it/strutture/casa-di-cura-villa-dei-pini-civitanova-marche)                       | 50             | 83          | 30                  | Nome con maiuscole e virgolette; telefono scritto +39 39 0733 7861 (prefisso doppio); sito = casadicuravillapini.it (dominio storico) |
| Ville di Nozzano, Nozzano San Pietro                | [Ville di Nozzano - Casa di cura neuropsichiatrica](https://www.miodottore.it/strutture/ville-di-nozzano-casa-di-cura-neuropsichiatrica)                   | 0              | 0           | 2                   | Pagina vuota; CAP 55050 Lucca (sito: 55100)                                                                                           |
| Poliambulatorio Villa Pineta, Modena                | [Poliambulatorio Privato Villa Pineta](https://www.miodottore.it/strutture/poliambulatorio-privato-villa-pineta)                                           | 0              | 0           | 3                   | Pagina vuota; «Via Scaglia Est 21-27» (sito: Strada Scaglia Est, 27)                                                                  |
| Villa Pineta, Pavullo nel Frignano                  | [Villa Pineta](https://www.miodottore.it/strutture/villa-pineta)                                                                                           | 1              | 4           | 12                  | Nome e indirizzo uguali; telefono e sito assenti                                                                                      |
| Centro Santo Stefano, Cagli                         | [Centro Ambulatoriale Di Riabilitazione S. Stefano Cagli](https://www.miodottore.it/strutture/centro-ambulatoriale-di-riabilitazione-s-stefano-cagli)      | 0              | 0           | 4                   | Pagina vuota; nome diverso                                                                                                            |

Come leggere la tabella:

- **Professionisti** è il numero di schede nella sezione «Il nostro team»: comprende medici e altri professionisti sanitari. Il numero atteso manca: serve l'elenco dei medici prenotabili per struttura (esportazione per areaid, §6).
- **Prestazioni** su MioDottore sono voci singole («Visita cardiologica»); **Branche in TuoTempo** sono le categorie dell'export del 26/10. Le due colonne non si confrontano una a una: serve l'elenco delle prestazioni per areaid.
- **Orari**: le pagine struttura di MioDottore non li mostrano. Il confronto non è possibile.
- **Non trovata** significa assente dalla ricerca interna e dall'elenco della città (Campofilone, Fano prima pagina). Non esclude una pagina con un nome diverso.
- L'abbinamento tra areaid e scheda di kosgroup.com è dedotto da nome e indirizzo. Il lato «sito» è l'archivio delle schede del 6/11.

Tre osservazioni che non stanno nella tabella:

- Solo 2 pagine su 15 rimandano a kosgroup.com. Tre rimandano a domini storici, dieci non hanno un sito.
- Le 5 pagine vuote sembrano schede di elenco create da MioDottore, non profili gestiti. Tre portano nomi che KOS non usa più o non usa per quella unità.
- Su MioDottore esiste ancora la pagina «Villamare Neomesia» (Lido di Camaiore), struttura trasferita a Ville di Nozzano.

## 3. Sincronizzazione dei contenuti

Nessun canale pubblico allinea in automatico descrizioni, foto, titoli e recapiti: questi dati si modificano solo dentro MioDottore. Prestazioni, prezzi e agende hanno invece un canale API. Le prenotazioni restano su TuoTempo in tutte le opzioni.

### Canali esistenti

| Canale                                                                        | Cosa allinea                                                                                                                                                               | Cosa non allinea                                     | Da chi dipende                                                                                                                                       |
| --- | --- | --- | --- |
| Visibility (TuoTempo → MioDottore)                                            | Prenotazioni; con ogni probabilità agende, prestazioni e prezzi                                                                                                            | Contenuti dei profili                                | TuoTempo: va chiesto quali campi invia                                                                                                               |
| [API Docplanner per i gestionali](https://integrations.docplanner.com/guide/) | Prestazioni per indirizzo (aggiunta, rimozione, prezzo, descrizione, visibilità), agende, prenotazioni, convenzioni assicurative; lettura di strutture, medici e indirizzi | Nome, indirizzo, telefono, descrizioni, foto, titoli | Docplanner: le credenziali le chiede la clinica al proprio referente Customer Success; un fornitore le ottiene solo con l'approvazione della clinica |
| Account della struttura su MioDottore                                         | Tutti i contenuti del profilo della struttura                                                                                                                              | —                                                    | KOS (contratto); che l'account possa modificare anche i profili dei medici non è verificato                                                          |
| Feed o caricamento massivo                                                    | Non documentato                                                                                                                                                            |                                                      | Da chiedere a MioDottore                                                                                                                             |

L'API ha un vincolo: è accettata in produzione solo un'integrazione completa (abbinamento di tutte le risorse, tutte le prenotazioni, agende in tempo reale). Non è pensata per un uso parziale accanto a TuoTempo. Un secondo accesso in sola lettura per KOS va chiesto espressamente.

### Opzioni

| Opzione                                                                       | Pro                                                                                                               | Contro                                                                                    |
| --- | --- | --- |
| A. Aggiornamento a mano dall'account della struttura, da una scheda-fonte KOS | Parte subito; nessuno sviluppo; copre tutti i contenuti                                                           | Una quindicina di pagine e circa 190 profili da tenere allineati a mano; deriva nel tempo |
| B. Solo TuoTempo (Visibility)                                                 | Già attivo; prestazioni, prezzi e agende seguono il gestionale                                                    | Non tocca nome, recapiti, descrizioni, foto, sito; non spiega le 5 pagine vuote           |
| C. API Docplanner in sola lettura, per il controllo                           | Confronto periodico e automatico con l'archivio KOS; fornisce gli identificativi MioDottore di strutture e medici | Non scrive i contenuti; l'API è pensata per un'integrazione completa e un accesso solo in lettura va concordato; richiede sviluppo |
| D. Caricamento iniziale a cura di MioDottore da un file KOS                   | Corregge in una volta nomi, recapiti, siti e pagine doppie o vuote                                                | Dipende dal contratto; non è un allineamento continuo                                     |

Proposta: D per la correzione iniziale, A per la manutenzione, con un controllo trimestrale a mano delle circa 15 pagine insieme a quello delle schede Google. B resta com'è. C solo se la misura del §4 mostra che il canale pesa sulle prenotazioni: per controllare una quindicina di pagine non vale lo sviluppo.

La fonte KOS è la stessa in ogni opzione: RetailTune per nome, indirizzo, telefono e orari; le schede MODX per descrizioni e pagine dei medici; TuoTempo per prestazioni e prezzi. L'abbinamento usa areaid per le strutture e l'identificativo della risorsa TuoTempo per i medici.

## 4. Ricadute SEO/GEO

Oggi nessun profilo MioDottore va aggiunto come sameAs: lo esclude la convenzione del progetto e, nella maggior parte dei casi, lo esclude lo stato delle pagine. La priorità è correggere nomi, recapiti e link al sito sulle pagine MioDottore.

### sameAs verso i profili MioDottore

- **Convenzione in vigore.** Il dato strutturato delle schede accoglie solo profili ufficiali, «mai directory di terzi»; la specifica ammette sameAs solo verso profili esterni ufficiali dello stesso soggetto. MioDottore è una directory di terzi: servirebbe una deroga scritta nel registro.
- **Strutture.** Una deroga avrebbe senso solo per le pagine che soddisfano tre condizioni: profilo gestito da KOS con il contratto, stessa entità della scheda (non Polo Geriatrico per il Centro Medico Riabilitativo, non Villa Rosa Diagnostica per Villa Rosa), nome e recapiti uguali all'archivio. Oggi le soddisfa, forse, solo Myolab.
- **Medici (IndividualPhysician).** Il profilo MioDottore è del professionista, non di KOS: può elencare studi di altri gruppi e lo gestisce il medico. Il sameAs sarebbe corretto come identità della persona, ma porta fuori dal sito verso una pagina con altri indirizzi. Se KOS lo vuole, serve l'abbinamento per identificativo della risorsa TuoTempo, mai per nome o codice fiscale, e il consenso del medico.
- **Verifica** (se la deroga passa): ogni URL in sameAs risponde 200, ha lo stesso nome del nodo e lo stesso indirizzo; controllo nel confronto trimestrale.

### Coerenza di nome, indirizzo e telefono

Su 10 pagine attive, 2 sono coerenti con il sito in tutti i campi presenti (Myolab, Poliambulatorio Santo Stefano). Le differenze sono nella tabella del §2. Le più rilevanti:

- telefoni diversi dal sito o malformati: Villa Rosa Diagnostica, Villa San Giuseppe, Villa dei Pini;
- link a domini storici: polodiagnostico.it, villasangiuseppecdc.it, casadicuravillapini.it. Sono gli stessi domini che la specifica toglie dalle schede Google;
- nomi che non corrispondono al nome ufficiale della scheda: Villa dei Pini, Villa Margherita, Cagli, Villa Sant'Alessandro, Cinisello.

Google non è stato ricontrollato in questo lavoro: il terzo lato del confronto resta l'export delle schede del 26/10.

### Recensioni

- Fatto: le pagine struttura mostrano le recensioni dei singoli professionisti, sommate. Villa dei Pini ne ha 1.226, Myolab 843, Villalba 314, San Martino 294.
- MioDottore pubblica su queste pagine il proprio dato strutturato MedicalClinic con aggregateRating (verificato su Myolab).
- Nel dato strutturato di kosgroup.com non entrano: [Google](https://developers.google.com/search/docs/appearance/structured-data/review-snippet) esclude le stelle per le recensioni che un'attività pubblica su sé stessa e chiede di non aggregare valutazioni di altri siti.
- Punto di attenzione: Visibility prevede l'invio automatico della richiesta di recensione dopo la visita. La specifica vieta la richiesta attiva nelle strutture psichiatriche. Tra le 18 ci sono Villa Rosa e Ville di Nozzano, più Villa Sant'Alessandro e Villa Margherita da confermare. Va verificato che l'invio sia spento per queste agende.

### Rischio che MioDottore superi kosgroup.com nei risultati

**Misura sulle ricerche con il nome della struttura** (Google Search Console di kosgroup.com, 22/11/2025-21/11/2026, ricerche contenenti il nome; dati aggregati):

| Struttura | Clic sulla scheda | Impressioni della scheda | Posizione media della scheda | Clic su tutto il sito |
| --- | --- | --- | --- | --- |
| Villa dei Pini, Civitanova Marche | 15.778 | 146.731 | 2,5 | 24.423 |
| Myolab, Jesi | 7.804 | 49.404 | 1,4 | 8.167 |
| Villa San Giuseppe, Anzano del Parco | 6.303 | 52.296 | 2,9 | 6.550 |
| Poliambulatorio Villalba, Macerata | 5.775 | 66.565 | 3,2 | 6.410 |
| Villa Rosa, Modena | 5.670 | 74.224 | 4,4 | 7.161 |
| Villa Pineta, Pavullo nel Frignano | 4.357 | 37.975 | 2,9 | 6.065 |
| Centro Cardinal Ferrari, Fontanellato | 3.341 | 22.418 | 2,7 | 3.389 |
| Villa Margherita, Arcugnano (riabilitazione) | 1.936 | 59.655 | 5,3 | 15.803 |
| Poliambulatorio San Martino, Bollate | 1.470 | 12.549 | 4,7 | 1.476 |
| Poliambulatorio Santo Stefano, Porto Potenza Picena | 1.051 | 30.502 | 2,8 | 4.940 |

Come leggerla: «Clic su tutto il sito» comprende le altre pagine che rispondono alla stessa ricerca (per Villa Margherita anche la psichiatria di Vicenza, per Santo Stefano le altre sedi). Nessuna ricerca contiene «miodottore» insieme al nome di una struttura. Le posizioni medie fra 4 e 5,3 (Villa Rosa, San Martino, Villa Margherita) sono le schede dove altri risultati, MioDottore compreso, stanno più spesso sopra il sito: sono le prime da controllare a mano.

**Visite da MioDottore al sito** (GA4 di kosgroup.com, dati dal 30/12/2025): 3 sessioni in undici mesi, una ciascuna su Villa dei Pini, Poliambulatorio San Martino e un approfondimento. Con 2 pagine su 15 che rimandano a kosgroup.com, MioDottore oggi non porta traffico al sito. GA4 conta solo le visite con il consenso alle statistiche: il dato è un ordine di grandezza.

Resta da misurare il sorpasso nelle ricerche «prestazione + città». Gli elementi che lo rendono plausibile sono tre.

- Le pagine MioDottore hanno titolo «Nome a Città», stelle da recensioni di terzi e prezzi: nelle ricerche «prestazione + città» partono avvantaggiate rispetto a una scheda struttura.
- Docplanner ha annunciato nel 2024 il collegamento con «Prenota con Google»: il pulsante sul profilo Google porta all'agenda sul marketplace ([Sanità Digitale, 12/6/2024](https://www.sanita-digitale.com/tendenze/google-la-piu-grande-integrazione-nel-settore-sanitario)). Se è attivo sui profili delle strutture KOS, il traffico di prenotazione passa da MioDottore invece che dalla barra TuoTempo del sito. Da verificare scheda per scheda.
- Otto pagine attive su dieci non rimandano a kosgroup.com: MioDottore riceve visibilità dal nome KOS senza restituire un collegamento.

Misura ancora da fare: per le 10 strutture con pagina attiva, posizione di miodottore.it nella ricerca del nome e in due ricerche «prestazione + città»; presenza del pulsante «Prenota» e sua destinazione sui profili Google; le prenotazioni arrivate da MioDottore per struttura, dalle statistiche di MioDottore o di TuoTempo (§6).

## 5. Domande da porre

Le domande a MioDottore e a TuoTempo le pone KOS: questo lavoro non ha contattato nessuno dei due.

### A KOS

1. Il contratto con MioDottore copre quali strutture e quanti profili di medici? È il servizio Visibility di TuoTempo o un contratto separato con Docplanner Italy?
2. Chi in KOS ha l'accesso all'account della struttura su MioDottore e chi aggiorna oggi le pagine?
3. Le 5 pagine vuote e le 3 strutture senza pagina sono fuori contratto per scelta?
4. Per le strutture psichiatriche, la presenza su MioDottore e la richiesta di recensioni sono volute?
5. KOS vuole i profili MioDottore nel dato strutturato (sameAs)? Serve una deroga alla convenzione del progetto.
6. I medici hanno firmato un consenso alla pubblicazione del profilo su MioDottore? Con quale testo?
7. Per Villa Rosa: l'istanza TuoTempo «Villa Rosa - Modena» corrisponde a Villa Rosa o a Villa Rosa Diagnostica?
8. Per il DPO: con quale ruolo Docplanner tratta i dati di chi prenota su MioDottore (titolare autonomo del proprio servizio o responsabile per KOS) e quali dati arrivano a KOS tramite TuoTempo? È previsto nell'informativa dei siti e nel registro dei trattamenti?

### A MioDottore (Docplanner Italy)

1. Quali campi del profilo struttura e dei profili dei medici può modificare l'account della struttura? Esiste un caricamento massivo da file?
2. È possibile un accesso in sola lettura all'API per KOS, accanto all'integrazione TuoTempo?
3. Come si correggono nome, telefono e sito di una pagina, e come si uniscono o si chiudono le pagine vuote, superate o doppie (Villamare Neomesia, S.Alessandro Neomesia, Polo Geriatrico)?
4. «Prenota con Google» è attivo sui profili Google delle strutture KOS? Si può disattivare per struttura?
5. La richiesta automatica di recensione si può spegnere per struttura o per agenda?
6. Il link «Sito web» può puntare alla scheda di kosgroup.com per ogni struttura e per ogni medico?

### A TuoTempo

1. Quali dati invia Visibility a MioDottore: prestazioni, prezzi, agende, anagrafica dei medici? Con quale frequenza?
2. Qual è la tabella di abbinamento tra areaid, risorse (medici) e identificativi MioDottore? Si può esportare?
3. Una prestazione nascosta in TuoTempo sparisce anche da MioDottore?

## 6. Dati da chiedere a KOS

| Dato                                                                                                                                                  | A cosa serve                                                  |
| --- | --- |
| Contratto con MioDottore/Docplanner: piano, strutture e numero di profili inclusi, durata, costo, clausole su contenuti, recensioni e uso del marchio | Capire cosa è già pagato e cosa si può chiedere               |
| Contratto TuoTempo, parte Visibility                                                                                                                  | Sapere se MioDottore è un modulo TuoTempo o un contratto a sé |
| Account della struttura su MioDottore: titolare, utenti, ruoli; accesso in lettura per Danilo                                                         | Verificare cosa si può modificare, senza modificare           |
| Referente KOS per MioDottore e referenti Customer Success di MioDottore e di TuoTempo                                                                 | Porre le domande del §5                                       |
| Elenco dei medici e delle prestazioni prenotabili per areaid (esportazione da TuoTempo o dal database del sito)                                       | Completare le colonne «attesi» della copertura                |
| Tabella di abbinamento TuoTempo ↔ MioDottore (strutture, medici, prestazioni)                                                                         | Abbinare i profili senza usare nomi o codici fiscali          |
| Consensi dei medici alla pubblicazione su MioDottore                                                                                                  | Decidere su sameAs e sull'allineamento dei profili            |
| Statistiche MioDottore per struttura: visite alle pagine, prenotazioni, provenienza                                                                   | Pesare il canale rispetto al sito                             |
| Decisione sulle strutture psichiatriche: presenza e recensioni                                                                                        | Applicare la regola della specifica                           |

## 7. Prossimi passi

Nessuno di questi passi tocca i siti: procedono in parallelo alla verifica dello sprint 4 (dal 30/11) e alla decisione sulla nuova ricerca (4/12).

| Quando | Chi | Cosa |
| --- | --- | --- |
| Entro il 4/12 | KOS | Correzioni urgenti a chi gestisce l'account MioDottore; risposte alle domande del §5 rivolte a KOS; dati del §6 |
| Entro il 4/12 | KOS (DPO) | Ruolo di Docplanner sui dati delle prenotazioni (§5, domanda 8) |
| Dopo le risposte | KOS con MioDottore e TuoTempo | Domande del §5; richiesta della correzione iniziale (opzione D) |
| Dopo le risposte | Danilo Bernabeo | Misura delle ricerche «prestazione + città» e dei pulsanti «Prenota»; file KOS per la correzione iniziale; scheda-fonte per la manutenzione |
| Ogni trimestre | Chi gestisce l'account, con Danilo Bernabeo | Controllo delle pagine MioDottore insieme alle schede Google |

## Appendice: dati che passano tra gestionale e MioDottore

La colonna «API Docplanner» descrive l'API pubblica per i gestionali. Che TuoTempo usi questa API o un connettore interno al gruppo non è documentato: va chiesto.

| Dato                                 | Direzione                                                          | API Docplanner                                                                                          | Stato                                       |
| --- | --- | --- | --- |
| Sede (struttura)                     | Creata in MioDottore                                               | Sola lettura; la struttura è assegnata a mano dal personale Docplanner su richiesta scritta del cliente | Documentato                                 |
| Nome, indirizzo, telefono della sede | Gestiti in MioDottore                                              | Nessuna operazione di modifica                                                                          | Documentato (assenza)                       |
| Medici                               | Profili creati in MioDottore, abbinati alle risorse del gestionale | Sola lettura                                                                                            | Documentato                                 |
| Descrizioni, foto, titoli            | Gestiti in MioDottore                                              | Nessuna operazione                                                                                      | Documentato (assenza)                       |
| Prestazioni                          | Dal gestionale a MioDottore, scelte dal dizionario di MioDottore   | Scrittura: aggiunta, rimozione, descrizione, visibilità                                                 | Documentato per l'API; non per TuoTempo     |
| Prezzi                               | Dal gestionale a MioDottore                                        | Scrittura: prezzo e «a partire da» per prestazione                                                      | Documentato per l'API; non per TuoTempo     |
| Agende (disponibilità)               | Dal gestionale a MioDottore                                        | Scrittura, in tempo reale                                                                               | Documentato per l'API; per TuoTempo dedotto |
| Prenotazioni                         | Nei due sensi                                                      | Scrittura e notifiche                                                                                   | Documentato, anche per TuoTempo             |
| Recensioni                           | Solo su MioDottore                                                 | Nessuna operazione                                                                                      | Documentato (assenza)                       |

L'abbinamento tra oggetti del gestionale e oggetti di MioDottore (struttura, medico, indirizzo, prestazione) è un passaggio obbligato dell'integrazione ([processo di integrazione](https://integrations.docplanner.com/guide/integration-process.html)). Un medico che cambia le prestazioni dall'interfaccia di MioDottore può far cambiare gli identificativi abbinati.

## Fonti

Pagine aperte il 25/11/2026.

- Docplanner, documentazione per sviluppatori: [guida](https://integrations.docplanner.com/guide/), [risorse](https://integrations.docplanner.com/guide/api-objects/resources.html), [processo di integrazione](https://integrations.docplanner.com/guide/integration-process.html), [estensioni](https://integrations.docplanner.com/guide/fundamentals/extensions.html)
- TuoTempo: [Visibility](https://www.tuotempo.it/prodotti/visibility), [API e integrazione](https://www.tuotempo.it/api-integrazioni), [home](https://www.tuotempo.it/)
- MioDottore: [termini e condizioni](https://www.miodottore.it/termini-e-condizioni) (§5 e §9: uso non eccessivo, divieto di estrarre o riprodurre i contenuti senza permesso scritto); le 15 pagine struttura della tabella del §2; gli elenchi di [Campofilone](https://www.miodottore.it/strutture/campofilone), [Cagli](https://www.miodottore.it/strutture/cagli), [Fano](https://www.miodottore.it/strutture/fano), [Cinisello Balsamo](https://www.miodottore.it/strutture/cinisello-balsamo)
- Stampa: [Sanità Digitale, 29/10/2019](https://sanita-digitale.com/cura/tuotempo-entra-in-gruppo-docplanner); [Sanità Digitale, 12/6/2024](https://www.sanita-digitale.com/tendenze/google-la-piu-grande-integrazione-nel-settore-sanitario)
- Google: [dato strutturato delle recensioni](https://developers.google.com/search/docs/appearance/structured-data/review-snippet)
- Progetto SEO/GEO: registro delle decisioni, specifica del dato strutturato, esportazione delle branche TuoTempo per sede (26/10/2026), archivio delle schede delle strutture (6/11/2026), elenco delle 18 strutture TuoTempo
- Misure: Google Search Console e GA4 di kosgroup.com (esportazione in BigQuery del progetto KOS), consultate il 25/11/2026 in forma aggregata
