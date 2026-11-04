<?php
/** Où vont les euros d'une commande d'un pack. @var float $price */
$price = $price ?? (float)setting('default_pack_price', '30');
$fee = Asso::fee(); $fund = Asso::fundShare(); $ops = max(0, $fee - $fund); $tot = $price + $fee;
$pc = fn(float $x) => $tot > 0 ? round($x / $tot * 100, 1) : 0;
?>
<div class="msplit" data-reveal>
  <div class="msplit-bar" role="img" aria-label="Sur <?= eur($tot) ?> : <?= eur($price) ?> pour les producteurs, <?= eur($ops) ?> pour la logistique, <?= eur($fund) ?> pour planter des arbres">
    <span class="ms ms--prod" style="flex-grow:<?= $price ?>"><b><?= eur($price) ?></b></span>
    <span class="ms ms--ops" style="flex-grow:<?= $ops ?>"><b><?= eur($ops) ?></b></span>
    <?php if ($fund > 0): ?><span class="ms ms--tree" style="flex-grow:<?= $fund ?>"><b><?= eur($fund) ?></b></span><?php endif; ?>
  </div>
  <ul class="msplit-legend">
    <li><i class="ms--prod"></i><span><b>Producteurs</b> — le prix du pack, payé tel quel, sans marge</span></li>
    <li><i class="ms--ops"></i><span><b>Logistique et fonctionnement</b> — transport, sachets, étiquettes, assurance, outil en ligne</span></li>
    <?php if ($fund > 0): ?><li><i class="ms--tree"></i><span><b>Plantations</b> — versé au fonds qui finance des arbres, en France et ailleurs</span></li><?php endif; ?>
  </ul>
  <p class="small muted mb-0">Exemple pour une commande d'un pack à <?= eur($price) ?>. Avec deux packs, les frais restent de <?= eur($fee) ?>.</p>
</div>
