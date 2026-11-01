<?php
$steps = [
    ['Engagement', true],
    ['Clôture & paiement', in_array($o['status'], ['confirmed', 'ready', 'collected'], true)],
    ['Achat en gros & ensachage', in_array($o['status'], ['ready', 'collected'], true) || ($o['status'] === 'confirmed' && array_filter($items, fn($i) => $i['status'] === 'packed'))],
    ['Prête au retrait', in_array($o['status'], ['ready', 'collected'], true)],
    ['Retirée', $o['status'] === 'collected'],
];
$itemStatus = ['reserved' => 'En cours', 'cancelled' => 'Annulé', 'validated' => 'Validé', 'failed' => 'Non atteint — 0 €', 'packed' => 'Ensaché', 'collected' => 'Retiré'];
?>
<div class="wrap page">
  <p class="crumbs"><a href="/compte">Mon compte</a> / <?= e($o['reference']) ?></p>
  <?php if ($thanks): ?>
    <div class="slate mb-6"><h2 class="chalk" style="font-size:2.2rem;color:var(--chalk-yellow)">Merci, la plante a poussé grâce à vous !</h2>
      <p class="mb-0">Partagez la campagne à vos voisins : chaque nouvel engagement fait baisser votre propre prix.</p></div>
  <?php endif; ?>
  <div class="admin-head">
    <h1>Commande <?= e($o['reference']) ?></h1>
    <span class="chip"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span>
  </div>

  <?php if (!in_array($o['status'], ['cancelled', 'failed'], true)): ?>
  <ol class="timeline" aria-label="Avancement">
    <?php foreach ($steps as [$l, $done]): ?><li class="<?= $done ? 'done' : '' ?>"><?= e($l) ?></li><?php endforeach; ?>
  </ol>
  <?php endif; ?>

  <div class="layout-aside">
    <section class="panel">
      <h2>Détail</h2>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Produit</th><th>Quantité</th><th>Statut</th><th class="num">Montant</th></tr></thead>
        <tbody><?php foreach ($items as $it):
            $price = $it['unit_price_final'] !== null ? (float)$it['unit_price_final'] : (float)$it['current_unit_price'];
            $amount = in_array($it['status'], ['cancelled', 'failed'], true) ? 0 : Pricing::packetPrice($price, (float)$it['portion_qty']) * (int)$it['packets'];
            $commit = Pricing::packetPrice((float)$it['unit_price_commit'], (float)$it['portion_qty']) * (int)$it['packets'];
        ?>
          <tr>
            <td><a href="/campagne/<?= e($it['slug']) ?>"><?= e($it['title']) ?></a></td>
            <td><?= (int)$it['packets'] ?> × <?= e(Units::qty($it['portion_qty'], $it['unit'])) ?></td>
            <td><span class="chip <?= $it['status'] === 'failed' ? 'chip--bad' : ($it['status'] === 'reserved' ? '' : 'chip--ok') ?>"><?= e($itemStatus[$it['status']]) ?></span>
              <?php if ($it['status'] === 'reserved'): ?><br><small class="muted">clôture <?= e(date_fr($it['end_at'])) ?></small><?php endif; ?></td>
            <td class="num"><?= money($amount) ?><?php if ($amount < $commit - 0.009 && $it['status'] !== 'failed'): ?><br><small style="color:var(--forest)">au lieu de <?= money($commit) ?></small><?php endif; ?></td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </section>

    <aside class="stack">
      <div class="panel summary">
        <h2>Paiement</h2>
        <div class="line"><span>Engagement maximum</span><span><?= money($o['amount_committed']) ?></span></div>
        <div class="line total"><span><?= $o['amount_final'] !== null ? 'Montant final' : 'Au prix actuel' ?></span><span><?= money($o['amount_final'] ?? $live) ?></span></div>
        <p class="small mt-4"><strong><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></strong>
          <?php if ($o['payment_mode'] === 'stripe' && $o['payment_status'] === 'pending'): ?><br><span class="muted">Carte enregistrée, débit à la clôture.</span><?php endif; ?></p>
        <?php if (in_array($o['payment_status'], ['action_required', 'failed'], true) && stripe_enabled()): ?>
          <a class="btn btn--go btn--block" href="/compte/paiement/<?= e($o['reference']) ?>">Régler maintenant</a>
        <?php endif; ?>
        <?php if ($o['invoice_number']): ?><a class="btn btn--ghost btn--block mt-4" href="/compte/commande/<?= e($o['reference']) ?>/facture" target="_blank">Facture <?= e($o['invoice_number']) ?></a><?php endif; ?>
      </div>

      <?php if (in_array($o['status'], ['ready', 'confirmed'], true)): ?>
      <div class="panel">
        <h2>Retrait</h2>
        <?php if ($o['window_label']): ?><p><strong><?= e($o['window_location']) ?></strong><br><?= e($o['window_address']) ?><br><?= e(time_range_fr($o['window_starts'], $o['window_ends'])) ?></p><?php endif; ?>
        <?php if ($o['status'] === 'ready'): ?><a class="btn btn--block" href="/retrait/<?= e($o['pickup_token']) ?>">Ouvrir mon Pass Retrait</a>
        <?php else: ?><p class="small muted mb-0">Votre Pass Retrait (QR code) arrivera par email dès que les bénévoles auront terminé l'ensachage.</p><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($cancellable): ?>
      <form method="post" action="/compte/commande/<?= e($o['reference']) ?>/annuler" data-confirm="Annuler cet engagement ? La quantité sera libérée pour la campagne.">
        <?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit">Annuler mon engagement</button>
        <p class="small muted mt-4">Possible jusqu'à la clôture de la campagne.</p>
      </form>
      <?php endif; ?>
    </aside>
  </div>
</div>
