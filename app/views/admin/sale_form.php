<?php $dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; $sel = array_map(fn($v) => (int)$v['id'], $s['varieties']); $locked = !in_array($s['status'], ['waiting', 'voting'], true);
$isNew = !$s['id']; $pj = $s['projection']; ?>
<div class="admin-head">
  <h1><?= $s['id'] ? e($s['group_name']) : 'Nouvelle vente' ?></h1>
  <?php if ($s['id']): ?><span class="chip chip--dark"><?= e(Cat::SALE_STATUS[$s['status']]) ?></span><?php endif; ?>
</div>
<?php if ($s['id']): $act = fn($a, $l, $cls = 'btn--ghost', $c = '') => '<form method="post" action="/admin/ventes/' . (int)$s['id'] . '/action"' . ($c ? ' data-confirm="' . e($c) . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><button class="btn btn--sm ' . $cls . '">' . e($l) . '</button></form>'; ?>
<section class="panel mb-4" style="margin-bottom:1.5rem">
  <?php if ($s['status'] === 'waiting'): ?><p>En attente : le vote s'ouvrira automatiquement quand le groupe aura <?= (int)$s['volunteers_needed'] ?> bénévoles.</p><?php endif; ?>
  <?php if ($s['status'] === 'voting'): ?><p><strong><?= $s['voters'] ?> <?= plural($s['voters'], 'votant', 'votants') ?></strong> · vote ouvert jusqu'au <?= e(date_fr($s['vote_ends_at'], true, true)) ?>. À cette date, le pack est composé avec les variétés les plus demandées et part dans le panier des votants.</p><?php endif; ?>
  <?php if ($pj): ?><p class="mb-2"><strong>Composition actuelle :</strong> <?= count($pj['items']) ?> variétés · coût <?= money($pj['cost']) ?> pour un prix de <?= money($pj['price']) ?> · valeur jardinerie <?= money((float)$pj['market']) ?>.<br><span class="small muted"><?= e(implode(', ', array_map(fn($it) => ($it['variety'] ?: $it['name']) . ' (' . $it['votes'] . ')', $pj['items']))) ?></span></p><?php endif; ?>
  <?php if ($s['status'] === 'open'): $b = $s['batch']; ?><p>Pack en vente : <strong><?= e($s['winner']['name']) ?></strong> · lot n° <?= $b['no'] ?> : <?= $b['fill'] ?>/<?= $b['size'] ?> · <?= $b['complete'] ?> lot(s) complet(s), <?= $b['sold'] ?> packs commandés · fin le <?= e(date_fr($s['sale_ends_at'], true)) ?>.</p><?php endif; ?>
  <div class="row">
    <?= $s['status'] === 'waiting' ? $act('start_vote', 'Ouvrir le vote maintenant', '', 'Ouvrir le vote sans attendre les bénévoles ?') : '' ?>
    <?= in_array($s['status'], ['waiting', 'voting'], true) ? $act('open', 'Clore le vote et ouvrir les ventes', 'btn--accent', 'Clore le vote maintenant ? Le pack sera composé avec les variétés les plus demandées et mis en vente.') : '' ?>
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
      <div class="field"><label for="price">Prix du pack (€)</label><input id="price" name="price" inputmode="decimal" value="<?= e(number_format((float)$s['price'], 2, ',', '')) ?>"<?= $locked ? ' readonly' : '' ?>><span class="hint">Le pack se remplit jusqu'à ce prix.</span></div>
      <div class="field"><label for="max_choices">Choix par membre</label><input id="max_choices" name="max_choices" type="number" min="1" max="50" value="<?= (int)$s['max_choices'] ?>"><span class="hint">Nombre de variétés que chacun peut cocher.</span></div>
      <div class="field"><label for="max_varieties">Variétés max. dans le pack</label><input id="max_varieties" name="max_varieties" type="number" min="1" value="<?= e((string)($s['max_varieties'] ?? '')) ?>" placeholder="sans limite"<?= $locked ? ' readonly' : '' ?>></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="pickup_at">Date de distribution</label><input id="pickup_at" name="pickup_at" type="datetime-local" value="<?= $dt($s['pickup_at']) ?>"></div>
      <div class="field"><label for="pickup_place">Lieu (si différent du groupe)</label><input id="pickup_place" name="pickup_place" value="<?= e($s['id'] ? (db()->val('SELECT pickup_place FROM sales WHERE id = ?', [$s['id']]) ?? '') : '') ?>"></div>
      <div class="field"><label for="pickup_address">Adresse</label><input id="pickup_address" name="pickup_address" value="<?= e($s['id'] ? (db()->val('SELECT pickup_address FROM sales WHERE id = ?', [$s['id']]) ?? '') : '') ?>"></div>
    </div>
  </section>
  <section class="panel">
    <h2>Variétés soumises au vote</h2>
    <?php if ($locked): ?><p class="muted">Le vote est terminé : la liste ne peut plus être modifiée.</p><?php endif; ?>
    <?php $byCat = []; foreach ($catalog as $v) $byCat[$v['category']][$v['family']][] = $v; ?>
    <?php foreach ($byCat as $cat => $fams): ?>
      <fieldset class="vadmin" data-cat="<?= e($cat) ?>">
        <legend><?= e(Cat::label($cat)) ?></legend>
        <?php foreach ($fams as $fam => $list): ?>
          <div class="vadmin-fam"><strong><?= e($fam) ?></strong>
            <?php foreach ($list as $v): ?><label class="check"><input type="checkbox" name="varieties[]" value="<?= (int)$v['id'] ?>"<?= ($isNew ? $cat === $s['category'] : in_array((int)$v['id'], $sel, true)) ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>> <?= e($v['variety'] ?: $v['name']) ?> <span class="muted small"><?= $v['unit_cost'] !== null ? money($v['unit_cost']) : 'prix ?' ?></span></label><?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </fieldset>
    <?php endforeach; ?>
    <?php if ($locked): foreach ($sel as $vid): ?><input type="hidden" name="varieties[]" value="<?= $vid ?>"><?php endforeach; endif; ?>
    <p class="small muted mt-2 mb-0">Proposez plus de variétés que le prix n'en permet : le vote décide alors lesquelles entrent dans le pack. <a href="/admin/varietes/nouvelle">Ajouter une variété au catalogue</a></p>
  </section>
  <button class="btn btn--lg mt-3" type="submit">Enregistrer la vente</button>
</form>
