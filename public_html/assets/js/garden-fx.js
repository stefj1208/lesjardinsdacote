/* Les Jardins d'à Côté — micro-interactions végétales (sans dépendance)
   1. Germination au clic   2. Liane de progression   3. Rideau de feuilles   4. Goutte d'eau et pollen */
(function () {
  'use strict';
  var doc = document, html = doc.documentElement, body = doc.body;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var admin = body.classList.contains('admin');
  var $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };

  // L'arrivée (rideau qui s'ouvre) est jouée en CSS dès le premier rendu ; on nettoie ensuite.
  if (html.classList.contains('canopy-arrive')) setTimeout(function () { html.classList.remove('canopy-arrive'); }, 1000);
  window.addEventListener('pageshow', function (e) { if (e.persisted) html.classList.remove('canopy-leave', 'canopy-arrive'); });
  if (reduce) return;

  /* ---------- 1. Germination au clic ---------- */
  function sprout(el, x, y) {
    var r = el.getBoundingClientRect();
    if (x == null) { x = r.left + r.width / 2; y = r.top + r.height / 2; }
    var s = doc.createElement('span');
    s.className = 'sprout';
    s.style.left = x + 'px'; s.style.top = Math.min(y, r.bottom - 4) + 'px';
    s.style.setProperty('--w', Math.max(60, r.width) + 'px');
    s.style.setProperty('--h', Math.max(30, r.height) + 'px');
    s.innerHTML = '<i class="seed"></i><i class="lf lf--l"></i><i class="lf lf--r"></i><i class="bloom"></i>';
    body.appendChild(s);
    el.classList.remove('is-sprouted'); void el.offsetWidth; el.classList.add('is-sprouted');
    setTimeout(function () { s.remove(); el.classList.remove('is-sprouted'); }, 900);
  }
  doc.addEventListener('pointerdown', function (e) {
    if (e.button !== 0) return;
    var el = e.target.closest && e.target.closest('.btn, .nav-cta, [data-sprout], .vcard');
    if (el && !el.disabled) sprout(el, e.clientX, e.clientY);
  }, { passive: true });
  // Clavier : même retour visuel à la validation.
  doc.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var el = doc.activeElement;
    if (el && el.matches && el.matches('.btn, [data-sprout]')) sprout(el);
  });

  if (admin) return;

  /* ---------- 2. Liane de progression (bord droit, grands écrans) ---------- */
  (function trellis() {
    var sections = $$('main > section').filter(function (s) { return s.offsetHeight > 160 && s.querySelector('h1, h2'); });
    if (sections.length < 3 || doc.documentElement.scrollHeight < window.innerHeight * 2) return;
    var NS = 'http://www.w3.org/2000/svg';
    var box = doc.createElement('div'); box.className = 'trellis'; box.setAttribute('aria-hidden', 'false');
    var svg = doc.createElementNS(NS, 'svg'); box.appendChild(svg); body.appendChild(box);
    var stake, vine, tip, buds = [], len = 0, H = 0, lastP = -1, idle;

    function el(tag, attrs, parent) { var n = doc.createElementNS(NS, tag); for (var k in attrs) n.setAttribute(k, attrs[k]); (parent || svg).appendChild(n); return n; }
    function build() {
      while (svg.firstChild) svg.removeChild(svg.firstChild);
      H = box.clientHeight; if (H < 200) return;
      svg.setAttribute('viewBox', '0 0 34 ' + H);
      stake = el('line', { x1: 17, y1: 0, x2: 17, y2: H, 'class': 'stake' });
      // Tige qui s'enroule autour du tuteur : demi-boucles alternées.
      var d = 'M17 0', step = 46, y = 0, side = 1;
      while (y < H) { var ny = Math.min(H, y + step); d += ' C' + (17 + 13 * side) + ' ' + (y + step * .25) + ' ' + (17 + 13 * side) + ' ' + (y + step * .75) + ' 17 ' + ny; y = ny; side = -side; }
      vine = el('path', { d: d, 'class': 'vine' });
      len = vine.getTotalLength();
      vine.style.strokeDasharray = len; vine.style.strokeDashoffset = len;
      buds = sections.map(function (sec, i) {
        var title = (sec.querySelector('h1, h2').textContent || '').trim().replace(/\s+/g, ' ');
        var g = el('g', { 'class': 'bud', tabindex: 0, role: 'link', 'aria-label': 'Aller à : ' + title });
        g._sec = sec; g._title = title;
        g.appendChild(Object.assign(doc.createElementNS(NS, 'circle'), {})).setAttribute('r', 6);
        var leaf = doc.createElementNS(NS, 'path'); leaf.setAttribute('d', 'M0 0 C-7 -4 -7 -13 0 -17 C7 -13 7 -4 0 0Z'); g.appendChild(leaf);
        var t = doc.createElementNS(NS, 'text'); t.setAttribute('x', -12); t.setAttribute('y', 4); t.setAttribute('text-anchor', 'end'); t.textContent = title.length > 34 ? title.slice(0, 32) + '…' : title; g.appendChild(t);
        g.addEventListener('click', function () { sec.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
        g.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sec.scrollIntoView({ behavior: 'smooth', block: 'start' }); } });
        return g;
      });
      tip = el('g', { 'class': 'tip' });
      var tl = doc.createElementNS(NS, 'path'); tl.setAttribute('d', 'M0 0 C-9 -3 -11 -14 -3 -20 C2 -12 4 -5 0 0Z'); tl.setAttribute('fill', '#6A9E6D'); tip.appendChild(tl);
      var tr = doc.createElementNS(NS, 'path'); tr.setAttribute('d', 'M0 0 C8 -2 11 -11 5 -16 C1 -10 -1 -4 0 0Z'); tr.setAttribute('fill', '#4A7D4F'); tip.appendChild(tr);
      place(); update(true);
    }
    // Position des bourgeons : début de chaque section rapporté à la hauteur totale de la page.
    function place() {
      var max = Math.max(1, doc.documentElement.scrollHeight - window.innerHeight);
      buds.forEach(function (g) {
        var f = Math.min(.98, Math.max(.02, (g._sec.getBoundingClientRect().top + window.scrollY - window.innerHeight * .35) / max));
        g._f = f;
        var p = vine.getPointAtLength(f * len);
        g.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ')');
      });
    }
    function update(force) {
      if (!vine) return;
      var max = Math.max(1, doc.documentElement.scrollHeight - window.innerHeight);
      var p = Math.min(1, Math.max(0, window.scrollY / max));
      if (!force && Math.abs(p - lastP) < .0005) return;
      lastP = p;
      vine.style.strokeDashoffset = (len * (1 - p)).toFixed(1);
      var pt = vine.getPointAtLength(Math.max(.5, p * len));
      tip.setAttribute('transform', 'translate(' + pt.x.toFixed(1) + ' ' + pt.y.toFixed(1) + ')');
      buds.forEach(function (g) { g.classList.toggle('is-open', p >= g._f - .005); });
      box.classList.toggle('is-on', window.scrollY > 120);
    }
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) { ticking = true; requestAnimationFrame(function () { ticking = false; update(); }); }
      // Arrêt du défilement : l'extrémité oscille avec une inertie naturelle.
      clearTimeout(idle);
      idle = setTimeout(function () { if (!tip) return; tip.classList.remove('is-sway'); void tip.getBoundingClientRect(); tip.classList.add('is-sway'); }, 140);
    }, { passive: true });
    var rs; window.addEventListener('resize', function () { clearTimeout(rs); rs = setTimeout(build, 200); });
    window.addEventListener('load', function () { place(); update(true); });
    build();
  })();

  /* ---------- 3. Rideau de feuilles entre les pages ---------- */
  if (doc.querySelector('.canopy')) {
    doc.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-canopy')) return;
      var url;
      try { url = new URL(a.href, location.href); } catch (err) { return; }
      if (url.origin !== location.origin || /^\/(admin|benevoles|qr|r\/|retrait|compte\/commande\/.+\/facture)/.test(url.pathname)) return;
      if (url.pathname === location.pathname && url.search === location.search) return; // ancre sur la même page
      e.preventDefault();
      try { sessionStorage.setItem('jdc-canopy', '1'); } catch (err) {}
      html.classList.add('canopy-leave');
      setTimeout(function () { location.href = url.href; }, 430);
    });
  }

  /* ---------- 4. Goutte d'eau et traînée de pollen (souris uniquement) ---------- */
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  var drop = doc.createElement('div'); drop.className = 'drop is-hidden'; body.appendChild(drop);
  html.classList.add('has-drop');
  var cv = doc.createElement('canvas'); cv.className = 'pollen'; body.appendChild(cv);
  var ctx = cv.getContext('2d'), dpr = Math.min(2, window.devicePixelRatio || 1), parts = [], running = false;
  function size() { cv.width = innerWidth * dpr; cv.height = innerHeight * dpr; cv.style.width = innerWidth + 'px'; cv.style.height = innerHeight + 'px'; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); }
  size(); window.addEventListener('resize', size);
  var mx = -100, my = -100, lastEmit = 0, glowEl = null, glowT;
  function loop() {
    ctx.clearRect(0, 0, innerWidth, innerHeight);
    for (var i = parts.length - 1; i >= 0; i--) {
      var p = parts[i];
      p.life -= 1; p.x += p.vx; p.y += p.vy; p.vy -= .015; p.vx *= .985;
      if (p.life <= 0) { parts.splice(i, 1); continue; }
      var a = p.life / p.max;
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r * (0.6 + a * .4), 0, 6.283);
      ctx.fillStyle = 'rgba(' + p.c + ',' + (a * .9).toFixed(3) + ')'; ctx.fill();
    }
    if (parts.length) requestAnimationFrame(loop); else { running = false; ctx.clearRect(0, 0, innerWidth, innerHeight); }
  }
  function emit(x, y, n) {
    for (var i = 0; i < n; i++) {
      var max = 26 + Math.random() * 22;
      parts.push({ x: x + (Math.random() - .5) * 10, y: y + (Math.random() - .5) * 10, vx: (Math.random() - .5) * .9, vy: (Math.random() - .2) * .6,
        r: 1 + Math.random() * 1.8, life: max, max: max, c: Math.random() < .7 ? '245,184,61' : '255,229,138' });
    }
    if (parts.length > 160) parts.splice(0, parts.length - 160);
    if (!running) { running = true; requestAnimationFrame(loop); }
  }
  doc.addEventListener('mousemove', function (e) {
    mx = e.clientX; my = e.clientY;
    drop.style.transform = 'translate3d(' + mx + 'px,' + my + 'px,0)';
    drop.classList.remove('is-hidden');
    var t = e.target;
    var field = t.closest && t.closest('input, textarea, select, [contenteditable]');
    drop.classList.toggle('is-text', !!field);
    drop.classList.toggle('is-link', !field && !!(t.closest && t.closest('a, button, label, summary, [role=link], .vcard')));
    // Images et titres clés : pollen doré et couleurs ravivées.
    var target = t.closest && t.closest('.ph, .reveal-img, .tile, .ucard-photo, h1, h2');
    if (target) {
      var now = performance.now();
      if (now - lastEmit > 28) { lastEmit = now; emit(mx, my, 2); }
      var img = target.matches('h1, h2') ? null : target;
      if (img && img !== glowEl) { if (glowEl) glowEl.classList.remove('is-pollinated'); glowEl = img; img.classList.add('is-pollinated'); }
      clearTimeout(glowT);
      glowT = setTimeout(function () { if (glowEl) glowEl.classList.remove('is-pollinated'); glowEl = null; }, 900);
    }
  }, { passive: true });
  doc.addEventListener('mouseleave', function () { drop.classList.add('is-hidden'); });
  doc.addEventListener('mousedown', function () { drop.style.scale = '.8'; });
  doc.addEventListener('mouseup', function () { drop.style.scale = ''; emit(mx, my, 6); });
})();

