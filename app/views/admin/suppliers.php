<?php $f = $edit ?? []; ?>
<div class="admin-head"><h1>Fournisseurs</h1></div>
<div class="layout-aside">
  <div class="panel table-wrap"><table class="table">
    <thead><tr><th>Fournisseur</th><th class="num">Franco HT</th><th class="num">Port HT</th><th class="num">Délai</th><th class="num">Produits</th><th></th></tr></thead>
    <tbody><?php foreach ($suppliers as $s): ?>
      <tr><td><strong><?= e($s['name']) ?></strong><?= $s['catalog_url'] ? '<br><a class="small" href="' . e($s['catalog_url']) . '" target="_blank" rel="noopener">catalogue</a>' : '' ?></td>
        <td class="num"><?= money($s['min_order_franco_ht']) ?></td><td class="num"><?= money($s['shipping_cost_ht']) ?></td><td class="num"><?= (int)$s['lead_time_days'] ?> j</td><td class="num"><?= (int)$s['products'] ?></td>
        <td class="actions"><a class="btn btn--ghost btn--sm" href="?edit=<?= (int)$s['id'] ?>">Modifier</a></td></tr>
    <?php endforeach; ?><?php if (!$suppliers): ?><tr><td colspan="6" class="muted">Ajoutez votre premier fournisseur, par exemple Agrosemens.</td></tr><?php endif; ?></tbody>
  </table></div>
  <form class="panel" method="post" action="/admin/fournisseurs">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
    <h2><?= $f ? 'Modifier' : 'Nouveau fournisseur' ?></h2>
    <div class="field"><label for="s-name">Nom</label><input id="s-name" name="name" required value="<?= e($f['name'] ?? '') ?>"></div>
    <div class="field"><label for="s-url">Catalogue en ligne</label><input id="s-url" name="catalog_url" type="url" value="<?= e($f['catalog_url'] ?? '') ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="s-franco">Franco de port (€ HT)</label><input id="s-franco" name="min_order_franco_ht" inputmode="decimal" value="<?= e(dec_in($f['min_order_franco_ht'] ?? 0, 2)) ?>"></div>
      <div class="field"><label for="s-port">Frais de port (€ HT)</label><input id="s-port" name="shipping_cost_ht" inputmode="decimal" value="<?= e(dec_in($f['shipping_cost_ht'] ?? 0, 2)) ?>"></div>
      <div class="field"><label for="s-lead">Délai de livraison (jours)</label><input id="s-lead" name="lead_time_days" type="number" value="<?= (int)($f['lead_time_days'] ?? 7) ?>"></div>
      <div class="field"><label for="s-mail">Email commandes</label><input id="s-mail" name="contact_email" type="email" value="<?= e($f['contact_email'] ?? '') ?>"></div>
    </div>
    <div class="field"><label for="s-tel">Téléphone</label><input id="s-tel" name="contact_phone" value="<?= e($f['contact_phone'] ?? '') ?>"></div>
    <div class="field"><label for="s-notes">Notes (n° client, conditions…)</label><textarea id="s-notes" name="notes" style="min-height:70px"><?= e($f['notes'] ?? '') ?></textarea></div>
    <div class="row"><button class="btn" type="submit">Enregistrer</button><?php if ($f): ?><a class="btn btn--ghost" href="/admin/fournisseurs">Annuler</a><button class="btn btn--danger btn--sm" name="delete" value="1" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
