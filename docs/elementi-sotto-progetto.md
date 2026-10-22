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
| `convegnoAA` | template | AA-18 (H1 galleria), canonical, `[[aaSchema]]` | in collaudo |
| Web Rules | configurazione | AA-14 e redirect | `anniazzurri/files/web-rules/web-rules.conf` |

## kosgroup.com

| Elemento | Tipo | Voce | Stato |
|---|---|---|---|
| `AssetVersion` (shared) | plugin (nuovo) | KOS-3 (`?v=` automatico su CSS/JS) | in produzione (20/10) |
| Web Rules | configurazione | KOS-3 e redirect | `kosgroup/files/web-rules/web-rules.conf` |
| `kosSchema` (shared `seoSchema`) | snippet (nuovo) | KOS-9, KOS-12, KOS-13 | sviluppo (22/10) |
| `myBreadcrumb` | snippet | KOS-12, KOS-13 (delega a `kosSchema`) | sviluppo (22/10) |
| `breadcrumb` | chunk | KOS-12, KOS-13 | sviluppo (22/10) |
| `scripts`, `scripts-formazione` | chunk | KOS-9, KOS-13 (`[[kosSchema]]`) | sviluppo (22/10) |
| `strutture`, `strutture-VDP`, `specializzazioni`, `cure` | template | KOS-12 (briciole visibili) | sviluppo (22/10) |
| impostazioni `kos.*` | impostazioni di sistema | KOS-9, KOS-12, KOS-13 | sviluppo (22/10) |
| `grafico-dipendenti`, `employee-chart` | chunk | lavoro extra Sostenibilità | in produzione (20/10), vedi `extra/kos-sostenibilita` |
