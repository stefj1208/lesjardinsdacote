<div class="wrap page">
  <div class="admin-head">
    <h1>Bonjour <?= e($u['first_name'] ?: 'et bienvenue') ?></h1>
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="/compte/profil">Mon profil</a>
      <form method="post" action="/deconnexion" class="inline-form"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Se déconnecter</button></form>
    </div>
  </div>

  <?php $validCart = array_filter($cart, fn($c) => $c['valid']); if ($validCart): ?>
    <div class="callout callout--tomato mb-4" style="margin-bottom:1.5rem">
      <div class="row row--between"><div><strong><?= count($validCart) ?> <?= plural(count($validCart), 'pack vous attend', 'packs vous attendent') ?> dans votre panier.</strong><br><span class="small">Confirmez votre achat : rien n'est débité avant que le lot soit complet.</span></div><a class="btn btn--accent" href="/panier">Voir mon panier</a></div>
    </div>
  <?php endif; ?>

  <h2>Mes groupes</h2>
  <?php if (!$groups): ?>
    <div class="empty"><h3>Vous n'avez pas encore rejoint de groupe</h3><p>Trouvez celui de votre commune pour voter et acheter les packs.</p><a class="btn" href="/groupes">Trouver mon groupe</a></div>
  <?php else: ?>
    <div class="groups-grid"><?php foreach ($groups as $g) partial('partials/group_card', ['g' => $g]); ?></div>
  <?php endif; ?>

  <?php if ($shifts): ?>
  <section class="panel mt-4">
    <h2>Mes prochains créneaux bénévoles</h2>
    <?php foreach ($shifts as $s): ?><p class="mb-0"><strong><?= e($s['title']) ?></strong> — <?= e(time_range_fr($s['starts_at'], $s['ends_at'])) ?></p><?php endforeach; ?>
    <a class="btn btn--sm mt-2" href="/benevoles/planning">Voir le planning</a>
  </section>
  <?php endif; ?>

  <h2 class="mt-6">Mes achats</h2>
  <?php if (!$orders): ?><p class="muted">Aucun achat pour l'instant.</p><?php endif; ?>
  <?php foreach ($orders as $o):
      $chip = match ($o['status']) { 'ready' => 'chip--ok', 'collected' => 'chip--dark', 'cancelled' => 'chip--bad', 'pending' => 'chip--warn', default => '' }; ?>
    <a class="order-card" href="/compte/commande/<?= e($o['reference']) ?>">
      <?= ph($o['pack_photo'], $o['pack_name'], '', 300) ?>
      <div>
        <div class="ref"><?= (int)$o['quantity'] ?> × <?= e($o['pack_name']) ?> <span class="chip <?= $chip ?>"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></div>
        <div class="small muted"><?= e($o['group_name']) ?> · <?= e($o['reference']) ?> · lot n° <?= (int)$o['batch_no'] ?></div>
        <div class="small muted"><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></div>
      </div>
      <strong><?= money($o['amount']) ?></strong>
    </a>
  <?php endforeach; ?>
</div>
