# Clinica Dentale Villa dei Pini su kosgroup.com (fuori dal progetto SEO/GEO)

Lavoro extra: scheda nuova «Clinica Dentale Villa dei Pini» sotto Centri, `/it/centri/clinica-dentale-villa-dei-pini`,
con lo stesso template, le stesse TV e lo stesso schema di sezioni delle altre due cliniche dentali (Villalba, Porto
Potenza Picena). La scheda è la pagina di riferimento della clinica; la landing di campagna
`/it/landing/clinica-dentale-civitanova` resta per gli annunci, non indicizzata. Non è una voce d'audit e non compare
nella documentazione del progetto.

## Decisioni (Danilo, 25/11; registro decisioni)

| Tema | Decisione |
|---|---|
| Dato strutturato | `Dentist` `#localbusiness` con le TV `str.*`, `parentOrganization` come le altre due cliniche: la clinica ha una scheda Google propria («Clinica Dentale Villa dei Pini, Viale dei Pini 31», gruppo Poliambulatori, attiva da aprile 2025), anche se l'indirizzo è quello della Casa di Cura. `containedInPlace` verso Villa dei Pini dopo il 4/12, nella libreria comune |
| Telefono e orari | 345 6026346; lun-ven 8:30-19:00, sabato 8:30-13:00 (quelli della landing) nella scheda, nelle TV e nel dato strutturato |
| Alias | `clinica-dentale-villa-dei-pini`, anche per le cartelle delle immagini |
| Prenotazione | barra delle schede (`barraPrenotazioneDBmedicoPrestazione`, Tom Select) con l'istanza di Villa dei Pini, limitata a Odontoiatria con la nuova proprietà `&branca` (2 prestazioni attese). I «Prenota online» della scheda e della landing portano alla sezione `#prenota-online` |
| Landing | è di Danilo ed entra in questo pacchetto: `noindex, follow`, via il JSON-LD scritto a mano (`MedicalClinic` senza `@id`, doppione del nodo della scheda), prenotazione online attiva verso la scheda |
| News | la news dell'inaugurazione e i due approfondimenti dentali (sensibilità, sbiancamento, già sulla scheda di Porto Potenza) hanno l'id della scheda nella TV `idStruttura`: escono in «Le ultime news da …». Le 5 news orfane di Villa dei Pini sono già collegate dalla sua scheda (`contenutiStruttura` del 25/11) |
| Collegamenti | Villa dei Pini Civitanova (risorsa 218): voce «Clinica Dentale» negli approfondimenti della struttura, dopo «Diagnostica e Servizi Ambulatoriali», con il link `[[~id]]`. Elenco `/it/strutture/cliniche-dentali`: automatico (pdoMenu sulla TV `tipo_struttura`, copiata dal modello). Sitemap e menu come le altre cliniche (campi copiati da Villalba); nessuna versione inglese, quindi niente hreflang |

## Contenuto

| Percorso | Cosa |
|---|---|
| `config.json` | campi, TV, regole di costruzione del contenuto, news, landing, llms.txt, foto provvisorie |
| `elementi/*.html` | testi della scheda: intestazione (H1 e indirizzo), contatti, pulsanti, La struttura, approfondimenti (come funziona e domande frequenti), trattamenti (dentro Aree di intervento), équipe, Come raggiungerci; voce per la scheda di Villa dei Pini |
| `installa.php` | installazione ripetibile: prova, `--scrivi`, `--foto-provvisorie` (solo stage), `--senza-sede` (non tocca la 218), `--ripristina`, `--test` (senza MODX). La chiave di Google Maps della mappa si prende dall'iframe di Villalba: nel repository non c'è |
| `verifica-barra.php` | sola lettura: la barra con e senza `&branca`, confronto byte per byte su tutte le schede |
| `ispeziona.php` | sola lettura: campi, TV, contenuti e template delle risorse coinvolte (`~/clinica-dentale-ispezione/`) |
| `collaudo.mjs` | collaudo sul sito servito (scheda, landing, Villa dei Pini, elenco, sitemap, llms.txt) |
| `baseline/` | copie pubbliche del 25/11 della landing e della scheda di Villalba (riferimento). Le copie esatte dal database sono nel backup di ogni `--scrivi` e in `~/clinica-dentale-ispezione/` |

