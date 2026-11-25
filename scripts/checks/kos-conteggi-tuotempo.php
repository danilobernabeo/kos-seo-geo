<?php
/**
 * Conteggi in SOLA LETTURA dei dati TuoTempo usati dalla barra di prenotazione di kosgroup.com, per struttura:
 * risorse prenotabili (kos_medici con almeno una prestazione) e prestazioni prenotabili (con almeno una risorsa).
 * Stampa solo numeri aggregati, nessun nome. Serve all'analisi MioDottore (confronto con le pagine MioDottore).
 *
 * Uso: php scripts/checks/kos-conteggi-tuotempo.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-conteggi-tuotempo.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('web');
$sql = "SELECT s.areaid, s.nome,
  (SELECT COUNT(DISTINCT mp.resourceid) FROM kos_medici_prestazioni mp
     JOIN kos_medici m ON m.resourceid = mp.resourceid AND m.areaid = mp.areaid
     JOIN kos_prestazioni p ON p.activity_id = mp.activity_id AND p.areaid = mp.areaid
    WHERE mp.areaid = s.areaid) AS risorse,
  (SELECT COUNT(DISTINCT p.activity_id) FROM kos_prestazioni p
    WHERE p.areaid = s.areaid AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp
      WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)) AS prestazioni
  FROM kos_strutture s ORDER BY s.nome";
$st = $modx->prepare($sql);
if (!$st || !$st->execute()) { fwrite(STDERR, "Query non riuscita\n"); exit(1); }
echo "areaid;struttura;risorse_prenotabili;prestazioni_prenotabili\n";
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) echo "{$r['areaid']};{$r['nome']};{$r['risorse']};{$r['prestazioni']}\n";
