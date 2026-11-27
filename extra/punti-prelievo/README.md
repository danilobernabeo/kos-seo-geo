# Punto Prelievi nelle schede di Bollate e Cinisello (fuori dal progetto SEO/GEO)

Lavoro extra su richiesta di KOS: sezione «Punto Prelievi» nelle schede
`/it/centri/poliambulatorio-san-martino` (Bollate) e `/it/centri/centro-medico-riabilitativo` (Cinisello Balsamo), con
orari dei prelievi e del ritiro referti e il box del pieghevole (modello: box «Scarica il pieghevole» di
`/it/servizi-e-cure/homecare-academy`; orari con il markup di `/it/centri/villa-dei-pini-civitanova`). Non è una voce
d'audit e non compare nella documentazione del progetto. Pubblicazione in produzione il 26/11, eccezione al calendario
registrata in `docs/registro-decisioni.md` (voce del 26/11).

## Dati (richiesta di KOS del 26/11)

| Dato | Bollate (San Martino) | Cinisello (Centro Medico Riabilitativo) |
|---|---|---|
| Prelievi | lunedì-venerdì 7:00-9:30 | lunedì-venerdì 7:00-9:30 |
| Esami | prelievi ed esami ematochimici, in collaborazione con MeticaLab; percorsi di prevenzione | idem |
| Ritiro referti in sede | lunedì-venerdì 8:30-19:30 | lunedì-venerdì 8:00-18:00 |
| Accesso | libero, senza prenotazione; solo in regime privato (KOS, 27/11) | idem |
| Informazioni | Tel. 02 38306561 | Tel. 02 9737941 (il CUP non esiste più: niente «CUP» nella sezione, KOS 27/11) |
| Referti online | sul sito di MeticaLab, `https://www.meticalab.it/ritiro-referti` (servizio di MeticaLab, link chiesto da KOS il 27/11) | idem |
| Pieghevole | `/assets/doc/punto-prelievi-poliambulatorio-san-martino-bollate.pdf` | `/assets/doc/punto-prelievi-centro-medico-riabilitativo-cinisello.pdf` |

Pieghevoli: i due «6 ante» forniti da Danilo, versione v6 del 26/11 (`KOS PRELIEVI SMARTINO 6ino ESE v6.pdf`,
`KOS PRELIEVI CMR CINISELLO 6ino ESE v6.pdf`, cartella locale `20261126/`, non nel repository), con testo selezionabile (non sono solo immagine). Metadati (titolo, oggetto,
autore, lingua it-IT) aggiunti con `scripts/pdf/pdf-metadati.php` e `pdf-metadati.json` (aggiornamento incrementale:
i byte originali restano un prefisso del file). Copertine: pannello destro della prima pagina, 600 × 857, JPG 82, circa
78 KB (`files/assets/img/pieghevole-punto-prelievi-*.jpg`). Le locandine A3 dell'Open Day non sono usate.

## Cosa tocca

| Elemento | Cosa |
|---|---|
| contenuto delle due risorse (o la TV che contiene il blocco degli orari della struttura) | sezione tra `<!-- punto-prelievi:inizio -->` e `<!-- punto-prelievi:fine -->`, inserita prima del blocco `#box-dettagli` con `#orari-struttura` |
| `assets/doc/` | i due pieghevoli |
| `assets/img/` | le due copertine (varianti WebP 480 e 600 in `assets/cache-img/img/` con `ottimizza-immagini.php`) |

Non tocca template, chunk, snippet, plugin, CSS, head, libreria del dato strutturato, né le TV `str.*` (gli orari dei
prelievi e degli sportelli referti non sono quelli della struttura). Lo stile del box pieghevole non è nel CSS del sito:
sta in un `<style>` nel contenuto della pagina HomeCare Academy; le stesse regole sono copiate nella sezione
(`elementi/stile.html`), limitate a `#punto-prelievi`. Da spostare nel CSS del sito quando il box si usa altrove.

## Contenuto

