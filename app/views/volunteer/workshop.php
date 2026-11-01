<?php
$totalPackets = array_sum(array_column($sheet, 'packets'));
$packedPackets = array_sum(array_column($sheet, 'packed'));
$totalQty = array_sum(array_map(fn($r) => $r['packets'] * $r['portion_qty'], $sheet));
?>
<div class="wrap page" style="max-width:900px">
  <p class="crumbs"><a href="/benevoles/atelier">Atelier</a> / <?= e($c['title']) ?></p>
  <div class="admin-head">
    <h1><?= e($c['title']) ?></h1>
    <span class="chip chip--dark"><?= e(Units::STATUSES[$c['status']]) ?></span>
  </div>

  <section class="panel">
    <h2>1. Réception du fournisseur</h2>
    <?php if ($po && $po['opt']['lines']): ?>
      <p>À recevoir : <?php foreach ($po['opt']['lines'] as $i => $l): ?><?= $i ? ' + ' : '' ?><strong><?= $l['count'] ?> × <?= e($l['label']) ?></strong><?php endforeach; ?>
        <span class="muted">(<?= e(Units::qty($po['opt']['qty'], $c['unit'])) ?> pour <?= e(Units::qty($totalQty, $c['unit'])) ?> à ensacher, perte de pesée comprise)</span></p>
    <?php endif; ?>
    <form method="post" action="/benevoles/atelier/<?= (int)$c['id'] ?>/lot" class="grid-3" style="align-items:end">
      <?= csrf_field() ?>
      <div class="field"><label for="lot">N° de lot d'origine</label><input id="lot" name="supplier_lot" required value="<?= e($c['supplier_lot']) ?>" placeholder="Ex. AG-204" style="font-size:1.2rem;font-weight:700"></div>
      <div class="field"><label for="year">Année de conditionnement</label><input id="year" name="packed_year" type="number" value="<?= (int)($c['packed_year'] ?: date('Y')) ?>"></div>
      <div class="field"><label for="note">État à réception</label><input id="note" name="reception_note" placeholder="Conforme, sac abîmé…"></div>
      <button class="big-btn big-btn--dark" type="submit" style="grid-column:1/-1"><?= $c['supplier_lot'] ? 'Mettre à jour la réception' : 'Valider la réception' ?></button>
    </form>
  </section>

  <section class="mt-6">
    <h2>2. Feuille d'ensachage<?= $c['supplier_lot'] ? ' — lot ' . e($c['supplier_lot']) : '' ?></h2>
    <div class="pack-sheet">
      <?php foreach ($sheet as $r):
          $seeds = $c['unit'] === 'g' ? Units::seedsFromGrams((float)$r['portion_qty'], $c['pmg'] ? (float)$c['pmg'] : null) : null; ?>
        <div class="pack-row">
          <div class="n"><?= (int)$r['packets'] ?></div>
          <div><div class="what">sachets de <?= e(Units::qty($r['portion_qty'], $c['unit'])) ?></div>
            <div class="done"><?= (int)$r['packed'] ?> faits<?= $seeds ? ' · ≈ ' . num($seeds, 0) . ' graines par sachet' : '' ?></div></div>
          <div class="chalk" style="font-size:1.6rem;color:<?= $r['packed'] >= $r['packets'] ? 'var(--green)' : 'var(--chalk-dim)' ?>"><?= $r['packed'] >= $r['packets'] ? '✓' : '' ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="grid-2 mt-4">
      <a class="big-btn big-btn--outline" href="/benevoles/atelier/<?= (int)$c['id'] ?>/etiquettes" target="_blank">Étiquettes par acheteur (<?= $totalPackets ?>)</a>
      <a class="big-btn big-btn--outline" href="/benevoles/atelier/<?= (int)$c['id'] ?>/etiquettes?mode=stock&n=24" target="_blank">Planche d'étiquettes vierges</a>
    </div>
  </section>

  <section class="mt-6">
    <h2>3. Ensachage par commande <span class="muted" style="font-size:1rem">(<span data-packed-count><?= $packedPackets ?></span>/<?= $totalPackets ?> sachets)</span></h2>
    <p class="muted small">Le casier correspond à l'initiale du nom. Rangez chaque commande dans son casier.</p>
    <?php if (!$items): ?><div class="empty">Aucune ligne à préparer.</div><?php endif; ?>
    <?php foreach ($items as $it): $packed = in_array($it['status'], ['packed', 'collected'], true); ?>
      <div class="pack-item<?= $packed ? ' is-packed' : '' ?>">
        <div class="bin" title="Casier"><?= e($it['bin_code']) ?></div>
        <div>
          <div class="who"><?= e($it['first_name'] . ' ' . $it['last_name']) ?> <span class="small muted"><?= e($it['reference']) ?></span></div>
          <div class="qty"><strong><?= (int)$it['packets'] ?> × <?= e(Units::qty($it['portion_qty'], $c['unit'])) ?></strong><?= $it['container_choice'] === 'own' ? ' · <span class="chip chip--warn">apporte ses contenants</span>' : '' ?></div>
          <?php if ($it['notes']): ?><div class="small muted">« <?= e($it['notes']) ?> »</div><?php endif; ?>
        </div>
        <?php if ($it['status'] === 'collected'): ?><span class="chip chip--dark">Retiré</span>
        <?php else: ?>
        <form method="post" action="/benevoles/ensacher/<?= (int)$it['id'] ?>" data-pack-toggle>
          <?= csrf_field() ?><input type="hidden" name="packed" value="<?= $packed ? '0' : '1' ?>">
          <button class="toggle-pack" type="submit"><?= $packed ? '✓ Ensaché' : 'Marquer ensaché' ?></button>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($c['status'] !== 'ready_pickup' && $c['status'] !== 'closed'): ?>
  <form class="mt-6" method="post" action="/benevoles/atelier/<?= (int)$c['id'] ?>/termine" data-confirm="Tout est ensaché et rangé ? Les clients vont recevoir leur Pass Retrait.">
    <?= csrf_field() ?><button class="big-btn" type="submit">Tout est ensaché : prévenir les clients</button>
  </form>
  <?php endif; ?>
</div>
