# kosgroup.com — voce 24: dato strutturato delle strutture e delle landing

Sviluppo dal 25 ottobre 2026. Analisi di partenza del JSON-LD Yext: `docs/yext-schema-kos.md`.

## Decisioni (25/10)

- **Rilascio separato dagli head.** Il consolidamento degli head (`docs/kos-head-consolidamento.md`) esce nella
  finestra 26/10–6/11 con lo script Yext e i JSON-LD delle landing ancora al loro posto. La voce 24 esce dopo
  l'import dei dati anagrafici corretti (linea C, fuori dalla penale).
- **Fonte dei dati:** l'export Yext corretto da KOS (correzioni entro il 30/10, import 2-6/11), lo stesso da cui
  importa RetailTune. L'export dei profili Google serve come confronto: un confronto a tre tra sito, Yext e Google,
  con le differenze corrette in Yext prima dell'import. Non è la fonte, perché finché c'è Yext è Yext a scrivere nei
  profili Google. Dalle categorie Google si ricava anche la tabella dei tipi schema.org.
- **Landing:** la pagina rimanda alla struttura (`about` → `#localbusiness`) con i servizi della landing, letti da
  una TV. Le FAQ restano come testo visibile, senza `FAQPage` (Google non mostra più il risultato FAQ, maggio 2026).
- **Tipi schema.org:** proposta da rivedere sulle categorie dei profili Google, poi da far approvare a KOS e
  all'agenzia. Le specialità vanno sempre in `medicalSpecialty`, mai nel tipo.

## Come funziona

Un solo `@graph` per pagina, emesso dalla libreria comune (`shared/snippets/seoSchema.php`, installata come
`kosSchema`):

- **Scheda struttura** (template `strutture`, `strutture-VDP`): `WebPage` con `mainEntity` → `{URL scheda}#localbusiness`.
  Il nodo struttura contiene: tipo, nome, URL, descrizione, immagine, telefono, email, indirizzo con
  `addressCountry`, coordinate, `hasMap` (scheda Google Maps dal place_id), orari e orari speciali, specialità,
  marchio, profili ufficiali e `parentOrganization` → `https://kosgroup.com/#organization`.
- **Landing** (template `landing`): `WebPage` con `about` → la struttura della TV `lnd.struttura`. Il nodo della
  struttura è ripetuto per intero, con i servizi della landing in `availableService`: i crawler AI raramente seguono
  un `@id` verso un'altra pagina.
- Nessun dato inventato: un campo vuoto non esce. Tipi e specialità passano da elenchi chiusi di schema.org, e un
  valore sconosciuto si scarta: l'errore di Yext era proprio usare le specialità come tipo.
- Le recensioni non entrano nel dato strutturato: Google non mostra le valutazioni che un'azienda scrive di sé.

Interruttore: impostazione `kos.struttura_attiva`. Vale 0 finché resta lo script Yext, che descrive la stessa
struttura: due descrizioni diverse della stessa entità vanno evitate. Si accende nello stesso rilascio in cui si
tolgono lo script Yext e i JSON-LD delle landing.

### TV anagrafiche (categoria «Scheda struttura (dato strutturato)»)

Nomi uguali sui due siti, così la stessa libreria serve anche le residenze di anniazzurri.it (sprint 3).

| TV | Contenuto | Proprietà |
|---|---|---|
| `str.nome` | nome ufficiale (vuoto = pagetitle) | `name` |
| `str.via`, `str.cap`, `str.citta`, `str.provincia` | indirizzo, provincia in sigla | `address` |
| `str.telefono`, `str.email` | contatti principali, telefono in formato internazionale | `telephone`, `email` |
| `str.lat`, `str.lng` | coordinate in gradi decimali | `geo` |
| `str.orari` | una riga per fascia: `Mo-Fr 08:00-13:00,14:00-18:00` | `openingHoursSpecification` |
| `str.orari_speciali` | `2026-12-25 chiuso`, `2026-12-24 08:00-12:00`, `2026-08-10/2026-08-21 chiuso` | `specialOpeningHoursSpecification` |
| `str.place_id` | identificativo del profilo Google | `hasMap` |
| `str.tipo` | tipo schema.org (vuoto = MedicalClinic) | `@type` |
| `str.specialita` | valori di `MedicalSpecialty` | `medicalSpecialty` |
| `str.marchio` | Santo Stefano Riabilitazione, Neomesia... | `brand` |
| `str.sameAs` | profili ufficiali, uno per riga (mai directory di terzi) | `sameAs` |
| `lnd.struttura` | id della scheda struttura (landing) | `about` |
| `lnd.servizi` | `Tipo\|Nome\|Descrizione` per riga (landing) | `availableService` |

