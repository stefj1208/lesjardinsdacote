<div class="wrap page" style="max-width:820px">
  <p class="crumbs"><a href="/benevoles">Espace bénévoles</a> / Distribution</p>
  <div class="admin-head"><h1>Distribution</h1><span class="chip chip--ok"><?= $collectedToday ?> <?= plural($collectedToday, 'remise', 'remises') ?> aujourd'hui</span></div>

  <div class="scan-box">
    <div id="qr-reader" hidden></div>
    <button class="big-btn" type="button" id="scan-start">Scanner un Pass Retrait</button>
    <p class="small center mt-4 mb-0" id="scan-status" style="color:#B9C7BE">Autorisez l'accès à la caméra. Le QR code s'ouvre aussi avec l'appareil photo du téléphone.</p>
  </div>

  <form class="panel mt-6" method="get" action="/benevoles/distribution">
    <div class="field mb-0"><label for="q">Ou chercher par code (6 caractères), nom ou référence</label>
      <div class="row"><input id="q" name="q" value="<?= e($q) ?>" autocomplete="off" autocapitalize="characters" class="grow" style="font-size:1.2rem;min-width:200px"><button class="btn btn--lg" type="submit">Chercher</button></div></div>
  </form>

  <?php if ($q !== ''): ?>
    <section class="mt-6">
      <h2>Résultats</h2>
      <?php if (!$results): ?><p class="muted">Aucune commande trouvée pour « <?= e($q) ?> ».</p><?php endif; ?>
      <?php foreach ($results as $o): ?>
        <a class="order-card" style="grid-template-columns:1fr auto" href="/benevoles/distribution/<?= e($o['pickup_token']) ?>"><div><div class="ref"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></div><div class="small muted"><?= e($o['reference']) ?> · code <?= e($o['pickup_code']) ?></div></div><span class="chip"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="mt-6">
    <h2>Commandes prêtes (<?= count($today) ?>)</h2>
    <?php if (!$today): ?><p class="muted">Aucune commande en attente de retrait.</p><?php endif; ?>
    <?php foreach ($today as $o): ?>
      <a class="order-card" style="grid-template-columns:1fr auto" href="/benevoles/distribution/<?= e($o['pickup_token']) ?>">
        <div class="row"><span class="bin" style="display:grid;place-items:center;width:44px;height:44px;border-radius:10px;background:var(--ink);color:var(--sun);font:800 1.4rem var(--f-display)"><?= e($o['bin_code']) ?></span>
          <div><div class="ref"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></div><div class="small muted"><?= e($o['reference']) ?><?= $o['payment_status'] === 'to_collect' ? ' · à encaisser ' . money($o['amount']) : '' ?></div></div></div>
        <span class="chip <?= $o['payment_status'] === 'paid' ? 'chip--ok' : 'chip--warn' ?>"><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></span>
      </a>
    <?php endforeach; ?>
  </section>
</div>
