<?php
$months = array_filter(array_map('intval', explode(',', (string)$c['sowing_months'])));
$sow = $months ? 'Semis : ' . FR_MONTHS_SHORT[min($months)] . ' → ' . FR_MONTHS_SHORT[max($months)] : '';
$pages = array_chunk($labels, 24);
?>
<?php if (!$c['supplier_lot']): ?><div class="flash flash--error no-print" style="max-width:210mm;margin:12px auto">Le numéro de lot d'origine n'est pas encore renseigné : il est obligatoire sur les sachets de semences.</div><?php endif; ?>
<?php if (!$labels): ?><p class="center page">Aucune étiquette à imprimer.</p><?php endif; ?>
<?php foreach ($pages as $page): ?>
<div class="labels<?= $style === 'kraft' ? ' labels--kraft' : '' ?>" style="page-break-after:always">
  <?php foreach ($page as $l):
      $weight = Units::qty($l['portion'], $c['unit']);
      if ($c['unit'] === 'g' && $c['pmg']) $weight .= ' (≈ ' . num(Units::seedsFromGrams($l['portion'], (float)$c['pmg']), 0) . ' gr.)';
  ?>
  <div class="lbl">
    <div>
      <div class="l-name"><?= e($c['product_name']) ?></div>
      <div class="l-var"><?= e($c['variety']) ?></div>
      <div class="l-latin"><?= e($c['latin_name']) ?></div>
      <div class="l-weight">Poids net : <?= e($weight) ?></div>
      <div class="l-meta">Lot <?= e($c['supplier_lot'] ?: '______') ?> · cond. <?= (int)($c['packed_year'] ?: date('Y')) ?><?= $c['is_organic'] ? ' · AB' : '' ?><br><?= e($sow) ?><?= $c['sowing_depth'] ? ' · prof. ' . e($c['sowing_depth']) : '' ?><br>Semences destinées aux jardiniers amateurs</div>
    </div>
    <div>
      <div class="l-qr"><?= $qr ?></div>
      <?php if ($l['buyer']): ?><div class="l-buyer"><?= e($l['buyer']) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
<p class="center small muted no-print" style="padding:12px">Planches A4 de 24 étiquettes 70 × 37 mm (format courant des planches autocollantes). Impression à 100 %, sans marges.</p>
