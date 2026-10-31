# Elementi sotto il progetto

Elementi che il progetto modifica: non vanno cambiati dal manager di produzione, ma dal repository.
Aggiornare l'elenco a ogni intervento.

## anniazzurri.it

| Elemento | Tipo | Voce | Stato |
|---|---|---|---|
| `aaSchema` | snippet (nuovo) | AA-8 | in produzione (20/10) |
| `footer-script`, `footer-script-regione`, `footer-script-provincia`, `footer-script-form-registrazione` | chunk | AA-8 | in produzione (20/10) |
| `breadcrumb-stripe` | chunk | AA-8 | in produzione (20/10) |
| `schema-525`, `schema-lrsa-volpiano`, `schema-assunzione-assistenti-infermieristici` | chunk | AA-8 | in produzione (20/10) |
| `head-sportello-sociale` | chunk | AA-8 (collegamento `schema-525`) | in produzione (20/10) |
| `PaginationCanonical` | plugin | AA-7 / paginazione news | aggiornato, disattivato |
| `heading-home2` | chunk | AA-18 (H1) | in produzione (20/10) |
| `AssetVersion` (shared) | plugin (nuovo) | AA-14 (`?v=` automatico su CSS/JS) | in produzione (20/10) |
| `convegnoAA` | template | AA-18 (H1 galleria), canonical, `[[aaSchema]]` | **produzione (26/10)** con il pacchetto `aa-og-image` |
| Web Rules | configurazione | AA-14 e redirect | `anniazzurri/files/web-rules/web-rules.conf` |
| `immagine` (shared) | snippet (nuovo) | AA-1, AA-17 | **produzione (26/10)**, pacchetto `aa-1-17-libreria` |
| `ImmaginiCaricamento` (shared) | plugin (nuovo, OnFileManagerUpload) | AA-17 | **produzione (26/10)**, pacchetto `aa-1-17-libreria` |
| `core/components/kosseogeo/Immagini.php`, `/dist/css/custom.css`, `/dist/css/kos-seo-geo.css` | file | AA-1, AA-6, AA-17 | **produzione (26/10)**, pacchetto `aa-1-17-libreria` |
| chunk e template di sfondi, titoli, gallerie (`gal-*`), `servizi-*`, news, head delle landing, `photoGalleryAA2025` | 235 elementi | AA-1, AA-6, Alt Text | **produzione (27/10)**, pacchetto `aa-1-6-markup` |
| contenuti di residenze, centri diurni, regioni, province, landing, home (152 risorse) e 7 news | campo content | AA-1, AA-6, Alt Text | **produzione (27/10)**, `anniazzurri/dati/aa-6-contenuti`, `alt-news-contenuti` |
| `aaSchema`, `head-sportello-sociale`, template `interna-cure-e-assistenza-domiciliari-new`, `albero-dei-ricordi-speciale`, `convegnoAA` | snippet, chunk, template | AA-8 (correzioni dalla verifica del 29/10: `/news/`, nomi, Sportello nel grafo, briciole visibili) | **produzione (29/10)**, pacchetto `aa-8-correzioni` |
| `ogImage` (shared) e 26 head `head*`, template `convegnoAA` | snippet (nuovo), chunk, template | og:image (fuori perimetro) | **produzione (26/10)**, pacchetto `aa-og-image` |
| `titoloPagina` (shared) e gli stessi 26 head `head*` + template `convegnoAA`, impostazione `seo.titolo_marchio` | snippet (nuovo), chunk, template | AA-20 (`<title>`: longtitle, marchio una volta sola; `:esc` su description e og:title) | **produzione (27/10, `:esc` 28/10)**, pacchetto `aa-20-title` |
| template `interna-menu-form-registrazione` | template | AA-18 (pulizia: H1 e introtext commentati tolti dalla testata degli eventi) | **produzione (30/10)**, pacchetto `aa-18-commenti`, ripristino `php scripts/deploy/installa-elementi.php /www /paas/c0072/backup-kos-seo-geo/20261030-052126/ripristino.json --scrivi` |
| template `news`, chunk `news-list` | template, chunk | KOS-2/AA prestazioni (`/news/` in cache, dalla chat sulle prestazioni del 31/10) | sviluppo (31/10), pacchetto `aa-news` |

