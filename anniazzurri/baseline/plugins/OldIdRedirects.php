<?php
// solo in front-end
if ($modx->context->key !== 'web') return;

// se arriva index.php?id=…
if (isset($_GET['id'])) {
    switch ($_GET['id']) {

        case '197':
            $modx->sendRedirect(
                'https://anniazzurri.it/assistenza-e-cure-domiciliari-in-regime-privato',
                ['responseCode' => 'HTTP/1.1 301 Moved Permanently']
            );
            break;

        case '213':
            $modx->sendRedirect(
                'https://anniazzurri.it/assistenza-e-cure-domiciliari-gratuite-con-il-servizio-sanitario',
                ['responseCode' => 'HTTP/1.1 301 Moved Permanently']
            );
            break;
    }
}