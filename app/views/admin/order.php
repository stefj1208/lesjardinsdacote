<?php $act = function (string $a, string $label, string $cls = 'btn--ghost', string $confirm = '') use ($o) {
    echo '<form method="post" action="/admin/commandes/' . (int)$o['id'] . '/action"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><button class="btn btn--sm btn--block ' . $cls . '" type="submit">' . e($label) . '</button></form>';
}; ?>
<div class="admin-head"><h1>Commande <?= e($o['reference']) ?></h1><span class="chip chip--dark"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></div>
<div class="layout-aside">
  <section class="panel">
    <h2><?= (int)$o['quantity'] ?> × <?= e($o['pack_name']) ?></h2>
    <dl class="dl">
      <dt>Membre</dt><dd><?= e($o['first_name'] . ' ' . $o['last_name']) ?> — <a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a> <?= e($o['phone']) ?></dd>
      <dt>Groupe</dt><dd><a href="/admin/ventes/<?= (int)$o['sale_id'] ?>"><?= e($o['group_name']) ?></a> · lot n° <?= (int)$o['batch_no'] ?></dd>
      <dt>Achetée le</dt><dd><?= e(date_fr($o['created_at'], true)) ?><?= $o['from_vote'] ? ' · via son vote' : '' ?></dd>
      <dt>Retrait</dt><dd>code <?= e($o['pickup_code']) ?> · casier <?= e($o['bin_code']) ?> · <a href="/retrait/<?= e($o['pickup_token']) ?>" target="_blank">Pass</a></dd>
      <?php if ($o['notes']): ?><dt>Note</dt><dd><?= e($o['notes']) ?></dd><?php endif; ?>
      <?php if ($o['collected_at']): ?><dt>Remise</dt><dd><?= e(date_fr($o['collected_at'], true)) ?></dd><?php endif; ?>
    </dl>
    <ul class="items mt-3"><?php foreach ($items as $it): ?><li class="item"><?= ph($it['photo'], '', '', 200) ?><span><b><?= e($it['name']) ?></b><small><?= e($it['variety']) ?></small></span><span class="qty"><?= e(Cat::qty((int)$it['quantity'] * (int)$o['quantity'], $it['unit'])) ?></span></li><?php endforeach; ?></ul>
  </section>
  <aside class="stack">
    <div class="panel summary"><h2>Paiement</h2>
      <div class="line"><span>Mode</span><span><?= $o['payment_mode'] === 'stripe' ? 'Carte (Stripe)' : 'Au retrait' ?></span></div>
      <div class="line"><span>Statut</span><span><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></span></div>
      <div class="line total"><span>Montant</span><span><?= money($o['amount']) ?></span></div>
      <?php if ($o['invoice_number']): ?><a class="btn btn--ghost btn--sm btn--block" target="_blank" href="/compte/commande/<?= e($o['reference']) ?>/facture">Facture <?= e($o['invoice_number']) ?></a><?php endif; ?>
    </div>
    <div class="panel stack"><h3>Actions</h3>
      <?php if (in_array($o['payment_status'], ['failed', 'action_required'], true)) { if ($o['payment_mode'] === 'stripe') $act('retry', 'Relancer le prélèvement'); $act('to_pickup', 'Basculer en paiement au retrait'); } ?>
      <?php if (in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true)) $act('mark_paid', 'Marquer comme payée', 'btn--ghost', 'Confirmer l\'encaissement ?'); ?>
      <?php if ($o['status'] === 'ready') $act('resend_pass', 'Renvoyer le Pass Retrait'); ?>
      <?php if ($o['status'] === 'pending') $act('cancel', 'Annuler l\'achat', 'btn--danger', 'Annuler cet achat ?'); ?>
      <?php if ($o['payment_status'] === 'paid' && $o['stripe_payment_intent_id']) $act('refund', 'Rembourser', 'btn--danger', 'Rembourser intégralement ?'); ?>
    </div>
  </aside>
</div>
