<?php
/** Ardoise des paliers (US-C03). @var array $c */
$st = $c['state'];
$portion = (float)$c['ref_portion'];
$unitQty = Units::qty($portion, $c['unit']);
?>
<div class="price-board">
  <?php if ($st['public_price'] > 0 && $st['discount_pct'] > 0): ?>
    <div><span class="chalk strike" style="font-size:1.6rem">Prix public <?= money(Pricing::packetPrice($st['public_price'], $portion)) ?></span></div>
  <?php endif; ?>
  <div class="chalk now" data-live="pricePortion"><?= money(Pricing::packetPrice($st['price'], $portion)) ?></div>
  <div class="unit">les <?= e($unitQty) ?> · soit <span data-live="priceRef"><?= e(Units::refPrice($st['price'], $c['unit'])) ?></span>
    <?= $st['tier'] === 0 ? '— prix garanti dès la validation' : '' ?></div>
</div>
<ol class="tiers" data-tiers>
  <?php foreach ($c['tiers'] as $i => $t):
      $lvl = (int)$t['tier_level'];
      $cls = $lvl <= $st['tier'] ? ' is-reached' : '';
      if ($lvl === $st['tier']) $cls .= ' is-active';
      $disc = $st['public_price'] > 0 ? (int)round((1 - $t['unit_price_ttc'] / $st['public_price']) * 100) : 0;
  ?>
  <li class="tier<?= $cls ?>" data-tier="<?= $lvl ?>">
    <span class="t-name">Palier <?= $lvl ?></span>
    <span class="t-cond"><?= e(Units::qty($t['min_qty'], $c['unit'])) ?> réservés<?= $t['label'] ? ' · ' . e($t['label']) : '' ?><?= $lvl === 1 ? ' — seuil de validation' : '' ?></span>
    <span class="t-price"><?= money(Pricing::packetPrice((float)$t['unit_price_ttc'], $portion)) ?><?php if ($disc > 0): ?> <small style="font-size:1rem;color:var(--green)">−<?= $disc ?>%</small><?php endif; ?></span>
  </li>
  <?php endforeach; ?>
</ol>
<div class="tier-note" data-live="note">
  <svg viewBox="0 0 40 24" aria-hidden="true"><path d="M2 14 C10 4 22 4 32 12 M26 6 L33 12 L25 16" stroke="#FDE047" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <span data-live="noteText"><?php if ($st['next_tier']): ?>
    Plus que <?= $st['parts_missing'] ?> <?= plural($st['parts_missing'], 'part', 'parts') ?> de <?= e($unitQty) ?> pour <?= $st['tier'] === 0 ? 'valider la commande' : 'passer à ' . money(Pricing::packetPrice((float)$st['next_price'], $portion)) ?> !
  <?php else: ?>Récolte abondante : prix plancher débloqué !<?php endif; ?></span>
</div>
