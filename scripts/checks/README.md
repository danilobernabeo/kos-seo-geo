# Verifiche di collaudo

Script da lanciare sul PC (con Claude Code) o in SSH sui cloud, contro lo stage prima di ogni rilascio e contro la produzione dopo.

Previsti per lo sprint 1:
- `breadcrumb-check` — confronta le briciole visibili con il `BreadcrumbList` JSON-LD su un campione di pagine per template.
- `headers-check` — intestazioni di cache delle risorse statiche, `X-Robots-Tag`, redirect 301 dei domini storici.
- `bots-check` — codici di risposta con gli user agent dei crawler (GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, PerplexityBot, Bingbot).
- `schema-check` — estrae il JSON-LD dalle pagine e verifica `@id`, forma canonica degli URL e riferimenti non risolti.

## Head di kosgroup.com (KOS-2, voce 24)

- `risorse-per-template.php /www --campione` — sul cloud: elenco di pagine campione (2 per template, paginate 30-34,
  coppie italiano/inglese) da salvare in `pagine.txt`.
- `kos-head-controllo.py --base <stage> --pagine pagine.txt [--confronta https://kosgroup.com]` — sul PC, password
  dello stage solo in `$env:AUTH`: title, description, robots, canonical, hreflang (anche reciproci), og completi con
  immagine raggiungibile, tag MODX non risolti; con `--confronta` le differenze dalla produzione.
