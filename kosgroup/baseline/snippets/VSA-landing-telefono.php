<?php
date_default_timezone_set('CET');
$dayofweek = date('w'); // 0=domenica, 1=lunedì, ..., 6=sabato
$now = date('H:i');

// Lunedì - Venerdì: 9:00 - 19:00
if( $dayofweek >= 1 && $dayofweek <= 5 ) {
    if( $now >= '09:00' && $now <= '19:00' ) {
        echo '<p class="h3 text-center mt-5 mb-3">Pronto per iniziare la tua trasformazione?</p>
                <p class="h5 mb-3 text-center">Chiamaci al numero <span style="white-space: preserve nowrap;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone-fill" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/></svg>&nbsp;<a href="tel:+393499371072">349&nbsp;9371072</a><p class="h5 text-center">Il nostro team è qui per ascoltarti, senza giudizio e con la massima riservatezza.</p></span></p><hr class="my-4 text-white"><p class="h3 text-center mt-5">Preferisci che ti richiamiamo noi?</p>';
    }
    else {
        // Fuori orario feriale: solo richiamata
        echo '<p class="h3 text-center mt-5">Vuoi essere ricontattato?</p>';
    }
}
// Sabato e Domenica: 8:30 - 12:00
else if( $dayofweek == 0 || $dayofweek == 6 ) {
    if( $now >= '08:30' && $now <= '12:00' ) {
        echo '<p class="h3 text-center mt-5">Pronto per iniziare la tua trasformazione?</p>
                <p class="h5 mb-5 text-center">Chiamaci al numero <span style="white-space: preserve nowrap;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone-fill" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/></svg>&nbsp;<a href="tel:+393499371072">349&nbsp;9371072</a></span></p><hr class="my-4 text-white"><p class="h3 text-center mt-5">Preferisci che ti richiamiamo noi?</p>';
    }
    else {
        // Fuori orario weekrnd: solo richiamata
        echo '<p class="h3 text-center mt-5">Vuoi essere ricontattato?</p>';
    }
}