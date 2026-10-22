<?php
/**
 * mostraFino – restituisce un chunk solo fino alla data indicata (ora di Roma), poi niente.
 *
 * Uso, sempre non in cache (con il punto esclamativo):
 *   [[!mostraFino? &chunk=`nomeChunk` &fino=`2026-11-07 23:59`]]
 *
 * &fino accetta solo due forme:
 *   AAAA-MM-GG HH:MM  il minuto indicato è compreso (23:59 vale fino a 23:59:59);
 *   AAAA-MM-GG        vale fino a 23:59:59 di quel giorno.
 * L'ora è sempre quella di Roma, qualunque sia il fuso del server.
 * Parametri mancanti, date in altri formati o impossibili (31 novembre): non mostra
 * nulla e scrive l'errore nel log di MODX, invece di indovinare una data sbagliata.
 */
$chunk = trim((string) $modx->getOption('chunk', $scriptProperties, ''));
$fino  = trim((string) $modx->getOption('fino', $scriptProperties, ''));
if ($chunk === '' || $fino === '') {
    $modx->log($modx::LOG_LEVEL_ERROR, '[mostraFino] servono &chunk e &fino');
    return '';
}

$tz = new DateTimeZone('Europe/Rome');
$limite = false;
if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fino)) {
    $limite = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $fino, $tz);
    $valida = $limite && $limite->format('Y-m-d H:i') === $fino;
} elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fino)) {
    $limite = DateTimeImmutable::createFromFormat('!Y-m-d', $fino, $tz);
    $valida = $limite && $limite->format('Y-m-d') === $fino;
    if ($valida) $limite = $limite->setTime(23, 59);
} else {
    $valida = false;
}
if (!$valida) {
    $modx->log($modx::LOG_LEVEL_ERROR, '[mostraFino] data non valida in &fino: "' . $fino . '" (formato AAAA-MM-GG HH:MM), chunk ' . $chunk);
    return '';
}
$limite = $limite->setTime((int) $limite->format('H'), (int) $limite->format('i'), 59);

if (new DateTimeImmutable('now', $tz) > $limite) return '';
return (string) $modx->getChunk($chunk, $scriptProperties);