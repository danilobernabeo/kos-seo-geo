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
| Ritiro referti in sede | lunedì-venerdì 8:00-18:00 | lunedì-venerdì 8:30-19:30 (conferma di KOS del 27/11, come i pieghevoli v6; la news del 26/11 li aveva invertiti) |
| Accesso | libero, senza prenotazione; solo in regime privato (KOS, 27/11) | idem |
| Informazioni | Tel. 02 38306561 | Tel. 02 9737941 (il CUP non esiste più: niente «CUP» nella sezione, KOS 27/11) |
| Referti online | sul sito di MeticaLab, `https://www.meticalab.it/ritiro-referti` (servizio di MeticaLab, link chiesto da KOS il 27/11) | idem |
| Pieghevole | `/assets/doc/punto-prelievi-poliambulatorio-san-martino-bollate.pdf` | `/assets/doc/punto-prelievi-centro-medico-riabilitativo-cinisello.pdf` |

Pieghevoli: i due «6 ante» forniti da Danilo, versione v6 del 26/11 (`KOS PRELIEVI SMARTINO 6ino ESE v6.pdf`,
`KOS PRELIEVI CMR CINISELLO 6ino ESE v6.pdf`, cartella locale `20261126/`, non nel repository), con testo selezionabile (non sono solo immagine). Metadati (titolo, oggetto,
autore, lingua it-IT) aggiunti con `scripts/pdf/pdf-metadati.php` e `pdf-metadati.json` (aggiornamento incrementale:
i byte originali restano un prefisso del file). Copertine: pannello destro della prima pagina, 600 × 857, JPG 82, circa
77 KB, con il riquadro di indirizzo e orari coperto di bianco (gli orari sono nel testo della sezione; decisione del 27/11),
generate con `sh prepara-copertine.sh` (da rilanciare a ogni nuova versione dei PDF) (`files/assets/img/pieghevole-punto-prelievi-*.jpg`). Le locandine A3 dell'Open Day non sono usate.

## Cosa tocca

| Elemento | Cosa |
|---|---|
| contenuto delle due risorse (o la TV che contiene il blocco degli orari della struttura) | sezione tra `<!-- punto-prelievi:inizio -->` e `<!-- punto-prelievi:fine -->`, inserita prima del blocco `#box-dettagli` con `#orari-struttura` |
| `assets/doc/` | i due pieghevoli |
| `assets/img/` | le due copertine (varianti WebP 480 e 600 in `assets/cache-img/img/` con `ottimizza-immagini.php`) |

Non tocca template, chunk, snippet, plugin, CSS o JS del sito, head, libreria del dato strutturato, né le TV `str.*` (gli orari dei
prelievi e degli sportelli referti non sono quelli della struttura). Lo stile del box pieghevole non è nel CSS del sito:
sta in un `<style>` nel contenuto della pagina HomeCare Academy; le stesse regole sono copiate nella sezione
(`elementi/stile.html`), limitate a `#punto-prelievi`. Da spostare nel CSS del sito quando il box si usa altrove.

## Slide-in «Nuovo punto prelievi» (27/11)

Richiesta di KOS: un pop-up all'apertura delle schede. Al suo posto (meno invasivo, nessun interstitial da telefono) uno
slide-in non modale, sul modello di Newsletter27 di 27factory.ch, approvato dalla chat principale (seconda eccezione,
`docs/registro-decisioni.md`, voce del 27/11). Codice: `elementi/slidein.html` (`<style>` e `<script>`), inserito nella
sezione dentro i segni, quindi nel contenuto delle due risorse; `data-sede` sul contenitore `#punto-prelievi`.

- **Contenuto**: `<aside aria-label="Nuovo punto prelievi">` con miniatura (`/assets/img/punto-prelievi-miniatura.jpg`,
  144 × 144, 72 × 72 a schermo, assente da telefono), «Nuovo punto prelievi», «Dal lunedì al venerdì, 7:00-9:30, ad accesso
  libero», link «Scopri di più» e X (anche Esc). Non sposta il focus all'apertura; al clic il focus va all'H2 della sezione.
