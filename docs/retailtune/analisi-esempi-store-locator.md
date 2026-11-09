# Store locator RetailTune: analisi degli esempi e requisiti di design

Danilo Bernabeo · 9 novembre 2026

## In breve

Per kosgroup.com e anniazzurri.it propongo come riferimento lo store locator di **OVS**: mappa con elenco laterale e **pannello di dettaglio** che prende il posto dell'elenco, con il rimando finale alla pagina della struttura. Da noi il terzo livello sono le schede struttura già esistenti sui due siti, perché RetailTune non pubblica pagine proprie. Gli altri esempi danno spunti puntuali, elencati più avanti.

Le scelte di configurazione sono già fissate nel primo invio a RetailTune del 2 novembre: due istanze separate (una per sito), filtri preimpostati pagina per pagina, nessuna pagina struttura pubblicata da RetailTune, consenso gestito con Cookiebot, dati letti dai siti tramite l'API. Questo documento riguarda solo l'aspetto e il comportamento del widget.

## Gli esempi esaminati

| Esempio | Link | Cosa mostra | Da prendere |
| --- | --- | --- | --- |
| OVS | [ovs.it/it/it/stores](https://www.ovs.it/it/it/stores) | Elenco, pannello di dettaglio, pagina negozio sul dominio principale; pulsante «Filtra» con reparti, servizi e marchi | Il modello di riferimento (vedi sotto). Da evitare: dopo l'applicazione i filtri attivi non si vedono più |
| Plenitude | [eniplenitude.com/energy-store](https://eniplenitude.com/energy-store) | Tendina «Tutte le tipologie» accanto alla ricerca per città o CAP; sezione «Qual è lo store più adatto a te?» con i servizi di ogni tipo di punto; sezione «Per saperne di più» con approfondimenti e contatti; pulsante di condivisione su ogni punto vendita | La tendina delle tipologie; le due sezioni nella pagina, fuori dal widget; la condivisione, ampliata (vedi sotto). Da evitare la versione mobile: richiesta di posizione a pagina aperta, mappa a tutta larghezza prima dell'elenco, elenco in un riquadro con scorrimento interno |
| Percassi | [percassi.com/store-locator](https://www.percassi.com/store-locator/) e pagina marchio [LEGO](https://www.percassi.com/portfolio/lego/) | Mappa generale e, nella pagina del marchio, il pulsante «Trova lo store più vicino» verso la sezione negozi | Lo schema del widget filtrato nelle pagine interne |
| CR Lab | [crlab.com/it_it/storelocator](https://crlab.com/it_it/storelocator) | Scheda nell'elenco con etichette delle specialità, stato «Chiuso. Riapre martedì alle ore 08:00», riquadro a scomparsa «Trattamenti disponibili in questo centro» con icone; «Prenota una consulenza» nel menu del sito | Il riquadro a scomparsa dei servizi, per le prestazioni delle strutture KOS (vedi sotto); il messaggio di riapertura per ambulatori e punti prelievo |

Nota: OVS, CR Lab e Plenitude li ho provati dal vivo. Percassi carica tutto via JavaScript: l'ho visto nella struttura, non nel comportamento su mobile.

## Il modello OVS

Il dettaglio di un negozio si apre in tre modi:

1. **Dalla scheda nell'elenco:** «Maggiori info» sostituisce l'elenco con il pannello di dettaglio, nella stessa colonna. La mappa resta visibile e si centra sul negozio; la X in alto riporta all'elenco nella posizione in cui era.
2. **Dal pin sulla mappa:** un fumetto con nome, indirizzo, stato di apertura e telefono, insieme al pannello di dettaglio.
3. **Dal pannello:** «Vai alla pagina negozio» porta alla pagina del negozio sul dominio principale (esempio: [OVS Roma Tritone](https://www.ovs.it/it/it/stores/italia/lazio/roma/ovs-roma-tritone)).

Da noi il terzo passaggio porta alla scheda della struttura su kosgroup.com o anniazzurri.it (campo `link` dell'API, uguale all'URL canonico). Il comportamento standard di RetailTune porta invece subito alla pagina negozio: va chiesto il pannello di dettaglio come secondo livello.

## Requisiti di design proposti

### Scheda nell'elenco

- Nome, etichetta della tipologia (anche più di una, es. Ospedale San Pancrazio), indirizzo, distanza dopo una ricerca, telefono cliccabile.
- Due pulsanti: «Scheda» (la nostra pagina) e «Indicazioni» (link a Google Maps, vedi sotto).
- Contatore dei risultati.
- Niente «Aperto / Chiuso» per residenze, hospice e comunità, aperte tutto il giorno.

### Pannello di dettaglio

- Foto della struttura (per le residenze è già disponibile).
- Telefono ed email in chiaro.
- Orari con un'etichetta per tipologia: «Accoglienza e informazioni» per le residenze, «Prelievi» per i punti prelievo, «Orari» per gli ambulatori.
- Servizi e aree di cura con icone (fase 2), con link alle pagine dei servizi del sito.
- **Prestazioni in un riquadro a scomparsa** (sul modello di CR Lab), chiuso all'apertura del pannello: «Prestazioni disponibili in questa struttura (24)». Per i poliambulatori e i centri con molte prestazioni le voci sono raggruppate per area (es. Diagnostica per immagini, Visite specialistiche, Riabilitazione), ciascuna con il link alla pagina del servizio e, dove c'è, alla prenotazione online; oltre una certa lunghezza il riquadro rimanda alla scheda della struttura per l'elenco completo. Per le residenze lo stesso riquadro elenca servizi e nuclei (Alzheimer, sollievo, centro diurno).
- Per ambulatori e punti prelievo, stato con la prossima apertura (es. «Chiuso. Riapre lunedì alle 7:30»).
- Azione principale per tipologia: «Richiedi informazioni» per le residenze, «Prenota» per gli ambulatori con prenotazione online.
- «Indicazioni»: link al profilo della struttura su Google Maps (campo già previsto nell'API), senza calcolo del percorso nel widget.
- «Condividi» (vedi sotto).
- In fondo: «Vai alla scheda della struttura».

### Indicazioni stradali

Il pulsante «Indicazioni» apre il profilo della struttura su Google Maps invece di calcolare il percorso dentro il widget, come fa il pulsante «portami qui» di molti store locator:

- il calcolo nel widget usa servizi a pagamento di Google Maps, con fatturazione sull'account di KOS; il link a Google Maps è gratuito;
- Google Maps offre già percorso a piedi, in auto e con i mezzi, apre l'app sul telefono e da computer permette di inviare il percorso al telefono;
- le richieste di indicazioni dal profilo si sommano alle statistiche della scheda Google della struttura.

Da chiedere a RetailTune quali servizi Google a pagamento usa il widget (mappa, completamento dell'indirizzo nella ricerca, geolocalizzazione, percorsi), per spegnere quelli non necessari.

### Condivisione

Plenitude ha su ogni punto vendita un pulsante di condivisione che apre una finestra con il link a Google Maps da copiare. Per le nostre strutture è utile, perché la scelta di una residenza o di un centro si fa spesso in famiglia. Propongo di ampliarlo:

- nella stessa finestra, oltre a «Copia link», il pulsante **WhatsApp** (un semplice link `wa.me` con il testo già pronto, senza costi né integrazioni) e, da telefono, il menu di condivisione del sistema, che offre anche SMS, email e le altre app;
- testo condiviso: nome della struttura, indirizzo, telefono, link alla nostra scheda e link al profilo Google Maps (Plenitude condivide solo le coordinate per il percorso).

### Widget nelle pagine interne

- Versione compatta: filtri nascosti, ricerca per indirizzo attiva, link «Vedi tutte le strutture» alla mappa generale.
- Contenitore ad altezza fissa definita dal sito, per non spostare il contenuto durante il caricamento.
- Prima del consenso alla mappa: immagine statica con «Mostra la mappa»; l'elenco resta visibile.

### Mobile

- Elenco prima della mappa, con interruttore «Elenco / Mappa».
- L'elenco scorre con la pagina, non in un riquadro con scorrimento proprio dentro la pagina (su Plenitude da telefono si scorre la pagina o l'elenco a seconda di dove si appoggia il dito).
- «Chiama» sempre visibile nel pannello di dettaglio.

### Filtri

- Accanto alla ricerca, una tendina «Tutte le tipologie» (come Plenitude) per la scelta più frequente.
- Per gli altri criteri un pulsante «Filtri» che apre un pannello a gruppi (come OVS): marchio e aree di cura su kosgroup.com; servizi e nuclei su anniazzurri.it, dove le voci sono poche e possono stare anche in vista.
- **Importante: filtri attivi sempre visibili** come etichette sopra l'elenco, ognuna con la X per toglierla, anche quando arrivano preimpostati dalla pagina. OVS non li mostra dopo l'applicazione: l'utente vede meno strutture senza sapere perché e può concludere che nella sua zona non c'è ciò che cerca.
- Numero dei risultati sul pulsante di conferma («Mostra 23 strutture») e contatore sempre aggiornato.
- Nei widget delle pagine interne, dove i filtri sono fissi e nascosti, un'intestazione dice cosa si sta vedendo (es. «Residenze in provincia di Lodi · 6 strutture») con il link alla mappa generale.
- Se un filtro azzera i risultati, il messaggio lo dice e propone la struttura più vicina e il pulsante «Togli i filtri».
- Da chiedere a RetailTune: i filtri si possono passare nell'indirizzo della pagina (es. `/it/strutture?tipologia=centri-riabilitazione&citta=milano`)? Servirebbe a portare la nuova ricerca del sito, le newsletter e le campagne direttamente sulla mappa filtrata.

### Comportamenti generali

- Nessuna richiesta di posizione all'apertura della pagina: la geolocalizzazione parte solo dal pulsante «Vicino a me».
- Se nella zona cercata non ci sono strutture, il widget propone la più vicina con la distanza.
- Ordine dei risultati per distanza dal punto cercato.
- Pin distinti per strutture sanitarie e residenze.
- Elenco utilizzabile da tastiera e con lettore di schermo; colori dai token già consegnati.
- Niente recensioni e niente promozioni.

## Fuori dal widget, nelle pagine del sito

Due sezioni scritte da noi nella pagina della mappa, sul modello di Plenitude, testo leggibile da Google e dai motori di intelligenza artificiale:

- **«Quale struttura fa per te?»**: per ogni tipo di struttura i servizi offerti, con il link alla pagina della tipologia. Su kosgroup.com riabilitazione, residenze, hospice, psichiatria, ambulatori e diagnostica; su anniazzurri.it residenza, nucleo Alzheimer, ricovero di sollievo, centro diurno.
- **«Per saperne di più»**: i rimandi alle pagine pratiche. Su anniazzurri.it come accedere, costi e contributi, documenti, cosa sono le RSA; su kosgroup.com prenotazioni, referti, convenzioni, contatti.

Le pagine provincia di anniazzurri.it mantengono il loro elenco delle residenze nel testo della pagina; il widget si aggiunge sopra.

## Punti ancora da decidere che toccano il design

- **Assistenza domiciliare:** resta fuori dalla mappa; da decidere se nel widget di anniazzurri.it serve un rimando testuale (es. «Assistenza a casa in queste province»).
- **Pagina `/cosa-sono-le-rsa`:** widget come `/residenze` o mappa tolta.
- **Categoria Cookiebot della mappa:** proposta «marketing», da confermare con KOS; decide quando compare l'immagine statica al posto della mappa.
