# News «Creiamo insieme le forme del nostro Natale: il progetto Anni Azzurri e MUBA» (fuori dal progetto SEO/GEO)

Lavoro extra su anniazzurri.it: news sul progetto con MUBA – Museo dei Bambini Milano e REMIDA Milano. L'impianto è
quello classico delle news (template `interna-articolo`), con la grafica del totem «Un Natale alla portata di tutti»:

- fondo azzurro `#99dde3` su tutta la pagina;
- cerchi bianchi semitrasparenti e, accanto al testo, le sagome vettoriali (albero, stella, raggi, onda), estratte
  dal PDF Illustrator del totem senza le foto delle decorazioni;
- galleria fotografica che si apre in una finestra sopra la pagina, come nell'albero dei ricordi;
- tre videotutorial con anteprima e pulsante play: il video si apre in una finestra sopra la pagina, uno alla volta;
  YouTube (`youtube-nocookie.com`) si carica solo al clic e si ferma alla chiusura della finestra.

Non è una voce d'audit e non compare nella documentazione del progetto.

## Indirizzo fisso

La news deve stare su `https://anniazzurri.it/il-natale-prende-nuova-forma-anni-azzurri-muba`: è la destinazione del
QR code stampato sul totem (`https://bit.ly/3TQ9ymn?r=qr`, che reindirizza qui con i parametri
`utm_source=totem&utm_medium=qr_code&utm_campaign=muba`). L'alias non va cambiato, e la pagina deve rispondere anche
con quei parametri. Lo script si ferma se l'alias è già usato da un'altra risorsa.

## Contenuto

| Percorso | Cosa |
|---|---|
| `elementi/templates/interna-articolo-muba.html` | copia di `interna-articolo` (versione del repository) con: classe `news-muba-page` sul body, cerchi decorativi, testo a tutta larghezza (le righe sono limitate dal CSS), finestra della galleria, testata con ripiego |
| `elementi/snippets/newsMubaRisorse.php` | registra CSS e JS solo su questa pagina (il chunk `head-news`, comune a tutte le news, non si tocca) |
| `elementi/snippets/newsMubaImmagine.php` | testata: usa lo snippet `immagine` del progetto se c'è, altrimenti un `<img>` semplice (produzione prima del rilascio della libreria immagini) |
| `elementi/contenuto.html` | contenuto della news, dal docx; link a MUBA e alle residenze di Milano (`/rsa-a-milano-e-provincia`) |
| `files/assets/news-muba/` | `news-muba.css`, `news-muba.js`, sagome SVG (`albero-stella`, `raggi`, `onda`), icone dei video (`albero`, `stella`, `sfera`), `logo-muba.svg`, testata e 9 foto **segnaposto** |
| `anteprima/costruisci.py` | anteprima locale senza MODX (Bootstrap 5.1.3 al posto del CSS del sito) |
| `installa.php` | installazione con prova, pubblicazione separata, aggiornamento e ripristino |

La news nasce con il campo «Editor visuale» spento (`richtext` = 0), così l'editor del manager non riscrive i pulsanti
della galleria e dei video.

## Da completare prima della pubblicazione

- **Testata** (TV `newsImage`): arriva dall'agenzia, **980 × 551 px** come le altre news (16:9, mostrata al massimo a
  980 px di larghezza). Oggi c'è `testata-segnaposto.jpg`, nello stesso formato. La testata è anche
  l'immagine dell'elenco delle news e della condivisione social.
- **Foto della galleria**: 24 ricevute, da selezionare (proposta 9 o 13: 1 grande + 8 o 12 in griglia). Vanno in
  `files/assets/news-muba/foto/`, con le righe corrispondenti in `contenuto.html` (`data-foto-jpg`, `data-foto-dida`,
  `src`, `alt`, `width`, `height`). Prima: **liberatorie** per ospiti e bambini riconoscibili.
- **REMIDA Milano**: senza link, finché non c'è una pagina ufficiale da collegare.

## Rilascio

Prima sullo stage, per la bozza. Dalla radice del repository, in PowerShell:

```powershell
git add extra/aa-news-muba
git commit -m "Extra aa-news-muba: news Anni Azzurri e MUBA con grafica del totem"
git archive -o $env:TEMP\muba.tar HEAD extra/aa-news-muba
scp $env:TEMP\muba.tar aa-stage:
Start-Sleep 20
ssh aa-stage "rm -rf ~/muba && mkdir ~/muba && tar xf ~/muba.tar -C ~/muba && cd ~/muba/extra/aa-news-muba && php installa.php /www"
Start-Sleep 20
ssh aa-stage "cd ~/muba/extra/aa-news-muba && php installa.php /www --scrivi --pubblica"
```

La prova mostra genitore, TV del template e, come confronto, le impostazioni e le TV della news più recente.

**Aggiornamenti** (foto vere, testata, correzioni di testo o grafica): si cambiano i file del pacchetto, si rifanno
commit e archivio e si lancia `php installa.php /www --aggiorna`, poi con `--scrivi`. Riallinea file, template,
snippet, contenuto e riassunto della news, con backup e comando di ripristino. Le modifiche fatte dal manager al
contenuto si perdono: restano nel backup.

**Produzione**: gli stessi comandi sul cloud di produzione, ad approvazione. In alternativa prima `--scrivi`, per
vedere la news non pubblicata in anteprima dal manager, poi `--pubblica --scrivi`.

Ripristino: il comando è stampato alla fine di ogni scrittura
(`php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-news-muba/ripristino.json --scrivi`).

## Verifiche dopo l'installazione

- Fondo azzurro su tutta la pagina, sagome a destra del primo blocco di testo e dei videotutorial (da 992 px in su),
  onde fra le sezioni; su smartphone restano cerchi e onde.
- Galleria: clic su una foto, frecce e tasti sinistra/destra, contatore, Esc; il focus torna sulla foto.
- Video: niente richieste a YouTube prima del clic (pannello Rete del browser); al clic si apre la finestra e parte
  il video; chiudendo (X, Esc, clic fuori) l'audio si ferma e il focus torna sul pulsante; aprendo un altro video
  parte solo quello.
- Elenco delle news e condivisione: la news compare con la testata.

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 27/10/2026 | stage (c0025) | installata **non pubblicata** (news 592, template 66 con le 11 TV di `interna-articolo`), foto e testata segnaposto | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261027-095552-news-muba/ripristino.json --scrivi` |

Aperto dopo il primo rilascio: TV `newsArticle` («Tipo articolo», etichetta sopra il titolo nelle card delle news)
vuota; nella news più recente vale «Approfondimenti». Valore da decidere, poi si aggiunge a `$TV_NEWS` di
`installa.php`. `soloStruttura` vuota va bene: il valore predefinito è 0, «Ovunque».