- **Link**: `location.pathname + '#punto-prelievi'` con `scrollIntoView` e `preventDefault`. Le pagine hanno
  `<base href="https://kosgroup.com/">`: un `href="#punto-prelievi"` creato via JS porterebbe alla home (il plugin
  LinkFrammenti corregge solo l'HTML servito). `scroll-margin-top: 120px` sulla sezione.
- **Quando**: dopo 2 s con la scheda visibile, se la sezione non è nello schermo e il banner Cookiebot non è aperto
  (8 s di pausa dopo la scelta); sparisce quando la sezione entra nello schermo (`IntersectionObserver`) e **ricompare
  quando ne esce**, sopra o sotto, finché l'utente non clicca o non lo chiude. Evento `impression` una volta per pagina.
- **Quante volte**: una pagina per sessione (nella pagina può ricomparire); chiuso con X o Esc: 30 giorni; cliccato: mai più.
  **Scade il 26/01/2027** (data nel codice).
- **Posizione**: in basso a destra (da telefono a tutta larghezza), sopra gli elementi fissi in basso che occuperebbero lo
  stesso spazio, compresi quelli negli shadow DOM: pulsante di accessiBe (acsbapp.com, in basso a sinistra), widget
  Cookiebot, «torna su», barra da telefono. Verificato il 27/11 sulla pagina di produzione con lo script iniettato:
  da computer nessuna sovrapposizione (accessiBe e Cookiebot a sinistra), link corretto con `<base href>`, sezione a
  120 px dal bordo, focus sull'H2.
- **Misure**: `dataLayer` con `punto_prelievi_impression`, `punto_prelievi_click`, `punto_prelievi_dismiss`
  (`punto_prelievi_sede`: bollate o cinisello). I tag vanno nel contenitore GTM di **kosgroup.com** (gtm.kosgroup.com),
  non in GTM-THMLNFX, che è di anniazzurri.it; senza tag gli eventi non producono nulla.
- **Cookiebot**: alla prossima scansione dichiarare `sessionStorage` `kosPuntoPrelieviVisto` (sessione) e `localStorage`
  `kosPuntoPrelieviStato` (`chiuso:<ms>` o `cliccato:<ms>`, funzionale, nessun dato personale).
- **Promemoria**: dopo il 26/01/2027 togliere il codice (`elementi/slidein.html` e `{{SLIDEIN}}` in
  `elementi/sezione.html`, poi rilancio dello script con `--scrivi`); fino ad allora non fa nulla dopo la scadenza.
- **Editor**: il contenuto delle due schede ora contiene `<script>`. Se qualcuno apre e salva la risorsa con TinyMCE, il
  codice può essere tolto o alterato: dopo modifiche manuali alle schede, rilanciare lo script (ripristina la sezione).

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
| 27/11/2026 | produzione KOS (c0095) | sezione aggiornata (niente CUP, accesso libero, regime privato, referti online MeticaLab, referti in sede Bollate 8:00-18:00 e Cinisello 8:30-19:30, minuscole), pieghevoli v6, copertine senza orari, slide-in; collaudo 0 KO (JSON-LD con lo stesso contenuto, solo l'ordine delle Offer diverso), varianti WebP 200, miniatura 200; clic su «Scopri di più» provato con Chrome su entrambe le schede: URL `…#punto-prelievi`, sezione a 120 px, slide-in chiuso; da computer nessuna sovrapposizione con accessiBe e Cookiebot | `php extra/punti-prelievo/installa.php /www --ripristina /paas/c0095/backup-kos-seo-geo/20261127-032343-punti-prelievo/ripristino.json --scrivi` |

## Ripristino

Attenzione: il ripristino rimette il **contenuto intero** delle schede com'era prima di quel `--scrivi`; se nel frattempo
qualcuno ha modificato altre parti delle schede (es. «Aree di intervento», 26/11), per togliere solo la sezione meglio
rilanciare lo script senza i frammenti, oppure togliere a mano il blocco tra i segni.

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
- **News «Nuovi punti prelievi KOS a Cinisello Balsamo e Bollate»**: ha gli orari del ritiro referti invertiti
  (KOS, 27/11: Bollate 8:00-18:00, Cinisello 8:30-19:30, come i pieghevoli v6); va corretta prima della pubblicazione.
- **Pop-up «Nuovo punto prelievi»** all'apertura della pagina: richiesto da KOS il 27/11, sospeso (da valutare dopo).
