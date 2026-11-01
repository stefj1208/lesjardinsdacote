/* Régularisation d'un paiement (authentification bancaire ou nouvelle carte). */
(function () {
  'use strict';
  var form = document.getElementById('pay-form');
  if (!form || !window.Stripe) return;
  var stripe = Stripe(form.getAttribute('data-stripe-key'), { locale: 'fr' });
  var elements = stripe.elements({ clientSecret: form.getAttribute('data-client-secret'),
    appearance: { theme: 'stripe', variables: { colorPrimary: '#1F4E3D', borderRadius: '10px' } } });
  elements.create('payment').mount('#payment-element');
  var btn = form.querySelector('button[type="submit"]');
  var errors = document.getElementById('payment-errors');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    btn.disabled = true; errors.textContent = '';
    stripe.confirmPayment({ elements: elements, redirect: 'if_required', confirmParams: { return_url: form.getAttribute('data-return-url') } })
      .then(function (res) {
        if (res.error) { errors.textContent = res.error.message; btn.disabled = false; return; }
        HTMLFormElement.prototype.submit.call(form);
      });
  });
})();
