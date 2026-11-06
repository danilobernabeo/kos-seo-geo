# Import delle TV anagrafiche dall'export Yext del 2026-11-06 (anniazzurri.it)

Entità Yext con URL su anniazzurri.it: 58; schede da importare: 57.
Il file JSON si applica con `scripts/deploy/imposta-tv.php` (prova, poi `--scrivi`): la prova mostra la scheda (pagetitle) accanto a ogni valore, così si vedono anche le differenze di nome. Le schede del sito senza entità Yext risultano dall'elenco delle schede pubblicate, non da qui.

## Tipi schema.org applicati (PROPOSTA, da approvare)

| Tipo | Specialità | Schede |
|---|---|---|
| MedicalBusiness,MedicalOrganization | Geriatric | 56 |
| (predefinito) | — | 1 |

## Indirizzi abbreviati scritti per esteso (41)

In Yext la via è abbreviata («V.», «V.le», «P.za»): nel dato strutturato e nelle TV si scrive per esteso. Conviene correggerli anche in Yext, perché gli stessi valori vanno ai profili Google e a RetailTune.

- V. Giuseppe Negrini 40 → Via Giuseppe Negrini 40
- V. Lorenzo Delleani 19 → Via Lorenzo Delleani 19
- V. Guglielmo Marconi 163 → Via Guglielmo Marconi 163
- V. Paolo Borsellino 6 → Via Paolo Borsellino 6
- V. San Faustino 27 → Via San Faustino 27
- V. Dei Lavoratori 133 → Via Dei Lavoratori 133
- V. San Faustino 21 → Via San Faustino 21
- P.za Dei Volontari 3 → Piazza Dei Volontari 3
- V. Cassola 13/A → Via Cassola 13/A
- V. Alcide De Gasperi 1 → Via Alcide De Gasperi 1
- V. Aurelio Saffi 12 → Via Aurelio Saffi 12
- V. Giovan Battista Borri 5 → Via Giovan Battista Borri 5
- V. Rovasenda 42 → Via Rovasenda 42
- V. Della Taccona 24 → Via Della Taccona 24
- V. Vaifro Sberna 4/6 → Via Vaifro Sberna 4/6
- V. Castello 6 → Via Castello 6
- V. Carlo Linneo 2 → Via Carlo Linneo 2
- V. Luigi Luciani 81 → Via Luigi Luciani 81
- V. San Luca 4 → Via San Luca 4
- V. Don Luigi Uboldi 40/42 → Via Don Luigi Uboldi 40/42
- V. San Francesco D'Assisi 56 → Via San Francesco D'Assisi 56
- V. Bornaresio 22 → Via Bornaresio 22
- V. Del Pozzo 6 → Via Del Pozzo 6
- V. Dell'Ariete 145 → Via Dell'Ariete 145
- V. Napoli 2 → Via Napoli 2
- V. Primo Maggio 152 → Via Primo Maggio 152
- V. Monviso 87 → Via Monviso 87
- V. Felice Lope De Vega 2 → Via Felice Lope De Vega 2
- V. Giacomino Gabriele Michele Bertetti 22 → Via Giacomino Gabriele Michele Bertetti 22
- V. Faentina 63/A → Via Faentina 63/A
- V. Colognola Ai Colli 8 → Via Colognola Ai Colli 8
- V. Colonnello Enrico Franceschi 87 → Via Colonnello Enrico Franceschi 87
- V. Giovanni Pascoli 19 → Via Giovanni Pascoli 19
- V. Morosini 11 → Via Morosini 11
- V. Carlo Darwin 17 → Via Carlo Darwin 17
- C.so Cristoforo Colombo 5 → Corso Cristoforo Colombo 5
- V. Vecchia Chieri 10 → Via Vecchia Chieri 10
- V. Del Marinaio 2 → Via Del Marinaio 2
- V. Piano di Castello 1 → Via Piano di Castello 1
- V. Barbarano Romano 43 → Via Barbarano Romano 43
- V. Giovanni Pascoli 3 → Via Giovanni Pascoli 3

