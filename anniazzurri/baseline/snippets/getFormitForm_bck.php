<?php
/**
 * Snippet to get formit forms by name
 * usage:
 * [[!getFormitForm? &name=`Name of form`]]
 */

$id = $modx->resource->get('id');
$page = $modx->getObject('modResource', $id);
$skey = $page->getTVValue('skey');

if( !isset($_GET['key']) ) {
  $modx->sendUnauthorizedPage();
}

$akey = $_GET['key'];

if( $akey != $skey ) {
  $modx->sendUnauthorizedPage();
}

use Sterc\FormIt\Model\FormItForm;

// Load formit class
$formit = $modx->getService('formit', 'FormIt', $modx->getOption('formit.core_path', null, $modx->getOption('core_path') . 'components/formit/') . 'model/formit/');

// Load PDO Tools 
$pdo = $modx->getService('pdoTools');

// set snippet props
$formName = $modx->getOption('name', $scriptProperties, null);


// xpdo query to get forms
$q = $modx->newQuery(FormItForm::class);
$q->where([
    'form' => $formName,
]);
$total = $modx->getCount(FormItForm::class, $q);

$forms = $modx->getCollection(FormItForm::class, $q);
$i = 0;
if (!empty($forms)) {
    // Set our inner tpl var
    $dataOutput = '';

    // Loop through each object and tpl up
    foreach ($forms as $form) {

        $fields = $form->toArray();

        // Check if data is encrypted
        if ($form->get('encrypted')) {
            // Form is encrypted, use the objects decrypt method to decyrpt
            $fields['values'] = $form->decrypt($form->get('values'));
        }

        // $fields['values'] contains the JSON array to access pass to json_decode() ie;
        $values = json_decode($fields['values'], true);

        $fields['fields'] = '';
        // then access like: $values['firstName']
        foreach ($values as $key => $value) {
            $fields['fields'] .= "<strong>$key:</strong> $value<br>";
        }

        // To get a link to the CSV try something like
        //$csvFile = str_replace(MODX_BASE_PATH, $modx->getOption('site_url'), $values['uploadcv']);
        //print $csvFile;

        // The above var should now contain something like
        // https://yourdomain.com/assets/components/formit/tmp/6d47ca637e436c5f17a8f744db739b2d-inbound3035221944389664944.pdf

        // Format the date string
        $fields['date'] = date('Y-m-d', $fields['date']);

        $oss [$i]["region"] = $values['region'];
        $oss [$i]["date"] = $fields['date'];
        $oss [$i]["surname"] = $values['surname'];
        $oss [$i]["name"] = $values['name'];
        $oss [$i]["email"] = $values['email'];
        $oss [$i]["cellulare"] = $values['cellulare'];
        $oss [$i]["regione"] = $values['regione'];
        //$oss [$i]["cv"] = str_replace('/www/assets/components/formit/tmp/','assets/doc/cv/',(array_values($values['uploadcv'])[2]));
        $oss [$i]["cv"] = str_replace('/www/assets/components/formit/tmp/','assets/doc/cv/',(array_values($values['uploadcv'])[count($values['uploadcv'])-3]));
        $i = $i + 1;

    }

    $tempArr = array_unique(array_column($oss, 'email'));
    $newarr = array_intersect_key($oss, $tempArr);
    $count = sizeof($newarr);
    $newarr = array_values($newarr);

    $x = 0;
    echo "<header class='card-header'><h2 class='card-title'>Report [[*regione]] ($count candidati)</h2></header>";
    echo "<div class='card-body'><table class='table table-bordered table-striped mb-0' id='datatable-default'><thead><tr><th>Data</th><th>Cognome</th><th>Nome</th><th>email</th><th>Cellulare</th><th>Regione di preferenza</th><th>CV</th></tr></thead>";
    echo "<tbody>";
    
    while($x < $count) {
        echo "<tr>";
        echo "<td>" . $newarr[$x]['date'] . "</td>";
        echo "<td>" . $newarr[$x]['surname'] . "</td>";
        echo "<td>" . $newarr[$x]['name'] . "</td>";
        echo "<td>" . $newarr[$x]['email'] . "</td>";
        echo "<td>" . $newarr[$x]['cellulare'] . "</td>";
        echo "<td>" . $newarr[$x]['regione'] . "</td>";
        echo "<td>";
        if(!empty($newarr[$x]['cv'])) echo "<a type='button' class='mb-1 mt-1 mr-1 btn btn-primary' href='" . rawurlencode(($newarr[$x]['cv'])) . "' target='_blank'>Scarica CV</a>";
        echo "</td>";
        echo "</tr>";
        $x++;
    }
    echo "</tbody></table></div>";
}
return;