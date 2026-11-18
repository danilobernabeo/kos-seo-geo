# Store locator di kosgroup.com: proposta per la tendina e i filtri

18/11/2026 · Danilo Bernabeo

## Sintesi

Propongo una tendina con le quattro aree del menu «Servizi e cure», che sono anche le quattro famiglie di bisogni che gli utenti esprimono nelle visite e nelle ricerche: Ambulatori e medicina specialistica, Riabilitazione, Psichiatria, Residenze per anziani. Nel pannello «Filtri» vanno il tipo di struttura (le 11 voci del menu «Strutture») e i servizi dell'area scelta, limitati alle voci che oggi hanno una pagina nel menu del sito. Trattamenti e tecnologie restano nelle schede e non diventano filtri.

La proposta si basa sui dati di kosgroup.com:

- **Pagine viste (GA4):** dal 30/12/2025 al 17/11/2026, primo giorno dell'esportazione in BigQuery.
- **Ricerche nella barra Yext del sito:** 27.158 ricerche dal 1/11/2025, contate per parola chiave senza leggere i singoli testi.
- **Ricerche Google (Search Console):** dal 1/11/2025.
- **Associazioni strutture-servizi:** quelle del sito al 18/11.

## 1. La tendina: quattro aree

| Area | Pagine viste dell'area (servizi e cure) | Pagine viste delle schede delle sue strutture | Ricerche nella barra del sito | Strutture |
| --- | --- | --- | --- | --- |
| Ambulatori e medicina specialistica | 54.319 | 268.270 | 2.352 | 18 |
| Riabilitazione | 38.405 | 200.532 | 1.050 | 30 |
| Psichiatria | 48.191 | 168.279 | 869 | 18 |
| Residenze per anziani | 10.235 (pagine di kosgroup.com; il menu porta ad anniazzurri.it) | — (schede su anniazzurri.it) | 1.304 | 61 |

Le quattro aree hanno tutte un peso reale, e nessuna altra voce lo raggiunge:

- **Ospedali:** una sola struttura, Villa dei Pini Civitanova. La sua scheda è la più vista del sito (86.717 pagine viste), ma una voce di tendina con un solo punto non aiuta a scegliere.
- **Odontoiatria:** due cliniche dentali, 2.508 pagine viste delle schede e 47 ricerche.

Per questo propongo quattro aree e non le sei discusse nella call del 13/11. L'ospedale e le cliniche dentali rientrano in «Ambulatori e medicina specialistica», perché i loro servizi sono pagine di quell'area.

Il dato più forte per le residenze sono le ricerche: «RSA», «residenza», «casa di riposo» e «anziani» sono il primo tema cercato nella barra di kosgroup.com (1.197 ricerche). Chi cerca una residenza sul sito del gruppo va accompagnato. Le residenze compaiono con l'etichetta «Anni Azzurri» e il link alla scheda di anniazzurri.it, come già deciso.

**Appartenenza a un'area.** Propongo che una struttura appartenga a un'area se ha un tipo di quell'area **oppure** offre almeno un servizio di quell'area. Ad esempio, il Centro Cardinal Ferrari è un centro di riabilitazione ma ha ambulatori di cardiologia e neurologia: chi sceglie «Ambulatori» e filtra «Cardiologia» deve trovarlo, come oggi lo trova nella pagina della cardiologia.

Con questa regola:
- l'area Ambulatori passa da 18 a 29 strutture, con 11 centri di riabilitazione e cliniche che offrono anche visite ed esami;
- Riabilitazione e Psichiatria cambiano di poco (3 e 1 strutture in più).

Le strutture in più aree per tipo sono solo tre: Ospedale San Pancrazio (Riabilitazione e Psichiatria), Villa Pineta (Riabilitazione e Ambulatori), Villa Azzurra (Psichiatria e Ambulatori).

**Pin:** un colore per area, quindi quattro colori. Per le tre strutture con più tipi serve un'area principale, da indicare da parte di KOS.

## 2. Filtri: tipo di struttura

Sono le 11 voci del menu «Strutture», con il numero di strutture e le pagine viste della pagina di tipologia e delle schede.

| Area | Tipo di struttura | Strutture | Pagina di tipologia | Schede |
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

Le schede delle strutture con due tipi sono contate in entrambi; nella tabella delle aree ogni scheda è contata una volta per area.

- **Con un'area scelta:** il gruppo mostra solo i tipi di quell'area, da due a quattro voci. È il caso normale.
- **Senza area:** 11 voci sono gestibili in un pannello, a patto di mostrarle nell'ordine delle aree, con il numero di strutture accanto (come confermato da RetailTune). Non le raggrupperei con intestazioni di area: duplicherebbero la tendina.
- **Etichetta da valutare con KOS:** nelle ricerche «fisioterapia» compare 251 volte, ma nessuna voce del sito si chiama così. Un'etichetta come «Ambulatori di riabilitazione e fisioterapia» aiuterebbe chi cerca con quella parola. Lo stesso vale per i servizi (paragrafo 3).

