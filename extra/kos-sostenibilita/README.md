# Pagina Sostenibilità di kosgroup.com (fuori dal progetto SEO/GEO)

Lavoro esterno al progetto: grafici HTML al posto delle immagini sulla pagina Sostenibilità
(risorsa 54 `/it/chi-siamo/governance/sostenibilita`, template `generica`; risorsa 980
`/en/who-we-are/governance/sustainability`, template `generica-en`). Usa gli stessi strumenti di rilascio
del repository, ma non è una voce d'audit e non compare nella documentazione del progetto.

## Contenuto

| Percorso | Cosa |
|---|---|
| `originale/` | istruzioni e configurazioni TV del pacchetto preparato nell'altra chat (riferimento) |
| `files/assets/` | CSS, JS e immagini da copiare in `/www/assets/` (tutti nuovi) |
| `elementi/` | 3 snippet, 8 chunk nuovi e i 2 chunk esistenti modificati |
| `tv.json`, `dati.json` | TV MIGX e dati iniziali; in `dati.json` le conferme richieste a KOS |
| `baseline/` | contenuti di produzione del 20/10 (pagine 54 e 980, chunk `grafico-dipendenti` ed `employee-chart`) |
| `installa.php` | installazione con prova, backup e ripristino |
| `ispeziona.php` | lettura dello stato della produzione |

## Differenze rispetto alle istruzioni dell'altra chat

1. **Box dei dipendenti**: in produzione è nel chunk `grafico-dipendenti` (EN `employee-chart`), che apre anche
   `<div class="row infografiche-container">`. Si modifica il chunk tenendo l'apertura, invece di sostituire la
   chiamata nella pagina (che toglieva l'apertura della riga: il "box che esce dalla riga" visto in test).
2. **CSS e JS solo sulla pagina**: niente modifiche ai template `generica`/`generica-en`, condivisi da molte pagine.
   Lo snippet `kosSostenibilitaRisorse`, richiamato dai chunk, registra i file (CSS prima di `</head>`, JS prima di
   `</body>`); MODX li inserisce una volta sola.
3. **Niente `?v=` scritto a mano**: lo aggiunge il plugin AssetVersion con la data del file.
4. **Numeri nell'HTML servito**: gli snippet scrivono anche la tabella di dipendenti e ricavi
   (`&output=`table``), leggibile da crawler e assistenti AI che non eseguono JavaScript; lo script la
   sostituisce con la sua versione per scheda.
5. **Pagina EN**: `cultureKey` è `it` in tutto il sito (un solo contesto `web`), quindi si usano i nomi espliciti
   `_en`, non `[[$kosDipendenti_[[++cultureKey]]]]`.
6. **Nessun intervento a mano nel manager**: le pagine si modificano da script (anche per evitare che TinyMCE
   riformatti il contenuto), con controllo che siano uguali alla copia del 20/10.

## Rilascio

Prima sullo stage (con `--dati-provvisori`, perché i dati non sono ancora confermati), poi in produzione
con `dati.json` aggiornato e tutte le conferme a `true`.

```powershell
git pull
git archive -o $env:TEMP\sost.tar HEAD extra/kos-sostenibilita
scp $env:TEMP\sost.tar kos-stage:
Start-Sleep 20
ssh kos-stage "rm -rf ~/sost && mkdir ~/sost && tar xf ~/sost.tar -C ~/sost && cd ~/sost/extra/kos-sostenibilita && php installa.php /www"
Start-Sleep 20
ssh kos-stage "cd ~/sost/extra/kos-sostenibilita && php installa.php /www --scrivi --dati-provvisori"
```

Ripristino: il comando esatto è stampato alla fine dell'installazione
(`php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-sostenibilita/ripristino.json --scrivi`).

## Verifiche dopo il rilascio

- Console del browser (F12) sulle due pagine: lo script del § 6.1 di `originale/ISTRUZIONI-PRODUZIONE.md`.
- Sorgente (Ctrl+U): CSS `kos-*.css?v=` nell'head, JS `kos-*.js?v=` prima di `</body>`, tabelle con i numeri.
- Pagina EN: testi in inglese, numeri con la virgola, ricavi come €780M.

## Da segnalare a KOS (non bloccanti)

Classificazione delle strutture confermata dal Bilancio 2025: 144 = 110 RSA (57 Italia + 53 Germania) + 33 riabilitazione,
cure psichiatriche e assistenza non residenziale + 1 acuti (Sanatrix); 91 in Italia; 15 centri ambulatoriali a parte;
13.722 posti letto.

- Dipendenti Germania 2025: chiarito dal Bilancio 2025, i 6 di differenza sono 2 «altro» e 4 «non segnalato».
- Bilancio 2025, pag. 74: la tabella «per tipologia contrattuale e genere al 31 dicembre 2025» riporta i numeri
  del 2024 (totale 11.714 invece di 11.770). Da segnalare a KOS: non tocca la pagina del sito.

- Pagina 54: `[[!ShowEditStatus? … &date_format`%B %Y`]]` senza `=` (formato della data ignorato); non toccato
  perché cambierebbe la data mostrata e `%B` dipende da `strftime`, deprecata in PHP 8.
- Contrasto colori e icone ritagliate da screenshot: vedi § 10 delle istruzioni originali.

## Rilasci

| Data | Ambiente | Esito | Ripristino |
|---|---|---|---|
| 20/10/2026 | stage (c0044) | dati provvisori, poi ripristinata e reinstallata con i dati confermati | `~/backup-kos-seo-geo/20261020-071502-sostenibilita/ripristino.json` |
| 20/10/2026 | produzione (c0095) | installata, ripristino provato a vuoto | `~/backup-kos-seo-geo/20261020-073409-sostenibilita/ripristino.json` |
| 20/10/2026 | stage e produzione | correzione dei difetti HTML preesistenti delle pagine 54 e 980 (`correggi-html.php`) | `php correggi-html.php /www --ripristina --scrivi` |

La baseline del progetto (`kosgroup/baseline/chunks/grafico-dipendenti.html`, `employee-chart.html`) è stata
aggiornata al contenuto pubblicato, così gli strumenti del progetto non segnalano questi chunk come modificati a mano.
