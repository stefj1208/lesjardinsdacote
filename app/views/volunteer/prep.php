<?php $w = $s['winner']; $todo = count(array_filter($orders, fn($o) => $o['status'] === 'confirmed')); ?>
<div class="wrap page" style="max-width:920px">
  <p class="crumbs"><a href="/benevoles">Espace bénévoles</a> / Préparation</p>
  <div class="admin-head"><h1><?= e($s['group_name']) ?></h1><span class="chip chip--dark"><?= e($w['name'] ?? '') ?></span></div>

  <h2>1. À commander et à répartir</h2>
  <p class="muted">Quantités totales pour les <?= count($orders) ?> commandes des lots complets.</p>
  <?php foreach ($needs as $n): ?>
    <div class="need-row"><span class="n"><?= e(Cat::qty($n['total'], $n['unit'])) ?></span><span><strong><?= e($n['name']) ?></strong> <?= e($n['variety']) ?><br><small style="color:#B9C7BE"><?= e($n['latin_name']) ?></small></span></div>
  <?php endforeach; ?>
  <?php if (!$needs): ?><p class="muted">Aucun lot complet pour l'instant.</p><?php endif; ?>

  <h2 class="mt-6">2. Composition d'un pack</h2>
  <div class="panel"><ul class="items"><?php foreach ($w['items'] ?? [] as $it): ?><li class="item"><?= ph($it['photo'], $it['name'], '', 200) ?><span><b><?= e($it['name']) ?></b><small><?= e($it['variety']) ?></small></span><span class="qty"><?= e(Cat::qty($it['quantity'], $it['unit'])) ?></span></li><?php endforeach; ?></ul></div>
  <form class="row mt-2" method="get" action="/benevoles/preparation/<?= (int)$s['id'] ?>/etiquettes" target="_blank">
    <label class="label" for="lot">N° de lot fournisseur (pour les étiquettes)</label>
    <input id="lot" name="lot" style="max-width:220px" placeholder="Ex. AG-204">
    <button class="btn btn--ghost" type="submit">Imprimer les étiquettes</button>
  </form>

  <h2 class="mt-6">3. Packs par membre <span class="muted" style="font-size:1rem">(<?= $todo ?> à préparer)</span></h2>
  <p class="muted small">Le casier correspond à l'initiale du nom. Marquez « prêt » : le membre reçoit aussitôt son Pass Retrait.</p>
  <?php if (!$orders): ?><div class="empty">Aucun pack à préparer.</div><?php endif; ?>
  <?php foreach ($orders as $o): $done = $o['status'] !== 'confirmed'; ?>
    <div class="prep-item<?= $done ? ' is-done' : '' ?>">
      <span class="bin"><?= e($o['bin_code']) ?></span>
      <div><div class="who"><?= e($o['first_name'] . ' ' . $o['last_name']) ?> <span class="small muted"><?= e($o['reference']) ?> · lot <?= (int)$o['batch_no'] ?></span></div>
        <div><strong><?= (int)$o['quantity'] ?> pack<?= $o['quantity'] > 1 ? 's' : '' ?></strong><?= $o['notes'] ? ' · « ' . e($o['notes']) . ' »' : '' ?></div></div>
      <?php if ($o['status'] === 'collected'): ?><span class="chip chip--dark">Remis</span>
      <?php else: ?>
        <form method="post" action="/benevoles/preparer/<?= (int)$o['id'] ?>"><?= csrf_field() ?><input type="hidden" name="ready" value="<?= $done ? '0' : '1' ?>">
          <button class="toggle-prep" type="submit"><?= $done ? '✓ Prêt' : 'Marquer prêt' ?></button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
