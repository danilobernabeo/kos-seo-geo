# KOS 11 · Dato strutturato dei professionisti

Voce 11 dell'audit KosGroup (settembre 2026, slide 27, priorità media): dato strutturato nelle schede dei medici con
almeno nome, immagine, specializzazione e struttura di appartenenza.

## Stato

- 8/11: parte tecnica pronta nella libreria comune (`shared/snippets/seoSchema.php`, snippet `kosSchema`), pacchetto
  `kosgroup/deploy/kos-11.json`, simulazione superata (`scripts/test/seo-schema-simulazione.php`, casi 777-780),
  collaudo `scripts/collaudo/collaudo-kos-11.mjs`. Da installare sullo stage.
- Dati da IT/TuoTempo (entro il 20/11): servono solo per l'abbinamento delle pagine alle risorse di prenotazione
  (identificativo del gestionale, mai il codice fiscale) e per il widget per medico. Il dato strutturato non ne dipende.

## Cosa esce

Su ogni pagina pubblicata del template `medici` (150 l'8/11; fonte `kosgroup/dati/medici/pagine-medici-20261029.json`)
la pagina (`WebPage`) ha come `mainEntity` il nodo del professionista, nell'unico `@graph` della pagina.

| Caso | Tipo | `@id` | Proprietà |
| --- | --- | --- | --- |
| Medico che opera in una struttura con scheda (nodo `#localbusiness`) | `IndividualPhysician` | `{URL}#medico` | `name`, `url`, `image`, `description`, `medicalSpecialty`, `address` della sede, `practicesAt` → struttura |
| Altro professionista sanitario (fisioterapista, psicologo, infermiere, logopedista, nutrizionista…) | `Person` | `{URL}#person` | `name`, `url`, `image`, `description`, `jobTitle` = ruolo della pagina, `worksFor` → struttura (o ente) |
| Medico senza una sede con scheda | `Person` | `{URL}#person` | come sopra, `worksFor` → ente |

Fonti nella pagina: titolo (nome), TV `ruolo`, `infoImage` (foto), `idSedeLavoro` (struttura), descrizione della pagina.
Nessun dato inventato: un campo vuoto non esce.

Medico o no si decide dal ruolo scritto nella pagina (tabella `$RUOLI_MEDICI` della libreria); un ruolo non
riconosciuto diventa `Person`, perché descrivere come medico chi non lo è sarebbe un dato falso. Classificazione delle
150 pagine pubblicate al 29/10: 100 medici, 50 altri professionisti. Il collaudo scrive per ogni pagina tipo,
specialità e struttura: è l'elenco da far rivedere a KOS.

Specialità dal ruolo con la stessa corrispondenza delle schede struttura (`docs/kos-24.md`): per esempio Ortopedico →
`Musculoskeletal`, Neurologo → `Neurologic`, Radiologo ed Ecografista → `Radiography`, Fisiatra → `Physiotherapy`,
Oculista → `Optometric`, Medicina del dolore → `Anesthesia`. «Medico estetico» resta senza specialità (schema.org non
ne ha una adatta).

## Differenze dall'esempio dell'audit (da motivare nella nota di consegna)

- `IndividualPhysician` invece di `Physician`: dalla versione 28 di schema.org `Physician` indica anche lo studio
  medico, `IndividualPhysician` il singolo medico ([schema.org/IndividualPhysician](https://schema.org/IndividualPhysician)).
- `practicesAt` verso il nodo della struttura (`#localbusiness`, lo stesso delle schede) invece di `worksFor` con una
  struttura scritta per esteso: `worksFor` è una proprietà di `Person`, non di `IndividualPhysician`, e il riferimento
  all'`@id` lega il medico alla stessa entità della scheda e del profilo Google.
- Niente `jobTitle` sui medici (proprietà di `Person`): il ruolo è espresso da `medicalSpecialty`.
- Frammento `#medico` (convenzione degli identificativi del progetto) invece di `#physician`.
- `address` della sede sul medico: Google tratta `IndividualPhysician` come attività locale e vuole l'indirizzo.
- Professionisti non medici come `Person` con `jobTitle` e `worksFor`, secondo la convenzione del progetto.

## Da segnalare a KOS (contenuti delle pagine)

- Refusi nel ruolo, visibili sulla pagina: «Chiururgo generale», «Chirrugo vascolare».
- Ruoli scritti come disciplina invece che come professione: «Cardiologia», «Radiologia».
- Due pagine pubblicate senza struttura (`idSedeLavoro` vuoto) e 18 senza foto (export del 29/10).
- 14 pagine non pubblicate nel template.

## Collaudo

```powershell
$env:AUTH = "<password dello stage>"
node collaudo-kos-11.mjs --base=https://stage.kosgroup.com --uscita=kos11-stage
```

Poi Rich Results Test e validator.schema.org su tre pagine campione: un medico con sede, un fisioterapista, un
medico con più strutture o senza sede.
