# Calendario dei convegni su /convegni-e-webinar di anniazzurri.it (fuori dal progetto SEO/GEO)

Lavoro extra: sotto «Calendario esperienze formative» il blocco scritto a mano (oggi solo il Mantello del custode)
diventa un elenco automatico dei convegni in programma. Ogni convegno ha una card orizzontale: immagine a sinistra,
data e luogo, titolo, sottotitolo e pulsante «Per programma e iscrizioni». Su smartphone le card si impilano, con
l'immagine sopra; niente carosello, perché i convegni in contemporanea sono due o tre al massimo. L'ordine è per
data, e dal giorno dopo il convegno la card sparisce da sola. Non è una voce d'audit e non compare nella
documentazione del progetto.

## Come si aggiunge un convegno

Sulla pagina dell'evento (template `interna-menu-form-registrazione`), scheda delle TV:

| TV | Cosa mettere |
|---|---|
| `evento_data` | giorno del convegno (vuota = non compare nel calendario) |
| `evento_luogo` | es. «Sala Pertini, Cinisello Balsamo (MI)» |
| `evento_immagine` | immagine 16:9, 980×551, con il titolo leggibile anche piccola (come `il-mantello-del-custode-header-top.jpg`) |
| Riassunto (introtext) | sottotitolo della card |

La pagina deve essere pubblicata. Non serve toccare `/convegni-e-webinar`.

## Contenuto

| Percorso | Cosa |
|---|---|
| `elementi/snippets/aaCalendarioConvegni.php` | snippet dell'elenco, richiamato non in cache (`[[!aaCalendarioConvegni]]`) perché la data va confrontata a ogni visita |
| `elementi/chunks/aaCalendarioConvegniCard.html` | card di un convegno (classi Bootstrap del sito, stile delle card delle news) |
| `eventi.json` | valori iniziali delle TV per Mantello e Parkinson |
| `files/assets/img/convegno-parkinson-realta-prospettive-card.jpg` | card del Parkinson, generata da `../aa-convegno-parkinson/genera-immagini.py` |
| `baseline/convegni-e-webinar.html` | contenuto della pagina **ricostruito dall'HTML pubblicato** il 26/10: serve solo a `--test` |
| `installa.php` | installazione con prova, backup e ripristino |

Le card usano lo snippet `immagine` (`<picture>` con varianti) se è installato; altrimenti un `<img>` semplice.
Così funzionano anche in produzione prima del rilascio della libreria immagini del progetto.
I chunk delle news (`news-home-*`, elementi del progetto) non si toccano.

## Cosa fa `installa.php`

1. Copia la card del Parkinson in `/www/assets/img/`.
2. Crea lo snippet e il chunk (categoria della TV `evento_titolo`).
3. Crea le TV `evento_data`, `evento_luogo` ed `evento_immagine` sul template delle pagine evento. La TV immagine
   prende la stessa media source di `imgHeader`. Le TV si compilano per gli eventi di `eventi.json`.
4. Scrive il riassunto degli eventi solo dove è vuoto (oggi il Mantello).
5. Nella pagina `/convegni-e-webinar` sostituisce il solo `<div class="row">` che segue il titolo «Calendario
   esperienze formative» con `[[!aaCalendarioConvegni]]`. Il resto del contenuto e il modulo newsletter restano
   com'erano.

La prova mostra il testo del blocco che verrebbe tolto. Si ferma se il titolo non c'è, se c'è più di una volta o se
non è seguito da un `<div class="row">`, se una TV, lo snippet o il chunk esistono già, se una pagina evento non
c'è o ha un template diverso.

## Rilascio

Richiede le pagine del convegno Parkinson già installate (`extra/aa-convegno-parkinson`). Dalla radice del
repository, in PowerShell:

```powershell
git add extra/aa-calendario-convegni extra/aa-convegno-parkinson/genera-immagini.py
git commit -m "Extra aa-calendario-convegni: calendario automatico dei convegni"
git archive -o $env:TEMP\calendario.tar HEAD extra/aa-calendario-convegni
scp $env:TEMP\calendario.tar aa-stage:
Start-Sleep 20
ssh aa-stage "rm -rf ~/calendario && mkdir ~/calendario && tar xf ~/calendario.tar -C ~/calendario && cd ~/calendario/extra/aa-calendario-convegni && php installa.php /www"
Start-Sleep 20
ssh aa-stage "cd ~/calendario/extra/aa-calendario-convegni && php installa.php /www --scrivi"
```

In produzione: stessi comandi sul cloud di produzione, dopo le pagine del Parkinson. Niente rilasci giovedì 29/10.

Dopo il rilascio, su `/convegni-e-webinar`:

- due card, Mantello e poi Parkinson; da desktop affiancate a immagine e testo, da smartphone impilate;
- pulsanti e immagini portano alle pagine dei convegni;
- il modulo newsletter sotto il calendario è invariato.

Prova della scadenza: sullo stage si mette nel Mantello una `evento_data` di ieri. La sua card sparisce, e se non
resta nessun convegno compare il testo «Al momento non ci sono convegni in programma…».

Immagini o documenti di `files/` cambiati dopo l'installazione: `php installa.php /www --aggiorna-file`, poi con
`--scrivi`. Sostituisce solo i file diversi, salva i precedenti nel backup, svuota la cache (le varianti
dello snippet `immagine` si rigenerano da sole) e stampa il comando di ripristino. Pagine, TV e chunk non cambiano.

Ripristino: il comando esatto è stampato alla fine dell'installazione
(`php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-calendario-convegni/ripristino.json --scrivi`):
rimette il contenuto originale della pagina e il riassunto del Mantello, poi toglie TV (con i valori), snippet,
chunk e immagine.

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 28/10/2026 | produzione (c0072) | installato: blocco del Mantello sostituito da `[[!aaCalendarioConvegni]]` nella risorsa 116, TV `evento_data`/`evento_luogo`/`evento_immagine` sul template 30, Mantello (560) e Parkinson (587) compilati; snippet `immagine` presente, card con varianti | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261028-102919-calendario-convegni/ripristino.json --scrivi` |
