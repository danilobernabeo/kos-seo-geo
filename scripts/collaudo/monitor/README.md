# Controlli dalla chat sulle prestazioni (TTFB e crawler)

Copiati il 31/10/2026 dalla cartella `C:\Users\danil\progetti\kosgroup\monitor` di Danilo, dove sono nati (chat sulle
prestazioni lato server e cache, 19-31/10). Da qui in avanti la copia di riferimento è questa: si lanciano da PowerShell,
dalla cartella `scripts\collaudo\monitor` del repository. I risultati (CSV) finiscono sul Desktop, fuori dal repository.

| File | Cosa controlla | Atteso |
| --- | --- | --- |
| `controlla-link-relativi.ps1` | link interni e immagini senza barra iniziale nelle pagine della sitemap (`-Sito`, `-Elenco` per un elenco di URI) | 0 pagine |
| `controlla-og-image.ps1` | anteprime social (og:image presente, raggiungibile, con misure) | tutte OK |
| `test-annidati-anniazzurri.ps1` | URL annidati inventati su anniazzurri.it: 404 da nginx | tutti OK (301 su `/chi-siamo/` atteso) |
| `misura-ttfb.ps1`, `monitor-notte.ps1` | misura del tempo di risposta, anche ripetuta di notte | confronto con le misure precedenti |
| `verifica-kosgroup.sql`, `verifica-anniazzurri.sql` | impostazioni e modifiche fatte in produzione dalla chat sulle prestazioni (da lanciare sul database) | ogni riga trovato = atteso |
| `gtm-web-vitals/` | tag Web Vitals da importare in Tag Manager con «Unisci» (vedi `LEGGIMI.md`) e query BigQuery | — |

Istruzioni complete: `docs/riferimenti/istruzioni-chat-prestazioni-20261031.md`; dettaglio degli interventi:
`docs/riferimenti/handoff-ttfb-20261031.md`.

Uso dopo ogni rilascio:

```powershell
cd $R\scripts\collaudo\monitor
powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1
powershell -ExecutionPolicy Bypass -File .\controlla-link-relativi.ps1 -Sito https://anniazzurri.it -Elenco .\uri-anniazzurri.csv
powershell -ExecutionPolicy Bypass -File .\controlla-og-image.ps1 -Sito https://kosgroup.com
powershell -ExecutionPolicy Bypass -File .\controlla-og-image.ps1 -Sito https://anniazzurri.it
powershell -ExecutionPolicy Bypass -File .\test-annidati-anniazzurri.ps1
```

`uri-anniazzurri.csv` non è nel repository: va rigenerato o copiato dalla cartella `monitor` di Danilo.
