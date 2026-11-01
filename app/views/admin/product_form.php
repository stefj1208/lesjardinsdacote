<?php $months = array_filter(array_map('intval', explode(',', (string)($p['sowing_months'] ?? '')))); ?>
<div class="admin-head">
  <h1><?= $p['id'] ? e($p['name'] . ' ' . ($p['variety'] ?? '')) : 'Nouveau produit' ?></h1>
  <?php if ($p['id']): ?><a class="btn btn--sm" href="/admin/campagnes/nouvelle?produit=<?= (int)$p['id'] ?>">Créer une campagne</a><?php endif; ?>
</div>
<form method="post" action="/admin/produits/enregistrer" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
  <section class="panel">
    <h2>Identité</h2>
    <div class="grid-3">
      <div class="field"><label for="name">Nom commun</label><input id="name" name="name" required value="<?= e($p['name'] ?? '') ?>" placeholder="Tomate"></div>
      <div class="field"><label for="variety">Variété</label><input id="variety" name="variety" value="<?= e($p['variety'] ?? '') ?>" placeholder="Cœur de Bœuf"></div>
      <div class="field"><label for="latin_name">Nom latin</label><input id="latin_name" name="latin_name" value="<?= e($p['latin_name'] ?? '') ?>"></div>
      <div class="field"><label for="family">Famille botanique</label><input id="family" name="family" value="<?= e($p['family'] ?? '') ?>"></div>
      <div class="field"><label for="category">Univers</label><select id="category" name="category"><?php foreach (Units::CATEGORIES as $k => $cat): ?><option value="<?= $k ?>"<?= ($p['category'] ?? '') === $k ? ' selected' : '' ?>><?= e($cat['label']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="unit">Unité de mesure</label><select id="unit" name="unit"><?php foreach (Units::ALL as $k => $u): ?><option value="<?= $k ?>"<?= ($p['unit'] ?? '') === $k ? ' selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="supplier_id">Fournisseur</label><select id="supplier_id" name="supplier_id"><option value="">—</option><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"<?= (int)($p['supplier_id'] ?? 0) === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="sku_supplier">Référence fournisseur</label><input id="sku_supplier" name="sku_supplier" value="<?= e($p['sku_supplier'] ?? '') ?>"></div>
      <div class="field"><label for="pmg">PMG — poids de mille graines (g)</label><input id="pmg" name="pmg" inputmode="decimal" value="<?= e(dec_in($p['pmg'] ?? '', 3)) ?>"><span class="hint">Sert à convertir grammes ↔ nombre de graines.</span></div>
    </div>
    <label class="check"><input type="checkbox" name="is_organic" value="1"<?= !empty($p['is_organic']) ? ' checked' : '' ?>> Agriculture biologique</label>
  </section>

  <section class="panel">
    <h2>Grille tarifaire fournisseur</h2>
    <p class="muted small">Un conditionnement par ligne. Quantité dans l'unité du produit (ex. 250 pour un sac de 250 g, 1000 pour 1000 graines).</p>
    <div class="table-wrap"><table class="table tier-editor" id="packs">
      <thead><tr><th>Libellé</th><th>Quantité</th><th>Prix € HT</th><th></th></tr></thead>
      <tbody><?php foreach ($packs ?: [['label' => '', 'qty' => '', 'cost_ht' => '']] as $pk): ?>
        <tr><td><input name="pack_label[]" value="<?= e($pk['label']) ?>" placeholder="Sac 500 g"></td><td><input name="pack_qty[]" inputmode="decimal" value="<?= e(dec_in($pk['qty'], 3)) ?>"></td>
          <td><input name="pack_cost[]" inputmode="decimal" value="<?= e(dec_in($pk['cost_ht'], 2)) ?>"></td><td><button class="btn btn--danger btn--sm" type="button" data-remove-pack aria-label="Supprimer">×</button></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <button class="btn btn--ghost btn--sm mt-4" type="button" data-add-pack>Ajouter un conditionnement</button>
  </section>

  <section class="panel">
    <h2>Fiche jardinier</h2>
    <div class="field"><label for="description">Description</label><textarea id="description" name="description"><?= e($p['description'] ?? '') ?></textarea></div>
    <div class="field"><label for="sowing_advice">Conseils de semis / plantation</label><textarea id="sowing_advice" name="sowing_advice"><?= e($p['sowing_advice'] ?? '') ?></textarea></div>
    <fieldset style="border:0;padding:0;margin:0 0 var(--s-4)"><legend class="label">Mois de semis</legend>
      <div class="chips"><?php for ($m = 1; $m <= 12; $m++): ?><label class="chip"><input type="checkbox" name="sowing_months[]" value="<?= $m ?>"<?= in_array($m, $months, true) ? ' checked' : '' ?>> <?= e(FR_MONTHS_SHORT[$m]) ?></label><?php endfor; ?></div></fieldset>
    <div class="grid-4">
      <div class="field"><label for="sowing_depth">Profondeur</label><input id="sowing_depth" name="sowing_depth" value="<?= e($p['sowing_depth'] ?? '') ?>"></div>
      <div class="field"><label for="exposure">Exposition</label><select id="exposure" name="exposure"><option value="">—</option><?php foreach (Units::EXPOSURE as $k => $l): ?><option value="<?= $k ?>"<?= ($p['exposure'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="water">Besoin en eau</label><select id="water" name="water"><option value="">—</option><?php foreach (Units::WATER as $k => $l): ?><option value="<?= $k ?>"<?= ($p['water'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="difficulty">Difficulté</label><select id="difficulty" name="difficulty"><option value="">—</option><?php foreach (Units::DIFFICULTY as $k => $l): ?><option value="<?= $k ?>"<?= ($p['difficulty'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="pot_size">Taille de pot / conteneur</label><input id="pot_size" name="pot_size" value="<?= e($p['pot_size'] ?? '') ?>" placeholder="Ø 12 cm, 3 L…"></div>
      <div class="field"><label for="image_url">Photo (URL)</label><input id="image_url" name="image_url" value="<?= e($p['image_url'] ?? '') ?>"></div>
      <div class="field"><label for="image">ou envoyer une photo</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
    </div>
    <div class="field"><label for="source_url">Page produit chez le fournisseur</label><input id="source_url" name="source_url" type="url" value="<?= e($p['source_url'] ?? '') ?>"></div>
  </section>

  <div class="row mt-6">
    <button class="btn btn--lg" type="submit">Enregistrer</button>
    <button class="btn btn--go btn--lg" type="submit" name="then_campaign" value="1">Enregistrer et créer une campagne</button>
  </div>
</form>
<?php if ($p['id']): ?>
<form method="post" action="/admin/produits/<?= (int)$p['id'] ?>/supprimer" class="mt-6" data-confirm="Supprimer ce produit ?"><?= csrf_field() ?><button class="btn btn--danger btn--sm" type="submit">Supprimer le produit</button></form>
<?php endif; ?>
