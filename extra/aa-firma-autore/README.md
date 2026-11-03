# Firma autore degli approfondimenti — anniazzurri.it

## Scopo

I 265 approfondimenti di anniazzurri.it sono oggi **anonimi**: nessuna firma
visibile, nessun `author` nel markup, e nessuna delle pagine emette uno schema
`Article`. Si parte da zero, non si migliora un markup esistente.

Questo lavoro attribuisce una firma reale e verificabile agli articoli che la
reggono, e attribuisce gli altri all'organizzazione. L'effetto cercato non è il
markup in sé: è che su contenuti sanitari letti da familiari di persone fragili
compaia una professionista identificabile, con un numero d'albo e una pubblicazione
scientifica dietro, invece del nulla.

**Perimetro:** anniazzurri.it. Su kosgroup.com non si tocca niente.

---

## Le decisioni prese

### Chi firma

**Antonella Schiavino**, infermiera, Referente Qualità ed Accreditamento del Gruppo
KOS Care, iscritta all'OPI di Torino n. IP/20414, 26 anni nelle RSA, co-autrice di
uno studio multicentrico su 6.400 residenti presentato al 40° Congresso Nazionale
SIGOT (DOI `10.4081/gc.2026.15812`).

È l'unica autrice: non ci sono altri firmatari e non è disponibile, per ora, un
medico per la revisione clinica.

### Come si dividono i 265 articoli

| Decisione | Quanti | Cosa significa |
|---|---|---|
| `firma` | **62** | Firmabili subito: TV `aut.ref` → risorsa Antonella Schiavino |
| `da correggere` | **21** | Nel suo perimetro, ma bloccati finché il testo non è corretto |
| `redazione` | **182** | `author` sull'organizzazione, nessuna `Person` |

83 articoli sono nel perimetro di Antonella (62 + 21). In `decisioni.csv` i 62
firmabili sono **esattamente le righe con `decisione = firma`**: nessuna
interpretazione, nessun flag da incrociare.

### Perché 21 restano fuori

Non è il merito a bloccarli: il contenuto è nel suo perimetro. Sono affermazioni
non attribuite («recenti studi dimostrano…» senza citare nulla), raccomandazioni
commerciali («il ricovero in una RSA è certamente la soluzione più indicata», sul
sito di chi vende quel servizio), numeri clinici senza fonte e refusi che, sotto un
nome e un numero d'albo, smettono di essere un difetto redazionale e diventano
esposizione personale della professionista.

Il dettaglio articolo per articolo è in `correzioni-rimandate.md`. Corretto il
testo, la riga passa a `firma` e si riapplica la TV per quella sola risorsa.

### Il criterio che ha prodotto la divisione

> La firma va dove **l'esperienza professionale di Antonella è la fonte** di ciò che
> l'articolo afferma.

Non è «clinico sì / clinico no». Quattro domini restano esclusi, e non sono tutti
clinici: **medico** (diagnosi, terapie, farmaci, vaccinazioni, valori di
laboratorio), **legale-normativo** (Legge 104, invalidità, normativa RSA — anche
perché quel contenuto invecchia a ogni scadenza di legge), **psicologico
specialistico**, **riabilitativo-fisioterapico** (programmi di esercizi con serie e
ripetizioni). Cronaca, eventi, comunicati e interviste vanno alla redazione: non
contengono competenza da attribuire, e firmare tutto svaluta la firma dove conta.

Una sola eccezione prevale su tutto: se il contenuto riporta **uno studio di cui
Antonella è autrice**, la firma è sua anche se la forma è quella di un comunicato.
Vale per `anni-azzurri-al-congresso-nazionale-sigot-2026-...`.

### Forma della firma

**«Antonella Schiavino», senza «Dottoressa».** Il diploma è di scuola per infermieri
del vecchio ordinamento, che non conferisce il titolo di dottore, e non ci sono
titoli universitari successivi (verificato anche sul profilo LinkedIn, che non ha
sezione Formazione). Un `honorificPrefix` «Dott.ssa» accanto a un `hasCredential`
che dichiara quel diploma sarebbe un'incoerenza leggibile a macchina su un sito
sanitario — e non porta alcun segnale in più: la qualificazione la portano
`hasCredential`, `identifier` con il numero d'albo e il DOI.

In pagina la firma è: **Antonella Schiavino — Referente Qualità ed Accreditamento**.

### Società di riferimento

**KOS Care S.r.l.**, P.IVA 01148190547, confermata dal referente. L'`Organization`
resta ancorata ad anniazzurri.it; il legame con KOS S.p.A. (P.IVA 10674520159) è
`parentOrganization`, **mai `sameAs`**: sono soggetti giuridici distinti con partite
IVA diverse, e `sameAs` li dichiarerebbe la stessa entità.

