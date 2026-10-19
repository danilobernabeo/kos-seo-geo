# Sprint 1 — file nella radice dei siti

Voci: robots.txt (19 KOS), llms.txt (18 KOS; perimetro B1 per AA), favicon e apple-touch-icon (log AA).

I file vivono in `<sito>/files/` e si copiano nella webroot `/www` del cloud (prima stage, poi produzione).
Sono file statici serviti direttamente da nginx: non passano da MODX.

## robots.txt

- **kosgroup.com**: Crawl-delay già rimosso da Danilo. Le regole `Disallow: *?query=*` non iniziavano con `/`:
  lo standard (RFC 9309) e Google richiedono che il percorso inizi con `/`. Riscritte come `/*?query=` e
  `/*&query=` (parametro primo o successivo); idem per `formId`. Il `*` finale è implicito.
- **anniazzurri.it**: allineato a kosgroup.com. La ricerca Yext del sito (`yext-scripts`, `redirectUrl` → `/cerca`)
  genera pagine di risultati con `?query=`: escluse come su kosgroup. Tolto `Allow: /`, superfluo.
- Nessun gruppo dedicato ai crawler AI: con un gruppo specifico un bot ignora le regole di `User-agent: *`;
  il gruppo generale li ammette già tutti.

## llms.txt

Formato llmstxt.org: titolo, sintesi, sezioni di link. Generato da `scripts/llms/genera-llms.py` con i dati
della mappatura kosgroup, della query residenze AA e dell'export Yext. **Le due sintesi iniziali e il numero
di residenze vanno validati con KOS prima della pubblicazione.** Da rigenerare quando cambiano le strutture.

### Stato (19 ottobre)

- llms.txt pubblicato in produzione su entrambi i siti (bozza; sintesi da validare con KOS).
- Regola web aggiunta su entrambi i cloud per dichiarare il charset (verificato `text/plain; charset=utf-8`):

```nginx
location = /llms.txt {
    charset utf-8;
}
location = /robots.txt {
    charset utf-8;
}
```

## favicon e apple-touch-icon

Browser e crawler chiedono `/favicon.ico` e `/apple-touch-icon.png` nella radice anche se la pagina dichiara
le icone altrove. Su AA ~56.000 richieste 404 nelle ultime 200.000 righe di log.

| Sito | favicon.ico in radice | apple-touch-icon in radice | Icone dichiarate nell'head |
|---|---|---|---|
| kosgroup.com | presente | assente | `/assets/img/` |
| anniazzurri.it | assente | assente | `/dist/assets/` (solo 26 dei 50 chunk head) |

Intervento: copia nella radice delle icone già esistenti (nessun nuovo grafico).

```sh
# anniazzurri.it
cp /www/dist/assets/favicon.ico /www/favicon.ico
cp /www/dist/assets/apple-touch-icon.png /www/apple-touch-icon.png
cp /www/dist/assets/apple-touch-icon.png /www/apple-touch-icon-precomposed.png
# kosgroup.com
cp /www/assets/img/apple-touch-icon.png /www/apple-touch-icon.png
cp /www/assets/img/apple-touch-icon.png /www/apple-touch-icon-precomposed.png
```

Da valutare a parte (sprint 2): 24 chunk head di AA senza dichiarazione delle icone.

## Collaudo

```sh
for u in robots.txt llms.txt favicon.ico apple-touch-icon.png apple-touch-icon-precomposed.png; do
  curl -s -o /dev/null -w "%{http_code} %{content_type} $u\n" https://<host>/$u; done
```
Atteso: 200 per tutti, `text/plain` per robots e llms (con charset utf-8 per llms.txt).
