<?php
/**
 * KOS-8 / AA-3 — Metadati dei PDF da tenere con aggiornamento incrementale: titolo (Info /Title + XMP dc:title) e, se il
 * piano li indica, oggetto (/Subject + dc:description), autore (/Author + dc:creator) e lingua (/Lang del catalogo + dc:language).
 *
 * Il contenuto e i byte originali del file NON cambiano: in coda si aggiungono, come prevede lo standard PDF per gli
 * aggiornamenti incrementali, il dizionario Info e il flusso XMP con i nuovi valori (e, se il catalogo non ha ancora
 * /Metadata o la lingua è diversa, il catalogo aggiornato) più una nuova tabella di riferimenti. Ogni file scritto viene riletto e confrontato:
 * i byte originali devono essere un prefisso identico del nuovo file e il titolo deve risultare nei due punti.
 * Senza dipendenze (solo PHP): non serve pdftotext, qpdf o Python sul cloud.
 *
 * Uso:   php pdf-metadati.php <webroot> <piano.json> [--scrivi] [--solo=<testo nel percorso>] [--senza-comunicati] [--backup=<cartella>]
 *        php pdf-metadati.php <webroot> --ripristina=<cartella di backup>
 *   Senza --scrivi è una PROVA: non scrive nulla, elenca per ogni file titolo attuale → nuovo e gli eventuali problemi.
 *   --scrivi: copia ogni originale in <backup> (default ~/backup-pdf-metadati-AAAAMMGG, stesso percorso relativo) con
 *   il manifest (md5 prima/dopo), poi scrive il file nuovo in modo atomico (file temporaneo + rename).
 *   --ripristina: rimette i file dal backup, verificando l'md5 «dopo» del manifest (non sovrascrive file modificati
 *   nel frattempo, salvo --forza).
 * Piano: {"sito":"aa","default":{"a":"Autore","l":"it-IT"},"pdf":[{"p":"/assets/doc/...pdf","t":"Titolo","d":"Oggetto (max 150)","a":"Autore","l":"it-IT"},
 *          {"p":"/assets/doc/comunicati/2025/x.pdf","tipo":"comunicato"}]}   (d, a, l facoltativi; «default» vale per le voci che non li hanno;
 *   senza questi campi il comportamento è quello di sempre: solo il titolo.)
 *   Il backup tiene sempre il PRIMO originale di un percorso: una riscrittura dello stesso file non lo sovrascrive; un file
 *   sostituito dopo una scrittura (nuovo caricamento) ha una sua copia «<nome>.<md5>.orig» nel backup.
 *   tipo «comunicato»: il titolo è «Comunicato stampa – <titolo della risorsa> – <mese anno>», letto dal database MODX
 *   (TV comunicatoPdf) e mostrato nella prova per il controllo.
 * File che si saltano (e si elencano): cifrati, struttura non leggibile, filtri diversi da Flate nelle parti da leggere,
 * catalogo in sola forma compressa senza /Metadata e non riscrivibile.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
ini_set('memory_limit', '1024M');

/* ============================ lettura minima della struttura PDF ============================ */

final class Pdf
{
    public string $b;
    public int $len;
    /** @var array<int,array{0:int,1:int,2:int}> numero oggetto → [tipo, a, b] (1: offset, generazione; 2: flusso oggetti, indice) */
    public array $xref = [];
    public string $trailer = '';
    public string $ultimoTipo = 'table';
    public int $ultimoStartxref = 0;
    private array $cacheObjstm = [];

    public function __construct(string $bytes)
    {
        $this->b = $bytes;
        $this->len = strlen($bytes);
        $this->leggiXref();
    }

    private function leggiXref(): void
    {
        $coda = substr($this->b, max(0, $this->len - 2048));
        if (!preg_match_all('/startxref\s+(\d+)\s*(?:%%EOF)?/', $coda, $m) || !$m[1]) throw new RuntimeException('startxref non trovato');
        $pos = (int) end($m[1]);
        $this->ultimoStartxref = $pos;
        $primo = true;
        $visti = [];
        while ($pos > 0 && $pos < $this->len) {
            if (isset($visti[$pos])) break;
            $visti[$pos] = true;
            $dict = '';
            if (preg_match('/\Gxref\s/', $this->b, $mm, 0, $pos)) {
                $tipo = 'table';
                $p = $pos + strlen($mm[0]) - 1;
                while (true) {
                    if (!preg_match('/\G\s*(\d+)\s+(\d+)[ \t]*\r?\n?/', $this->b, $h, 0, $p)) break;
                    $p += strlen($h[0]);
                    $inizio = (int) $h[1];
                    $n = (int) $h[2];
                    for ($i = 0; $i < $n; $i++) {
                        if (!preg_match('/\G\s*(\d{10})\s+(\d{5})\s+([nf])/', $this->b, $e, 0, $p)) throw new RuntimeException('voce xref non valida');
                        $p += strlen($e[0]);
                        $num = $inizio + $i;
                        if ($e[3] === 'n' && !isset($this->xref[$num])) $this->xref[$num] = [1, (int) $e[1], (int) $e[2]];
                        elseif ($e[3] === 'f' && !isset($this->xref[$num])) $this->xref[$num] = [0, 0, 0];
                    }
                }
                if (!preg_match('/\G\s*trailer\s*/', $this->b, $t, 0, $p)) throw new RuntimeException('trailer non trovato');
                $dict = self::dizionario($this->b, $p + strlen($t[0]));
                if (preg_match('#/XRefStm\s+(\d+)#', $dict, $xs)) $this->leggiStreamXref((int) $xs[1], false);
            } else {
                $tipo = 'stream';
                $dict = $this->leggiStreamXref($pos, true);
            }
            if ($primo) { $this->trailer = $dict; $this->ultimoTipo = $tipo; $primo = false; }
            $pos = preg_match('#/Prev\s+(\d+)#', $dict, $pv) ? (int) $pv[1] : 0;
        }
        if ($this->trailer === '') throw new RuntimeException('trailer vuoto');
    }