### Firma della redazione

**«Redazione Anni Azzurri»**, confermata dal referente. In pagina compare come
*«A cura della Redazione Anni Azzurri»*; nel markup `author` punta all'`@id`
dell'organizzazione.

**Nessuna `Person` inventata per la redazione.** In territorio YMYL sanitario è il
modo più rapido di farsi male: una persona che non esiste, con un nome che nessuno
può verificare, è peggio di nessuna firma. L'organizzazione invece è un soggetto
identificabile, con partita IVA e sede, che si assume la responsabilità editoriale.

### LinkedIn escluso da `sameAs`

Il profilo esiste (`linkedin.com/in/antonella-schiavino-b535b7304/`) ma **resta
fuori**. Oggi non ha foto né headline, riporta un'unica esperienza «Infermiere
coordinatore» collegata a una pagina azienda inesistente, 38 contatti, e non nomina
né KOS Care né Anni Azzurri.

Un `sameAs` serve a far **confermare** l'entità da una fonte terza. Un crawler che
segue quel link non trova conferma di quello che la scheda dichiara: trova una
contraddizione. Si aggiunge quando il profilo viene aggiornato — nel frattempo il
DOI copre quella funzione, ed è una credenziale di un altro ordine: identificatore
persistente, risolvibile, indicizzato in Scopus, DOAJ, Google Scholar e OpenAlex.

---

## Cosa è cambiato dopo il 14 ottobre

Tre decisioni sono state riviste. **Questa è la versione valida.**

**1. Il frammento delle strutture: `#struttura` → `#localbusiness`.**
La convenzione degli `@id` del repository (decisa il 20/10, in `CLAUDE.md`) fissa
`{URL scheda}#localbusiness` per le strutture. La prima stesura di questo lavoro
usava `#struttura`. Riguarda `aut.affiliazione` sulla pagina autore e i riferimenti
alle strutture negli articoli. È il disallineamento più insidioso dei tre: non si
vedrebbe finché le pagine struttura non iniziano a emettere i propri nodi, e a quel
punto i riferimenti punterebbero a entità inesistenti, in silenzio.

**2. `sameAs` verso kosgroup.com → `parentOrganization`.**
Corretto il 15/10. kosgroup.com non espone JSON-LD e il suo footer dichiara KOS
S.p.A. con partita IVA diversa da KOS Care: sono due società, non due nomi della
stessa. Il nodo completo di KOS S.p.A. nasce con lo sprint 2; il riferimento si può
pubblicare da subito e si fonderà con quello, perché l'`@id` è lo stesso.

**3. Forma canonica verificata e fissata (15/10).**
`https://anniazzurri.it`, senza www e senza slash finale sulle pagine interne.
Quattro indicatori concordi: `www.anniazzurri.it` risponde 301 verso la forma nuda,
la forma nuda risponde 200, la sitemap elenca URL senza www e i canonical delle
pagine pure. L'ente di gruppo è `https://kosgroup.com/#organization` — senza www,
ancorato alla radice e **non** a `/it`, anche se la radice reindirizza lì: una
organizzazione non ha una lingua.

Invariato rispetto al 14/10: i conteggi (83 / 62 / 21 / 182), la firma senza
«Dottoressa», KOS Care come società di riferimento, «Redazione Anni Azzurri»,
l'esclusione di LinkedIn.

---

## Le Template Variable `aut.*`

### Sul template della pagina autore (`tpl.Autore`)

