# Convenzione degli identificativi delle entità

Sprint 1, linea A. Vale per kosgroup.com e anniazzurri.it. Da condividere con Cerved/ProWeb.

## Principio

Ogni entità descritta nel dato strutturato ha un identificativo (`@id`) stabile, formato dall'**URL canonico della pagina** che la descrive più un **frammento fisso** per tipo di entità. Le altre pagine richiamano l'entità con lo stesso `@id` invece di descriverla di nuovo.

Gli identificativi:
- sono generati da un'unica funzione, mai scritti a mano;
- si basano sugli indirizzi del sito, non sugli identificativi di Yext o RetailTune: non cambiano con il passaggio di piattaforma;
- sono identici in tutte le pagine e su entrambi i siti quando si riferiscono alla stessa entità.

## Forma canonica dei domini

| Sito | Base | Note |
|---|---|---|
| anniazzurri.it | `https://anniazzurri.it` | senza www; home con slash finale, pagine interne senza |
| kosgroup.com | `https://kosgroup.com` | senza www; pagine sotto il prefisso di lingua `/it` |

## Identificativi

| Entità | Tipo schema.org | `@id` | Esempio |
|---|---|---|---|
| Ente anniazzurri.it | `Organization` | `{base}/#organization` | `https://anniazzurri.it/#organization` (KOS Care S.r.l.) |
| Ente kosgroup.com | `Organization` | `{base}/#organization` | `https://kosgroup.com/#organization` (KOS S.p.A.) — alla radice, non sotto `/it` |
| Sito | `WebSite` | `{base}/#website` | `https://anniazzurri.it/#website` |
| Briciole | `BreadcrumbList` | `{URL}#breadcrumb` | `https://anniazzurri.it/residenza-anni-azzurri-mestre#breadcrumb` |
| Struttura / residenza | `LocalBusiness` e sottotipi | `{URL scheda}#localbusiness` | `https://anniazzurri.it/residenza-anni-azzurri-navigli#localbusiness` |
| Medico | `Physician` | `{URL scheda}#medico` | `https://kosgroup.com/it/.../nome-cognome#medico` |
| Articolo | `Article` | `{URL}#article` | `https://anniazzurri.it/come-vivono-gli-anziani-in-rsa#article` |
| Autore | `Person` | `{URL pagina autore}#person` | `https://anniazzurri.it/autori/antonella-schiavino#person` |

**Strutture: `#localbusiness`** (deciso il 20 ottobre 2026, per entrambi i siti), come indicato dall'audit Anni Azzurri (slide 29-30). Il frammento è solo un identificativo: né schema.org né Google prescrivono una forma, contano la stabilità e l'uso coerente in tutte le pagine. Il nome resta corretto qualunque sia il tipo. Tipo delle residenze (deciso il 29 ottobre 2026): schema.org non ha un tipo «casa di riposo» (`NursingHome` non esiste, verificato su schema.org 30.1), quindi `["MedicalBusiness", "MedicalOrganization"]` con `medicalSpecialty` `Geriatric`: `MedicalBusiness` è un sottotipo di `LocalBusiness`, come chiede l'audit Anni Azzurri (slide 30) e come richiede Google (sottotipo più specifico), mentre `MedicalOrganization` porta la specialità. Le residenze solo socio-assistenziali restano `LocalBusiness`. Servizi senza sede propria (RSA Aperta, assistenza domiciliare): `Service` con `provider` verso la struttura che li eroga. Cliniche e ambulatori con il sottotipo sanitario più specifico (`MedicalClinic`, `Hospital`...). Medici: `IndividualPhysician` (sottotipo di `Physician`, il tipo dell'esempio dell'audit KosGroup, slide 27) con `practicesAt` verso il `#localbusiness` della struttura; altri professionisti sanitari `Person` con `jobTitle`.

## Relazioni

- KOS Care (anniazzurri.it) → `parentOrganization` → KOS S.p.A. (kosgroup.com). **Mai `sameAs`** tra le due: sono soggetti giuridici diversi.
- Struttura → `parentOrganization` → ente del proprio sito.
- Medico → strutture in cui opera, tramite i loro `@id`.
- Articolo → `author` (persona o ente) e `publisher` (ente), tramite `@id`.
- `WebSite` → `publisher` → ente del proprio sito.

## Lingue (kosgroup.com)

Le entità sono uniche indipendentemente dalla lingua: l'`@id` si ancora sempre alla pagina italiana (`/it/...`). Una pagina inglese equivalente richiama lo stesso `@id`. La lingua è una proprietà della pagina (`inLanguage`), non dell'entità.
