<div class="admin-head"><h1>Packs</h1><a class="btn btn--sm" href="/admin/packs/nouveau<?= $cat ? '?univers=' . $cat : '' ?>">Nouveau pack</a></div>
<nav class="tabs"><a href="/admin/packs"<?= !$cat ? ' aria-current="page"' : '' ?>>Tous</a><?php foreach (Cat::CATEGORIES as $k => $c): ?><a href="?univers=<?= $k ?>"<?= $cat === $k ? ' aria-current="page"' : '' ?>><?= e($c['label']) ?></a><?php endforeach; ?></nav>
<div class="groups-grid">
  <?php foreach ($packs as $p): ?>
    <a class="group-card" href="/admin/packs/<?= (int)$p['id'] ?>"><?= ph($p['photo'], $p['name'], '', 600) ?>
      <div class="group-card-body"><div class="row row--between"><span class="chip"><?= e(Cat::label($p['category'])) ?></span><strong><?= money($p['price']) ?></strong></div>
        <h3><?= e($p['name']) ?></h3><div class="meta"><?= e($p['summary']) ?><?= $p['is_active'] ? '' : ' · désactivé' ?></div></div></a>
  <?php endforeach; ?>
</div>
<?php if (!$packs): ?><div class="empty"><h3>Aucun pack</h3><p>Créez vos packs de saison : variétés, quantités, photos et prix.</p></div><?php endif; ?>
