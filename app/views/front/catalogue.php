<div class="wrap page">
  <div class="section-head">
    <div>
      <h1 class="mb-0">Campagnes d'achat groupé</h1>
      <p class="mt-4">Rejoignez un bac : plus il se remplit, plus le prix baisse pour tous les participants.</p>
    </div>
  </div>

  <form class="filters" data-filters onsubmit="return false" role="search" aria-label="Filtrer les campagnes">
    <div class="filter-cats" role="group" aria-label="Univers">
      <button type="button" data-cat="" aria-pressed="true">Tout</button>
      <?php foreach (Units::CATEGORIES as $k => $cat): ?>
        <button type="button" data-cat="<?= e($k) ?>" aria-pressed="false"><?= e($cat['label']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="filter-row">
      <div class="field"><label for="f-q">Recherche</label><input id="f-q" type="search" name="q" placeholder="Tomate, basilic…"></div>
      <div class="field"><label for="f-expo">Exposition</label>
        <select id="f-expo" name="expo"><option value="">Toutes</option><?php foreach (Units::EXPOSURE as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="f-month">Semis en</label>
        <select id="f-month" name="month"><option value="">Tous les mois</option><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>"<?= $m === (int)date('n') ? '' : '' ?>><?= ucfirst(FR_MONTHS[$m]) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="f-diff">Difficulté</label>
        <select id="f-diff" name="diff"><option value="">Toutes</option><?php foreach (Units::DIFFICULTY as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="f-sort">Trier par</label>
        <select id="f-sort" name="sort">
          <option value="soon">Bientôt validées</option>
          <option value="end">Fin imminente</option>
          <option value="new">Nouveaux arrivages</option>
          <option value="drop">Prix en baisse</option>
        </select></div>
    </div>
  </form>

  <?php if ($campaigns): ?>
    <div class="crates" data-crates><?php foreach ($campaigns as $c) partial('partials/crate', ['c' => $c]); ?></div>
    <div class="empty" data-empty hidden><h3>Aucun bac ne correspond</h3><p>Essayez un autre univers ou retirez un filtre.</p><button class="btn btn--ghost" type="button" data-reset>Effacer les filtres</button></div>
  <?php else: ?>
    <div class="empty"><h3>Aucune campagne ouverte pour l'instant</h3><p>La prochaine se prépare. Créez un compte pour être prévenu à l'ouverture.</p><a class="btn" href="/inscription">Créer mon compte</a></div>
  <?php endif; ?>

  <?php if ($upcoming): ?>
    <h2 class="mt-6" style="margin-top:var(--s-7)">Bientôt en rayon</h2>
    <div class="crates"><?php foreach ($upcoming as $c) partial('partials/crate', ['c' => $c]); ?></div>
  <?php endif; ?>

  <?php if ($recent): ?>
    <h2 style="margin-top:var(--s-7)">Dernières récoltes</h2>
    <p class="muted">Campagnes clôturées avec succès, en cours de préparation par les bénévoles.</p>
    <div class="table-wrap panel"><table class="table">
      <thead><tr><th>Campagne</th><th>Palier atteint</th><th class="num">Prix final</th><th>Étape</th></tr></thead>
      <tbody><?php foreach ($recent as $c): ?>
        <tr><td><a href="/campagne/<?= e($c['slug']) ?>"><?= e($c['title']) ?></a></td><td>Palier <?= $c['state']['tier'] ?: 1 ?> / <?= $c['state']['max_tier'] ?></td>
          <td class="num"><?= money(Pricing::packetPrice((float)$c['current_unit_price'], (float)$c['ref_portion'])) ?> les <?= e(Units::qty($c['ref_portion'], $c['unit'])) ?></td>
          <td><span class="chip chip--ok"><?= e(Units::STATUSES[$c['status']]) ?></span></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</div>
