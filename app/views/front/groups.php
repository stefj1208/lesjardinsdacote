<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow eyebrow--start">Près de chez vous</p>
    <h1>Les groupes inscrits</h1>
    <p class="lede">Chaque groupe porte le nom de sa commune : « Les Jardins d'à Côté de Pins-Justaret », « … de Pinsaguel ». Rejoignez celui de chez vous, ou lancez-le s'il n'existe pas encore.</p>
    <form class="row mt-3" method="get" action="/groupes" role="search" style="max-width:620px">
      <label class="sr-only" for="q">Commune ou code postal</label>
      <input id="q" name="q" value="<?= e($q) ?>" placeholder="Commune ou code postal" class="grow" style="min-width:220px">
      <button class="btn" type="submit">Rechercher</button>
      <?php if ($q !== ''): ?><a class="btn btn--ghost" href="/groupes">Tout afficher</a><?php endif; ?>
    </form>
    <div class="row mt-3" role="group" aria-label="Filtrer par statut">
      <a class="btn btn--sm <?= $st === '' ? '' : 'btn--ghost' ?>" href="?<?= e(http_build_query(['q' => $q ?: null])) ?>">Tous (<?= $counts['all'] ?>)</a>
      <?php foreach (Cat::GROUP_STATUS as $k => $s): ?>
        <a class="btn btn--sm <?= $st === $k ? '' : 'btn--ghost' ?>" href="?<?= e(http_build_query(['q' => $q ?: null, 'statut' => $k])) ?>"><?= e($s['short']) ?> (<?= $counts[$k] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <?php if ($groups): ?>
      <div class="groups-grid"><?php foreach ($groups as $k => $g) partial('partials/group_card', ['g' => $g, 'i' => $k]); ?></div>
    <?php else: ?>
      <div class="empty"><h3><?= $q !== '' ? 'Pas encore de groupe à « ' . e($q) . ' »' : 'Aucun groupe dans cette étape' ?></h3><p>Mairie, entreprise, association ou simple voisin : proposez le groupe de votre commune, nous vous aidons à le lancer.</p><a class="btn" href="/lancer-un-groupe<?= $q !== '' ? '?commune=' . rawurlencode($q) : '' ?>">Lancer le groupe</a></div>
    <?php endif; ?>
    <div class="cta-band on-photo mt-6" data-reveal="zoom">
      <?= ph('commons:George_Washington_University_Community_Garden.jpg|commons:Raised_bed.jpg', '', '', 1600) ?>
      <h2>Votre commune n'a pas encore son groupe ?</h2>
      <p>Une mairie, un CCAS, une entreprise, un jardin partagé ou quelques voisins suffisent pour le proposer.</p>
      <div class="row"><a class="btn btn--light" href="/lancer-un-groupe">Proposer un groupe</a></div>
    </div>
  </div>
</section>
