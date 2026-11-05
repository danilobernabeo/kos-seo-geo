# Piano di deindicizzazione dei PDF esclusi (KOS-8 / AA-3)

Documento interno (Danilo e Claude), 5 novembre 2026.

## Punto di partenza

Dal 1/11/2026 i PDF che non sono nell'elenco da tenere rispondono con `X-Robots-Tag: noindex` (sezione PDF delle regole web,
`<sito>/files/web-rules/pdf-noindex.conf`). Google e Bing tolgono un PDF dall'indice solo dopo averlo riscansionato e letto
l'intestazione: per i PDF, scansionati di rado, possono servire mesi. Questo piano accelera la riscansione, toglie subito
dai risultati i casi più visibili e misura l'avanzamento.

Stato in Search Console (storico 14/6/2025-23/10/2026, `searchconsole_aa` e `searchconsole_kos` in BigQuery), PDF classificati
con la stessa logica delle regole web (`scripts/pdf/deindicizza-pdf.py`):

| | anniazzurri.it | kosgroup.com |
|---|---|---|
| PDF sul server (inventario del 5/11, curriculum esclusi) | 412 | 679 |
| PDF in noindex visti su Google | 135 | 263 |
| di cui ancora sul server | 87 | 217 |
| PDF in noindex mai visti su Google, sul server | 130 | 208 |
| URL nella sitemap di uscita (noindex presenti sul server) | 217 | 426 |
| impressioni negli ultimi 90 giorni (noindex) | 1.123 | 3.647 |
| impressioni negli ultimi 90 giorni (PDF indicizzabili, per confronto) | 34.860 | 41.353 |
| priorità A (≥ 100 impressioni in 90 giorni o ≥ 10 clic) | 9 | 13 |

I PDF in noindex pesano circa il 3% (AA) e l'8% (KOS) delle impressioni dei PDF: la deindicizzazione non toglie traffico
utile, salvo i casi da riesaminare sotto. I PDF in noindex che non sono più sul server (48 AA, 46 KOS) rispondono 404 e
Google li toglie da sé; i PDF in redirect (25 AA, 19 KOS, compresa la cartella di Beata Corte verso Corte Orero) non entrano
nella sitemap. Classificazione: regole della sezione PDF più i redirect dell'ultima istantanea delle regole di produzione.

## Decisioni sui PDF da riesaminare (5/11)

Criterio di Danilo: si indicizza ciò che risponde a ricerche degli utenti. Decisioni sui PDF in noindex con clic veri:

| Sito | PDF | Impressioni | Clic | Decisione |
|---|---|---|---|---|
| AA | `residenza-anni-azzurri-sacra-famiglia/Scheda AGED.pdf` | 2.627 | 425 | Escluso: vecchia scansione di un modulo |
| AA | `scheda-invio-e-valutazione-per-ricovero-in-unita-di-cure-intermedie.pdf` | 3.076 | 150 | Escluso: modulo regionale lombardo del 2022, non collegato da nessuna pagina |
| AA | `residenza-anni-azzurri-casa-argento/Contratto accoglienza Privato.pdf` e `… RSA.pdf` | 1.023, 620 | 133, 23 | Esclusi: vecchie scansioni |
| AA | `residenza-anni-azzurri-la-corte/PARM 2025.pdf` | 278 | 35 | Escluso |
| KOS | `villa-jolanda/gestione-liste-di-attesa.pdf` | 3.164 | 41 | Escluso: vecchia scansione |
| KOS | `societa-trasparente/kos-care-estratto-visura-camerale-…-2024.pdf` | 1.694 | 39 | Escluso; candidato allo strumento Rimozioni (nome di una persona nel file) |
| KOS | `societa-trasparente/kos-care-bilancio-2024.pdf` | 898 | 23 | Escluso (resta pubblicato nella pagina «Società trasparente») |
| KOS | `villa-margherita-vicenza-psichiatria/tirocini-in-area-psichiatrica.pdf` (era indicizzabile) | 128 | 2 | Passa a noindex: pubblico di tirocinanti, nessun clic utile (`ESCLUDI_DOPO` in `genera-piano-pdf.py`) |
| KOS | `ospedale-san-pancrazio/il-team.pdf`, `villa-azzurra/la-nostra-équipe-2026.pdf`; AA `santa-maria-in-chienti/Organizzazione.pdf` | 1.384, 204, 550 | 61, 5, 26 | Restano indicizzabili: rispondono a ricerche sui medici e sulla direzione delle strutture |