Il progetto fornisce lo snippet `barraPrenotazioneDBmedicoPrestazione` con `&branca` (pacchetto
`kosgroup/deploy/kos-barra-branca-20261125.json`, elemento del progetto modificato con l'autorizzazione di Danilo):
va installato prima della scheda, che lo richiede.

### Come si costruisce la scheda

Il contenuto non è scritto da zero: `installa.php` parte dal contenuto della scheda di Villalba (stesso markup, stesse
chiamate per galleria, briciole e piè di pagina) e:

1. sostituisce per intero gli elementi riconosciuti dall'apertura (`config.json` → `sostituzioni`): intestazione,
   contatti e pulsanti (due volte: barra laterale e versione mobile), La struttura, approfondimenti, équipe, Come
   raggiungerci; si ferma se un elemento non c'è o compare un numero di volte diverso;
2. aggiunge i nove trattamenti dentro «Aree di intervento» (le card restano quelle delle TV di mappatura dei servizi:
   per ora solo Odontoiatria) e la barra di prenotazione prima del blocco foto;
3. sostituisce l'alias e il nome di Villalba nel resto (galleria, piè di pagina) e rende in cache la galleria;
4. si ferma se nel contenuto restano parole di Villalba (indirizzo, telefono, persone, modulo «Parla con noi») o
   chiamate non in cache diverse dalla barra (ammessa: ha la sua cache interna, registro del 22/11);
5. risolve i link `[[~{{uri:…}}]]` dei frammenti con gli id del sito (registro del 25/11: link con `[[~id]]`).

TV: quelle di Villalba, tranne le `str.*`, l'indirizzo e `translationId` (da `config.json`), `istanza` e coordinate
(da Villa dei Pini) e le TV di mappatura dei servizi (solo Odontoiatria). Lo script si ferma se una TV copiata contiene
parole di Villalba.

## Segnaposto da completare durante il collaudo

Nel testo ci sono segnaposto visibili `[DA COMPLETARE: …]`. In produzione lo script non installa una scheda che ne
contiene, nemmeno con `--forza`. Si completano nel repository (`elementi/`), poi si rilancia lo script.

| Dove | Cosa manca |
|---|---|
| `elementi/contatti.html` | nome del responsabile delle cliniche dentali |
| `elementi/equipe.html` | conferma dell'elenco e dei ruoli (oggi i 9 odontoiatri e direttori della landing); igienista, assistenti alla poltrona, infermiera e front office sì o no; foto singole |
| `elementi/servizi.html` | chirurgia maxillofacciale e TC Cone Beam: se ci sono, si aggiungono alle TV di mappatura dei servizi (`config.json` → `servizi_uri`) e il segnaposto si toglie |
| foto e documenti | foto della clinica per galleria (`/assets/img/strutture/clinica-dentale-villa-dei-pini/01.jpg`…), card dell'elenco (`/assets/img/strutture/clinica-dentale-villa-dei-pini.jpg`) e piè di pagina (`/assets/img/bg-clinica-dentale-villa-dei-pini.jpg`); carta dei servizi, se c'è. Sullo stage, intanto, `--foto-provvisorie` le ricava dalle foto della landing |
| `config.json` → `str.place_id` | place_id della scheda Google (serve per `hasMap`; il collaudo lo segnala come avviso) |

Testi alternativi: la galleria usa il testo comune delle schede (`galleryStruttura`, KOS-7: «Clinica Dentale Villa dei
Pini – foto N di T»); alt descrittivi per singola foto richiedono una modifica dello snippet del progetto (miglioria,
dopo il 4/12).

## Nota per KOS (scheda Google)

Nella scheda Google «Clinica Dentale Villa dei Pini» vanno messi gli stessi dati della pagina: telefono 345 6026346;
orari lunedì-venerdì 8:30-19:00, sabato 8:30-13:00; sito `https://kosgroup.com/it/centri/clinica-dentale-villa-dei-pini`
(oggi il sito della scheda è `laclinicadentale.com`, senza canonical); link per gli appuntamenti
`https://kosgroup.com/it/centri/clinica-dentale-villa-dei-pini#prenota-online`; categoria principale «Clinica dentale».

## Rilasci

Calendario (Danilo, 25/11): il 25 e il 26/11 stage e collaudo; produzione venerdì 27/11 dopo l'invio della nota dello
sprint 4, oppure lunedì 30/11. Il 26/11 niente produzione. Il link dalla risorsa 218 (Villa dei Pini) va in produzione
solo prima del 30/11, quindi il 27/11 insieme alla scheda; se la scheda slitta al 30/11, si installa con `--senza-sede` e
il link si aggiunge dopo il 4/12 (stesso script, senza l'opzione). Da decidere con Danilo se anche lo snippet della barra
(tocca tutte le schede con la barra) può andare il 30/11: il rilascio della cache della barra del 22/11 indicava «entro il
26/11 oppure dopo il 4/12» per la verifica dello sprint 3 (KOS 11) dal 30/11 al 4/12.

Ordine, prima sullo stage poi in produzione, sempre prima la prova e poi `--scrivi` (comandi pronti nella chat):

1. barra, **prima di installarla**: `php extra/kos-clinica-dentale-villa-dei-pini/verifica-barra.php /www` confronta la
   versione installata con quella del repository (atteso IDENTICO su tutte le schede) e conta le prestazioni di
   Odontoiatria a Villa dei Pini (attese 2; se sono di più ci si ferma). Dopo l'installazione il confronto non dice più
   nulla: le due versioni coincidono. Poi `php scripts/deploy/installa-elementi.php /www kosgroup/deploy/kos-barra-branca-20261125.json`
   (prova, poi `--scrivi`);
2. scheda: `php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www` (sullo stage `--foto-provvisorie`). La prova
   scrive il contenuto costruito in `~/clinica-dentale-contenuto.html`: è il primo controllo sul contenuto vero di
   Villalba (le prove in locale sono state fatte sull'HTML pubblico); può fermarsi su un elemento non riconosciuto;
3. peso delle immagini, anche delle varianti generate alla prima visita: aprire la scheda, poi
   `cd /www && find assets/img/strutture assets/img/bg-clinica-dentale-villa-dei-pini.jpg assets/cache-img -path '*clinica-dentale-villa-dei-pini*' -type f > ~/cdvdp-tetto.txt`
   e, dalla radice dell'archivio, `php scripts/immagini/tetto-peso.php /www ~/cdvdp-tetto.txt` (poi `--scrivi`);
4. collaudo: `node extra/kos-clinica-dentale-villa-dei-pini/collaudo.mjs --base=https://stage.kosgroup.com`
   (`--senza-sede` se la 218 non è stata toccata), poi la regressione delle schede (`scripts/collaudo/collaudo-kos-24.mjs`)
   e il confronto visivo di Villa dei Pini e delle due cliniche esistenti (`scripts/collaudo/collaudo-visivo.mjs`).
   Il collaudo è stato provato su Villalba in produzione (`--uri=it/centri/clinica-dentale-villalba`): controlli di
   struttura OK, KO attesi solo sui valori di Villa dei Pini.

Sullo stage le tabelle `kos_*` sono ferme al 18/10 e Odontoiatria (aggiunta in TuoTempo il 29/10) non ha prestazioni; in produzione sì (verifica di Danilo del 25/11). Sullo stage la scheda si installa con `--prestazioni-stage` (vale solo lì): la barra mostra «Nessuna prestazione disponibile» e il collaudo dà KO attesi sui due controlli della barra. In produzione il controllo delle 2 prestazioni resta.

In produzione lo script si ferma se restano segnaposto `[DA COMPLETARE]` o se mancano le foto (galleria, card
dell'elenco `/assets/img/strutture/clinica-dentale-villa-dei-pini.jpg`, piè di pagina): le foto provvisorie sono solo
per lo stage.

La landing è stata costruita con il kit delle landing di Danilo (`CD_CONFIG` scritto dalla build): una nuova build
dal kit rimetterebbe `prenotaOnline` spento, il JSON-LD e niente `noindex`. Le stesse modifiche vanno portate nel
`config.json` e nel modello del kit, oppure si rilancia questo script dopo ogni ripubblicazione.

| Data | Ambiente | Cosa | Backup / ripristino |
|---|---|---|---|
| | stage | | |

## Ripristino

`installa.php` scrive `~/backup-kos-seo-geo/<data>-clinica-dentale/ripristino.json`:

    php extra/kos-clinica-dentale-villa-dei-pini/installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-clinica-dentale/ripristino.json --scrivi

Toglie la scheda creata (con le sue TV), rimette contenuto della 218 e della landing (o il suo template), `idStruttura`
delle news, `llms.txt`, e cancella le foto create. Solo il `ripristino.json` della **prima** installazione toglie la
scheda: quelli dei rilanci successivi (aggiornamenti) la riportano allo stato precedente. Per tornare a prima di tutto,
si applicano i ripristini dal più recente al primo. La barra si ripristina con il `ripristino.json` stampato da
`installa-elementi.php`.

## Fuori da questo lavoro: difetti trovati nelle schede esistenti

Segnalati il 25/11, da correggere a parte (le schede esistenti non si toccano qui):

- Porto Potenza Picena: indirizzo «Via Aprutina, 217» nell'intestazione, «194» nel dato strutturato e nelle indicazioni;
- Porto Potenza Picena: il testo de «La struttura» parla di «Clinica Dentale Santo Stefano» e «Poliambulatorio Santo Stefano»;
- tutte e due: link «Perché lavorare in KOS» del riquadro KOSplayer con `href="//it/lavorare-in-kos/..."` (senza
  schema: il browser lo legge come dominio «it»); la scheda nuova lo eredita dal modello;
- tutte e due: immagine del piè di pagina con percorso relativo (`assets/img/...`) e icone dei mezzi pubblici
  (`assets/img/icona-mezzi.png`); la scheda nuova eredita il piè di pagina (nella mappa usa il percorso assoluto);
- Villalba: nel dato strutturato (TV `str.orari`, da Yext) il giovedì apre alle «08:09», probabilmente 08:30.