| Percorso | Cosa |
|---|---|
| `config.json` | sedi (URI, testi variabili, file, parole dell'altra sede che non devono comparire), punto di inserimento |
| `elementi/sezione.html`, `elementi/stile.html` | markup della sezione e stile del box |
| `files/` | file da copiare nel webroot, stesso percorso |
| `pdf-metadati.json` | piano dei metadati dei due PDF (già applicato ai file di `files/`) |
| `installa.php` | prova, `--scrivi` con backup, `--ripristina`; `--test <san-martino.html> <cmr.html>` in locale sull'HTML pubblico |
| `collaudo.mjs` | collaudo sul sito servito; `--salva` prima dell'installazione salva il JSON-LD in `collaudo/` |
| `collaudo/` | JSON-LD delle schede prima dell'installazione (produzione salvata il 26/11 mattina) |

## Rilascio

Prima lo stage, poi la produzione; sempre prima la prova e poi `--scrivi`. Comandi pronti nella chat. Ordine:

1. `node extra/punti-prelievo/collaudo.mjs --base=<sito> --salva` (JSON-LD di prima);
2. `php extra/punti-prelievo/installa.php /www` (prova; scrive il risultato in `~/punto-prelievi-<scheda>.html`), poi `--scrivi`;
3. varianti WebP delle copertine:
   `php scripts/immagini/ottimizza-immagini.php /www extra/punti-prelievo/copertine.txt --varianti --solo-varianti --solo-webp` (poi `--scrivi`);
4. `node extra/punti-prelievo/collaudo.mjs --base=<sito>`.

`dateModified` del JSON-LD cambia (la risorsa viene salvata): il collaudo lo segnala come AVV e confronta il resto.

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 26/11/2026 | stage KOS (c0044) | sezione nelle due schede, pieghevoli, copertine e varianti WebP; collaudo 49 OK. JSON-LD: stesso contenuto di prima, `dateModified` invariato; cambia solo l'ordine delle Offer del catalogo TuoTempo (rigenerazione della cache, non dipende dalla sezione: da segnalare alla chat principale) | `~/backup-kos-seo-geo/<data>-punti-prelievo/ripristino.json` sullo stage |

## Ripristino

    php extra/punti-prelievo/installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-punti-prelievo/ripristino.json --scrivi

Rimette contenuto (o TV) delle due risorse com'erano prima di quel `--scrivi` e toglie i file creati (rimette quelli
sostituiti). Le varianti WebP si ripristinano con il backup stampato da `ottimizza-immagini.php`.

## Richieste al progetto (chat principale)

- **Dato strutturato del servizio prelievi, dopo l'11/12**: nodo `Service` (o `MedicalTest`/servizio diagnostico, da
  decidere nella libreria comune) per ciascun punto prelievi, con `provider` verso il `#localbusiness` della scheda e
  `hoursAvailable` lunedì-venerdì 7:00-9:30; nessun JSON-LD scritto a mano in questa sezione.
- **Elenco `/it/strutture/punti-prelievo`**: inserire le due strutture.
- **Profili Google (Yext/RetailTune)**: orari dei prelievi come orari speciali o servizio.
- **Link ai referti online**: quando KOS lo fornisce, si aggiunge alla riga «online» (rilancio dello script).
- **Ordine delle Offer nel catalogo TuoTempo del JSON-LD**: cambia tra una generazione della cache e l'altra (stage
  26/11, stesso contenuto): un ordinamento stabile nella libreria rende confrontabili le versioni.
- **Orari «Ritiro referti» delle schede**: il blocco «Orari Poliambulatorio» delle due schede indica
  «Diagnostica - Poliambulatorio - Ritiro referti 8:00-20:00»; KOS (26/11) conferma come corretti quelli della news
  approvata: 8:30-19:30 Bollate, 8:00-18:00 Cinisello. Quel blocco non si tocca qui (orari della struttura, TV `str.*`
  e dato strutturato): da correggere separatamente.
- **Pieghevoli v6, ritiro referti**: indicano Bollate 8:00-18:00 e Cinisello 8:30-19:30, cioè gli orari scambiati
  rispetto alla news approvata e alla sezione (Bollate 8:30-19:30, Cinisello 8:00-18:00): da chiarire con KOS (27/11).
- **Pop-up «Nuovo punto prelievi»** all'apertura della pagina: richiesto da KOS il 27/11, sospeso (da valutare dopo).
