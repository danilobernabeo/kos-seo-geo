# Widget di kosgroup.com e anniazzurri.it: tendina e filtri

18/11/2026 · Danilo Bernabeo

Questa è la proposta di lavoro per la riunione. Le voci sono ancora da confermare con KOS: tra parentesi c'è il numero di strutture che oggi offrono ciascuna voce.

## Come funziona il widget

Il widget ha tre comandi, che si combinano tra loro:

1. **Casella:** cerca solo luoghi (indirizzo, comune, provincia, regione), oppure la posizione dell'utente.
2. **Tendina:** su kosgroup.com sceglie l'area, su anniazzurri.it il tipo di struttura. Il valore iniziale è «Tutte».
3. **Pannello «Filtri»:** il tipo di struttura e i servizi. Valgono la logica OR all'interno di un gruppo e AND tra gruppi diversi, e i numeri accanto alle voci si aggiornano sulla zona inquadrata, come concordato il 13/11.

Le frasi libere, come «rsa in lombardia», si scrivono nella barra di ricerca del sito. La barra riconosce il tipo e il luogo e apre il widget con i filtri già impostati, attraverso i parametri dell'indirizzo.

## kosgroup.com

### Tendina: area

| Area | Strutture | Colore del pin |
| --- | --- | --- |
| Tutte le aree | 124 | |
| Ambulatori e medicina specialistica | 29 | colore 1 |
| Riabilitazione | 33 | colore 2 |
| Psichiatria | 19 | colore 3 |
| Residenze per anziani | 66 | colore 4 |

Una struttura può appartenere a più aree, perché le aree dipendono sia dal tipo di struttura sia dai servizi offerti. Il colore del pin è quello della sua area principale, che indicheremo noi nell'anagrafica.

Le residenze compaiono con l'etichetta «Anni Azzurri» e il link alla scheda di anniazzurri.it.

### Filtri: tipo di struttura

- **Con un'area scelta:** compaiono solo i tipi di quell'area.
- **Senza area:** compaiono tutti, in quest'ordine.

| Area | Tipo di struttura |
| --- | --- |
| Ambulatori | Poliambulatori (9), Punti prelievo (6), Ospedali (1), Cliniche dentali (2) |
| Riabilitazione | Centri di riabilitazione (15), Ambulatori di riabilitazione (15) |
| Psichiatria | Cliniche psichiatriche (9), Comunità terapeutiche psichiatriche (8), Comunità terapeutiche riabilitative per minori (2) |
| Residenze per anziani | Residenze per anziani (59), Hospice (2), Centri diurni (5) |

### Filtri: servizi (solo dopo la scelta dell'area)

**Ambulatori — Visite specialistiche (25):** gruppo lungo, da comprimere o con la ricerca interna.

Cardiologia, Neurologia, Ortopedia, Dermatologia, Otorinolaringoiatria, Ambulatorio cardio-metabolico, Chirurgia generale, Dietologia, Endocrinologia, Podologia, Chirurgia maxillofacciale, Ginecologia, Oculistica, Pneumologia, Reumatologia, Urologia e andrologia, Gastroenterologia, Medicina dello sport, Odontoiatria, Flebologia, Neurochirurgia, Psicologia, Chirurgia vascolare, Fisiatria, Oncologia.

**Ambulatori — Esami e diagnostica (11)**

Diagnostica ecografica, Laboratorio analisi, Elettromiografia, Radiologia convenzionale, Risonanza magnetica, Senologia e mammografia, TAC, TC Cone Beam, MOC, Cardiodiagnostica, Elettroencefalografia.

**Riabilitazione — Tipi di riabilitazione (15)**

Ortopedica, Neurologica, Pneumologica, Cardiologica, In età evolutiva, Urologica e del pavimento pelvico, Gravi cerebrolesioni, Assistenza stati vegetativi persistenti, Estensiva e generale geriatrica, Mielolesioni, Parkinson e disordini del movimento, Medicina dello sport, Assistenza persone con disabilità, Dipendenze, Metabolica.

**Psichiatria — Disturbi trattati (12)**

Disturbi della personalità, Disturbi d'ansia, Psicosi, Disturbi dell'umore e depressione, Schizofrenia, Disturbi della nutrizione e dell'alimentazione, Dipendenze, Disturbo ossessivo compulsivo, Disturbi del neurosviluppo e ADHD, Psicogeriatria, Disturbi del sonno, Disturbo post traumatico da stress.

**Residenze per anziani:** gli stessi tre gruppi di anniazzurri.it.

## anniazzurri.it

### Tendina: tipo di struttura

| Tipo | Strutture |
| --- | --- |
| Tutte le strutture | 65 |
| Residenze per anziani | 57 |
| Centri diurni | 5 |
| Hospice | 2 |
| Appartamenti per anziani | 1 |

Centri diurni, hospice e appartamenti sono collegati alla residenza che li ospita, e in genere hanno la stessa sede.

RSA Aperta e assistenza domiciliare non sono punti sulla mappa: compaiono come servizi delle residenze che li erogano.

### Filtri

| Gruppo | Voci |
| --- | --- |
| Chi accogliamo | Persone autosufficienti (47), Persone con demenza (39), Persone con disabilità (16), Persone con disturbi psichiatrici (9), Stati vegetativi e gravi insufficienze respiratorie (6) |
| Nuclei specialistici | Nucleo Alzheimer e demenze (17), Nucleo stati vegetativi (3), Nucleo alta complessità neurologica (3), Nucleo disabili (2) |
| Servizi | Riabilitazione (27), Assistenza domiciliare post dimissione (15), RSA Aperta (14), Cure intermedie (9), Visite ed esami (4), Centro dialisi (2) |

I ricoveri temporanei e di sollievo sono presenti in quasi tutte le residenze, quindi non sono un filtro. Chiediamo se si possono mostrare come etichetta nella scheda del punto.

## Domande

1. **Casella.** Si può limitare il completamento a comuni, province e regioni italiane? Il rischio da escludere è che, scrivendo «rsa», compaiano attività di altri gestori. Il completamento ha un costo a parte?
2. **Gruppi dipendenti dall'area.** I gruppi del pannello possono comparire solo per l'area scelta? Il gruppo dei servizi, ad esempio, deve restare nascosto finché non si sceglie un'area.
3. **Più aree per struttura.** Una struttura può avere più aree e un'area principale, che decide il colore del pin?
4. **Parametri nell'indirizzo.** Il widget accetta tipologia, regione e servizi come parametri, per aprirlo dalla barra di ricerca del sito con i filtri già impostati?
5. **Etichette nella scheda del punto.** Si possono mostrare etichette che non sono filtri, come «Ricoveri temporanei»?
6. **Codici delle voci.** I codici delle voci li forniamo noi (stabili, uno per voce) con l'import: confermate il formato?
