<?php $f = $edit ?? []; $dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
$types = ['packing' => 'Pesée & ensachage', 'unloading' => 'Réception / déchargement', 'distribution' => 'Permanence distribution', 'other' => 'Autre']; ?>
<div class="admin-head"><h1>Créneaux bénévoles</h1></div>
<div class="layout-aside">
  <div class="panel table-wrap"><table class="table">
    <thead><tr><th>Créneau</th><th>Quand</th><th>Inscrits</th><th></th></tr></thead>
    <tbody><?php foreach ($shifts as $s): $full = (int)$s['signed'] >= (int)$s['volunteers_needed']; ?>
      <tr<?= strtotime($s['ends_at']) < time() ? ' style="opacity:.55"' : '' ?>><td><strong><?= e($s['title']) ?></strong><br><small class="muted"><?= e($types[$s['type']]) ?></small></td><td class="small"><?= e(time_range_fr($s['starts_at'], $s['ends_at'])) ?></td>
        <td><span class="chip <?= $full ? 'chip--ok' : 'chip--warn' ?>"><?= (int)$s['signed'] ?>/<?= (int)$s['volunteers_needed'] ?></span><br><small class="muted"><?= e($s['names']) ?></small></td>
        <td class="actions"><a class="btn btn--ghost btn--sm" href="?edit=<?= (int)$s['id'] ?>">Modifier</a></td></tr>
    <?php endforeach; ?><?php if (!$shifts): ?><tr><td colspan="4" class="muted">Aucun créneau.</td></tr><?php endif; ?></tbody>
  </table></div>
  <form class="panel" method="post" action="/admin/creneaux">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
    <h2><?= $f ? 'Modifier' : 'Nouveau créneau' ?></h2>
    <div class="field"><label for="sh-type">Type</label><select id="sh-type" name="type"><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>"<?= ($f['type'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="sh-title">Intitulé</label><input id="sh-title" name="title" required value="<?= e($f['title'] ?? '') ?>" placeholder="Atelier pesée & ensachage graines"></div>
    <div class="grid-2">
      <div class="field"><label for="sh-start">Début</label><input id="sh-start" name="starts_at" type="datetime-local" required value="<?= $dt($f['starts_at'] ?? '') ?>"></div>
      <div class="field"><label for="sh-end">Fin</label><input id="sh-end" name="ends_at" type="datetime-local" required value="<?= $dt($f['ends_at'] ?? '') ?>"></div>
      <div class="field"><label for="sh-need">Bénévoles requis</label><input id="sh-need" name="volunteers_needed" type="number" min="1" value="<?= (int)($f['volunteers_needed'] ?? 3) ?>"></div>
      <div class="field"><label for="sh-camp">Campagne liée</label><select id="sh-camp" name="campaign_id"><option value="">—</option><?php foreach ($campaigns as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)($f['campaign_id'] ?? 0) === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="field"><label for="sh-loc">Lieu</label><input id="sh-loc" name="location" value="<?= e($f['location'] ?? setting('association_address')) ?>"></div>
    <div class="field"><label for="sh-desc">Consignes</label><textarea id="sh-desc" name="description" style="min-height:70px"><?= e($f['description'] ?? '') ?></textarea></div>
    <div class="row"><button class="btn" type="submit">Enregistrer</button><?php if ($f): ?><button class="btn btn--danger btn--sm" name="delete" value="1" data-confirm="Supprimer ce créneau ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
