<?php $act = function (string $a, string $label, string $cls = 'btn--ghost', string $confirm = '') use ($o) {
    echo '<form method="post" action="/admin/commandes/' . (int)$o['id'] . '/action"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><button class="btn btn--sm ' . $cls . '" type="submit">' . e($label) . '</button></form>';
}; ?>
<div class="admin-head"><h1>Commande <?= e($o['reference']) ?></h1><span class="chip chip--dark"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></div>
<div class="layout-aside">
  <section class="panel">
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Campagne</th><th>Quantité</th><th>Statut</th><th class="num">Prix engagé</th><th class="num">Prix final</th></tr></thead>
      <tbody><?php foreach ($items as $it): ?>
        <tr><td><a href="/admin/campagnes/<?= (int)$it['campaign_id'] ?>"><?= e($it['title']) ?></a></td><td><?= (int)$it['packets'] ?> × <?= e(Units::qty($it['portion_qty'], $it['unit'])) ?></td><td><?= e($it['status']) ?></td>
          <td class="num"><?= money(Pricing::packetPrice((float)$it['unit_price_commit'], (float)$it['portion_qty']) * (int)$it['packets']) ?></td>
          <td class="num"><?= $it['line_total_final'] !== null ? money($it['line_total_final']) : '—' ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <dl class="dl mt-6">
      <dt>Membre</dt><dd><?= e($o['first_name'] . ' ' . $o['last_name']) ?> — <a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a> <?= e($o['phone']) ?></dd>
      <dt>Engagée le</dt><dd><?= e(date_fr($o['created_at'], true)) ?></dd>
      <dt>Contenant</dt><dd><?= e(['kraft' => 'Sachets kraft', 'own' => 'Apporte ses contenants', 'deposit' => 'Sac consigné'][$o['container_choice']]) ?></dd>
      <dt>Code retrait</dt><dd><?= e($o['pickup_code']) ?> · casier <?= e($o['bin_code']) ?> · <a href="/retrait/<?= e($o['pickup_token']) ?>" target="_blank">Pass</a></dd>
      <?php if ($o['notes']): ?><dt>Note</dt><dd><?= e($o['notes']) ?></dd><?php endif; ?>
      <?php if ($o['collected_at']): ?><dt>Retirée le</dt><dd><?= e(date_fr($o['collected_at'], true)) ?></dd><?php endif; ?>
    </dl>
  </section>
  <aside class="stack">
    <div class="panel summary">
      <h2>Paiement</h2>
      <div class="line"><span>Mode</span><span><?= $o['payment_mode'] === 'stripe' ? 'Carte (Stripe)' : 'Au retrait' ?></span></div>
      <div class="line"><span>Statut</span><span><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></span></div>
      <div class="line"><span>Engagé</span><span><?= money($o['amount_committed']) ?></span></div>
      <div class="line total"><span>Final</span><span><?= $o['amount_final'] !== null ? money($o['amount_final']) : '—' ?></span></div>
      <?php if ($o['stripe_payment_intent_id']): ?><p class="small muted">Stripe : <?= e($o['stripe_payment_intent_id']) ?></p><?php endif; ?>
      <?php if ($o['invoice_number']): ?><a class="btn btn--ghost btn--sm btn--block" target="_blank" href="/compte/commande/<?= e($o['reference']) ?>/facture">Facture <?= e($o['invoice_number']) ?></a><?php endif; ?>
    </div>
    <div class="panel stack">
      <h2>Actions</h2>
      <?php if (in_array($o['payment_status'], ['failed', 'action_required'], true)) { if ($o['payment_mode'] === 'stripe') $act('retry', 'Relancer le prélèvement'); $act('to_pickup', 'Basculer en paiement au retrait'); } ?>
      <?php if (in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true) && $o['amount_final'] !== null) $act('mark_paid', 'Marquer comme payée', 'btn--ghost', 'Confirmer l\'encaissement de ' . money($o['amount_final']) . ' ?'); ?>
      <?php if (in_array($o['status'], ['ready', 'confirmed'], true)) $act('resend_pass', 'Renvoyer le Pass Retrait'); ?>
      <?php if ($o['status'] === 'committed') $act('cancel', 'Annuler l\'engagement', 'btn--danger', 'Annuler cet engagement ?'); ?>
      <?php if ($o['payment_status'] === 'paid' && $o['stripe_payment_intent_id']) $act('refund', 'Rembourser', 'btn--danger', 'Rembourser intégralement ' . money($o['amount_final']) . ' ?'); ?>
    </div>
  </aside>
</div>
