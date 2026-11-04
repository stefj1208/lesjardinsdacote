<?php
/**
 * Ventilation limpide d'un panier : où va chaque euro.
 * @var array $b  Asso::breakdown()
 */
$feeLabel = $b['orders'] > 1 ? $b['orders'] . ' × ' . money($b['fee_unit']) : money($b['fee_unit']);
?>
<div class="bd">
  <div class="bd-line">
    <span><b>Vos plantes, au prix coûtant</b><small>Payé tel quel au producteur, sans marge</small></span>
    <span><?= money($b['goods']) ?></span>
  </div>
  <div class="bd-line">
    <span><b>Frais de fonctionnement et logistique</b><small><?= $b['orders'] > 1 ? $feeLabel . ' (une commande par groupe)' : 'Une seule fois par commande, quel que soit le nombre de packs' ?></small></span>
    <span><?= money($b['fee']) ?></span>
  </div>
  <?php if ($b['fund'] > 0): ?>
  <div class="bd-line bd-line--tree">
    <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22v-7M12 15c-4 0-6-2.5-6-5.5S8.5 4 12 2c3.5 2 6 4.5 6 7.5S16 15 12 15z"/></svg><span>dont <b><?= money($b['fund']) ?></b> pour planter des arbres</span></span>
  </div>
  <?php endif; ?>
  <?php if ($b['needs_membership']): ?>
  <div class="bd-line">
    <span><b>Adhésion <?= Asso::year() ?> à l'association</b><small><?= $b['membership'] > 0 ? 'Une fois par an' : 'Gratuite, obligatoire pour commander en groupe' ?></small></span>
    <span><?= $b['membership'] > 0 ? money($b['membership']) : 'offerte' ?></span>
  </div>
  <?php endif; ?>
  <div class="bd-line bd-total"><span>Total</span><span><?= money($b['total']) ?></span></div>
  <?php if ($b['saved'] > 0): ?><div class="bd-save">Vous économisez <b><?= money($b['saved']) ?></b> par rapport aux prix publics constatés (<?= money($b['market']) ?>), frais compris.</div><?php endif; ?>
  <div class="bd-now"><span>Débité aujourd'hui</span><b><?= money(0) ?></b></div>
</div>
