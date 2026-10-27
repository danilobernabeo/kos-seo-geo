<?php
/**
 * newsMubaRisorse — CSS e JavaScript della news Anni Azzurri e MUBA (template interna-articolo-muba).
 *
 *   [[newsMubaRisorse]]      (in cache: MODX conserva i file registrati insieme alla pagina)
 *
 * Il CSS va prima di </head>, il JavaScript prima di </body>; il ?v= lo aggiunge il plugin AssetVersion.
 * Così non serve toccare il chunk head-news, condiviso da tutte le news.
 */
$modx->regClientCSS('/assets/news-muba/news-muba.css');
$modx->regClientScript('/assets/news-muba/news-muba.js');
return '';
