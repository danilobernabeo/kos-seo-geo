<?php
/* --------------------------------------------------------------------------
 * 1. Richiamo API + Setup Tracking
 * ------------------------------------------------------------------------ */
$ist = $modx->resource->getTVValue('istanza');

// Dati centro per tracking
$centroAreaid = $ist ?: 'unknown';
$centroNome = $modx->resource->get('pagetitle') ?: 'Centro KOS';

// Debug info (rimuovi in produzione)
echo '<!-- DEBUG: Centro = ' . htmlspecialchars($centroNome) . ', Areaid = ' . htmlspecialchars($centroAreaid) . ' -->';

$ch = curl_init(
    'https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?areaid=' . $ist
);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOSIGNAL       => 1,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FORBID_REUSE   => 1,
    CURLOPT_FRESH_CONNECT  => 1,
    CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_3,
]);
$string      = curl_exec($ch);
$curl_errno  = curl_errno($ch);
$curl_error  = curl_error($ch);
curl_close($ch);

if ($curl_errno > 0) {
    echo "cURL Error ($curl_errno): $curl_error\n";
    return;
}

/* --------------------------------------------------------------------------
 * 2. Raggruppo per branca
 * ------------------------------------------------------------------------ */
$arr    = json_decode($string, true);
$branca = [];
foreach ($arr['return']['results'] as $value) {
    $branca[$value['typologyTitle']][] = $value;
}
ksort($branca);

/* --------------------------------------------------------------------------
 * 3. Markup accordion + tabelle accessibili + TRACKING
 * ------------------------------------------------------------------------ */
echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up">
        <div class="container">
        <div class="col xbox-faq">';

/* ---- Alert tracking attivo
echo '<div class="alert alert-info mb-4" role="alert">';
echo '<strong> TRACKING ATTIVO</strong> - Widget di prenotazione con tracking completo KOS → TuoTempo. ';
echo 'Centro: <strong>' . htmlspecialchars($centroNome) . '</strong> ';
echo '(' . array_sum(array_map('count', $branca)) . ' prestazioni disponibili)';
echo '</div>'; -------------------------------------------- */

/* ---- JS di ricerca + TRACKING COMPLETO -------------------------------- */
// VERSIONE SEMPLIFICATA - prenotaOnline.php
// Sostituisci la sezione JavaScript con questo codice pulito

echo <<<HTML
<script>
/* ==========================================================
   KOS – Widget Prenotazioni (versione robusta + retry _gl)
   ========================================================== */

