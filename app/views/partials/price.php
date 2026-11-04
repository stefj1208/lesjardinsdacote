<?php /** Prix d'un pack : produits au prix coûtant + frais par commande, face aux prix publics. @var array $p  @var bool $note */ ?>
<div class="price-line">
  <span class="price-now"><?= eur($p['price']) ?></span>
  <span class="price-fee">+ <?= eur(Asso::fee()) ?> de frais par commande<?php if (Asso::fundShare() > 0): ?>, dont <?= eur(Asso::fundShare()) ?> pour planter des arbres<?php endif; ?></span>
  <?php if (!empty($p['save'])): ?>
    <span class="price-was">Les mêmes variétés à l'unité en magasin : <span class="strike"><?= eur($p['market']) ?></span></span>
    <span class="price-save"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 8.5l3 3L13 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg><?= eur($p['save']) ?> d'économie, frais compris</span>
  <?php endif; ?>
</div>
<?php if (!empty($note) && !empty($p['save'])): ?><p class="source-note"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p><?php endif; ?>