| TV | Tipo MODX | Valori ammessi | A cosa serve nel dato strutturato |
|---|---|---|---|
| `aut.given` | text | testo libero | `Person.givenName` |
| `aut.family` | text | testo libero | `Person.familyName`; insieme a `given` compone `Person.name` |
| `aut.honorific` | text | vuoto, oppure `Dott.`, `Dott.ssa`, `Prof.` | `Person.honorificPrefix`, emesso solo se valorizzato. **Vuoto per Antonella Schiavino** |
| `aut.jobTitle` | text | testo libero | `Person.jobTitle` |
| `aut.bio` | richtext | 3-4 righe, terza persona | `Person.description` (i tag vengono rimossi) |
| `aut.foto` | image | percorso relativo al webroot | `Person.image` come `ImageObject`; `width` e `height` sono letti dal file reale, non scritti a mano |
| `aut.professione` | text | testo libero | `Occupation.name` dentro `Person.hasOccupation` |
| `aut.professione_cat` | text | testo libero | `Occupation.occupationalCategory` |
| `aut.knowsAbout` | textarea | una competenza per riga | `Person.knowsAbout` (array di stringhe) |
| `aut.albo_nome` | text | nome esteso dell'ordine | `identifier.propertyID` + `hasCredential.recognizedBy.name` + `memberOf.name` |
| `aut.albo_numero` | text | numero di iscrizione | `identifier.value` (`PropertyValue`) + `hasCredential.identifier` |
| `aut.credential` | textarea | una riga per titolo, anno facoltativo dopo `\|` | `hasCredential` di tipo `degree` |
| `aut.sameAs` | textarea | un URL per riga | `Person.sameAs`. Il DOI vi viene aggiunto automaticamente: non va ripetuto |
| `aut.affiliazione` | resource list (schede struttura) | id risorsa, o vuoto | `Person.affiliation` → `{URL scheda}#localbusiness` |
| `aut.pub_titolo` | text | titolo della pubblicazione | `subjectOf` di tipo `ScholarlyArticle` |
| `aut.pub_doi` | text | DOI senza prefisso URL | `identifier` del `ScholarlyArticle` + voce in `sameAs` |
| `aut.pub_url` | text | URL dell'abstract | `url` del `ScholarlyArticle` |
| `aut.pub_data` | text | `AAAA-MM-GG` | `datePublished` del `ScholarlyArticle` |
| `aut.consenso` | checkbox | 0 / 1 | Nessun uso nel markup: è il **gate di pubblicazione**. Senza la spunta la pagina non va pubblicata |

`albo_nome` e `albo_numero` vanno **insieme**: se uno dei due è vuoto, né
`identifier` né la credenziale professionale vengono emessi.

### Sul template degli articoli

| TV | Tipo MODX | Valori ammessi | A cosa serve |
|---|---|---|---|
| `aut.ref` | resource list, limitata al contenitore `/autori/` | id risorsa autore, **oppure vuoto** | **Vuoto = redazione.** È la regola cardine: `Article.author` punta all'`@id` dell'organizzazione |

Accanto, non `aut.*` ma necessarie allo stesso markup: `rev.ref` e `rev.data` (per
la futura revisione clinica, oggi sempre vuote), `art.medical` (checkbox: 1 →
`MedicalWebPage` con `audience` di tipo paziente), `art.immagine`, `art.about` e
`art.mentions` (resource list multiple verso patologie e strutture).

**Perché `aut.ref` vuota significa redazione** e non c'è un valore esplicito: così
il comportamento predefinito è quello giusto. Nessuno deve ricordarsi di impostare
qualcosa sui 182 articoli della redazione, e un articolo nuovo nasce già attribuito
correttamente.

---

## Il box autore a fondo articolo

Due elementi visibili per articolo.

**Sotto il titolo**, accanto alla data già presente: la riga di firma.

**A fine articolo**, dentro `<main id="main-content">`, il box completo.

### Articolo firmato

```
[foto 120×120, tonda]   Antonella Schiavino            ← link alla pagina autore
                        Referente Qualità ed Accreditamento
                        [biografia breve, aut.bio, 2-3 righe]
                        Chi è Antonella Schiavino        ← link alla pagina autore
```

**Box tolto dal template il 3/11** (decisione di Danilo): basta la firma sotto il titolo, con foto tonda, nome con link alla pagina autore e ruolo, come nel modello chiesto dal cliente. Lo snippet sa ancora generare il box (`[[aaSchema? &type=`box`]]`). Prima era stato reso sintetico: iscrizione all'albo, pubblicazione
scientifica, competenze e percorso completo stanno nella pagina autore e nel nodo `Person`, non nel box.

Il nome e «Scheda completa» puntano entrambi a `/autori/antonella-schiavino`. I link
di `sameAs` escono con un'etichetta parlante («Pubblicazione scientifica»,
«Profilo LinkedIn») e non come URL nudi.

### Articolo della redazione

**Aggiornamento 3/11 (decisione di Danilo):** sotto il titolo esce la firma «Redazione Anni Azzurri» con
sotto «Gruppo KOS» e il monogramma AA della favicon (`aa.redazione_logo`, `aa.redazione_sottotitolo`),
nome non cliccabile: non c'è una pagina della redazione. Quello che segue descrive la scelta precedente.

**Nessun markup** (decisione del 3/11, in implementazione): né riga di firma né box.
L'attribuzione alla redazione resta nel dato strutturato (`author` = organizzazione).
La riga *«A cura della Redazione Anni Azzurri»* è predisposta e si accende con
`[[aaSchema? &type=`box` &redazione=`1`]]` se un giorno la si vuole visibile.

