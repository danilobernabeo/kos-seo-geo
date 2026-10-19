<?php
$currentAlias = $modx->resource->get('alias');
$parentId     = $modx->resource->get('parent');
$parentAlias  = $modx->getObject('modResource', $parentId)->get('alias');

$basePath = MODX_BASE_PATH . 'assets/doc/strutture/' . $parentAlias . '/' . $currentAlias . '/';
$baseUrl  = '/assets/doc/strutture/' . $parentAlias . '/' . $currentAlias . '/';

if (!is_dir($basePath)) return '';

$yearFolders = array_filter(glob($basePath . '*'), 'is_dir');
if (empty($yearFolders)) return '';
rsort($yearFolders);

$output = '';

foreach ($yearFolders as $yearPath) {

    $yearName = basename($yearPath);

    $files = glob($yearPath . '/*.pdf');
    if (empty($files)) continue;
    rsort($files);

    $rows = '';

    foreach ($files as $filePath) {

        $filename      = basename($filePath);
        $filenameNoExt = pathinfo($filename, PATHINFO_FILENAME);

        $dateStr       = substr($filenameNoExt, -8);
        $descPart      = rtrim(substr($filenameNoExt, 0, -8), '-');
        $desc          = ucfirst(str_replace('-', ' ', $descPart));
        $dateFormatted = substr($dateStr, 6, 2) . '/' . substr($dateStr, 4, 2) . '/' . substr($dateStr, 0, 4);
        $label         = $desc . ' rilevati il ' . $dateFormatted;

        $relativePath  = str_replace(MODX_BASE_PATH, '', $filePath);
        $fileUrl       = '/' . ltrim($relativePath, '/');

        $rows .= '
                <tr>
                    <td class="doc-titolo">' . htmlspecialchars($label) . '</td>
                    <td class="doc-allegato">
                        <a class="btn btn-primary" href="' . htmlspecialchars($fileUrl) . '" target="_blank" rel="noopener">
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
                    <h2>' . htmlspecialchars($yearName) . '</h2>
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