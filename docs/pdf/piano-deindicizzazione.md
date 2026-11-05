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
| PDF in noindex visti su Google | 135 | 276 |
| di cui con impressioni dal 1/10 | 27 | 77 |
| impressioni negli ultimi 90 giorni (noindex) | 1.123 | 3.913 |
| impressioni negli ultimi 90 giorni (PDF indicizzabili, per confronto) | 34.860 | 41.353 |
| priorità A (≥ 100 impressioni in 90 giorni o ≥ 10 clic) | 9 | 16 |

I PDF in noindex pesano circa il 3% (AA) e il 9% (KOS) delle impressioni dei PDF: la deindicizzazione non toglie traffico
utile, salvo i casi da riesaminare sotto.

## Prima di partire: 10 PDF da riesaminare

Criterio di Danilo (5/11): si indicizza ciò che risponde a ricerche degli utenti. Questi PDF in noindex hanno clic veri,
cioè persone che li cercano e li aprono: vanno confermati uno per uno prima di metterli nella sitemap di uscita.

| Sito | PDF | Impressioni | Clic | Proposta |
|---|---|---|---|---|
| AA | `residenza-anni-azzurri-sacra-famiglia/Scheda AGED.pdf` | 2.627 | 425 | Probabile scheda per l'ingresso in RSA: se è un modulo di accesso, tenere (come i questionari di ammissione già indicizzabili) |
| AA | `scheda-invio-e-valutazione-per-ricovero-in-unita-di-cure-intermedie.pdf` | 3.076 | 150 | Modulo di accesso: tenere |
| AA | `residenza-anni-azzurri-casa-argento/Contratto accoglienza Privato.pdf` | 1.023 | 133 | Da decidere con KOS: un contratto in chiaro nei risultati è una scelta commerciale |
| AA | `residenza-anni-azzurri-casa-argento/Contratto accoglienza RSA.pdf` | 620 | 23 | Come sopra |
| AA | `residenza-anni-azzurri-la-corte/PARM 2025.pdf` | 278 | 35 | Escludere (documento di obbligo; verificare se c'è già l'edizione 2026) |
| KOS | `villa-jolanda/gestione-liste-di-attesa.pdf` | 3.164 | 41 | Tenere: informazione che gli utenti cercano |
| KOS | `beata-corte/regolamento-comunita.pdf`, `carta-dei-servizi.pdf` | 6.765, 1.810 | 56, 42 | Escludere (struttura in 301, cartella da archiviare) |
| KOS | `societa-trasparente/kos-care-estratto-visura-camerale-…-2024.pdf` | 1.694 | 39 | Escludere e chiedere la rimozione: il nome del file contiene il nome di una persona |
| KOS | `societa-trasparente/kos-care-bilancio-2024.pdf` | 898 | 23 | Escludere (resta pubblicato nella pagina «Società trasparente») |

Elenco completo, con tutti i PDF e le priorità: `deindicizzare-aa.csv`, `deindicizzare-kos.csv` (generati dallo script,
fuori dal repository).

## Passi

| # | Cosa | Chi | Quando |
|---|---|---|---|
| 1 | Inventario dei PDF presenti sui due server (la sitemap deve contenere solo file che esistono: un 404 in sitemap è un errore) | D | 5/11 |
| 2 | Decisione sui 10 PDF da riesaminare; quelli da tenere passano nell'elenco delle regole web (`genera-piano-pdf.py`) | D + C | 5/11 |
| 3 | `sitemap-pdf-noindex.xml` per sito (PDF in noindex presenti sul server, anche mai visti su Google), caricata nella webroot | C, D | 6/11 |
| 4 | Invio della sitemap in Search Console e in Bing Webmaster Tools | D | 6/11 |
| 5 | Strumento «Rimozioni» di Search Console per i PDF con dati personali, documenti superati o errati con impressioni recenti (priorità A, dopo il passo 2): li nasconde per circa 6 mesi, il tempo che il noindex agisca | D | 6/11 |
| 6 | Misura settimanale (lunedì): storico di Search Console aggiornato e script rilanciato; report «Indicizzazione delle pagine» filtrato sulla sitemap (pagine «Escluse dal tag noindex») | C | dal 9/11 |
| 7 | Quando un PDF non ha impressioni da 4 settimane ed è segnato «Escluso dal tag noindex», esce dalla sitemap; a sitemap vuota la si toglie da Search Console e dal server | C, D | da dicembre |
| 8 | Fase finale chiesta dall'audit AA: `rel="nofollow"` sui link ai PDF esclusi e blocco in `robots.txt`, solo per i percorsi già usciti dall'indice (prima Google deve poter leggere il noindex) | C | dopo la deindicizzazione, prevedibilmente nel primo trimestre 2027 |

Avvertenze:
- In Search Console la sitemap risulterà con URL «inviati ma con noindex»: è l'effetto voluto, non un errore da correggere.
- La sitemap non va dichiarata in `robots.txt` (non serve, e non va confusa con la sitemap del sito).
- I curriculum (`/assets/doc/cv/`) non entrano nella sitemap né nei file di lavoro: hanno già `noindex, nofollow`.
- I PDF in redirect (25 AA, 6 KOS) non entrano nella sitemap: Google li toglie da sé quando segue il 301.
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
