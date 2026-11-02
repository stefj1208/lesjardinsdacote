<div class="admin-head"><h1><?= $v['id'] ? e(trim($v['name'] . ' ' . $v['variety'])) : 'Nouvelle variété' ?></h1></div>
<form method="post" action="/admin/varietes/enregistrer" enctype="multipart/form-data" class="layout-aside">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
  <section class="panel">
    <div class="grid-3">
      <div class="field"><label for="category">Univers</label><select id="category" name="category"><?php foreach (Cat::CATEGORIES as $k => $c): ?><option value="<?= $k ?>"<?= $v['category'] === $k ? ' selected' : '' ?>><?= e($c['label']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="name">Plante</label><input id="name" name="name" required value="<?= e($v['name']) ?>" placeholder="Tomate noire"></div>
      <div class="field"><label for="variety">Variété</label><input id="variety" name="variety" value="<?= e($v['variety']) ?>" placeholder="Black from Tula"></div>
    </div>
    <div class="grid-2">
      <div class="field"><label for="latin_name">Nom latin</label><input id="latin_name" name="latin_name" value="<?= e($v['latin_name']) ?>"></div>
      <div class="field"><label for="note">Description courte</label><input id="note" name="note" value="<?= e($v['note']) ?>" maxlength="255"></div>
    </div>
    <h3 class="mt-2">Portion et prix</h3>
    <div class="grid-4">
      <div class="field"><label for="portion_qty">Portion dans le pack</label><input id="portion_qty" name="portion_qty" inputmode="decimal" value="<?= e(num($v['portion_qty'], 2)) ?>"></div>
      <div class="field"><label for="unit">Unité</label><select id="unit" name="unit"><?php foreach (Cat::UNITS as $k => $u): ?><option value="<?= $k ?>"<?= $v['unit'] === $k ? ' selected' : '' ?>><?= e($u[1]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="purchase_price">Prix d'achat TTC (€)</label><input id="purchase_price" name="purchase_price" inputmode="decimal" value="<?= $v['purchase_price'] !== null ? e(number_format((float)$v['purchase_price'], 2, ',', '')) : '' ?>" placeholder="9,92"></div>
      <div class="field"><label for="purchase_qty">… pour (quantité achetée)</label><input id="purchase_qty" name="purchase_qty" inputmode="decimal" value="<?= $v['purchase_qty'] !== null ? e(num($v['purchase_qty'], 2)) : '' ?>" placeholder="100"></div>
    </div>
    <div class="grid-2">
      <div class="field"><label for="market_price">Prix d'un sachet en jardinerie (€)</label><input id="market_price" name="market_price" inputmode="decimal" value="<?= $v['market_price'] !== null ? e(number_format((float)$v['market_price'], 2, ',', '')) : '' ?>" placeholder="3,30"><span class="hint">Sert à afficher l'économie réalisée.</span></div>
      <div class="field"><label>Coût d'une portion</label><p class="big-num" style="font-size:1.8rem;margin:.2rem 0 0"><?= $v['unit_cost'] !== null ? money($v['unit_cost']) : '—' ?></p><span class="hint">+ <?= money(Varieties::sachetCost()) ?> de sachet et d'étiquette</span></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="supplier">Fournisseur</label><input id="supplier" name="supplier" value="<?= e($v['supplier']) ?>"></div>
      <div class="field"><label for="supplier_ref">Référence</label><input id="supplier_ref" name="supplier_ref" value="<?= e($v['supplier_ref']) ?>" placeholder="TO134"></div>
      <div class="field"><label for="position">Ordre d'affichage</label><input id="position" name="position" type="number" value="<?= (int)$v['position'] ?>"></div>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $v['is_active'] ? ' checked' : '' ?>> Proposée dans les nouveaux votes</label>
  </section>
  <aside class="panel">
    <h3>Photo</h3>
    <?= ph($v['photo'], '', 'thumb-preview', 400) ?>
    <div class="field mt-2"><label for="photo_file">Envoyer une photo</label><input id="photo_file" name="photo_file" type="file" accept="image/jpeg,image/png,image/webp"></div>
    <div class="field"><label for="photo">ou référence / adresse</label><input id="photo" name="photo" value="<?= e($v['photo']) ?>" placeholder="https://… ou commons:Fichier.jpg"></div>
    <button class="btn btn--block" type="submit">Enregistrer</button>
    <?php if (!$v['id']): ?><button class="btn btn--ghost btn--block mt-1" type="submit" name="next" value="new">Enregistrer et en ajouter une autre</button><?php endif; ?>
  </aside>
</form>
<?php if ($v['id']): ?><form class="mt-3" method="post" action="/admin/varietes/<?= (int)$v['id'] ?>/supprimer" data-confirm="Supprimer cette variété ?"><?= csrf_field() ?><button class="btn btn--danger btn--sm">Supprimer</button></form><?php endif; ?>
