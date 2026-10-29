# Istruzioni per Claude

Progetto: ottimizzazione SEO/GEO di kosgroup.com e anniazzurri.it e migrazione da Yext a RetailTune per Gruppo KOS. Referente tecnico e sviluppatore: Danilo Bernabeo. Lingua di lavoro: italiano (documenti, commenti, commit).

## Contesto tecnico

- MODX 3.2.4 (namespace `MODX\Revolution\...`), PHP 8.3, MariaDB 10.2.54 (CTE ricorsive sì, `JSON_ARRAYAGG` no), prefisso tabelle `modx_`.
- Hosting MODX Cloud, un cloud di produzione e uno di sviluppo per sito. Shell BusyBox: niente `gawk`, usare `awk` POSIX. Configurazione nginx solo tramite le regole web del pannello.
- Cloud: kosgroup.com = utente `c0095`; anniazzurri.it = utente `c0072`. Webroot `/www`, log in `/log` (access.log non attivo).
- Componenti installati (PHP 8.3.9 CLI, git, rsync, zip, tar disponibili su entrambi i cloud):
  - comuni: pdoTools, getResources, Wayfinder, BreadCrumb, FormIt, GoogleSitemap, Canonical, FirstChildRedirect, Orphans, QuickEmail, reCaptchaV2, TinyMCE RTE;
  - solo kosgroup.com: MIGX, Tagger, ImportX;
  - solo anniazzurri.it: CKEditor, EMO (offusca le email via JavaScript: gli indirizzi non sono leggibili dai crawler AI che non eseguono JS; da considerare per la scheda contatti lato server, voce 21 audit Anni Azzurri, e per il dato strutturato, dove l'email va scritta in chiaro).

## Regole dagli interventi su prestazioni e link (8-22/10/2026)

Interventi fatti da Danilo in un'altra chat, ora conclusa (dettaglio in `docs/riferimenti/handoff-ttfb-20261022.md`). Dal 22/10 ogni ulteriore modifica su questi temi (TTFB, regole web, link, sitemap, head KOS, Cloud Edge) si guida da questo progetto. Valgono per ogni elemento che il progetto tocca:

- Link interni sempre assoluti (`/...`): `link_tag_scheme` = abs su entrambi i siti; ogni chiamata Wayfinder/pdoMenu/pdoCrumbs con `&scheme=`abs``; placeholder `href="/[[+uri]]"`; negli snippet `makeUrl` con `link_tag_scheme`.
- Nessun plugin o snippet che risponda 200 a URL inesistenti (OnPageNotFound). kosgroup.com: regola nginx che dà 404 agli URL annidati `/(it|en)/.../(it|en)`.
- Niente nuove chiamate non in cache `[[!...]]` negli head e nei menu (pesano sul TTFB): JSON-LD e dati di risorsa in cache.
- anniazzurri.it: sitemap (pdoSitemap) e `noindex, follow` governati dalla casella «Ricercabile»; nuovi head con la stessa riga `[[*searchable:is=`0`:then=...]]`.
- kosgroup.com: gli head (13 chunk) vanno consolidati in un punto solo (hreflang, noindex, Canonical, CSS); interventi SEO sugli head dentro il consolidamento, gestito da questo progetto insieme alla voce 24 (JSON-LD delle landing).
- Possibile Cloud Edge (Cloudflare) davanti ai siti: prima di attivarlo rivedere header, redirect e accesso dei crawler AI (KOS-6, AA-19).
- Prima di UPDATE massivi sul database: tabella di backup `..._bak_AAAAMMGG`.

## Convenzioni del dato strutturato (vincolanti)

- Forma canonica: `https://anniazzurri.it` (senza www, senza slash finale sulle pagine interne); `https://kosgroup.com/it/...` (senza www, prefisso lingua `/it`).
- `@id` = URL canonico della pagina + frammento fisso, generato da un'unica funzione:
  - ente: `https://anniazzurri.it/#organization` (KOS Care S.r.l.), `https://kosgroup.com/#organization` (KOS S.p.A., radice senza `/it`);
  - sito: `{base}/#website`; briciole: `{URL}#breadcrumb`; articolo `#article`; persona `#person`; medico `#medico`;
  - strutture: `{URL scheda}#localbusiness` (deciso il 20/10/2026); residenze `["MedicalBusiness", "MedicalOrganization"]` con `medicalSpecialty` `Geriatric` (deciso il 29/10; `NursingHome` NON esiste su schema.org), residenze solo socio-assistenziali `LocalBusiness` finché la verifica dell'accreditamento non è chiusa; strutture sanitarie con il sottotipo medico più specifico (`MedicalClinic`, `Hospital`...), e un tipo che non è attività locale (es. `DiagnosticLab`) affiancato a `MedicalBusiness`; nodo struttura solo se c'è l'indirizzo;
  - servizi senza sede propria (RSA Aperta, assistenza domiciliare): `Service` con `provider` verso il `#localbusiness` che li eroga e `areaServed`, nessun nodo struttura né scheda Google;
  - professionisti KOS: medici `IndividualPhysician`, altri professionisti sanitari `Person` con `jobTitle`, sempre con `practicesAt` verso `#localbusiness`; abbinamento alle risorse TuoTempo con un identificativo del gestionale, mai con il codice fiscale.
- Le scelte di modellazione le decide il progetto (schema.org, Google); quando la forma differisce dall'esempio dell'audit, la nota di consegna lo motiva in una riga con la fonte.
- Mai `sameAs` tra KOS Care e kosgroup.com: il legame è `parentOrganization`.
- Un solo `@graph` JSON-LD per pagina, emesso lato server. Niente dato strutturato scritto a mano nei chunk.

## Modo di lavorare

- Prima di intervenire su una voce d'audit, verificare lo stato attuale del sito: alcune voci possono essere già state risolte nei giorni precedenti. In quel caso raccogliere l'evidenza (comando e output) per la nota di consegna.
- Le voci d'audit sono il minimo, non il limite: ragionare secondo le buone pratiche attuali e proporre sempre eventuali migliorie, indicando se rientrano nel perimetro o vanno consuntivate a parte.

- Si sviluppa in questo repository e si collauda sullo stage prima della produzione.
- Gli elementi modificati diventano elementi statici (vedi README). Il codice comune ai due siti va in `shared/`.
- Prima di modificare un elemento esistente, partire dalla sua versione in `<sito>/baseline/`.
- Le briciole visibili (snippet `BreadCrumb`) e il `BreadcrumbList` JSON-LD devono avere sempre lo stesso percorso: stesse esclusioni (`aa.breadcrumb_exclude`), `pagetitle` per i livelli intermedi, `menutitle` o `pagetitle` per la pagina corrente.
- Ogni intervento si riferisce a una voce degli audit dell'agenzia (KosGroup settembre 2026, Anni Azzurri aprile 2026): citarla nel commit, es. `AA-8 BreadcrumbList completo`.
- Il piano degli sprint è in `docs/piano-sprint-1.md`; le scadenze sono vincolate da una penale: segnalare subito ciò che rischia di slittare.
- Le proposte fuori piano (a consuntivo, es. pagina 404 completa, paginazione news AA) non compaiono nella documentazione condivisa con KOS o l'agenzia: restano nel piano interno e si documentano per il referente di Danilo solo dopo l'implementazione.
- Ogni documento prodotto ha anche la versione docx in `docs/docx/` (stesso nome del file o della scheda): i Claude Docs si esportano dalla scheda (export docx, A4), i Markdown di `docs/` con `scripts/docs/md-in-docx.sh`. Rigenerare il docx a ogni modifica del documento e includerlo nello stesso commit.

## Lavori extra (fuori dal progetto SEO/GEO)

Altri lavori sui due siti (es. pagina Sostenibilità, ricerca nelle news, sincronizzazione video YouTube) si fanno in chat separate ma in questo repository e sugli stessi stage, con queste regole:

- Una cartella per lavoro in `extra/<nome>/` con il suo README (scopo, elementi toccati, rilasci, ripristino). Commit con prefisso `Extra <nome>:`, senza codici d'audit; i lavori extra non compaiono nella documentazione del progetto.
- Installazione solo con script ripetibili (prova, `--scrivi`, backup, ripristino), come `extra/kos-sostenibilita/installa.php`: dopo ogni riallineamento dello stage dalla produzione si rilanciano.
- Prima di toccare un elemento, controllare `docs/elementi-sotto-progetto.md` e i README degli altri lavori in `extra/`: un elemento ha un solo lavoro proprietario alla volta. Se serve modificarne uno del progetto, si coordina qui.
- Le regole del dato strutturato valgono anche per gli extra: un solo `@graph` per pagina, emesso dalla libreria comune (nuovi tipi, es. `VideoObject`, si aggiungono lì, non in chunk scritti a mano).
- Precedenza al progetto (penale): niente rilasci extra in produzione il giorno prima di una consegna di sprint e durante le settimane di verifica di Cerved/ProWeb sugli elementi verificati.
- Segreti (chiavi API, token) mai nel codice: impostazioni di sistema MODX.

## Comandi per Danilo (PowerShell → ssh/scp)

Danilo lancia i comandi da PowerShell su Windows; Claude li prepara pronti da incollare, uno per blocco.

- Repository locale: `$R = "C:\Users\danil\progetti\kos-seo-geo\repo"`; sempre `git -C $R ...` (un `git archive`
  lanciato da un'altra cartella produce un tar vuoto). Prima di dare i comandi, fare `git push` dei commit:
  Danilo scarica con `git -C $R pull`.
- Alias SSH: `aa-stage`, `kos-stage`, `kos-prod`, `aa-prod` (vedi `docs/ambienti-di-prova.md`).
- Schema di rilascio: `git -C $R archive -o $env:TEMP\<nome>.tar HEAD <cartelle>` → `scp` → `Start-Sleep 15` →
  `ssh <alias> "rm -rf ~/<dir> && mkdir -p ~/<dir> && tar xf ~/<nome>.tar -C ~/<dir> && cd ~/<dir> && php ... /www"`.
  Prima la prova, poi lo stesso comando con `--scrivi`; prima lo stage, poi la produzione.
- Insidie del passaggio PowerShell → ssh: le virgolette doppie dentro un comando remoto vanno evitate (vengono
  tolte); `--scrivi` va prima di un eventuale `| tail`; in caso di «Connection reset» si riprova dopo un minuto.
- La shell remota è BusyBox (`ash`): niente `gawk`, niente sintassi bash.
- Password dello stage (Basic Auth, utente `kos`) solo da `$env:AUTH`, mai in file del repository.