    /** Legge un flusso /Type /XRef alla posizione $pos, riempie $xref, restituisce il dizionario. */
    private function leggiStreamXref(int $pos, bool $principale): string
    {
        if (!preg_match('/\G(\d+)\s+(\d+)\s+obj\s*/', $this->b, $h, 0, $pos)) throw new RuntimeException('flusso xref non valido');
        $dict = self::dizionario($this->b, $pos + strlen($h[0]));
        $dati = $this->datiFlusso($pos + strlen($h[0]) + strlen($dict), $dict);
        if (!preg_match('#/W\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s*\]#', $dict, $w)) throw new RuntimeException('/W mancante');
        [$w1, $w2, $w3] = [(int) $w[1], (int) $w[2], (int) $w[3]];
        $size = preg_match('#/Size\s+(\d+)#', $dict, $s) ? (int) $s[1] : 0;
        $indice = [0, $size];
        if (preg_match('#/Index\s*\[([\d\s]+)\]#', $dict, $ix)) $indice = array_map('intval', preg_split('/\s+/', trim($ix[1])));
        $riga = $w1 + $w2 + $w3;
        $off = 0;
        for ($k = 0; $k + 1 < count($indice); $k += 2) {
            for ($i = 0; $i < $indice[$k + 1]; $i++) {
                $num = $indice[$k] + $i;
                $r = substr($dati, $off, $riga);
                if (strlen($r) < $riga) break 2;
                $off += $riga;
                $t = $w1 ? self::num(substr($r, 0, $w1)) : 1;
                $a = self::num(substr($r, $w1, $w2));
                $c = self::num(substr($r, $w1 + $w2, $w3));
                if (!isset($this->xref[$num])) $this->xref[$num] = $t === 1 ? [1, $a, $c] : ($t === 2 ? [2, $a, $c] : [0, 0, 0]);
            }
        }
        return $dict;
    }

    private static function num(string $s): int
    {
        $v = 0;
        for ($i = 0, $n = strlen($s); $i < $n; $i++) $v = ($v << 8) | ord($s[$i]);
        return $v;
    }

    /** Dati decodificati del flusso che inizia dopo il dizionario (posizione $dopoDict). */
    public function datiFlusso(int $dopoDict, string $dict): string
    {
        if (!preg_match('/\G\s*stream(\r\n|\n|\r)/', $this->b, $m, 0, $dopoDict)) throw new RuntimeException('stream non trovato');
        $ini = $dopoDict + strlen($m[0]);
        if (preg_match('#/Length\s+(\d+)(?!\s+\d+\s+R)#', $dict, $l)) {
            $raw = substr($this->b, $ini, (int) $l[1]);
        } else {
            $fine = strpos($this->b, 'endstream', $ini);
            if ($fine === false) throw new RuntimeException('endstream non trovato');
            $raw = rtrim(substr($this->b, $ini, $fine - $ini), "\r\n");
        }
        if (preg_match('#/Filter\s*(\[[^\]]*\]|/\w+)#', $dict, $f)) {
            if (!preg_match('#^\s*\[?\s*/FlateDecode\s*\]?\s*$#', $f[1])) throw new RuntimeException('filtro ' . $f[1] . ' non gestito');
            $d = @gzuncompress($raw);
            if ($d === false) $d = @gzinflate(substr($raw, 2));
            if ($d === false) throw new RuntimeException('decompressione non riuscita');
            if (preg_match('#/Predictor\s+(\d+)#', $dict, $p) && (int) $p[1] >= 10) {
                $cols = preg_match('#/Columns\s+(\d+)#', $dict, $c) ? (int) $c[1] : 1;
                $d = self::predittorePng($d, $cols);
            }
            return $d;
        }
        return $raw;
    }

    private static function predittorePng(string $d, int $cols): string
    {
        $out = '';
        $prev = str_repeat("\0", $cols);
        $n = intdiv(strlen($d), $cols + 1);
        for ($r = 0; $r < $n; $r++) {
            $f = ord($d[$r * ($cols + 1)]);
            $row = substr($d, $r * ($cols + 1) + 1, $cols);
            $cur = '';
            for ($i = 0; $i < $cols; $i++) {
                $x = ord($row[$i]);
                $a = $i ? ord($cur[$i - 1]) : 0;
                $b = ord($prev[$i]);
                $c = $i ? ord($prev[$i - 1]) : 0;
                switch ($f) {
                    case 0: $v = $x; break;
                    case 1: $v = $x + $a; break;
                    case 2: $v = $x + $b; break;
                    case 3: $v = $x + intdiv($a + $b, 2); break;
                    case 4:
                        $p = $a + $b - $c; $pa = abs($p - $a); $pb = abs($p - $b); $pc = abs($p - $c);
                        $v = $x + ($pa <= $pb && $pa <= $pc ? $a : ($pb <= $pc ? $b : $c)); break;
                    default: throw new RuntimeException('predittore PNG ' . $f);
                }
                $cur .= chr($v & 255);
            }
            $out .= $cur;
            $prev = $cur;
        }
        return $out;
    }

    /** Dizionario << ... >> che inizia alla prima «<<» a partire da $pos (conta annidamenti, stringhe e stringhe esadecimali). */
    public static function dizionario(string $b, int $pos): string
    {
        $i = strpos($b, '<<', $pos);
        if ($i === false) throw new RuntimeException('dizionario non trovato');
        $liv = 0; $n = strlen($b); $j = $i;
        while ($j < $n) {
            $c = $b[$j];
            if ($c === '<' && ($b[$j + 1] ?? '') === '<') { $liv++; $j += 2; continue; }
            if ($c === '>' && ($b[$j + 1] ?? '') === '>') { $liv--; $j += 2; if ($liv === 0) return substr($b, $i, $j - $i); continue; }
            if ($c === '(') { $j = self::fineStringa($b, $j); continue; }
            if ($c === '<') { $k = strpos($b, '>', $j); $j = $k === false ? $n : $k + 1; continue; }
            if ($c === '%') { $k = strcspn($b, "\r\n", $j); $j += $k + 1; continue; }
            $j++;
        }
        throw new RuntimeException('dizionario non chiuso');
    }

