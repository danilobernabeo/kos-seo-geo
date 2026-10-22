# Dato strutturato inserito da Yext nelle schede struttura di kosgroup.com

Analisi del 22/10/2026, voce 24 dell'audit KosGroup (dato strutturato delle strutture). Documenta lo stato del
JSON-LD che lo script Yext Knowledge Tags inserisce oggi nelle schede, prima di sostituirlo con il nodo
`#localbusiness` emesso dal sito.

## Come è stato rilevato

- Pagine esaminate: le 61 risorse pubblicate con la TV `idYext` valorizzata (template strutture), sul sito pubblico.
- Per ogni pagina lo script `scripts/checks/yext-schema.php` scarica lo script Knowledge Tags
  (`knowledgetags.yextapis.com/embed?...&entity_id=<idYext>`), ne estrae il JSON-LD che la libreria Yext inserisce
  nel `body` della pagina e lo controlla con la convenzione del progetto.
- Lo script Yext è inserito dal chunk `head-strutture` quando la TV `idYext` è valorizzata. Nessuna pagina di
  kosgroup.com usa campi visibili riempiti da Yext (`data-yext-field`): togliendo lo script cambia solo il dato
  strutturato.
- Archivio completo dei 59 JSON-LD e dell'elenco dei difetti: `docs/evidenze/yext-schema-kosgroup-20261022/`
  (`schemi.json`, `problemi.csv`, `riepilogo.txt`).

## Sintesi

Tutte le 61 schede hanno almeno un difetto. I quattro più gravi per la comprensione da parte di motori di ricerca
e assistenti AI:

1. **Identità non valida (59 su 59).** L'`@id` è il codice interno Yext (`KCA00_OSA`, `108`, `Villalba`...), non un
   URL: ogni lettore lo interpreta a modo suo (il Rich Results Test lo mostra come
   `http://example-test.site/KCA00_OSA`). Due schede di Villa Margherita Vicenza (psichiatria e riabilitazione)
   condividono lo stesso id `VMA00_OAR` e quindi risultano la stessa entità.
2. **Nessun legame con il Gruppo (59 su 59).** Nessuna struttura dichiara `parentOrganization` verso l'ente
   `https://kosgroup.com/#organization`: per un motore ogni clinica è un'organizzazione a sé. Il marchio è indicato
   solo come `brand` testuale (Santo Stefano Riabilitazione, Neomesia, Polo Diagnostico...), assente su 12 schede,
   «0» su Casa Varazze e «Anni Azzurri» su Poliambulatorio Parco di Veio.
3. **Tipo sbagliato (26 schede).** Valori dell'elenco delle specialità mediche usati come tipo di entità:
   - `Psychiatric` da solo su 5 comunità (Casa Cima, Pero, Sampeyre, Sanremo, Villa Maura) e insieme ad altri tipi
     su 4 schede, tra cui Villa Sant'Alessandro: le 5 comunità non hanno di fatto alcun tipo valido;
   - `Pediatric` su 14 centri ambulatoriali Santo Stefano, Fidia e Myolab (attività di riabilitazione, non
     pediatria) e da solo su Corte Carcare e Corte Orero;
   - `Podiatric` e `Obstetric` su un'altra scheda.
   In più: `Physician` (studio di un singolo medico) sull'Ospedale San Pancrazio e sul Centro Cardinal Ferrari,
   `Dentist` sull'ospedale Villa dei Pini Civitanova e su due poliambulatori.
4. **Collegamenti verso Yext (50 schede).** `sameAs` punta alla pagina della struttura su `location.com`
   (directory Yext): dichiara come «stessa entità» una pagina di terzi che sparirà con la fine del contratto. Le
   immagini e i loghi di 16 schede sono ospitati sul CDN di Yext (`a.mktgcdn.com`), con lo stesso rischio.

## Altri difetti

