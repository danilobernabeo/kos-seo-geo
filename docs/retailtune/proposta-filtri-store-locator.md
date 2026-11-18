# Store locator di kosgroup.com e anniazzurri.it: proposta di tendina e filtri

18/11/2026 · Danilo Bernabeo

## Sintesi

Il widget funziona con tre comandi, che si combinano tra loro:

1. **la casella**, che cerca solo luoghi (indirizzo, comune, provincia, regione) o la posizione dell'utente;
2. **la tendina**, con l'area su kosgroup.com e il tipo di struttura su anniazzurri.it;
3. **il pannello «Filtri»**, con il tipo di struttura e i servizi dell'area scelta.

Una frase come «rsa in lombardia» si scrive invece nella barra di ricerca del sito. La barra riconosce il tipo e il luogo e apre il widget con i filtri già impostati, attraverso i parametri dell'indirizzo previsti da RetailTune. Oggi Yext Answers fa la stessa cosa dentro i suoi risultati, e così la capacità si mantiene.

Le voci sono scelte in base ai dati dei due siti: le pagine viste, le ricerche nelle barre Yext e su Google, e i servizi pubblicati nelle schede. Accanto a ogni voce c'è il numero di strutture che la offrono. I servizi presenti in quasi tutte le strutture restano nelle schede e non diventano filtri, perché non aiutano a scegliere.

## 1. kosgroup.com

### Tendina: area

Sono le quattro voci del menu «Servizi e cure». Le aree hanno tutte un peso reale, e nessun'altra voce lo raggiunge: l'ospedale (una struttura) e le cliniche dentali (due) rientrano in «Ambulatori e medicina specialistica», perché i loro servizi sono pagine di quell'area.

| Area | Strutture | Pagine viste dell'area | Pagine viste delle schede | Ricerche nella barra del sito |
| --- | --- | --- | --- | --- |
| Tutte le aree (valore iniziale) | 124 | | | |
| Ambulatori e medicina specialistica | 18 (29 con i servizi) | 54.319 | 268.270 | 2.352 |
| Riabilitazione | 30 (33 con i servizi) | 38.405 | 200.532 | 1.050 |
| Psichiatria | 18 (19 con i servizi) | 48.191 | 168.279 | 869 |
| Residenze per anziani | 61 più 5 centri diurni | 10.235, più il link ad anniazzurri.it | schede su anniazzurri.it | 1.304 |

Il tema più cercato nella barra di kosgroup.com è «RSA, residenza, casa di riposo, anziani», con 1.197 ricerche: chi cerca una residenza sul sito del gruppo va accompagnato. Le residenze compaiono con l'etichetta «Anni Azzurri» e con il link alla scheda di anniazzurri.it.

**Appartenenza a un'area.** Propongo che una struttura appartenga a un'area se ha un tipo di quell'area oppure se offre almeno un servizio di quell'area: sono i numeri tra parentesi nella tabella. Ad esempio il Centro Cardinal Ferrari è un centro di riabilitazione, ma ha visite di cardiologia: chi sceglie «Ambulatori» e «Cardiologia» lo trova, come oggi lo trova nella pagina della cardiologia.

**Pin:** quattro colori, uno per area. Tre strutture hanno tipi di due aree e serve indicarne una principale: Ospedale San Pancrazio (Riabilitazione e Psichiatria), Villa Pineta (Riabilitazione e Ambulatori), Villa Azzurra (Psichiatria e Ambulatori).

### Filtri: tipo di struttura

Sono le voci del menu «Strutture».
- **Con un'area scelta:** compaiono solo i tipi di quell'area.
- **Senza area:** compaiono tutte, nell'ordine della tabella e con il numero di strutture accanto.

| Area | Tipo di struttura | Strutture | Pagine viste della pagina di tipologia | Pagine viste delle schede |
| --- | --- | --- | --- | --- |
| Ambulatori | Poliambulatori | 9 | 23.566 | 109.009 |
| Ambulatori | Punti prelievo | 6 | 2.579 | 70.036 |
| Ambulatori | Ospedali | 1 | 5.764 | 86.717 |
| Ambulatori | Cliniche dentali | 2 | 898 | 2.508 |
| Riabilitazione | Centri di riabilitazione | 15 | 12.366 | 133.610 |
| Riabilitazione | Ambulatori di riabilitazione | 15 | 4.837 | 66.922 |
| Psichiatria | Cliniche psichiatriche | 9 | 10.698 | 150.083 |
| Psichiatria | Comunità terapeutiche psichiatriche | 8 | 7.749 | 32.910 |
| Psichiatria | Comunità terapeutiche riabilitative per minori | 2 | 6.453 | 3.288 |
| Residenze per anziani | Residenze per anziani | 59 | 7.492 | su anniazzurri.it |
| Residenze per anziani | Hospice | 2 | 1.333 | su anniazzurri.it |
| Residenze per anziani | Centri diurni | 5 | (nessuna pagina su kosgroup.com) | su anniazzurri.it |