**Non deve comparire un box vuoto** con il riquadro della foto mancante: è uno dei
controlli a occhio da fare in collaudo.

### Revisione clinica (predisposta, oggi inattiva)

Quando `rev.ref` sarà valorizzata, sotto il box compare:

> *Revisione clinica a cura di [nome], [ruolo] — [data]*

### Regole non negoziabili

**Il box visibile e il markup devono dire la stessa cosa, dalla stessa sorgente.**
Un JSON-LD che dichiara un autore che in pagina non compare è, nella migliore delle
ipotesi, ignorato. Entrambi leggono le stesse TV: nessun dato duplicato a mano.

Le classi CSS sono `box-autore` con i modificatori `__foto`, `__nome`, `__ruolo`,
`__bio`, `__albo`, `__link`, `__profilo`, più `box-autore--redazione` e
`box-revisione`.

---

## La pagina autore

URL: `/autori/antonella-schiavino`. Contenitore `/autori/` da creare, `hidemenu` 1.

**L'alias si decide una volta e non si tocca più.** È la chiave dell'entità: se
cambia l'URL cambia l'`@id`, e per i motori diventa una persona nuova.

### Sezioni, nell'ordine

1. **Intestazione** — foto 200×200, `<h1>` con il nome, ruolo, riga dell'albo.
   Testo fisso del ruolo: *«Referente Qualità ed Accreditamento — Gruppo KOS Care»*.
   Testo fisso dell'albo: *«Infermiera, iscritta all'Albo delle Professioni
   Infermieristiche di Torino con il n. IP/20414»*.
2. **Il percorso professionale** — due paragrafi di biografia.
3. **Aree di competenza** — elenco puntato, le cinque voci di `aut.knowsAbout`.
4. **Formazione e iscrizione professionale** — diploma 1997 e iscrizione OPI.
5. **Pubblicazioni** — citazione completa con tutti e nove gli autori, Antonella in
   grassetto, e il link al DOI.
6. **Articoli firmati** — elenco generato, non scritto a mano.

I testi completi sono in `autore-antonella-schiavino.json`, chiave
`contenuto_visibile`.

### L'elenco degli articoli firmati

Si genera interrogando la TV `aut.ref` delle risorse figlie del contenitore news
(id 8), ordinate per `publishedon` decrescente, con link assoluti. Su anniazzurri.it
sono disponibili pdoTools e getResources.

Scriverlo a mano significherebbe aggiornarlo a ogni articolo nuovo, e si
disallineerebbe al primo che qualcuno dimentica.

### La pagina va linkata da un punto reale del sito

Una pagina autore orfana viene ignorata: senza link interni è come se non
esistesse. Il posto naturale è **«La nostra équipe»**, che esiste già.

---

## Il markup

Lo snippet `aaSchema` **esiste già nel repository**: qui non va scritto codice.
Quattro vincoli dalle convenzioni del repository, che valgono anche per questo
extra:

- **Un solo `@graph` JSON-LD per pagina**, emesso lato server dalla libreria comune.
  Il nodo `Person` e l'`author` dell'`Article` entrano in quello, non in un secondo
  blocco.
- **Niente chiamate non in cache (`[[!...]]`) negli head**: pesano sul TTFB. Il
  JSON-LD va in cache. I dati dell'autore cambiano una volta l'anno, non ha senso
  ricalcolarli a ogni richiesta. Serve un plugin su `OnDocFormSave` che svuoti la
  cache quando viene salvata una risorsa autore.
- **Link interni assoluti** (`link_tag_scheme` = abs).
- **Niente dato strutturato scritto a mano nei chunk.**

Il nodo `Person` va **ripetuto per intero su ogni articolo firmato**, non ridotto a
un riferimento: i crawler degli assistenti AI raramente seguono un `@id` verso
un'altra pagina, e l'articolo deve bastare a sé stesso. L'`@id` però punta sempre
alla pagina autore: è quello a rendere la persona una sola entità su 62 articoli
invece di 62 omonimi.

---

## `decisioni.csv`

265 righe, una per articolo. Separatore virgola, UTF-8 senza BOM.

| Colonna | Contenuto |
|---|---|
| `id_modx` | **Vuota.** Da ricavare (vedi sotto) |
| `uri` | `site_content.uri` senza slash iniziale — la chiave di join |
| `titolo` | `pagetitle` |
| `decisione` | `firma` \| `da correggere` \| `redazione` |
| `motivo` | Perché; per i bloccati, che cosa blocca |

Ordinato per decisione (prima i 62 firmabili), poi per `uri`.

### Perché `id_modx` è vuota

