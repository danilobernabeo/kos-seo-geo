<?php
/* ShowEditStatus snippet */

/* Get the current resource as an object */
$doc = $modx->resource;

/* Save some typing */
$config = $scriptProperties;

/* Let the code run in both MODX 2 and MODX 3 */
$classPrefix = $modx->getVersionData()['version'] >= 3
    ? 'MODX\Revolution\\'
    :'';

/* Get the property values from the snippet tag */
$showFullName = $modx->getOption('show_full_name', $config, true);
$showDate = $modx->getOption('show_date', $config, true);
$dateFormat = $modx->getOption('date_format', 
    $config, '%d %B %Y', true);

/* Initialize the output to an empty string */
$output = '';

/* Get the ID of the most recent editor (if any) */
$editorId = $doc->get('editedby');

if ($showFullName && $editorId) {
    $profile = $modx->getObject($classPrefix . 'modUserProfile',
        array('internalKey' => $editorId), false);
    if ($profile) {
        $fullName = $profile->get('fullname');
        if (!empty($fullName)) {
            $output .= "\nEdited by " . $fullName . '';
        }
    }
}

$editedOn = $doc->_fields['editedon'];
if ($showDate && $editedOn) {
        $output .= "\nUltimo aggiornamento: " . 
            strftime($dateFormat, $editedOn) . '';
}

return $output;