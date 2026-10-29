# News «Creiamo insieme le forme del nostro Natale: il progetto Anni Azzurri e MUBA» (fuori dal progetto SEO/GEO)

Lavoro extra su anniazzurri.it: news sul progetto con MUBA – Museo dei Bambini Milano e REMIDA Milano. L'impianto è
quello classico delle news (template `interna-articolo`), con la grafica del totem «Un Natale alla portata di tutti»:

- fondo azzurro `#99dde3` su tutta la pagina;
- cerchi bianchi semitrasparenti e, accanto al testo, le sagome vettoriali (albero, stella, raggi, onda), estratte
  dal PDF Illustrator del totem senza le foto delle decorazioni;
- galleria: mosaico di 5 foto con la griglia della home (versione ridotta, come nelle pagine interne, sotto i
  1200 px) e visore a tutto schermo con tutte le foto, divise per residenza;
- tre videotutorial con anteprima e pulsante play (l'anteprima riprende la parte bassa del totem: la decorazione
  fotografica appesa al filo, palla bianca e palline, in una cornice blu scura): il video si apre in una finestra sopra la pagina, uno alla volta;
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
| `elementi/templates/interna-articolo-muba.html` | copia di `interna-articolo` (versione del repository) con: classe `news-muba-page` sul body, cerchi decorativi, testo a tutta larghezza (le righe sono limitate dal CSS), visore della galleria e finestra dei video, testata con ripiego |
| `elementi/snippets/newsMubaRisorse.php` | registra CSS e JS solo su questa pagina (il chunk `head-news`, comune a tutte le news, non si tocca) |
| `elementi/snippets/newsMubaImmagine.php` | testata: usa lo snippet `immagine` del progetto se c'è, altrimenti un `<img>` semplice (produzione prima del rilascio della libreria immagini) |
| `elementi/contenuto.html` | contenuto della news, dal docx; link a MUBA e alle residenze di Milano (`/rsa-a-milano-e-provincia`) |
| `files/assets/news-muba/` | `news-muba.css`, `news-muba.js`, sagome SVG (`albero-stella`, `raggi`, `onda`), anteprime dei video (`video-albero`, `video-stella`, `video-pallina`, WebP 1280 × 720), `logo-muba.svg`, testata dell'agenzia (`testata.jpg`, 980 × 551, senza metadati), testata di prova e segnaposto (non più usate), `foto/` (generata da `prepara-foto.py`) |
| `foto.json` | gruppi (residenze, con nome e link), foto grande e iniziali del mosaico, foto fuori dal sorteggio, testi alternativi |
| `prepara-foto.py` | da `foto-sorgenti/` alle immagini di `foto/` e al blocco della galleria in `contenuto.html` (vedi sotto) |
| `foto-sorgenti/` | originali, una cartella per residenza; **fuori da git** (`.gitignore`): pesanti e con la posizione GPS |
| `anteprima/costruisci.py` | anteprima locale senza MODX (Bootstrap 5.1.3 al posto del CSS del sito) |
| `anteprima/anteprime-video.py` | rigenera le tre anteprime dei video dal PDF Illustrator del totem (decorazioni scontornate; posizione e tipo delle palline in `VAR`) |
| `installa.php` | installazione con prova, pubblicazione separata, aggiornamento e ripristino |

Title e description seguono le regole del progetto (AA-20): `longtitle` «Anziani e bambini creano il Natale con
MUBA» (43 caratteri; il title è longtitle + « | Anni Azzurri», 58), `description` di 153 caratteri (massimo 155).
Il `pagetitle` lungo resta come titolo visibile della news. `--aggiorna` riallinea anche longtitle e description.

La news nasce con il campo «Editor visuale» spento (`richtext` = 0), così l'editor del manager non riscrive i pulsanti
della galleria e dei video.

## Da completare prima della pubblicazione

- **Testata** (TV `newsImage`): `testata.jpg`, dell'agenzia (29/10), **980 × 551 px** come le altre news; originale
  in `foto-sorgenti/Testata/`, fuori da git. `--aggiorna` riallinea anche la TV. La testata è anche l'immagine
  dell'elenco delle news e della condivisione social.
- **Foto della galleria**: oggi 53 in 4 gruppi: Navigli 15 (7 più 8 da WhatsApp del 29/10, 1200 × 1600), San Luca 7, San Rocco 10 (7 più 3 ingrandimenti scattati
  lì), San Faustino e PGR Milano 21 (evento unico con gli ospiti delle due strutture insieme, non distinguibili: un
  gruppo solo, con il link a entrambe le pagine; 3 scatti quasi doppi esclusi in `escluse`). Ne arriveranno altre.
  Liberatorie presenti. San Luca è a 1200 × 1600: se ci sono gli originali dal telefono, meglio quelli. Testi
  alternativi in `foto.json`; nel primo gruppo di foto al tavolo ci sono solo operatrici della residenza; nelle foto
  di San Faustino e PGR alcune persone indossano la maglietta del MUBA: i testi dicono «operatore/operatrice».

