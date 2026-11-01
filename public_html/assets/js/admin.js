/* Back-office : éditeur de paliers, générateur et simulateur de rentabilité, grilles fournisseur. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var money = function (v) { return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v); };
  var num = function (v, d) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: d === undefined ? 2 : d }).format(v); };
  var dec = function (v) { return parseFloat(String(v || '').replace(/\s/g, '').replace(',', '.')) || 0; };
  var toInput = function (v, d) { return (Math.round(v * Math.pow(10, d)) / Math.pow(10, d)).toString().replace('.', ','); };
  var csrf = function () { var i = $('input[name="_csrf"]'); return i ? i.value : ''; };

  /* ---------- Lignes dynamiques (paliers, conditionnements) ---------- */
  function wireRows(table, addBtn, removeSel, onChange) {
    if (!table) return;
    var tbody = $('tbody', table);
    var renumber = function () { $$('tr', tbody).forEach(function (tr, i) { var n = $('.t-num', tr); if (n) n.textContent = i + 1; }); };
    if (addBtn) addBtn.addEventListener('click', function () {
      var last = $$('tr', tbody).pop();
      var clone = last.cloneNode(true);
      $$('input', clone).forEach(function (i) { i.value = ''; });
      tbody.appendChild(clone); renumber(); if (onChange) onChange();
      var first = $('input', clone); if (first) first.focus();
    });
    tbody.addEventListener('click', function (e) {
      var b = e.target.closest(removeSel); if (!b) return;
      var rows = $$('tr', tbody);
      if (rows.length === 1) { $$('input', rows[0]).forEach(function (i) { i.value = ''; }); }
      else b.closest('tr').remove();
      renumber(); if (onChange) onChange();
    });
  }

  /* ---------- Formulaire campagne ---------- */
  var form = $('#campaign-form');
  if (form) {
    var productSel = $('[data-reload-product]', form);
    if (productSel) productSel.addEventListener('change', function () {
      if (productSel.value) window.location.href = '/admin/campagnes/nouvelle?produit=' + productSel.value;
    });
    var table = $('#tiers');
    var simBody = $('#simulation tbody');
    var unit = form.getAttribute('data-unit');
    var unitShort = { g: 'g', seeds: 'graines', l: 'L', kg: 'kg', pot: 'pots', piece: 'pièces' }[unit] || unit;
    var timer;

    var params = function () {
      return {
        product_id: dec($('#product_id_sim').value) || dec(($('#product_id') || {}).value),
        ref_portion: dec(form.ref_portion.value), margin_pct: dec(form.margin_pct.value), waste_pct: dec(form.waste_pct.value),
        packet_cost: dec(form.packet_cost.value), vat_pct: dec(form.vat_pct.value), shipping_share_ht: dec(form.shipping_share_ht.value),
        public_price_part: dec(form.public_price_part.value), include_smallest: $('#include_smallest').checked,
        reserved: dec($('#reserved_sim').value) || null,
        tiers: $$('tbody tr', table).map(function (tr) {
          return { parts: dec($('[name="tier_parts[]"]', tr).value), price: dec($('[name="tier_price[]"]', tr).value), label: $('[name="tier_label[]"]', tr).value };
        })
      };
    };
    var call = function (mode) {
      var body = params(); body.mode = mode;
      return fetch('/admin/api/paliers', { method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf(), 'Accept': 'application/json' }, body: JSON.stringify(body) })
        .then(function (r) { return r.json(); });
    };
    var renderSim = function (rows) {
      if (!rows || !rows.length) { simBody.innerHTML = '<tr><td colspan="6" class="muted">Ajoutez des conditionnements fournisseur au produit et des paliers pour simuler.</td></tr>'; return; }
      simBody.innerHTML = rows.map(function (r) {
        var buy = r.purchase_lines.map(function (l) { return l.count + ' × ' + l.label; }).join(' + ') || '—';
        return '<tr><td>' + r.label + '<br><small class="muted">' + money(r.price * params().ref_portion) + ' / part</small></td>'
          + '<td class="num">' + num(r.qty, 2) + ' ' + unitShort + '</td>'
          + '<td class="num">' + money(r.revenue_ht) + '</td>'
          + '<td>' + buy + '<br><small class="muted">' + money(r.purchase_ht) + ' HT</small></td>'
          + '<td class="num">' + money(r.shipping_ht + r.packets_cost) + '</td>'
          + '<td class="num ' + (r.net_margin >= 0 ? 'pos' : 'neg') + '">' + money(r.net_margin) + '<br><small>' + num(r.net_margin_pct, 1) + ' %</small></td></tr>';
      }).join('');
    };
    var simulate = function () {
      clearTimeout(timer);
      timer = setTimeout(function () { call('simulate').then(function (d) { renderSim(d.simulation); }).catch(function () {}); }, 350);
    };
    wireRows(table, $('[data-add-tier]'), '[data-remove-tier]', simulate);
    form.addEventListener('input', function (e) { if (e.target.hasAttribute('data-sim')) simulate(); });

    var gen = $('[data-generate]');
    if (gen) gen.addEventListener('click', function () {
      var hasValues = $$('[name="tier_parts[]"]', table).some(function (i) { return i.value; });
      if (hasValues && !window.confirm('Remplacer les paliers actuels par ceux calculés depuis la grille fournisseur ?')) return;
      gen.disabled = true;
      call('generate').then(function (d) {
        gen.disabled = false;
        if (!d.tiers || !d.tiers.length) { alert('Impossible de générer des paliers : vérifiez la grille du fournisseur.'); return; }
        var tbody = $('tbody', table), tpl = $('tr', tbody);
        tbody.innerHTML = '';
        d.tiers.forEach(function (t, i) {
          var tr = tpl.cloneNode(true);
          $('.t-num', tr).textContent = i + 1;
          $('[name="tier_parts[]"]', tr).value = toInput(t.parts, 2);
          $('[name="tier_price[]"]', tr).value = toInput(t.price, 2);
          $('[name="tier_label[]"]', tr).value = t.label || '';
          tbody.appendChild(tr);
        });
        if (d.public_price_part) form.public_price_part.value = toInput(d.public_price_part, 2);
        renderSim(d.simulation);
      }).catch(function () { gen.disabled = false; });
    });
    simulate();
  }

  /* ---------- Formulaire produit : conditionnements ---------- */
  var packs = $('#packs');
  if (packs) wireRows(packs, $('[data-add-pack]'), '[data-remove-pack]');
})();
