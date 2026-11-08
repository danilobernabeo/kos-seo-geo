#!/usr/bin/env python3
"""
Converte il markup Yext Knowledge Tags (elementi vuoti con data-yext-field, riempiti nel browser) nelle chiamate
allo snippet aaContatto, che scrive il dato dal server dalle TV str.* (anniazzurri.it, schede residenza, sprint 3).

    <span class="font-weight-600" data-yext-field="name" data-yext-id="[[*data-yext-id]]"></span>
      -> <span class="font-weight-600">[[aaContatto? &campo=`name`]]</span>
    <a class="x" data-yext-field="email" data-yext-id="[[*data-yext-id]]"></a>
      -> [[aaContatto? &campo=`email` &classe=`x`]]

Uso: python3 scripts/yext/converti-markup-yext.py <file di ingresso> <file di uscita>
La stessa regola, in PHP, è in scripts/deploy/aa-yext-contenuti.php per i contenuti delle pagine.
"""
import re
import sys

ELEM = re.compile(r'<(a|span)\b([^>]*?)\sdata-yext-field="(\w+)"\s+data-yext-id="\[\[\*data-yext-id\]\]"([^>]*)>\s*</\1>', re.S)
CLASSE = re.compile(r'\bclass="([^"]*)"')


def converti(testo):
    def sost(m):
        tag, attr, campo = m.group(1), m.group(2) + m.group(4), m.group(3)
        c = CLASSE.search(attr)
        classe = c.group(1).strip() if c else ''
        if tag == 'a':
            return f'[[aaContatto? &campo=`{campo}`' + (f' &classe=`{classe}`' if classe else '') + ']]'
        chiamata = f'[[aaContatto? &campo=`{campo}`]]'
        return f'<span class="{classe}">{chiamata}</span>' if classe else chiamata
    nuovo, n = ELEM.subn(sost, testo)
    return nuovo, n


if __name__ == '__main__':
    t = open(sys.argv[1], encoding='utf-8').read()
    nuovo, n = converti(t)
    if 'data-yext' in nuovo:
        sys.exit(f'{sys.argv[1]}: resta markup Yext non riconosciuto')
    open(sys.argv[2], 'w', encoding='utf-8').write(nuovo)
    print(f'{sys.argv[2]}: {n} campi')