    public static function fineStringa(string $b, int $j): int
    {
        $liv = 0; $n = strlen($b);
        for (; $j < $n; $j++) {
            $c = $b[$j];
            if ($c === '\\') { $j++; continue; }
            if ($c === '(') $liv++;
            elseif ($c === ')') { $liv--; if ($liv === 0) return $j + 1; }
        }
        return $n;
    }

    /** Testo dell'oggetto $num (da «num gen obj» a «endobj», senza intestazione) e, se presente, i dati del flusso. */
    public function oggetto(int $num): ?array
    {
        $e = $this->xref[$num] ?? null;
        if (!$e || $e[0] === 0) return null;
        if ($e[0] === 1) {
            if (!preg_match('/\G' . $num . '\s+(\d+)\s+obj\s*/', $this->b, $h, 0, $e[1])) throw new RuntimeException("oggetto $num non trovato all'offset " . $e[1]);
            $ini = $e[1] + strlen($h[0]);
            $fine = $this->fineOggetto($ini);
            $corpo = substr($this->b, $ini, $fine - $ini);
            $dict = null; $dati = null;
            if (strncmp($corpo, '<<', 2) === 0) {
                $dict = self::dizionario($this->b, $ini);
                if (preg_match('/\G\s*stream(\r\n|\n|\r)/', $this->b, $s, 0, $ini + strlen($dict))) $dati = $this->datiFlusso($ini + strlen($dict), $dict);
            }
            return ['testo' => $dict ?? trim($corpo), 'dati' => $dati, 'gen' => $e[2], 'dentro' => false];
        }
        // oggetto in un flusso di oggetti
        [$stm, $idx] = [$e[1], $e[2]];
        if (!isset($this->cacheObjstm[$stm])) {
            $o = $this->oggetto($stm);
            if (!$o || $o['dati'] === null) throw new RuntimeException("flusso oggetti $stm illeggibile");
            preg_match('#/N\s+(\d+)#', $o['testo'], $n);
            preg_match('#/First\s+(\d+)#', $o['testo'], $f);
            $coppie = array_map('intval', preg_split('/\s+/', trim(substr($o['dati'], 0, (int) $f[1]))));
            $this->cacheObjstm[$stm] = [$o['dati'], (int) $f[1], $coppie, (int) $n[1]];
        }
        [$d, $first, $coppie, $n] = $this->cacheObjstm[$stm];
        $pos = null; $next = strlen($d) - $first;
        for ($k = 0; $k < $n; $k++) {
            if ($coppie[$k * 2] === $num) { $pos = $coppie[$k * 2 + 1]; $next = isset($coppie[($k + 1) * 2 + 1]) ? $coppie[($k + 1) * 2 + 1] : strlen($d) - $first; break; }
        }
        if ($pos === null) return null;
        return ['testo' => trim(substr($d, $first + $pos, $next - $pos)), 'dati' => null, 'gen' => 0, 'dentro' => true];
    }

    private function fineOggetto(int $ini): int
    {
        // salta eventuali flussi: cerca «endobj» dopo l'eventuale «endstream»
        $s = strpos($this->b, 'stream', $ini);
        $e = strpos($this->b, 'endobj', $ini);
        if ($e === false) throw new RuntimeException('endobj non trovato');
        if ($s !== false && $s < $e) {
            $es = strpos($this->b, 'endstream', $s);
            if ($es !== false) { $e2 = strpos($this->b, 'endobj', $es); if ($e2 !== false) return $e2; }
        }
        return $e;
    }
}

/* ============================ scrittura dei metadati ============================ */

function esadecimale16(string $utf8): string
{
    return '<FEFF' . strtoupper(bin2hex(mb_convert_encoding($utf8, 'UTF-16BE', 'UTF-8'))) . '>';
}

/** Decodifica una stringa PDF «(...)» o «<...>» (UTF-16BE con BOM, UTF-8 con BOM o PDFDocEncoding≈Latin-1) in UTF-8. */
function decodificaStringa(?string $s): ?string
{
    if ($s === null) return null;
    if ($s[0] === '<') {
        $raw = hex2bin(preg_replace('/[^0-9A-Fa-f]/', '', substr($s, 1, -1)) . (strlen(preg_replace('/[^0-9A-Fa-f]/', '', $s)) % 2 ? '0' : '')) ?: '';
    } else {
        $raw = preg_replace_callback('/\\\\([0-7]{1,3}|.)/s', function ($m) {
            $c = $m[1];
            if (ctype_digit($c[0])) return chr(octdec($c) & 255);
            return ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0c"][$c] ?? $c;
        }, substr($s, 1, -1));
    }
    if (strncmp($raw, "\xFE\xFF", 2) === 0) return mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) return substr($raw, 3);
    return mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
}

/**
 * Sostituisce o inserisce la chiave $chiave (senza «/») al livello superiore di un dizionario PDF.
 * $valore è già in sintassi PDF (stringa esadecimale UTF-16, stringa letterale, ...). I sotto-dizionari non si toccano.
 */
