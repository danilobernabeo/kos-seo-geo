/* =====================================================================
   NEWS ANNI AZZURRI E MUBA — galleria fotografica e videotutorial
   Come l'albero dei ricordi: nessuna dipendenza oltre al bundle Bootstrap
   già caricato dal sito, niente jQuery.

   Senza questo file la pagina resta completa: le fotografie si vedono
   (non si ingrandiscono) e ogni video ha il collegamento a YouTube.
   ===================================================================== */
(function () {
  'use strict';

  /* ---------------------------------------------------------------
     GALLERIA
     Ogni foto è un <button data-galleria-foto data-foto-jpg data-foto-webp
     data-foto-dida>. Una sola finestra Bootstrap, con frecce, contatore
     e tasti sinistra/destra.
     --------------------------------------------------------------- */
  var modale = document.getElementById('muba-modale-foto');
  var foto = Array.prototype.slice.call(document.querySelectorAll('[data-galleria-foto]'));

  if (modale && foto.length && window.bootstrap && window.bootstrap.Modal) {
    // Inizializzata da JavaScript e non con data-bs-*: il sito carica il CSS
    // di Bootstrap 5.1.3 ma il JS 5.0.2 (stessa scelta dell'albero dei ricordi).
    var istanza = new window.bootstrap.Modal(modale);
    var img = modale.querySelector('[data-modale-img]');
    var webp = modale.querySelector('[data-modale-webp]');
    var dida = modale.querySelector('[data-modale-dida]');
    var contatore = modale.querySelector('[data-modale-contatore]');
    var prec = modale.querySelector('[data-modale-prec]');
    var succ = modale.querySelector('[data-modale-succ]');
    var corrente = 0;
    var origine = null;

    var mostra = function (i) {
      corrente = (i + foto.length) % foto.length;
      var b = foto[corrente];
      if (b.dataset.fotoWebp) webp.setAttribute('srcset', b.dataset.fotoWebp);
      else webp.removeAttribute('srcset');
      img.src = b.dataset.fotoJpg;
      img.alt = b.dataset.fotoDida || '';
      dida.textContent = b.dataset.fotoDida || '';
      contatore.textContent = (corrente + 1) + ' di ' + foto.length;
    };

    foto.forEach(function (b, i) {
      b.addEventListener('click', function () {
        origine = b;
        mostra(i);
        istanza.show();
      });
    });
    prec.addEventListener('click', function () { mostra(corrente - 1); });
    succ.addEventListener('click', function () { mostra(corrente + 1); });
    modale.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); mostra(corrente - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); mostra(corrente + 1); }
    });
    // Il focus torna alla foto da cui si è partiti: la finestra non è stata
    // aperta da un data-bs-toggle, quindi Bootstrap non lo fa da solo.
    modale.addEventListener('hidden.bs.modal', function () {
      if (origine) origine.focus();
    });
  }

  /* ---------------------------------------------------------------
     VIDEOTUTORIAL
     Anteprima senza contenuti di terze parti; YouTube (dominio
     youtube-nocookie) si carica solo al clic sul pulsante play.
     --------------------------------------------------------------- */
  Array.prototype.forEach.call(document.querySelectorAll('[data-muba-video]'), function (box) {
    var btn = box.querySelector('.muba-video-play');
    var id = box.getAttribute('data-muba-video');
    if (!btn || !/^[\w-]{6,20}$/.test(id)) return;
    btn.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
      f.title = box.getAttribute('data-titolo') || 'Videotutorial';
      f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
      f.allowFullscreen = true;
      f.className = 'muba-video-frame';
      btn.replaceWith(f);
      f.focus();
    });
  });
})();
