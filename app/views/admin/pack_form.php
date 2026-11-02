<?php $rows = $p['items'] ?: [['name' => '', 'variety' => '', 'latin_name' => '', 'quantity' => '', 'unit' => $p['category'] === 'seeds' ? 'graines' : 'plants', 'note' => '', 'photo' => '', 'market_price' => null]]; ?>
<div class="admin-head"><h1><?= $p['id'] ? e($p['name']) : 'Nouveau pack' ?></h1><?php if ($p['id']): ?><a class="btn btn--ghost btn--sm" href="/packs/<?= e($p['slug']) ?>" target="_blank">Voir la page</a><?php endif; ?></div>
<form method="post" action="/admin/packs/enregistrer" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
  <section class="panel">
    <div class="grid-3">
      <div class="field"><label for="name">Nom du pack</label><input id="name" name="name" required value="<?= e($p['name']) ?>"></div>
      <div class="field"><label for="category">Univers</label><select id="category" name="category"><?php foreach (Cat::CATEGORIES as $k => $c): ?><option value="<?= $k ?>"<?= $p['category'] === $k ? ' selected' : '' ?>><?= e($c['label']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="price">Prix (€ TTC)</label><input id="price" name="price" inputmode="decimal" value="<?= e(dec_in($p['price'], 2)) ?>"></div>
    </div>
    <div class="field"><label for="tagline">Accroche</label><input id="tagline" name="tagline" value="<?= e($p['tagline']) ?>" maxlength="255"></div>
    <div class="field"><label for="description">Description</label><textarea id="description" name="description"><?= e($p['description']) ?></textarea></div>
    <div class="row" style="align-items:flex-start">
      <?= ph($p['photo'], '', 'thumb-preview', 400) ?>
      <div class="grow"><div class="field"><label for="photo_file">Photo principale</label><input id="photo_file" name="photo_file" type="file" accept="image/jpeg,image/png,image/webp"></div>
        <div class="field"><label for="photo">ou référence / adresse</label><input id="photo" name="photo" value="<?= e($p['photo']) ?>" placeholder="https://… ou commons:Fichier.jpg"></div></div>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $p['is_active'] ? ' checked' : '' ?>> Pack disponible pour les ventes</label>
  </section>
  <section class="panel">
    <h2>Contenu : variétés et quantités</h2>
    <div class="table-wrap"><table class="table editor" id="items">
      <thead><tr><th>Photo</th><th>Plante</th><th>Variété</th><th>Nom latin</th><th>Quantité</th><th>Unité</th><th title="Prix public d'un sachet ou d'un plant équivalent en jardinerie">Prix marché</th><th>Conseil</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $it): ?>
        <tr>
          <td style="min-width:150px"><?= ph($it['photo'], '', 'thumb-preview', 200) ?><input name="item_photo[<?= $i ?>]" value="<?= e($it['photo']) ?>" placeholder="URL ou commons:" style="margin-top:4px;font-size:.8rem"><input type="file" name="item_file[<?= $i ?>]" accept="image/jpeg,image/png,image/webp" style="font-size:.75rem;margin-top:4px"></td>
          <td><input name="item_name[<?= $i ?>]" value="<?= e($it['name']) ?>" placeholder="Tomate"></td>
          <td><input name="item_variety[<?= $i ?>]" value="<?= e($it['variety']) ?>" placeholder="Cœur de bœuf"></td>
          <td><input name="item_latin[<?= $i ?>]" value="<?= e($it['latin_name']) ?>"></td>
          <td style="width:90px"><input name="item_qty[<?= $i ?>]" type="number" min="1" value="<?= e($it['quantity']) ?>"></td>
          <td><select name="item_unit[<?= $i ?>]"><?php foreach (Cat::UNITS as $k => $u): ?><option value="<?= $k ?>"<?= $it['unit'] === $k ? ' selected' : '' ?>><?= e($u[1]) ?></option><?php endforeach; ?></select></td>
          <td style="width:100px"><input name="item_market[<?= $i ?>]" inputmode="decimal" value="<?= ($it['market_price'] ?? null) !== null && $it['market_price'] !== '' ? e(number_format((float)$it['market_price'], 2, ',', '')) : '' ?>" placeholder="3,30"></td>
          <td><input name="item_note[<?= $i ?>]" value="<?= e($it['note']) ?>"></td>
          <td><button class="btn btn--danger btn--sm" type="button" data-remove-row aria-label="Retirer">×</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <button class="btn btn--ghost btn--sm mt-2" type="button" data-add-row="#items">Ajouter une variété</button>
    <?php if ($p['id'] && !empty($p['market'])): ?>
      <p class="mt-3 mb-0"><strong>Valeur au prix du marché : <?= eur($p['market']) ?></strong> pour <?= eur($p['price']) ?><?= $p['save'] > 0 ? ' · économie affichée : ' . eur($p['save']) . ' (−' . $p['save_pct'] . ' %)' : ' · aucune économie affichée (prix marché inférieur au prix du pack)' ?></p>
    <?php endif; ?>
    <p class="hint mt-1 mb-0">Le prix marché est le prix public d'un sachet (ou d'un plant) de la même variété en jardinerie. La source est indiquée sur le site : <a href="/admin/parametres">Paramètres</a>.</p>
  </section>
  <div class="row mt-3"><button class="btn btn--lg" type="submit">Enregistrer le pack</button></div>
</form>
<?php if ($p['id']): ?><form class="mt-3" method="post" action="/admin/packs/<?= (int)$p['id'] ?>/supprimer" data-confirm="Supprimer ce pack ?"><?= csrf_field() ?><button class="btn btn--danger btn--sm">Supprimer</button></form><?php endif; ?>
