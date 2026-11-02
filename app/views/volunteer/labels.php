<?php $qr = []; $pages = array_chunk($labels, 24); ?>
<?php if (!$labels): ?><p class="center page">Aucune étiquette à imprimer : aucun pack à préparer.</p><?php endif; ?>
<?php foreach ($pages as $page): ?>
<div class="labels" style="page-break-after:always">
  <?php foreach ($page as $l): $it = $l['it']; $qr[$l['token']] ??= QR::svg(abs_url('/r/' . $l['token']), 54); ?>
  <div class="lbl">
    <div>
      <div class="l-name"><?= e($it['name']) ?></div>
      <div class="l-var"><?= e($it['variety']) ?></div>
      <div class="l-latin"><?= e($it['latin_name']) ?></div>
      <div class="l-qty"><?= e(Cat::qty($it['quantity'], $it['unit'])) ?></div>
      <div class="l-meta"><?= $lot !== '' ? 'Lot ' . e($lot) . ' · ' : '' ?>cond. <?= date('Y') ?><?= $it['unit'] === 'graines' || $it['unit'] === 'g' ? '<br>Semences destinées aux jardiniers amateurs' : '' ?></div>
    </div>
    <div><div class="l-qr"><?= $qr[$l['token']] ?></div><div class="l-buyer"><?= e($l['buyer']) ?></div></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
<p class="center small muted no-print" style="padding:12px">Planches A4 de 24 étiquettes 70 × 37 mm. Imprimer à 100 %, sans marges.</p>
