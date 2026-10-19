<?php
// FIX: verifica che la TV esista e sia valorizzata prima di formattare
$comunicatoData = $row['tv.comunicatoData'] ?? '';

if (!empty($comunicatoData)) {
    $timestamp = strtotime($comunicatoData);
    // FIX 2: strtotime può restituire false se la stringa non è una data valida
    $row['formatted_date'] = ($timestamp !== false) 
        ? date('d.m.Y', $timestamp) 
        : '';
} else {
    $row['formatted_date'] = '';
}

return $row;