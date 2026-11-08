# KOS 11 · Dato strutturato dei professionisti

Voce 11 dell'audit KosGroup (settembre 2026, slide 27, priorità media): dato strutturato nelle schede dei medici con
almeno nome, immagine, specializzazione e struttura di appartenenza.

## Stato

- 8/11 sera, **produzione**: collaudo 150/150 (91 `IndividualPhysician`, 59 `Person`), schede e landing 63/63.
- 8/11, collaudo sullo stage: 150 pagine, 91 `IndividualPhysician` e 59 `Person`; 2 KO per spazi doppi o finali
  nel titolo della pagina (Michele Tulli, Rolando Gregorio Edoardo Rocchetti), corretti nella libreria (nome con gli
  spazi normalizzati). I 9 medici `Person` sono i 7 di Villa Margherita Vicenza (id 233), che non ha ancora il nodo
  struttura (esclusa dall'import della voce 24 in attesa della decisione di KOS: una entità, due pagine), e 2 senza
  struttura. Con il nodo di Villa Margherita Vicenza diventano `IndividualPhysician` senza altri interventi (29
  professionisti della struttura in tutto).
- 8/11, dopo la correzione: collaudo sullo stage 150/150 OK; Rich Results Test su Stefano Albanelli (pagina
  dell'esempio dell'audit): 5 elementi validi (Breadcrumb; Attività locali 1, solo `priceRange` facoltativo mancante;
  Società 3: struttura, medico, ente). Evidenze in `docs/evidenze/kos-11-20261108/`.
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
| Medico che opera in una struttura con scheda (nodo `#localbusiness`) | `IndividualPhysician` | `{URL}#medico` | `name`, `url`, `image`, `description`, `medicalSpecialty`, `address` e `telephone` della sede (centralino), `practicesAt` → struttura |
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
- `address` e `telephone` della sede sul medico: Google tratta `IndividualPhysician` come attività locale, vuole
  l'indirizzo e consiglia il telefono; il numero è il centralino della sede, a cui si prenota. `priceRange` non esce
  (nessuna tariffa nelle pagine): resta l'avviso facoltativo del Rich Results Test.
- Professionisti non medici come `Person` con `jobTitle` e `worksFor`, secondo la convenzione del progetto.

## Da segnalare a KOS (contenuti delle pagine)

- Refusi nel ruolo, visibili sulla pagina: «Chiururgo generale», «Chirrugo vascolare».
- Ruoli scritti come disciplina invece che come professione: «Cardiologia», «Radiologia».
- Corretti da Danilo in produzione l'8/11: ruoli di Andrea Conti, Arcangela Rozza, Diego Spagnolo, Elena Vezzelli;
  spazio finale nel titolo di Rocchetti; spazio non separabile (U+00A0, invisibile nel manager) tra nome e cognome di
  Michele Tulli, corretto lo stesso giorno.
- Cosimo Argentieri (Direttore Sanitario e Responsabile Scientifico e Qualità) fa le televisite psichiatriche di Villa
  Sant'Alessandro: Danilo chiede a KOS (9/11) se associarlo a quella struttura (TV `idSedeLavoro`); con la sede diventa
  `IndividualPhysician` con `practicesAt`, senza altri interventi. Finché non c'è risposta resta `Person` legato all'ente.
- Senza struttura per scelta (8/11): Alessandro Stronati (Kos Academy, Comitato Scientifico) non sono legati a una sede: `Person` con `worksFor` verso
  l'ente, che è il dato corretto.
- Due pagine pubblicate senza struttura (`idSedeLavoro` vuoto) e 18 senza foto (export del 29/10).
- 14 pagine non pubblicate nel template.

## Collaudo

```powershell
$env:AUTH = "<password dello stage>"
node collaudo-kos-11.mjs --base=https://stage.kosgroup.com --uscita=kos11-stage
```

Poi Rich Results Test e validator.schema.org su tre pagine campione: un medico con sede, un fisioterapista, un
medico con più strutture o senza sede.
