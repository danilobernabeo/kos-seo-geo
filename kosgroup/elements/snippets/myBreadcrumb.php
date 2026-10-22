<?php
/**
 * myBreadcrumb — kosgroup.com (voci KOS-12, KOS-13)
 * Briciole visibili richiamate nel contenuto di alcune pagine ([[!myBreadcrumb]]).
 * Ora delegano alla libreria comune: stesso percorso della BreadcrumbList JSON-LD,
 * con la Home in testa e i livelli intermedi cliccabili (audit KosGroup, slide 28-29).
 * Resta come snippet per non modificare il contenuto delle pagine che lo richiamano.
 */
return $modx->runSnippet('kosSchema', ['type' => 'briciole']);
