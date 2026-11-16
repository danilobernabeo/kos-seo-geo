#!/usr/bin/env python3
"""Secondo controllo del set ripulito con Google Sensitive Data Protection (DLP), regione UE, prima di ogni giro su
Yext o su Vertex (decisione del 16/11: le regole a mano avevano mancato 76 nomi).

Uso:
  python3 -I scripts/ricerca/controlla-dlp.py --ricerche <dati>/ricerche-kosgroup.csv \
      --chiave <kos-ricerca-chiavi>/....json --uscita <dati>/esiti

Manda a DLP (content:inspect, projects/kos-ricerca/locations/europe) le ricerche ripulite, in blocchi da 500 righe, con
i rilevatori di nomi, codici fiscali, telefoni, email, indirizzi, date di nascita, IBAN e documenti, più un rilevatore
per i codici con lettere e cifre. Scrive i ritrovamenti in <uscita>/dlp-<nome file>.json (fuori dal repository: contiene
i testi). Se non ci sono ritrovamenti scrive <ricerche>.dlp-ok con l'impronta del file: misura.py rifiuta di lanciare
le ricerche se l'impronta manca o non corrisponde al file. Con ritrovamenti: si controllano a mano, si aggiornano le
correzioni di pulisci-ricerche.py, si rifà la pulizia e si rilancia questo controllo; i falsi allarmi si elencano in
<uscita>/dlp-falsi-allarmi.json ({"ricerca": "motivo"}) e non bloccano.
"""
import argparse
import csv
import hashlib
import json
import sys
from pathlib import Path

import requests

DLP = 'https://dlp.googleapis.com/v2/projects/kos-ricerca/locations/europe/content:inspect'
TIPI = ['PERSON_NAME', 'FIRST_NAME', 'LAST_NAME', 'ITALY_FISCAL_CODE', 'PHONE_NUMBER', 'EMAIL_ADDRESS',
        'STREET_ADDRESS', 'DATE_OF_BIRTH', 'IBAN_CODE', 'PASSPORT', 'ITALY_PASSPORT', 'ITALY_IDENTITY_CARD_NUMBER']
CODICE = {'infoType': {'name': 'CODICE_ALFANUMERICO'}, 'likelihood': 'LIKELY',
          'regex': {'pattern': r'\b(?=[a-z0-9]*[a-z])(?=(?:[a-z]*\d){2})[a-z0-9]{5,}\b'}}


def impronta(percorso):
    return hashlib.sha256(Path(percorso).read_bytes()).hexdigest()


def credenziali(chiave):
    from google.oauth2 import service_account
    import google.auth.transport.requests as tr
    c = service_account.Credentials.from_service_account_file(
        chiave, scopes=['https://www.googleapis.com/auth/cloud-platform'])
    c.refresh(tr.Request())
    return c


def ispeziona(righe, cred):
    corpo = {
        'inspectConfig': {'infoTypes': [{'name': t} for t in TIPI], 'customInfoTypes': [CODICE],
                          'minLikelihood': 'POSSIBLE', 'includeQuote': True,
                          'limits': {'maxFindingsPerRequest': 0}},
        'item': {'table': {'headers': [{'name': 'ricerca'}],
                           'rows': [{'values': [{'stringValue': q}]} for q in righe]}},
    }
    r = requests.post(DLP, json=corpo, timeout=120,
                      headers={'Authorization': 'Bearer ' + cred.token, 'x-goog-user-project': 'kos-ricerca'})
    r.raise_for_status()
    out = []
    for f in r.json().get('result', {}).get('findings', []):
        riga = int(f['location']['contentLocations'][0]['recordLocation']['tableLocation'].get('rowIndex', 0))
        out.append({'ricerca': righe[riga], 'tipo': f['infoType']['name'], 'probabilita': f.get('likelihood'),
                    'testo': f.get('quote', '')})
    return out


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--ricerche', required=True)
    a.add_argument('--chiave', required=True)
    a.add_argument('--uscita', required=True)
    o = a.parse_args()
    with open(o.ricerche, encoding='utf-8', newline='') as f:
        righe = [r['ricerca'] for r in csv.DictReader(f)]
    cred = credenziali(o.chiave)
    trovati = []
    for i in range(0, len(righe), 500):
        trovati += ispeziona(righe[i:i + 500], cred)
    falsi_f = Path(o.uscita) / 'dlp-falsi-allarmi.json'
    falsi = json.loads(falsi_f.read_text(encoding='utf-8')) if falsi_f.exists() else {}
    aperti = [t for t in trovati if t['ricerca'] not in falsi]
    nome = Path(o.ricerche).stem
    Path(o.uscita, f'dlp-{nome}.json').write_text(json.dumps(trovati, ensure_ascii=False, indent=1), encoding='utf-8')
    ok = Path(str(o.ricerche) + '.dlp-ok')
    from collections import Counter
    print(f'{nome}: {len(righe)} ricerche, {len(trovati)} ritrovamenti, {len(aperti)} da controllare',
          dict(Counter(t["tipo"] for t in aperti)))
    if aperti:
        ok.unlink(missing_ok=True)
        return 1
    ok.write_text(impronta(o.ricerche) + '\n', encoding='utf-8')
    return 0


if __name__ == '__main__':
    sys.exit(main())
