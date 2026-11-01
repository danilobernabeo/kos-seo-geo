"""
KOS-8 / AA-3 — Regole di classificazione dei PDF (passo 3 del brief docs/pdf/brief-pdf.md).

Classe e sottotipo si ricavano dalla cartella, dal nome del file e, solo se il nome non basta, dall'inizio del testo
della prima pagina. Le regole stanno qui, in un punto solo, perché la classificazione confermata da Danilo diventa la
regola web `X-Robots-Tag`: ogni riga del foglio dice quale regola l'ha prodotta.

Decisioni proposte:  TIENI (indicizzabile, con titolo nei metadati) · ESCLUDI (noindex) · DECIDI (serve una scelta).
"""
import re
import unicodedata


def norma(s):
    s = unicodedata.normalize('NFKD', s.lower())
    s = ''.join(c for c in s if not unicodedata.combining(c))
    return re.sub(r'[^a-z0-9]+', ' ', s).strip()


# (classe, sottotipo, decisione, motivo, regex sul nome del file normalizzato)
NOME = [
    ('Carta dei servizi', '', 'TIENI', 'nome del file', r'\bcarta (dei|degli|della|delle) (servizi|alloggi)\b|\bservice charter\b'),
    ('Pieghevole', '', 'TIENI', 'nome del file', r'\b(pieghevole|brochure|depliant|opuscolo|leaflet)\b'),
    ('Documenti amministrativi', 'contratti, regolamenti, DGR, relazioni', 'ESCLUDI', 'nome del file', r'\b(dgr|delibera|autorizzazione|accreditamento|contratto|contratti|regolamento|adempimenti|ppsc|piano di azione|igiene delle mani|paa|disciplinare|documentazione|scheda anagrafica|scheda aged|scheda contatto|mod\d+|ufficio pubblica tutela|analisi (di |degli |dei )?(eventi|avventi|categorie)|sintesi clinica|relazione consuntiva|indicatori di qualita|scheda invio)\b'),
    ('Modulo / questionario', 'privacy e consenso', 'ESCLUDI', 'nome del file', r'\b(gdpr|informativa|consenso|privacy|trattamento dei dati)\b'),
    ('Modulo / questionario', 'disposizioni anticipate (DAT)', 'ESCLUDI', 'nome del file', r'\b(disposizioni anticipate|dat)\b'),
    ('Modulo / questionario', 'customer satisfaction', 'ESCLUDI', 'nome del file', r'\b(customer|gradimento|soddisfazione|rilevazione customer)\b'),
    ('Modulo / questionario', 'reclami e segnalazioni', 'ESCLUDI', 'nome del file', r'\b(reclamo|reclami|segnalazione|segnalazioni|ringraziamento)\b'),
    ('Modulo / questionario', 'richiesta ricovero / domanda', 'ESCLUDI', 'nome del file', r'\b(modulo|moduli|scheda prenotazione|prenotazione ricovero|richiesta|domanda|questionario|scheda sanitaria|scheda di invio|scheda di ammissione)\b'),
    ('Risk management e sicurezza delle cure', 'PARM / PARS', 'ESCLUDI', 'nome del file', r'\b(parm|pars)\b'),
    ('Risk management e sicurezza delle cure', 'relazioni e report', 'ESCLUDI', 'nome del file', r'\b(eventi avversi|accadimenti|rischio clinico|sicurezza delle cure|sicurezza cure|report cadute|relazione annuale|relazione sicurezza|gestione rischio|politica salute e sicurezza)\b'),
    ('Trasparenza e amministrazione', 'bilanci', 'ESCLUDI', 'nome del file', r'\b(bilancio|bilanci|nota integrativa|rendicontazione|bilancio di sostenibilita)\b'),
    ('Trasparenza e amministrazione', 'class action', 'ESCLUDI', 'nome del file', r'\bclass action\b'),
    ('Trasparenza e amministrazione', 'registro accessi', 'ESCLUDI', 'nome del file', r'\bregistro degli accessi\b'),
    ('Trasparenza e amministrazione', 'ANAC e attestazioni', 'ESCLUDI', 'nome del file', r'\b(anac|griglia|attestazione|scheda di sintesi|griglia di rilevazione)\b'),
    ('Trasparenza e amministrazione', 'visure, organizzazione, codice etico', 'ESCLUDI', 'nome del file', r'\b(visura|modello organizzativo|codice etico|organigramma|funzionigramma|etico)\b'),
    ('Trasparenza e amministrazione', 'altro', 'ESCLUDI', 'nome del file', r'\b(informativa fea|accessibilita)\b'),
    ('Tempi di attesa e liste', '', 'ESCLUDI', 'nome del file', r'\b(tempi di attesa|lista di attesa|liste di attesa|liste d attesa|attesa)\b'),
    ('Guida ai servizi della struttura', 'guida utente / user manual', 'DECIDI', 'nome del file: guida ai servizi della struttura (spesso la versione inglese), come un pieghevole?', r'\b(user manual|user guide|guida utente|guida ai servizi|manuale|guida)\b'),
    ('Scheda di servizio o percorso', '', 'DECIDI', 'nome del file: descrive un servizio, un percorso o la struttura (come un pieghevole?)', r'\b(ambulatorio|ambulatori|poliambulatorio|percorso|percorsi|ricovero|riabilitazione|uo|uor|check up|malattia|disturbi|disturbo|laserterapia|tecarterapia|colloqui|medicina estetica|team|parco|storia|terapia|tirocini|equipe|attivita|servizi generali|servizi sanitari|servizi sociali|organizzazione|progetto|esercizi|preparazione|infografica|benessere|consigli|cose da verificare|patients and visitors|guide to services|informazioni utili|informazioni|dossier sanitario|istruzioni|come arrivare|centro diurno|cure domiciliari|assistenza domiciliare|home care|open day|alzheimer|open day)\b'),
    ('Newsletter e comunicazione', 'newsletter', 'ESCLUDI', 'nome del file', r'\b(kosnews|newsletter|notiziario)\b'),
    ('Eventi e convegni', '', 'ESCLUDI', 'nome del file', r'\b(programma|convegno|locandina|evento|eventi|corso|seminario|congresso|calendario)\b'),
]
CARTELLA = [
    ('Comunicato stampa', '', 'DECIDI', 'cartella comunicati (decisione del 1/11: proposta di tenerli, da confermare)', r'^assets/doc/comunicati/'),
    ('Newsletter e comunicazione', 'newsletter', 'ESCLUDI', 'cartella newsletter', r'^assets/doc/newsletter/'),
    ('Newsletter e comunicazione', 'news (allegati)', 'ESCLUDI', 'cartella news', r'^assets/doc/news/'),
    ('Eventi e convegni', '', 'ESCLUDI', 'cartella eventi', r'^assets/doc/eventi/'),
    ('Trasparenza e amministrazione', 'società trasparente', 'ESCLUDI', 'cartella società trasparente', r'^assets/doc/societa-trasparente/'),
    ('Trasparenza e amministrazione', 'etica e compliance', 'ESCLUDI', 'cartella etica e compliance', r'^assets/doc/etica-e-compliance/'),
    ('Trasparenza e amministrazione', 'sostenibilità', 'ESCLUDI', 'cartella sostenibilità', r'^assets/doc/sostenibilita/'),
    ('Modulo / questionario', 'moduli', 'ESCLUDI', 'cartella moduli', r'^assets/doc/moduli/'),
    ('Curriculum', '', 'ESCLUDI', 'cartella cv (candidature)', r'^assets/doc/cv/'),
]
TESTO = [   # solo se il nome non basta: prime ~300 caratteri della prima pagina
    ('Carta dei servizi', '', 'TIENI', 'inizio del testo («carta dei servizi»)', r'^.{0,250}\bcarta dei servizi\b'),
    ('Pieghevole', '', 'TIENI', 'inizio del testo («pieghevole»)', r'^.{0,250}\bpieghevole\b'),
    ('Modulo / questionario', 'richiesta ricovero / domanda', 'ESCLUDI', 'inizio del testo (modulo)', r'^.{0,250}\b(modulo|questionario|scheda di)\b'),
]
NON_CLASSIFICATO = ('Altro', 'da classificare', 'DECIDI', 'nessuna regola applicabile')


def classifica(percorso, testo=''):
    rel = percorso.lstrip('/')
    nome = norma(rel.rsplit('/', 1)[-1].rsplit('.', 1)[0])
    for cl, st, dec, mot, rx in CARTELLA:
        if cl in ('Comunicato stampa', 'Curriculum') and re.search(rx, rel):
            return cl, st, dec, mot                   # comunicati e CV: la cartella batte il nome («ritorno alla guida»)
    for cl, st, dec, mot, rx in NOME:                 # per il resto il nome batte la cartella (una carta dei servizi nella cartella news resta una carta)
        if re.search(rx, nome):
            return cl, st, dec, mot
    for cl, st, dec, mot, rx in CARTELLA:
        if re.search(rx, rel):
            return cl, st, dec, mot
    t = norma(testo)[:400]
    for cl, st, dec, mot, rx in TESTO:
        if re.search(rx, t):
            return cl, st, dec, mot
    return NON_CLASSIFICATO
