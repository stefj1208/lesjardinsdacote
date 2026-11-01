<div class="wrap page">
  <div class="admin-head">
    <h1>Bonjour <?= e($u['first_name'] ?: 'jardinier·e') ?></h1>
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="/compte/profil">Mon profil</a>
      <form method="post" action="/deconnexion" class="inline-form"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Se déconnecter</button></form>
    </div>
  </div>

  <?php if ($shifts): ?>
  <section class="panel mb-6">
    <h2>Mes prochains créneaux bénévoles</h2>
    <?php foreach ($shifts as $s): ?><p class="mb-0"><strong><?= e($s['title']) ?></strong> — <?= e(time_range_fr($s['starts_at'], $s['ends_at'])) ?></p><?php endforeach; ?>
    <a class="btn btn--sm mt-4" href="/benevoles/planning">Voir le planning</a>
  </section>
  <?php endif; ?>

  <h2>Mes engagements</h2>
  <?php if (!$orders): ?>
    <div class="empty"><h3>Aucun engagement pour l'instant</h3><p>Rejoignez une campagne : votre commande fera pousser la plante.</p><a class="btn" href="/campagnes">Voir les campagnes</a></div>
  <?php endif; ?>
  <?php foreach ($orders as $o):
      $chip = match ($o['status']) { 'ready' => 'chip--ok', 'collected' => 'chip--dark', 'cancelled', 'failed' => 'chip--bad', default => '' };
  ?>
  <a class="order-card" href="/compte/commande/<?= e($o['reference']) ?>">
    <div>
      <div class="ref"><?= e($o['reference']) ?> <span class="chip <?= $chip ?>"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></div>
      <div class="small muted"><?= e(mb_strimwidth((string)$o['titles'], 0, 110, '…')) ?></div>
      <div class="small muted">Engagé le <?= e(date_fr($o['created_at'])) ?> · <?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></div>
    </div>
    <div style="text-align:right">
      <strong><?= money($o['amount_final'] ?? $o['live_total']) ?></strong>
      <?php if ($o['amount_final'] === null && $o['live_total'] < (float)$o['amount_committed'] - 0.009): ?><div class="small" style="color:var(--forest)">−<?= money((float)$o['amount_committed'] - $o['live_total']) ?> grâce aux paliers</div><?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
</div>