(function () {
  "use strict";

  /* ====== CONFIG ====== */
  const GA4_MEASUREMENT_ID = "G-LJNBWGX7YN";
  const PORTAL_BASE_URL = "https://app.tuotempo.com/mop/index.php";
  const PORTAL_DBNAME   = "tt_kosgroup_portale";
  const BUTTON_SELECTOR = ".prenota-btn-produzione"; // classe dei bottoni "Prenota"

  /* ====== INIT SAFE ====== */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initKosWidget);
  } else {
    initKosWidget();
  }

  function initKosWidget() {
    console.log("KOS Widget – init (robusto: _gl + kos_session + consent ads)");

    /* ====== HELPER: consenso marketing (Cookiebot) ====== */
    function hasMarketingConsent() {
      try {
        return !!(window.Cookiebot && Cookiebot.consent && Cookiebot.consent.marketing);
      } catch (e) {
        return false;
      }
    }

    /* ====== HELPER: GA4 linker param (_gl) con retry + fallback ====== */
    async function getLinkerParamRetry() {
      const TRIES = 12, DELAY = 100; // ~1.2s max
      function askOnce() {
        return new Promise((resolve) => {
          try {
            if (window.gtag) {
              gtag("get", GA4_MEASUREMENT_ID, "linker_param", function (lp) {
                resolve(lp || "");
              });
            } else {
              resolve("");
            }
          } catch (e) {
            resolve("");
          }
        });
      }
      for (let i = 0; i < TRIES; i++) {
        const lp = await askOnce();
        if (lp) return lp;
        await new Promise(r => setTimeout(r, DELAY));
      }
      // Fallback: se la landing ha già _gl nell'URL
      const fromUrl = new URL(location.href).searchParams.get("_gl") || "";
      if (!fromUrl) console.warn("[KOS] _gl non disponibile: GA4 non pronto o non caricato");
      return fromUrl;
    }

    /* ====== HELPER: ad click ids (URL/cookie) ====== */
    function getAdClickIds() {
      const u = new URL(location.href);
      const ids = {
        gclid:  u.searchParams.get("gclid")  || "",
        gbraid: u.searchParams.get("gbraid") || "",
        wbraid: u.searchParams.get("wbraid") || ""
      };
      // Fallback gclid da cookie _gcl_aw (solo se consenso marketing)
      if (hasMarketingConsent() && !ids.gclid && document.cookie.includes("_gcl_aw=")) {
        try {
          const c = document.cookie.split("; ").find(x => x.startsWith("_gcl_aw=")).split("=")[1];
          const parts = decodeURIComponent(c).split(".");
          ids.gclid = parts[parts.length - 1] || "";
        } catch (e) {}
      }
      return ids;
    }

    /* ====== HELPER: kos_session ====== */
    function ensureKosSession() {
      let sessionId = sessionStorage.getItem("kos_booking_session_id");
      if (!sessionId) {
        sessionId = "kos_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);
        sessionStorage.setItem("kos_booking_session_id", sessionId);
      }
      return sessionId;
    }

    /* ====== DATALAYER PUSH (arricchito) ====== */
    function pushBookingStartToDataLayer(button, sessionId, extras) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: "booking_start",
        // chiave/sessione e contesto
        booking_session_id: sessionId,
        source_type: "widget",
        referrer_page: window.location.pathname,
        // centro & prestazione (dai data-attributes, con fallback)
        centro_areaid: button?.dataset?.centroAreaid || "unknown",
        centro_nome:   button?.dataset?.centroNome   || undefined,
        prestazione_id:      button?.dataset?.prestazioneId      || "unknown",
        prestazione_titolo:  button?.dataset?.prestazioneTitolo  || undefined,
        prestazione_tipologia: button?.dataset?.tipologia        || undefined,
        // advertising ids & linker (se presenti)
        gclid: extras?.gclid || undefined,
        gbraid: extras?.gbraid || undefined,
        wbraid: extras?.wbraid || undefined,
        linker_param: extras?.linker || undefined,
        // device basics
        device_type: /Mobi/i.test(navigator.userAgent) ? "mobile" : "desktop",
        timestamp: new Date().toISOString()
      });
    }

    /* ====== COSTRUISCI URL PORTALE DECORATO ====== */
    async function buildPortalUrl(prestazioneId, centroAreaid) {
      const url = new URL(PORTAL_BASE_URL);
      url.searchParams.set("dbName", PORTAL_DBNAME);
      url.searchParams.set("activityid", prestazioneId || "unknown");
      url.searchParams.set("areaid",    centroAreaid  || "unknown");

      // kos_session sempre presente (riuso se esiste)
      url.searchParams.set("kos_session", ensureKosSession());

      // referrer "grezzo": URLSearchParams fa già encoding corretto (no doppia codifica)
      url.searchParams.set("kos_referrer", window.location.pathname);

      // _gl dal linker GA4 (retry + fallback); aggiungi solo se mancante
      if (!url.searchParams.has("_gl")) {
        const gl = await getLinkerParamRetry();
        if (gl) url.searchParams.set("_gl", gl);
      }

      // ad click ids SOLO con consenso marketing
      if (hasMarketingConsent()) {
        const { gclid, gbraid, wbraid } = getAdClickIds();
        if (gclid  && !url.searchParams.has("gclid"))  url.searchParams.set("gclid",  gclid);
        if (gbraid && !url.searchParams.has("gbraid")) url.searchParams.set("gbraid", gbraid);
        if (wbraid && !url.searchParams.has("wbraid")) url.searchParams.set("wbraid", wbraid);
      }

      return url.toString();
    }

    /* ====== FILTRO PRESTAZIONI (se usato) ====== */
    function cercaPrestazioneTabella(inputId, tableId) {
      const input = document.getElementById(inputId);
      const table = document.getElementById(tableId);
      if (!input || !table) {
        console.log("Elementi di ricerca non trovati:", inputId, tableId);
        return;
      }
      const filter = (input.value || "").toUpperCase();
      const rows = table.getElementsByTagName("tr") || [];
      for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (!row) continue;
        const td = row.getElementsByTagName("td")[0];
        if (!td) continue;
        const txtValue = td.textContent || td.innerText || "";
        row.style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
      }
    }

    /* ====== HANDLER CLICK PRENOTA ====== */
    async function handleBookingClickProduzione(event) {
      const button = event.target.closest(BUTTON_SELECTOR);
      if (!button) return;
      event.preventDefault();

      const prestazioneId = button.dataset.prestazioneId || "unknown";
      const centroAreaid  = button.dataset.centroAreaid  || "unknown";

      // Costruisci URL (con _gl/kos_session/ads ids)
      const finalUrl = await buildPortalUrl(prestazioneId, centroAreaid);

      // Push in dataLayer (includo linker ed ids se presenti in landing)
      const linker = new URL(finalUrl).searchParams.get("_gl") || undefined;
      const { gclid, gbraid, wbraid } = getAdClickIds();
      pushBookingStartToDataLayer(button, sessionStorage.getItem("kos_booking_session_id"), { linker, gclid, gbraid, wbraid });

      // Log e navigazione
      console.log("Widget booking start →", { prestazioneId, centroAreaid, finalUrl });
      setTimeout(function () {
        const target = button.getAttribute("target") || "_self";
        window.open(finalUrl, target);
      }, 120);
    }

    /* ====== ATTACH LISTENER AI BOTTONI ====== */
    function attachEventListeners() {
      const buttons = document.querySelectorAll(BUTTON_SELECTOR);
      let attached = 0;
      buttons.forEach(function (btn) {
        btn.removeEventListener("click", handleBookingClickProduzione);
        btn.addEventListener("click", handleBookingClickProduzione);
        attached++;
      });
      console.log("✅ Listener attaccati a", attached, "bottoni");
      return attached;
    }

    /* ====== RETRY + MUTATION OBSERVER ====== */
    let retryCount = 0;
    const maxRetries = 10; // 10 * 500ms = 5s
    function tryAttachListeners() {
      const attached = attachEventListeners();
      if (attached === 0 && retryCount < maxRetries) {
        retryCount++;
        console.log("Retry", retryCount + "/" + maxRetries, "- bottoni non ancora caricati…");
        setTimeout(tryAttachListeners, 500);
      } else if (attached > 0) {
        console.log("✅ Widget attivo –", attached, "bottoni");
        const observer = new MutationObserver(function (mutations) {
          let needsReattach = false;
          mutations.forEach(function (m) {
            if (m.addedNodes && m.addedNodes.length > 0) {
              for (let i = 0; i < m.addedNodes.length; i++) {
                const node = m.addedNodes[i];
                if (node.nodeType === 1 && (node.matches?.(BUTTON_SELECTOR) || node.querySelector?.(BUTTON_SELECTOR))) {
                  needsReattach = true;
                  break;
                }
              }
            }
          });
          if (needsReattach) {
            console.log("Nuovi bottoni rilevati, ri-attacco listener…");
            attachEventListeners();
          }
        });
        observer.observe(document.body, { childList: true, subtree: true });
      } else {
        console.warn("⚠️ Nessun bottone", BUTTON_SELECTOR, "trovato dopo", maxRetries, "tentativi");
      }
    }

    /* ====== ESPORTA FUNZIONI PER DEBUG (opzionale) ====== */
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
    window.handleBookingClickProduzione = handleBookingClickProduzione;
    window.attachEventListeners = attachEventListeners;

    /* ====== AVVIO ====== */
    tryAttachListeners();
  }
})();

