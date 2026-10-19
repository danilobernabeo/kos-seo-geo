# kos-seo-geo

Codice e documentazione del progetto di ottimizzazione SEO/GEO di **kosgroup.com** e **anniazzurri.it** e della migrazione da Yext a RetailTune (Gruppo KOS, ottobre-dicembre 2026).

Entrambi i siti girano su MODX 3.2.4, MariaDB 10.2, hosting MODX Cloud.

## Struttura

```
shared/          codice comune ai due siti (dato strutturato, briciole, utilità)
kosgroup/
  elements/      template, chunk, snippet, plugin del sito come file statici
  files/         robots.txt, llms.txt, regole web di MODX Cloud
  baseline/      istantanea degli elementi prima del progetto (sola consultazione)
anniazzurri/     stessa struttura
scripts/         export degli elementi, query SQL, analisi dei log, verifiche di collaudo
docs/            convenzioni, piano degli sprint, note di consegna
```

## Flusso di lavoro

1. **Sviluppo** sul PC, in questo repository, su un ramo per attività (`sprint1/robots-llms`, `sprint1/aa-breadcrumb`...).
2. **Collaudo** sull'ambiente di prova (`stage.kosgroup.com`, `stage.anniazzurri.it`): sul cloud di sviluppo `git pull` del ramo, poi verifiche con gli script in `scripts/checks/`.
3. **Rilascio**: merge su `main`, backup del database, `git pull` sul cloud di produzione, svuotamento della cache MODX. Fuori dagli orari di maggior traffico.
4. **Ritorno indietro**: `git checkout` del commit precedente sul cloud e svuotamento cache.

## Elementi statici

Gli elementi che il progetto crea o modifica diventano **elementi statici** di MODX: il codice sta nei file di questo repository, letti da MODX tramite una media source dedicata che punta alla copia del repository sul cloud. Gli elementi non toccati restano nel database come sono.

- Una media source "Repository" (Filesystem) con `basePath` = percorso assoluto del clone sul cloud, `basePathRelative` = no.
- Ogni elemento statico: *File statico* = percorso relativo, es. `shared/snippets/schemaGraph.php` o `anniazzurri/elements/chunks/breadcrumb.html`.
- Il codice condiviso sta in `shared/` e viene usato da entrambi i siti con lo stesso file.

## Istantanea iniziale (`baseline/`)

Prima di qualsiasi modifica, per ciascun sito:

```bash
php ~/kos-seo-geo/scripts/export-elements.php /www /tmp/export-elements
```

Il contenuto di `/tmp/export-elements` va in `<sito>/baseline/` con un commit dedicato. Serve come riferimento per i confronti; non si modifica.

## Regole

- **Mai credenziali nel repository**: password, chiavi API, `config.inc.php`, export del database.
- Gli export CSV dal database si salvano direttamente da HeidiSQL in UTF-8, senza aprirli e risalvarli in Excel.
- Nessuna modifica diretta in produzione che non passi dallo stage.
- Documentazione e messaggi di commit in italiano.
