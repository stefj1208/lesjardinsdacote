<?php $dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; $sel = array_map(fn($p) => (int)$p['id'], $s['packs']); $locked = !in_array($s['status'], ['waiting', 'voting'], true); ?>
<div class="admin-head">
  <h1><?= $s['id'] ? e($s['group_name']) : 'Nouvelle vente' ?></h1>
  <?php if ($s['id']): ?><span class="chip chip--dark"><?= e(Cat::SALE_STATUS[$s['status']]) ?></span><?php endif; ?>
</div>
<?php if ($s['id']): $act = fn($a, $l, $cls = 'btn--ghost', $c = '') => '<form method="post" action="/admin/ventes/' . (int)$s['id'] . '/action"' . ($c ? ' data-confirm="' . e($c) . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><button class="btn btn--sm ' . $cls . '">' . e($l) . '</button></form>'; ?>
<section class="panel mb-4" style="margin-bottom:1.5rem">
  <?php if ($s['status'] === 'waiting'): ?><p>En attente : le vote s'ouvrira automatiquement quand le groupe aura <?= (int)$s['volunteers_needed'] ?> bénévoles.</p><?php endif; ?>
  <?php if ($s['status'] === 'voting'): ?><p><strong><?= $s['votes_total'] ?> voix</strong> · vote ouvert jusqu'au <?= e(date_fr($s['vote_ends_at'], true, true)) ?>. À cette date, le pack gagnant passe en vente et part dans le panier des votants.</p><?php endif; ?>
  <?php if ($s['status'] === 'open'): $b = $s['batch']; ?><p>Pack en vente : <strong><?= e($s['winner']['name']) ?></strong> · lot n° <?= $b['no'] ?> : <?= $b['fill'] ?>/<?= $b['size'] ?> · <?= $b['complete'] ?> lot(s) complet(s), <?= $b['sold'] ?> packs commandés · fin le <?= e(date_fr($s['sale_ends_at'], true)) ?>.</p><?php endif; ?>
  <div class="row">
    <?= $s['status'] === 'waiting' ? $act('start_vote', 'Ouvrir le vote maintenant', '', 'Ouvrir le vote sans attendre les bénévoles ?') : '' ?>
    <?= in_array($s['status'], ['waiting', 'voting'], true) ? $act('open', 'Clore le vote et ouvrir les ventes', 'btn--accent', 'Clore le vote maintenant ? Le pack en tête sera mis en vente.') : '' ?>
    <?= in_array($s['status'], ['voting', 'open'], true) ? $act('extend', $s['status'] === 'voting' ? 'Prolonger le vote de 3 jours' : 'Prolonger la vente de 7 jours') : '' ?>
    <?= $s['status'] === 'open' ? $act('close', 'Clôturer la vente', 'btn--danger', 'Clôturer ? Le lot en cours, s\'il est incomplet, sera annulé sans débit.') : '' ?>
    <?= in_array($s['status'], ['open', 'closed'], true) ? '<a class="btn btn--ghost btn--sm" href="/benevoles/preparation/' . (int)$s['id'] . '">Préparation</a><a class="btn btn--ghost btn--sm" href="/admin/ventes/' . (int)$s['id'] . '/acheteurs.csv">Acheteurs (CSV)</a>' : '' ?>
    <?= in_array($s['status'], ['waiting', 'voting', 'open'], true) ? $act('cancel', 'Annuler la vente', 'btn--danger', 'Annuler cette vente ? Les achats en attente sont annulés sans débit.') : '' ?>
    <?= $act('delete', 'Supprimer', 'btn--danger', 'Supprimer cette vente ?') ?>
  </div>
</section>
<?php endif; ?>
<form method="post" action="/admin/ventes/enregistrer">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
  <section class="panel">
    <div class="grid-3">
      <div class="field"><label for="group_id">Groupe</label><select id="group_id" name="group_id" required><option value="">—</option><?php foreach ($groups as $g): ?><option value="<?= (int)$g['id'] ?>"<?= (int)$g['id'] === (int)$s['group_id'] ? ' selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="category">Univers</label><select id="category" name="category"><?php foreach (Cat::CATEGORIES as $k => $c): ?><option value="<?= $k ?>"<?= $s['category'] === $k ? ' selected' : '' ?>><?= e($c['label']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="title">Titre</label><input id="title" name="title" required value="<?= e($s['title']) ?>"></div>
    </div>
    <div class="grid-4">
      <div class="field"><label for="vote_days">Durée du vote (jours)</label><input id="vote_days" name="vote_days" type="number" min="1" value="<?= (int)$s['vote_days'] ?>"></div>
      <div class="field"><label for="sale_days">Durée de la vente (jours)</label><input id="sale_days" name="sale_days" type="number" min="1" value="<?= (int)$s['sale_days'] ?>"></div>
      <div class="field"><label for="batch_size">Taille d'un lot</label><input id="batch_size" name="batch_size" type="number" min="1" value="<?= (int)$s['batch_size'] ?>"></div>
      <div class="field"><label for="max_per_order">Packs max. par personne</label><input id="max_per_order" name="max_per_order" type="number" min="1" max="10" value="<?= (int)$s['max_per_order'] ?>"></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="pickup_at">Date de distribution</label><input id="pickup_at" name="pickup_at" type="datetime-local" value="<?= $dt($s['pickup_at']) ?>"></div>
      <div class="field"><label for="pickup_place">Lieu (si différent du groupe)</label><input id="pickup_place" name="pickup_place" value="<?= e($s['id'] ? (db()->val('SELECT pickup_place FROM sales WHERE id = ?', [$s['id']]) ?? '') : '') ?>"></div>
      <div class="field"><label for="pickup_address">Adresse</label><input id="pickup_address" name="pickup_address" value="<?= e($s['id'] ? (db()->val('SELECT pickup_address FROM sales WHERE id = ?', [$s['id']]) ?? '') : '') ?>"></div>
    </div>
  </section>
  <section class="panel">
    <h2>Les 3 packs soumis au vote</h2>
    <?php if ($locked): ?><p class="muted">Le vote est terminé : les packs ne peuvent plus être modifiés.</p><?php endif; ?>
    <div class="pack-picker">
      <?php foreach ($catalog as $p): ?>
        <label><input type="checkbox" name="packs[]" value="<?= (int)$p['id'] ?>"<?= in_array((int)$p['id'], $sel, true) ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>><?= ph($p['photo'], '', '', 200) ?><span><strong><?= e($p['name']) ?></strong><br><small class="muted"><?= e(Cat::label($p['category'])) ?> · <?= money($p['price']) ?></small></span></label>
      <?php endforeach; ?>
    </div>
    <?php if ($locked): foreach ($sel as $pid): ?><input type="hidden" name="packs[]" value="<?= $pid ?>"><?php endforeach; endif; ?>
    <p class="small muted mt-2 mb-0">Cochez 3 packs (2 minimum). <a href="/admin/packs/nouveau">Créer un pack</a></p>
  </section>
  <button class="btn btn--lg mt-3" type="submit">Enregistrer la vente</button>
</form>
