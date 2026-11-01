<div class="admin-head">
  <h1>Catalogue produits</h1>
  <div class="row"><a class="btn btn--ghost btn--sm" href="/admin/import">Importer un CSV</a><a class="btn btn--sm" href="/admin/produits/nouveau">Nouveau produit</a></div>
</div>
<form method="get" class="mb-6" style="margin-bottom:var(--s-4)"><div class="row"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, variété, référence…" style="max-width:360px"><button class="btn btn--ghost" type="submit">Chercher</button></div></form>
<div class="panel table-wrap">
  <table class="table">
    <thead><tr><th>Produit</th><th>Univers</th><th>Unité</th><th>Fournisseur</th><th class="num">Conditionnements</th><th class="num">Campagnes</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><a href="/admin/produits/<?= (int)$p['id'] ?>"><strong><?= e($p['name']) ?></strong> <?= e($p['variety']) ?></a><br><small class="muted"><?= e($p['latin_name']) ?><?= $p['sku_supplier'] ? ' · ' . e($p['sku_supplier']) : '' ?></small></td>
        <td><?= e(Units::CATEGORIES[$p['category']]['label']) ?></td>
        <td><?= e(Units::ALL[$p['unit']]['name']) ?></td>
        <td><?= e($p['supplier']) ?></td>
        <td class="num"><?= (int)$p['packs'] ?: '<span style="color:var(--red)">0</span>' ?></td>
        <td class="num"><?= (int)$p['camps'] ?></td>
        <td class="actions"><a class="btn btn--ghost btn--sm" href="/admin/campagnes/nouvelle?produit=<?= (int)$p['id'] ?>">Créer une campagne</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="7" class="muted">Aucun produit. Commencez par <a href="/admin/import">importer le catalogue</a>.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
