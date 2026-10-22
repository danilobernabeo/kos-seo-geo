# AA-8 — Dato strutturato unico di anniazzurri.it (sprint 1)

Un solo `@graph` JSON-LD per pagina, emesso lato server dallo snippet `aaSchema`, al posto dei tre
blocchi scritti a mano nei chunk `footer-script*` (Organization senza `@id` con logo a doppia barra,
WebSite minimo, BreadcrumbList con una sola voce).

## Cosa esce

| Pagina | Nodi |
|---|---|
| Home | WebPage (about → Organization), WebSite, Organization |
| Regione, provincia, residenze, news, indice storie | CollectionPage, BreadcrumbList, WebSite, Organization |
| Altre pagine (residenze comprese) | WebPage, BreadcrumbList, WebSite, Organization |
| Articolo (template `interna-articolo`) | Article, WebPage/MedicalWebPage, Person (se firmato), BreadcrumbList, WebSite, Organization |
| Pagina autore (template `autore`, da creare) | ProfilePage, Person, BreadcrumbList, WebSite, Organization |

Le briciole seguono le stesse regole dello snippet `BreadCrumb` (albero MODX, esclusioni da
`aa.breadcrumb_exclude`, pagetitle per i livelli intermedi, menutitle o pagetitle per la pagina corrente).
Esempio verificato con MODX simulato su Vico Mercati: identico a quello della slide 35 dell'audit.

Il nodo delle residenze (LocalBusiness, tipo da definire) non è in questo rilascio: arriva con le schede
struttura (voci 2, 5, 11, 21, sprint 3).

## Installazione (prima sullo stage)

Con lo script `scripts/deploy/installa-elementi.php` e il manifest `anniazzurri/deploy/aa-8.json`
(impostazioni `aa.*`, snippet `aaSchema`, chunk `breadcrumb-stripe`, `footer-script*`, `schema-*`,
plugin `PaginationCanonical` riattivato). Lo script:
- prima prova senza scrivere, poi applica con `--scrivi`;
- si ferma se un elemento sul sito è cambiato rispetto alla baseline (modifica fatta dopo l'istantanea);
- salva gli elementi sostituiti e un manifest di ripristino in `~/backup-kos-seo-geo/<data>/` sul cloud;
- svuota la cache.

Da PowerShell, nella cartella del repository:

```powershell
git pull
git archive -o $env:TEMPa8.tar HEAD anniazzurri/elements anniazzurri/baseline anniazzurri/deploy scripts/deploy
scp $env:TEMP\aa8.tar aa-stage:~/
ssh aa-stage "rm -rf ~/kos-seo-geo && mkdir -p ~/kos-seo-geo && tar xf ~/aa8.tar -C ~/kos-seo-geo && cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-8.json"
ssh aa-stage "cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-8.json --scrivi"
```

Ripristino: `cd ~/kos-seo-geo && php scripts/deploy/installa-elementi.php /www ~/backup-kos-seo-geo/<data>/ripristino.json --scrivi`

`aa.org_phone` = `800 131 851` (numero verde: senza +39, che dall'Italia non funziona; confermato da Danilo).
Lo Sportello sociale (risorsa 525) usa la head `head-sportello-sociale`, che non includeva `schema-525`: aggiunta l'inclusione, come nella head generica.
Tre template non usano i `footer-script` (`BaseTemplate`, `convegnoAA`, `recruiting`): da verificare
se hanno pagine pubblicate; se sì, aggiungere `[[aaSchema]]`.

## Collaudo

Su home, una regione, una provincia, una residenza, un articolo, la pagina 525 e una landing:

```powershell
$u = "https://anniazzurri.it/residenza-anni-azzurri-vico-mercati"
(curl.exe -s $u | Select-String -Pattern 'application/ld\+json' -AllMatches).Matches.Count   # atteso: 1 (2 sulle pagine con schema-*)
```

Poi Schema Markup Validator e Rich Results Test (briciole), e confronto a occhio briciole visibili / JSON-LD.

## Rilascio in produzione

1. Snapshot del cloud di produzione dal pannello MODX Cloud.
2. Stessi comandi dello stage con `aa-prod`: prima la prova (deve dire "Nessun problema": se un elemento
   è cambiato dopo la baseline lo script si ferma), poi `--scrivi`.
3. Verifiche dagli URL pubblici: numero di blocchi JSON-LD (1; 2 sullo Sportello sociale; 3 su lrsa-volpiano),
   briciole visibili e JSON-LD su Vico Mercati, Rich Results Test e validator.schema.org da URL su home,
   una regione, una provincia, una residenza, un articolo, lo Sportello sociale.
4. Ripristino se serve: `php scripts/deploy/installa-elementi.php /www ~/backup-kos-seo-geo/<data>/ripristino.json --scrivi`.
