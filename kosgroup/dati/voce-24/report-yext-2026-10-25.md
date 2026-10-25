# Import delle TV anagrafiche dall'export Yext del 2026-10-25 (kosgroup.com)

Entità Yext con URL su kosgroup.com: 60; schede da importare: 57.
Il file JSON si applica con `scripts/deploy/imposta-tv.php` (prova, poi `--scrivi`): la prova mostra la scheda (pagetitle) accanto a ogni valore, così si vedono anche le differenze di nome. Le schede del sito senza entità Yext risultano dall'elenco delle schede pubblicate, non da qui.

## Più entità sulla stessa scheda (non importate)

- /it/centri/poliambulatorio-villalba: VPI00_VPM, Villalba

## Tipi schema.org applicati (PROPOSTA, da approvare)

| Tipo | Specialità | Schede |
|---|---|---|
| MedicalClinic | Physiotherapy | 27 |
| MedicalClinic | Psychiatric | 16 |
| MedicalClinic | — | 5 |
| DiagnosticLab | LaboratoryScience | 5 |
| Dentist | Dentistry | 2 |
| Hospital | — | 1 |
| (predefinito) | — | 1 |

## Indirizzi abbreviati scritti per esteso (34)

In Yext la via è abbreviata («V.», «V.le», «P.za»): nel dato strutturato e nelle TV si scrive per esteso. Conviene correggerli anche in Yext, perché gli stessi valori vanno ai profili Google e a RetailTune.

- V. Bolzano 5 → Via Bolzano 5
- V.le Don Giovanni Minzoni 29/A → Viale Don Giovanni Minzoni 29/A
- V.le Don Giovanni Minzoni 29 → Viale Don Giovanni Minzoni 29
- V. Nazario Sauro 162 → Via Nazario Sauro 162
- V. Battaglia 55 → Via Battaglia 55
- V. Cavina 9 → Via Cavina 9
- V. Scisciano Nord 11 → Via Scisciano Nord 11
- V. Fratelli Rosselli 83 → Via Fratelli Rosselli 83
- V. Della Bordogna 144/154 → Via Della Bordogna 144/154
- V. IV Novembre 21 → Via IV Novembre 21
- V.le Regina Margherita 133 → Viale Regina Margherita 133
- V. Damiano Chiesa 2 → Via Damiano Chiesa 2
- V. Aprutina 194 → Via Aprutina 194
- V. Simone Stratico 10 → Via Simone Stratico 10
- V. Pero 148 → Via Pero 148
- V. Nomentana 1362 → Via Nomentana 1362
- V. Dei Narcisi 5 → Via Dei Narcisi 5
- V. Don Giovanni Minzoni 98 → Via Don Giovanni Minzoni 98
- V. Atanagi 66 → Via Atanagi 66
- V. Silvio Pellico 32 → Via Silvio Pellico 32
- V. Piave 72 → Via Piave 72
- V. Don Giovanni Minzoni 16 → Via Don Giovanni Minzoni 16
- V. Villa San Giuseppe 1 → Via Villa San Giuseppe 1
- V.le Dei Pini 31 → Viale Dei Pini 31
- V.le Dei Pini 31 → Viale Dei Pini 31
- V. Andrea D'Accorso 2 → Via Andrea D'Accorso 2
- V. Ferrante Ferranti 41 → Via Ferrante Ferranti 41
- V. Francesco Penserini 9 → Via Francesco Penserini 9
- V. Roma 5 → Via Roma 5
- V. Per Isolona 3 → Via Per Isolona 3
- V.le Piave 36 → Viale Piave 36
- V. Ottone IV di Brunswik 12 → Via Ottone IV di Brunswik 12
- V. Jacopo Robusti, 37 → Via Jacopo Robusti, 37
- P.za Tarquinio Provini, 2 → Piazza Tarquinio Provini, 2

## Da verificare o correggere in Yext

- **Casa Cima (KCA00_CIM) → /it/centri/casa-cima**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Casa Mioglia (KCA00_MIO) → /it/centri/casa-mioglia**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Casa Pero (KCA00_PER) → /it/centri/casa-pero**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Casa Sampeyre (KCA00_SAM) → /it/centri/casa-sampeyre**
  - stesso orario sette giorni su sette (10:00-16:00): da confermare con KOS (apertura, visite o accettazione?)
