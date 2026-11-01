<div class="admin-head">
  <h1>Bon de commande fournisseur</h1>
  <div class="row">
    <a class="btn btn--sm" href="?<?= e(http_build_query(['panier' => $batchId ?: null, 'c' => $batchId ? null : $ids, 'format' => 'csv'])) ?>">Exporter (CSV / Excel)</a>
    <button class="btn btn--ghost btn--sm" type="button" onclick="window.print()">Imprimer / PDF</button>
  </div>
</div>
<form method="get" class="panel mb-6 no-print">
  <div class="row">
    <label class="label" for="panier">Panier fournisseur</label>
    <select id="panier" name="panier" data-autosubmit style="max-width:420px"><option value="">Campagnes validées en attente de commande</option>
      <?php foreach ($batches as $b): ?><option value="<?= (int)$b['id'] ?>"<?= (int)$b['id'] === $batchId ? ' selected' : '' ?>><?= e($b['name']) ?> — <?= e($b['supplier']) ?></option><?php endforeach; ?></select>
  </div>
</form>
<?php if ($batch): ?>
  <div class="panel mb-6">
    <strong>Franco de port :</strong> <?= money($batch['total_ht']) ?> HT réservés sur <?= money($batch['franco_ht']) ?> HT (<?= $batch['pct'] ?> %)
    <div class="gauge gauge--light mt-4" style="--p:<?= $batch['pct'] ?>"><span></span></div>
  </div>
<?php endif; ?>
<div class="panel table-wrap">
  <table class="table">
    <thead><tr><th>Produit</th><th>Réf. fournisseur</th><th class="num">Besoin net</th><th>Conditionnements à commander</th><th class="num">Total HT</th></tr></thead>
    <tbody>
    <?php foreach ($po['lines'] as $l): $c = $l['campaign']; ?>
      <tr>
        <td><strong><?= e($c['product_name'] . ' ' . $c['variety']) ?></strong><br><small class="muted"><?= e($c['supplier_name']) ?> · <?= e(Units::STATUSES[$c['status']]) ?></small></td>
        <td><?= e($c['sku_supplier'] ?: '—') ?></td>
        <td class="num"><?= e(Units::qty($l['need'], $c['unit'])) ?><br><small class="muted">brut <?= e(Units::qty($l['opt']['need_raw'], $c['unit'])) ?></small></td>
        <td><?php foreach ($l['opt']['lines'] as $p): ?><div><strong><?= $p['count'] ?> ×</strong> <?= e($p['label']) ?> <span class="muted">à <?= money($p['unit_cost_ht']) ?></span></div><?php endforeach; ?>
          <?php if (!$l['opt']['lines']): ?><span class="muted">Rien à commander</span><?php endif; ?></td>
        <td class="num"><?= money($l['opt']['cost_ht']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$po['lines']): ?><tr><td colspan="5" class="muted">Aucune campagne validée à commander.</td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><td colspan="4"><strong>Total</strong></td><td class="num"><strong><?= money($po['total_ht']) ?> HT</strong></td></tr></tfoot>
  </table>
</div>
<?php if ($batchId && $batch && $batch['batch']['status'] !== 'ordered'): ?>
<form method="post" action="/admin/paniers-fournisseur" class="mt-6 no-print" data-confirm="Marquer la commande comme passée chez le fournisseur ?">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $batchId ?>"><input type="hidden" name="ordered" value="1">
  <button class="btn btn--wood" type="submit">Commande passée chez le fournisseur</button>
</form>
<?php endif; ?>