L'export dei contenuti presente nel repository è del 23 ottobre e non contiene gli
articoli pubblicati dopo: ricostruire gli id da lì sarebbe una **stima**, e un id
sbagliato firma in silenzio l'articolo sbagliato. La chiave `uri` invece è
autoritativa e completa.

Si riempie con una query sul database, che dà 265 id verificati:

```sql
SELECT id, uri
FROM modx_site_content
WHERE context_key = 'web' AND deleted = 0;
```

e si incrocia su `uri`. In alternativa l'applicazione può lavorare direttamente su
`uri`, che è univoco in MODX: l'id è una comodità, non un requisito.

### Dieci righe sono pagine di servizio

Marcate nel `motivo`: *«Pagina di servizio, non un approfondimento: nessun markup
Article.»* Sono sotto le 150 parole (auguri, panchina rossa, video). Vanno escluse
dal markup `Article`, non solo dalla firma.

### Un URL non è un articolo

`il-segreto-per-tenersi-in-forma` risponde 200 ma restituisce l'indice delle news,
con canonical su `/news/`. È un soft 404 da girare al fornitore tecnico, fuori dal
perimetro di questo lavoro.

---

## Elementi toccati

Nessun elemento esistente viene modificato nella sostanza; si aggiungono elementi e
si estendono due template.

| Elemento | Azione |
|---|---|
| Contenitore `/autori/` | nuovo |
| Template `tpl.Autore` | nuovo |
| TV `aut.*` della pagina autore (19) | nuove |
| TV `aut.ref`, `rev.ref`, `rev.data`, `art.*` sul template articoli | nuove |
| Template degli approfondimenti | due chiamate aggiunte: JSON-LD nell'head, box nel corpo |
| Template della pagina autore | chiamata al JSON-LD `ProfilePage` |
| Plugin su `OnDocFormSave` | nuovo, per lo svuotamento mirato della cache |
| CSS | classi `box-autore*`, `scheda-autore*` |
| Pagina «La nostra équipe» | un link alla pagina autore |

**Prima di toccare il template degli approfondimenti**, controllare
`docs/elementi-sotto-progetto.md` e i README degli altri lavori in `extra/`: un
elemento ha un solo lavoro proprietario alla volta.

---

## Rilascio

Prima lo stage, poi la produzione; prima la prova, poi `--scrivi`.

**L'ordine conta.** La pagina autore va creata **per prima**: il suo URL *è* l'`@id`
dell'entità, e finché non esiste nessun articolo può referenziarla.

1. Impostazioni di sistema e TV (additive, nessun rischio).
2. Contenitore, template e risorsa autore; popolamento delle TV dal JSON; foto
   caricata.
3. Link dalla pagina «La nostra équipe».
4. Prova dello snippet **su una risorsa non pubblicata** con il template degli
   approfondimenti, vista in anteprima dal manager. Se qualcosa si rompe, si rompe
   su una pagina che nessuno vede.
5. Chiamate nel template vero, **in due tempi**: prima il JSON-LD nell'head,
   verifica che le pagine stiano in piedi, poi il box visibile. Così, se qualcosa si
   rompe, si sa subito quale dei due.
6. Applicazione massiva della TV `aut.ref` sui 62.

### Due trappole nell'applicazione massiva

**Non usare il processor `resource/update`.** Aggiorna `editedon`, che alimenta
`dateModified`: firmare 62 articoli in un pomeriggio segnalerebbe a Google 62
aggiornamenti di contenuto mai avvenuti. Si scrive direttamente il valore della TV.

**Backup prima.** Come da regola del repository, tabella `..._bak_AAAAMMGG` prima di
UPDATE massivi sul database.

---

## Ripristino

L'operazione è reversibile a ogni livello.

- **Una singola firma:** si svuota `aut.ref` su quella risorsa e torna alla
  redazione.
- **Tutte le firme:** si svuota `aut.ref` su tutte le risorse, oppure si ripristina
  la tabella `modx_site_tmplvar_contentvalues` dal backup. Il sito torna allo stato
  «tutto redazione», che è comunque valido.
- **L'intero markup:** si tolgono le due chiamate dal template degli
  approfondimenti. Si torna allo stato attuale, cioè nessuno schema `Article`.
- **La pagina autore:** si depubblica. Lo snippet ricade automaticamente
  sull'organizzazione quando la risorsa autore non è pubblicata, quindi gli articoli
  non restano con un `author` rotto.

Nessun contenuto degli articoli viene modificato da questo lavoro: le correzioni
redazionali sono un filone separato, descritto in `correzioni-rimandate.md`.

---

## Collaudo

Su sei pagine: due firmate, due della redazione, una sanitaria, una no.

