<section class="hero">
  <?= ph(setting('hero_photo', Cat::HERO), 'Potager en pleine saison', '', 2000, true) ?>
  <div class="wrap hero-inner">
    <h1>Le potager se prépare entre voisins.</h1>
    <p class="lede">Dans votre commune, un groupe choisit ensemble un pack de graines ou de plants à 30 €. On l'achète en gros, des bénévoles le préparent, vous le retirez près de chez vous.</p>
    <form class="hero-search" action="/groupes" method="get" role="search">
      <label class="sr-only" for="hq">Votre commune</label>
      <input id="hq" name="q" placeholder="Votre commune ou code postal" autocomplete="address-level2">
      <button class="btn" type="submit">Trouver mon groupe</button>
    </form>
    <div class="hero-links"><a href="/lancer-un-groupe">Vous êtes une mairie, une entreprise, un jardin partagé ?</a></div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <div><h2>Deux façons de participer</h2><p>Que vous vouliez simplement remplir votre potager ou créer une dynamique dans votre commune.</p></div>
    </div>
    <div class="doors">
      <article class="door">
        <?= ph('commons:Tomates_anciennes.jpg|commons:Heirloom_tomatoes_2.jpg', 'Récolte de tomates anciennes', '', 1100) ?>
        <div class="door-body">
          <h3>Je jardine et je veux acheter</h3>
          <ul>
            <li>Je rejoins le groupe de ma commune, gratuitement.</li>
            <li>Je vote pour le pack de saison qui me plaît.</li>
            <li>Le pack gagnant arrive dans mon panier : je confirme, rien n'est débité tant que le lot de 10 n'est pas complet.</li>
            <li>Je retire mon pack, préparé par les bénévoles, à côté de chez moi.</li>
          </ul>
          <div class="row mt-1"><a class="btn" href="/groupes">Trouver mon groupe</a><a class="btn btn--ghost" href="/comment-ca-marche">Tout comprendre</a></div>
        </div>
      </article>
      <article class="door">
        <?= ph('commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg', 'Bacs de potager partagé', '', 1100) ?>
        <div class="door-body">
          <h3>J'organise : mairie, entreprise, association, jardin partagé</h3>
          <ul>
            <li>Vous proposez un groupe « Les Jardins d'à Côté » à votre nom ou à celui de votre commune.</li>
            <li>Vous offrez un lieu de retrait et relayez auprès de vos habitants ou salariés.</li>
            <li>Nous fournissons les packs, le site, les paiements et l'organisation des bénévoles.</li>
            <li>Aucun stock, aucune caisse à tenir, aucun coût pour vous.</li>
          </ul>
          <div class="row mt-1"><a class="btn" href="/lancer-un-groupe">Lancer un groupe</a></div>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head">
      <div><h2>Comment avance un groupe</h2><p>Chaque groupe passe par trois étapes. Le statut est affiché en direct sur sa page.</p></div>
      <a class="btn btn--ghost" href="/comment-ca-marche">Questions fréquentes</a>
    </div>
    <?php partial('partials/phases') ?>
  </div>
</section>

<?php if ($packs): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head">
      <div><h2>Les packs graines de ce printemps</h2><p>Trois packs sont soumis au vote dans chaque groupe. Chacun détaille les variétés et le nombre de graines.</p></div>
    </div>
    <div class="packs"><?php foreach ($packs as $p) partial('partials/pack_card', ['p' => $p, 'mode' => 'show', 'maxItems' => 4]); ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head">
      <div><h2>Les groupes déjà inscrits</h2><p><?= count($groups) ?> <?= plural(count($groups), 'groupe local', 'groupes locaux') ?> autour de Toulouse. Le vôtre n'y est pas ? Lancez-le.</p></div>
      <a class="btn btn--ghost" href="/groupes">Voir tous les groupes</a>
    </div>
    <?php if ($groups): ?>
      <div class="groups-grid"><?php foreach (array_slice($groups, 0, 6) as $g) partial('partials/group_card', ['g' => $g]); ?></div>
    <?php else: ?>
      <div class="empty"><h3>Le premier groupe reste à créer</h3><p>Proposez-en un dans votre commune.</p><a class="btn" href="/lancer-un-groupe">Lancer un groupe</a></div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head"><div><h2>Quatre univers au fil des saisons</h2><p>Les graines ouvrent la saison. Viendront ensuite les plants potagers, les arbres fruitiers et les aromatiques, sur le même principe.</p></div></div>
    <div class="cats">
      <?php foreach (Cat::CATEGORIES as $k => $c): ?>
        <div class="cat"><?= ph($c['photo'], $c['label'], '', 700) ?><div class="cat-body"><h3><?= e($c['label']) ?></h3><p><?= e($c['intro']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="cta-band">
      <?= ph('commons:Vegetable_garden_detail.jpg|commons:Vegetable_garden_1.JPG', '', '', 1600) ?>
      <h2>Il manque peut-être deux bénévoles dans votre commune.</h2>
      <p>Une demi-journée pour préparer les packs, une matinée pour les distribuer. C'est tout ce qu'il faut pour que le groupe ouvre ses ventes.</p>
      <div class="row"><a class="btn btn--light" href="/groupes">Voir les groupes qui cherchent</a></div>
    </div>
  </div>
</section>