Le schede delle strutture con due tipi sono contate in entrambi.

La pagina delle residenze di kosgroup.com elenca 59 residenze, mentre le schede di anniazzurri.it sono 57: Villa Olga e Villalba compaiono solo su kosgroup.com e vanno verificate prima dell'import.

### Filtri: servizi

Il gruppo dei servizi compare solo dopo la scelta dell'area: senza area le voci sarebbero più di 60. Le voci sono le pagine del menu di ciascuna area, con il numero di strutture associate sul sito.

**Ambulatori e medicina specialistica — Visite specialistiche (25)**

Cardiologia 8, Neurologia 8, Ortopedia 7, Dermatologia 6, Otorinolaringoiatria 6, Ambulatorio cardio-metabolico 5, Chirurgia generale 5, Dietologia 5, Endocrinologia 5, Podologia 5, Chirurgia maxillofacciale 4, Ginecologia 4, Oculistica 4, Pneumologia 4, Reumatologia 4, Urologia e andrologia 4, Gastroenterologia 3, Medicina dello sport 3, Odontoiatria 3, Flebologia 2, Neurochirurgia 2, Psicologia 2, Chirurgia vascolare 1, Fisiatria 1, Oncologia 1.

**Ambulatori e medicina specialistica — Esami e diagnostica (11)**

Diagnostica ecografica 10, Laboratorio analisi 8, Elettromiografia 7, Radiologia convenzionale 5, Risonanza magnetica 4, Senologia e mammografia 4, TAC 3, TC Cone Beam 3, MOC 2, Cardiodiagnostica 1, Elettroencefalografia 1.

**Riabilitazione — Tipi di riabilitazione (15)**

Ortopedica 25, Neurologica 24, Pneumologica 18, Cardiologica 13, In età evolutiva 13, Urologica e del pavimento pelvico 8, Gravi cerebrolesioni 6, Assistenza stati vegetativi persistenti 4, Estensiva e generale geriatrica 3, Mielolesioni 3, Parkinson e disordini del movimento 3, Medicina dello sport 2, Assistenza persone con disabilità 1, Dipendenze 1, Metabolica 1.

«Medicina dello sport» ha due pagine sul sito, una sotto gli ambulatori e una sotto la riabilitazione: va tenuta una sola voce.

**Psichiatria — Disturbi trattati (12)**

Disturbi della personalità 18, Disturbi d'ansia 17, Psicosi 13, Disturbi dell'umore e depressione 12, Schizofrenia 12, Disturbi della nutrizione e dell'alimentazione 8, Dipendenze 7, Disturbo ossessivo compulsivo 5, Disturbi del neurosviluppo e ADHD 4, Psicogeriatria 4, Disturbi del sonno 2, Disturbo post traumatico da stress 2.

**Residenze per anziani:** gli stessi gruppi di anniazzurri.it (paragrafo 2).

**Restano nelle schede, senza filtro:**
- **Trattamenti di riabilitazione (13) e di psichiatria (18):** sono trasversali. Ad esempio il trattamento farmacologico è in 18 strutture e il reinserimento sociale in 30, e un filtro che tiene quasi tutte le strutture non aiuta.
- **Tecnologie (21), tra cui esoscheletro EKSO, Gloreha e realtà virtuale:** sono presenti in una, al massimo quattro strutture, e nelle ricerche non compaiono con quei nomi.

**Visite ed esami in due gruppi.** Le 36 voci degli ambulatori sono troppe per un solo gruppo. Divise in due corrispondono a due modi di cercare:
- le visite: «ortopedico», «dermatologo»;
- gli esami: «prelievi» 876 ricerche nella barra del sito, «risonanza» 226, «ecografia» 155.

Il gruppo delle visite va compresso o con la ricerca interna, come proposto da RetailTune.

## 2. anniazzurri.it

### Tendina: tipo di struttura

| Tipo | Strutture | Ricerche Google (impressioni) |
| --- | --- | --- |
| Tutte le strutture (valore iniziale) | 65 | |
| Residenze per anziani | 57 | «RSA» 1.427.658, «casa di riposo» 642.742, «residenza per anziani» 95.755 |
| Centri diurni | 5 | 41.401 |
| Hospice | 2 | 220.314 |
| Appartamenti per anziani | 1 (Bergamo) | 34.373 |

Centri diurni, hospice e appartamenti sono reparti della residenza che li ospita, con la stessa sede salvo il Centro diurno Biancospino. Il pin compare comunque, perché il tipo è diverso.

Gli appartamenti di Bergamo sono una sola struttura, ma sono cercati: propongo la voce, da confermare con KOS. RSA Aperta e assistenza domiciliare non hanno una sede propria, quindi non sono punti sulla mappa: compaiono come servizi delle residenze che li erogano.

### Filtri