- Schema Markup Validator di schema.org — validità sintattica.
- Rich Results Test — idoneità Google.
- `curl -s -A "GPTBot" <url> | grep -c 'application/ld+json'` — **è il test che
  conta**: conferma che il blocco esce server-side.
- **A occhio**, e vale quanto i validatori: il nome nel markup e il nome in pagina
  sono identici carattere per carattere? Il box compare? Sulle pagine della
  redazione **non** compare un box vuoto?

Poi reindicizzazione in Search Console delle pagine firmate più importanti.

---

## File in questa cartella

| File | Contenuto |
|---|---|
| `README.md` | questo |
| `autore-antonella-schiavino.json` | tutti i valori della pagina autore, TV per TV |
| `decisioni.csv` | 265 righe: `uri`, titolo, decisione, motivo |
| `correzioni-rimandate.md` | i 21 bloccati e i 4 recuperabili, con cosa correggere |
| `foto/` | versione quadrata pronta da pubblicare; l'originale resta in locale (`.gitignore`) |
| `installa.php` | installazione con prova, `--scrivi`, `--non-pubblicare`, `--pubblica`, backup e ripristino |
| `firme.php` | compilazione di `aut.ref` dai «firma» di `decisioni.csv`, con tabella di backup e ripristino |
| `elementi/` | template `autore`, chunk `head-autore` e `autore-articolo-firmato`, plugin `aaFirmaAutoreCache` |
| `files/assets/firma-autore/firma-autore.css` | stili di firma, box e pagina autore |

## Punti aperti

1. **`aut.affiliazione`**: TV predisposta e vuota. L'abstract SIGOT dichiara «Anni
   Azzurri-KOS Care, Carmagnola»: finché Danilo non conferma dove opera, nessun legame
   con una struttura nel grafo; l'appartenenza resta a KOS Care tramite `worksFor`.
2. **Regola web sugli URL annidati** (vedi «Implementazione»): senza l'eccezione
   `autori/`, `/autori/antonella-schiavino` risponde 404 da nginx.

---

## Implementazione (3/11/2026)

### Elementi

| Elemento | Proprietario | Cosa |
|---|---|---|
| categoria «Firma autore» | extra | raccoglie TV, template, chunk e plugin |
| template `autore` | extra | pagina autore: head proprio, menu, briciole (`breadcrumb-stripe`), intestazione dalle TV (foto con lo snippet `immagine`, `<h1>` = pagetitle, ruolo + «— Gruppo KOS Care», riga dell'albo = riassunto), sezioni 2-5 nel contenuto (dal JSON, `contenuto_visibile`), sezione 6 generata con `pdoResources` in cache (`&tvFilters=`aut.ref===[[*id]]``, figli di 8, `publishedon` decrescente, link `/[[+uri]]`). `aaSchema` lo riconosce dal nome (`aa.tpl_profile` = `autore`) ed emette ProfilePage + Person |
| chunk `head-autore` | extra | come `head`, con `og:type` profile, `og:url` dall'URI vero (il `head` comune usa l'alias, sbagliato per una pagina in `/autori/`), `profile:first_name/last_name`, `og:image` dalla foto, nessuna chiamata non in cache, riga `searchable` |
| chunk `autore-articolo-firmato` | extra | voce dell'elenco «Articoli firmati» (in un chunk: un `@INLINE` con i backtick della data spezza la chiamata) |
| plugin `aaFirmaAutoreCache` | extra | OnDocFormSave, OnDocPublished, OnDocUnPublished, OnResourceDelete, OnResourceUndelete: pagina autore salvata → cache dei suoi articoli; articolo con `aut.ref` salvato → cache delle pagine autore. `clearCache()` con il contesto della risorsa (senza argomento userebbe `mgr` e non svuoterebbe nulla) |
| TV `aut.*` (19) sul template `autore`, `aut.ref` sul template `interna-articolo` | extra | come da tabelle sopra; `aut.ref` limitata alle pagine con template `autore` in `/autori/`; `aut.affiliazione` alle schede dei template `rsa` e `centro-diurno` |
| contenitore `/autori/` | extra | **non pubblicato**, nascosto dal menu, non ricercabile; il suo id va in `aa.breadcrumb_exclude`: briciole Home → autore, visibili e `BreadcrumbList` uguali, nessuna pagina `/autori/` vuota da indicizzare |
| pagina `/autori/antonella-schiavino` | extra | valori del JSON; **URI fissato** (`uri_override`): sul sito `use_alias_path` è spento e gli URL sono piatti, senza il fermo la pagina finirebbe in `/antonella-schiavino`. Pubblicata solo con `aut.consenso` = 1 |
| «La nostra équipe» (risorsa 129) | extra | in fondo al contenuto il blocco «Chi firma i nostri approfondimenti» con `[[~id]]` della pagina autore (testo da rivedere se serve: è in `installa.php`, `$EQUIPE_BLOCCO`) |
| `/assets/firma-autore/firma-autore.css`, `/assets/img/autori/antonella-schiavino.jpg` | extra | file nuovi |
| `aaSchema` (+ `shared/snippets/seoSchema.php` allineato) | **progetto, in coordinamento** | `&type=`firma`` (nuovo) e `&type=`box``: vuoti sugli articoli della redazione (`&redazione=`1`` rimette la riga), link `/...` invece dell'URL canonico (regola dei link assoluti; sullo stage restano sullo stage), foto con lo snippet `immagine`, `<aside>`; `headline` sullo ScholarlyArticle (avviso del Rich Results Test) |
| template `interna-articolo` | **progetto, in coordinamento** | `[[aaSchema? &type=`firma`]]` accanto alla data, `[[aaSchema? &type=`box`]]` sotto il contenuto, foglio di stile solo se `aut.ref` è compilata |
| `aa.breadcrumb_exclude` | **progetto, in coordinamento** | + id del contenitore `/autori/` |

