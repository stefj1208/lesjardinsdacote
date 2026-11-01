<div class="admin-head">
  <h1>Campagnes</h1>
  <div class="row"><a class="btn btn--ghost btn--sm" href="/admin/bon-de-commande">Bon de commande</a><a class="btn btn--sm" href="/admin/campagnes/nouvelle">Nouvelle campagne</a></div>
</div>
<nav class="tabs">
  <a href="/admin/campagnes"<?= $status === '' ? ' aria-current="page"' : '' ?>>Toutes</a>
  <?php foreach (Units::STATUSES as $k => $l): if (empty($counts[$k])) continue; ?><a href="?statut=<?= $k ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?> (<?= $counts[$k] ?>)</a><?php endforeach; ?>
</nav>
<div class="panel table-wrap">
  <table class="table">
    <thead><tr><th>Campagne</th><th>Statut</th><th>Mode</th><th>Réservé</th><th>Palier</th><th class="num">Prix part</th><th>Fin</th></tr></thead>
    <tbody>
    <?php foreach ($camps as $c): $st = $c['state']; ?>
      <tr>
        <td><a href="/admin/campagnes/<?= (int)$c['id'] ?>"><strong><?= e($c['title']) ?></strong></a><br><small class="muted"><?= e($c['supplier_name']) ?> · <?= $c['participants'] ?> participants</small></td>
        <td><span class="chip <?= in_array($c['status'], ['active', 'validated', 'ready_pickup'], true) ? 'chip--ok' : ($c['status'] === 'failed' ? 'chip--bad' : '') ?>"><?= e(Units::STATUSES[$c['status']]) ?></span></td>
        <td class="small"><?= e(['volume' => 'Volume', 'logistic' => 'Logistique', 'franco' => 'Franco'][$c['trigger_mode']]) ?></td>
        <td><?= e(Units::qty($st['reserved'], $c['unit'])) ?><br><small class="muted"><?= $st['to_t1_pct'] ?> % du palier 1</small></td>
        <td><?= $st['tier'] ?>/<?= $st['max_tier'] ?></td>
        <td class="num"><?= money(Pricing::packetPrice($st['price'], (float)$c['ref_portion'])) ?><br><small class="muted"><?= e(Units::qty($c['ref_portion'], $c['unit'])) ?></small></td>
        <td class="small"><?= e(date_fr($c['end_at'], true)) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$camps): ?><tr><td colspan="7" class="muted">Aucune campagne.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
