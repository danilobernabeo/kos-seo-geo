<?php
// Snippet: getFromPage
if (empty($_GET['formId'])) {
    $modx->sendRedirect($modx->makeUrl(20));
    return;
}

$defaultFromText = 'il nostro sito';

$fromPage = isset($_GET['fromPage']) && trim($_GET['fromPage']) !== ''
    ? htmlspecialchars(urldecode($_GET['fromPage']), ENT_QUOTES, 'UTF-8')
    : $defaultFromText;

return '<strong>' . $fromPage . '</strong>';