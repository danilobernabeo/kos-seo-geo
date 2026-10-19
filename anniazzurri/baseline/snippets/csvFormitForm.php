<?php
/**
 * Snippet to get formit forms by name
 * usage:
 * [[!getFormitForm? &name=`Name of form`]]
 */

$id = $modx->resource->get('id');
$page = $modx->getObject('modResource', $id);

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
            $fields['fields'] .= "$key: $value";
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
        $i = $i + 1;
    }

    $tempArr = array_unique(array_column($oss, 'email'));
    $newarr = array_intersect_key($oss, $tempArr);
    $count = sizeof($newarr);
    $newarr = array_values($newarr);

    $x = 0;
    $out = '';
    $out = fopen('prova.csv', 'w');

    while($x < $count) {
        fputcsv($out, $newarr[$x]['date']);
        fputcsv($out, $newarr[$x]['surname']);
        fputcsv($out, $newarr[$x]['name']);
        fputcsv($out, $newarr[$x]['email']);
        fputcsv($out, $newarr[$x]['cellulare']);
        $x++;
    }
    fclose($out);
}
return;