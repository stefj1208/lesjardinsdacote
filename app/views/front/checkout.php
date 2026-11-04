<div class="wrap page">
  <h1>Confirmer mon achat</h1>
  <form method="post" action="/commande" id="checkout-form" class="layout-aside mt-3"
        <?php if ($clientSecret): ?>data-stripe-key="<?= e(config('stripe.public_key')) ?>" data-client-secret="<?= e($clientSecret) ?>" data-return-url="<?= e(abs_url('/commande')) ?>"<?php endif; ?>
        <?php if ($returning): ?>data-returning="<?= e($returning) ?>"<?php endif; ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="setup_intent_id" value="<?= e($returning ?? '') ?>">
    <div>
      <section class="panel">
        <h2>Vos packs</h2>
        <?php foreach ($items as $it): ?>
          <div class="cart-line cart-line--sm">
            <?= ph($it['photo'], $it['pack_name'], '', 300) ?>
            <div><strong><?= (int)$it['quantity'] ?> × <?= e($it['pack_name']) ?></strong><div class="small muted"><?= e($it['group_name']) ?> · lot n° <?= $it['batch']['no'] ?> (<?= $it['batch']['fill'] ?>/<?= $it['batch']['size'] ?>)</div></div>
            <strong><?= money($it['total']) ?></strong>
          </div>
        <?php endforeach; ?>
        <div class="field mt-3"><label for="notes">Un mot pour les bénévoles <span class="muted">(facultatif)</span></label><textarea id="notes" name="notes" maxlength="500" style="min-height:70px"></textarea></div>
        <?php if (!$user['phone']): ?><div class="field"><label for="phone">Téléphone <span class="muted">(facultatif, rappel de retrait)</span></label><input id="phone" name="phone" type="tel" autocomplete="tel"></div><?php endif; ?>
      </section>
      <section class="panel">
        <h2>Paiement</h2>
        <?php if ($clientSecret): ?>
          <p class="muted small">Votre carte est enregistrée et vérifiée par Stripe. <strong>Aucun débit aujourd'hui</strong> : le prélèvement a lieu quand votre lot de 10 achats est complet. S'il ne l'est jamais, rien n'est prélevé.</p>
          <div id="payment-element"></div>
          <p class="error-text" id="payment-errors" role="alert"></p>
        <?php else: ?>
          <?php if ($stripeError): ?><div class="notice mb-2"><?= e($stripeError) ?></div><?php endif; ?>
          <input type="hidden" name="pay_mode" value="on_pickup_fallback">
          <p class="mb-0"><strong>Paiement au retrait.</strong> Vous réglerez sur place (espèces, chèque ou carte), seulement si votre lot de 10 achats est complet.</p>
        <?php endif; ?>
      </section>
    </div>
    <aside class="panel summary">
      <h2>Où va votre argent</h2>
      <?php partial('partials/breakdown', ['b' => $b]) ?>
      <?php if ($b['needs_membership']): ?>
        <label class="check mt-3"><input type="checkbox" name="adhesion" value="1" required><span>J'adhère à l'association <?= e(setting('association_name')) ?> pour <?= Asso::year() ?><?= $b['membership'] > 0 ? ' (' . money($b['membership']) . ')' : ' (adhésion offerte)' ?> et j'accepte ses <a href="/association#statuts" target="_blank">statuts</a>.</span></label>
      <?php endif; ?>
      <label class="check mt-2"><input type="checkbox" name="cgv" value="1" required><span>Je donne mandat à l'association d'acheter ces produits <strong>en mon nom</strong>, au prix coûtant, selon les <a href="/cgv" target="_blank">conditions du mandat d'achat groupé</a>.</span></label>
      <button class="btn btn--accent btn--lg btn--block mt-3" type="submit" id="checkout-submit" data-sprout>Valider ma commande</button>
      <p class="small muted mt-2 mb-0">L'association ne prend aucune marge sur les produits. Les frais financent la logistique (transport, sachets, étiquettes, outil en ligne) et les plantations.</p>
    </aside>
  </form>
</div>
