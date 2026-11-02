<?php /** Prix d'un pack avec sa valeur en jardinerie. @var array $p  @var bool $note */ ?>
<div class="price-line">
  <span class="price-now"><?= eur($p['price']) ?></span>
  <?php if (!empty($p['save'])): ?>
    <span class="price-was">au lieu de <span class="strike"><?= eur($p['market']) ?></span> en jardinerie</span>
    <span class="price-save"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 8.5l3 3L13 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg><?= eur($p['save']) ?> d'économie</span>
  <?php else: ?>
    <span class="price-was">le pack</span>
  <?php endif; ?>
</div>
<?php if (!empty($note) && !empty($p['save'])): ?><p class="source-note"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p><?php endif; ?>
