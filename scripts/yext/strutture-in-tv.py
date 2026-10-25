#!/usr/bin/env python3
"""
Voce 24 — dall'export Yext (xlsx, «Esporta entità») ai valori delle TV anagrafiche str.* delle schede struttura.

Uso:
    python3 scripts/yext/strutture-in-tv.py <export.xlsx> [--sito kosgroup.com] [--uscita kosgroup/dati/voce-24]

Produce, nella cartella di uscita:
    strutture-yext-<data>.json   valori per scripts/deploy/imposta-tv.php (risorse indicate per uri, dall'URL del sito
                                 web dell'entità Yext): prima in prova, poi --scrivi
    report-yext-<data>.md        anomalie da correggere in Yext prima dell'import definitivo e scelte fatte dallo script

Regole (da docs/kos-24.md):
  - abbinamento entità → scheda dal campo «URL del sito web» (solo l'host del sito indicato, forma canonica);
  - telefono in formato internazionale (+39 seguito dal numero com'è, zero iniziale compreso);
  - orari «8am-8pm», «9am-12:30pm, 3pm-6pm», «Closed» → righe «Mo-Fr 08:00-20:00» (giorni consecutivi uguali uniti);
    orari di riposo («Orari di riposo > Data / Orari») → str.orari_speciali;
  - provincia dal «Codice regionale ISO»; coordinate da «Lat/long visualizzata su Yext»;
  - tipo e specialità schema.org dalla «Tipologia di Struttura» secondo la tabella PROPOSTA qui sotto, da far approvare
    a KOS e all'agenzia (eventualmente rivista sulle categorie dei profili Google): fino ad allora resta una proposta;
  - marchio da «Marche» (valori non validi come «0» scartati e segnalati);
  - sameAs: solo la pagina Facebook propria della struttura (nome utente usato da una sola entità), mai i profili del
    gruppo (LinkedIn, YouTube) né le pagine location.com di Yext.
"""
import argparse
import collections
import datetime as dt
import json
import os
import re
import sys
import warnings

TIPI = {   # Tipologia di Struttura (Yext) → (tipo schema.org, specialità) — PROPOSTA del 25/10/2026
    'Ambulatori di riabilitazione': ('MedicalClinic', ['Physiotherapy']),
    'Centri riabilitazione': ('MedicalClinic', ['Physiotherapy']),
    'Centri di riabilitazione': ('MedicalClinic', ['Physiotherapy']),
    'Comunità terapeutiche psichiatriche': ('MedicalClinic', ['Psychiatric']),
    'Comunità terapeutiche riabilitative per minori': ('MedicalClinic', ['Psychiatric', 'Pediatric']),
    'Cliniche psichiatriche': ('MedicalClinic', ['Psychiatric']),
    'Poliambulatori': ('MedicalClinic', []),
    'Laboratorio analisi': ('DiagnosticLab', ['LaboratoryScience']),
    'Punti prelievo': ('DiagnosticLab', ['LaboratoryScience']),
    'Cliniche dentali': ('Dentist', ['Dentistry']),
    'Ospedali': ('Hospital', []),
    'Hospice': ('MedicalOrganization,LocalBusiness', []),
    'Residenze per anziani': ('MedicalOrganization,LocalBusiness', ['Geriatric']),
}
GIORNI = [('Lunedì', 'Mo'), ('Martedì', 'Tu'), ('Mercoledì', 'We'), ('Giovedì', 'Th'), ('Venerdì', 'Fr'),
          ('Sabato', 'Sa'), ('Domenica', 'Su')]
ABBREV = [(r'^V\.le\s+', 'Viale '), (r'^P\.?za\s+', 'Piazza '), (r'^C\.so\s+', 'Corso '), (r'^Loc\.\s*', 'Località '),
          (r'^V\.\s*', 'Via ')]   # prima le forme più lunghe: «V.le» non deve diventare «Via le»


def ora24(t):
    m = re.fullmatch(r'(\d{1,2})(?::(\d{2}))?\s*(am|pm)', t.strip().lower())
    if not m:
        return None
    h, mi, ap = int(m.group(1)), int(m.group(2) or 0), m.group(3)
    if ap == 'am' and h == 12:
        h = 0
    if ap == 'pm' and h != 12:
        h += 12
    return f'{h:02d}:{mi:02d}'