| Difetto | Schede | Dettaglio |
|---|---|---|
| Paese assente nell'indirizzo | 59 | manca `addressCountry` (avviso facoltativo di Google) |
| Stesso orario tutti i giorni | 20 | stessa fascia dal lunedì alla domenica (08:00-20:00 su 11 schede, 00:00-23:59 su una), tra cui le comunità psichiatriche, Villa Sant'Alessandro e l'Ospedale San Pancrazio: da verificare con KOS |
| Giorni senza orario | 37 | giorni di chiusura scritti senza `opens`/`closes`, forma non valida |
| `url` diverso dalla pagina | 3 | Poliambulatorio Parco di Veio → `anniazzurri.it/residenza-anni-azzurri-parco-di-veio` (con nome «Residenza Anni Azzurri Parco di Veio»); le due Villa Margherita Vicenza → `/it/villa-margherita` |
| Id Yext non valido nella TV | 2 | Poliambulatorio Villa Pineta («Temporary 1») e Villa Armonia («Temporary 7»): Yext risponde 400, nessun dato |
| Email assente | 9 | tra cui Poliambulatorio Valdaso, Villa Fastiggi, Villa Rosa Diagnostica |
| Nome diverso dalla pagina | 4 | Villalba, Villa dei Pini Firenze («Villa dei Pini»), Villa dei Pini Civitanova, Parco di Veio |

Nessun problema di formato su telefoni (tutti internazionali) e domini email (tutti del gruppo).

## Cosa cambia con la voce 24

Il nodo `#localbusiness` emesso dal sito, nel `@graph` unico della pagina, sostituisce il blocco Yext:

| Oggi (Yext) | Con la voce 24 |
|---|---|
| `@id` codice Yext | `https://kosgroup.com/it/centri/<scheda>#localbusiness` |
| nessun legame | `parentOrganization` → `https://kosgroup.com/#organization` |
| specialità come tipo | tipo sanitario più specifico (`MedicalClinic`, `Hospital`...) e `medicalSpecialty` per le specialità |
| `sameAs` verso location.com | `sameAs` solo verso profili ufficiali (es. scheda Google della struttura, se disponibile) |
| immagini sul CDN Yext | immagini del sito |
| dati gestiti in Yext | dati dal sito (TV) e poi da RetailTune, verificati con KOS |

Il nodo nuovo si pubblica nello stesso rilascio in cui si toglie lo script dal chunk `head-strutture`, per non
avere due descrizioni diverse della stessa struttura.

Da decidere prima del rilascio:

