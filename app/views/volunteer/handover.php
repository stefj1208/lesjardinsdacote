<?php
$give = array_filter($items, fn($it) => in_array($it['status'], ['validated', 'packed', 'collected'], true));
$notPacked = array_filter($give, fn($it) => $it['status'] === 'validated');
$mustCollect = in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true);
?>
<div class="wrap page" style="max-width:720px">
  <p class="crumbs"><a href="/benevoles/distribution">Distribution</a> / <?= e($o['reference']) ?></p>
  <div class="slate">
    <div class="row row--between">
      <div><div class="chalk" style="font-size:2.4rem"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></div><div style="color:var(--chalk-dim)"><?= e($o['reference']) ?> · code <?= e($o['pickup_code']) ?></div></div>
      <div class="center"><div class="chalk" style="font-size:3.4rem;color:var(--chalk-yellow);line-height:1"><?= e($o['bin_code']) ?></div><small style="color:var(--chalk-dim)">casier</small></div>
    </div>
  </div>

  <?php if ($o['status'] === 'collected'): ?>
    <div class="flash flash--success mt-6">Déjà remise le <?= e(date_fr($o['collected_at'], true)) ?>.</div>
  <?php elseif (!in_array($o['status'], ['ready', 'confirmed'], true)): ?>
    <div class="flash flash--error mt-6">Commande non remettable : <?= e(Orders::STATUS_LABELS[$o['status']]) ?>.</div>
  <?php endif; ?>
  <?php if ($notPacked && $o['status'] !== 'collected'): ?><div class="flash flash--error mt-6">Attention : <?= count($notPacked) ?> <?= plural(count($notPacked), 'ligne n\'est pas encore ensachée', 'lignes ne sont pas encore ensachées') ?>.</div><?php endif; ?>

  <h2 class="mt-6">À remettre</h2>
  <div class="handover">
    <?php foreach ($give as $it): ?>
      <div class="handover-item"><span class="n"><?= (int)$it['packets'] ?>×</span><div><strong><?= e(Units::qty($it['portion_qty'], $it['unit'])) ?></strong> — <?= e($it['title']) ?><?= $it['status'] === 'validated' ? ' <span class="chip chip--warn">pas ensaché</span>' : '' ?></div></div>
    <?php endforeach; ?>
    <?php if ($o['container_choice'] === 'own'): ?><div class="notice">Le client apporte ses propres contenants : remplir sur place.</div><?php endif; ?>
    <?php if ($o['notes']): ?><div class="notice">Note du client : « <?= e($o['notes']) ?> »</div><?php endif; ?>
  </div>

  <?php if (in_array($o['status'], ['ready', 'confirmed'], true)): ?>
  <form method="post" action="/benevoles/distribution/<?= e($o['pickup_token']) ?>" class="mt-6">
    <?= csrf_field() ?>
    <?php if ($mustCollect): ?>
      <div class="panel mb-6" style="border:3px solid var(--amber)">
        <p class="mb-0" style="font-size:1.2rem"><strong>À encaisser : <?= money($o['amount_final']) ?></strong></p>
        <label class="check mt-4" style="font-size:1.1rem"><input type="checkbox" name="paid_on_site" value="1" required> J'ai encaissé <?= money($o['amount_final']) ?> (espèces, chèque ou carte)</label>
      </div>
    <?php else: ?>
      <p class="chip chip--ok" style="font-size:1rem;padding:8px 14px">Payée en ligne</p>
    <?php endif; ?>
    <button class="big-btn" type="submit">Valider la remise</button>
  </form>
  <?php endif; ?>
</div>
