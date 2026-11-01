<div class="admin-head"><h1>Ventes & votes</h1><a class="btn btn--sm" href="/admin/ventes/nouvelle">Nouvelle vente</a></div>
<div class="panel table-wrap"><table class="table">
  <thead><tr><th>Vente</th><th>Statut</th><th>Packs / vote</th><th>Lots</th><th>Échéance</th></tr></thead>
  <tbody><?php foreach ($sales as $s): ?>
    <tr><td><a href="/admin/ventes/<?= (int)$s['id'] ?>"><strong><?= e($s['group_name']) ?></strong></a><br><small class="muted"><?= e($s['title']) ?> · <?= e(Cat::label($s['category'])) ?></small></td>
      <td><span class="chip <?= $s['status'] === 'open' ? 'chip--bad' : ($s['status'] === 'voting' ? 'chip--ok' : '') ?>"><?= e(Cat::SALE_STATUS[$s['status']]) ?></span></td>
      <td class="small"><?php if ($s['winner']): ?>Gagnant : <?= e($s['winner']['name']) ?><?php else: ?><?= count($s['packs']) ?> packs · <?= $s['votes_total'] ?> voix<?php endif; ?></td>
      <td class="small"><?php if (in_array($s['status'], ['open', 'closed'], true)): ?><?= $s['batch']['complete'] ?> complet(s) · <?= $s['batch']['sold'] ?> vendus<?= $s['status'] === 'open' ? ' · en cours ' . $s['batch']['fill'] . '/' . $s['batch']['size'] : '' ?><?php else: ?>—<?php endif; ?></td>
      <td class="small"><?= $s['status'] === 'voting' ? 'vote → ' . e(date_fr($s['vote_ends_at'], true)) : ($s['status'] === 'open' ? 'fin → ' . e(date_fr($s['sale_ends_at'], true)) : '—') ?></td></tr>
  <?php endforeach; ?><?php if (!$sales): ?><tr><td colspan="5" class="muted">Aucune vente.</td></tr><?php endif; ?></tbody>
</table></div>
