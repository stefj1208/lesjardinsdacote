<div class="admin-head"><h1>Commandes</h1><a class="btn btn--ghost btn--sm" href="?<?= e(http_build_query(['q' => $q, 'statut' => $st, 'paiement' => $pay, 'format' => 'csv'])) ?>">Exporter (CSV)</a></div>
<form method="get" class="panel mb-2" style="margin-bottom:1rem">
  <div class="grid-4" style="align-items:end">
    <div class="field mb-0"><label for="q">Recherche</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Référence, code, nom, email"></div>
    <div class="field mb-0"><label for="st">Statut</label><select id="st" name="statut"><option value="">Tous</option><?php foreach (Orders::STATUS_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $st === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field mb-0"><label for="pay">Paiement</label><select id="pay" name="paiement"><option value="">Tous</option><?php foreach (Orders::PAYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $pay === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <button class="btn" type="submit">Filtrer</button>
  </div>
</form>
<div class="panel table-wrap"><table class="table">
  <thead><tr><th>Référence</th><th>Membre</th><th>Groupe · pack</th><th>Lot</th><th>Statut</th><th>Paiement</th><th class="num">Montant</th></tr></thead>
  <tbody><?php foreach ($orders as $o): ?>
    <tr><td><a href="/admin/commandes/<?= (int)$o['id'] ?>"><strong><?= e($o['reference']) ?></strong></a><br><small class="muted"><?= e(date_fr($o['created_at'])) ?></small></td>
      <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
      <td class="small"><?= e($o['group_name']) ?><br><?= (int)$o['quantity'] ?> × <?= e($o['pack_name']) ?></td>
      <td><?= (int)$o['batch_no'] ?></td>
      <td class="small"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></td>
      <td><span class="chip <?= $o['payment_status'] === 'paid' ? 'chip--ok' : (in_array($o['payment_status'], ['failed', 'action_required'], true) ? 'chip--bad' : '') ?>"><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></span></td>
      <td class="num"><?= money($o['amount']) ?></td></tr>
  <?php endforeach; ?><?php if (!$orders): ?><tr><td colspan="7" class="muted">Aucune commande.</td></tr><?php endif; ?></tbody>
</table></div>