Regole web KOS con i tirocini in noindex: stage e produzione il 5/11 (`curl -I`: tirocini `X-Robots-Tag: noindex`, `il-team.pdf` senza intestazione, home 200); file completi `kosgroup/files/web-rules/stage-2026-11-05.conf` e `produzione-2026-11-05.conf`, ripristino con `produzione-2026-11-05-prima.conf`.

Proposte in attesa di conferma, già scritte nelle regole come righe commentate: 301 di `villa-azzurra/la-nostra-equipe-2025.pdf`
(non più sul server, 15 clic) verso l'edizione 2026; 410 per la carta e il pieghevole di Biarella (struttura uscita dal
gruppo, nessuna impressione da settembre 2025), insieme alla rimozione della regola `/rsa-biarella` dalle regole di produzione.

Sitemap definitive: `anniazzurri/files/sitemap/sitemap-pdf-noindex.xml` (217 URL), `kosgroup/files/sitemap/sitemap-pdf-noindex.xml`
(426 URL), pubblicate il 5/11 in `/www/sitemap-pdf-noindex.xml` su stage e produzione (`curl -I`: 200, `text/xml`).

Elenco completo, con tutti i PDF e le priorità: `deindicizzare-aa.csv`, `deindicizzare-kos.csv` (generati dallo script,
fuori dal repository).

## Passi

| # | Cosa | Chi | Quando |
|---|---|---|---|
| 1 | Inventario dei PDF presenti sui due server (la sitemap deve contenere solo file che esistono: un 404 in sitemap è un errore) | D | **fatto il 5/11** |
| 2 | Decisione sui PDF da riesaminare; quelli da tenere passano nell'elenco delle regole web (`genera-piano-pdf.py`) | D + C | **fatto il 5/11** |
| 3 | `sitemap-pdf-noindex.xml` per sito (PDF in noindex presenti sul server, anche mai visti su Google), caricata nella webroot | C, D | **fatto il 5/11** |
| 4 | Invio della sitemap in Search Console e in Bing Webmaster Tools | D | 6/11 |
| 5 | Strumento «Rimozioni» di Search Console per i PDF con dati personali, documenti superati o errati con impressioni recenti (priorità A, dopo il passo 2): li nasconde per circa 6 mesi, il tempo che il noindex agisca | D | 6/11 |
| 6 | Misura settimanale (lunedì): storico di Search Console aggiornato e script rilanciato; report «Indicizzazione delle pagine» filtrato sulla sitemap (pagine «Escluse dal tag noindex») | C | dal 9/11 |
| 7 | Quando un PDF non ha impressioni da 4 settimane ed è segnato «Escluso dal tag noindex», esce dalla sitemap; a sitemap vuota la si toglie da Search Console e dal server | C, D | da dicembre |
| 8 | Fase finale chiesta dall'audit AA: `rel="nofollow"` sui link ai PDF esclusi e blocco in `robots.txt`, solo per i percorsi già usciti dall'indice (prima Google deve poter leggere il noindex) | C | dopo la deindicizzazione, prevedibilmente nel primo trimestre 2027 |

Avvertenze:
- In Search Console la sitemap risulterà con URL «inviati ma con noindex»: è l'effetto voluto, non un errore da correggere.
- La sitemap non va dichiarata in `robots.txt` (non serve, e non va confusa con la sitemap del sito).
- I curriculum (`/assets/doc/cv/`) non entrano nella sitemap né nei file di lavoro: hanno già `noindex, nofollow`.
- I PDF in redirect (25 AA, 19 KOS) non entrano nella sitemap: Google li toglie da sé quando segue il 301.
- Le sitemap sono file nuovi, non elementi dello sprint 1: possono uscire anche nella settimana di verifica 9-14/11.

## Comandi

Inventario (uno per sito, da PowerShell; i percorsi dei curriculum non escono dal server):

```powershell
ssh aa-prod "cd /www && find . -type f -iname '*.pdf' | grep -v '^./assets/doc/cv/'" > $env:TEMP\pdf-aa.txt
ssh kos-prod "cd /www && find . -type f -iname '*.pdf' | grep -v '^./assets/doc/cv/'" > $env:TEMP\pdf-kos.txt
```

Generazione (Claude, dai due inventari e dalla query di Search Console indicata nello script):

```
python3 scripts/pdf/deindicizza-pdf.py --gsc gsc-pdf.json --inventario-aa pdf-aa.txt --inventario-kos pdf-kos.txt --uscita <cartella>
```
