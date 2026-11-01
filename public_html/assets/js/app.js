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
