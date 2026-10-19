# Ambienti di prova (stage)

| Sito | Host MODX Cloud | Utente SSH | Alias SSH | Dominio previsto |
|---|---|---|---|---|
| anniazzurri.it | tcm7b0i8.modx.dev | c0025 | `aa-stage` | stage.anniazzurri.it |
| kosgroup.com | b0k2gmat.modx.dev | c0044 | `kos-stage` | stage.kosgroup.com |

Accesso SSH con la chiave `~/.ssh/modxcloud` (alias nel file `config` di Danilo).

## Protezione (19 ottobre 2026)

Entrambi gli stage erano pubblici e indicizzabili (200, nessun `X-Robots-Tag`). Ora:

- **Basic Auth**, utente `kos`, password nel password manager di Danilo (stessa per i due stage).
  File `/www/core/.htpasswd` (hash SHA-512 crypt, generato con PHP: sul cloud non ci sono `openssl` né `htpasswd`).
  **Percorsi**: nginx vede il filesystem diversamente dalla shell SSH. La home (`/paas/cXXXX` → `/home`)
  non è visibile a nginx; la webroot `/www` per nginx è `/cXXXX/www`. Il file sta in `/www/core/`,
  cartella che MODX Cloud già nega via web (verificato: 403 anche con la password).
- **`X-Robots-Tag: noindex, nofollow`** su tutte le risposte.

Regole web in cima alle Web Rules di ciascun cloud di sviluppo:

```nginx
add_header X-Robots-Tag "noindex, nofollow" always;
auth_basic "Ambiente di prova Anni Azzurri";      # KOS: "Ambiente di prova KOS"
auth_basic_user_file /c0025/www/core/.htpasswd;   # KOS: /c0044/www/core/.htpasswd
```

Verifica (19/10, confermata): senza credenziali `401` con `WWW-Authenticate` e `X-Robots-Tag`; con `curl -u kos` `200`;
`/core/.htpasswd` `403`; produzione invariata.

Cambio password o revoca: rigenerare il file sul cloud con

```sh
printf 'Password per lo stage: '; stty -echo; read -r P; stty echo; echo; printf 'kos:%s\n' "$(php -r 'echo crypt($argv[1], "\$6\$".bin2hex(random_bytes(8))."\$");' "$P")" > /www/core/.htpasswd; unset P; chmod 644 /www/core/.htpasswd
```

(Una sola riga: incollata su più righe, `read` prende la riga successiva come password.)

## Attenzione

- Le regole web di produzione **non** vanno copiate così come sono sugli stage: quelle di anniazzurri
  reindirizzano verso `anniazzurri.it` ogni host diverso, e lo stage rimanderebbe in produzione.
- `robots.txt` e `llms.txt` di produzione non vanno copiati sugli stage.
- Quando arrivano i domini `stage.*`, aggiornare `site_url`/contesti dello stage e verificare che la
  protezione valga anche sul nuovo host.

## Convivenza tra stage e produzione

- **Lo stage collauda codice, non contenuti.** Template, chunk, snippet, plugin, impostazioni e regole web
  si provano sullo stage; contenuti (news, approfondimenti, landing, pagine) si gestiscono solo in produzione.
- **Mai copiare il database dello stage in produzione.** In produzione arrivano solo gli elementi modificati,
  dal repository.
- **Riallineamento dello stage** (snapshot di produzione → cloud di sviluppo) quando un collaudo ha bisogno di
  dati recenti. Prima salvare `/www/core/.htpasswd` in `/home/htpasswd.bak` e rimetterlo dopo; le Web Rules
  non fanno parte dello snapshot.

### Lavoro in produzione durante il progetto

- **Contenuti**: si pubblica normalmente. Non serve riallineare lo stage.
- **Nuove landing e pagine**: usare i template esistenti (così ricevono `[[aaSchema]]` dal footer) e non
  scrivere a mano Organization, WebSite o BreadcrumbList. Dato strutturato specifico della pagina (FAQ,
  JobPosting) ammesso nei chunk `schema-*`, con l'ente richiamato per `@id` (`https://anniazzurri.it/#organization`).
- **Elementi sotto il progetto** (elenco in `docs/elementi-sotto-progetto.md`): nessuna modifica dal manager
  di produzione; si passa dal repository. Se una modifica urgente è inevitabile, riportarla subito nel repository.
- **Regole web e impostazioni di sistema**: ogni modifica fatta in produzione va riportata nel file del
  repository (`<sito>/files/web-rules/web-rules.conf`), che resta la fonte da cui si incolla.

### Controllo prima di ogni rilascio

Prima di sostituire in produzione un elemento, confrontare la versione di produzione con la baseline: se è
cambiata dopo il 19 ottobre, integrare la modifica nel repository prima del rilascio
(`scripts/export-elements.php` sul cloud di produzione e confronto con `<sito>/baseline/`).
