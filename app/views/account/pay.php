<div class="wrap page" style="max-width:620px">
  <h1 class="page-title">Régler la commande <?= e($o['reference']) ?></h1>
  <form id="pay-form" class="panel" method="post" action="/compte/paiement/<?= e($o['reference']) ?>"
        data-stripe-key="<?= e(config('stripe.public_key')) ?>" data-client-secret="<?= e($clientSecret) ?>" data-return-url="<?= e(abs_url('/compte/commande/' . $o['reference'])) ?>">
    <?= csrf_field() ?>
    <p>Montant final : <strong style="font-size:1.3rem"><?= money($o['amount']) ?></strong></p>
    <div id="payment-element"></div>
    <p class="error-text" id="payment-errors" role="alert"></p>
    <button class="btn btn--accent btn--block btn--lg" type="submit">Payer <?= money($o['amount']) ?></button>
  </form>
</div>