- orari reali delle 20 schede con orario identico sette giorni su sette e dei giorni di chiusura;
- id Yext di Villa Pineta e Villa Armonia, e destinazione corretta di Parco di Veio e Villa Margherita Vicenza
  (anche per l'import in RetailTune);
- tipo schema.org per tipologia di struttura (comunità psichiatriche, centri ambulatoriali, punti prelievo),
  coerente con l'elenco delle tipologie per RetailTune.

## Dettaglio per scheda

I tre difetti presenti su tutte le schede (`@id` non valido, nessun legame con l'ente, paese assente) sono
omessi dalla colonna «Altri difetti».

| Pagina | id Yext | `@type` Yext | Altri difetti |
|---|---|---|---|
| /it/centri/casa-cima | `KCA00_CIM` | Psychiatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-mioglia | `KCA00_MIO` | Psychiatric, MedicalBusiness, MedicalOrganization | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-pero | `KCA00_PER` | Psychiatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-sampeyre | `KCA00_SAM` | Psychiatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-sanremo | `KCA00_SRM` | Psychiatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-varazze | `KCA00_SAS` | Psychiatric, MedicalClinic | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/casa-villa-maura | `KCA00_MAU` | Psychiatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/centro-ambulatoriale-santo-stefano-ascoli-piceno | `KCA00_OSG` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-camerino | `KCA00_ACA` | MedicalBusiness, MedicalOrganization, Pediatric, LocalBusiness | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-civitanova-marche | `KCA00_ACM` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-fabriano | `KCA00_AFA` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-filottrano | `KCA00_AFI` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-jesi | `KCA00_AJE` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-macerata | `KCA00_AMC` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-matelica | `KCA00_AMA` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-pesaro | `KCA00_APS` | MedicalBusiness, MedicalOrganization, Pediatric, LocalBusiness | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-porto-potenza-picena | `KCA00_APP` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-san-benedetto-del-tronto | `KCA00_ASB` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-san-severino | `KCA00_ASS` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-ambulatoriale-santo-stefano-tolentino | `KCA00_ATO` | MedicalBusiness, MedicalOrganization, LocalBusiness, Pediatric | specialita-come-tipo, sameas-yext |
| /it/centri/centro-cardinal-ferrari | `KCA00_OCF` | MedicalBusiness, MedicalOrganization, MedicalClinic, Physician | sameas-yext |
| /it/centri/centro-medico-riabilitativo | `108` | MedicalClinic, MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/centro-residenziale-santo-stefano-foligno | `KCA00_OFO` | MedicalBusiness, MedicalOrganization, LocalBusiness | sameas-yext |
| /it/centri/centro-santo-stefano-cagli | `KCA00_OAC` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext |
| /it/centri/centro-santo-stefano-macerata-feltria | `KCA00_OBE` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext |
| /it/centri/centro-venerabile-marcucci | `106` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext |
| /it/centri/clinica-dentale-porto-potenza-picena | `101` | Dentist | sameas-yext |
| /it/centri/clinica-dentale-villalba | `100` | Dentist, MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/corte-carcare | `KCA00_TU1` | Pediatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/corte-orero | `KCA00_TU2` | Pediatric | specialita-come-tipo, orari-uguali-7-giorni |
| /it/centri/fidia | `FID00_FIS` | MedicalBusiness, MedicalOrganization, Pediatric, LocalBusiness | specialita-come-tipo, sameas-yext |
| /it/centri/istituto-santo-stefano | `KCA00_OSS` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext |
| /it/centri/myolab | `JES00_JES` | MedicalBusiness, MedicalOrganization, Pediatric, LocalBusiness | specialita-come-tipo, sameas-yext |
| /it/centri/ospedale-san-pancrazio | `KCA00_OSP` | MedicalBusiness, MedicalOrganization, Physician | sameas-yext, orari-uguali-7-giorni |
| /it/centri/poliambulatorio-parco-di-veio | `KCA00_OPV` | LocalBusiness, MedicalBusiness, MedicalOrganization | sameas-yext, url-diverso |
| /it/centri/poliambulatorio-san-martino | `107` | MedicalBusiness, MedicalOrganization, Podiatric, Obstetric | specialita-come-tipo, sameas-yext |
| /it/centri/poliambulatorio-santo-stefano | `KCA00_APO` | MedicalBusiness, MedicalOrganization, Dentist | sameas-yext |
| /it/centri/poliambulatorio-valdaso | `1162064197903061649` | MedicalClinic | sameas-yext |
| /it/centri/poliambulatorio-villa-pineta | `Temporary 1` | — | entita-non-trovata |
| /it/centri/poliambulatorio-villalba | `Villalba` | MedicalBusiness, MedicalOrganization, Dentist | sameas-yext, immagine-su-cdn-yext |
| /it/centri/punto-prelievi-biemme | `VPI00_DLB` | MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/punto-prelievi-delta | `1009` | MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/punto-prelievi-gamma | `2067293087438676369` | MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/punto-prelievi-salus-villalba | `VPI00_DLS` | MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/punto-prelievi-selemar | `PDUrbino` | MedicalBusiness, MedicalOrganization | sameas-yext |
| /it/centri/villa-adria | `KCA00_OVA` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-armonia | `Temporary 7` | — | entita-non-trovata |
| /it/centri/villa-azzurra | `KCA00_OVZ` | MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-dei-pini-civitanova | `VPI00_VPS` | Hospital, MedicalBusiness, MedicalOrganization, Dentist | sameas-yext, immagine-su-cdn-yext |
| /it/centri/villa-dei-pini-firenze | `KCA00_OVP` | MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-fastiggi | `104` | MedicalBusiness, MedicalOrganization | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-jolanda | `KCA00_OVJ` | MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-margherita-benevento | `VMA00_OBN` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-margherita-vicenza-psichiatria | `VMA00_OAR` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext, url-diverso, immagine-su-cdn-yext |
| /it/centri/villa-margherita-vicenza-riabilitazione | `VMA00_OAR` | MedicalBusiness, MedicalOrganization, MedicalClinic | id-duplicato, sameas-yext, url-diverso, immagine-su-cdn-yext |
| /it/centri/villa-pineta | `KCA08202` | Hospital, MedicalBusiness, MedicalOrganization | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-rosa | `KCA00_DEG` | MedicalClinic, Psychiatric | specialita-come-tipo, sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/villa-rosa-diagnostica | `105` | MedicalBusiness, MedicalOrganization | sameas-yext, immagine-su-cdn-yext |
| /it/centri/villa-san-giuseppe | `KCA00_ORE` | MedicalBusiness, MedicalOrganization, MedicalClinic | sameas-yext, immagine-su-cdn-yext |
| /it/centri/villa-sant-alessandro | `KCA00_OSA` | MedicalClinic, Psychiatric | specialita-come-tipo, sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
| /it/centri/ville-di-nozzano | `KCA00_OV1` | MedicalClinic | sameas-yext, orari-uguali-7-giorni, immagine-su-cdn-yext |
