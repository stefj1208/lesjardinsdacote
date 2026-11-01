<div class="admin-head"><h1>Import du catalogue</h1><a class="btn btn--ghost btn--sm" href="/admin/import/modele.csv">Télécharger le modèle CSV</a></div>
<?php if ($rows === null): ?>
<div class="layout-aside">
  <form class="panel" method="post" action="/admin/import" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h2>1. Envoyer le fichier</h2>
    <div class="field"><label for="supplier_id">Fournisseur</label>
      <select id="supplier_id" name="supplier_id" required><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select>
      <?php if (!$suppliers): ?><span class="error-text">Créez d'abord le fournisseur (ex. Agrosemens) dans <a href="/admin/fournisseurs">Fournisseurs</a>.</span><?php endif; ?></div>
    <div class="field"><label for="csv">Fichier CSV (séparateur ; ou ,)</label><input id="csv" name="csv" type="file" accept=".csv,text/csv" required></div>
    <button class="btn" type="submit">Prévisualiser</button>
  </form>
  <aside class="panel small">
    <h2>Format attendu</h2>
    <p>Une ligne par produit. Colonnes : <code><?= e(implode(', ', IMPORT_COLUMNS)) ?></code>.</p>
    <p>La colonne <code>conditionnements</code> contient la grille tarifaire : <code>Libellé=quantité=prix HT</code> séparés par <code>|</code>, par exemple <code>Sachet 10 g=10=4,20|Sac 1 kg=1000=160</code>.</p>
    <p>Un produit déjà importé (même référence fournisseur) est mis à jour, jamais dupliqué.</p>
    <p>Pour extraire automatiquement les fiches du site Agrosemens, utilisez le script fourni <code>outils/import_agrosemens.py</code> sur votre ordinateur : il produit ce même format.</p>
  </aside>
</div>
<?php else: $ok = count(array_filter($rows, fn($r) => !$r['errors'])); ?>
<form method="post" action="/admin/import/confirmer">
  <?= csrf_field() ?>
  <section class="panel">
    <h2>2. Vérifier (<?= $ok ?> <?= plural($ok, 'ligne valide', 'lignes valides') ?> sur <?= count($rows) ?>)</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Ligne</th><th>Produit</th><th>Unité</th><th>PMG</th><th>Conditionnements</th><th>État</th></tr></thead>
      <tbody><?php foreach ($rows as $r): $d = $r['data']; ?>
        <tr<?= $r['errors'] ? ' style="background:#FDECEA"' : '' ?>>
          <td><?= $r['line'] ?></td>
          <td><strong><?= e($d['name'] . ' ' . $d['variety']) ?></strong><br><small class="muted"><?= e($d['latin_name']) ?> · <?= e($d['sku_supplier']) ?></small></td>
          <td><?= e(Units::short($d['unit'])) ?></td>
          <td><?= $d['pmg'] !== null ? e(num($d['pmg'], 3)) : '—' ?></td>
          <td class="small"><?php foreach ($r['packs'] as $pk): ?><?= e($pk['label']) ?> : <?= money($pk['cost_ht']) ?><br><?php endforeach; ?></td>
          <td><?php if ($r['errors']): ?><span class="error-text"><?= e(implode(' ; ', $r['errors'])) ?></span><?php else: ?><span class="chip <?= $r['exists'] ? '' : 'chip--ok' ?>"><?= $r['exists'] ? 'Mise à jour' : 'Nouveau' ?></span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <label class="check mt-4"><input type="checkbox" name="create_campaigns" value="1" checked> Créer automatiquement une campagne brouillon par produit, avec ses paliers calculés (marge, freinte, sachet par défaut des Paramètres)</label>
    <div class="row mt-6"><button class="btn btn--go btn--lg" type="submit"<?= $ok ? '' : ' disabled' ?>>Importer <?= $ok ?> <?= plural($ok, 'produit', 'produits') ?></button><a class="btn btn--ghost" href="/admin/import">Changer de fichier</a></div>
  </section>
</form>
<?php endif; ?>
