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

  var targets = $$('[data-reveal], .meter, .slots, .compare, [data-count]');
  function show(el) {
    el.classList.add('is-in');
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