/* ---------- 5. Coccinelles qui se baladent et 6. Feuilles de saison (page d'accueil) ---------- */
(function () {
  'use strict';
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var hero = document.querySelector('.h5');
  if (!hero) return;

  /* Feuilles de saison : automne = feuilles rousses, hiver = flocons, printemps = pétales, été = aigrettes de pissenlit */
  (function seasonal() {
    var d = new Date(), md = (d.getMonth() + 1) * 100 + d.getDate();
    var season = md >= 1221 || md < 320 ? 'winter' : md < 621 ? 'spring' : md < 923 ? 'summer' : 'autumn';
    var palette = {
      autumn: ['#C8662D', '#D9902E', '#A8452A', '#E0B24A', '#8C5A2B'],
      winter: ['#FFFFFF', '#EAF2FB', '#DCE8F5'],
      spring: ['#F6C6D3', '#F9DCE4', '#FFFFFF', '#F3B3C4'],
      summer: ['#FFFFFF', '#F4F1E6']
    }[season];
    var cv = document.createElement('canvas');
    cv.className = 'season-fx season-fx--' + season; cv.setAttribute('aria-hidden', 'true');
    hero.style.position = hero.style.position || 'relative';
    hero.appendChild(cv);
    var ctx = cv.getContext('2d'), dpr = Math.min(2, window.devicePixelRatio || 1), W = 0, H = 0, items = [], visible = true, raf = 0, last = 0;
    var count = window.innerWidth < 700 ? 9 : 16;
    function size() { W = hero.clientWidth; H = hero.clientHeight; cv.width = W * dpr; cv.height = H * dpr; cv.style.width = W + 'px'; cv.style.height = H + 'px'; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); }
    function spawn(initial) {
      return { x: Math.random() * W, y: initial ? Math.random() * H : -20 - Math.random() * 60, s: season === 'winter' ? 2 + Math.random() * 3 : 7 + Math.random() * 8,
        vy: season === 'winter' ? .35 + Math.random() * .5 : .45 + Math.random() * .7, sw: Math.random() * 6.28, sws: .01 + Math.random() * .02,
        rot: Math.random() * 6.28, vr: (Math.random() - .5) * .04, c: palette[(Math.random() * palette.length) | 0] };
    }
    function drawLeaf(p) {
      ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.fillStyle = p.c; ctx.globalAlpha = .85;
      if (season === 'winter') { ctx.beginPath(); ctx.arc(0, 0, p.s, 0, 6.283); ctx.fill(); }
      else if (season === 'summer') { ctx.strokeStyle = p.c; ctx.globalAlpha = .7; ctx.lineWidth = .8; for (var i = 0; i < 8; i++) { ctx.rotate(.785); ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, p.s * .8); ctx.stroke(); } }
      else if (season === 'spring') { ctx.beginPath(); ctx.ellipse(0, 0, p.s * .55, p.s * .35, 0, 0, 6.283); ctx.fill(); }
      else { ctx.beginPath(); ctx.moveTo(0, -p.s); ctx.quadraticCurveTo(p.s * .8, -p.s * .2, 0, p.s); ctx.quadraticCurveTo(-p.s * .8, -p.s * .2, 0, -p.s); ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,.18)'; ctx.lineWidth = .8; ctx.beginPath(); ctx.moveTo(0, -p.s); ctx.lineTo(0, p.s); ctx.stroke(); }
      ctx.restore();
    }
    function frame(t) {
      raf = 0;
      if (!visible || document.hidden) return;
      var dt = Math.min(3, (t - last) / 16.7 || 1); last = t;
      ctx.clearRect(0, 0, W, H);
      items.forEach(function (p, i) {
        p.sw += p.sws * dt; p.y += p.vy * dt; p.x += Math.sin(p.sw) * .6 * dt; p.rot += p.vr * dt;
        if (p.y > H + 20) items[i] = spawn(false);
        drawLeaf(p);
      });
      raf = requestAnimationFrame(frame);
    }
    function start() { if (!raf) { last = performance.now(); raf = requestAnimationFrame(frame); } }
    size(); for (var i = 0; i < count; i++) items.push(spawn(true));
    window.addEventListener('resize', function () { size(); });
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; if (visible) start(); }).observe(hero);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) start(); });
    start();
  })();

  /* Coccinelles : se promènent doucement, s'envolent quand on les touche, puis reviennent */
  (function ladybugs() {
    var SVG = '<svg viewBox="0 0 40 40" aria-hidden="true"><ellipse cx="20" cy="23" rx="12" ry="13" fill="#D7372C"/><path d="M20 10v26" stroke="#1B1B1B" stroke-width="1.6"/><circle cx="20" cy="10" r="6" fill="#1B1B1B"/><circle cx="17.5" cy="8.6" r="1.2" fill="#fff"/><circle cx="22.5" cy="8.6" r="1.2" fill="#fff"/><circle cx="14" cy="19" r="2.4" fill="#1B1B1B"/><circle cx="26" cy="19" r="2.4" fill="#1B1B1B"/><circle cx="13.5" cy="28" r="2.2" fill="#1B1B1B"/><circle cx="26.5" cy="28" r="2.2" fill="#1B1B1B"/><circle cx="20" cy="31.5" r="1.8" fill="#1B1B1B"/><g stroke="#1B1B1B" stroke-width="1.4" stroke-linecap="round"><path d="M9 18l-4-2M9 24H4M10 30l-4 3M31 18l4-2M31 24h5M30 30l4 3"/></g></svg>';
    var n = window.innerWidth < 700 ? 1 : 2;
    for (var i = 0; i < n; i++) (function (k) {
      var bug = document.createElement('button');
      bug.type = 'button'; bug.className = 'ladybug'; bug.setAttribute('aria-label', 'Une coccinelle'); bug.tabIndex = -1;
      bug.innerHTML = SVG; hero.appendChild(bug);
      var x, y, a, speed = .35 + Math.random() * .25, turn = 0, flying = false, t0 = 0;
      function place() { x = Math.random() * (hero.clientWidth - 60) + 30; y = hero.clientHeight * (.55 + Math.random() * .35); a = Math.random() * 6.283;
        bug.style.transform = 'translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px) rotate(' + (a * 57.3 + 90).toFixed(1) + 'deg)'; }
      bug.style.opacity = '0';
      place();
      function step(t) {
        if (!flying && !document.hidden) {
          turn += (Math.random() - .5) * .08; turn *= .96; a += turn;
          x += Math.cos(a) * speed; y += Math.sin(a) * speed;
          var W = hero.clientWidth, H = hero.clientHeight;
          if (x < 20 || x > W - 20) { a = Math.PI - a; x = Math.max(20, Math.min(W - 20, x)); }
          if (y < H * .35 || y > H - 20) { a = -a; y = Math.max(H * .35, Math.min(H - 20, y)); }
          // petites pauses, comme une vraie coccinelle
          if (Math.random() < .004) { t0 = t + 600 + Math.random() * 1400; }
          if (t < t0) { x -= Math.cos(a) * speed; y -= Math.sin(a) * speed; }
          bug.style.transform = 'translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px) rotate(' + (a * 57.3 + 90).toFixed(1) + 'deg)';
        }
        requestAnimationFrame(step);
      }
      bug.addEventListener('pointerenter', fly); bug.addEventListener('click', fly);
      function fly() {
        if (flying) return; flying = true;
        bug.classList.add('is-flying');
        bug.style.transform = 'translate(' + (x + (Math.random() - .5) * 300).toFixed(0) + 'px,' + (y - 500).toFixed(0) + 'px) rotate(' + ((Math.random() - .5) * 720).toFixed(0) + 'deg) scale(.6)';
        setTimeout(function () { bug.classList.remove('is-flying'); place(); bug.style.opacity = '0'; flying = false; setTimeout(function () { bug.style.opacity = ''; }, 50); }, 9000 + Math.random() * 8000);
      }
      setTimeout(function () { bug.style.opacity = ''; requestAnimationFrame(step); }, 1200 + k * 2500);
    })(i);
  })();
})();