</script>
HTML;


foreach ($branca as $titoloBranca => $prestazioni) {

    /* –– Normalizzo il titolo per creare ID univoci e sicuri –– */
    $baseId = preg_replace('/[^a-zA-Z0-9]/', '', $titoloBranca);
    $panelId = 'panel_' . $baseId;
    $btnId   = 'btn_'   . $baseId;
    $inputId = 'input_' . $baseId;
    $tableId = 'table_' . $baseId;
    $tot     = count($prestazioni);

    /* ---- HEADER accordion ------------------------------------------------ */
    echo '
    <div class="faq-singola">

        <button class="faq-titolo accordion-button"
                type="button"
                id="'. $btnId .'"
                data-bs-toggle="collapse"
                data-bs-target="#'. $panelId .'"
                aria-controls="'. $panelId .'"
                aria-expanded="false">
            <span>'. ucwords(strtolower($titoloBranca)) .' <small>(' . $tot . ' prestazioni)</small></span>
        </button>

        <!-- REGIONE COLLASSABILE -->
        <div id="'. $panelId .'"
             class="faq-contenuto collapse"
             role="region"
             aria-labelledby="'. $btnId .'">';

    /* ---- Campo ricerca con label sr-only -------------------------------- */
    echo '
            <label for="'. $inputId .'" class="sr-only">
                Cerca una prestazione di '. htmlspecialchars($titoloBranca) .'
            </label>
            <input id="'. $inputId .'"
                   class="form-control text-2 m-2"
                   type="text"
                   placeholder="Cerca una prestazione…"
                   onkeyup="cercaPrestazioneTabella(\''. $inputId .'\', \''. $tableId .'\')">';

    /* ---- Tabella accessibile ------------------------------------------- */
    echo '
            <table id="'. $tableId .'"
                   class="table table-striped mt-0"
                   aria-label="Prestazioni di '. htmlspecialchars($titoloBranca) .' prenotabili online">

                <caption class="sr-only">
                    Elenco delle prestazioni di '. htmlspecialchars($titoloBranca) .' prenotabili online
                </caption>

                <thead>
                    <tr>
                        <th scope="col" class="text-left text-2 align-middle">Prestazione</th>
                        <th scope="col" class="text-center align-middle">Prenotazione</th>
                    </tr>
                </thead>

                <tbody aria-live="polite">';

    /* ---- Righe singole CON TRACKING ------------------------------------ */
    $row = 1;
    foreach ($prestazioni as $p) {

        $rowId   = 'row-' . $baseId . '-' . $row++;
        $titolo  = ucfirst(strtolower($p['activityTitle']));
        
        // Dati sicuri per tracking
        $safePrestazioneTitolo = htmlspecialchars($p['activityTitle'], ENT_QUOTES, 'UTF-8');
        $safeActivityId = htmlspecialchars($p['activityid'], ENT_QUOTES, 'UTF-8');
        $safeTipologia = htmlspecialchars($titoloBranca, ENT_QUOTES, 'UTF-8');
        $safeCentroAreaid = htmlspecialchars($centroAreaid, ENT_QUOTES, 'UTF-8');
        $safeCentroNome = htmlspecialchars($centroNome, ENT_QUOTES, 'UTF-8');
        $safeTitolo = htmlspecialchars($titolo, ENT_QUOTES, 'UTF-8');

        echo '
                    <tr>
                        <th scope="row" id="'. $rowId .'" class="align-middle" style="width:80%">
                            '. $safeTitolo .'
                        </th>
                        <td class="text-center align-middle" style="width:20%">
                            <a class="btn btn-primary prenota-btn-produzione"
                               href="#"
                               data-prestazione-titolo="'. $safePrestazioneTitolo .'"
                               data-prestazione-id="'. $safeActivityId .'"
                               data-tipologia="'. $safeTipologia .'"
                               data-centro-areaid="'. $safeCentroAreaid .'"
                               data-centro-nome="'. $safeCentroNome .'"
                               aria-describedby="'. $rowId .'"
                               aria-label="Prenota on-line la prestazione: '. $safeTitolo .'"
                               title="Prenota '. $safeTitolo .' - Tracking attivo">
                                Prenota
                            </a>
                        </td>
                    </tr>';
    }

    /* ---- Contatore risultati live (invisibile) ------------------------- */
    echo '
                    <tr class="sr-only">
                        <td colspan="2">
                            <span data-live-count>'. $tot .'</span> risultati visibili
                        </td>
                    </tr>

                </tbody>
            </table>
        </div> <!-- /faq-contenuto -->
    </div>';     // /faq-singola
}

/* ---------------------------------------------------------------------- */
echo '  </div></div></div>';   // chiusura container & accordion