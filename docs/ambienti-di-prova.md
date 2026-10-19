# Ambienti di prova (stage)

| Sito | Host MODX Cloud | Utente SSH | Alias SSH | Dominio previsto |
|---|---|---|---|---|
| anniazzurri.it | tcm7b0i8.modx.dev | c0025 | `aa-stage` | stage.anniazzurri.it |
| kosgroup.com | b0k2gmat.modx.dev | c0044 | `kos-stage` | stage.kosgroup.com |

Accesso SSH con la chiave `~/.ssh/modxcloud` (alias nel file `config` di Danilo).

## Protezione (19 ottobre 2026)

Entrambi gli stage erano pubblici e indicizzabili (200, nessun `X-Robots-Tag`). Ora:

- **Basic Auth**, utente `kos`, password nel password manager di Danilo (stessa per i due stage).
  File `~/.htpasswd` (hash SHA-512 crypt, generato con PHP: sul cloud non ci sono `openssl` né `htpasswd`).
- **`X-Robots-Tag: noindex, nofollow`** su tutte le risposte.

Regole web in cima alle Web Rules di ciascun cloud di sviluppo:

```nginx
add_header X-Robots-Tag "noindex, nofollow" always;
auth_basic "Ambiente di prova Anni Azzurri";      # KOS: "Ambiente di prova KOS"
auth_basic_user_file /paas/c0025/.htpasswd;       # KOS: /paas/c0044/.htpasswd
```

Verifica: senza credenziali `401` con `WWW-Authenticate` e `X-Robots-Tag`; con `curl -u kos` `200`.

Cambio password o revoca: rigenerare il file sul cloud con

```sh
printf 'Password per lo stage: '; stty -echo; read -r P; stty echo; echo; printf 'kos:%s\n' "$(php -r 'echo crypt($argv[1], "\$6\$".bin2hex(random_bytes(8))."\$");' "$P")" > ~/.htpasswd; unset P; chmod 644 ~/.htpasswd
```

(Una sola riga: incollata su più righe, `read` prende la riga successiva come password.)

## Attenzione

- Le regole web di produzione **non** vanno copiate così come sono sugli stage: quelle di anniazzurri
  reindirizzano verso `anniazzurri.it` ogni host diverso, e lo stage rimanderebbe in produzione.
- `robots.txt` e `llms.txt` di produzione non vanno copiati sugli stage.
- Quando arrivano i domini `stage.*`, aggiornare `site_url`/contesti dello stage e verificare che la
  protezione valga anche sul nuovo host.