Le due versioni del progetto si installano con `anniazzurri/deploy/aa-firma-autore.json`
(`scripts/deploy/installa-elementi.php`, controllo sulla baseline e backup). Il JSON-LD degli
articoli era già in produzione (AA-8, `footer-script`): questo lavoro aggiunge solo la parte visibile.

Differenze dalla prima stesura di questo README: template `autore` (non `tpl.Autore`, è il nome
che `aaSchema` riconosce); nessuna riga visibile sulla redazione; `rev.ref`, `rev.data`,
`art.medical`, `art.about`, `art.mentions` non create (vuote oggi, `aaSchema` le legge se
compaiono: si creano con la revisione clinica).

### Regola web (pannello MODX Cloud, stage e produzione)

Dal 31/10 le regole web di anniazzurri.it danno 404 da nginx a ogni URL a due livelli fuori dalle
cartelle tecniche: `/autori/antonella-schiavino` non arriva a MODX. Nella riga

```nginx
if ($uri ~ "^/(?!assets/|dist/|manager/|connectors/|core/|setup/|\.well-known/)[^/]+/[^/]") { return 404; }
```

si aggiunge `autori/` alle eccezioni:

```nginx
if ($uri ~ "^/(?!assets/|dist/|manager/|connectors/|core/|setup/|\.well-known/|autori/)[^/]+/[^/]") { return 404; }
```

Un URL inventato sotto `/autori/` arriva a MODX, che risponde 404 (nessun plugin su OnPageNotFound).
Va fatto **prima** di pubblicare la pagina in produzione; sullo stage finché manca la pagina si
vede solo da `index.php?id=<id>`. Dopo la modifica, salvare la copia aggiornata delle regole in
`anniazzurri/files/web-rules/`.

### Comandi (PowerShell)

Archivio, uguale per stage e produzione:

```powershell
git -C $R pull
git -C $R archive -o $env:TEMP\firma.tar HEAD extra/aa-firma-autore anniazzurri/elements/snippets/aaSchema.php anniazzurri/elements/templates/interna-articolo.html anniazzurri/baseline/templates/interna-articolo.html anniazzurri/deploy/aa-firma-autore.json scripts/deploy/installa-elementi.php
scp $env:TEMP\firma.tar aa-prod:
Start-Sleep 15
```

Poi, un comando alla volta, ciascuno prima in prova e poi con `--scrivi`:

```powershell
ssh aa-prod "rm -rf ~/firma && mkdir -p ~/firma && tar xf ~/firma.tar -C ~/firma && cd ~/firma/extra/aa-firma-autore && php installa.php /www"
ssh aa-prod "cd ~/firma/extra/aa-firma-autore && php installa.php /www --scrivi"
ssh aa-prod "cd ~/firma && php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-firma-autore.json"
ssh aa-prod "cd ~/firma && php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-firma-autore.json --scrivi"
ssh aa-prod "cd ~/firma/extra/aa-firma-autore && php firme.php /www"
ssh aa-prod "cd ~/firma/extra/aa-firma-autore && php firme.php /www --scrivi"
```

Sullo stage stessi comandi con `aa-stage`. Dopo un riallineamento dello stage dalla produzione
(prima del rilascio in produzione) si rilanciano tutti e tre.

