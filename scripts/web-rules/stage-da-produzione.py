#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Regole web complete di STAGE e di PRODUZIONE con la sezione PDF, a partire dal file di produzione del 31/10.

  <sito>/files/web-rules/produzione-2026-10-31.conf + pdf-noindex(-stage).conf
      → <sito>/files/web-rules/produzione-2026-11-01.conf   (produzione con la sezione PDF; per AA sostituisce il vecchio blocco .pdf)
      → <sito>/files/web-rules/stage-2026-11-01.conf        (stesse regole per lo stage)

Differenze dello stage rispetto alla produzione (docs/ambienti-di-prova.md): intestazione noindex + Basic Auth, niente reindirizzamento
di host/schema ($redirect_to e relativi «if»), niente robots.txt/llms.txt di produzione, sezione PDF in variante stage.
Controlla che non ci siano «location =» duplicate (nginx rifiuta la configurazione).
"""
import re, sys, os, collections

RADICE = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
SITI = {
    'kosgroup': ('Ambiente di prova KOS', '/c0044/www/core/.htpasswd'),
    'anniazzurri': ('Ambiente di prova Anni Azzurri', '/c0025/www/core/.htpasswd'),
}
DATA = '2026-11-01'


def togli_blocco(righe, inizio_re, conta=None):
    """Toglie i blocchi «{ ... }» la cui riga di apertura corrisponde a inizio_re (con i commenti che li precedono subito sopra)."""
    out, i, tolti = [], 0, 0
    while i < len(righe):
        if re.match(inizio_re, righe[i]):
            prof = 0
            j = i
            while True:
                prof += righe[j].count('{') - righe[j].count('}')
                if prof <= 0 and '{' in ''.join(righe[i:j + 1]):
                    break
                j += 1
            while out and out[-1].startswith('#'):   # commenti attaccati al blocco
                out.pop()
            while out and out[-1].strip() == '' and (j + 1 < len(righe) and righe[j + 1].strip() == ''):
                out.pop()
            i = j + 1
            tolti += 1
            continue
        out.append(righe[i])
        i += 1
    return out, tolti


def duplicati(testo):
    c = collections.Counter(re.findall(r'^\s*location\s*=\s*("[^"]+"|\S+)\s*\{', testo, re.M))
    return [k for k, v in c.items() if v > 1]


def main():
    for sito, (realm, htpasswd) in SITI.items():
        d = os.path.join(RADICE, sito, 'files', 'web-rules')
        prod = open(os.path.join(d, 'produzione-2026-10-31.conf'), encoding='utf-8').read().split('\n')
        sez_p = open(os.path.join(d, 'pdf-noindex.conf'), encoding='utf-8').read().rstrip('\n').split('\n')
        sez_s = open(os.path.join(d, 'pdf-noindex-stage.conf'), encoding='utf-8').read().rstrip('\n').split('\n')

        base = list(prod)
        if sito == 'anniazzurri':   # il vecchio blocco PDF (expires 1d) è assorbito dalla sezione
            base, n = togli_blocco(base, r'^location ~\* \\\.pdf\$ \{')
            assert n == 1, 'blocco .pdf di AA non trovato'

        def inserisci(righe, sezione):
            k = max(i for i, r in enumerate(righe) if re.match(r'^location / \{', r))
            while k > 0 and (righe[k - 1].startswith('#') or righe[k - 1].strip() == ''):   # prima dei commenti del blocco «location /»
                k -= 1
            return righe[:k] + [''] + sezione + [''] + righe[k:]

        # ---- produzione ----
        out_p = inserisci(base, sez_p)
        t_p = '\n'.join(out_p).rstrip('\n') + '\n'
        t_p = ('# Regole web di PRODUZIONE %s = produzione-2026-10-31.conf + sezione PDF (KOS-8 / AA-3)%s. Generato da scripts/web-rules/stage-da-produzione.py: non modificare a mano.\n'
               % (sito, ', che al posto del vecchio blocco «location ~* \\.pdf$» di questo file vale da solo' if sito == 'anniazzurri' else '')) + t_p

        # ---- stage ----
        s = list(base)
        # 1. reindirizzamento di host/schema e rete di sicurezza PHP (calcolano e usano $redirect_to)
        s, n1 = togli_blocco(s, r'^if \(\$host = "7ydxba3m\.modx\.dev"\) \{')
        s, n2 = togli_blocco(s, r'^if \(\$host (!=|=) "(www\.)?(kosgroup\.com|anniazzurri\.it)"\) \{')
        s, n3 = togli_blocco(s, r'^if \(\$scheme != "https"\) \{')
        s, n4 = togli_blocco(s, r'^if \(\$redirect_to ~ ')
        s, n5 = togli_blocco(s, r'^if \(\$redirect_check ~ ')
        s = [r for r in s if not re.match(r'^\s*set \$redirect_(to|check) ', r)]
        s = [r for r in s if not re.match(r'^\s*if \(\$redirect_to\) \{ return 301 \$redirect_to; \}\s*$', r)]
        # 2. robots.txt e llms.txt di produzione non vanno sullo stage
        s, n6 = togli_blocco(s, r'^location = /llms\.txt \{')
        s, n7 = togli_blocco(s, r'^location = /robots\.txt \{')
        assert n6 == 1 and n7 == 1, (sito, n6, n7)
        out_s = inserisci(s, sez_s)
        # 3. i redirect puntano all'host dello stage invece che alla produzione (altrimenti chi collauda finisce in produzione);
        #    Link rel=canonical e URL negli altri contesti restano quelli di produzione
        origine = {'kosgroup': 'https://kosgroup.com', 'anniazzurri': 'https://anniazzurri.it'}[sito]
        out_s = [re.sub(re.escape(origine) + r'(?=[/"\s;$]|$)', '$scheme://$host', l) if re.search(r'\b(return 30[12]|rewrite )', l) else l for l in out_s]
        intest = ['# Regole web di STAGE %s (%s) = produzione-2026-10-31.conf + sezione PDF in variante stage (KOS-8 / AA-3).' % (sito, DATA),
                  '# Generato da scripts/web-rules/stage-da-produzione.py: non modificare a mano. Differenze dalla produzione: noindex + Basic Auth qui sotto,',
                  '# niente reindirizzamento di host/schema ($redirect_to), niente robots.txt/llms.txt, sezione PDF senza $redirect_to, redirect verso l\'host dello stage.',
                  'add_header X-Robots-Tag "noindex, nofollow" always;',
                  'auth_basic "%s";' % realm,
                  'auth_basic_user_file %s;' % htpasswd, '']
        t_s = '\n'.join(intest + out_s).rstrip('\n') + '\n'

        for nome, t in (('produzione', t_p), ('stage', t_s)):
            dup = duplicati(t)
            if dup:
                print('!! %s %s: location = DUPLICATE: %s' % (sito, nome, dup))
                sys.exit(1)
        if any(('redirect_to' in l or 'redirect_check' in l) and not l.lstrip().startswith('#') for l in t_s.split('\n')):
            print('!! %s stage: resta un riferimento a $redirect_to:' % sito)
            for l in t_s.split('\n'):
                if ('redirect_to' in l or 'redirect_check' in l) and not l.lstrip().startswith('#'): print('   ', l.strip()[:120])
            sys.exit(1)
        # parentesi graffe bilanciate (fuori dai commenti e dalle stringhe)
        for nome, t in (('produzione', t_p), ('stage', t_s)):
            pulito = '\n'.join(re.sub(r'"[^"\n]*"', '""', l.split('#')[0] if not l.lstrip().startswith('rewrite') else l) for l in t.split('\n'))
            if pulito.count('{') != pulito.count('}'):
                print('!! %s %s: graffe non bilanciate (%d/%d)' % (sito, nome, pulito.count('{'), pulito.count('}')))
                sys.exit(1)
        open(os.path.join(d, 'produzione-%s.conf' % DATA), 'w', encoding='utf-8').write(t_p)
        open(os.path.join(d, 'stage-%s.conf' % DATA), 'w', encoding='utf-8').write(t_s)
        print('%s: produzione %d righe, stage %d righe (tolti: host %d/%d, schema %d, radice %d, php %d, llms/robots %d/%d)'
              % (sito, t_p.count('\n'), t_s.count('\n'), n1, n2, n3, n4, n5, n6, n7))


if __name__ == '__main__':
    main()