function dizionarioConChiave(string $dict, string $chiave, string $valore): string
{
    $corpo = substr($dict, 2, -2);
    $nuovo = '/' . $chiave . ' ' . $valore;
    $n = strlen($corpo); $i = 0; $out = ''; $fatto = false; $liv = 0;
    while ($i < $n) {
        $c = $corpo[$i];
        if ($liv === 0 && $c === '/' && preg_match('#\G/' . preg_quote($chiave, '#') . '(?=[\s/<(\[]|$)#', $corpo, $m, 0, $i)) {
            $j = $i + strlen($m[0]);
            while ($j < $n && ctype_space($corpo[$j])) $j++;
            if ($j < $n && $corpo[$j] === '(') $j = Pdf::fineStringa($corpo, $j);
            elseif ($j < $n && $corpo[$j] === '<') { $k = strpos($corpo, '>', $j); $j = $k === false ? $n : $k + 1; }
            elseif (preg_match('#\G\d+\s+\d+\s+R#', $corpo, $r, 0, $j)) $j += strlen($r[0]);
            elseif (preg_match('#\G/[^\s/<>(\[\]]*#', $corpo, $r, 0, $j)) $j += strlen($r[0]);
            else { $j += strcspn($corpo, "/\r\n", $j); }
            $out .= $nuovo; $i = $j; $fatto = true; continue;
        }
        if ($c === '(') { $j = Pdf::fineStringa($corpo, $i); $out .= substr($corpo, $i, $j - $i); $i = $j; continue; }
        if ($c === '<' && ($corpo[$i + 1] ?? '') === '<') { $liv++; $out .= '<<'; $i += 2; continue; }
        if ($c === '>' && ($corpo[$i + 1] ?? '') === '>') { $liv--; $out .= '>>'; $i += 2; continue; }
        if ($c === '<') { $k = strpos($corpo, '>', $i); $k = $k === false ? $n : $k + 1; $out .= substr($corpo, $i, $k - $i); $i = $k; continue; }
        $out .= $corpo[$i++];
    }
    if (!$fatto) $out = $nuovo . "\n" . $out;
    return '<<' . $out . '>>';
}

/** Valore (UTF-8) di una chiave stringa di un dizionario, o null. */
function valoreChiave(?string $dict, string $chiave): ?string
{
    if ($dict === null || !preg_match('#/' . preg_quote($chiave, '#') . '\s*(\((?:[^()\\\\]|\\\\.)*\)|<[0-9A-Fa-f\s]*>)#s', $dict, $m)) return null;
    return decodificaStringa($m[1]);
}

/** Campi del piano → chiavi del dizionario Info (la lingua sta nel catalogo, non in Info). */
const CAMPI_INFO = ['t' => 'Title', 'd' => 'Subject', 'a' => 'Author'];
const LIMITE_TITOLO = 65;
const LIMITE_OGGETTO = 150;

