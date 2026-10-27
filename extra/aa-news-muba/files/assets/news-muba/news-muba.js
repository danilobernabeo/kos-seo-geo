/* =====================================================================
   NEWS ANNI AZZURRI E MUBA — galleria fotografica e videotutorial
   Come l'albero dei ricordi: nessuna dipendenza oltre al bundle Bootstrap
   già caricato dal sito, niente jQuery.

   Senza questo file la pagina resta completa: il mosaico si vede (non si
   apre il visore) e ogni video ha il collegamento a YouTube.
   ===================================================================== */
(function () {
  'use strict';

  /* ---------------------------------------------------------------
     GALLERIA: mosaico e visore
     I dati (gruppi e foto) sono in <script id="muba-galleria-dati">,
     scritto da prepara-foto.py. Il mosaico arriva già pronto dal server;
     qui le caselle piccole pescano a caso, per formato, preferendo
     residenze diverse. Il visore è una finestra Bootstrap a tutto schermo.
     --------------------------------------------------------------- */
  var datiEl = document.getElementById('muba-galleria-dati');
  var visore = document.getElementById('muba-visore');
  var D = null;
  try { D = datiEl ? JSON.parse(datiEl.textContent) : null; } catch (err) { D = null; }

  if (D && D.foto && D.foto.length && visore && window.bootstrap && window.bootstrap.Modal) {
    var foto = D.foto, gruppi = D.gruppi, base = D.base;
    var verticale = function (f) { return f.h > f.w; };
    var srcsetWebp = function (f) { return base + f.s + '-800.webp ' + f.w8 + 'w, ' + base + f.s + '-1600.webp ' + f.w + 'w'; };

    /* ---------- mosaico: sorteggio delle caselle piccole ---------- */
    var tessere = Array.prototype.slice.call(document.querySelectorAll('[data-muba-mosaico] [data-tessera]'));
    var usate = {}, gruppiUsati = {};
    tessere.forEach(function (t) {
      if (t.getAttribute('data-tessera') === 'grande') {
        var i = +t.getAttribute('data-indice');
        usate[i] = true; gruppiUsati[foto[i].g] = true;
      }
    });
    tessere.forEach(function (t) {
      var pos = t.getAttribute('data-tessera');
      if (pos === 'grande') return;
      var vuoleV = pos.indexOf('verticale') === 0;
      var candidati = [];
      foto.forEach(function (f, i) { if (f.m && !usate[i] && verticale(f) === vuoleV) candidati.push(i); });
      if (!candidati.length) return;
      var nuovi = candidati.filter(function (i) { return !gruppiUsati[foto[i].g]; });
      var scelta = nuovi.length ? nuovi : candidati;
      var i = scelta[Math.floor(Math.random() * scelta.length)];
      usate[i] = true; gruppiUsati[foto[i].g] = true;
      if (i === +t.getAttribute('data-indice')) return;
      var f = foto[i], img = t.querySelector('img'), src = t.querySelector('source'), nome = t.querySelector('.muba-tessera-nome');
      if (src) src.setAttribute('srcset', srcsetWebp(f));
      img.src = base + f.s + '-1600.jpg';
      img.width = f.w; img.height = f.h;
      t.setAttribute('data-indice', i);
      t.setAttribute('data-formato', verticale(f) ? 'v' : 'o');
      t.setAttribute('aria-label', f.a + ' (' + gruppi[f.g].e + '): apri la galleria');
      if (nome) nome.textContent = gruppi[f.g].e;
    });

    /* ---------- visore ---------- */
    var istanza = new window.bootstrap.Modal(visore);
    var q = function (sel) { return visore.querySelector(sel); };
    var filtriEl = q('[data-visore-filtri]'), menu = q('[data-visore-menu]'), striscia = q('[data-visore-striscia]');
    var scena = q('[data-visore-scena]'), vImg = q('[data-visore-img]'), vWebp = q('[data-visore-webp]');
    var dida = q('[data-visore-dida]'), contatore = q('[data-visore-contatore]'), residenza = q('[data-visore-residenza]');
    var filtro = -1, lista = [], pos = 0, origine = null, pronti = false;

    var conta = function (g) { return foto.filter(function (f) { return g < 0 || f.g === g; }).length; };
    var voci = [{ g: -1, e: 'Tutte' }].concat(gruppi.map(function (gr, g) { return { g: g, e: gr.e }; }));

    var preparaFiltri = function () {
      voci.forEach(function (v) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = v.e + ' (' + conta(v.g) + ')';
        b.setAttribute('aria-pressed', 'false');
        b.addEventListener('click', function () { scegliFiltro(v.g); });
        b.setAttribute('data-gruppo', v.g);
        filtriEl.appendChild(b);
        var o = document.createElement('option');
        o.value = v.g; o.textContent = b.textContent;
        menu.appendChild(o);
      });
      menu.addEventListener('change', function () { scegliFiltro(+menu.value); });
      pronti = true;
    };

    // larghezza con cui la foto verrà mostrata: serve allo srcset per scegliere 800 o 1600
    var larghezzaMostrata = function (f) {
      var r = scena.getBoundingClientRect();
      var w = r.width || window.innerWidth, h = r.height || (window.innerHeight - 220);
      if (window.innerWidth >= 768) w -= 144;
      return Math.max(160, Math.ceil(Math.min(w, h * f.w / f.h)));
    };

    var costruisciStriscia = function () {
      striscia.innerHTML = '';
      lista.forEach(function (i, k) {
        var f = foto[i], b = document.createElement('button');
        b.type = 'button';
        b.setAttribute('aria-label', 'Foto ' + (k + 1) + ' di ' + lista.length + ': ' + f.a);
        b.innerHTML = '<img src="' + base + f.s + '-q.webp" alt="" width="80" height="80" loading="lazy" decoding="async">';
        b.addEventListener('click', function () { mostra(k); });
        striscia.appendChild(b);
      });
    };

    var precarica = function (k) {
      var f = foto[lista[(k + lista.length) % lista.length]], im = new Image();
      im.sizes = larghezzaMostrata(f) + 'px';
      im.srcset = srcsetWebp(f);
    };

    var mostra = function (k) {
      pos = (k + lista.length) % lista.length;
      var f = foto[lista[pos]], g = gruppi[f.g];
      var sizes = larghezzaMostrata(f) + 'px';
      vImg.classList.add('muba-carica');
      vWebp.setAttribute('sizes', sizes);
      vWebp.setAttribute('srcset', srcsetWebp(f));
      vImg.setAttribute('sizes', sizes);
      vImg.src = base + f.s + '-1600.jpg';
      vImg.width = f.w; vImg.height = f.h;
      vImg.alt = f.a;
      dida.textContent = f.a;
      contatore.textContent = (pos + 1) + ' di ' + lista.length;
      residenza.textContent = g.n + ', ' + g.l;
      residenza.href = g.u;
      Array.prototype.forEach.call(striscia.children, function (b, n) {
        if (n === pos) {
          b.setAttribute('aria-current', 'true');
        } else b.removeAttribute('aria-current');
      });
      centraMiniatura(true);
      precarica(pos + 1); precarica(pos - 1);
    };
    vImg.addEventListener('load', function () { vImg.classList.remove('muba-carica'); });

    var scegliFiltro = function (g, indiceFoto) {
      var corrente = lista.length ? lista[pos] : -1;
      filtro = g;
      lista = [];
      foto.forEach(function (f, i) { if (g < 0 || f.g === g) lista.push(i); });
      Array.prototype.forEach.call(filtriEl.children, function (b) {
        b.setAttribute('aria-pressed', +b.getAttribute('data-gruppo') === g ? 'true' : 'false');
      });
      menu.value = String(g);
      costruisciStriscia();
      var cerca = indiceFoto !== undefined ? indiceFoto : corrente;
      var k = lista.indexOf(cerca);
      mostra(k >= 0 ? k : 0);
    };

    var apri = function (indiceFoto, da) {
      if (!pronti) preparaFiltri();
      origine = da || null;
      lista = [];
      scegliFiltro(-1, indiceFoto);
      istanza.show();
    };

    tessere.forEach(function (t) {
      t.addEventListener('click', function () { apri(+t.getAttribute('data-indice'), t); });
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-visore-apri]'), function (b) {
      b.addEventListener('click', function () { apri(0, b); });
    });
    q('[data-visore-prec]').addEventListener('click', function () { mostra(pos - 1); });
    q('[data-visore-succ]').addEventListener('click', function () { mostra(pos + 1); });
    visore.addEventListener('keydown', function (e) {
      if (e.target === menu) return;
      if (e.key === 'ArrowLeft') { e.preventDefault(); mostra(pos - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); mostra(pos + 1); }
    });
    // scorrimento con il dito sulla foto
    var x0 = null;
    scena.addEventListener('touchstart', function (e) { x0 = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
    scena.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      x0 = null;
      if (Math.abs(dx) > 50) mostra(dx < 0 ? pos + 1 : pos - 1);
    });
    // la striscia si misura solo a finestra aperta: allora si centra la miniatura della foto corrente
    var centraMiniatura = function (liscio) {
      var b = striscia.children[pos];
      if (b) striscia.scrollTo({ left: b.offsetLeft - (striscia.clientWidth - b.offsetWidth) / 2, behavior: liscio ? 'smooth' : 'auto' });
    };
    visore.addEventListener('shown.bs.modal', function () { centraMiniatura(false); });
    visore.addEventListener('hidden.bs.modal', function () { if (origine) origine.focus(); });
  }

  /* ---------------------------------------------------------------
     VIDEOTUTORIAL
     Anteprima senza contenuti di terze parti; YouTube (dominio
     youtube-nocookie) si carica solo al clic sul pulsante play, in una
     sola finestra: un video alla volta. Alla chiusura l'iframe viene
     tolto, così il video si ferma davvero.
     Senza la finestra o senza Bootstrap il video si apre nella pagina.
     --------------------------------------------------------------- */
  var finestraVideo = document.getElementById('muba-modale-video');
  var contenitore = finestraVideo && finestraVideo.querySelector('[data-video-contenitore]');
  var titoloVideo = finestraVideo && finestraVideo.querySelector('[data-video-titolo]');
  var istanzaVideo = (contenitore && window.bootstrap && window.bootstrap.Modal)
    ? new window.bootstrap.Modal(finestraVideo) : null;
  var origineVideo = null;

  var creaIframe = function (id, titolo) {
    var f = document.createElement('iframe');
    f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
    f.title = titolo;
    f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
    f.allowFullscreen = true;
    f.className = 'muba-video-frame';
    return f;
  };

  if (istanzaVideo) {
    finestraVideo.addEventListener('hidden.bs.modal', function () {
      contenitore.innerHTML = '';                 // ferma il video
      if (origineVideo) origineVideo.focus();
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-muba-video]'), function (box) {
    var btn = box.querySelector('.muba-video-play');
    var id = box.getAttribute('data-muba-video');
    if (!btn || !/^[\w-]{6,20}$/.test(id)) return;
    var titolo = box.getAttribute('data-titolo') || 'Videotutorial';
    btn.addEventListener('click', function () {
      if (istanzaVideo) {
        origineVideo = btn;
        titoloVideo.textContent = titolo;
        contenitore.innerHTML = '';
        contenitore.appendChild(creaIframe(id, titolo));
        istanzaVideo.show();
      } else {
        btn.replaceWith(creaIframe(id, titolo));
      }
    });
  });
})();
