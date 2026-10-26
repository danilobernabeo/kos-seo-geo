# Convegno «La malattia di Parkinson - Realtà e prospettive» su anniazzurri.it (fuori dal progetto SEO/GEO)

Lavoro extra: pagina del convegno del 12 dicembre 2026 (Sala Pertini, Cinisello Balsamo) e pagina di iscrizione,
identiche a quelle del Mantello del custode (risorsa 560 `/il-mantello-del-custode` e
`/iscrizione-convegno-il-mantello-del-custode`). Per circa due settimane i due convegni convivono: pagine,
moduli e destinatari delle iscrizioni sono separati. Non è una voce d'audit e non compare nella documentazione
del progetto.

| | Mantello del custode | Parkinson |
|---|---|---|
| Pagina evento | `/il-mantello-del-custode` (560, template `interna-menu-form-registrazione`) | `/convegno-parkinson-realta-prospettive` (stesso template, genitore e impostazioni) |
| Iscrizione | `/iscrizione-convegno-il-mantello-del-custode` (template `formIscrizione`) | `/iscrizione-convegno-parkinson-realta-prospettive` |
| Programma | chunk `programma-560` | chunk `programma-convegno-parkinson-realta-prospettive` (stesso schema e CSS) |
| Iscrizioni a | segreteria dell'Hospice | `manuela.devasini@anniazzurri.it` (TV `evento_notifiche`) |
| Iscrizioni salvate (FormIt) | modulo `iscrizione-convegno-il-mantello-del-custode` | modulo `iscrizione-convegno-parkinson-realta-prospettive` (nome = alias) |

## Contenuto

| Percorso | Cosa |
|---|---|
| `KOS_CONVEGNO PARKINSON_Anni Azzurri_locandinaA3_02.pdf`, `visual-convegno.jpg` | materiali ricevuti (fonte dei testi e delle immagini) |
| `genera-immagini.py` | rigenera le immagini di `files/` dai materiali (Pillow, numpy, pdftoppm) |
| `files/assets/` | testate desktop e mobile, immagine di condivisione, slide della home, locandina: da copiare in `/www/assets/` (tutti nuovi) |
| `elementi/contenuto-evento.html` | campo content della pagina evento |
| `elementi/segreteria-evento.html` | TV `segreteria` (colonna laterale: pulsante di iscrizione, segreteria, provider ECM, patrocinio) |
| `elementi/chunks/programma-convegno-parkinson-realta-prospettive.html` | scheda informativa, programma e faculty; toglie anche, solo su questa pagina, la velatura scura che il template mette sulla testata |
| `installa.php` | installazione con prova, pubblicazione separata e ripristino |
| `mantello-senza-velatura.php` | toglie la velatura scura anche dalla testata del Mantello (chunk `programma-560`) |
| `slide-home.html` | slide del carosello della home (da inserire a mano nel chunk del progetto, vedi sotto) |

Le immagini seguono la locandina: foto specchiata (testa a sinistra) e filo virato dal rosa della foto fornita
al rosso della locandina; logo e titolo sono presi dalla locandina stessa (Helvetica Neue), non ricomposti.
Nella slide della home il testo resta HTML bianco: la foto ha una velatura blu a sinistra perché sia leggibile.

## Cosa fa `installa.php`

Copia le due pagine dai modelli (template, genitore, impostazioni, TV) e cambia solo testi, immagini, locandina,
segreteria e destinatario. Non copia le date di pubblicazione o ritiro programmate del modello. Prima di scrivere
mostra campo per campo e TV per TV il valore del modello e quello nuovo, e si ferma se:

- in un campo o in una TV delle pagine nuove resta un riferimento al Mantello (titolo, Hospice, data, indirizzi della sua segreteria);
- testate o locandina nelle TV non sono i file nuovi (il nome del file del modello non contiene il suo alias);
- il contenuto della pagina di iscrizione del modello passa proprietà allo snippet (`emailTo`, `&evento`, indirizzi): scavalcherebbero il destinatario;
- c'è nel cestino una risorsa con uno dei due alias.
- la pagina di ringraziamento (`evento_redirect`, copiata dal modello) nomina il Mantello;
- un file da copiare esiste già con un contenuto diverso, o un'immagine delle TV non è tra i file.

