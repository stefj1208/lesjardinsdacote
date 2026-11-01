/* Engagement : enregistrement de la carte via Stripe SetupIntent (3-D Secure), sans débit. */
(function () {
  'use strict';
  var form = document.getElementById('checkout-form');
  if (!form || !window.Stripe) return;
  var key = form.getAttribute('data-stripe-key');
  var secret = form.getAttribute('data-client-secret');
  var stripe = Stripe(key, { locale: 'fr' });
  var errors = document.getElementById('payment-errors');
  var btn = document.getElementById('checkout-submit');
  var STORE = 'jdc-checkout';

  // Retour après une redirection bancaire : on restaure les choix et on valide.
  var returning = form.getAttribute('data-returning');
  if (returning) {
    try {
      var saved = JSON.parse(sessionStorage.getItem(STORE) || '{}');
      Object.keys(saved).forEach(function (n) {
        var el = form.elements[n];
        if (!el) return;
        if (el.length && el[0] && el[0].type === 'radio') { Array.prototype.forEach.call(el, function (r) { r.checked = r.value === saved[n]; }); }
        else if (el.type === 'checkbox') el.checked = !!saved[n];
        else el.value = saved[n];
      });
    } catch (e) {}
    form.elements.setup_intent_id.value = returning;
    if (form.elements.cgv.checked) { HTMLFormElement.prototype.submit.call(form); return; }
  }

  var elements = stripe.elements({
    clientSecret: secret,
    appearance: {
      theme: 'stripe',
      variables: { colorPrimary: '#1F4E3D', colorText: '#2E383B', fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif', borderRadius: '10px' }
    }
  });
  var payment = elements.create('payment', { layout: 'tabs' });
  payment.mount('#payment-element');

  form.addEventListener('submit', function (e) {
    if (form.elements.setup_intent_id.value) return;
    e.preventDefault();
    if (!form.elements.cgv.checked) { form.elements.cgv.reportValidity(); return; }
    btn.disabled = true; btn.textContent = 'Vérification de la carte…'; errors.textContent = '';
    var data = {};
    ['container', 'notes', 'phone', 'cgv'].forEach(function (n) {
      var el = form.elements[n]; if (!el) return;
      data[n] = el.type === 'checkbox' ? el.checked : el.value;
    });
    try { sessionStorage.setItem(STORE, JSON.stringify(data)); } catch (err) {}
    stripe.confirmSetup({ elements: elements, redirect: 'if_required', confirmParams: { return_url: form.getAttribute('data-return-url') } })
      .then(function (res) {
        if (res.error) {
          errors.textContent = res.error.message || 'La carte n\'a pas pu être validée.';
          btn.disabled = false; btn.textContent = 'Valider mon engagement';
          return;
        }
        form.elements.setup_intent_id.value = res.setupIntent.id;
        btn.textContent = 'Enregistrement…';
        HTMLFormElement.prototype.submit.call(form);
      });
  });
})();
