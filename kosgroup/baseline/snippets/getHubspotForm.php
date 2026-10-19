<?php
// Snippet per gestire il form HubSpot
$defaultFormId = '9217aba0-30e5-4bd3-a99c-5ed6c8c5eade';  // ID del form di default
$formId = $_GET['formId'] ?? $defaultFormId;

return '
<script charset="utf-8" type="text/javascript" src="//js-eu1.hsforms.net/forms/embed/v2.js"></script>
<script>
  hbspt.forms.create({
    region: "eu1",
    portalId: "26644680",
    formId: "' . htmlspecialchars($formId) . '"
  });
</script>
';