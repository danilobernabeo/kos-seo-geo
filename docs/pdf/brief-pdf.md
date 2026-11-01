# Brief: gestione dei PDF di kosgroup.com e anniazzurri.it (KOS-8, AA-3)

Danilo Bernabeo · 1 novembre 2026 · brief per una chat dedicata di questo progetto, nello stesso repository

Leggi prima `CLAUDE.md` (regole del progetto, comandi per Danilo, convenzioni), poi questo file. Il lavoro è una voce
d'audit dello sprint 2: commit con il codice della voce (`KOS-8`, `AA-3`), documentazione e rilasci come il resto del
progetto. Lingua di lavoro: italiano.

## 1. Obiettivo

Mettere ordine nei PDF dei due siti in ottica SEO/GEO:

- **carte dei servizi e pieghevoli** (documenti che descrivono una struttura o un servizio): restano indicizzabili e
  ricevono un **titolo corretto nei metadati del file** (è il titolo che Google mostra nei risultati e che gli assistenti
  AI leggono come nome del documento), secondo uno schema fisso per tipo, ricavato dal tipo e dal contenuto;
- **tutti gli altri** (PARM/PARS, questionari, moduli, regolamenti, liste d'attesa, comunicati, menu, documenti
  amministrativi): **fuori dall'indice** di Google, senza renderli irraggiungibili;
- capire **se e come escono oggi nei risultati** (Search Console) e gestire i **reindirizzamenti** dei PDF spostati,
  rinominati o con traffico da recuperare.

Decisione di Danilo (1/11): per i PDF da tenere si riscrivono i metadati del file. Il contenuto resta intatto e l'URL
non cambia.

## 2. Cosa chiedono gli audit

| Voce | Testo dell'audit (sintesi) | Foglio di dettaglio |
| --- | --- | --- |
| KOS-8 (media) | 201 PDF tra le 1.127 pagine indicizzate: possono intercettare keyword unbranded e competere con le pagine HTML; molti senza un meta title ottimizzato | `kosgroup/dati/kos-21-23/ac7a9f06-202609_KosGroup_Dettaglio_Audit_GEO_Ant.xlsx` nel repository, foglio «Gestione PDF» (circa 590 righe, URL e meta title) |
| AA-3 (alta) | `noindex, nofollow` su tutti i PDF; valutare una sitemap dei PDF per seguirne la deindicizzazione; dopo la deindicizzazione blocco in robots.txt e `rel="nofollow"` sui link ai PDF | file del progetto `202604_AnniAzzurri_Audit_GEO_v2.2_Ant.xlsx` (leggerlo con `project_read`), foglio «Gestione PDF» (circa 300 righe) |

Il calendario degli sprint indica come prerequisito «la conferma della classificazione dei PDF» da parte di KOS.
Su AA la scelta del progetto è più fine dell'audit: carte e pieghevoli restano indicizzabili (sono la fonte più ricca su
servizi, tariffe e accesso di una residenza); la nota di consegna lo motiva in una riga.

## 3. Cosa c'è già

- `scripts/checks/pdf-mancanti.php` e `pdf-intervento.php`: PDF richiesti e mancanti (dal log), link rotti nel sito,
  redirect proposti.
- Regole web AA: `anniazzurri/files/web-rules/aa-3-pdf-redirect.conf` (97 redirect dei PDF rinominati o del vecchio
  sito, in produzione dal 27/10); regole complete di produzione al 31/10: `<sito>/files/web-rules/produzione-2026-10-31.conf`.
- Regole web KOS: `kosgroup/files/web-rules/kos-8-cartelle-documenti.conf` (cartelle di documenti → scheda struttura).
- `scripts/checks/aa-carte-servizi.php`: trova le carte dei servizi AA ed estrae il testo **sul server** (pdftotext o
  la libreria PHP in `scripts/vendor/pdfparser`), con un CSV e un archivio dei testi. Da generalizzare a tutti i PDF dei
  due siti.