Immagine: `infoImage`, poi `assets/img/header/<alias>.jpg`, poi la prima foto di `assets/img/strutture/<alias>/`.
È la stessa regola dell'og:image delle schede.

## JSON-LD scritti a mano nelle 5 landing (`head-1065`, `-1108`, `-1114`, `-1118`, `-1133`)

Vengono sostituiti dalla libreria. I servizi elencati sono già trascritti nella TV `lnd.servizi`
(`kosgroup/dati/voce-24/landing.json`). Difetti dei blocchi attuali:

- `medicalSpecialty` con valori che schema.org non conosce (`Cardiology`, `Radiology`, `InternalMedicine`,
  `Nutrition`, `Neuropsychology`, `Psychiatry`, `Psychology`): i nomi corretti sono `Cardiovascular`, `Radiography`,
  `DietNutrition`, `Psychiatric`;
- struttura senza `@id`, con nome diverso dalla scheda («Centro Cardinal Ferrari KOS») e collegata al gruppo solo per
  nome (`parentOrganization` senza `@id`), quindi un'entità in più per ogni landing;
- su `head-1133` l'URL della clinica è quello della landing, non della scheda di Villa Sant'Alessandro;
- 4 landing hanno un secondo `BreadcrumbList` scritto a mano, oltre a quello della libreria;
- `FAQPage` su tutte e 5: con la voce 24 le FAQ restano solo come testo visibile.

## Pacchetti e rilascio

1. **Preparazione** (`kosgroup/deploy/kos-24.json`, nessun effetto visibile): impostazioni dei template, 18 TV
   nuove (16 `str.*`, 2 `lnd.*`), libreria aggiornata, interruttore spento. Sullo stage subito; in produzione con gli head o prima
   dell'import.
2. **Dati:** import delle TV `str.*` dall'export Yext corretto, dopo il confronto con i profili Google; TV delle
   landing con `scripts/deploy/imposta-tv.php /www kosgroup/dati/voce-24/landing.json` (prova, poi `--scrivi`;
   backup e ripristino automatici).
3. **Attivazione** (`kosgroup/deploy/kos-24-attivazione.json`): `kos.struttura_attiva = 1`, `head-extra-strutture`
   senza lo script Yext, `head-extra-landing` senza i JSON-LD `head-<id>`. Ripristino con il manifest che l'installer
   scrive nel backup.

Collaudo: un solo `@graph` per pagina e nessun JSON-LD Yext; `#localbusiness` su tutte le schede pubblicate, con
indirizzo, telefono e coordinate; Rich Results Test e validatore schema.org su un campione per tipo di struttura;
landing con `about` verso la scheda corretta e FAQ visibili nel testo.

## Import dall'export Yext (prova del 25/10)

`scripts/yext/strutture-in-tv.py <export.xlsx>` produce `kosgroup/dati/voce-24/strutture-yext-<data>.json`, da applicare
con `imposta-tv.php`, e `report-yext-<data>.md`. Export del 25/10: 60 entità su kosgroup.com, 57 schede importabili.
Non entrano: le due entità di Poliambulatorio Villalba sulla stessa scheda e Villa Margherita Vicenza, che punta a
`/it/villa-margherita`. Il report elenca quello che va corretto in Yext entro il 30/10: 20 schede con lo stesso orario
sette giorni su sette, 9 email assenti, 4 place_id mancanti, il marchio «0» di Casa Varazze, 34 indirizzi abbreviati («V.», «V.le»)
che lo script scrive per esteso. L'export conferma anche che i profili Google sono collegati all'organizzazione Google
di Yext («GMB Organization»): al passaggio a RetailTune la proprietà dei profili va trasferita a un account di KOS.
