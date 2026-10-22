# Guida: riallineare lo stage dalla produzione e installare un pacchetto

Procedura usata il 22/10/2026 sullo stage di kosgroup.com, prima del pacchetto KOS 9/12/13 (sprint 2). Vale per
entrambi i siti: cambiano solo i nomi della tabella qui sotto.

## Quando farlo

- All'inizio di ogni sprint, prima di installare i nuovi pacchetti sullo stage.
- Quando sullo stage serve il contenuto aggiornato della produzione (risorse, chunk, snippet modificati dalla
  redazione o da altri fornitori).
- Mai nel senso opposto: il database dello stage non si copia in produzione. Lo stage collauda il codice, non i
  contenuti.

## Nomi da usare

| | kosgroup.com | anniazzurri.it |
|---|---|---|
| Alias SSH produzione | `kos-prod` (utente `c0095`) | `aa-prod` (utente `c0072`) |
| Alias SSH stage | `kos-stage` (utente `c0044`) | `aa-stage` (utente `c0025`) |
| Indirizzo stage | `https://b0k2gmat.modx.dev` | `https://tcm7b0i8.modx.dev` |
| Cartella baseline | `kosgroup/baseline/` | `anniazzurri/baseline/` |

Tutti i cloud hanno lo stesso indirizzo IP: connessioni SSH ravvicinate vengono interrotte («connection reset»).
Tra un comando `ssh`/`scp` e il successivo lasciare `Start-Sleep 15`.

I comandi si lanciano da PowerShell, nella cartella locale del repository. Gli esempi usano kosgroup.com.

## Passo 1 — Salvare la password dello stage

L'istantanea della produzione sovrascrive anche `/www/core/.htpasswd`, il file della protezione con password
dello stage. Prima va copiato fuori da `/www`:

```powershell
ssh kos-stage "cp /www/core/.htpasswd /home/htpasswd.bak && ls -la /home/htpasswd.bak && head -c 10 /home/htpasswd.bak"
```

Atteso: il file esiste (circa 110 byte) e comincia con `kos:$6$`.

## Passo 2 — Caricare l'istantanea della produzione sullo stage

Dal pannello di MODX Cloud:

1. sul cloud di produzione, creare un'istantanea (snapshot) aggiornata;
2. sul cloud di stage, caricare quell'istantanea;
3. attendere che il caricamento sia completato.

Dopo questo passo lo stage è una copia della produzione: database, `/www` e quindi anche gli elementi già
pubblicati dei pacchetti del progetto e dei lavori extra.

## Passo 3 — Ripristinare la password e verificare la protezione

```powershell
ssh kos-stage "cp /home/htpasswd.bak /www/core/.htpasswd && ls -la /www/core/.htpasswd"
Start-Sleep 15
curl.exe -sI https://b0k2gmat.modx.dev/ | Select-String "HTTP/|WWW-Authenticate|X-Robots-Tag"
curl.exe -sI -u kos https://b0k2gmat.modx.dev/ | Select-String "HTTP/|X-Robots-Tag"
```

Atteso:

- senza password: `401`, `WWW-Authenticate: Basic realm="Ambiente di prova KOS"`, `X-Robots-Tag: noindex, nofollow`;
- con password: `200`, `X-Robots-Tag: noindex, nofollow`.

Se la risposta senza password è `200`, fermarsi: lo stage è esposto. Ricontrollare il file e le regole web del
pannello (il percorso del file per nginx è `/c0044/www/core/.htpasswd`).

## Passo 4 — Riesportare la baseline dalla produzione

Lo script è in sola lettura: fotografa template, chunk, snippet, plugin, TV e impostazioni.

```powershell
git pull
scp scripts/export-elements.php kos-prod:
Start-Sleep 15
ssh kos-prod "rm -rf ~/export-elements && php export-elements.php /www ~/export-elements && cd ~ && tar czf export-elements-kos.tgz export-elements && ls -la export-elements-kos.tgz"
Start-Sleep 15
scp kos-prod:export-elements-kos.tgz .
```

Poi, nella sessione di lavoro con Claude, si carica il file `.tgz` e si confronta con la baseline del
repository:

- gli elementi del pacchetto da installare devono essere uguali alla baseline; se sono cambiati, la modifica
  fatta in produzione va integrata nel repository prima di installare;
- le altre differenze (elementi nuovi o modificati da altri) entrano nella baseline;
- i segreti lato server (token Arca24 in `arca24Import`, chiave YouTube in `syncYoutubeVideos`) si sostituiscono
  con il segnaposto prima del commit (vedi `README.md` della baseline);
- le differenze solo di fine riga si scartano;
- commit della baseline aggiornata, con la data nel `README.md` della baseline.

Il file `.tgz` resta nella cartella locale: non va nel repository.

## Passo 5 — Installare il pacchetto in prova

