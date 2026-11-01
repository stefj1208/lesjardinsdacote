<?php $roles = ['client' => 'Membre', 'volunteer' => 'Bénévole', 'admin' => 'Administrateur']; ?>
<div class="admin-head"><h1>Membres</h1></div>
<nav class="tabs">
  <?php foreach (['' => 'Tous', 'candidats' => 'Candidats bénévoles', 'volunteer' => 'Bénévoles', 'admin' => 'Administrateurs', 'client' => 'Membres'] as $k => $l): ?><a href="?filtre=<?= $k ?>"<?= $f === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
</nav>
<form method="get" class="mb-6" style="margin-bottom:var(--s-4)"><input type="hidden" name="filtre" value="<?= e($f) ?>"><div class="row"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom ou email" style="max-width:320px"><button class="btn btn--ghost" type="submit">Chercher</button></div></form>
<div class="panel table-wrap"><table class="table">
  <thead><tr><th>Membre</th><th>Contact</th><th class="num">Commandes</th><th>Inscrit le</th><th>Rôle</th></tr></thead>
  <tbody><?php foreach ($users as $u): ?>
    <tr><td><strong><?= e(trim($u['first_name'] . ' ' . $u['last_name'])) ?></strong><?= $u['wants_volunteer'] && $u['role'] === 'client' ? ' <span class="chip chip--warn">veut aider</span>' : '' ?></td>
      <td class="small"><?= e($u['email']) ?><br><?= e($u['phone']) ?> <?= e($u['postal_code']) ?></td><td class="num"><?= (int)$u['orders'] ?></td><td class="small"><?= e(date_fr($u['created_at'])) ?></td>
      <td><form method="post" action="/admin/utilisateurs/<?= (int)$u['id'] ?>" class="row"><?= csrf_field() ?>
        <select name="role" data-autosubmit aria-label="Rôle de <?= e($u['first_name']) ?>"><?php foreach ($roles as $k => $l): ?><option value="<?= $k ?>"<?= $u['role'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></form></td></tr>
  <?php endforeach; ?><?php if (!$users): ?><tr><td colspan="5" class="muted">Personne ici.</td></tr><?php endif; ?></tbody>
</table></div>
