<?php
/**
 * Libreria comune per le immagini (AA-1, AA-17; utilizzabile anche su kosgroup.com).
 *
 * Installata in core/components/kosseogeo/Immagini.php (cartella non raggiungibile dal web) e usata da:
 *   - snippet `immagine`: markup <picture> con WebP, ripiego nel formato originale, srcset, width/height e alt;
 *   - plugin `ImmaginiCaricamento` (OnFileManagerUpload): ottimizza le immagini appena caricate e ne crea le varianti;
 *   - script `scripts/immagini/ottimizza-immagini.php`: conversione delle immagini già presenti, con backup e ripristino.
 *
 * Gli originali non si sovrascrivono mai senza copia di backup (tranne le immagini appena caricate dal plugin).
 * Le varianti stanno in assets/cache-img/<percorso sotto assets/>/<nome>-<larghezza>.<jpg|png|webp>.
 * Richiede Imagick con supporto WebP; senza, il markup ricade su un semplice <img> e nulla viene scritto.
 */
declare(strict_types=1);

namespace KosSeoGeo;

class Immagini
{
    public const ESTENSIONI = ['jpg', 'jpeg', 'png'];

    private string $root;
    private array $o;
    private ?bool $ok = null;

    public function __construct(string $webroot, array $opzioni = [])
    {
        $this->root = rtrim($webroot, '/') . '/';
        $this->o = $opzioni + [
            'cache'     => 'assets/cache-img',          // relativo alla radice del sito
            'larghezze' => [480, 800, 1200, 1600, 2400],
            'max_lato'  => 2400,                        // lato massimo dell'originale ottimizzato
            'q_jpg'     => 82,
            'q_webp'    => 80,
        ];
        sort($this->o['larghezze']);
    }

    /** Imagick presente e capace di scrivere WebP. */
    public function disponibile(): bool
    {
        if ($this->ok === null) {
            $this->ok = class_exists(\Imagick::class) && in_array('WEBP', \Imagick::queryFormats('WEBP'), true);
        }
        return $this->ok;
    }

