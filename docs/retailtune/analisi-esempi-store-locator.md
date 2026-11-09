# Store locator RetailTune: analisi degli esempi e requisiti di design

Danilo Bernabeo · 9 novembre 2026

## In breve

Per kosgroup.com e anniazzurri.it propongo come riferimento lo store locator di **OVS**: mappa con elenco laterale e **pannello di dettaglio** che prende il posto dell'elenco, con il rimando finale alla pagina della struttura. Da noi il terzo livello sono le schede struttura già esistenti sui due siti, perché RetailTune non pubblica pagine proprie. Gli altri esempi danno spunti puntuali, elencati più avanti.

Le scelte di configurazione sono già fissate nel primo invio a RetailTune del 2 novembre: due istanze separate (una per sito), filtri preimpostati pagina per pagina, nessuna pagina struttura pubblicata da RetailTune, consenso gestito con Cookiebot, dati letti dai siti tramite l'API. Questo documento riguarda solo l'aspetto e il comportamento del widget.

## Gli esempi esaminati

| Esempio | Link | Cosa mostra | Da prendere |
| --- | --- | --- | --- |
| OVS | [ovs.it/it/it/stores](https://www.ovs.it/it/it/stores) | Elenco, pannello di dettaglio, pagina negozio sul dominio principale; filtri per reparti, servizi e marchi | Il modello di riferimento (vedi sotto) |
| Plenitude | [eniplenitude.com/energy-store](https://eniplenitude.com/energy-store) | Tabella dei servizi offerti da ogni tipo di punto, accanto alla ricerca per città o CAP | Un blocco «quale struttura fa per me» nella pagina, fuori dal widget |
| Calligaris | [stores.calligaris.com/it](https://stores.calligaris.com/it) | Prodotto standard RetailTune: etichetta di tipologia sopra il nome, contatore dei risultati, «info e promozioni» e «portami qui» | Etichetta di tipologia e contatore. Da verificare: ordine per distanza (nell'elenco di Roma compare un negozio di Digione) |
| Pinko | [pinko.com/it-it/a/storelocator](https://www.pinko.com/it-it/a/storelocator) | Widget RetailTune dentro la pagina del sito, con briciole; percorso a piedi, in auto o con i mezzi | Percorso con i mezzi pubblici, utile a chi va a trovare un parente in residenza |
| Decò (Multicedi) | [puntivenditadeco.multicedi.it/it](https://puntivenditadeco.multicedi.it/it) | Più formati di punto vendita nello stesso locator | Pin distinti per grandi famiglie (sanitario, residenze), non per ogni tipologia |
| Percassi | [percassi.com/store-locator](https://www.percassi.com/store-locator/) e pagina marchio [LEGO](https://www.percassi.com/portfolio/lego/) | Mappa generale e, nella pagina del marchio, il pulsante «Trova lo store più vicino» verso la sezione negozi | Lo schema del widget filtrato nelle pagine interne |
| CR Lab | [crlab.com/it_it/storelocator](https://crlab.com/it_it/storelocator) | Centri con «Prenota una consulenza» come azione principale | Azione principale diversa secondo la tipologia |

Nota: OVS e Calligaris li ho provati dal vivo. Pinko, Decò, Percassi e CR Lab caricano tutto via JavaScript: li ho visti nella struttura, non nel comportamento su mobile.

## Il modello OVS

Il dettaglio di un negozio si apre in tre modi:

1. **Dalla scheda nell'elenco:** «Maggiori info» sostituisce l'elenco con il pannello di dettaglio, nella stessa colonna. La mappa resta visibile e si centra sul negozio; la X in alto riporta all'elenco nella posizione in cui era.
2. **Dal pin sulla mappa:** un fumetto con nome, indirizzo, stato di apertura e telefono, insieme al pannello di dettaglio.
3. **Dal pannello:** «Vai alla pagina negozio» porta alla pagina del negozio sul dominio principale (esempio: [OVS Roma Tritone](https://www.ovs.it/it/it/stores/italia/lazio/roma/ovs-roma-tritone)).

Da noi il terzo passaggio porta alla scheda della struttura su kosgroup.com o anniazzurri.it (campo `link` dell'API, uguale all'URL canonico). Il comportamento standard di RetailTune, come su Calligaris, porta invece subito alla pagina negozio: va chiesto il pannello di dettaglio come secondo livello.

## Requisiti di design proposti

### Scheda nell'elenco

- Nome, etichetta della tipologia (anche più di una, es. Ospedale San Pancrazio), indirizzo, distanza dopo una ricerca, telefono cliccabile.
- Due pulsanti: «Scheda» (la nostra pagina) e «Indicazioni».
- Contatore dei risultati.
- Niente «Aperto / Chiuso» per residenze, hospice e comunità, aperte tutto il giorno.

### Pannello di dettaglio

- Foto della struttura (per le residenze è già disponibile).
- Telefono ed email in chiaro.
- Orari con un'etichetta per tipologia: «Accoglienza e informazioni» per le residenze, «Prelievi» per i punti prelievo, «Orari» per gli ambulatori.
- Servizi e aree di cura con icone (fase 2), con link alle pagine dei servizi del sito.
- Azione principale per tipologia: «Richiedi informazioni» per le residenze, «Prenota» per gli ambulatori con prenotazione online.
- Percorso a piedi, in auto o con i mezzi.
- In fondo: «Vai alla scheda della struttura».

### Widget nelle pagine interne

- Versione compatta: filtri nascosti, ricerca per indirizzo attiva, link «Vedi tutte le strutture» alla mappa generale.
- Contenitore ad altezza fissa definita dal sito, per non spostare il contenuto durante il caricamento.
- Prima del consenso alla mappa: immagine statica con «Mostra la mappa»; l'elenco resta visibile.

### Mobile

- Elenco prima della mappa, con interruttore «Elenco / Mappa».
- «Chiama» sempre visibile nel pannello di dettaglio.

### Comportamenti generali

- Nessuna richiesta di posizione all'apertura della pagina: la geolocalizzazione parte solo dal pulsante «Vicino a me».
- Se nella zona cercata non ci sono strutture, il widget propone la più vicina con la distanza.
- Ordine dei risultati per distanza dal punto cercato.
- Pin distinti per strutture sanitarie e residenze.
- Elenco utilizzabile da tastiera e con lettore di schermo; colori dai token già consegnati.
- Niente recensioni e niente promozioni.

## Fuori dal widget, nelle pagine del sito

Sulla pagina `/it/strutture` di kosgroup.com, sopra la mappa, un blocco scritto da noi che spiega quale tipo di struttura offre cosa (sul modello di Plenitude). Aiuta le famiglie a scegliere ed è testo leggibile da Google e dai motori di intelligenza artificiale. Le pagine provincia di anniazzurri.it mantengono il loro elenco delle residenze nel testo della pagina; il widget si aggiunge sopra.

## Punti ancora da decidere che toccano il design

- **Assistenza domiciliare:** resta fuori dalla mappa; da decidere se nel widget di anniazzurri.it serve un rimando testuale (es. «Assistenza a casa in queste province»).
- **Pagina `/cosa-sono-le-rsa`:** widget come `/residenze` o mappa tolta.
- **Categoria Cookiebot della mappa:** proposta «marketing», da confermare con KOS; decide quando compare l'immagine statica al posto della mappa.