/** Elementi XMP (dc:*) dei campi presenti: titolo e oggetto come Alt, autore come Seq, lingua come Bag. */
function xmpElementi(array $campi): array
{
    $x = fn(string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $e = [];
    if (isset($campi['t'])) $e['dc:title'] = '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">' . $x($campi['t']) . '</rdf:li></rdf:Alt></dc:title>';
    if (isset($campi['d'])) $e['dc:description'] = '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">' . $x($campi['d']) . '</rdf:li></rdf:Alt></dc:description>';
    if (isset($campi['a'])) $e['dc:creator'] = '<dc:creator><rdf:Seq><rdf:li>' . $x($campi['a']) . '</rdf:li></rdf:Seq></dc:creator>';
    if (isset($campi['l'])) $e['dc:language'] = '<dc:language><rdf:Bag><rdf:li>' . $x($campi['l']) . '</rdf:li></rdf:Bag></dc:language>';
    return $e;
}

/** Primo valore di un elemento XMP dc:* (primo rdf:li), o null. */
function xmpValore(?string $xmp, string $tag): ?string
{
    if ($xmp === null || !preg_match('#<' . preg_quote($tag, '#') . '\b[^>]*>.*?<rdf:li[^>]*>(.*?)</rdf:li>#s', $xmp, $m)) return null;
    return html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function xmpConCampi(?string $xmp, array $campi): string
{
    $elementi = xmpElementi($campi);
    $dcNs = 'xmlns:dc="http://purl.org/dc/elements/1.1/"';
    if ($xmp === null || trim($xmp) === '' || !str_contains($xmp, 'rdf:RDF')) {
        return '<?xpacket begin="' . "\xEF\xBB\xBF" . '" id="W5M0MpCehiHzreSzNTczkc9d"?>' . "\n"
            . '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            . '<rdf:Description rdf:about="" ' . $dcNs . '>' . implode('', $elementi) . '</rdf:Description></rdf:RDF></x:xmpmeta>' . "\n" . '<?xpacket end="w"?>';
    }
    foreach ($elementi as $tag => $el) {
        $q = preg_quote($tag, '#');
        if (preg_match('#<' . $q . '\b[^>]*?/>#s', $xmp)) {
            $xmp = preg_replace_callback('#<' . $q . '\b[^>]*?/>#s', fn() => $el, $xmp, 1);
        } elseif (preg_match('#<' . $q . '\b[^>]*>.*?</' . $q . '>#s', $xmp)) {
            $xmp = preg_replace_callback('#<' . $q . '\b[^>]*>.*?</' . $q . '>#s', fn() => $el, $xmp, 1);
        } else {
            // l'elemento può essere un attributo di rdf:Description: lo si toglie e si inserisce l'elemento
            $xmp = preg_replace('#\s' . $q . '="[^"]*"#', '', $xmp, 1);
            if (preg_match('#<rdf:Description\b[^>]*?(/?)>#s', $xmp, $m, PREG_OFFSET_CAPTURE)) {
                $t = $m[0][0]; $p = $m[0][1];
                $nuovoTag = $m[1][0] === '/' ? substr($t, 0, -2) . '>' . $el . '</rdf:Description>' : $t . $el;
                $xmp = substr($xmp, 0, $p) . $nuovoTag . substr($xmp, $p + strlen($t));
            } else {
                throw new RuntimeException('XMP senza rdf:Description');
            }
        }
    }
    if (!preg_match('#xmlns:dc\s*=#', $xmp)) $xmp = preg_replace('#<rdf:Description\b#', '<rdf:Description ' . $dcNs, $xmp, 1);
    return $xmp;
}

/** Valida i campi del piano e li porta in forma normale (stringhe senza spazi doppi). Restituisce [campi, avvisi]. */
function campiDelPiano(array $voce, array $predefiniti, ?string $titolo): array
{
    $c = [];
    if ($titolo !== null && $titolo !== '') $c['t'] = $titolo;
    foreach (['d', 'a', 'l'] as $k) {
        $v = $voce[$k] ?? $predefiniti[$k] ?? null;
        if ($v === null || $v === '') continue;
        $c[$k] = trim((string) preg_replace('/\s+/u', ' ', (string) $v));
    }
    $avvisi = [];
    if (isset($c['t']) && mb_strlen($c['t']) > LIMITE_TITOLO) $avvisi[] = 'titolo >' . LIMITE_TITOLO . ' car.';
    if (isset($c['d']) && mb_strlen($c['d']) > LIMITE_OGGETTO) $avvisi[] = 'oggetto >' . LIMITE_OGGETTO . ' car.';
    if (isset($c['l']) && !preg_match('/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $c['l'])) throw new RuntimeException('lingua non valida: ' . $c['l']);
    return [$c, $avvisi];
}

/** Costruisce il file nuovo (byte originali + aggiornamento incrementale). $campi: t titolo, d oggetto, a autore, l lingua. */
function aggiorna(string $bytes, array $campi): array
{
    $pdf = new Pdf($bytes);
    $tr = $pdf->trailer;
    if (str_contains($tr, '/Encrypt')) throw new RuntimeException('PDF cifrato');
    if (!preg_match('#/Size\s+(\d+)#', $tr, $s)) throw new RuntimeException('/Size mancante');
    if (!preg_match('#/Root\s+(\d+)\s+(\d+)\s+R#', $tr, $r)) throw new RuntimeException('/Root mancante');
    $size = (int) $s[1];
    $rootNum = (int) $r[1];
    $infoNum = preg_match('#/Info\s+(\d+)\s+(\d+)\s+R#', $tr, $i) ? (int) $i[1] : 0;
    $idRaw = preg_match('#/ID\s*\[[^\]]*\]#', $tr, $id) ? $id[0] : '';

    $info = ['attuali' => [], 'xmp' => []];
    $nuovi = [];   // numero → [gen, corpo (testo completo «n g obj ... endobj»)]
    $prossimo = $size;

    // --- Info
    $infoVecchio = null;
    if ($infoNum) {
        $o = $pdf->oggetto($infoNum);
        if ($o && strncmp($o['testo'], '<<', 2) === 0) $infoVecchio = $o['testo'];
    }
    $infoNuovo = $infoVecchio ?? '<<>>';
    foreach (CAMPI_INFO as $k => $chiave) {
        if (!isset($campi[$k])) continue;
        $info['attuali'][$k] = valoreChiave($infoVecchio, $chiave);
        $infoNuovo = dizionarioConChiave($infoNuovo, $chiave, esadecimale16($campi[$k]));
    }
    if (!$infoNum) { $infoNum = $prossimo++; $generaInfo = true; } else { $generaInfo = false; }
    $genInfo = $generaInfo ? 0 : ($pdf->xref[$infoNum][0] === 1 ? $pdf->xref[$infoNum][2] : 0);
    $nuovi[$infoNum] = [$genInfo, $infoNuovo];

    // --- Catalogo e Metadata
    $cat = $pdf->oggetto($rootNum);
    if (!$cat || strncmp($cat['testo'], '<<', 2) !== 0) throw new RuntimeException('catalogo illeggibile');
    $xmpVecchio = null; $metaNum = 0; $genMeta = 0;
    if (preg_match('#/Metadata\s+(\d+)\s+(\d+)\s+R#', $cat['testo'], $mm)) {
        $metaNum = (int) $mm[1];
        $m = $pdf->oggetto($metaNum);
        if ($m && $m['dati'] !== null) $xmpVecchio = $m['dati'];
        elseif ($m) throw new RuntimeException('Metadata senza dati leggibili');
        $genMeta = ($pdf->xref[$metaNum][0] ?? 0) === 1 ? $pdf->xref[$metaNum][2] : 0;
    }
    $xmpNuovo = xmpConCampi($xmpVecchio, $campi);
    foreach (['t' => 'dc:title', 'd' => 'dc:description', 'a' => 'dc:creator', 'l' => 'dc:language'] as $k => $tag) {
        if (isset($campi[$k])) $info['xmp'][$k] = xmpValore($xmpVecchio, $tag);
    }
    $langCatalogo = valoreChiave($cat['testo'], 'Lang');
    if (isset($campi['l'])) $info['attuali']['l'] = $langCatalogo;
    $aPosto = true;
    foreach ($campi as $k => $v) {
        if (isset(CAMPI_INFO[$k]) && ($info['attuali'][$k] ?? null) !== $v) $aPosto = false;
        if (($info['xmp'][$k] ?? null) !== $v) $aPosto = false;
        if ($k === 'l' && $langCatalogo !== $v) $aPosto = false;
    }
    if ($aPosto) { $info['gia_a_posto'] = true; return [$bytes, $info]; }

    // il catalogo si riscrive solo se manca /Metadata o se la lingua è diversa
    $catNuovo = $cat['testo'];
    $riscriviCatalogo = false;
    if (!$metaNum) {
        $metaNum = $prossimo++;
        $catNuovo = '<<' . ' /Metadata ' . $metaNum . ' 0 R ' . substr($catNuovo, 2);
        $riscriviCatalogo = true;
    }
    if (isset($campi['l']) && $langCatalogo !== $campi['l']) {
        $catNuovo = dizionarioConChiave($catNuovo, 'Lang', '(' . $campi['l'] . ')');
        $riscriviCatalogo = true;
    }
    if ($riscriviCatalogo) {
        $genCat = ($pdf->xref[$rootNum][0] ?? 0) === 1 ? $pdf->xref[$rootNum][2] : 0;
        $nuovi[$rootNum] = [$genCat, $catNuovo];
    }
    $nuovi[$metaNum] = [$genMeta, null, $xmpNuovo];

    // --- serializza
    $out = $bytes;
    if (!str_ends_with($out, "\n") && !str_ends_with($out, "\r")) $out .= "\n";
    $offset = [];
    ksort($nuovi);
    foreach ($nuovi as $num => $o) {
        $offset[$num] = strlen($out);
        if (isset($o[2])) {
            $out .= "$num {$o[0]} obj\n<< /Type /Metadata /Subtype /XML /Length " . strlen($o[2]) . " >>\nstream\n" . $o[2] . "\nendstream\nendobj\n";
        } else {
            $out .= "$num {$o[0]} obj\n{$o[1]}\nendobj\n";
        }
    }
    $nuovoSize = $prossimo;
    $infoRef = $infoNum . ' 0 R';
    $startNuovo = strlen($out);
    $prev = $pdf->ultimoStartxref;
    if ($pdf->ultimoTipo === 'table') {
        $x = "xref\n";
        foreach ($offset as $num => $off) {
            $x .= "$num 1\n" . sprintf('%010d %05d n', $off, $nuovi[$num][0]) . " \n";
        }
        $x .= "trailer\n<< /Size $nuovoSize /Root $rootNum 0 R /Info $infoRef /Prev $prev" . ($idRaw ? " $idRaw" : '') . " >>\nstartxref\n$startNuovo\n%%EOF\n";
        $out .= $x;
    } else {
        $xrefNum = $nuovoSize;
        $nuovoSize++;
        $offset[$xrefNum] = $startNuovo;
        $nuovi[$xrefNum] = [0, ''];
        ksort($offset);
        $index = ''; $dati = '';
        foreach ($offset as $num => $off) {
            $index .= "$num 1 ";
            $dati .= chr(1) . pack('N', $off) . pack('n', $nuovi[$num][0]);
        }
        $out .= "$xrefNum 0 obj\n<< /Type /XRef /Size $nuovoSize /W [1 4 2] /Index [" . trim($index) . "] /Root $rootNum 0 R /Info $infoRef /Prev $prev"
            . ($idRaw ? " $idRaw" : '') . ' /Length ' . strlen($dati) . " >>\nstream\n" . $dati . "\nendstream\nendobj\nstartxref\n$startNuovo\n%%EOF\n";
    }
    $info['catalogo_riscritto'] = $riscriviCatalogo;
    return [$out, $info];
}

/** Rilegge il file scritto: prefisso identico e ogni campo richiesto in Info, in XMP e (lingua) nel catalogo. */
function verifica(string $originale, string $nuovo, array $campi): void
{
    if (strncmp($nuovo, $originale, strlen($originale)) !== 0) throw new RuntimeException('i byte originali non sono un prefisso del file nuovo');
    $pdf = new Pdf($nuovo);
    $tr = $pdf->trailer;
    if (!preg_match('#/Info\s+(\d+)\s+\d+\s+R#', $tr, $i)) throw new RuntimeException('verifica: /Info assente');
    $o = $pdf->oggetto((int) $i[1]);
    foreach (CAMPI_INFO as $k => $chiave) {
        if (isset($campi[$k]) && (!$o || !str_contains($o['testo'], '/' . $chiave . ' ' . esadecimale16($campi[$k])))) throw new RuntimeException("verifica: $chiave non presente in Info");
    }
    if (!preg_match('#/Root\s+(\d+)\s+\d+\s+R#', $tr, $r)) throw new RuntimeException('verifica: /Root assente');
    $cat = $pdf->oggetto((int) $r[1]);
    if (!$cat || !preg_match('#/Metadata\s+(\d+)\s+\d+\s+R#', $cat['testo'], $m)) throw new RuntimeException('verifica: /Metadata assente');
    if (isset($campi['l']) && valoreChiave($cat['testo'], 'Lang') !== $campi['l']) throw new RuntimeException('verifica: /Lang non presente nel catalogo');
    $meta = $pdf->oggetto((int) $m[1]);
    if (!$meta || $meta['dati'] === null) throw new RuntimeException('verifica: XMP illeggibile');
    foreach (xmpElementi($campi) as $tag => $el) {
        $k = ['dc:title' => 't', 'dc:description' => 'd', 'dc:creator' => 'a', 'dc:language' => 'l'][$tag];
        if (xmpValore($meta['dati'], $tag) !== $campi[$k]) throw new RuntimeException("verifica: $tag non presente in XMP");
    }
    if (@simplexml_load_string(preg_replace('#<\?xpacket[^>]*\?>#', '', $meta['dati'])) === false) throw new RuntimeException('verifica: XMP non è XML valido');
}

/* ============================ comunicati: titolo dal database MODX ============================ */

function mesiIt(): array
{
    return [1 => 'gennaio', 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile', 5 => 'maggio', 6 => 'giugno', 7 => 'luglio', 8 => 'agosto', 9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre'];
}

function titoliComunicati(string $webroot): array
{
    require $webroot . '/config.core.php';
    require MODX_CORE_PATH . 'vendor/autoload.php';
    $modx = \MODX\Revolution\modX::getInstance();
    $modx->initialize('mgr');
    $db = $modx->pdo;
    $p = $modx->getOption('table_prefix', null, 'modx_');
    $sql = "SELECT v.value, c.id, c.pagetitle, c.publishedon, c.createdon,
                   (SELECT d.value FROM {$p}site_tmplvar_contentvalues d JOIN {$p}site_tmplvars td ON td.id = d.tmplvarid AND td.name = 'comunicatoData'
                     WHERE d.contentid = c.id LIMIT 1) AS data_tv
            FROM {$p}site_tmplvar_contentvalues v
            JOIN {$p}site_tmplvars t ON t.id = v.tmplvarid AND t.name = 'comunicatoPdf'
            JOIN {$p}site_content c ON c.id = v.contentid AND c.deleted = 0";
    $out = [];
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $v = '/' . ltrim(rawurldecode(trim((string) $r['value'])), '/');
        $ts = dataComunicato((string) $r['data_tv']);
        $out[$v] = ['titolo' => trim((string) $r['pagetitle']), 'ts' => $ts, 'data_tv' => (string) $r['data_tv'], 'id' => (int) $r['id']];
    }
    return $out;
}

/** Legge la TV comunicatoData (timestamp, AAAA-MM-GG[ hh:mm[:ss]], GG/MM/AAAA, GG-MM-AAAA, testo con strtotime). 0 se non leggibile. */
function dataComunicato(string $v): int
{
    $v = trim($v);
    if ($v === '') return 0;
    if (preg_match('/^\d{9,11}$/', $v)) return (int) $v;
    if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})#', $v, $m)) return (int) mktime(12, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
    $t = strtotime($v);
    return $t === false ? 0 : $t;
}

function titoloComunicato(array $c): string
{
    if (empty($c['ts'])) return '';
    $m = mesiIt();
    $coda = ' – ' . $m[(int) date('n', $c['ts'])] . ' ' . date('Y', $c['ts']);
    $testa = 'Comunicato stampa – ';
    $arg = trim((string) preg_replace('/\s+/u', ' ', $c['titolo']));
    $max = 65;
    if (mb_strlen($testa . $arg . $coda) <= $max) return $testa . $arg . $coda;
    $spazio = $max - mb_strlen($testa . $coda) - 1;   // 1 = «…»
    $taglio = mb_substr($arg, 0, $spazio);
    if (mb_substr($arg, $spazio, 1) !== ' ' && ($p = mb_strrpos($taglio, ' ')) !== false && $p > 10) $taglio = mb_substr($taglio, 0, $p);
    $taglio = rtrim($taglio, " ,;:–-.\"“”‘’'(");
    foreach ([['“', '”'], ['"', '"'], ['(', ')']] as [$ap, $ch]) {   // virgolette o parentesi rimaste aperte dopo il taglio
        if ($ap === $ch ? substr_count($taglio, $ap) % 2 === 1 : substr_count($taglio, $ap) > substr_count($taglio, $ch)) {
            $q = mb_strrpos($taglio, $ap);
            $taglio = mb_substr($taglio, 0, $q) . mb_substr($taglio, $q + 1);   // si toglie solo il segno rimasto aperto
        }
    }
    return $testa . $taglio . '…' . $coda;
}

/* ============================ comando ============================ */

/* Incluso da un altro script (controllo-pdf-nuovi.php): servono solo la classe e le funzioni, il comando non parte. */
if (realpath($argv[0] ?? '') !== realpath(__FILE__)) return;

$webroot = rtrim((string) ($argv[1] ?? ''), '/');
$opt = [];
$pos = [];
foreach (array_slice($argv, 2) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');

if ($webroot === '' || !is_dir($webroot)) { fwrite(STDERR, "Uso: php pdf-metadati.php <webroot> <piano.json> [--scrivi] [--solo=testo] [--backup=cartella]\n     php pdf-metadati.php <webroot> --ripristina=<cartella backup> [--forza]\n"); exit(1); }

/* ---- ripristino ---- */
if (isset($opt['ripristina'])) {
    $cartella = rtrim((string) $opt['ripristina'], '/');
    $manifest = json_decode((string) @file_get_contents($cartella . '/manifest.json'), true);
    if (!is_array($manifest)) { fwrite(STDERR, "manifest.json non trovato in $cartella\n"); exit(1); }
    $ok = 0; $saltati = 0;
    $fatti = [];   // un percorso con più voci (file sostituito e riscritto) si ripristina una volta sola, dalla voce più recente
    foreach (array_reverse($manifest['file']) as $f) {
        if (isset($fatti[$f['p']])) continue;
        $dest = $webroot . $f['p'];
        $src = $cartella . ($f['copia'] ?? $f['p']);
        if (!is_file($src)) { echo "MANCA nel backup: {$f['p']}\n"; $saltati++; continue; }
        if (md5_file($src) !== $f['md5_prima']) { echo "BACKUP ALTERATO: {$f['p']}\n"; $saltati++; continue; }
        if (is_file($dest) && md5_file($dest) !== $f['md5_dopo'] && !isset($opt['forza'])) { echo "MODIFICATO DOPO IL RILASCIO (usa --forza): {$f['p']}\n"; $saltati++; continue; }
        $fatti[$f['p']] = true;
        if (!isset($opt['scrivi'])) { echo "[prova] ripristinerei {$f['p']}\n"; $ok++; continue; }
        $tmp = $dest . '.ripr.' . getmypid();
        copy($src, $tmp);
        touch($tmp, (int) $f['mtime']);
        rename($tmp, $dest);
        $ok++;
    }
    echo ($ok ? '' : '') . "== Ripristino: $ok file" . (isset($opt['scrivi']) ? ' ripristinati' : ' (prova: aggiungi --scrivi)') . ", $saltati saltati\n";
    exit($saltati ? 2 : 0);
}

/* ---- piano ---- */
$pianoFile = $pos[0] ?? '';
$piano = json_decode((string) @file_get_contents($pianoFile), true);
if (!is_array($piano) || empty($piano['pdf'])) { fwrite(STDERR, "Piano non leggibile: $pianoFile\n"); exit(1); }
$scrivi = isset($opt['scrivi']);
$solo = isset($opt['solo']) && is_string($opt['solo']) ? $opt['solo'] : '';
$backup = isset($opt['backup']) && is_string($opt['backup']) ? rtrim($opt['backup'], '/') : $home . '/backup-pdf-metadati-' . date('Ymd');
$comunicati = null;
$manifest = ['sito' => $piano['sito'] ?? '', 'creato' => date('c'), 'file' => []];
$conteggi = ['da_scrivere' => 0, 'gia_a_posto' => 0, 'scritti' => 0, 'saltati' => 0, 'mancanti' => 0];
$tutti = [];
$predefiniti = is_array($piano['default'] ?? null) ? $piano['default'] : [];
$gruppoManifest = [];
$mf = $backup . '/manifest.json';
$precedente = is_file($mf) ? json_decode((string) file_get_contents($mf), true) : null;
$manifest['file'] = is_array($precedente) ? ($precedente['file'] ?? []) : [];
foreach ($piano['pdf'] as $voce) {
    $p = $voce['p'];
    if ($solo !== '' && !str_contains($p, $solo)) continue;
    if (isset($opt['senza-comunicati']) && ($voce['tipo'] ?? '') === 'comunicato') continue;
    $file = $webroot . $p;
    if (!is_file($file)) { echo "MANCANTE   $p\n"; $conteggi['mancanti']++; continue; }
    $titolo = $voce['t'] ?? null;
    if (($voce['tipo'] ?? '') === 'comunicato') {
        $comunicati ??= titoliComunicati($webroot);
        $c = $comunicati[$p] ?? null;
        if (!$c) { echo "SALTATO    $p — nessuna risorsa MODX con questo PDF nella TV comunicatoPdf\n"; $conteggi['saltati']++; continue; }
        $titolo = titoloComunicato($c);
        if ($titolo === '') { echo "SALTATO    $p — TV comunicatoData vuota o illeggibile («" . $c['data_tv'] . "»)\n"; $conteggi['saltati']++; continue; }
    }
    try {
        [$campi, $avvisi] = campiDelPiano($voce, $predefiniti, $titolo);
    } catch (Throwable $e) {
        echo "SALTATO    $p — " . $e->getMessage() . "\n"; $conteggi['saltati']++; continue;
    }
    if (!$campi) continue;
    $originale = (string) file_get_contents($file);
    try {
        [$nuovo, $info] = aggiorna($originale, $campi);
        if (!empty($info['gia_a_posto'])) { echo "GIÀ A POSTO $p\n"; $conteggi['gia_a_posto']++; continue; }
        verifica($originale, $nuovo, $campi);
    } catch (Throwable $e) {
        echo "SALTATO    $p — " . $e->getMessage() . "\n";
        $conteggi['saltati']++;
        continue;
    }
    $etichette = ['t' => 'titolo', 'd' => 'oggetto', 'a' => 'autore', 'l' => 'lingua'];
    echo ($scrivi ? 'SCRIVO     ' : 'PROVA      ') . $p . "\n";
    foreach ($campi as $k => $v) {
        $att = $info['attuali'][$k] ?? null;
        $lim = ($k === 't' && mb_strlen($v) > LIMITE_TITOLO) ? ' [>' . LIMITE_TITOLO . ' car.]' : (($k === 'd' && mb_strlen($v) > LIMITE_OGGETTO) ? ' [>' . LIMITE_OGGETTO . ' car.]' : '');
        echo '             ' . $etichette[$k] . ': ' . $v . $lim . ($att !== null && $att !== '' && $att !== $v && $k !== 't' ? '   (era: ' . $att . ')' : '') . "\n";
    }
    if (!empty($info['catalogo_riscritto'])) echo "             (catalogo aggiornato)\n";
    $conteggi['da_scrivere']++;
    if (isset($campi['t'])) $tutti[] = $campi['t'];
    if (!$scrivi) continue;
    // backup: il primo originale di un percorso non si sovrascrive mai (una riscrittura dello stesso file tiene l'originale vero)
    $copia = $p;
    $md5Originale = md5($originale);
    $voceMf = null;
    foreach ($manifest['file'] as $k2 => $f) { if ($f['p'] === $p && $f['md5_dopo'] === $md5Originale) $voceMf = $k2; }
    $dest = $backup . $copia;
    if (is_file($dest) && $voceMf === null) { $copia = $p . '.' . substr($md5Originale, 0, 8) . '.orig'; $dest = $backup . $copia; }   // file sostituito dopo una scrittura: altro originale
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
    if ($voceMf === null && !copy($file, $dest)) { echo "ERRORE backup $p\n"; $conteggi['saltati']++; continue; }
    $mtime = filemtime($file);
    $tmp = $file . '.nuovo.' . getmypid();
    if (file_put_contents($tmp, $nuovo) === false) { @unlink($tmp); echo "ERRORE scrittura $p\n"; $conteggi['saltati']++; continue; }
    @chmod($tmp, fileperms($file) & 0777);
    rename($tmp, $file);
    if ($voceMf !== null) {   // riscrittura di un file già scritto: si aggiorna la voce, l'originale resta quello del primo backup
        $manifest['file'][$voceMf]['campi'] = $campi;
        $manifest['file'][$voceMf]['titolo'] = $campi['t'] ?? ($manifest['file'][$voceMf]['titolo'] ?? '');
        $manifest['file'][$voceMf]['md5_dopo'] = md5($nuovo);
        $manifest['file'][$voceMf]['byte_dopo'] = strlen($nuovo);
    } else {
        $manifest['file'][] = ['p' => $p, 'copia' => $copia, 'titolo' => $campi['t'] ?? '', 'campi' => $campi, 'md5_prima' => $md5Originale, 'md5_dopo' => md5($nuovo), 'mtime' => $mtime, 'byte_prima' => strlen($originale), 'byte_dopo' => strlen($nuovo)];
    }
    $conteggi['scritti']++;
}
if ($scrivi && $conteggi['scritti'] > 0) {
    if (!is_dir($backup)) mkdir($backup, 0700, true);
    $manifest['creato'] = is_array($precedente) && !empty($precedente['creato']) ? $precedente['creato'] : $manifest['creato'];
    $manifest['aggiornato'] = date('c');
    file_put_contents($mf, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    echo "== Backup e manifest: $backup\n";
}
$dup = array_keys(array_filter(array_count_values($tutti), fn($n) => $n > 1));
echo sprintf("== %s: %d da scrivere, %d scritti, %d già a posto, %d saltati, %d mancanti%s\n", $scrivi ? 'Scrittura' : 'Prova', $conteggi['da_scrivere'], $conteggi['scritti'], $conteggi['gia_a_posto'], $conteggi['saltati'], $conteggi['mancanti'], $dup ? ', titoli duplicati: ' . count($dup) : '');
exit($conteggi['saltati'] ? 2 : 0);