Solo il primo passo, senza effetti visibili: `php installa.php /www --scrivi --non-pubblicare`
(pagina non pubblicata, nessun link dall'équipe; `aut.ref` si può già compilare). Poi
`php installa.php /www --pubblica` e `--pubblica --scrivi`.

Un articolo corretto che passa da «da correggere» a «firma»: si cambia la riga in
`decisioni.csv`, commit, poi `php firme.php /www --uri=<uri>` e con `--scrivi`.

### Ripristino

Ogni comando stampa alla fine il proprio comando di ripristino. Ordine inverso:

1. `php firme.php /www --ripristina ~/backup-kos-seo-geo/<data>-firme/ripristino.json --scrivi`
   (rimette i valori precedenti di `aut.ref`; c'è anche la tabella
   `modx_site_tmplvar_contentvalues_bak_AAAAMMGG`);
2. `php scripts/deploy/installa-elementi.php /www ~/backup-kos-seo-geo/<data>/ripristino.json --scrivi`
   (versioni precedenti di `aaSchema` e `interna-articolo`);
3. `php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-firma-autore/ripristino.json --scrivi`
   (contenuto dell'équipe, pagina e contenitore, `aa.breadcrumb_exclude`, TV, template, chunk, plugin,
   categoria, file).

Ripristino parziale: depubblicare la pagina autore riporta tutti gli articoli alla redazione
(firma, box e `Person` spariscono, `author` torna l'organizzazione).

### Collaudo sullo stage (3/11)

- `installa.php`: prova, scrittura, **ripristino completo** e reinstallazione (la prova dopo il
  ripristino ritrova tutto da creare); `firme.php`: 62/62 firmati, `editedon` invariato,
  ripristino provato.
- Pagina autore (via `index.php?id=596`, vedi regola web): 200, un solo `application/ld+json`
  (ProfilePage, Person, BreadcrumbList, WebSite, Organization; `hasPart` con i 62 articoli),
  canonical e `og:url` `/autori/antonella-schiavino`, nessun `noindex`, nessun tag MODX non
  elaborato, briciole visibili = `BreadcrumbList` (Anni Azzurri → Antonella Schiavino), elenco
  generato con 62 voci; nella sitemap (451 URL).
- Articolo firmato (`memoria-e-invecchiamento-quando-preoccuparsi`): un solo JSON-LD, `author` →
  `…/autori/antonella-schiavino#person` con il nodo Person per intero, firma e box presenti, nome
  identico in firma, box e markup; `dateModified` invariato (30/12/2025).
- Articolo della redazione (`piano-di-sostenibilita-impegno-di-anni-azzurri`): `author` →
  `#organization`, nessuna firma, nessun box, nessun foglio di stile.
- validator.schema.org (sull'HTML servito): 0 errori, 0 avvisi su tutte e tre.
- Rich Results Test (scheda «Codice», lo stage è protetto): tutti validi (Articoli, Breadcrumb,
  Pagina del profilo, Società). Avvisi facoltativi rimasti: `image` mancante sull'Article
  (preesistente: `aaSchema` legge `art.immagine`, che non esiste; le news hanno `newsImage`) e
  data senza ora sullo ScholarlyArticle (la data reale è solo il giorno).
- Svuotamento mirato, salvataggio dal processor del manager **senza** «Svuota cache»: pagina
  autore salvata → svuotati i due articoli firmati in prova e la pagina autore, l'articolo della
  redazione resta in cache; articolo firmato salvato → svuotata la pagina autore, la redazione resta.
- A occhio (390 e 1366 px, `scripts/collaudo/collaudo-visivo.mjs --solo-stage`): nessuna immagine
  rotta o senza alt, nessuno scorrimento orizzontale, un H1; foto tonda nel box, firma accanto
  alla data che va a capo bene sul telefono.

Da segnalare al progetto (non bloccanti): l'avviso PHP `modElement.php : 745 Undefined array key 1`
sugli articoli c'era già dal 19/10; l'`image` mancante sugli Article (sopra).

### Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 3/11/2026 | stage (c0025) | installato e collaudato; pagina 596, contenitore 595, template 69; 62 firme; regola web da aggiornare | `php firme.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261103-083708-firme/ripristino.json --scrivi`; `php scripts/deploy/installa-elementi.php /www /paas/c0025/backup-kos-seo-geo/20261103-083415/ripristino.json --scrivi` (versioni originali; quello del 084323 riporta alla prima versione dell'extra); `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261103-083708-firma-autore/ripristino.json --scrivi` |
| | produzione | lunedì 9 o martedì 10/11, dopo l'ok di Danilo e il controllo del collaudo dalla chat «Dato strutturato»; mai il 12/11 | |