- **Casa Sanremo (KCA00_SRM) → /it/centri/casa-sanremo**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Casa Varazze (KCA00_SAS) → /it/centri/casa-varazze**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
  - marchio «0» non valido: non importato
- **Casa Villa Maura (KCA00_MAU) → /it/centri/casa-villa-maura**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Centro Ambulatoriale Santo Stefano Matelica (KCA00_AMA) → /it/centri/centro-ambulatoriale-santo-stefano-matelica**
  - seconda riga dell'indirizzo non importata: «Via Ottone IV di Brunswik, 12»
- **Centro Medico Riabilitativo (108) → /it/centri/centro-medico-riabilitativo**
  - seconda riga dell'indirizzo non importata: «Via Amilcare Pizzi»
  - email assente
- **Centro Venerabile Marcucci (106) → /it/centri/centro-venerabile-marcucci**
  - email assente
- **Clinica Dentale Porto Potenza Picena (101) → /it/centri/clinica-dentale-porto-potenza-picena**
  - email assente
- **Clinica Dentale Villalba (100) → /it/centri/clinica-dentale-villalba**
  - email assente
- **Corte Carcare (KCA00_TU1) → /it/centri/corte-carcare**
  - stesso orario sette giorni su sette (09:00-19:00): da confermare con KOS (apertura, visite o accettazione?)
- **Corte Orero (KCA00_TU2) → /it/centri/corte-orero**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Fidia (FID00_FIS) → /it/centri/fidia**
  - place_id di Google assente (profilo Google non collegato?)
- **Myolab (JES00_JES) → /it/centri/myolab**
  - place_id di Google assente (profilo Google non collegato?)
- **Ospedale San Pancrazio (KCA00_OSP) → /it/centri/ospedale-san-pancrazio**
  - stesso orario sette giorni su sette (08:00-20:30): da confermare con KOS (apertura, visite o accettazione?)
- **Poliambulatorio San Martino (107) → /it/centri/poliambulatorio-san-martino**
  - email assente
- **Poliambulatorio Valdaso (1162064197903061649) → /it/centri/poliambulatorio-valdaso**
  - email assente
  - tipologia di struttura assente: tipo predefinito (MedicalClinic)
- **Punto Prelievi Biemme (VPI00_DLB) → /it/centri/punto-prelievi-biemme**
  - place_id di Google assente (profilo Google non collegato?)
- **Villa Adria (KCA00_OVA) → /it/centri/villa-adria**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Azzurra (KCA00_OVZ) → /it/centri/villa-azzurra**
  - stesso orario sette giorni su sette (08:00-18:30): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Fastiggi (104) → /it/centri/villa-fastiggi**
  - email assente
  - stesso orario sette giorni su sette (07:30-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Jolanda (KCA00_OVJ) → /it/centri/villa-jolanda**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Margherita Benevento (VMA00_OBN) → /it/centri/villa-margherita-benevento**
  - stesso orario sette giorni su sette (08:30-17:30): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Margherita Vicenza (VMA00_OAR) → /it/villa-margherita**
  - URL del sito web /it/villa-margherita non è una scheda struttura (/it/centri/...): l'entità non si abbina
- **Villa Pineta (KCA08202) → /it/centri/villa-pineta**
  - stesso orario sette giorni su sette (07:00-21:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Rosa (KCA00_DEG) → /it/centri/villa-rosa**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa Rosa Diagnostica (105) → /it/centri/villa-rosa-diagnostica**
  - email assente
- **Villa Sant'Alessandro (KCA00_OSA) → /it/centri/villa-sant-alessandro**
  - stesso orario sette giorni su sette (08:00-20:00): da confermare con KOS (apertura, visite o accettazione?)
- **Villa dei Pini (KCA00_OVP) → /it/centri/villa-dei-pini-firenze**
  - stesso orario sette giorni su sette (00:00-23:59): da confermare con KOS (apertura, visite o accettazione?)
  - place_id di Google assente (profilo Google non collegato?)
- **Villalba (Villalba) → /it/centri/poliambulatorio-villalba**
  - email assente
  - tipologia di struttura assente: tipo predefinito (MedicalClinic)
- **Ville di Nozzano (KCA00_OV1) → /it/centri/ville-di-nozzano**
  - stesso orario sette giorni su sette (09:00-18:30): da confermare con KOS (apertura, visite o accettazione?)
