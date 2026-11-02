# Parco di Veio: foto nuove (fuori dal progetto SEO/GEO)

Lavoro extra: aggiornare le foto della struttura Parco di Veio, organizzata in due unità.

| Unità | Pagina | Dove sono le foto |
|---|---|---|
| Poliambulatorio | `https://kosgroup.com/it/centri/poliambulatorio-parco-di-veio` | galleria: tutte le immagini di `/assets/img/strutture/poliambulatorio-parco-di-veio/` (snippet `galleryStruttura`, ordine alfabetico, la prima è la grande); footer: `/assets/img/bg-poliambulatorio-parco-di-veio.jpg` |
| RSA | `https://anniazzurri.it/residenza-anni-azzurri-parco-di-veio` | galleria: chunk `gal-residenza-anni-azzurri-parco-di-veio` (elenco dei file, snippet `immagine` per le varianti) in `/assets/img/strutture/residenza-anni-azzurri-parco-di-veio/` |

Servizio fotografico del 25/9/2026 (Nikon D850, 122 foto, nessuna posizione GPS), liberatorie presenti (conferma del
2/11). Gli originali sono in `selezione01/` e `selezione02/`, fuori da git (`.gitignore` della radice).

## Contenuto

| Percorso | Cosa |
|---|---|
| `kos/files/` | file da copiare nel webroot di kosgroup.com, stesso percorso |
| `aa/files/` | idem per anniazzurri.it (gallerie, da preparare) |
| `<sito>/svuota.txt` | cartelle da allineare al pacchetto (i file in più vanno nel backup) |
| `installa.php` | prova, `--scrivi` con backup, `--ripristina` |

## Footer del poliambulatorio

`bg-poliambulatorio-parco-di-veio.jpg`: palestra con fisioterapista e paziente alle parallele (scatto 7573), taglio
1920 × 645 come l'immagine precedente (1500 × 504, circa 3:1), JPG 82, senza metadati, circa 150 KB. Stesso nome: nessun
elemento toccato. Niente srcset per ora: il markup lo scrive la scheda struttura comune a tutte le strutture KOS; le
varianti arriveranno con KOS-2 C (`<picture>`/WebP su kosgroup.com, dopo il 16/11).

## Gallerie

Proposta del 2/11: 20 foto per il poliambulatorio (copertina: esterno, scatto 7209). Risposta di KOS del 2/11: palestra
e ingresso/accettazione (bancone blu nella hall) sono **solo del poliambulatorio**; anche il corridoio con le porte rosse e
la foto in carrozzina sono nella zona della palestra. RSA: esterni, soggiorno, sala, camere, bagno (13 foto); da confermare
l'ingresso con la pensilina (7277) e le porte vetrate sul giardino (7320).
Prima di spostare le foto vecchie, `installa.php` cerca i loro nomi in contenuti, TV, chunk e template: se qualcuno le
cita ancora si ferma (`--forza` per procedere comunque).

- Poliambulatorio (pronta, `kos/files/assets/img/strutture/poliambulatorio-parco-di-veio/`, 20 file, 2 MB): 1280 × 853, JPG 80, prefisso numerico (ordine) e nome descrittivo; le 18 foto attuali escono dalla
  cartella (backup), `kos/svuota.txt`.
- RSA (pronta, 2/11): 15 foto in `aa/files/assets/img/strutture/residenza-anni-azzurri-parco-di-veio/` a 1600 × 1067,
  JPG 82 (le varianti 480-1600 in JPG e WebP le crea lo snippet `immagine` al rinnovo della cache); 7277 e 7320
  confermate da KOS. Chunk `gal-residenza-anni-azzurri-parco-di-veio` riscritto in
  `anniazzurri/elements/chunks/` (elemento del progetto, AA-1 sprint 1: uscita entro il 6/11 oppure dopo il 14/11),
  manifest `aa/gal.json`: riquadri desktop esterno, camera, soggiorno; caroselli con le 15 foto; testi alternativi
  descrittivi al posto di «[[*pagetitle]], foto N». Le foto vecchie restano nella cartella (non più citate): niente
  `aa/svuota.txt`, perché il controllo delle citazioni le troverebbe nel chunk prima della sua sostituzione.

## Peso delle immagini (2/11)

Misura in produzione (Chrome, 1920 px, DPR 2): 2,3 MB di immagini al primo caricamento, di cui 1,99 MB sono le 15 foto
della galleria nella variante WebP 1600, caricate anche da desktop: carosello mobile (nascosto da `d-lg-none`) e
finestra «guarda tutte le foto» avevano tutte `&differita=`0``.