    /**
     * Percorso sul disco di un'immagine del sito a partire da URL assoluto, percorso "/assets/..." o "assets/...".
     * Null se fuori dalla radice, inesistente o di un formato non gestito.
     */
    public function percorso(string $src): ?string
    {
        $src = trim(html_entity_decode($src, ENT_QUOTES, 'UTF-8'));
        if (preg_match('#^(https?:)?//#i', $src)) {
            $src = (string) parse_url($src, PHP_URL_PATH);
        }
        $src = rawurldecode((string) preg_replace('/[?#].*$/', '', $src));
        $rel = ltrim($src, '/');
        if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0")) return null;
        if (!in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), self::ESTENSIONI, true)) return null;
        $file = $this->root . $rel;
        $reale = realpath($file);
        if ($reale === false || !str_starts_with($reale, realpath($this->root) . '/') || !is_file($reale)) return null;
        return $reale;
    }

    /** URL a partire dalla radice del sito ("/assets/..."), con i segmenti codificati. */
    public function url(string $file): string
    {
        $rel = substr($file, strlen(rtrim((string) realpath($this->root), '/')));
        return implode('/', array_map('rawurlencode', explode('/', $rel)));
    }

    /** Apre un'immagine raddrizzata (orientamento EXIF) e in sRGB. Le immagini animate non si toccano. */
    private function apri(string $file): \Imagick
    {
        $im = new \Imagick($file);
        if ($im->getNumberImages() > 1) throw new \RuntimeException('immagine animata o multipagina');
        switch ($im->getImageOrientation()) {
            case \Imagick::ORIENTATION_BOTTOMRIGHT: $im->rotateImage('#000', 180); break;
            case \Imagick::ORIENTATION_RIGHTTOP:    $im->rotateImage('#000', 90);  break;
            case \Imagick::ORIENTATION_LEFTBOTTOM:  $im->rotateImage('#000', -90); break;
        }
        $im->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        if ($im->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            $im->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        return $im;
    }

    /** Imposta formato e compressione e scrive su $dest. */
    private function scrivi(\Imagick $im, string $dest, string $formato, ?int $qualita = null): void
    {
        $im = clone $im;
        $im->stripImage();
        if ($formato === 'png8') {                     // PNG a tavolozza (256 colori): grafiche, mappe, icone
            $im->quantizeImage(256, \Imagick::COLORSPACE_SRGB, 0, false, false);
            $im->setImageFormat('png8');               // scrittura come PNG indicizzato (non truecolor)
            $im->setOption('png:compression-level', '9');
            $formato = 'png8-fatto';
        }
        switch ($formato) {
            case 'webp':
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($this->o['q_webp']);
                $im->setOption('webp:method', '6');
                break;
            case 'png8-fatto':
                break;
            case 'png':
                $im->setImageFormat('png');
                $im->setOption('png:compression-level', '9');
                break;
            default:
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality($qualita ?? $this->o['q_jpg']);
                $im->setInterlaceScheme(\Imagick::INTERLACE_PLANE);   // JPEG progressivo
                $im->setSamplingFactors(['2x2', '1x1', '1x1']);
        }
        $tmp = $dest . '.tmp-' . getmypid();
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        $im->writeImage($tmp);
        $im->clear();
        if (!rename($tmp, $dest)) { @unlink($tmp); throw new \RuntimeException("scrittura fallita: $dest"); }
        @chmod($dest, 0644);
    }

    /**
     * Ottimizza l'originale nello stesso file e con lo stesso URL: lo riduce se supera $maxLato e lo ricomprime.
     * Con $obiettivoKb (es. 200, soglia dell'audit) e risultato ancora più pesante: JPG con qualità 76 e poi 70;
     * PNG convertito a tavolozza di 256 colori se la differenza visiva resta trascurabile (errore RMS < 2%).
     * Lo sostituisce solo se il risultato è più leggero almeno del 10% (3% se serve a scendere sotto l'obiettivo)
     * o se è stato ridotto.
     * $backup: cartella in cui copiare l'originale (stesso percorso relativo) prima di sostituirlo; null = nessuna copia.
     * $applica false = prova: calcola il risultato in un file temporaneo e lo scarta.
     */
    public function ottimizza(string $file, ?string $backup, bool $applica, ?int $maxLato = null, ?int $obiettivoKb = null): array
    {
        $maxLato = $maxLato ?: (int) $this->o['max_lato'];
        $prima = filesize($file);
        $r = ['file' => $file, 'prima' => $prima, 'dopo' => $prima, 'azione' => 'invariato', 'lati' => '', 'nota' => ''];
        try {
            $im = $this->apri($file);
        } catch (\Throwable $e) {
            return ['azione' => 'saltato', 'nota' => $e->getMessage()] + $r;
        }
        $w = $im->getImageWidth(); $h = $im->getImageHeight();
        $r['lati'] = "{$w}x{$h}";
        $ridotto = false;
        if (max($w, $h) > $maxLato) {
            $w > $h ? $im->resizeImage($maxLato, 0, \Imagick::FILTER_LANCZOS, 1) : $im->resizeImage(0, $maxLato, \Imagick::FILTER_LANCZOS, 1);
            $ridotto = true;
            $r['lati'] .= ' → ' . $im->getImageWidth() . 'x' . $im->getImageHeight();
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $png = $ext === 'png';
        $tmp = sys_get_temp_dir() . '/kosseogeo-' . getmypid() . '-' . md5($file) . '.' . $ext;
        $this->scrivi($im, $tmp, $png ? 'png' : 'jpg');
        clearstatcache();
        $dopo = filesize($tmp);
        $obiettivo = $obiettivoKb ? $obiettivoKb * 1024 : null;
        if ($obiettivo && $dopo > $obiettivo) {
            if (!$png) {
                // qualità più bassa solo se raggiunge l'obiettivo o fa risparmiare almeno un quarto del peso
                $base = $dopo;
                foreach ([76, 70] as $q) {
                    $this->scrivi($im, $tmp, 'jpg', $q);
                    clearstatcache();
                    $dopo = filesize($tmp);
                    $r['nota'] = "qualità $q";
                    if ($dopo <= $obiettivo) break;
                }
                if ($dopo > $obiettivo && $dopo > $prima * 0.75) {
                    $this->scrivi($im, $tmp, 'jpg');
                    clearstatcache();
                    $dopo = $base;
                    $r['nota'] = 'qualità ridotta non conveniente';
                }
            } elseif ($im->getImageColors() > 4096) {
                // foto salvata in PNG: la tavolozza a 256 colori creerebbe bande nei cieli e nelle sfumature
                $r['nota'] = 'foto in PNG: nelle pagine va servita in WebP con lo snippet immagine';
            } else {
                $t8 = $tmp . '.png8.png';
                $this->scrivi($im, $t8, 'png8');
                clearstatcache();
                $q8 = new \Imagick($t8);
                $rif = clone $im; $rif->stripImage();
                [, $errore] = $rif->compareImages($q8, \Imagick::METRIC_ROOTMEANSQUAREDERROR);
                $q8->clear(); $rif->clear();
                if ($errore < 0.02 && filesize($t8) < $dopo) {
                    rename($t8, $tmp);
                    clearstatcache();
                    $dopo = filesize($tmp);
                    $r['nota'] = sprintf('256 colori, differenza %.1f%%', $errore * 100);
                } else {
                    @unlink($t8);
                    $r['nota'] = sprintf('256 colori scartati (differenza %.1f%%)', $errore * 100);
                }
            }
        }
        $im->clear();
        $soglia = ($obiettivo && $prima > $obiettivo && $dopo <= $obiettivo) ? 0.97 : 0.9;
        if (!$ridotto && $dopo > $prima * $soglia) {
            @unlink($tmp);
            return ['nota' => trim('già ottimizzata ' . ($r['nota'] ? "({$r['nota']})" : ''))] + $r;
        }
        $r['dopo'] = $dopo;
        $r['azione'] = $ridotto ? 'ridotta' : 'ricompressa';
        if (!$applica) { @unlink($tmp); return $r; }
        if ($backup !== null) {
            $copia = rtrim($backup, '/') . substr($file, strlen(rtrim((string) realpath($this->root), '/')));
            if (!is_dir(dirname($copia))) mkdir(dirname($copia), 0775, true);
            if (!is_file($copia) && !copy($file, $copia)) { @unlink($tmp); throw new \RuntimeException("backup fallito: $copia"); }
            $r['backup'] = $copia;
        }
        if (!rename($tmp, $file) && !(copy($tmp, $file) && unlink($tmp))) throw new \RuntimeException("sostituzione fallita: $file");
        @chmod($file, 0644);
        return $r;
    }

    /** Cartella e radice del nome delle varianti di un originale. */
    private function baseVariante(string $file): string
    {
        $rel = substr($file, strlen(rtrim((string) realpath($this->root), '/')) + 1);
        $rel = preg_replace('#^assets/#', '', $rel);   // assets/img/x.jpg → assets/cache-img/img/x-800.webp
        return $this->root . trim($this->o['cache'], '/') . '/' . preg_replace('/\.[^.\/]+$/', '', $rel);
    }

    /**
     * Varianti per lo srcset: ogni larghezza configurata minore di quella dell'originale, più l'originale stesso
     * (limitato a max_lato), ciascuna nel formato originale e in WebP. Rigenera solo quelle mancanti o più vecchie
     * dell'originale. $genera false = restituisce solo quelle già esistenti.
     * @return array<int, array{w:int,h:int,orig:string,webp:string}> in ordine di larghezza crescente
     */
    public function varianti(string $file, bool $genera = true): array
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpg';
        $base = $this->baseVariante($file);
        $mtime = filemtime($file);
        $dim = @getimagesize($file);
        if (!$dim) return [];
        [$W, $H] = $dim;
        // getimagesize ignora l'orientamento EXIF: per le foto ruotate si scambiano i lati
        if (function_exists('exif_read_data') && $ext === 'jpg') {
            $exif = @exif_read_data($file);
            if (in_array((int) ($exif['Orientation'] ?? 1), [5, 6, 7, 8], true)) [$W, $H] = [$H, $W];
        }
        $top = min($W, (int) $this->o['max_lato']);
        $larghezze = array_values(array_filter($this->o['larghezze'], fn($l) => $l < $top));
        $larghezze[] = $top;
        $out = []; $im = null;
        foreach ($larghezze as $l) {
            $h = (int) round($H * $l / $W);
            $v = ['w' => $l, 'h' => $h, 'orig' => "$base-$l.$ext", 'webp' => "$base-$l.webp"];
            $manca = !is_file($v['orig']) || filemtime($v['orig']) < $mtime || !is_file($v['webp']) || filemtime($v['webp']) < $mtime;
            if ($manca) {
                if (!$genera || !$this->disponibile()) continue;
                try {
                    $im ??= $this->apri($file);
                    $c = clone $im;
                    if ($l < $c->getImageWidth()) $c->resizeImage($l, 0, \Imagick::FILTER_LANCZOS, 1);
                    $v['h'] = $c->getImageHeight();
                    $this->scrivi($c, $v['orig'], $ext);
                    $this->scrivi($c, $v['webp'], 'webp');
                    $c->clear();
                } catch (\Throwable $e) {
                    continue;
                }
            }
            $out[] = $v;
        }
        $im?->clear();
        return $out;
    }

    /**
     * Markup <picture> per un'immagine del sito.
     * Opzioni: alt, sizes (default 100vw), class (sull'<img>), classe_picture, attributi (stringa aggiunta all'<img>),
     * priorita (true: immagine principale sopra la piega → fetchpriority high, niente lazy), differita (default true;
     * false = senza loading="lazy", per i caroselli), genera (default true),
     * src_mobile (immagine diversa sui telefoni, art direction) e media_mobile (default (max-width: 767.98px)).
     */
    public function picture(string $src, array $o = []): string
    {
        $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $alt = $e($o['alt'] ?? '');
        $cls = !empty($o['class']) ? ' class="' . $e($o['class']) . '"' : '';
        $extra = !empty($o['attributi']) ? ' ' . trim((string) $o['attributi']) : '';
        // differita = false: niente loading="lazy" senza alzare la priorità. Serve nei caroselli (Flickity con
        // adaptiveHeight misura le celle all'avvio: un'immagine non ancora caricata vale 0 px e il carosello resta chiuso)
        $carico = !empty($o['priorita']) ? ' fetchpriority="high" decoding="async"'
            : (($o['differita'] ?? true) ? ' loading="lazy" decoding="async"' : ' decoding="async"');
        $file = $this->percorso($src);
        if ($file === null) {
            // immagine esterna o mancante: <img> semplice con percorso assoluto
            $u = preg_match('#^(https?:)?//|^/#i', $src) ? $src : '/' . ltrim($src, '/');
            return '<img src="' . $e($u) . '" alt="' . $alt . '"' . $cls . $carico . $extra . '>';
        }
        $v = $this->varianti($file, $o['genera'] ?? true);
        if (!$v) {
            $dim = @getimagesize($file);
            $wh = $dim ? ' width="' . $dim[0] . '" height="' . $dim[1] . '"' : '';
            return '<img src="' . $e($this->url($file)) . '" alt="' . $alt . '"' . $wh . $cls . $carico . $extra . '>';
        }
        // «naturale»: stessa larghezza intrinseca di un <img> semplice (al massimo quella del file), per le immagini
        // mostrate alla loro dimensione (gallerie, caroselli) e non a tutta larghezza
        $sizes = ($o['sizes'] ?? '100vw') === 'naturale'
            ? '(max-width: ' . end($v)['w'] . 'px) 100vw, ' . end($v)['w'] . 'px'
            : $e($o['sizes'] ?? '100vw');
        $set = fn(string $k) => implode(', ', array_map(fn($x) => $this->url($x[$k]) . ' ' . $x['w'] . 'w', $v));
        $grande = end($v);
        // ripiego: la variante più vicina a 1200 px
        $rip = $v[0];
        foreach ($v as $x) if ($x['w'] <= 1200) $rip = $x;
        $pc = !empty($o['classe_picture']) ? ' class="' . $e($o['classe_picture']) . '"' : '';
        // Art direction: immagine diversa sui telefoni (es. TV imgHeaderMobile), con le sue varianti
        $mobile = '';
        $fm = !empty($o['src_mobile']) ? $this->percorso((string) $o['src_mobile']) : null;
        if ($fm !== null && $fm !== $file && ($vm = $this->varianti($fm, $o['genera'] ?? true))) {
            $media = $e($o['media_mobile'] ?? '(max-width: 767.98px)');
            $setm = fn(string $k) => implode(', ', array_map(fn($x) => $this->url($x[$k]) . ' ' . $x['w'] . 'w', $vm));
            $mobile = '<source media="' . $media . '" type="image/webp" srcset="' . $e($setm('webp')) . '" sizes="100vw">'
                . '<source media="' . $media . '" srcset="' . $e($setm('orig')) . '" sizes="100vw">';
        }
        return '<picture' . $pc . '>' . $mobile
            . '<source type="image/webp" srcset="' . $e($set('webp')) . '" sizes="' . $sizes . '">'
            . '<img src="' . $e($this->url($rip['orig'])) . '" srcset="' . $e($set('orig')) . '" sizes="' . $sizes . '"'
            . ' width="' . $grande['w'] . '" height="' . $grande['h'] . '" alt="' . $alt . '"' . $cls . $carico . $extra . '>'
            . '</picture>';
    }
}