## Da verificare o correggere in Yext

- **Residenza Anni Azzurri Abitare il Tempo (ABT00_ABS) → /residenza-anni-azzurri-abitare-il-tempo**
  - stesso orario sette giorni su sette (07:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri B. Franchiolo (KCA00_SRE) → /residenza-anni-azzurri-b-franchiolo**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Beato Angelico (KCA00_BEA) → /residenza-anni-azzurri-beato-angelico**
  - stesso orario sette giorni su sette (08:00-19:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Borgomanero (BGOMAN) → /residenza-anni-azzurri-borgomanero**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Campi Bisenzio (RAACampiBisenzio) → /residenza-anni-azzurri-campi-bisenzio**
  - indirizzo con il civico in testa: «1 AC, Via delle Miccine» (forma attesa «Via ..., numero»)
- **Residenza Anni Azzurri Carmagnola (KCA00_CRM) → /residenza-anni-azzurri-carmagnola**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Casa Argento (ABT00_ABA) → /residenza-anni-azzurri-casa-argento**
  - stesso orario sette giorni su sette (08:30-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Casa Serena (KCA00_OMO3) → /residenza-anni-azzurri-casa-serena**
  - stesso orario sette giorni su sette (10:00-16:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Casteldonino (KCA00_FVL) → /residenza-anni-azzurri-casteldonino**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Cit Turin (KCA00_TOR) → /residenza-anni-azzurri-cit-turin**
  - stesso orario sette giorni su sette (08:30-19:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Conero (KCA00_ANC) → /residenza-anni-azzurri-conero**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Ducale 1 (KCA00_DU1) → /residenza-anni-azzurri-ducale-1**
  - stesso orario sette giorni su sette (08:30-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Ducale 2 - 3 (KCA00_DU2) → /residenza-anni-azzurri-ducale-2-3**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Idice (KCA00_CAT) → /residenza-anni-azzurri-idice**
  - stesso orario sette giorni su sette (09:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Il Giardino (KCA00_BAG) → /residenza-anni-azzurri-il-giardino**
  - stesso orario sette giorni su sette (09:00-20:30): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Il Poggione (2081992527790767769) → /residenza-anni-azzurri-il-poggione**
  - orari assenti
- **Residenza Anni Azzurri La Corte (KCA00_MAR) → /residenza-anni-azzurri-la-corte**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri La Margherita (KCA00_BTS) → /residenza-anni-azzurri-la-margherita**
  - struttura chiusa o uscita dal gruppo, la scheda del sito non esiste più (Ne (GE), 301 verso /rsa-in-liguria): non importata; in Yext va segnata come chiusa
- **Residenza Anni Azzurri Le Grange (KCA00_RIL) → /residenza-anni-azzurri-le-grange**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Mestre (KCA00_MST) → /residenza-anni-azzurri-mestre**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Minerva (KCA00_RPL) → /residenza-anni-azzurri-minerva**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Mirasole (KCA00_MRS) → /residenza-anni-azzurri-mirasole**
  - stesso orario sette giorni su sette (07:15-21:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Montanaro (KCA00_MTA) → /residenza-anni-azzurri-montanaro**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Navigli (KCA00_NVG) → /residenza-anni-azzurri-navigli**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Parco Sempione (KCA00_SEM) → /residenza-anni-azzurri-parco-sempione**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Polo Geriatrico Riabilitativo Cinisello (KCA00_PGC) → /residenza-anni-azzurri-pgr-cinisello**
  - nome Yext «Residenza Anni Azzurri Polo Geriatrico Riabilitativo Cinisello» diverso dall'indirizzo della scheda (/residenza-anni-azzurri-pgr-cinisello): verificare il nome ufficiale (str.nome)
  - stesso orario sette giorni su sette (08:00-19:30): da confermare con KOS (apertura, visite o accettazione?)
  - place_id di Google assente (profilo Google non collegato?)
- **Residenza Anni Azzurri Polo Geriatrico Riabilitativo Milano (KCA00_PGM) → /residenza-anni-azzurri-pgr-milano**
  - nome Yext «Residenza Anni Azzurri Polo Geriatrico Riabilitativo Milano» diverso dall'indirizzo della scheda (/residenza-anni-azzurri-pgr-milano): verificare il nome ufficiale (str.nome)
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Quarto d'Altino (KCA00_QDA) → /residenza-anni-azzurri-quarto-d-altino**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Rezzato (KCA00_RZZ) → /residenza-anni-azzurri-rezzato**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Sacra Famiglia (KCA00_RIV) → /residenza-anni-azzurri-sacra-famiglia**
  - stesso orario sette giorni su sette (11:00-18:30): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Faustino (KCA00_SFA) → /residenza-anni-azzurri-san-faustino**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Giuseppe (KCA00_OSB) → /residenza-anni-azzurri-san-giuseppe**
  - place_id di Google assente (profilo Google non collegato?)
- **Residenza Anni Azzurri San Lorenzo (KCA00_GTN) → /residenza-anni-azzurri-san-lorenzo**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Luca (KCA00_SLC) → /residenza-anni-azzurri-san-luca**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Martino (KCA00_BOL) → /residenza-anni-azzurri-san-martino**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Rocco (KCA00_SRC) → /residenza-anni-azzurri-san-rocco**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Sisto (KCA00_BER) → /residenza-anni-azzurri-san-sisto**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri San Sisto 2 (5202618425469900374) → /residenza-anni-azzurri-san-sisto-2**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Sant'Ambrogio (KCA00_SAB) → /residenza-anni-azzurri-santambrogio**
  - nome Yext «Residenza Anni Azzurri Sant'Ambrogio» diverso dall'indirizzo della scheda (/residenza-anni-azzurri-santambrogio): verificare il nome ufficiale (str.nome)
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Sant'Anna (KCA00_VLD) → /residenza-anni-azzurri-sant-anna**
  - stesso orario sette giorni su sette (09:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Santa Maria in Chienti (KCA00_OMO) → /residenza-anni-azzurri-santa-maria-in-chienti**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Santena (KCA00_SNT) → /residenza-anni-azzurri-santena**
  - stesso orario sette giorni su sette (08:30-13:30,14:00-19:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Sestri Ponente (KCA00_SEP) → /residenza-anni-azzurri-sestri-ponente**
  - orari assenti
- **Residenza Anni Azzurri Tonengo D'Asti (KCA00_TNG) → /residenza-anni-azzurri-tonengo**
  - nome Yext «Residenza Anni Azzurri Tonengo D'Asti» diverso dall'indirizzo della scheda (/residenza-anni-azzurri-tonengo): verificare il nome ufficiale (str.nome)
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Valdaso (KCA00_OMO1) → /residenza-anni-azzurri-valdaso**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Vico Mercati (KCA00_OMO2) → /residenza-anni-azzurri-vico-mercati**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Villa Clarice (KCA00_CER) → /residenza-anni-azzurri-villa-clarice**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Villa Pineta (102) → /residenza-anni-azzurri-villa-pineta**
  - email assente
  - stesso orario sette giorni su sette (07:00-21:00): da confermare con KOS (apertura, visite o accettazione?)
  - tipologia di struttura assente: tipo predefinito (MedicalClinic)
- **Residenza Anni Azzurri Villa Reale (KCA00_MNZ) → /residenza-anni-azzurri-villa-reale**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Villa San Vincenzo (KCA00_SCR) → /residenza-anni-azzurri-villa-san-vincenzo**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Villa dei Ciliegi (VCI00_VCI) → /residenza-anni-azzurri-villa-dei-ciliegi**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Villaggio San Francesco (KCA00_VLN) → /residenza-anni-azzurri-villaggio-san-francesco**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Residenza Anni Azzurri Volpiano (KCA00_VPN) → /residenza-anni-azzurri-volpiano**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
