/* KOS – Grafico "Ricavi" (caricare con defer). Dati: <script type="application/json"> dentro [data-kos-area] */
(function () {
  "use strict";
  var I18N = {
    it: { value: function (v, nf) { return nf.format(v) + "M \u20AC"; }, locale: "it-IT",
          caption: "Ricavi per anno (milioni di euro)", year: "Anno", col: "Ricavi" },
    en: { value: function (v, nf) { return "\u20AC" + nf.format(v) + "M"; }, locale: "en-GB",
          caption: "Revenues by year (EUR million)", year: "Year", col: "Revenues" }
  };
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var NS = "http://www.w3.org/2000/svg";

  function init(root, data) {
    var lang = ((root.closest("[lang]") || document.documentElement).getAttribute("lang") || "it").slice(0, 2).toLowerCase();
    var T = I18N[lang] || I18N.it, nf = new Intl.NumberFormat(T.locale);
    var plot = root.querySelector(".kos-area__plot");
    // Parte visiva nascosta agli screen reader: i dati si leggono nella tabella, anno per anno
    plot.setAttribute("aria-hidden", "true");
    var years = data.years || [], vals = data.values || [];
    var n = years.length, step = data.yStep || 150;
    var maxVal = Math.max.apply(null, vals.filter(function (v) { return v != null; }).concat([0]));
    var yMax = Math.max(data.yMax || 0, Math.ceil(maxVal / step) * step);

    var X = function (i) { return (i + 0.5) / n * 1000; };       // coordinate SVG 0..1000
    var Y = function (v) { return 1000 - v / yMax * 1000; };
    var pct = function (c) { return (c / 10) + "%"; };

    // Griglia + etichette asse Y
    var grid = plot.querySelector(".kos-area__grid");
    for (var g = 0; g <= yMax; g += step) {
      var line = document.createElement("div");
      line.className = "kos-area__gridline";
      line.style.top = pct(Y(g));
      line.innerHTML = "<span>" + (g ? nf.format(g) + "M" : "0") + "</span>";
      grid.appendChild(line);
    }

    // Punti con dati (gli anni senza valore mostrano solo l'etichetta dell'anno)
    var pts = [];
    vals.forEach(function (v, i) { if (v != null) pts.push([X(i), Y(v), v, i]); });

    // Area: parte dal bordo sinistro (come nell'originale) e finisce sull'ultimo punto
    var svg = plot.querySelector(".kos-area__svg");
    if (pts.length) {
      var p0 = pts[0], p1 = pts[1] || pts[0];
      var startY = p0[1] + (p0[1] - p1[1]) * 0.2;
      var d = "M0," + startY.toFixed(1) + " L" + p0[0].toFixed(1) + "," + p0[1].toFixed(1);
      for (var k = 1; k < pts.length; k++) {                       // curva morbida (Catmull-Rom -> Bezier)
        var a = pts[k - 2] || pts[k - 1], b = pts[k - 1], c = pts[k], e = pts[k + 1] || c, t = 0.18;
        d += " C" + (b[0] + (c[0] - a[0]) * t).toFixed(1) + "," + (b[1] + (c[1] - a[1]) * t).toFixed(1) +
             " " + (c[0] - (e[0] - b[0]) * t).toFixed(1) + "," + (c[1] - (e[1] - b[1]) * t).toFixed(1) +
             " " + c[0].toFixed(1) + "," + c[1].toFixed(1);
      }
      var last = pts[pts.length - 1];
      d += " L" + last[0].toFixed(1) + ",1000 L0,1000 Z";
      var gid = "kosAreaGrad" + Math.random().toString(36).slice(2, 8);
      svg.innerHTML =
        '<defs><linearGradient id="' + gid + '" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="1000">' +
        '<stop offset="0" style="stop-color:var(--c-area)"/>' +
        '<stop offset="' + (1 - 600 / yMax).toFixed(3) + '" style="stop-color:var(--c-area)"/>' +   // pieno fino a ~600M
        '<stop offset="1" style="stop-color:var(--c-area);stop-opacity:.03"/></linearGradient></defs>' +
        '<path d="' + d + '" fill="url(#' + gid + ')"/>';
    }

    // Punti, valori, anni
    pts.forEach(function (p, j) {
      var delay = (reduce ? 0 : 250 + j * (900 / Math.max(pts.length, 1))) + "ms";
      var dot = document.createElement("span");
      dot.className = "kos-area__dot";
      dot.style.left = pct(p[0]); dot.style.top = pct(p[1]); dot.style.setProperty("--d", delay);
      var lab = document.createElement("span");
      lab.className = "kos-area__value";
      lab.style.left = pct(p[0]); lab.style.top = pct(p[1]); lab.style.setProperty("--d", delay);
      lab.textContent = T.value(p[2], nf);
      plot.appendChild(dot); plot.appendChild(lab);
    });
    years.forEach(function (y, i) {
      var el = document.createElement("span");
      el.className = "kos-area__year"; el.style.left = pct(X(i)); el.textContent = y;
      plot.appendChild(el);
    });

    // Tabella per screen reader
    var table = root.querySelector("table.kos-sr-only");
    if (table) {
      table.innerHTML = "<caption>" + T.caption + "</caption><tr><th scope=\"col\">" + T.year +
        "</th><th scope=\"col\">" + T.col + "</th></tr>" +
        pts.map(function (p) { return "<tr><th scope=\"row\">" + years[p[3]] + "</th><td>" + T.value(p[2], nf) + "</td></tr>"; }).join("");
    }

    // Animazione all'ingresso nel viewport
    if (reduce || !("IntersectionObserver" in window)) return;
    root.classList.add("is-ready");
    var io = new IntersectionObserver(function (entries) {
      if (entries.some(function (x) { return x.isIntersecting; })) { root.classList.add("is-visible"); io.disconnect(); }
    }, { threshold: 0.25 });
    io.observe(root);
  }

  document.querySelectorAll("[data-kos-area]").forEach(function (root) {
    var src = root.querySelector('script[type="application/json"]');
    if (!src) return;
    try { init(root, JSON.parse(src.textContent)); }
    catch (e) { console.error("kos-ricavi: JSON non valido", e); }
  });
})();
