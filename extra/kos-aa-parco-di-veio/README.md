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

## Gallerie (da fare)

Proposta del 2/11: 20 foto per il poliambulatorio (copertina: esterno, scatto 7209). Risposta di KOS del 2/11: palestra
e ingresso/accettazione (bancone blu nella hall) sono **solo del poliambulatorio**; anche il corridoio con le porte rosse e
la foto in carrozzina sono nella zona della palestra. RSA: esterni, soggiorno, sala, camere, bagno (13 foto); da confermare
l'ingresso con la pensilina (7277) e le porte vetrate sul giardino (7320).
Prima di spostare le foto vecchie, `installa.php` cerca i loro nomi in contenuti, TV, chunk e template: se qualcuno le
cita ancora si ferma (`--forza` per procedere comunque).

- Poliambulatorio (pronta, `kos/files/assets/img/strutture/poliambulatorio-parco-di-veio/`, 20 file, 2 MB): 1280 × 853, JPG 80, prefisso numerico (ordine) e nome descrittivo; le 18 foto attuali escono dalla
  cartella (backup), `kos/svuota.txt`.
- RSA: sorgenti a 1600 × 1067 (le varianti 480-1600 le crea `immagine`), chunk riscritto con i nomi nuovi e testi
  alternativi descrittivi. Il chunk è un elemento del progetto (AA-1, sprint 1): uscita entro il 6/11 oppure dopo il 14/11.

## Rilascio

```powershell
$R = "C:\Users\danil\progetti\kos-seo-geo\repo"
git -C $R archive -o $env:TEMP\veio.tar HEAD extra/kos-aa-parco-di-veio
scp $env:TEMP\veio.tar kos-stage:
Start-Sleep 15
ssh kos-stage "rm -rf ~/veio && mkdir ~/veio && tar xf ~/veio.tar -C ~/veio && cd ~/veio/extra/kos-aa-parco-di-veio && php installa.php /www kos"
```

Poi lo stesso comando con `--scrivi`; poi produzione (`kos-prod`).

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
