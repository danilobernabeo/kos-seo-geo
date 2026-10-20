/* KOS – Grafico "I nostri dipendenti" (caricare con defer) */
(function () {
  "use strict";

  // Testi generati dal JS: lingua presa dall'attributo lang più vicino (<html lang="en">)
  const I18N = {
    it: { locale: "it-IT", total: "Totale", women: "Donne", men: "Uomini", year: "Anno",
          caption: l => `Dipendenti ${l} per anno` },
    en: { locale: "en-GB", total: "Total", women: "Female", men: "Male", year: "Year",
          caption: l => `Employees (${l}) by year` }
  };
  let T = I18N.it, fmt = new Intl.NumberFormat(T.locale);
  const kFmt = v => v === 0 ? "0" : (v / 1000) + "k";

  function initChart(root, data) {
    const lang = (root.closest("[lang]")?.getAttribute("lang") || "it").slice(0, 2).toLowerCase();
    T = I18N[lang] || I18N.it;
    fmt = new Intl.NumberFormat(T.locale);
    const tabsEl = root.querySelector(".kos-chart__tabs");
    const gridEl = root.querySelector(".kos-chart__grid");
    const colsEl = root.querySelector(".kos-chart__cols");
    const table  = root.querySelector("table");
    const keys   = Object.keys(data.series);

    // Scala Y: fissa da config, oppure calcolata sul massimo dei dati
    const maxVal = Math.max(...keys.flatMap(k => data.series[k].total.filter(v => v != null)));
    const step   = data.yStep || 3000;
    const yMax   = Math.max(data.yMax || 0, Math.ceil(maxVal / step) * step); // yMax = valore minimo della scala, mai tagliato dai dati
    const pct    = v => (v == null ? 0 : (v / yMax) * 100) + "%";

    // Griglia
    for (let v = 0; v <= yMax; v += step) {
      const line = document.createElement("div");
      line.className = "kos-chart__gridline";
      line.style.bottom = pct(v);
      line.innerHTML = `<span>${kFmt(v)}</span>`;
      gridEl.appendChild(line);
    }

    // Colonne
    const cols = data.years.map((year, i) => {
      const col = document.createElement("div");
      col.className = "kos-chart__col";
      col.innerHTML = `
        <div class="kos-chart__stack">
          <div class="kos-chart__bar kos-chart__bar--total"></div>
          <div class="kos-chart__bar kos-chart__bar--women"></div>
          <div class="kos-chart__bar kos-chart__bar--men"></div>
        </div>
        <div class="kos-chart__tip" aria-hidden="true"></div>
        <div class="kos-chart__year" aria-hidden="true">${year}</div>`;
      col.querySelectorAll(".kos-chart__bar").forEach(b => b.style.setProperty("--delay", (i * 70) + "ms"));
      colsEl.appendChild(col);
      return col;
    });

    // Tab: usa i pulsanti già presenti nel markup ([data-kos-series], es. nav-pills Bootstrap);
    // se non ci sono, li genera dentro .kos-chart__tabs
    let btns = [...root.querySelectorAll("[data-kos-series]")];
    if (!btns.length && tabsEl) {
      btns = keys.map(key => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "kos-chart__tab";
        btn.setAttribute("role", "tab");
        btn.textContent = data.series[key].label;
        btn.dataset.kosSeries = key;
        tabsEl.appendChild(btn);
        return btn;
      });
    }
    btns = btns.filter(b => data.series[b.dataset.kosSeries]);
    btns.forEach((btn, i) => {
      btn.addEventListener("click", () => render(btn.dataset.kosSeries));
      btn.addEventListener("keydown", e => {
        if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
        e.preventDefault();
        const next = btns[(i + (e.key === "ArrowRight" ? 1 : -1) + btns.length) % btns.length];
        next.focus();
        render(next.dataset.kosSeries);
      });
    });

    function render(key) {
      const s = data.series[key];
      btns.forEach(b => {
        const on = b.dataset.kosSeries === key;
        b.classList.toggle("active", on);          // stile Bootstrap .nav-link.active
        b.setAttribute("aria-selected", on);
        b.tabIndex = on ? 0 : -1;
      });

      cols.forEach((col, i) => {
        const t = s.total[i], w = s.women[i], m = s.men[i];
        const [bt, bw, bm] = col.querySelectorAll(".kos-chart__bar");
        bt.style.height = pct(t);
        bw.style.height = pct(w);
        bm.style.height = pct(m);

        const tip = col.querySelector(".kos-chart__tip");
        if (t == null) {
          col.removeAttribute("tabindex");
          col.removeAttribute("aria-label");
          col.removeAttribute("role");
          tip.innerHTML = "";
          return;
        }
        col.tabIndex = 0;
        col.setAttribute("role", "img");          // senza un ruolo l'aria-label su un div non viene annunciato
        col.style.setProperty("--h", pct(t));
        col.setAttribute("aria-label",
          `${data.years[i]}: ${T.total.toLowerCase()} ${fmt.format(t)}, ${T.women.toLowerCase()} ${fmt.format(w)}, ${T.men.toLowerCase()} ${fmt.format(m)}`);
        tip.innerHTML =
          `<strong>${data.years[i]}</strong> &middot; ${fmt.format(t)}<br>` +
          `<i style="background:var(--c-women)"></i>${T.women} ${fmt.format(w)}<br>` +
          `<i style="background:var(--c-men)"></i>${T.men} ${fmt.format(m)}`;
      });

      // Tabella accessibile (screen reader / SEO)
      table.innerHTML =
        `<caption>${T.caption(btns.find(b => b.dataset.kosSeries === key)?.textContent.trim() || s.label)}</caption>` +
        `<tr><th scope="col">${T.year}</th><th scope="col">${T.total}</th><th scope="col">${T.women}</th><th scope="col">${T.men}</th></tr>` +
        data.years.map((y, i) => s.total[i] == null ? "" :
          `<tr><th scope="row">${y}</th><td>${fmt.format(s.total[i])}</td><td>${fmt.format(s.women[i])}</td><td>${fmt.format(s.men[i])}</td></tr>`
        ).join("");
    }

    // Prima animazione solo quando il grafico entra nel viewport
    const first = btns.find(b => b.classList.contains("active")) || btns[0];
    const start = () => render(first ? first.dataset.kosSeries : keys[0]);
    if ("IntersectionObserver" in window) {
      const io = new IntersectionObserver(entries => {
        if (entries.some(e => e.isIntersecting)) { start(); io.disconnect(); }
      }, { threshold: 0.3 });
      io.observe(root);
    } else {
      start();
    }
  }

  // Esc chiude il tooltip aperto (WCAG 1.4.13); riappare al successivo hover/focus
  document.addEventListener("keydown", e => {
    if (e.key !== "Escape") return;
    document.querySelectorAll(".kos-chart__col:hover, .kos-chart__col:focus")
      .forEach(c => c.classList.add("is-tip-dismissed"));
  });
  ["mouseover", "focusin"].forEach(ev => document.addEventListener(ev, e => {
    const c = e.target.closest && e.target.closest(".kos-chart__col");
    document.querySelectorAll(".kos-chart__col.is-tip-dismissed").forEach(x => { if (x !== c || ev === "focusin") x.classList.remove("is-tip-dismissed"); });
  }));
  document.addEventListener("mouseout", e => {
    const c = e.target.closest && e.target.closest(".kos-chart__col");
    if (c && !c.contains(e.relatedTarget)) c.classList.remove("is-tip-dismissed");
  });

  document.querySelectorAll("[data-kos-chart]").forEach(root => {
    const src = root.querySelector('script[type="application/json"]')
             || document.getElementById("kos-chart-" + root.dataset.kosChart + "-data");
    if (!src) return;
    try { initChart(root, JSON.parse(src.textContent)); }
    catch (e) { console.error("kos-chart: JSON non valido", e); }
  });
})();

