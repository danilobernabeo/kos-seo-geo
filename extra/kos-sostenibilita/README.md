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

- Pagina 54: in fondo al contenuto c'è un `>` in più (`</div>>`), visibile come carattere sulla pagina, e
  l'ultima `section` non è chiusa.
- Pagina 980: la `section` dei grafici si chiude con un `div` ancora aperto; immagini con percorsi relativi
  (`assets/...` senza `/`). Il contenuto è passato da TinyMCE. Difetti preesistenti: l'installazione sostituisce
  solo i tre box, che sono bilanciati, e non li peggiora.
- Contrasto colori e icone ritagliate da screenshot: vedi § 10 delle istruzioni originali.
