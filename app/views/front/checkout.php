<?php $hasBulk = (bool)array_filter($cart['lines'], fn($l) => $l['campaign']['category'] === 'substrate'); ?>
<div class="wrap page">
  <h1 class="page-title">Mon engagement</h1>
  <form method="post" action="/engagement" id="checkout-form" class="layout-aside"
        <?php if ($clientSecret): ?>data-stripe-key="<?= e(config('stripe.public_key')) ?>" data-client-secret="<?= e($clientSecret) ?>" data-return-url="<?= e(abs_url('/engagement')) ?>"<?php endif; ?>
        <?php if ($returning): ?>data-returning="<?= e($returning) ?>"<?php endif; ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="setup_intent_id" value="<?= e($returning ?? '') ?>">
    <div>
      <section class="panel">
        <h2>Vos sachets</h2>
        <?php foreach ($cart['groups'] as $g): ?>
          <p class="label mt-4"><?= e($g['label']) ?></p>
          <div class="table-wrap"><table class="table">
            <tbody><?php foreach ($g['lines'] as $l): ?>
              <tr><td><?= e($l['campaign']['title']) ?><br><small class="muted"><?= $l['packets'] ?> × <?= e(Units::qty($l['portion'], $l['campaign']['unit'])) ?></small></td><td class="num"><?= money($l['total']) ?></td></tr>
            <?php endforeach; ?></tbody>
          </table></div>
        <?php endforeach; ?>
      </section>

      <section class="panel">
        <h2>Contenant</h2>
        <div class="radio-cards">
          <label><input type="radio" name="container" value="kraft" checked><span><strong>Sachets kraft biodégradables</strong><br><small class="muted">Inclus dans le prix, étiquetés par les bénévoles.</small></span></label>
          <label><input type="radio" name="container" value="own"><span><strong>J'apporte mes contenants</strong><br><small class="muted">Petits bocaux ou enveloppes : zéro déchet, on remplit sur place.</small></span></label>
          <?php if ($hasBulk): ?><label><input type="radio" name="container" value="deposit"><span><strong>Sac consigné</strong><br><small class="muted"><?= money((float)setting('deposit_fee', 1)) ?> de consigne rendus au retour du sac.</small></span></label><?php endif; ?>
        </div>
        <div class="field mt-4"><label for="notes">Un mot pour les bénévoles <span class="muted">(facultatif)</span></label><textarea id="notes" name="notes" maxlength="500" style="min-height:70px" placeholder="Ex. : je passerai plutôt en fin de permanence."></textarea></div>
        <?php if (!$user['phone']): ?><div class="field"><label for="phone">Téléphone <span class="muted">(facultatif, pour les rappels de retrait)</span></label><input id="phone" name="phone" type="tel" autocomplete="tel"></div><?php endif; ?>
      </section>

      <section class="panel">
        <h2>Garantie de paiement</h2>
        <?php if ($clientSecret): ?>
          <p class="muted small">Votre carte est enregistrée et vérifiée par Stripe (3-D Secure). <strong>Aucun débit aujourd'hui</strong> : le prélèvement aura lieu à la clôture, au prix du dernier palier atteint. Si la campagne n'est pas validée, rien n'est prélevé.</p>
          <div id="payment-element"></div>
          <p class="error-text" id="payment-errors" role="alert"></p>
        <?php else: ?>
          <?php if ($stripeError): ?><div class="notice mb-6"><?= e($stripeError) ?></div><?php endif; ?>
          <input type="hidden" name="pay_mode" value="on_pickup_fallback">
          <p><strong>Paiement au retrait.</strong> Vous réglerez sur place (espèces, chèque ou carte), au prix du dernier palier atteint. Rien n'est dû si la campagne n'est pas validée.</p>
        <?php endif; ?>
      </section>
    </div>

    <aside class="panel summary">
      <h2>Récapitulatif</h2>
      <div class="line"><span>Montant maximum</span><span><?= money($cart['total']) ?></span></div>
      <div class="line"><span>Débité aujourd'hui</span><span><?= money(0) ?></span></div>
      <p class="small muted">Le montant final sera égal ou inférieur, selon les paliers débloqués d'ici la clôture.</p>
      <label class="check mt-4"><input type="checkbox" name="cgv" value="1" required>
        <span>J'accepte les <a href="/cgv" target="_blank">conditions générales</a> et je donne mandat à l'association pour acheter en mon nom, dans le cadre d'un groupement d'achat, les produits ci-dessus.</span></label>
      <button class="btn btn--go btn--block btn--lg mt-4" type="submit" id="checkout-submit">Valider mon engagement</button>
    </aside>
  </form>
</div>