/* ============ KOS – "Le nostre strutture": ingresso righe + contatori ============ */
(function () {
  "use strict";
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function countUp(el, locale, delay) {
    var end = parseInt(el.textContent.replace(/\D/g, ""), 10);
    if (!end || reduce) return;
    // stesso formato del markup: "7500" resta senza separatore, "13.700" lo mantiene
    // stesso separatore del markup ("9.700", "9,700"): Intl in italiano non separa le cifre sotto 10.000
    // e il numero finale dell'animazione sarebbe diverso da quello scritto nella pagina
    var sepMatch = el.textContent.trim().match(/[.,\s\u00A0]/);
    var nf = { format: function (v) {
      var s = String(v);
      return sepMatch ? s.replace(/\B(?=(\d{3})+(?!\d))/g, sepMatch[0]) : s;
    } }, dur = 1200, t0 = null;
    // lo screen reader legge sempre il valore finale, mai i numeri intermedi del conteggio
    var sr = document.createElement("span");
    sr.className = "kos-sr-only";
    sr.textContent = el.textContent;
    el.parentNode.insertBefore(sr, el);
    el.setAttribute("aria-hidden", "true");
    el.textContent = "0";
    setTimeout(function () {
      requestAnimationFrame(function step(t) {
        if (t0 === null) t0 = t;
        var p = Math.min((t - t0) / dur, 1);
        el.textContent = nf.format(Math.round(end * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
      });
    }, delay);
  }

  document.querySelectorAll(".kos-kpi").forEach(function (list) {
    var lang = ((list.closest("[lang]") || document.documentElement).getAttribute("lang") || "it").slice(0, 2);
    var locale = lang === "en" ? "en-GB" : "it-IT";
    var items = list.querySelectorAll(".kos-kpi__item");
    if (reduce || !("IntersectionObserver" in window)) return;   // senza animazioni: resta tutto statico e leggibile

    items.forEach(function (li, i) { li.style.setProperty("--d", (i * 70) + "ms"); });
    list.classList.add("is-ready");
    var io = new IntersectionObserver(function (entries) {
      if (!entries.some(function (e) { return e.isIntersecting; })) return;
      io.disconnect();
      list.classList.add("is-visible");
      items.forEach(function (li, i) {
        li.querySelectorAll("[data-kos-count]").forEach(function (n) { countUp(n, locale, i * 70); });
      });
    }, { threshold: 0.2 });
    io.observe(list);
  });
})();