## 3. Filtri: servizi

Nel pannello compaiono solo i servizi dell'area scelta. Senza area il gruppo dei servizi resta nascosto: le voci delle quattro aree insieme sarebbero più di 60.

| Area | Gruppo nel pannello | Voci | Esempi (strutture) | Fuori dai filtri |
| --- | --- | --- | --- | --- |
| Ambulatori | Visite specialistiche | 25 | Cardiologia 8, Neurologia 8, Ortopedia 7, Dermatologia 6 | — |
| Ambulatori | Esami e diagnostica | 11 | Diagnostica ecografica 10, Laboratorio analisi 8, Elettromiografia 7, Risonanza magnetica 4 | — |
| Riabilitazione | Tipi di riabilitazione | 15 | Ortopedica 25, Neurologica 24, Pneumologica 18, Cardiologica 13, Età evolutiva 13 | 13 trattamenti, 21 tecnologie |
| Psichiatria | Disturbi trattati | 12 | Disturbi della personalità 18, Disturbi d'ansia 17, Psicosi 13, Umore e depressione 12 | 18 trattamenti |
| Residenze per anziani | Servizi e ospiti accolti | come su anniazzurri.it | (proposta a parte, con anniazzurri.it) | — |

**Perché le visite separate dagli esami.** Le 36 voci degli ambulatori sono troppe per un solo gruppo. Divise in due gruppi da 25 e 11, si leggono meglio. Corrispondono anche a due modi di cercare: le visite («ortopedico», «dermatologo») e gli esami («prelievi», «risonanza», «ecografia»). Nelle ricerche del sito prelievi e analisi valgono 876 ricerche, la risonanza 226, l'ecografia 155. Il gruppo delle visite resta lungo, quindi va compresso o con la ricerca interna, come proposto da RetailTune.

**Perché trattamenti e tecnologie restano fuori.**
- I trattamenti sono trasversali: il trattamento farmacologico è in 18 strutture psichiatriche su 18, il reinserimento sociale in 30 strutture di riabilitazione. Un filtro che tiene quasi tutte le strutture non aiuta a scegliere.
- Le tecnologie (21, tra cui esoscheletro EKSO, Gloreha, realtà virtuale) sono in una, al massimo quattro strutture ciascuna, e gli utenti non le cercano con quei nomi.

Restano tutte visibili nella scheda della struttura.

**Coerenza con le ricerche.** I temi più cercati hanno tutti una voce:

| Tema | Voce del filtro | Ricerche nella barra del sito | Ricerche Google (impressioni) |
| --- | --- | --- | --- |
| Prelievi e analisi | Laboratorio analisi | 876 | 120.945 |
| Riabilitazione neurologica, ictus, Parkinson | Riabilitazione neurologica | 277 | 55.173 |
| Risonanza e TAC | Risonanza magnetica, TAC | 306 | 78.241 |
| Disturbi alimentari | Disturbi della nutrizione e dell'alimentazione | 52 | 185.412 |
| Depressione | Disturbi dell'umore e depressione | 81 | 508.352 |
| Dipendenze | Dipendenze | 134 | 121.309 |

Le parole degli utenti sono spesso diverse dalle etichette: «fisioterapia», «psicologo», «dentista», «analisi del sangue». Propongo di chiedere a RetailTune se la ricerca interna del gruppo accetta sinonimi. Se non li accetta, le etichette si possono scrivere con la parola più comune tra parentesi, ad esempio «Laboratorio analisi (prelievi)».

## 4. Da decidere con KOS

1. **Le quattro aree.** Le quattro voci del menu «Servizi e cure», con l'ospedale e le cliniche dentali dentro «Ambulatori e medicina specialistica».
2. **L'appartenenza alle aree** per tipo oppure per servizio offerto (paragrafo 1).
3. **L'area principale** di Ospedale San Pancrazio, Villa Pineta e Villa Azzurra, per il colore del pin.
4. **Le etichette con la parola cercata dagli utenti** (fisioterapia, prelievi).
5. **Le associazioni tra strutture e servizi ancora da confermare (31).** I filtri le useranno così come sono sul sito, quindi conviene chiuderle prima dell'import di RetailTune.

## Fonti dei numeri

- **Pagine viste:** GA4 di kosgroup.com (esportazione BigQuery), evento `page_view` dal 30/12/2025 al 17/11/2026. Le pagine viste dell'area sono la pagina dell'area più le sue pagine interne. Le schede sono assegnate al tipo dalle pagine di tipologia del sito al 18/11.
- **Ricerche nella barra del sito:** archivio Yext (`kos-care-search`) dal 1/11/2025, conteggi per parola chiave. Le categorie non sono esclusive: una ricerca può contare in più temi.
- **Ricerche Google:** Search Console di kosgroup.com dal 1/11/2025, impressioni delle ricerche che contengono il tema.
- **Strutture e servizi:** pagine di tipologia del menu «Strutture» e associazioni tra schede e pagine dei servizi del sito al 18/11.