- Chunk: nel carosello mobile solo la prima cella con `&differita=`0`` e `&sizes=`100vw`` (su telefono equivale a
  `naturale`, che diventa 1600 px solo sopra i 1600 px di finestra); nella finestra tutte le foto in caricamento differito,
  `sizes` sulla larghezza del `modal-xl` (1108/768/468 px) e rimisura di Flickity all'apertura (`shown.bs.modal`). Le 15
  foto hanno lo stesso formato (1600 × 1067): `adaptiveHeight` non cambia altezza, e `width`/`height` riservano lo spazio
  anche prima del caricamento.
- Misura sullo stage dopo il chunk (conta-lazy, `scripts/collaudo/pdv-aa-stage/scaricate.csv`): 32 immagini lazy, ma al
  caricamento 14 JPEG `-1200.jpg` (81-183 KB, circa 1,7 MB) su mobile e desktop, non legate a nessun `<img>`. Causa:
  l'opzione `"imagesLoaded": true` dei due caroselli. Flickity 2 (`flickity.pkgd`) include imagesLoaded v4, che per ogni
  immagine non ancora completa crea un `new Image()` con `src = img.src`: il ripiego JPEG 1200 del `<picture>`, senza
  srcset né WebP e scavalcando `loading="lazy"` (manca solo `esterno-cortile`, prima cella già caricata). Opzione tolta
  da entrambi i caroselli: lo spazio lo riservano `width`/`height`, la rimisura resta al `load` e a `shown.bs.modal`.
  Le altre gallerie `gal-*` usano `imagesLoaded` senza immagini differite: non hanno questo problema.
- Anche la prima cella del carosello mobile è differita (`&differita=`1``): il carosello sta sotto la piega e non è mai
  l'LCP (che è la testata); su telefono la foto arriva scorrendo, su desktop il carosello nascosto non scarica nulla.
  L'`esterno-cortile-1600.webp` scaricato su desktop resta: è il riquadro grande della griglia (visibile, `40vw` a DPR 2).
- RSA, foto esterne (esterno-cortile, esterno, cortile-pergolato, esterno-giardino, ingresso-giardino): sfocatura
  gaussiana σ 0,7 e JPG 82; WebP stimate (q80) 1200: da 102-181 a 81-130 KB, 1600: da 157-311 a 113-188 KB.
  Resa approvata da Danilo il 2/11: la sfocatura resta (nessuna riesportazione).
- Poliambulatorio: 01-esterno (scatto 7209) e 02-ingresso-ambulatori (7503) riesportati dagli originali, 1280 × 853,
  σ 0,6, JPG 64 progressivo: da 199 e 183 KB a 112 e 111 KB (sotto i 150 KB dall'originale servirebbe una qualità
  troppo bassa senza ammorbidire).

## Testata della pagina RSA

`/assets/img/rsa/residenza-anni-azzurri-parco-di-veio.jpg` (template `rsa`, snippet `immagine`, `object-fit: cover`): edificio
e giardino (scatto 7193), cornice spostata in alto perché da desktop (1905 × 567) si veda la linea del tetto; 1500 × 1000 come
la precedente, JPG 82. Stesso nome: le varianti 480-1500 in WebP si rigenerano dalla data del file.

## Rilascio

```powershell
$R = "C:\Users\danil\progetti\kos-seo-geo\repo"
git -C $R archive -o $env:TEMP\veio.tar HEAD extra/kos-aa-parco-di-veio
scp $env:TEMP\veio.tar kos-stage:
Start-Sleep 15
ssh kos-stage "rm -rf ~/veio && mkdir ~/veio && tar xf ~/veio.tar -C ~/veio && cd ~/veio/extra/kos-aa-parco-di-veio && php installa.php /www kos"
```

Poi lo stesso comando con `--scrivi`; poi produzione (`kos-prod`).

RSA (anniazzurri.it): prima le foto, poi il chunk.