## kosgroup.com

| Elemento | Tipo | Voce | Stato |
|---|---|---|---|
| `AssetVersion` (shared) | plugin (nuovo) | KOS-3 (`?v=` automatico su CSS/JS) | in produzione (20/10) |
| Web Rules | configurazione | KOS-3 e redirect | `kosgroup/files/web-rules/web-rules.conf` |
| `kosSchema` (shared `seoSchema`) | snippet (nuovo) | KOS-9, KOS-12, KOS-13 | in produzione (22/10) |
| `myBreadcrumb` | snippet | KOS-12, KOS-13 (delega a `kosSchema`) | in produzione (22/10) |
| `breadcrumb` | chunk | KOS-12, KOS-13 | in produzione (22/10) |
| `scripts`, `scripts-formazione` | chunk | KOS-9, KOS-13 (`[[kosSchema]]`) | in produzione (22/10) |
| `strutture`, `strutture-VDP`, `specializzazioni`, `cure` | template | KOS-12 (briciole visibili) | in produzione (22/10) |
| impostazioni `kos.*` | impostazioni di sistema | KOS-9, KOS-12, KOS-13 | in produzione (22/10) |
| `documenti`, `documenti-vm-ria`, `documenti-vm-psi`, `documenti-vdp-medica`, `documenti-vdp-chirurgica`, `documenti-vdp-diagnostica` | chunk | KOS-2 (cache di `docList`) | **produzione (27/10)**, pacchetto `kos-2-8-documenti` |
| `listLda` | snippet | KOS-8 (link ai PDF) | **produzione (27/10)**, pacchetto `kos-2-8-documenti` |
| `head` (unico, con parametri) e i richiami `head-informativo`, `head-medici`, `head-specializzazioni`, `head-video`, `head-strutture`, `head-landing`, `head-paginazione`, `head-en` | chunk | KOS-2, voce 24 (consolidamento head) | **produzione (26/10, controllo 0 errori su 65 pagine)**, pacchetto `kos-head` |
| `head-ricerca-yext`, `head-extra-strutture`, `head-extra-landing` | chunk (nuovi) | KOS-2, voce 24 | **produzione (26/10, controllo 0 errori su 65 pagine)**, pacchetto `kos-head` |
| `kosHreflang` | snippet (nuovo) | KOS-2 (hreflang corretti) | **produzione (26/10, controllo 0 errori su 65 pagine)**, pacchetto `kos-head` |
| `ogImage` (shared, sostituisce quello KOS) | snippet | og:image con dimensioni | **produzione (26/10, controllo 0 errori su 65 pagine)**, pacchetto `kos-head` |
| `kosSchema` (shared `seoSchema`): nodo struttura `#localbusiness`, landing | snippet | voce 24 | **produzione (28/10), spento** (`kos.struttura_attiva` assente), pacchetto `kos-24` |
| TV `str.*` (18), `lnd.*` (2) e `srv.schema`, categoria «Scheda struttura (dato strutturato)» | TV (nuove) | voce 24 | **produzione (28/10)**, vuote fino all'import, pacchetto `kos-24` |
| `head-extra-strutture`, `head-extra-landing` (senza Yext e JSON-LD scritti a mano) | chunk | voce 24 | sviluppo (25/10), pacchetto `kos-24-attivazione` |
| `grafico-dipendenti`, `employee-chart` | chunk | lavoro extra Sostenibilità | in produzione (20/10), vedi `extra/kos-sostenibilita` |
| template `informativo` (senza chiamate non in cache, senza `[[*schemaJsonLd]]`), chunk `listingNewsLast3`, `kosplayer-banner`, `listingComunicati`, `documenti-ambulatorio-cardio-metabolico` | template, chunk | KOS-2 (tempo di risposta), link assoluti (dalla chat sulle prestazioni del 31/10) | sviluppo (31/10), pacchetto `kos-ttfb-31-10` |
