/* Les Jardins d'à Côté — interactions du site (sans dépendance) */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = function () { var i = $('input[name="_csrf"]'); return i ? i.value : ''; };
  var fmtMoney = function (v) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Math.round(v * 100) / 100);
  };
  var fmtNum = function (v, d) {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: d === undefined ? 2 : d }).format(v);
  };
  var plural = function (n, one, many) { return n >= 2 ? many : one; };

  /* ---------------- Navigation mobile ---------------- */
  var toggle = $('[data-nav-toggle]');
  if (toggle) toggle.addEventListener('click', function () {
    var nav = $('#nav'); var open = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  /* ---------------- Confirmations ---------------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.matches && f.matches('[data-confirm]') && !window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  }, true);
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('button[data-confirm]');
    if (b && !window.confirm(b.getAttribute('data-confirm'))) e.preventDefault();
  }, true);
  $$('[data-autosubmit]').forEach(function (el) { el.addEventListener('change', function () { el.form.submit(); }); });

  /* ---------------- Toast ---------------- */
  var toastEl;
  function toast(html, ms) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.innerHTML = html;
    requestAnimationFrame(function () { toastEl.classList.add('is-on'); });
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('is-on'); }, ms || 6000);
  }
  window.JDC = window.JDC || {};
  window.JDC.toast = toast;

  /* ---------------- Comptes à rebours ---------------- */
  function remaining(ts) {
    var s = ts - Math.floor(Date.now() / 1000);
    if (s <= 0) return 'terminée';
    var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
    if (d > 0) return d + ' j ' + h + ' h';
    if (h > 0) return h + ' h ' + m + ' min';
    return Math.max(1, m) + ' min';
  }
  function tick() { $$('[data-countdown]').forEach(function (el) { el.textContent = remaining(+el.getAttribute('data-countdown')); }); }
  if ($('[data-countdown]')) { tick(); setInterval(tick, 30000); }

  /* ---------------- Moteur de paliers (miroir de Pricing::state) ---------------- */
  function campState(d, qty) {
    var tiers = d.tiers.slice().sort(function (a, b) { return a.level - b.level; });
    var max = tiers.length, tier = 0;
    tiers.forEach(function (t, i) { if (qty + 1e-9 >= t.min) tier = i + 1; });
    var t1 = tiers[0], cur = tier ? tiers[tier - 1] : null, next = tier < max ? tiers[tier] : null;
    var price = cur ? cur.price : (t1 ? t1.price : d.public);
    var toNext = 1, missing = 0;
    if (next) {
      var base = cur ? cur.min : 0;
      toNext = Math.max(0, Math.min(1, (qty - base) / Math.max(0.0001, next.min - base)));
      missing = Math.max(0, next.min - qty);
    }
    var t1Min = t1 ? Math.max(0.0001, t1.min) : 1, maxMin = max ? Math.max(0.0001, tiers[max - 1].min) : 1;
    var plant = tier === 0 ? (qty / t1Min < 0.25 ? 1 : 2) : (tier === max && max >= 2 ? 5 : (tier >= 2 ? 4 : 3));
    return {
      tier: tier, max: max, price: price, next: next, missing: missing, toNext: toNext,
      toT1: Math.min(1, qty / t1Min), growth: Math.min(1, qty / maxMin), plant: plant,
      partsMissing: d.ref > 0 ? Math.ceil(missing / d.ref - 1e-9) : 0
    };
  }
  var packetPrice = function (unitPrice, portion) { return Math.round(unitPrice * portion * 100) / 100; };
  function qtyLabel(q, d) {
    if (d.unit === 'g' && q >= 1000) return fmtNum(q / 1000, 3) + ' kg';
    if (d.unit === 'seeds') return fmtNum(q, 0) + ' ' + plural(q, 'graine', 'graines');
    if (d.unit === 'pot') return fmtNum(q, 0) + ' ' + plural(q, 'pot', 'pots');
    if (d.unit === 'piece') return fmtNum(q, 0) + ' ' + plural(q, 'pièce', 'pièces');
    return fmtNum(q, 3) + ' ' + d.unitShort;
  }

  function setPlant(el, s) {
    if (!el) return;
    el.setAttribute('data-state', s.plant);
    el.style.setProperty('--g', s.growth);
    el.style.setProperty('--t1', Math.max(0.35, s.toT1));
  }
  function water(el) {
    if (!el) return;
    el.classList.add('is-watering');
    clearTimeout(el._w);
    el._w = setTimeout(function () { el.classList.remove('is-watering'); }, 1400);
  }

  /* ---------------- Fiche campagne : sélecteur de quantité ---------------- */
  var dataEl = $('#camp-data');
  var form = $('[data-order-form]');
  var campData = dataEl ? JSON.parse(dataEl.textContent) : null;
  var bigPlant = $('.camp-visual .plant');
  var previewing = false;

  function updateOrder(animate) {
    if (!campData || !form) return;
    var portionEl = $('input[name="portion"]:checked', form);
    var portion = portionEl ? parseFloat(portionEl.value) : campData.ref;
    var input = $('input[name="packets"]', form);
    var n = Math.max(1, Math.min(parseInt(input.max || '99', 10), parseInt(input.value || '1', 10) || 1));
    input.value = n;
    var mine = portion * n;
    var now = campState(campData, campData.reserved);
    var after = campState(campData, campData.reserved + mine);
    $('[data-sum="qty"]', form).textContent = n + ' × ' + qtyLabel(portion, campData) + ' = ' + qtyLabel(mine, campData);
    $('[data-sum="total"]', form).textContent = fmtMoney(packetPrice(after.price, portion) * n);
    var nextPrice = after.next ? after.next.price : null;
    $('[data-sum="next"]', form).textContent = nextPrice ? fmtMoney(packetPrice(nextPrice, portion) * n) : 'prix plancher';
    var msg;
    if (after.tier > now.tier) {
      msg = after.tier === 1 && now.tier === 0
        ? 'Avec votre commande, la campagne est validée ! Prix garanti : ' + fmtMoney(packetPrice(after.price, portion)) + ' le sachet.'
        : 'Votre commande débloque le palier ' + after.tier + ' : le prix baisse pour tout le monde !';
    } else if (after.next) {
      msg = 'Votre commande rapproche la communauté du palier ' + after.next.level + ' : il manquera encore ' + after.partsMissing + ' ' + plural(after.partsMissing, 'part', 'parts') + '.';
    } else {
      msg = 'Prix plancher atteint : récolte abondante !';
    }
    $('[data-impact]', form).textContent = msg;
    if (bigPlant) {
      setPlant(bigPlant, after);
      bigPlant.classList.toggle('is-preview', after.plant !== now.plant);
      previewing = true;
      if (animate) water(bigPlant);
    }
  }
  if (form && campData) {
    form.addEventListener('change', function () { updateOrder(true); });
    form.addEventListener('input', function (e) { if (e.target.name === 'packets') updateOrder(true); });
    $$('[data-step]', form).forEach(function (b) {
      b.addEventListener('click', function () {
        var i = $('input[name="packets"]', form);
        i.value = Math.max(1, Math.min(parseInt(i.max || '99', 10), (parseInt(i.value, 10) || 1) + parseInt(b.getAttribute('data-step'), 10)));
        updateOrder(true);
      });
    });
    updateOrder(false);

    form.addEventListener('submit', function (e) {
      if (!window.fetch) return;
      e.preventDefault();
      var btn = $('button[type="submit"]', form); btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          btn.disabled = false;
          if (res.error) { toast('<p>' + res.error + '</p>'); return; }
          var c = $('[data-cart-count]'); if (c) { c.textContent = res.count; c.hidden = false; }
          water(bigPlant);
          toast('<p>' + res.message + '</p><a class="btn btn--go btn--sm" href="/engagement">M\'engager</a><a class="btn btn--chalk btn--sm" href="/panier">Voir le panier</a>', 8000);
        })
        .catch(function () { btn.disabled = false; form.submit(); });
    });
  }

  /* ---------------- Mise à jour en direct (toutes les 20 s) ---------------- */
  var lives = $$('[data-camp-live]');
  function refreshLive() {
    if (document.hidden) return;
    lives.forEach(function (box) {
      var id = box.getAttribute('data-camp-live');
      fetch('/api/campagne/' + id, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d) return;
          var s = d.state;
          var set = function (k, v) { $$('[data-live="' + k + '"]', box).forEach(function (el) { el.textContent = v; }); };
          set('pricePortion', d.pricePortion);
          set('priceRef', d.priceRef);
          set('participants', d.participants);
          var g = $('.gauge', box); if (g) g.style.setProperty('--p', s.validated ? s.to_next_pct : s.to_t1_pct);
          set('noteText', s.next_tier ? 'Plus que ' + s.parts_missing + ' ' + plural(s.parts_missing, 'part', 'parts') + (s.tier === 0 ? ' pour valider la commande !' : ' pour le palier ' + s.next_tier + ' !') : 'Récolte abondante : prix plancher débloqué !');
          $$('[data-tier]', box).forEach(function (li) {
            var l = +li.getAttribute('data-tier');
            li.classList.toggle('is-reached', l <= s.tier);
            li.classList.toggle('is-active', l === s.tier);
          });
          var p = $('.plant', box);
          if (p) setPlant(p, { plant: s.plant_state, growth: s.growth, toT1: s.to_t1_pct / 100 });
          if (campData && +id === campData.id && s.reserved !== campData.reserved) {
            var grew = s.reserved > campData.reserved;
            campData.reserved = s.reserved;
            if (form) updateOrder(false);
            else if (bigPlant) setPlant(bigPlant, { plant: s.plant_state, growth: s.growth, toT1: s.to_t1_pct / 100 });
            if (grew && bigPlant) { water(bigPlant); toast('<p>Un voisin vient de rejoindre la campagne : la plante pousse !</p>', 4000); }
          }
        }).catch(function () {});
    });
  }
  if (lives.length && window.fetch) setInterval(refreshLive, 20000);

  /* ---------------- Catalogue : filtres sans rechargement ---------------- */
  var filters = $('[data-filters]');
  var grid = $('[data-crates]');
  if (filters && grid) {
    var cat = '';
    var cards = $$('.crate', grid);
    var apply = function () {
      var q = (filters.q.value || '').trim().toLowerCase();
      var expo = filters.expo.value, month = filters.month.value, diff = filters.diff.value, sort = filters.sort.value;
      var shown = 0;
      cards.forEach(function (c) {
        var ok = (!cat || c.dataset.cat === cat) && (!expo || c.dataset.expo === expo) && (!diff || c.dataset.diff === diff)
          && (!month || (',' + c.dataset.months + ',').indexOf(',' + month + ',') >= 0) && (!q || c.dataset.name.indexOf(q) >= 0);
        c.hidden = !ok; if (ok) shown++;
      });
      var key = { soon: function (c) { return -parseFloat(c.dataset.pct); }, end: function (c) { return +c.dataset.end; },
        new: function (c) { return -c.dataset.start; }, drop: function (c) { return -c.dataset.drop * 1000 - parseFloat(c.dataset.pct); } }[sort];
      cards.slice().sort(function (a, b) { return key(a) - key(b); }).forEach(function (c) { grid.appendChild(c); });
      var empty = $('[data-empty]'); if (empty) empty.hidden = shown > 0;
    };
    $$('.filter-cats button', filters).forEach(function (b) {
      b.addEventListener('click', function () {
        cat = b.dataset.cat;
        $$('.filter-cats button', filters).forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        apply();
      });
    });
    filters.addEventListener('input', apply);
    filters.addEventListener('change', apply);
    var reset = $('[data-reset]');
    if (reset) reset.addEventListener('click', function () { filters.reset(); cat = ''; $$('.filter-cats button', filters).forEach(function (x, i) { x.setAttribute('aria-pressed', i === 0 ? 'true' : 'false'); }); apply(); });
    var params = new URLSearchParams(location.search);
    if (params.get('univers')) { var b = $('.filter-cats button[data-cat="' + params.get('univers') + '"]', filters); if (b) b.click(); }
    apply();
  }

  /* ---------------- Partage ---------------- */
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var text = b.getAttribute('data-share-text'), url = b.getAttribute('data-share-url');
      if (navigator.share) { navigator.share({ title: document.title, text: text, url: url }).catch(function () {}); return; }
      (navigator.clipboard ? navigator.clipboard.writeText(text + ' ' + url) : Promise.reject()).then(function () { toast('<p>Lien copié : collez-le dans vos messages !</p>', 3500); })
        .catch(function () { window.prompt('Copiez ce lien :', url); });
    });
  });

  /* ---------------- Atelier : bascule "ensaché" en AJAX ---------------- */
  $$('[data-pack-toggle]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.fetch) return;
      e.preventDefault();
      var row = f.closest('.pack-item'), btn = $('button', f);
      var packed = !row.classList.contains('is-packed');
      var fd = new FormData(f); fd.set('packed', packed ? '1' : '0');
      btn.disabled = true;
      fetch(f.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          btn.disabled = false;
          if (res.error) { toast('<p>' + res.error + '</p>'); return; }
          row.classList.toggle('is-packed', packed);
          btn.textContent = packed ? '✓ Ensaché' : 'Marquer ensaché';
          if (res.ready) toast('<p>Commande complète : le client reçoit son Pass Retrait.</p>', 4000);
          var counter = $('[data-packed-count]'); if (counter && typeof res.packedCount === 'number') counter.textContent = res.packedCount;
        }).catch(function () { btn.disabled = false; f.submit(); });
    });
  });

  window.JDC.campState = campState;
})();