I valori vengono dalle schede delle residenze: 55 su 57 hanno la sezione dei servizi, mentre Santa Maria in Chienti e Villaggio San Francesco non ce l'hanno. Oggi le schede scrivono la stessa cosa in modi diversi: ad esempio per i nuclei per le demenze ci sono una decina di diciture. Per il filtro le ho raggruppate in una voce sola, con un codice stabile.

**Chi accogliamo**

| Voce | Residenze | Ricerche Google (impressioni) |
| --- | --- | --- |
| Persone autosufficienti | 47 | 203.419 |
| Persone con demenza | 39 | 791.090 (Alzheimer e demenza) |
| Persone con disabilità | 16 | 103.204 |
| Persone con disturbi psichiatrici | 9 | 10.588 |
| Stati vegetativi e gravi insufficienze respiratorie | 6 | 37.859 |

**Nuclei specialistici**

| Voce | Residenze |
| --- | --- |
| Nucleo Alzheimer e demenze | 17 |
| Nucleo stati vegetativi | 3 |
| Nucleo alta complessità neurologica | 3 |
| Nucleo disabili | 2 |

**Servizi**

| Voce | Residenze | Ricerche Google (impressioni) |
| --- | --- | --- |
| Riabilitazione | 27 | 76.362 |
| Assistenza domiciliare post dimissione | 15 | 133.368 (assistenza domiciliare) |
| RSA Aperta | 14 | 41.377 |
| Cure intermedie | 9 | 11.709 |
| Visite ed esami | 4 | |
| Centro dialisi | 2 | |

**Restano nelle schede, senza filtro:**
- **Presenti in quasi tutte le residenze:** persone non autosufficienti (55 su 55), parzialmente autosufficienti (53), palestra e fisioterapia (55), ricoveri temporanei e di sollievo (53).
- **Équipe assistenziale.**

I ricoveri temporanei sono cercati (62.549 impressioni, 56 ricerche nella barra del sito), ma un filtro che tiene 53 residenze su 55 non restringe. Meglio un'etichetta nella scheda del widget.

## 3. Da decidere con KOS

1. **Le quattro aree di kosgroup.com:** con l'ospedale e le cliniche dentali dentro «Ambulatori e medicina specialistica».
2. **L'appartenenza alle aree:** per tipo, oppure anche per servizio offerto.
3. **L'area principale per il colore del pin:** per Ospedale San Pancrazio, Villa Pineta e Villa Azzurra.
4. **Le etichette con la parola cercata dagli utenti:** «fisioterapia» ha 251 ricerche nella barra di kosgroup.com e nessuna voce del sito porta questo nome; lo stesso vale per «prelievi». Ad esempio: «Ambulatori di riabilitazione e fisioterapia», «Laboratorio analisi (prelievi)».
5. **Gli appartamenti di Bergamo:** come voce della tendina di anniazzurri.it.
6. **Villa Olga e Villalba:** presenti nella pagina delle residenze di kosgroup.com ma senza scheda su anniazzurri.it.
7. **Le schede di Santa Maria in Chienti e Villaggio San Francesco:** sono senza la sezione dei servizi.
8. **Le 31 associazioni tra strutture e servizi di kosgroup.com ancora da confermare.** I filtri le useranno così come sono sul sito, quindi vanno chiuse prima dell'import in RetailTune.

## 4. Da chiedere a RetailTune

1. **Casella del widget:** il completamento dev'essere limitato a comuni, province e regioni italiane. Il rischio da escludere è che, scrivendo «rsa», proponga attività di altri gestori.
2. **Gruppi nel pannello «Filtri»:** con un'area scelta compaiono solo i gruppi di quell'area. Senza area resta visibile solo il tipo di struttura.
3. **Parametri nell'indirizzo:** `tipologia`, `regione` e `servizi`, per aprire il widget dalla barra di ricerca del sito con i filtri già impostati.

## Fonti dei numeri

- **Pagine viste:** GA4 dei due siti, esportazione in BigQuery, evento `page_view` dal 30/12/2025 al 17/11/2026. Per un'area si sommano la pagina dell'area e le sue pagine interne. Le schede sono attribuite al tipo secondo le pagine di tipologia di kosgroup.com al 18/11.
- **Ricerche nelle barre dei siti:** archivio Yext dal 1/11/2025, 27.158 ricerche su kosgroup.com e 731 su anniazzurri.it, contate per parola chiave senza leggere i singoli testi. Una ricerca può contare in più temi.
- **Ricerche Google:** Search Console dei due siti dal 1/11/2025, impressioni delle ricerche che contengono il tema.
- **Strutture e servizi:** per kosgroup.com, le associazioni tra schede e pagine dei servizi al 18/11. Per anniazzurri.it, le sezioni «Servizi» e «Chi accogliamo» delle schede delle residenze, lette dal sito il 18/11. I reparti (centri diurni, hospice, appartamenti) vengono dai dati del 17/11.
