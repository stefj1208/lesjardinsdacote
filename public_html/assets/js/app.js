/* Les Jardins d'à Côté — interactions (sans dépendance) */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  var toggle = $('[data-nav-toggle]');
  if (toggle) toggle.addEventListener('click', function () {
    var open = $('#nav').classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.matches && f.matches('[data-confirm]') && !window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  }, true);
  $$('[data-autosubmit]').forEach(function (el) { el.addEventListener('change', function () { el.form.submit(); }); });


  /* ---------- Animations ---------- */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // En-tête : se densifie au défilement
  var head = $('.site-head');
  function onScroll() { if (head) head.classList.toggle('is-scrolled', window.scrollY > 40); }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  // Compteurs : 0 → valeur
  function fmt(n, dec) {
    return n.toFixed(dec).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }
  function count(el) {
    if (el._done) return; el._done = true;
    var to = parseFloat(el.getAttribute('data-count')), dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var pre = el.getAttribute('data-prefix') || '', suf = el.getAttribute('data-suffix') || '';
    if (isNaN(to)) return;
    if (reduce) { el.textContent = pre + fmt(to, dec) + suf; return; }
    var t0 = null, dur = 1400;
    function step(t) {
      if (!t0) t0 = t;
      var k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
      el.textContent = pre + fmt(to * e, dec) + suf;
      if (k < 1) requestAnimationFrame(step);
    }
    el.textContent = pre + fmt(0, dec) + suf;
    requestAnimationFrame(step);
  }

  // Les images à révéler (clip-path) sont observées via leur parent : un élément entièrement découpé n'est jamais « visible ».
  var targets = $$('[data-reveal], .meter, .slots, .compare, [data-count]');
  $$('.reveal-img').forEach(function (img) { var p = img.parentNode; p._revealKids = true; if (targets.indexOf(p) < 0) targets.push(p); });
  function show(el) {
    el.classList.add('is-in');
    if (el._revealKids) $$(':scope > .reveal-img', el).forEach(function (k) { k.classList.add('is-in'); });
    if (el.hasAttribute('data-count')) count(el);
    $$('[data-count]', el).forEach(count);
  }
  if (!('IntersectionObserver' in window) || reduce) {
    targets.forEach(show);
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { show(en.target); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    targets.forEach(function (el) { io.observe(el); });
    // Filet de sécurité : rien ne reste caché si l'observateur ne se déclenche pas (impression, captures).
    setTimeout(function () { targets.forEach(function (el) { var r = el.getBoundingClientRect(); if (r.top < window.innerHeight) show(el); }); }, 1200);
    window.addEventListener('beforeprint', function () { targets.forEach(show); });
  }


  /* Filtre du catalogue (page univers) */
  $$('[data-vfilter]').forEach(function (bar) {
    var grid = bar.parentNode.querySelector('[data-vgrid]');
    $$('[data-fam]', bar).forEach(function (chip) {
      chip.addEventListener('click', function () {
        var fam = chip.getAttribute('data-fam');
        $$('[data-fam]', bar).forEach(function (c) { c.classList.toggle('is-on', c === chip); });
        $$('.vcard', grid).forEach(function (card) { var show = !fam || card.getAttribute('data-family') === fam; card.hidden = !show; if (show) card.classList.add('is-in'); });
      });
    });
  });

  if (!reduce) {
    /* Parallaxe douce de la mosaïque */
    var par = $$('[data-speed]');
    /* Défilement horizontal piloté par le défilement vertical */
    var hs = $('[data-hscroll]'), track = hs && $('.hs-track', hs), bar = hs && $('.hs-progress span', hs);
    var pin = false;
    function layoutHs() {
      if (!hs) return;
      pin = window.innerWidth > 900 && window.innerHeight > 620;
      hs.classList.toggle('is-pinned', pin);
      if (!pin) { hs.style.height = ''; track.style.transform = ''; return; }
      var dist = Math.max(0, track.scrollWidth - window.innerWidth);
      hs.style.height = (window.innerHeight + dist) + 'px';
      hs._dist = dist;
    }
    var ticking = false;
    function frame() {
      ticking = false;
      var y = window.scrollY;
      par.forEach(function (el) { var r = el.getBoundingClientRect(); if (r.bottom > 0 && r.top < window.innerHeight) el.style.transform = 'translate3d(0,' + (y * parseFloat(el.getAttribute('data-speed'))).toFixed(1) + 'px,0)'; });
      if (hs && pin) {
        var top = hs.getBoundingClientRect().top, p = Math.min(1, Math.max(0, -top / (hs._dist || 1)));
        track.style.transform = 'translate3d(' + (-p * hs._dist).toFixed(1) + 'px,0,0)';
        if (bar) bar.style.setProperty('--hp', Math.max(.08, p));
      }
    }
    function onScrollFx() { if (!ticking) { ticking = true; requestAnimationFrame(frame); } }
    if (hs) { layoutHs(); window.addEventListener('resize', function () { layoutHs(); frame(); }); window.addEventListener('load', function () { layoutHs(); frame(); }); }
    if (hs || par.length) { window.addEventListener('scroll', onScrollFx, { passive: true }); frame(); }
    if (hs && !pin && track && bar) track.addEventListener('scroll', function () { bar.style.setProperty('--hp', Math.max(.08, track.scrollLeft / Math.max(1, track.scrollWidth - track.clientWidth))); }, { passive: true });

    /* Cartes : inclinaison 3D et halo qui suit le curseur */
    if (window.matchMedia('(hover: hover)').matches) {
      $$('[data-tilt]').forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
          var r = card.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
          card.style.setProperty('--mx', (x * 100) + '%'); card.style.setProperty('--my', (y * 100) + '%');
          card.style.transform = 'perspective(900px) rotateX(' + ((.5 - y) * 5).toFixed(2) + 'deg) rotateY(' + ((x - .5) * 6).toFixed(2) + 'deg) translateY(-4px)';
        });
        card.addEventListener('mouseleave', function () { card.style.transform = ''; });
      });
    }
  }

  var toastEl;
  function toast(html, ms) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.innerHTML = html;
    requestAnimationFrame(function () { toastEl.classList.add('is-on'); });
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('is-on'); }, ms || 5000);
  }

  /* Sélecteur de quantité */
  $$('.qty-pick').forEach(function (box) {
    var input = $('input', box);
    $$('[data-step]', box).forEach(function (b) {
      b.addEventListener('click', function () {
        var v = (parseInt(input.value, 10) || 1) + parseInt(b.getAttribute('data-step'), 10);
        input.value = Math.max(parseInt(input.min || '1', 10), Math.min(parseInt(input.max || '9', 10), v));
      });
    });
  });


  /* Vote des variétés : compteur, limite, filtres */
  $$('[data-vpick]').forEach(function (form) {
    var max = parseInt(form.getAttribute('data-max') || '10', 10), counter = $('[data-vcount]', form), bar = $('.vbar', form);
    function sync(changed) {
      var boxes = $$('input[type=checkbox]', form), n = boxes.filter(function (b) { return b.checked; }).length;
      if (n > max && changed) { changed.checked = false; n--; toast('<p>Vous pouvez choisir ' + max + ' variétés au maximum. Décochez-en une pour en ajouter une autre.</p>', 3500); }
      boxes.forEach(function (b) { b.closest('.vcard').classList.toggle('is-on', b.checked); });
      if (counter) counter.textContent = n;
      if (bar) bar.classList.toggle('is-full', n >= max);
    }
    form.addEventListener('change', function (e) { if (e.target.matches('input[type=checkbox]')) sync(e.target); });
    sync(null);
    $$('[data-fam]', form).forEach(function (chip) {
      chip.addEventListener('click', function () {
        var fam = chip.getAttribute('data-fam');
        $$('[data-fam]', form).forEach(function (c) { c.classList.toggle('is-on', c === chip); });
        $$('.vcard', form).forEach(function (card) {
          var show = !fam || card.getAttribute('data-family') === fam;
          card.hidden = !show;
          if (show) card.classList.add('is-in');
        });
      });
    });
  });

  /* Partage */
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var text = b.getAttribute('data-share-text'), url = b.getAttribute('data-share-url');
      if (navigator.share) { navigator.share({ title: document.title, text: text, url: url }).catch(function () {}); return; }
      (navigator.clipboard ? navigator.clipboard.writeText(text + ' ' + url) : Promise.reject())
        .then(function () { toast('<p>Lien copié : collez-le dans vos messages.</p>', 3500); })
        .catch(function () { window.prompt('Copiez ce lien :', url); });
    });
  });

  /* Lignes dynamiques (variétés d'un pack) */
  $$('[data-add-row]').forEach(function (btn) {
    var table = $(btn.getAttribute('data-add-row'));
    btn.addEventListener('click', function () {
      var rows = $$('tbody tr', table), last = rows[rows.length - 1], clone = last.cloneNode(true), idx = rows.length;
      $$('input, select', clone).forEach(function (el) {
        el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
        if (el.tagName === 'INPUT') el.value = '';
      });
      var ph = $('.ph', clone); if (ph) { ph.innerHTML = ''; ph.classList.add('ph--empty'); }
      $('tbody', table).appendChild(clone);
      var first = $('input[name^="item_name"]', clone); if (first) first.focus();
    });
  });
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-remove-row]');
    if (!b) return;
    var tbody = b.closest('tbody');
    if ($$('tr', tbody).length > 1) b.closest('tr').remove();
    else $$('input', b.closest('tr')).forEach(function (i) { i.value = ''; });
  });
})();