## Galleria: come si aggiornano le foto

1. Originali in `foto-sorgenti/<cartella>/`, così come arrivano (rotazione, misure e metadati li sistema lo script).
2. Una residenza nuova: si aggiunge in `foto.json` → `gruppi` (etichetta del pulsante, nome, luogo, slug, cartelle,
   `collegamenti`: uno per struttura, con nome e indirizzo). L'ordine dei gruppi è quello dell'elenco. La frase del
   mosaico (`mosaico.invito`, oggi «Oltre 50 ospiti, un solo laboratorio», dal testo del 29/10) si cambia a mano. Scatti doppi: nome del file in
   `escluse`. Con più di 5 pulsanti va rimisurato sullo stage il punto (oggi 992 px, con il titolo del visore nascosto fino a 1199) da cui i pulsanti sostituiscono il menu.
3. Testi alternativi in `foto.json` → `alt`, con il nome del file come chiave (lo script avvisa di quelli mancanti).
4. `python prepara-foto.py` (serve Pillow). Prepara solo le foto nuove o cambiate (`--forza` le rifà tutte), toglie
   quelle che non servono più e riscrive il blocco fra `<!-- galleria:inizio -->` e `<!-- galleria:fine -->` in
   `contenuto.html`. Quel blocco non si modifica a mano.
5. Commit, archivio e `installa.php --aggiorna` come sotto.

Regole del mosaico: la foto grande è fissa (`mosaico.grande`, orizzontale); le altre quattro caselle (due
orizzontali, due verticali; da smartphone e tablet solo le verticali) partono dalle `iniziali` e a ogni visita
pescano a caso tra le foto del formato giusto, preferendo residenze diverse. Le foto di `fuori_mosaico` (gli
ingrandimenti, troppo simili alla foto grande) restano solo nel visore. Dentro una residenza l'ordine è quello di
scatto, se tutte le foto hanno la data; altrimenti quello dei nomi dei file.

Per ogni foto lo script scrive in `foto/`: `-1600.jpg` e `-1600.webp` (visore), `-800.webp` (mosaico e smartphone),
`-q.webp` (miniatura quadrata). Niente snippet `immagine` per la galleria: con 52 foto genererebbe centinaia di
varianti in una sola richiesta, al primo rinnovo della cache.

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