- Elenchi dei documenti nelle pagine: snippet `docList` (chunk `documenti*` su KOS, `documents`, `lrsa-documents`,
  `doc-utili-cure-domiciliari` su AA), `listLda` (liste d'attesa KOS), `listingComunicati` (comunicati KOS). Su KOS
  `docList` ha una cache basata sulla data della cartella.
- Search Console in BigQuery (progetto `kos-analytics-482819`): `searchconsole_aa.storico_url_impression` e
  `searchconsole_kos.storico_url_impression`, 14/6/2025-23/10/2026, per URL; tabelle `storico_site_*` per le query.
- Percorsi: i PDF stanno quasi tutti in `/assets/doc/strutture/<alias della struttura>/`; comunicati KOS in
  `/assets/doc/comunicati/<anno>/`.

## 4. Vincoli

- Dalla sessione di Claude i siti non sono raggiungibili con curl: i PDF si leggono **sul server** con script PHP
  (sola lettura, risultati in `~`), oppure si scaricano in un archivio che Danilo copia nella cartella condivisa. Un
  archivio di tutte le carte AA pesava 211 MB: meglio estrarre testo e metadati sul server.
- Riscrittura dei metadati: in sessione con Python (es. `pikepdf`, `pip install --break-system-packages`) su copie dei
  file, poi un installatore ripetibile sul server con prova, `--scrivi`, backup dei file originali e ripristino (come
  `scripts/deploy/installa-elementi.php`). Titolo sia nel dizionario Info (`/Title`) sia nei metadati XMP (`dc:title`);
  nient'altro cambia nel file. Prima lo stage, poi la produzione.
- Fuori dall'indice con `X-Robots-Tag: noindex` nelle regole web, per percorso o per elenco. **Mai** il blocco in
  robots.txt prima che i PDF siano usciti dall'indice: Google non rileggerebbe più l'intestazione. Le regole web sono un
  file condiviso con il resto del progetto: una sezione dedicata e marcata, rilasci coordinati con la chat principale.
- Nessun link relativo (`href="/assets/..."`), nessuna chiamata non in cache nuova, un solo `@graph` per pagina: vedi
  `CLAUDE.md`.
- Comandi per Danilo pronti da incollare, uno per blocco, **un pacchetto per comando con `--scrivi` dentro il comando**
  (il 1/11 un `--scrivi` messo solo in fondo ha lasciato in prova il primo di due pacchetti).
- Rilascio in produzione appena il pacchetto è collaudato sullo stage (Danilo, 1/11: anche lo stesso giorno); ultima data utile **venerdì 6/11 alle 13:00**. I PDF con nomi del personale («Il team», «La nostra équipe», «Organizzazione», tirocini) restano invariati fino alla conferma di KOS nella call del 3/11.
- I PDF caricati dalla redazione dopo il rilascio non avranno il titolo: serve un controllo ripetibile (script che segnala
  i PDF senza titolo o con un titolo fuori schema) o un plugin al caricamento, come `ImmaginiCaricamento` per le immagini.

## 5. Passi e consegne

| Quando | Passo | Consegna |
| --- | --- | --- |
| 1-2/11 | Inventario sul server dei due siti: percorso, dimensione, data, pagine che lo linkano, titolo nei metadati, numero di pagine, prime righe di testo | `docs/pdf/inventario-<sito>-<data>.csv` (o xlsx fuori da git se contiene dati personali) |
| 2/11 | Search Console: impressioni, clic, posizione e query per ogni PDF, e quali query il PDF intercetta al posto di una pagina HTML | foglio nell'inventario |
| 2/11 | Classificazione per regole (nome e testo): carta dei servizi, pieghevole, comunicato, modulo, altro (con sottotipo); proposta di titolo per i PDF da tenere | foglio per la revisione di Danilo (xlsx fuori da git se contiene dati personali) |
| 2-3/11 | Danilo conferma la classificazione e la condivide con KOS (call di martedì) | foglio confermato |
| 3-4/11 | Pacchetto metadati (riscrittura, installatore, ripristino) e regole web `X-Robots-Tag` sullo stage; sitemap dei soli PDF da escludere | `scripts/pdf/`, `<sito>/files/web-rules/pdf-noindex.conf` |
| 4-5/11 | Collaudo sullo stage, poi produzione | evidenze per la nota di consegna |
| 5-6/11 | Controllo ripetibile per i PDF nuovi; reindirizzamenti per PDF spostati o con traffico | script e regole |

Schema di titolo da proporre (da confermare): `Carta dei servizi – <nome ufficiale della struttura>`,
`<Titolo del pieghevole> – <nome della struttura>`, con il nome ufficiale della struttura come nella scheda del sito
(H1). Lunghezza entro 60-65 caratteri.

## 6. Decisioni (Danilo, 1/11) e punti da chiudere

- **Classificazione:** la conferma Danilo sul foglio; poi la condivide con KOS. Il foglio va preparato per la sua
  revisione (proposta, motivo, titolo proposto, impressioni), non come domanda aperta a KOS.
- **Comunicati stampa KOS:** oggi il comunicato esiste **solo** come PDF (il template `comunicati` con
  `comunicatoRedirect` manda la pagina del comunicato al PDF con un 301, circa 50 file). Con un `noindex` il contenuto
  sparirebbe del tutto da Google e dagli assistenti AI, che sulle notizie istituzionali del gruppo (risultati,
  acquisizioni, nomine) sono proprio la fonte da presidiare. Proposta: tenerli indicizzabili con un titolo
  `Comunicato stampa – <titolo> – <mese anno>` (titolo e data dalla risorsa MODX del comunicato) e un'intestazione
  `Link: <URL della pagina elenco>; rel="canonical"` solo se Danilo preferisce che vinca l'elenco. Decisione finale a Danilo
  dopo aver visto impressioni e query in Search Console. La buona pratica vera (comunicato in HTML con il PDF allegato)
  è fuori perimetro: si annota nel piano interno.
- **Moduli e documenti con dati personali:** elencarli a parte (nome, struttura, pagina che li linka, motivo), senza
  toglierli; Danilo decide dopo averli visti e sentito KOS. Nel frattempo ricevono il `noindex` come gli altri esclusi.
- **PDF delle strutture chiuse:** il PDF segue il reindirizzamento della sua pagina. Se la pagina della struttura
  chiusa rimanda a un'altra struttura e lì esiste il documento equivalente (carta → carta, pieghevole → pieghevole),
  301 a quel documento; altrimenti 301 alla pagina di destinazione. Mai un 301 verso un documento di contenuto diverso
  (Google lo tratta come soft 404). I documenti senza equivalente e senza traffico possono anche rispondere 410.
- **Da chiudere nella chat:** documenti identici su più strutture (stesso file in più cartelle): uno solo indicizzabile,
  gli altri con `Link: rel="canonical"` verso quello tenuto.

## 7. Fuori da questo lavoro

- Le pagine HTML delle strutture e il loro dato strutturato (voce 24 KOS, sprint 3 AA): non toccare `aaSchema`,
  `kosSchema` e i template delle schede. Se serve un collegamento PDF → pagina (es. `subjectOf` della carta dei servizi
  nel nodo struttura), si propone alla chat principale.
- Le proposte fuori perimetro (es. riscrivere i testi dei PDF) restano nel piano interno, non nei documenti per KOS.