def fasce(v):
    """'9am-12:30pm, 3pm-6pm' → '09:00-12:30,15:00-18:00'; 'Closed'/'' → '' ; None se non interpretabile."""
    v = (v or '').strip()
    if v == '' or v.lower() in ('closed', 'chiuso'):
        return ''
    if v.lower() in ('open 24 hours', 'aperto 24 ore', '24 hours', '24hr', '24h', '24 hr'):
        return '00:00-23:59'
    out = []
    for f in v.split(','):
        p = f.split('-')
        if len(p) != 2:
            return None
        a, b = ora24(p[0]), ora24(p[1])
        if not a or not b:
            return None
        out.append(f'{a}-{"23:59" if b == "00:00" else b}')
    return ','.join(out)


def righe_orari(giorni):
    """[(codice, fasce)] → righe con giorni consecutivi uguali uniti."""
    out, i = [], 0
    while i < len(giorni):
        j = i
        while j + 1 < len(giorni) and giorni[j + 1][1] == giorni[i][1]:
            j += 1
        if giorni[i][1]:
            out.append(f'{giorni[i][0]}{"-" + giorni[j][0] if j > i else ""} {giorni[i][1]}')
        i = j + 1
    return out


def main():
    a = argparse.ArgumentParser()
    a.add_argument('export')
    a.add_argument('--sito', default='kosgroup.com')
    a.add_argument('--uscita', default='kosgroup/dati/voce-24')
    o = a.parse_args()
    warnings.filterwarnings('ignore')
    import openpyxl
    ws = openpyxl.load_workbook(o.export, read_only=True).worksheets[0]
    rows = list(ws.iter_rows(values_only=True))
    Y = [dict(zip(rows[0], r)) for r in rows[1:]]
    g = lambda y, k: '' if y.get(k) is None else str(y[k]).strip()
    data = re.search(r'(\d{4}-\d{2}-\d{2})', os.path.basename(o.export))
    data = data.group(1) if data else dt.date.today().isoformat()

    mie = [y for y in Y if re.match(rf'https?://(www\.)?{re.escape(o.sito)}/', g(y, 'URL del sito web > URL'))]
    fb = collections.Counter(g(y, 'Nome utente di Facebook') for y in Y if g(y, 'Nome utente di Facebook'))
    per_uri = collections.defaultdict(list)
    risorse, note, estese = [], collections.defaultdict(list), []
    for y in mie:
        ide = g(y, 'ID entità')
        nome = g(y, 'Nome')
        uri = re.sub(rf'^https?://(www\.)?{re.escape(o.sito)}/', '', g(y, 'URL del sito web > URL')).split('?')[0].strip('/')
        per_uri[uri].append(ide)
        N = lambda s: note[f'{nome} ({ide}) → /{uri}'].append(s)
        if g(y, 'Chiuso').lower() in ('vero', 'true'):
            N('entità segnata come chiusa in Yext: non importata')
            continue

        via = g(y, 'Indirizzo > Riga 1')
        for pat, rep in ABBREV:
            nuova = re.sub(pat, rep, via)
            if nuova != via:
                estese.append(f'{via} → {nuova}')
                via = nuova
                break
        if not uri.startswith(('it/centri/', 'en/')):
            N(f'URL del sito web /{uri} non è una scheda struttura (/it/centri/...): l\'entità non si abbina')
            continue
        if g(y, 'Indirizzo > Riga 2'):
            N(f'seconda riga dell\'indirizzo non importata: «{g(y, "Indirizzo > Riga 2")}»')
        tel = re.sub(r'\D', '', g(y, 'Telefono principale > Telefono'))
        if g(y, 'Telefono principale > Codice paese ISO') not in ('', 'IT'):
            N('telefono con prefisso non italiano: da verificare')
        email = g(y, 'E-mail').lower()
        if not email:
            N('email assente')

        giorni = []
        for it, cod in GIORNI:
            f = fasce(g(y, f'Orari > {it}'))
            if f is None:
                N(f'orario di {it.lower()} non interpretabile: «{g(y, "Orari > " + it)}»')
                f = ''
            giorni.append((cod, f))
        orari = righe_orari(giorni)
        if not orari:
            N('orari assenti')
        elif len(orari) == 1 and orari[0].startswith('Mo-Su'):
            N(f'stesso orario sette giorni su sette ({orari[0][6:]}): da confermare con KOS (apertura, visite o accettazione?)')
        speciali = []
        for d, h in zip(g(y, 'Orari > Orari di riposo > Data').split(','), g(y, 'Orari > Orari di riposo > Orari').split(',')):
            d = d.strip()
            if not re.fullmatch(r'\d{4}-\d{2}-\d{2}', d):
                if d:
                    N(f'orario di riposo con data non interpretabile: «{d}»')
                continue
            f = fasce(h)
            speciali.append(f'{d} {"chiuso" if not f else f.split(",")[0]}')

        lat = g(y, 'Lat/long visualizzata su Yext > Latitudine').replace(',', '.')
        lng = g(y, 'Lat/long visualizzata su Yext > Longitudine').replace(',', '.')
        place = g(y, 'ID di Google Place')
        if not place:
            N('place_id di Google assente (profilo Google non collegato?)')

        tip = [t.strip() for t in g(y, 'Tipologia di Struttura').split(',') if t.strip()]
        tipo, spec = '', []
        for t in tip:
            if t not in TIPI:
                N(f'tipologia «{t}» senza corrispondenza nella tabella dei tipi')
                continue
            tipo = tipo or TIPI[t][0]
            spec += [s for s in TIPI[t][1] if s not in spec]
        if not tip:
            N('tipologia di struttura assente: tipo predefinito (MedicalClinic)')

        marchio = g(y, 'Marche')
        if marchio in ('0', '-') or '/' in marchio:
            N(f'marchio «{marchio}» non valido: non importato')
            marchio = ''
        fbu = g(y, 'Nome utente di Facebook')
        same = f'https://www.facebook.com/{fbu}' if fbu and fb[fbu] == 1 else ''

        risorse.append({'uri': uri, 'yext': ide, 'tv': {
            'str.nome': nome, 'str.via': via, 'str.cap': g(y, 'Indirizzo > CAP'),
            'str.citta': g(y, 'Indirizzo > Città'), 'str.provincia': g(y, 'Codice regionale ISO').upper(),
            'str.telefono': ('+39' + tel) if tel else '', 'str.email': email,
            'str.lat': lat, 'str.lng': lng, 'str.orari': '\n'.join(orari), 'str.orari_speciali': '\n'.join(speciali),
            'str.place_id': place, 'str.tipo': tipo, 'str.specialita': ', '.join(spec),
            'str.marchio': marchio, 'str.sameAs': same,
        }})

    doppi = {u: ids for u, ids in per_uri.items() if len(ids) > 1}
    if doppi:   # due entità sulla stessa scheda: non si sceglie a caso, si salta e si segnala
        risorse = [r for r in risorse if r['uri'] not in doppi]

    os.makedirs(o.uscita, exist_ok=True)
    fj = os.path.join(o.uscita, f'strutture-yext-{data}.json')
    with open(fj, 'w', encoding='utf-8') as f:
        json.dump({'nome': f'Voce 24: TV anagrafiche str.* dall\'export Yext del {data} ({len(risorse)} schede {o.sito})',
                   'risorse': [{'uri': r['uri'], 'tv': r['tv']} for r in risorse]}, f, ensure_ascii=False, indent=1)

    tipi = collections.Counter((r['tv']['str.tipo'] or '(predefinito)', r['tv']['str.specialita']) for r in risorse)
    fr = os.path.join(o.uscita, f'report-yext-{data}.md')
    with open(fr, 'w', encoding='utf-8') as f:
        f.write(f'# Import delle TV anagrafiche dall\'export Yext del {data} ({o.sito})\n\n')
        f.write(f'Entità Yext con URL su {o.sito}: {len(mie)}; schede da importare: {len(risorse)}.\n'
                'Il file JSON si applica con `scripts/deploy/imposta-tv.php` (prova, poi `--scrivi`): la prova mostra la '
                'scheda (pagetitle) accanto a ogni valore, così si vedono anche le differenze di nome. Le schede del sito '
                'senza entità Yext risultano dall\'elenco delle schede pubblicate, non da qui.\n\n')
        if doppi:
            f.write('## Più entità sulla stessa scheda (non importate)\n\n')
            for u, ids in doppi.items():
                f.write(f'- /{u}: {", ".join(ids)}\n')
            f.write('\n')
        f.write('## Tipi schema.org applicati (PROPOSTA, da approvare)\n\n| Tipo | Specialità | Schede |\n|---|---|---|\n')
        for (t, s), n in sorted(tipi.items(), key=lambda x: -x[1]):
            f.write(f'| {t} | {s or "—"} | {n} |\n')
        if estese:
            f.write(f'\n## Indirizzi abbreviati scritti per esteso ({len(estese)})\n\nIn Yext la via è abbreviata («V.», «V.le», '
                    '«P.za»): nel dato strutturato e nelle TV si scrive per esteso. Conviene correggerli anche in Yext, perché '
                    'gli stessi valori vanno ai profili Google e a RetailTune.\n\n' + ''.join(f'- {e}\n' for e in estese))
        f.write('\n## Da verificare o correggere in Yext\n\n')
        for k in sorted(note):
            f.write(f'- **{k}**\n' + ''.join(f'  - {n}\n' for n in note[k]))
    print(f'{len(risorse)} schede → {fj}\nreport → {fr}')


if __name__ == '__main__':
    main()