**Produzione**: gli stessi comandi sul cloud di produzione (`aa-prod`), ad approvazione. Per farla vedere prima del
lancio: `php installa.php /www --scrivi --pubblica --nascosta`. La news è pubblicata e l'indirizzo del QR code
funziona, ma è fuori da menu ed elenchi delle news («Nascondi dai menu»), fuori dalla sitemap e in `noindex`
(«Ricercabile» spenta). Al lancio: `php installa.php /www --mostra --scrivi` (riaccende le due caselle e porta la data
di pubblicazione ad adesso, così la news va in cima all'elenco); `--nascosta --scrivi` la rinasconde.
Dal manager si ottiene lo stesso con tre campi: «Ricercabile» acceso (via il noindex, dentro la sitemap), «Nascondi dai
menu» spento (gli elenchi delle news, getResources, escludono le risorse nascoste) e data di pubblicazione aggiornata
(gli elenchi sono ordinati per data). Solo «Ricercabile» non basta: la news resterebbe fuori da home e `/news/`.

Ripristino: il comando è stampato alla fine di ogni scrittura
(`php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-news-muba/ripristino.json --scrivi`).

## Verifiche dopo l'installazione

- Fondo azzurro su tutta la pagina, sagome a destra del primo blocco di testo e dei videotutorial (da 992 px in su),
  onde fra le sezioni; su smartphone restano cerchi e onde.
- Mosaico: griglia della home da 1200 px, sotto foto grande + due verticali + frase; ricaricando cambiano le
  caselle piccole (mai due volte la stessa foto, mai la grande).
- Visore: clic su una casella (si apre su quella foto) o su «Guarda tutte» (dalla prima); frecce, tasti
  sinistra/destra, scorrimento con il dito, miniature; pulsanti per residenza da 992 px, menu sotto; contatore e
  link alla residenza; Esc chiude e il focus torna dove si era.
- Video: niente richieste a YouTube prima del clic (pannello Rete del browser); al clic si apre la finestra e parte
  il video; chiudendo (X, Esc, clic fuori) l'audio si ferma e il focus torna sul pulsante; aprendo un altro video
  parte solo quello.
- Elenco delle news e condivisione: la news compare con la testata.

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 27/10/2026 | stage (c0025) | installata **non pubblicata** (news 592, template 66 con le 11 TV di `interna-articolo`), foto e testata segnaposto | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261027-095552-news-muba/ripristino.json --scrivi` |
| 27/10/2026 | stage (c0025) | `--aggiorna`: mosaico e visore con 24 foto in 3 residenze (news pubblicata sullo stage) | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261027-123458-news-muba-agg/ripristino.json --scrivi` |
| 27/10/2026 | stage (c0025) | `--aggiorna`: testata di prova (Foto generica 3, TV `newsImage`), testi delle foto corretti (operatrici); verificata con Chrome da 360 a 1920 px | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261027-124644-news-muba-agg/ripristino.json --scrivi` |
| 27/10/2026 | stage (c0025) | `--aggiorna`: longtitle (43 caratteri) e description (153) nei limiti AA-20 | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261027-142704-news-muba-agg/ripristino.json --scrivi` |
| 28/10/2026 | stage (c0025) | `--aggiorna`: 21 foto di San Faustino e PGR Milano (45 in tutto), didascalia con due link, frase «Cinque residenze»; verificato con Chrome da 390 a 1920 px | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261028-111309-news-muba-agg/ripristino.json --scrivi` |
| 28/10/2026 | produzione (c0072) | installata e pubblicata **nascosta** (news 589, template 66 con 11 TV, 191 file): fuori da menu, elenchi e sitemap, `noindex, follow`; verificato sul sito pubblico (indirizzo del QR anche con i parametri utm, 180 file delle foto, assente da home, `/news/` e sitemap). Al lancio: `--mostra --scrivi` | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261028-112111-news-muba/ripristino.json --scrivi` |
| 28/10/2026 | produzione (c0072) | `--aggiorna`: TV `newsArticle` = «News», pulsante «Guarda tutte le 45 foto»; news sempre nascosta (verificato: `noindex`, assente da home e `/news/`) | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261028-113949-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | produzione (c0072) | `--aggiorna`: testi del Word 29/10, introtext e description (145), frase del mosaico, testata dell'agenzia, anteprime dei video con cornice blu; news sempre nascosta (verificato: `noindex`, assente da home e `/news/`; da 390 a 1920 px) | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261029-073135-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | produzione (c0072) | `--aggiorna`: 8 foto in più di RSA Navigli (53 in tutto); news sempre nascosta (verificato: 32 file, visore, `noindex`, assente da home e `/news/`) | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261029-121231-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | produzione (c0072) | `--aggiorna`: invito «Prova anche tu a casa!» e pulsanti «Guarda il videotutorial»; news sempre nascosta (verificato: finestra del video, `noindex`, assente da home e `/news/`; 390 e 1280 px) | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261029-125328-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | produzione (c0072) | `--aggiorna`: link «Aprilo su YouTube ↗»; news sempre nascosta (verificato: link e pulsanti, `noindex`, assente da home e `/news/`) | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261029-135246-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | stage (c0025) | `--aggiorna`: anteprime dei video in stile totem con cornice blu, decorazioni appese tolte | (percorso non annotato) |
| 29/10/2026 | stage (c0025) | `--aggiorna`: testi del Word 29/10 (7 residenze, oltre 50 ospiti), introtext e description (145), frase del mosaico «Oltre 50 ospiti», testata dell'agenzia (`testata.jpg`); verificato con Chrome da 390 a 1280 px | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261029-072504-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | stage (c0025) | `--aggiorna`: 8 foto in più di RSA Navigli (53 in tutto, Navigli 15); verificato con Chrome (32 file, filtro e visore) | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261029-120920-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | stage (c0025) | `--aggiorna`: invito «Prova anche tu a casa!» sopra i videotutorial e pulsante «Guarda il videotutorial» sotto ciascuno (stessa finestra del play); verificato con Chrome da 390 a 1920 px | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261029-125021-news-muba-agg/ripristino.json --scrivi` |
| 29/10/2026 | stage (c0025) | `--aggiorna`: link «Aprilo su YouTube ↗» (con «si apre in una nuova scheda» per i lettori di schermo) al posto di «oppure su YouTube» | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261029-130652-news-muba-agg/ripristino.json --scrivi` |

TV `newsArticle` («Tipo articolo», etichetta sopra il titolo nelle card delle news): «News» (deciso il 28/10),
in `$TV_NEWS` di `installa.php`; `--aggiorna` la riallinea. `soloStruttura` vuota va bene: il valore predefinito è 0, «Ovunque».
