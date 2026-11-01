<?php
/** Carte "bac en bois" d'une campagne. @var array $c (hydratée) */
$st = $c['state'];
$portion = (float)$c['ref_portion'];
$now = Pricing::packetPrice($st['price'], $portion);
$was = Pricing::packetPrice($st['public_price'], $portion);
$hoursLeft = (strtotime($c['end_at']) - time()) / 3600;
$months = array_filter(array_map('intval', explode(',', (string)$c['sowing_months'])));
?>
<a class="crate" href="/campagne/<?= e($c['slug']) ?>"
   data-camp="<?= (int)$c['id'] ?>" data-cat="<?= e($c['category']) ?>" data-expo="<?= e($c['exposure']) ?>" data-water="<?= e($c['water']) ?>"
   data-diff="<?= e($c['difficulty']) ?>" data-months="<?= e(implode(',', $months)) ?>" data-pct="<?= $st['validated'] ? 100 + $st['to_next_pct'] / 100 : $st['to_t1_pct'] ?>"
   data-end="<?= strtotime($c['end_at']) ?>" data-start="<?= strtotime($c['start_at']) ?>" data-drop="<?= $st['tier'] ?>"
   data-name="<?= e(mb_strtolower($c['title'] . ' ' . $c['latin_name'] . ' ' . $c['family'])) ?>">
  <div class="crate-top">
    <div class="crate-slate">
      <?php if ($st['discount_pct'] > 0): ?><span class="was strike"><?= money($was) ?></span><?php endif; ?>
      <div class="now" data-live="pricePortion"><?= money($now) ?></div>
      <div class="per">les <?= e(Units::qty($portion, $c['unit'])) ?></div>
    </div>
    <?php partial('partials/plant', ['state' => $st['plant_state'], 'growth' => $st['growth'], 'toT1' => $st['to_t1_pct'] / 100, 'size' => 'sm']) ?>
    <?php if ($st['validated']): ?><span class="chip chip--ok badge-float">Commande garantie</span>
    <?php elseif ($st['to_t1_pct'] >= 90): ?><span class="chip chip--hot badge-float">Presque validée</span>
    <?php elseif ($hoursLeft < 48 && $hoursLeft > 0): ?><span class="chip chip--hot badge-float">Fin imminente</span><?php endif; ?>
  </div>
  <div class="crate-body">
    <div>
      <h3><?= e($c['title']) ?></h3>
      <?php if ($c['latin_name']): ?><div class="latin"><?= e($c['latin_name']) ?></div><?php endif; ?>
    </div>
    <div>
      <div class="gauge gauge--light" style="--p:<?= $st['validated'] ? $st['to_next_pct'] : $st['to_t1_pct'] ?>"><span></span></div>
      <div class="gauge-legend">
        <span><?php if ($st['next_tier']): ?><?= $st['tier'] === 0 ? 'Validation' : 'Palier ' . $st['next_tier'] ?> : encore <?= $st['parts_missing'] ?> <?= plural($st['parts_missing'], 'part', 'parts') ?><?php else: ?>Prix plancher atteint<?php endif; ?></span>
        <span><?= $c['participants'] ?> <?= plural($c['participants'], 'voisin', 'voisins') ?></span>
      </div>
    </div>
    <div class="crate-foot">
      <span class="chips"><?php if ($c['is_organic']): ?><span class="chip chip--bio">Bio</span><?php endif; ?><?php if ($c['difficulty'] === 'easy'): ?><span class="chip">Facile</span><?php endif; ?></span>
      <span><?= strtotime($c['start_at']) > time() ? 'Ouvre le ' . date_fr($c['start_at']) : 'Reste ' . remaining_fr($c['end_at']) ?></span>
    </div>
  </div>
</a>