L'installer senza `--scrivi` mostra cosa cambierebbe e non scrive nulla. Eventuali file statici (es. il logo) si
caricano prima.

```powershell
git pull
git archive -o $env:TEMP\kos2.tar HEAD shared kosgroup/elements kosgroup/baseline kosgroup/deploy scripts/deploy
scp $env:TEMP\kos2.tar kos-stage:
Start-Sleep 15
ssh kos-stage "rm -rf ~/kos-seo-geo && mkdir -p ~/kos-seo-geo && tar xf ~/kos2.tar -C ~/kos-seo-geo && cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www kosgroup/deploy/<manifest>.json"
```

Legenda dell'output:

| Riga | Significato |
|---|---|
| `+` | impostazione o elemento nuovo |
| `~` | elemento o impostazione che verrà modificato |
| `=` | già come nel repository, non viene toccato |
| `!` ... `FERMO` | l'elemento sul sito è diverso dalla baseline: qualcuno l'ha modificato. Non usare `--forza` senza aver capito perché |

Si prosegue solo con `== Nessun problema.` in fondo.

## Passo 6 — Applicare e collaudare

```powershell
ssh kos-stage "cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www kosgroup/deploy/<manifest>.json --scrivi"
```

L'output finisce con `Backup e manifest di ripristino in /paas/c0044/backup-kos-seo-geo/<data>/`: annotare il
percorso. Per tornare indietro:

```powershell
ssh kos-stage "cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www /paas/c0044/backup-kos-seo-geo/<data>/ripristino.json --scrivi"
```

Se lo stesso pacchetto viene reinstallato più volte (es. dopo una correzione), ogni installazione ha il suo
manifest: per annullare tutto si usa quello della prima.

Collaudo per i pacchetti di dato strutturato:

1. **Controllo da PowerShell** — conta i blocchi JSON-LD, elenca i tipi e legge le briciole visibili:

   ```powershell
   $c = Get-Credential kos
   $pagine = "/it", "/en", "/it/centri/poliambulatorio-valdaso"   # un indirizzo per template
   foreach ($p in $pagine) {
     try { $h = (Invoke-WebRequest "https://b0k2gmat.modx.dev$p" -Credential $c -UseBasicParsing).Content }
     catch { "{0}`n   ERRORE {1}" -f $p, $_.Exception.Message; continue }
     $ld = ([regex]::Matches($h, 'application/ld\+json')).Count
     $tipi = ([regex]::Matches($h, '"@type":"(\w+)"') | ForEach-Object { $_.Groups[1].Value } | Select-Object -Unique) -join ","
     $bc = [regex]::Match($h, '(?s)<nav[^>]*id="breadcrumbs".*?</nav>').Value -replace '<[^>]+>', '' -replace '\s+', ' '
     "{0}`n   JSON-LD: {1}  tipi: {2}`n   briciole: {3}" -f $p, $ld, $tipi, $(if ($bc) { $bc.Trim() } else { "-" })
     Start-Sleep 3
   }
   ```

   Atteso: un solo JSON-LD per pagina; dove si vedono le briciole c'è anche `BreadcrumbList`, con lo stesso
   percorso. Gli indirizzi di esempio per ogni template si ricavano da `scripts/checks/kos-albero.php`. Se le
   briciole si vedono ma manca la `BreadcrumbList`, `scripts/checks/kos-briciole-origine.php` dice da dove
   escono.
2. **Controllo nel browser** (lo stage chiede la password): aspetto delle pagine toccate dal pacchetto e home in
   una finestra anonima. Sullo stage il banner Cookiebot non compare (dominio diverso da quello registrato):
   il controllo «senza consenso» si fa in produzione, subito dopo il rilascio.
3. **Validatori** — lo stage è protetto, quindi si usa la modalità «codice»: `Ctrl+U` sulla pagina, copiare il
   sorgente e incollarlo in [validator.schema.org](https://validator.schema.org) e nella scheda *Code* di
   [Rich Results Test](https://search.google.com/test/rich-results).
4. **Script di terzi** — il Rich Results Test esegue il JavaScript, quindi vede anche il JSON-LD inserito da
   script esterni (es. Yext Knowledge Tags sulle schede struttura). Se trova più elementi di quelli attesi,
   confrontare con il numero di blocchi arrivati dal server (controllo da PowerShell, punto 1).

## Dopo il riallineamento

- I pacchetti già pubblicati in produzione sono arrivati con l'istantanea: non vanno reinstallati.
- I pacchetti ancora in collaudo (del progetto o dei lavori extra in `extra/`) si reinstallano con i loro script,
  ripartendo dal passo 5.
- Il registro dell'installer (`~/backup-kos-seo-geo/installati.json`) e i backup sono fuori da `/www`; per
  sicurezza, prima di un ripristino controllare che la cartella del backup esista ancora
  (`ssh kos-stage "ls ~/backup-kos-seo-geo"`).