/* Carte « Installer le pilotage » (back-office) */
(function () {
  var card = document.querySelector('[data-install]');
  if (!card) return;
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var dismissed = false;
  try { dismissed = localStorage.getItem('jdc-install-later') === '1'; } catch (e) {}
  if (standalone || dismissed) return;
  var ua = navigator.userAgent, ios = /iphone|ipad|ipod/i.test(ua), android = /android/i.test(ua);
  var show = function (sel) { var el = card.querySelector(sel); if (el) el.hidden = false; };
  var deferred = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); deferred = e;
    show('[data-install-android]'); show('[data-install-btn]'); card.hidden = false;
  });
  if (ios) { show('[data-install-ios]'); card.hidden = false; }
  else if (!android) { show('[data-install-other]'); card.hidden = false; }
  var btn = card.querySelector('[data-install-btn]');
  if (btn) btn.addEventListener('click', function () { if (!deferred) return; deferred.prompt(); deferred.userChoice.finally(function () { card.hidden = true; deferred = null; }); });
  card.querySelector('[data-install-close]').addEventListener('click', function () {
    card.hidden = true; try { localStorage.setItem('jdc-install-later', '1'); } catch (e) {}
  });
})();

/* Carte des groupes (Leaflet chargé à la demande, seulement si une carte est sur la page) */
(function () {
  var maps = document.querySelectorAll('.gmap[data-points]');
  if (!maps.length) return;
  var V = '/assets/vendor/leaflet/'; // Leaflet 1.9.4, servi depuis le site (intégrité vérifiée)
  function load(cb) {
    if (window.L) return cb();
    var css = document.createElement('link'); css.rel = 'stylesheet'; css.href = V + 'leaflet.css'; css.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY='; css.crossOrigin = ''; document.head.appendChild(css);
    var s = document.createElement('script'); s.src = V + 'leaflet.js'; s.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo='; s.crossOrigin = ''; s.onload = cb; document.head.appendChild(s);
  }
  var colors = { open: '#D9634A', voting: '#6A9E6D', waiting: '#6A9E6D', active: '#0F172B', proposed: '#9AA6B6' };
  var labels = { open: 'Ventes ouvertes', voting: 'Vote en cours', waiting: 'Prochain vote en préparation', active: 'Bénévoles recherchés', proposed: 'En création' };
  function esc(s) { return String(s || '').replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function init(el) {
    var pts = []; try { pts = JSON.parse(el.getAttribute('data-points')); } catch (e) {}
    if (!pts.length) return;
    var map = L.map(el, { scrollWheelZoom: false, attributionControl: true });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 17, subdomains: 'abcd',
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(map);
    var b = [];
    pts.forEach(function (p) {
      var c = colors[p.state] || colors.active;
      var icon = L.divIcon({ className: 'gpin', html: '<span style="--c:' + c + '"><svg viewBox="0 0 24 24"><path d="M12 21v-7M12 14c-4 0-6-2.5-6-5.5S8.5 3 12 1c3.5 2 6 4.5 6 7.5S16 14 12 14z"/></svg></span>', iconSize: [34, 34], iconAnchor: [17, 32], popupAnchor: [0, -28] });
      L.marker([p.lat, p.lng], { icon: icon, title: p.commune }).addTo(map)
        .bindPopup('<b>' + esc(p.name) + '</b><br><span class="gpop-st" style="color:' + c + '">' + esc(labels[p.state] || '') + '</span>' + (p.pickup ? '<br><small>Retrait : ' + esc(p.pickup) + '</small>' : '') + '<br><a href="' + esc(p.url) + '">Voir le groupe →</a>');
      b.push([p.lat, p.lng]);
    });
    if (b.length === 1) map.setView(b[0], 12); else map.fitBounds(b, { padding: [40, 40], maxZoom: 12 });
    el.addEventListener('click', function () { map.scrollWheelZoom.enable(); }, { once: true });
  }
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { io.unobserve(e.target); load(function () { init(e.target); }); } });
  }, { rootMargin: '300px' }) : null;
  maps.forEach(function (m) { if (io) io.observe(m); else load(function () { init(m); }); });
})();
