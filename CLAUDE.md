# Istruzioni per Claude

Progetto: ottimizzazione SEO/GEO di kosgroup.com e anniazzurri.it e migrazione da Yext a RetailTune per Gruppo KOS. Referente tecnico e sviluppatore: Danilo Bernabeo. Lingua di lavoro: italiano (documenti, commenti, commit).

## Contesto tecnico

- MODX 3.2.4 (namespace `MODX\Revolution\...`), PHP 8.3, MariaDB 10.2.54 (CTE ricorsive sì, `JSON_ARRAYAGG` no), prefisso tabelle `modx_`.
- Hosting MODX Cloud, un cloud di produzione e uno di sviluppo per sito. Shell BusyBox: niente `gawk`, usare `awk` POSIX. Configurazione nginx solo tramite le regole web del pannello.
- Cloud: kosgroup.com = utente `c0095`; anniazzurri.it = utente `c0072`. Webroot `/www`, log in `/log` (access.log non attivo).
- Componenti installati (PHP 8.3.9 CLI, git, rsync, zip, tar disponibili su entrambi i cloud):
  - comuni: pdoTools, getResources, Wayfinder, BreadCrumb, FormIt, GoogleSitemap, Canonical, FirstChildRedirect, Orphans, QuickEmail, reCaptchaV2, TinyMCE RTE;
  - solo kosgroup.com: MIGX, Tagger, ImportX;
  - solo anniazzurri.it: CKEditor, EMO (offusca le email via JavaScript: gli indirizzi non sono leggibili dai crawler AI che non eseguono JS; da considerare per la scheda contatti lato server, voce 21 audit Anni Azzurri, e per il dato strutturato, dove l'email va scritta in chiaro).

## Convenzioni del dato strutturato (vincolanti)

- Forma canonica: `https://anniazzurri.it` (senza www, senza slash finale sulle pagine interne); `https://kosgroup.com/it/...` (senza www, prefisso lingua `/it`).
- `@id` = URL canonico della pagina + frammento fisso, generato da un'unica funzione:
  - ente: `https://anniazzurri.it/#organization` (KOS Care S.r.l.), `https://kosgroup.com/#organization` (KOS S.p.A., radice senza `/it`);
  - sito: `{base}/#website`; briciole: `{URL}#breadcrumb`; articolo `#article`; persona `#person`; medico `#medico`;
  - strutture: frammento in decisione tra `#localbusiness` (indicato dall'audit Anni Azzurri, slide 29-30) e `#struttura`: verificare `docs/convenzione-identificativi.md` prima di scrivere codice.
- Mai `sameAs` tra KOS Care e kosgroup.com: il legame è `parentOrganization`.
- Un solo `@graph` JSON-LD per pagina, emesso lato server. Niente dato strutturato scritto a mano nei chunk.

## Modo di lavorare

- Prima di intervenire su una voce d'audit, verificare lo stato attuale del sito: alcune voci possono essere già state risolte nei giorni precedenti. In quel caso raccogliere l'evidenza (comando e output) per la nota di consegna.
- Le voci d'audit sono il minimo, non il limite: ragionare secondo le buone pratiche attuali e proporre sempre eventuali migliorie, indicando se rientrano nel perimetro o vanno consuntivate a parte.

- Si sviluppa in questo repository e si collauda sullo stage prima della produzione.
- Gli elementi modificati diventano elementi statici (vedi README). Il codice comune ai due siti va in `shared/`.
- Prima di modificare un elemento esistente, partire dalla sua versione in `<sito>/baseline/`.
- Le briciole visibili (snippet `BreadCrumb`) e il `BreadcrumbList` JSON-LD devono avere sempre lo stesso percorso: stesse esclusioni (`aa.breadcrumb_exclude`), `pagetitle` per i livelli intermedi, `menutitle` o `pagetitle` per la pagina corrente.
- Ogni intervento si riferisce a una voce degli audit dell'agenzia (KosGroup settembre 2026, Anni Azzurri aprile 2026): citarla nel commit, es. `AA-8 BreadcrumbList completo`.
- Il piano degli sprint è in `docs/piano-sprint-1.md`; le scadenze sono vincolate da una penale: segnalare subito ciò che rischia di slittare.
