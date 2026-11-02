<?php $mustCollect = in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true); ?>
<div class="wrap page" style="max-width:720px">
  <p class="crumbs"><a href="/benevoles/distribution">Distribution</a> / <?= e($o['reference']) ?></p>
  <div class="pass-card" style="text-align:left">
    <div class="row row--between">
      <div><div style="font:800 2rem/1.1 var(--f-display)"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></div><div style="color:#B9C7BE"><?= e($o['reference']) ?> · code <?= e($o['pickup_code']) ?> · <?= e($o['group_name']) ?></div></div>
      <div class="center"><div style="font:800 3rem/1 var(--f-display);color:var(--sun)"><?= e($o['bin_code']) ?></div><small style="color:#B9C7BE">casier</small></div>
    </div>
  </div>
  <?php if ($o['status'] === 'collected'): ?><div class="flash mt-3">Déjà remis le <?= e(date_fr($o['collected_at'], true)) ?>.</div>
  <?php elseif (!in_array($o['status'], ['ready', 'confirmed'], true)): ?><div class="flash flash--error mt-3">Commande non remettable : <?= e(Orders::STATUS_LABELS[$o['status']]) ?>.</div>
  <?php elseif ($o['status'] === 'confirmed'): ?><div class="flash flash--info mt-3">Ce pack n'a pas encore été marqué « prêt ». Vérifiez qu'il est bien préparé.</div><?php endif; ?>
  <h2 class="mt-4"><?= (int)$o['quantity'] ?> × <?= e($o['pack_name']) ?></h2>
  <div class="handover">
    <?php foreach ($items as $it): ?><div class="handover-item"><?= ph($it['photo'], $it['name'], '', 200) ?><div><strong><?= e($it['name']) ?></strong> <?= e($it['variety']) ?></div><strong><?= e(Cat::qty((float)$it['quantity'] * (int)$o['quantity'], $it['unit'])) ?></strong></div><?php endforeach; ?>
    <?php if ($o['notes']): ?><div class="notice">Note du membre : « <?= e($o['notes']) ?> »</div><?php endif; ?>
  </div>
  <?php if (in_array($o['status'], ['ready', 'confirmed'], true)): ?>
  <form method="post" action="/benevoles/distribution/<?= e($o['pickup_token']) ?>" class="mt-4"><?= csrf_field() ?>
    <?php if ($mustCollect): ?>
      <div class="callout mb-2" style="margin-bottom:1rem"><p class="mb-0" style="font-size:1.2rem"><strong>À encaisser : <?= money($o['amount']) ?></strong></p>
        <label class="check mt-2"><input type="checkbox" name="paid_on_site" value="1" required> J'ai encaissé <?= money($o['amount']) ?></label></div>
    <?php else: ?><p><span class="chip chip--ok">Payé en ligne</span></p><?php endif; ?>
    <button class="big-btn" type="submit">Valider la remise</button>
  </form>
  <?php endif; ?>
</div>