```powershell
git -C $R archive -o $env:TEMP\veio-aa.tar HEAD extra/kos-aa-parco-di-veio scripts/deploy/installa-elementi.php anniazzurri/elements/chunks/gal-residenza-anni-azzurri-parco-di-veio.html anniazzurri/baseline/chunks/gal-residenza-anni-azzurri-parco-di-veio.html
scp $env:TEMP\veio-aa.tar aa-stage:
Start-Sleep 15
ssh aa-stage "rm -rf ~/veio && mkdir ~/veio && tar xf ~/veio-aa.tar -C ~/veio && cd ~/veio/extra/kos-aa-parco-di-veio && php installa.php /www aa"
ssh aa-stage "cd ~/veio && php scripts/deploy/installa-elementi.php /www extra/kos-aa-parco-di-veio/aa/gal.json"
```

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 2/11/2026 | stage KOS (c0044) | galleria del poliambulatorio (20 foto) e footer; verificato con Chrome: 20 file 200, contatore 20, footer 1920 × 645 | `php installa.php /www --ripristina /paas/c0044/backup-kos-seo-geo/20261102-141343-parco-di-veio/ripristino.json --scrivi` |
| 2/11/2026 | produzione KOS (c0095) | galleria del poliambulatorio (20 foto) e footer; verificato con Chrome: 20 file 200, contatore 20, footer 1920 × 645, foto vecchie 404 | `php installa.php /www --ripristina /paas/c0095/backup-kos-seo-geo/20261102-142725-parco-di-veio/ripristino.json --scrivi` |
| 2/11/2026 | stage AA (c0025) | 15 foto della RSA e chunk della galleria; verificato con Chrome: 15 file 200 con varianti 480-1600 JPG e WebP, 15 testi alternativi, riquadri desktop, carosello a 390 px, finestra con 15 foto, nessun riferimento alle foto vecchie | foto: `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261102-142928-parco-di-veio/ripristino.json --scrivi`; chunk: percorso non annotato |
| 2/11/2026 | produzione AA (c0072) | 15 foto della RSA e chunk della galleria; verificato con Chrome: 15 file e 60 varianti WebP 200, 15 testi alternativi, nessun riferimento alle foto vecchie, un solo JSON-LD, nessun tag MODX non elaborato | foto: `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261102-143928-parco-di-veio/ripristino.json --scrivi`; chunk: `php scripts/deploy/installa-elementi.php /www /paas/c0072/backup-kos-seo-geo/20261102-144207/ripristino.json --scrivi` |
| 2/11/2026 | stage AA (c0025) | testata della RSA; verificato: originale e 8 varianti rigenerate dopo lo svuotamento della cache | `php installa.php /www --ripristina /paas/c0025/backup-kos-seo-geo/20261102-145316-parco-di-veio/ripristino.json --scrivi` |
| 2/11/2026 | produzione AA (c0072) | testata della RSA; verificato: 8 varianti rigenerate (21:56), galleria nuova, un solo JSON-LD | `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261102-145556-parco-di-veio/ripristino.json --scrivi` |
| 2/11/2026 | stage AA (c0025) | chunk della galleria con caroselli senza `imagesLoaded` e tutte le foto differite | `php scripts/deploy/installa-elementi.php /www /paas/c0025/backup-kos-seo-geo/20261102-155103/ripristino.json --scrivi` |
| 2/11/2026 | produzione KOS (c0095) | foto ricompresse del poliambulatorio (01-esterno, 02-ingresso-ambulatori); verificato 2/11 con `curl -sI`: 01-esterno.jpg 200 Content-Length 114747, 02-ingresso-ambulatori.jpg 200 Content-Length 113522, identici ai file del pacchetto | `php installa.php /www --ripristina /paas/c0095/backup-kos-seo-geo/20261102-155340-parco-di-veio/ripristino.json --scrivi` |
| 2/11/2026 | produzione AA (c0072) | 5 foto esterne ricompresse; chunk della galleria con caroselli senza `imagesLoaded` e tutte le foto differite; verificato con conta-lazy (`scripts/collaudo/pdv-aa-prod`): nessun `-1200.jpg` e nessuna foto della galleria al caricamento, immagini al caricamento 0,2 MB mobile e 0,4 MB desktop (prima 2,3 MB), 33 lazy, KO 0 | foto: `php installa.php /www --ripristina /paas/c0072/backup-kos-seo-geo/20261102-155205-parco-di-veio/ripristino.json --scrivi`; chunk: `php scripts/deploy/installa-elementi.php /www /paas/c0072/backup-kos-seo-geo/20261102-155236/ripristino.json --scrivi` |

Nota: le immagini hanno `cache-control: max-age=15552000` (180 giorni). Con lo stesso nome (testata RSA, footer del
poliambulatorio) chi aveva già visto la pagina può vedere la versione vecchia fino alla scadenza della copia nel browser.
Rimedio proposto e rimandato (2/11, decisione di Danilo): data del file nell'indirizzo delle varianti dello snippet
`immagine` (`?v=`), come AssetVersion per CSS e JS; su kosgroup.com con KOS-2 C.
