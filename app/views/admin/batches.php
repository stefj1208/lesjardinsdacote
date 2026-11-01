<?php $f = $edit ?? []; ?>
<div class="admin-head"><h1>Paniers fournisseur (franco de port)</h1></div>
<p class="muted">Regroupez plusieurs campagnes d'un même fournisseur pour atteindre ensemble le franco de port (mode 3). Les campagnes en mode « franco » sont validées dès que le total réservé atteint le montant.</p>
<div class="layout-aside">
  <div class="panel table-wrap"><table class="table">
    <thead><tr><th>Panier</th><th>Progression</th><th>Statut</th><th></th></tr></thead>
    <tbody><?php foreach ($batches as $b): $t = $b['totals']; ?>
      <tr><td><strong><?= e($b['name']) ?></strong><br><small class="muted"><?= e($b['supplier']) ?> · <?= $t['campaigns'] ?> campagnes</small></td>
        <td style="min-width:180px"><div class="gauge gauge--light" style="--p:<?= $t['pct'] ?>;height:8px"><span></span></div><small><?= money($t['total_ht']) ?> / <?= money($t['franco_ht']) ?> HT</small></td>
        <td><span class="chip"><?= e(['open' => 'Ouvert', 'validated' => 'Validé', 'failed' => 'Échoué', 'ordered' => 'Commandé'][$b['status']]) ?></span></td>
        <td class="actions"><a class="btn btn--ghost btn--sm" href="/admin/bon-de-commande?panier=<?= (int)$b['id'] ?>">Bon de commande</a> <a class="btn btn--ghost btn--sm" href="?edit=<?= (int)$b['id'] ?>">Modifier</a></td></tr>
    <?php endforeach; ?><?php if (!$batches): ?><tr><td colspan="4" class="muted">Aucun panier.</td></tr><?php endif; ?></tbody>
  </table></div>
  <form class="panel" method="post" action="/admin/paniers-fournisseur">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
    <h2><?= $f ? 'Modifier' : 'Nouveau panier' ?></h2>
    <div class="field"><label for="b-sup">Fournisseur</label><select id="b-sup" name="supplier_id" required><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>" data-franco="<?= e($s['min_order_franco_ht']) ?>"<?= (int)($f['supplier_id'] ?? 0) === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?> (franco <?= money($s['min_order_franco_ht']) ?>)</option><?php endforeach; ?></select></div>
    <div class="field"><label for="b-name">Nom</label><input id="b-name" name="name" required value="<?= e($f['name'] ?? '') ?>" placeholder="Agrosemens — printemps"></div>
    <div class="field"><label for="b-franco">Objectif franco (€ HT)</label><input id="b-franco" name="franco_ht" inputmode="decimal" required value="<?= e(dec_in($f['franco_ht'] ?? 350, 2)) ?>"></div>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
</div>