## Rilascio

Prima sullo stage (`aa-stage`), con le pagine pubblicate per l'approvazione:

```powershell
git pull
git archive -o $env:TEMP\parkinson.tar HEAD extra/aa-convegno-parkinson
scp $env:TEMP\parkinson.tar aa-stage:
Start-Sleep 20
ssh aa-stage "rm -rf ~/parkinson && mkdir ~/parkinson && tar xf ~/parkinson.tar -C ~/parkinson && cd ~/parkinson/extra/aa-convegno-parkinson && php installa.php /www"
Start-Sleep 20
ssh aa-stage "cd ~/parkinson/extra/aa-convegno-parkinson && php installa.php /www --scrivi --pubblica"
```

Se lo stage non ha le pagine del Mantello (riallineamento precedente alla loro creazione), la prova lo dice: modello non trovato.

In produzione, ad approvazione: stessi comandi sul cloud di produzione, con `--scrivi --pubblica`; oppure prima
`--scrivi` (pagine non pubblicate, anteprima dal manager) e alla conferma `--pubblica --scrivi`.
Niente rilasci in produzione giovedì 29/10 (giorno prima della consegna dello sprint 1).

Dopo il rilascio:

- invio di prova del modulo: arriva a `manuela.devasini@anniazzurri.it`, conferma all'iscritto con oggetto
  «Richiesta di iscrizione - La malattia di Parkinson - …», segreteria del Polo Geriatrico in fondo alla mail;
- l'iscrizione al Mantello del custode continua ad arrivare ai suoi destinatari;
- pagina evento: testata desktop e mobile, «Scarica la locandina», pulsante «Iscrizioni online», `og:image`
  (`/assets/img/convegno-parkinson-realta-prospettive.jpg`) nel sorgente.

Immagini o documenti di `files/`, o il chunk del programma, cambiati dopo l'installazione: `php installa.php /www --aggiorna-file`,
poi con `--scrivi`. Sostituisce solo i file e il chunk diversi, salva i precedenti nel backup, svuota la cache (le varianti
dello snippet `immagine` si rigenerano da sole) e stampa il comando di ripristino. Pagine e TV non cambiano.

Ripristino: il comando esatto è stampato alla fine dell'installazione
(`php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-convegno-parkinson/ripristino.json --scrivi`):
rimuove le due pagine (con le TV), il chunk e i file copiati. Le iscrizioni già salvate da FormIt restano.

## Testata del Mantello senza velatura

Il template delle pagine evento mette sulla testata una velatura scura da sinistra. Il Parkinson la toglie con una
regola CSS nel suo chunk del programma; `mantello-senza-velatura.php` aggiunge la stessa regola al chunk
`programma-560`, che compare solo nella pagina del Mantello. Il template, elemento del progetto, non cambia.

```powershell
ssh aa-stage "cd ~/parkinson/extra/aa-convegno-parkinson && php mantello-senza-velatura.php /www"
ssh aa-stage "cd ~/parkinson/extra/aa-convegno-parkinson && php mantello-senza-velatura.php /www --scrivi"
```

Si ferma se il chunk non ha esattamente uno `</style>`. Se la regola c'è già non fa niente. Il ripristino dal
backup è stampato alla fine. Dopo il rilascio in produzione va aggiornata la copia
`anniazzurri/baseline/chunks/programma-560.html`, perché gli strumenti del progetto non lo segnalino come modificato a mano.

## Carosello della home

La slide sta nel chunk `heading-home2`, che è un elemento statico del progetto (AA-18) e fa parte del pacchetto
`aa-1-6-markup` dello sprint 2: non si modifica da `installa.php`. Quando la pagina è pubblicata si incolla la
slide di `slide-home.html` dopo quella del Mantello, sul ramo da cui si rilascia:

- variante A (snippet `immagine`) se `heading-home2` esce insieme ad `aa-1-6-markup` (finestra 26/10-6/11);
- variante B (sfondo CSS) se il convegno si pubblica prima di quel rilascio.

Mai durante la verifica dell'agenzia (9-14/11), che controlla anche `heading-home2`. Finito il Mantello (11/11)
la sua slide si toglie con lo stesso vincolo.

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| | | | |
