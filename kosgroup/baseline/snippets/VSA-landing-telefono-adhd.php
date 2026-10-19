<?php
date_default_timezone_set('Europe/Rome');

$dayOfWeek = date('w');
$now       = date('H:i');

$showCentralino = (
    ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $now >= '08:30' && $now <= '19:00') ||
    (($dayOfWeek == 0 || $dayOfWeek == 6) && $now >= '08:30' && $now <= '12:30')
);

$centralinoHtml = <<<HTML
<p class="h5 text-center mb-3">
    Contatta il centralino al numero&nbsp;
    <span style="white-space:nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
             class="bi bi-telephone-fill" viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                  d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315
                     1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0
                     .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306
                     1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846
                     1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0
                     1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
        </svg>&nbsp;
        <a href="tel:+390641400356">06 4140 0356</a>
    </span>
</p>
HTML;

if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
    if ($now >= '09:00' && $now <= '19:00') {
        echo '
        <p class="h3 text-center mt-5 mb-3">Hai domande sul percorso diagnostico?</p>
        <p class="h5 mb-3 text-center">
            Chiamaci al numero&nbsp;
            <span style="white-space:nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-telephone-fill" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                          d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315
                             1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0
                             .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306
                             1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846
                             1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0
                             1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
                </svg>&nbsp;
                <a href="tel:+393499371072">349 9371072</a>
            </span>
        </p>'
        . ($showCentralino ? $centralinoHtml : '') . '
        <p class="h5 text-center">
            Il nostro team è qui per ascoltarti, senza giudizio e con la massima riservatezza.
        </p>
        <hr class="my-4 text-white">
        <p class="h3 text-center mt-5">Preferisci che ti richiamiamo noi?</p>';
    } else {
        echo '<p class="h3 text-center mt-5">Lasciaci i tuoi dati: ti richiamiamo al primo orario disponibile</p>';
    }
} else {
    if ($now >= '08:30' && $now <= '12:00') {
        echo '
        <p class="h3 text-center mt-5 mb-3">Hai domande sul percorso diagnostico?</p>
        <p class="h5 mb-3 text-center">
            Chiamaci al numero&nbsp;
            <span style="white-space:nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-telephone-fill" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                          d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315
                             1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0
                             .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306
                             1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846
                             1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0
                             1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
                </svg>&nbsp;
                <a href="tel:+393499371072">349 9371072</a>
            </span>
        </p>'
        . ($showCentralino ? $centralinoHtml : '') . '
        <hr class="my-4 text-white">
        <p class="h3 text-center mt-5">Preferisci che ti richiamiamo noi?</p>';
    } else {
        echo '<p class="h3 text-center mt-5">Lasciaci i tuoi dati: ti richiamiamo al primo orario disponibile</p>';
    }
}