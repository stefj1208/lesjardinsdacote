<div class="admin-head">
  <h1><?= $g['id'] ? e($g['name']) : 'Nouveau groupe' ?></h1>
  <?php if ($g['id']): ?><div class="row">
    <?php if ($g['status'] === 'active'): ?><a class="btn btn--ghost btn--sm" href="/groupes/<?= e($g['slug']) ?>" target="_blank">Voir la page</a><?php endif; ?>
    <?php $act = fn($a, $l, $cls = 'btn--ghost', $c = '') => '<form method="post" action="/admin/groupes/' . (int)$g['id'] . '/action"' . ($c ? ' data-confirm="' . e($c) . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><button class="btn btn--sm ' . $cls . '">' . e($l) . '</button></form>'; ?>
    <?= $g['status'] === 'proposed' ? $act('validate', 'Valider et publier', '') : '' ?>
    <?= $g['status'] === 'active' ? $act('archive', 'Archiver') : '' ?>
    <?= $g['status'] === 'archived' ? $act('reactivate', 'Réactiver') : '' ?>
    <?= $act('delete', 'Supprimer', 'btn--danger', 'Supprimer définitivement ce groupe ?') ?>
  </div><?php endif; ?>
</div>
<?php if ($g['id'] && $g['status'] === 'proposed'): ?><div class="notice mb-2" style="margin-bottom:1rem">Proposition reçue le <?= e(date_fr($g['created_at'], true)) ?>. Vérifiez les informations, ajoutez une photo et un lieu de retrait, puis publiez.</div><?php endif; ?>
<div class="layout-aside">
  <form class="panel" method="post" action="/admin/groupes/enregistrer" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
    <div class="grid-2">
      <div class="field"><label for="commune">Commune</label><input id="commune" name="commune" required value="<?= e($g['commune']) ?>"></div>
      <div class="field"><label for="postal_code">Code postal</label><input id="postal_code" name="postal_code" value="<?= e($g['postal_code']) ?>"></div>
    </div>
    <div class="field"><label for="name">Nom du groupe</label><input id="name" name="name" value="<?= e($g['name']) ?>" placeholder="Les Jardins d'à Côté de …"><span class="hint">Laissé vide : « Les Jardins d'à Côté de [commune] ».</span></div>
    <div class="grid-2">
      <div class="field"><label for="organizer_type">Organisateur</label><select id="organizer_type" name="organizer_type"><?php foreach (Cat::ORGANIZERS as $k => $l): ?><option value="<?= $k ?>"<?= $g['organizer_type'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="organizer_name">Nom affiché</label><input id="organizer_name" name="organizer_name" value="<?= e($g['organizer_name']) ?>" placeholder="Mairie de …"></div>
      <div class="field"><label for="contact_name">Contact</label><input id="contact_name" name="contact_name" value="<?= e($g['contact_name']) ?>"></div>
      <div class="field"><label for="contact_email">Email du contact</label><input id="contact_email" name="contact_email" type="email" value="<?= e($g['contact_email']) ?>"></div>
      <div class="field"><label for="contact_phone">Téléphone</label><input id="contact_phone" name="contact_phone" value="<?= e($g['contact_phone']) ?>"></div>
      <div class="field"><label for="volunteers_needed">Bénévoles nécessaires</label><input id="volunteers_needed" name="volunteers_needed" type="number" min="1" max="10" value="<?= (int)$g['volunteers_needed'] ?>"></div>
      <div class="field"><label for="pickup_place">Lieu de retrait</label><input id="pickup_place" name="pickup_place" value="<?= e($g['pickup_place']) ?>"></div>
      <div class="field"><label for="pickup_address">Adresse</label><input id="pickup_address" name="pickup_address" value="<?= e($g['pickup_address']) ?>"></div>
    </div>
    <div class="field"><label for="description">Présentation</label><textarea id="description" name="description"><?= e($g['description']) ?></textarea></div>
    <div class="row" style="align-items:flex-start">
      <?= ph($g['photo'], '', 'thumb-preview', 400) ?>
      <div class="grow">
        <div class="field"><label for="photo_file">Photo du groupe</label><input id="photo_file" name="photo_file" type="file" accept="image/jpeg,image/png,image/webp"></div>
        <div class="field"><label for="photo">ou référence / adresse de la photo</label><input id="photo" name="photo" value="<?= e($g['photo']) ?>" placeholder="https://… ou commons:Fichier.jpg"></div>
      </div>
    </div>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
  <aside class="stack">
    <?php if ($g['id']): ?>
    <div class="panel"><div class="row row--between"><h3 class="mb-0">Ventes</h3><a class="btn btn--sm" href="/admin/ventes/nouvelle?groupe=<?= (int)$g['id'] ?>">Nouvelle</a></div>
      <?php foreach ($sales as $s): ?><p class="mb-0 mt-1"><a href="/admin/ventes/<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a> <span class="chip"><?= e(Cat::SALE_STATUS[$s['status']]) ?></span></p><?php endforeach; ?>
      <?php if (!$sales): ?><p class="muted small mb-0 mt-1">Aucune vente : créez-en une pour préparer le vote.</p><?php endif; ?>
    </div>
    <div class="panel"><h3>Membres (<?= count($members) ?>)</h3>
      <?php foreach ($members as $m): ?>
        <form class="row row--between" method="post" action="/admin/groupes/<?= (int)$g['id'] ?>/action" style="padding:6px 0;border-bottom:1px solid var(--line)"><?= csrf_field() ?><input type="hidden" name="action" value="member_role"><input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
          <span class="small"><strong><?= e(trim($m['first_name'] . ' ' . $m['last_name'])) ?></strong><br><span class="muted"><?= e($m['email']) ?></span></span>
          <select name="role" data-autosubmit style="width:auto;padding:6px 8px" aria-label="Rôle"><option value="member"<?= $m['gm_role'] === 'member' ? ' selected' : '' ?>>Membre</option><option value="volunteer"<?= $m['gm_role'] === 'volunteer' ? ' selected' : '' ?>>Bénévole</option><option value="remove">Retirer</option></select>
        </form>
      <?php endforeach; ?>
      <?php if (!$members): ?><p class="muted small mb-0">Personne pour l'instant.</p><?php endif; ?>
    </div>
    <?php endif; ?>
  </aside>
</div>
