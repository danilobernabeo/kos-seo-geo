<?php
/* ShowEditStatus snippet - Multilanguage version */
/* Get the current resource as an object */
$doc = $modx->resource;
/* Save some typing */
$config = $scriptProperties;
/* Let the code run in both MODX 2 and MODX 3 */
$classPrefix = $modx->getVersionData()['version'] >= 3
    ? 'MODX\Revolution\\'
    :'';

/* Detect language by finding top-level resource (it or en folder) */
$currentLang = 'it'; // default
$tempResource = $modx->resource;
while ($tempResource) {
    $parentId = $tempResource->get('parent');
    // Se il parent è 0 o 1 (root/home), questa è la cartella lingua
    if ($parentId == 0 || $parentId == 1) {
        $alias = $tempResource->get('alias');
        if ($alias == 'en') {
            $currentLang = 'en';
        }
        break;
    }
    $tempResource = $tempResource->getOne('Parent');
}

/* Set locale based on detected language */
if ($currentLang == 'en') {
    setlocale(LC_TIME, 'en_US.UTF-8', 'en_US', 'en', 'english');
    $modifiedByText = "\nModified by ";
    $lastUpdateText = "\nLast update: ";
} else {
    setlocale(LC_TIME, 'it_IT.UTF-8', 'it_IT', 'it', 'italian');
    $modifiedByText = "\nModificato da ";
    $lastUpdateText = "\nUltimo aggiornamento: ";
}

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
            $output .= $modifiedByText . $fullName . '';
        }
    }
}

$editedOn = $doc->_fields['editedon'];
if ($showDate && $editedOn) {
    $output .= $lastUpdateText . 
        strftime($dateFormat, $editedOn) . '';
}

return $output;