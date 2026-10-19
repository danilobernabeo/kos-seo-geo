<?php
/**
 * Snippet to get formit forms by name
 * usage:
 * [[!getFormitForm? &name=`Name of form`]]
 */

$id = $modx->resource->get('id');
$page = $modx->getObject('modResource', $id);
$skey = $page->getTVValue('skey');
$alias = $page->get('alias');

if (!isset($_GET['key'])) {
    $modx->sendUnauthorizedPage();
}

$akey = $_GET['key'];

if ($akey != $skey) {
    $modx->sendUnauthorizedPage();
}

use Sterc\FormIt\Model\FormItForm;

$formit = $modx->getService('formit', 'FormIt', $modx->getOption('formit.core_path', null, $modx->getOption('core_path') . 'components/formit/') . 'model/formit/');
$pdo = $modx->getService('pdoTools');

$formName = $modx->getOption('name', $scriptProperties, null);

$q = $modx->newQuery(FormItForm::class);
$q->where(['form' => $formName]);
$total = $modx->getCount(FormItForm::class, $q);
$forms = $modx->getCollection(FormItForm::class, $q);

$i = 0;
$oss = [];

if (!empty($forms)) {
    foreach ($forms as $form) {
        $fields = $form->toArray();

        if ($form->get('encrypted')) {
            $fields['values'] = $form->decrypt($form->get('values'));
        }

        $values = json_decode($fields['values'], true);
        $fields['date'] = date('Y-m-d', $fields['date']);

        $oss[$i]['region']    = $values['region']    ?? '';
        $oss[$i]['date']      = $fields['date'];
        $oss[$i]['surname']   = $values['surname']   ?? '';
        $oss[$i]['name']      = $values['name']      ?? '';
        $oss[$i]['email']     = $values['email']     ?? '';
        $oss[$i]['cellulare'] = $values['cellulare'] ?? '';
        // $oss[$i]['regione']   = $values['regione']   ?? ''; // COLONNA REGIONE DI PREFERENZA DISABILITATA
        $oss[$i]['cv']        = str_replace(
            '/www/assets/components/formit/tmp/',
            'assets/doc/cv/',
            array_values($values['uploadcv'])[count($values['uploadcv']) - 3] ?? ''
        );
        $i++;
    }

    // Deduplica per email
    $tempArr = array_unique(array_column($oss, 'email'));
    $newarr  = array_values(array_intersect_key($oss, $tempArr));
    $count   = count($newarr);

    // -------------------------------------------------------
    // EXPORT XLS
    // -------------------------------------------------------
    if (isset($_GET['export']) && $_GET['export'] === 'xls') {
        $datetime = date('Ymd_His');
        $filename = $alias . '_' . $datetime . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // BOM UTF-8 per caratteri italiani
        echo "\xEF\xBB\xBF";

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
              <head><meta charset="UTF-8"></head><body>';

        echo '<table border="1">';
        echo '<thead><tr>
                <th>Data</th>
                <th>Cognome</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Cellulare</th>
                <!-- <th>Regione di preferenza</th> COLONNA DISABILITATA -->
                <th>CV</th>
              </tr></thead><tbody>';

        foreach ($newarr as $row) {
            $cvUrl = !empty($row['cv'])
                ? $modx->getOption('site_url') . $row['cv']
                : '';

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['date'])      . '</td>';
            echo '<td>' . htmlspecialchars($row['surname'])   . '</td>';
            echo '<td>' . htmlspecialchars($row['name'])      . '</td>';
            echo '<td>' . htmlspecialchars($row['email'])     . '</td>';
            echo '<td>' . htmlspecialchars($row['cellulare']) . '</td>';
            // echo '<td>' . htmlspecialchars($row['regione'])   . '</td>'; // COLONNA DISABILITATA
            echo '<td>' . htmlspecialchars($cvUrl)            . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }

    // -------------------------------------------------------
    // URL export con key preservata
    // -------------------------------------------------------
    $exportUrl = $_SERVER['REQUEST_URI'];
    $separator = strpos($exportUrl, '?') !== false ? '&' : '?';
    $exportUrl .= $separator . 'export=xls';

    // -------------------------------------------------------
    // OUTPUT DASHBOARD
    // -------------------------------------------------------
    echo "<header class='card-header d-flex justify-content-between align-items-center'>
            <h2 class='card-title mb-0'>Report [[*regione]] ($count candidati)</h2>
            <a href='" . htmlspecialchars($exportUrl) . "' 
               class='btn btn-success' 
               title='Scarica elenco in formato Excel'>
                <i class='far fa-file-excel mr-2'></i> Scarica XLS
            </a>
          </header>";

    echo "<div class='card-body'>
            <table class='table table-bordered table-striped mb-0' id='datatable-default'>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>email</th>
                        <th>Cellulare</th>
                        <!-- <th>Regione di preferenza</th> COLONNA DISABILITATA -->
                        <th>CV</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($newarr as $row) {
        $cvUrl = !empty($row['cv'])
            ? $modx->getOption('site_url') . $row['cv']
            : '';

        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['date'])      . '</td>';
        echo '<td>' . htmlspecialchars($row['surname'])   . '</td>';
        echo '<td>' . htmlspecialchars($row['name'])      . '</td>';
        echo '<td>' . htmlspecialchars($row['email'])     . '</td>';
        echo '<td>' . htmlspecialchars($row['cellulare']) . '</td>';
        // echo '<td>' . htmlspecialchars($row['regione'])   . '</td>'; // COLONNA DISABILITATA
        echo '<td>';
        if (!empty($row['cv'])) {
            echo "<a class='mb-1 mt-1 mr-1 btn btn-primary' href='" . rawurlencode($row['cv']) . "' target='_blank'>Scarica CV</a>";
        }
        echo '</td>';
        echo '</tr>';
    }

    echo "      </tbody>
            </table>
          </div>";
}

return;