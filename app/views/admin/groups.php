<div class="admin-head"><h1>Groupes</h1><a class="btn btn--sm" href="/admin/groupes/nouveau">Nouveau groupe</a></div>
<nav class="tabs">
  <a href="/admin/groupes"<?= $st === '' ? ' aria-current="page"' : '' ?>>Tous</a>
  <?php foreach (['proposed' => 'À valider', 'active' => 'Publiés', 'archived' => 'Archivés'] as $k => $l): ?><a href="?statut=<?= $k ?>"<?= $st === $k ? ' aria-current="page"' : '' ?>><?= $l ?> (<?= $counts[$k] ?? 0 ?>)</a><?php endforeach; ?>
</nav>
<div class="panel table-wrap"><table class="table">
  <thead><tr><th>Groupe</th><th>Organisateur</th><th>Contact</th><th>Bénévoles</th><th>Statut</th></tr></thead>
  <tbody><?php foreach ($groups as $g): ?>
    <tr><td><a href="/admin/groupes/<?= (int)$g['id'] ?>"><strong><?= e($g['name']) ?></strong></a><br><small class="muted"><?= e($g['postal_code']) ?> · <?= $g['members'] ?> membres</small></td>
      <td class="small"><?= e(Cat::ORGANIZERS[$g['organizer_type']]) ?><br><?= e($g['organizer_name']) ?></td>
      <td class="small"><?= e($g['contact_name']) ?><br><?= e($g['contact_email']) ?></td>
      <td><?= $g['volunteers'] ?> / <?= (int)$g['volunteers_needed'] ?></td>
      <td><?php if ($g['status'] === 'proposed'): ?><span class="chip chip--warn">À valider</span><?php elseif ($g['status'] === 'archived'): ?><span class="chip">Archivé</span><?php else: partial('partials/status', ['s' => $g['public_status']]); endif; ?></td></tr>
  <?php endforeach; ?><?php if (!$groups): ?><tr><td colspan="5" class="muted">Aucun groupe.</td></tr><?php endif; ?></tbody>
</table></div>
