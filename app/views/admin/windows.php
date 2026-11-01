<?php $f = $edit ?? []; $dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; ?>
<div class="admin-head"><h1>Distributions & retraits</h1></div>
<div class="layout-aside">
  <div class="panel table-wrap"><table class="table">
    <thead><tr><th>Distribution</th><th>Quand</th><th class="num">Campagnes</th><th></th></tr></thead>
    <tbody><?php foreach ($windows as $w): ?>
      <tr><td><strong><?= e($w['label']) ?></strong><br><small class="muted"><?= e($w['location']) ?> — <?= e($w['address']) ?></small></td><td><?= e(time_range_fr($w['starts_at'], $w['ends_at'])) ?></td><td class="num"><?= (int)$w['camps'] ?></td>
        <td class="actions"><a class="btn btn--ghost btn--sm" href="?edit=<?= (int)$w['id'] ?>">Modifier</a></td></tr>
    <?php endforeach; ?><?php if (!$windows): ?><tr><td colspan="4" class="muted">Aucune distribution planifiée.</td></tr><?php endif; ?></tbody>
  </table></div>
  <form class="panel" method="post" action="/admin/retraits">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
    <h2><?= $f ? 'Modifier' : 'Nouvelle distribution' ?></h2>
    <div class="field"><label for="w-label">Intitulé</label><input id="w-label" name="label" required value="<?= e($f['label'] ?? '') ?>" placeholder="Distribution de printemps"></div>
    <div class="field"><label for="w-loc">Lieu</label><input id="w-loc" name="location" required value="<?= e($f['location'] ?? 'Boutique Les Jardins d\'à Côté') ?>"></div>
    <div class="field"><label for="w-addr">Adresse</label><input id="w-addr" name="address" required value="<?= e($f['address'] ?? setting('association_address')) ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="w-start">Début</label><input id="w-start" name="starts_at" type="datetime-local" required value="<?= $dt($f['starts_at'] ?? '') ?>"></div>
      <div class="field"><label for="w-end">Fin</label><input id="w-end" name="ends_at" type="datetime-local" required value="<?= $dt($f['ends_at'] ?? '') ?>"></div>
    </div>
    <div class="field"><label for="w-notes">Notes</label><textarea id="w-notes" name="notes" style="min-height:70px"><?= e($f['notes'] ?? '') ?></textarea></div>
    <div class="row"><button class="btn" type="submit">Enregistrer</button><?php if ($f): ?><button class="btn btn--danger btn--sm" name="delete" value="1" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
