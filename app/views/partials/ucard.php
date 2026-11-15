<?php $ex = $u['example']; ?>
<a class="ucard" href="/univers/<?= e($u['slug']) ?>" data-tilt data-reveal style="--i:<?= (int)($i ?? 0) % 3 ?>">
  <span class="ucard-photo"><?= ph($u['photo'], $u['label'], '', 900) ?></span>
  <span class="ucard-body">
    <span class="row row--between"><span class="chip<?= ($u['live'] ?? true) ? ' chip--ok' : '' ?>"><?= ($u['live'] ?? true) ? e($u['season']) : 'À venir' ?></span><?php if ($ex && $ex['save_pct']): ?><span class="save-pill">−<?= (int)$ex['save_pct'] ?> %</span><?php endif; ?></span>
    <span class="ucard-title"><?= e($u['label']) ?></span>
    <span class="ucard-intro"><?= e($u['intro']) ?></span>
    <span class="ucard-ex"><?php foreach ($u['examples'] as $x): ?><span><?= e($x) ?></span><?php endforeach; ?></span>
    <?php if ($ex && $ex['save']): ?>
      <span class="ucard-price"><b><?= eur($ex['price']) ?></b> + <?= eur(Asso::fee()) ?> de frais · <s><?= eur($ex['market']) ?></s> au prix public</span>
    <?php else: ?>
      <span class="ucard-price"><?= ($u['live'] ?? true) ? 'Catalogue en préparation' : 'Aux saisons suivantes' ?></span>
    <?php endif; ?>
    <span class="ucard-go">Découvrir <span class="arr" aria-hidden="true">→</span></span>
  </span>
</a>
