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
