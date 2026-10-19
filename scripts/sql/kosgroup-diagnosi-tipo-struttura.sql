-- =============================================================================
-- kosgroup.com - Diagnosi: tipo_struttura non salvata su 4 strutture
-- Casa Cima (169), Casa Pero (171), Corte Carcare (197), Corte Orero (198)
-- Confronto con strutture dove il valore resta: Casa Mioglia (170),
-- Casa Sanremo (173), Casa Villa Maura (175).
-- D1-D5 sono in sola lettura. Il passo T (test di scrittura) modifica il
-- database: eseguirlo solo dopo aver letto i risultati di D1-D5.
-- =============================================================================

-- D1 · Valore salvato oggi e ultima modifica della risorsa
SELECT r.id, r.pagetitle, r.template, r.published,
       FROM_UNIXTIME(r.editedon) AS modificata_il, r.editedby,
       v.id AS riga_valore, v.value
FROM modx_site_content r
LEFT JOIN modx_site_tmplvar_contentvalues v
       ON v.contentid = r.id
      AND v.tmplvarid = (SELECT id FROM modx_site_tmplvars WHERE name = 'tipo_struttura')
WHERE r.id IN (169, 171, 197, 198, 170, 173, 175)
ORDER BY r.id;

-- D2 · Plugin attivi sugli eventi di salvataggio e di rendering del form
SELECT p.id, p.name, p.disabled, e.event, e.priority
FROM modx_site_plugins p
JOIN modx_site_plugin_events e ON e.pluginid = p.id
WHERE e.event IN ('OnBeforeDocFormSave', 'OnDocFormSave', 'OnDocFormPrerender',
                  'OnDocFormRender', 'OnResourceTVFormPrerender', 'OnResourceTVFormRender',
                  'OnBeforeResourceSave', 'OnResourceSave')
ORDER BY e.event, e.priority;

-- D3 · Regole di Form Customization che toccano tipo_struttura
--      (una TV nascosta o spostata da una regola può non essere inviata al salvataggio)
SELECT d.id, d.`set`, d.action, d.name, d.rule, d.value,
       d.constraint_field, d.`constraint`, d.active,
       s.template AS set_template, s.constraint_field AS set_campo, s.`constraint` AS set_vincolo, s.active AS set_attivo
FROM modx_actiondom d
LEFT JOIN modx_fc_sets s ON s.id = d.`set`
WHERE d.name = CONCAT('tv', (SELECT id FROM modx_site_tmplvars WHERE name = 'tipo_struttura'))
   OR d.name LIKE '%tipo_struttura%';

-- D4 · Restrizioni di accesso alla TV e gruppi di risorse delle strutture
SELECT 'accesso TV' AS controllo, a.tmplvarid AS id, a.documentgroup AS gruppo
FROM modx_site_tmplvar_access a
WHERE a.tmplvarid = (SELECT id FROM modx_site_tmplvars WHERE name = 'tipo_struttura')
UNION ALL
SELECT 'gruppo risorsa', dg.document, dg.document_group
FROM modx_document_groups dg
WHERE dg.document IN (169, 171, 197, 198, 170, 173, 175);

-- D5 · Indice univoco sulla tabella dei valori (serve al passo T)
SHOW INDEX FROM modx_site_tmplvar_contentvalues;


-- -----------------------------------------------------------------------------
-- T · Test di scrittura diretta (MODIFICA il database)
-- Scrive il valore senza passare dal manager. Sostituire il valore se per
-- qualcuna delle quattro la tipologia corretta è diversa.
-- Poi: svuotare la cache di MODX, riaprire la risorsa nel manager e
--   1) verificare che la tipologia compaia;
--   2) salvare la risorsa SENZA modifiche e rilanciare D1.
-- Se dopo il salvataggio il valore sparisce, la causa è nel salvataggio
-- (plugin di D2 o regola di D3), non nel dato.
-- -----------------------------------------------------------------------------
-- INSERT INTO modx_site_tmplvar_contentvalues (tmplvarid, contentid, value)
-- SELECT tv.id, r.id, 'Comunità terapeutiche psichiatriche'
-- FROM modx_site_tmplvars tv
-- JOIN modx_site_content r ON r.id IN (169, 171, 197, 198)
-- WHERE tv.name = 'tipo_struttura'
-- ON DUPLICATE KEY UPDATE value = VALUES(value);
