<?php
/** Détail des montants d'une commande. @var array $o */
$goods = $o['goods_amount'] !== null ? (float)$o['goods_amount'] : (float)$o['amount'];
?>
<div class="line"><span><?= (int)$o['quantity'] ?> × <?= money($o['unit_price']) ?> <small class="muted">(prix coûtant)</small></span><span><?= money($goods) ?></span></div>
<?php if ((float)$o['fee_amount'] > 0): ?><div class="line"><span>Frais de fonctionnement et logistique<?php if ((float)$o['fund_amount'] > 0): ?> <small class="muted">dont <?= money($o['fund_amount']) ?> pour les plantations</small><?php endif; ?></span><span><?= money($o['fee_amount']) ?></span></div><?php endif; ?>
<?php if ((float)$o['membership_amount'] > 0): ?><div class="line"><span>Adhésion annuelle</span><span><?= money($o['membership_amount']) ?></span></div><?php endif; ?>
<div class="line total"><span>Total</span><span><?= money($o['amount']) ?></span></div>
