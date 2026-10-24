<?php
/**
 * listLda — elenco dei PDF delle liste d'attesa per anno (Villa San Giuseppe e simili).
 * Cartella: assets/doc/strutture/<alias della pagina padre>/<alias della pagina>/<anno>/*.pdf
 * Nome dei file: <descrizione>-AAAAMMGG.pdf  →  «Descrizione rilevati il GG/MM/AAAA»
 *
 * KOS-8 (ottobre 2026), rispetto alla versione precedente:
 *   - link con codifica per segmento (rawurlencode): spazi, accenti e apostrofi nei nomi non rompono più l'URL;
 *   - controllo che la pagina padre esista (prima: errore fatale se la pagina era al primo livello);
 *   - escape degli attributi con ENT_QUOTES; data riconosciuta solo se il nome finisce davvero con 8 cifre.
 * Markup invariato.
 */
if (!$modx->resource) return '';
$currentAlias = (string) $modx->resource->get('alias');
$parent       = $modx->getObject(\MODX\Revolution\modResource::class, (int) $modx->resource->get('parent'));
if (!$parent || $currentAlias === '') return '';
$parentAlias  = (string) $parent->get('alias');

$relBase  = 'assets/doc/strutture/' . $parentAlias . '/' . $currentAlias . '/';
$basePath = MODX_BASE_PATH . $relBase;
if (!is_dir($basePath)) return '';

$e   = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$url = fn(string $rel) => '/' . implode('/', array_map('rawurlencode', explode('/', $rel)));

$yearFolders = array_filter(glob($basePath . '*') ?: [], 'is_dir');
if (empty($yearFolders)) return '';
rsort($yearFolders);

$output = '';
foreach ($yearFolders as $yearPath) {
    $yearName = basename($yearPath);
    $files = glob($yearPath . '/*.pdf') ?: [];
    if (empty($files)) continue;
    rsort($files);

    $rows = '';
    foreach ($files as $filePath) {
        $filename      = basename($filePath);
        $filenameNoExt = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/^(.*?)-?(\d{4})(\d{2})(\d{2})$/', $filenameNoExt, $m)) {
            $label = ucfirst(str_replace('-', ' ', $m[1])) . ' rilevati il ' . $m[4] . '/' . $m[3] . '/' . $m[2];
        } else {
            $label = ucfirst(str_replace('-', ' ', $filenameNoExt));
        }
        $fileUrl = $url($relBase . $yearName . '/' . $filename);

        $rows .= '
                <tr>
                    <td class="doc-titolo">' . $e($label) . '</td>
                    <td class="doc-allegato">
                        <a class="btn btn-primary" href="' . $e($fileUrl) . '" target="_blank" rel="noopener">
                            <img src="/assets/img/download.svg" alt="Scarica pdf" width="35" height="35" />
                            <span>PDF</span>
                        </a>
                    </td>
                </tr>';
    }

    $output .= '
        <div class="row">
            <div class="col col-12">
                <div class="titolo-sezione titolo-con-sep text-uppercase">
                    <h2>' . $e($yearName) . '</h2>
                </div>
            </div>
            <div class="col col-lg-8">
                <div class="tabella-documentazione pt-2 pt-md-0">
                    <div class="row box-tab-docs mt-0">
                        <div class="col">
                            <table class="table table-bordered table-striped table-body-distant">
                                <tbody>
                                    ' . $rows . '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}

return $output;
