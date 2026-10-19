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

Il nodo delle residenze (LocalBusiness/NursingHome) non è in questo rilascio: arriva con le schede
struttura (voci 2, 5, 11, 21, sprint 3).

## Installazione (prima sullo stage)

1. **Impostazioni di sistema**, namespace `aa` (Sistema → Impostazioni → Crea nuova):

   | Chiave | Valore |
   |---|---|
   | `aa.canonical_base` | `https://anniazzurri.it` |
   | `aa.breadcrumb_exclude` | `2,4,48,54,55,68,78,85,93,97,98,100,109,156` |
   | `aa.org_sameAs` | `https://www.facebook.com/ResidenzeAnniAzzurri,https://www.instagram.com/anniazzurri.perte,https://www.youtube.com/user/ResidenzeAnniAzzurri` |
   | `aa.org_phone` | `+39 800 131 851` (numero verde, da confermare) |

   Il canale YouTube `@KOSGroupIT` è di KOS S.p.A.: non va nel `sameAs` di KOS Care.
   Tutte le altre impostazioni hanno un valore predefinito nello snippet (vedi intestazione del file).

2. **Snippet** `aaSchema`: elemento statico da `anniazzurri/elements/snippets/aaSchema.php`
   (oppure incollare il codice senza `<?php`).

3. **Chunk** (versioni in `anniazzurri/elements/chunks/`):
   - `footer-script`, `footer-script-regione`, `footer-script-provincia`, `footer-script-form-registrazione`:
     i tre `<script type="application/ld+json">` sostituiti da `[[aaSchema]]`;
   - `breadcrumb-stripe`: `&exclude=` letto da `[[++aa.breadcrumb_exclude]]`;
   - `schema-525`, `schema-lrsa-volpiano`, `schema-assunzione-assistenti-infermieristici`: tolta la
     BreadcrumbList (stesso `@id` di quella di `aaSchema`), `provider` e `hiringOrganization`
     collegati all'Organization con `@id`.

   Tre template non usano i `footer-script` (`BaseTemplate`, `convegnoAA`, `recruiting`): da verificare
   se hanno pagine pubblicate; se sì, aggiungere `[[aaSchema]]`.

4. Svuotare la cache.

## Collaudo

Su home, una regione, una provincia, una residenza, un articolo, la pagina 525 e una landing:

```powershell
$u = "https://anniazzurri.it/residenza-anni-azzurri-vico-mercati"
(curl.exe -s $u | Select-String -Pattern 'application/ld\+json' -AllMatches).Matches.Count   # atteso: 1 (2 sulle pagine con schema-*)
```

Poi Schema Markup Validator e Rich Results Test (briciole), e confronto a occhio briciole visibili / JSON-LD.
