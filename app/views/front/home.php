<?php
/** @var array $groups  @var ?array $heroPack  @var ?array $heroSale  @var array $catalog  @var array $varieties  @var int $bestPct */
$nbVarieties = count($varieties);
$open = count(array_filter($groups, fn($g) => $g['public_status'] === 'open'));
?>
<section class="hero on-photo">
  <?= ph(setting('hero_photo', Cat::HERO), 'Potager en pleine saison', '', 2000, true) ?>
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow eyebrow--start eyebrow--light rise" style="--i:0">Achats groupés entre voisins</p>
      <h1 class="rise" style="--i:1">Des graines rares <?= $bestPct ? 'jusqu\'à <em>' . $bestPct . '&nbsp;% moins chères</em>' : '<em>moins chères</em>' ?> qu'en jardinerie.</h1>
      <p class="lede rise" style="--i:2">Dans votre commune, les voisins choisissent ensemble les variétés rares de leur pack à <?= eur(setting('default_pack_price', '30')) ?>. On les achète en gros, des bénévoles préparent les sachets, vous les retirez à deux pas de chez vous.</p>
      <form class="hero-search rise" style="--i:3" action="/groupes" method="get" role="search">
        <label class="sr-only" for="hq">Votre commune</label>
        <input id="hq" name="q" placeholder="Votre commune ou code postal" autocomplete="address-level2">
        <button class="btn" type="submit">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></button>
      </form>
      <div class="hero-links rise" style="--i:4"><a href="/lancer-un-groupe">Mairie, entreprise, jardin partagé : lancez votre groupe</a></div>
      <div class="hero-stats rise" style="--i:5">
        <?php if ($bestPct): ?><div><b class="hl" data-count="<?= $bestPct ?>" data-prefix="−" data-suffix=" %">−<?= $bestPct ?> %</b><span>par rapport aux sachets en jardinerie</span></div><?php endif; ?>
        <?php if ($nbVarieties): ?><div><b data-count="<?= $nbVarieties ?>"><?= $nbVarieties ?></b><span>variétés rares et bio au choix</span></div><?php endif; ?>
        <div><b>0&nbsp;€</b><span>débité avant que le lot de 10 soit complet</span></div>
      </div>
    </div>
    <?php if ($heroPack): ?>
      <a class="hero-card rise" style="--i:3;text-decoration:none" href="<?= e($heroPack['link'] ?? '/groupes') ?>" aria-label="Voir <?= e($heroPack['name']) ?>">
        <?= ph($heroPack['photo'], $heroPack['name'], '', 800, true) ?>
        <?php partial('partials/save_badge', ['p' => $heroPack]) ?>
        <div class="hero-card-body">
          <?php if ($heroSale && $heroSale['status'] === 'voting'): ?><span class="status status--leaf" style="margin-bottom:.5rem">Composition en direct</span><?php endif; ?>
          <h3><?= e($heroPack['name']) ?></h3>
          <div class="muted"><?= e($heroPack['summary']) ?></div>
          <div class="mini-items">
            <?php foreach (array_slice($heroPack['items'], 0, 5) as $it) echo ph($it['photo'], '', '', 120); ?>
            <?php if (count($heroPack['items']) > 5): ?><span class="more">+<?= count($heroPack['items']) - 5 ?></span><?php endif; ?>
          </div>
          <?php partial('partials/price', ['p' => $heroPack]) ?>
        </div>
      </a>
    <?php endif; ?>
  </div>
  <span class="hero-scroll" aria-hidden="true"></span>
</section>

<?php if ($varieties): ?>
<div class="ticker" aria-label="Quelques variétés des packs">
  <div class="ticker-track">
    <?php for ($k = 0; $k < 2; $k++): ?><div style="display:flex"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($varieties as $v): ?><span><em><?= e($v['name']) ?></em> <?= e($v['variety']) ?></span><?php endforeach; ?></div><?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="section-head section-head--center" data-reveal>
      <div><p class="eyebrow">Deux façons de participer</p><h2>Vous jardinez, ou vous voulez faire jardiner votre commune ?</h2></div>
    </div>
    <div class="doors">
      <article class="door" data-reveal="left">
        <span class="door-tag">Particuliers</span>
        <?= ph('commons:Tomates_anciennes.jpg|commons:Heirloom_tomatoes_2.jpg', 'Récolte de tomates anciennes', '', 1100) ?>
        <div class="door-body">
          <h3>Je jardine et je veux acheter</h3>
          <ul class="ticks">
            <li>Je rejoins le groupe de ma commune, gratuitement.</li>
            <li>Je choisis les variétés que je veux voir dans le pack.</li>
            <li>Le pack composé par le vote arrive dans mon panier. Rien n'est débité tant que le lot de 10 achats n'est pas complet.</li>
            <li>Je retire mon pack préparé par les bénévoles, près de chez moi, et je paie <strong>bien moins cher qu'en jardinerie</strong>.</li>
          </ul>
          <div class="row mt-1"><a class="btn" href="/groupes">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></a><a class="btn btn--ghost" href="/comment-ca-marche">Tout comprendre</a></div>
        </div>
      </article>
      <article class="door" data-reveal="right">
        <span class="door-tag">Collectivités & entreprises</span>
        <?= ph('commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg', 'Bacs de potager partagé', '', 1100) ?>
        <div class="door-body">
          <h3>J'organise : mairie, entreprise, association, jardin partagé</h3>
          <ul class="ticks">
            <li>Vous proposez « Les jardins d'à côté » de votre commune ou de votre entreprise.</li>
            <li>Vous offrez un lieu de retrait et relayez auprès des habitants ou des salariés.</li>
            <li>Nous fournissons les packs, le site, les paiements et l'organisation des bénévoles.</li>
            <li>Aucun stock, aucune caisse, aucun coût pour vous.</li>
          </ul>
          <div class="row mt-1"><a class="btn btn--navy" href="/lancer-un-groupe">Lancer un groupe <span class="arr" aria-hidden="true">→</span></a></div>
        </div>
      </article>
    </div>
  </div>
</section>

<?php if ($heroPack && $heroPack['save'] > 0): $h = $heroPack; $pctGroup = (int)round($h['price'] / $h['market'] * 100);
  $ex = null; foreach ($catalog as $v) if ($v['unit_cost'] !== null && (!$ex || $v['variety'] === 'Belmonte')) $ex = $v; ?>
<section class="section section--ink">
  <div class="wrap savings">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start eyebrow--light">Ce que vous économisez</p>
      <h2>Les mêmes variétés bio, <?= $h['save_pct'] ?>&nbsp;% moins cher.</h2>
      <p class="lede">Pour semer <?= count($h['items']) ?> variétés, il faut acheter <?= count($h['items']) ?> sachets en jardinerie. En groupe, l'association achète les grands conditionnements du semencier et les bénévoles répartissent les graines : environ 10 par variété, de quoi garnir un potager familial.</p>
      <?php if ($ex && $ex['market_price']): ?><p class="lede" style="font-size:1.05rem">Exemple : <?= e(mb_strtolower($ex['name'])) ?> <?= e($ex['variety']) ?>, <?= eur($ex['purchase_price']) ?> TTC les <?= e(Cat::qty($ex['purchase_qty'], $ex['unit'])) ?> chez le semencier, soit <strong><?= money($ex['unit_cost']) ?> les <?= e($ex['portion']) ?></strong>, contre <?= money($ex['market_price']) ?> le sachet en jardinerie.</p><?php endif; ?>
      <div class="reasons">
        <div class="reason" data-reveal style="--i:1"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4M3 17l9 4 9-4"/></svg></span><div><h4>Achat en gros conditionnement</h4><p>Les graines sont achetées par 100, par 1 000 ou au gramme directement au semencier bio, au tarif professionnel.</p></div></div>
        <div class="reason" data-reveal style="--i:2"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2.5 1.2-4.5 3-5"/></svg></span><div><h4>Préparé par des bénévoles</h4><p>Pas de magasin, pas d'emballage marketing : des sachets simples, étiquetés avec la variété et le lot.</p></div></div>
        <div class="reason" data-reveal style="--i:3"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.5-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 6.5-7 11-7 11z"/></svg></span><div><h4>Rien n'est perdu</h4><p>On commande par lots de 10 achats : pas de stock, pas d'invendus à rattraper sur le prix.</p></div></div>
      </div>
    </div>
    <div class="compare" data-reveal="right">
      <p class="mb-0" style="color:#fff;font:800 1.15rem var(--f-display)"><?= e($h['name']) ?> · <?= count($h['items']) ?> variétés</p>
      <div class="compare-row">
        <div class="row"><span>En jardinerie, sachet par sachet</span><b><?= eur($h['market']) ?></b></div>
        <div class="compare-bar compare-bar--shop"><span style="--p:100"><?= count($h['items']) ?> sachets</span></div>
      </div>
      <div class="compare-row">
        <div class="row"><span>Avec votre groupe</span><b><?= eur($h['price']) ?></b></div>
        <div class="compare-bar compare-bar--group"><span style="--p:<?= $pctGroup ?>">1 pack</span></div>
      </div>
      <div class="compare-total"><b data-count="<?= e(number_format($h['save'], 2, '.', '')) ?>" data-decimals="2" data-suffix=" €"><?= eur($h['save']) ?></b><span>d'économie sur ce pack, soit −<?= $h['save_pct'] ?>&nbsp;%</span></div>
      <p class="source-note" style="color:#93A1B5"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($catalog): $sample = array_slice($catalog, 0, 8); ?>
<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Vous choisissez</p><h2>Vous votez pour les variétés, le pack se compose tout seul</h2><p>Chaque membre coche jusqu'à 10 variétés parmi <?= count($catalog) ?> raretés bio. Les plus demandées entrent dans le pack, dans l'ordre des voix, jusqu'à atteindre son prix. Plus le groupe achète en gros, plus le pack est riche.</p></div>
      <?php if ($heroSale && $heroSale['status'] === 'voting'): ?><a class="btn" href="/groupes/<?= e($heroSale['group_slug']) ?>#voter">Voir un vote en cours <span class="arr" aria-hidden="true">→</span></a><?php else: ?><a class="btn btn--ghost" href="/comment-ca-marche">Comment se passe le vote</a><?php endif; ?>
    </div>
    <div class="vgrid vgrid--show">
      <?php foreach ($sample as $k => $v): ?>
        <div class="vcard" data-reveal style="--i:<?= $k % 4 ?>">
          <span class="vcard-photo"><?= ph($v['photo'], $v['name'] . ' ' . $v['variety'], '', 500) ?></span>
          <span class="vcard-body"><b><?= e($v['variety'] ?: $v['name']) ?></b><small><?= e($v['name']) ?> · <?= e($v['portion']) ?></small><?php if ($v['note']): ?><span class="vnote"><?= e($v['note']) ?></span><?php endif; ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="center mt-3 muted">… et <?= max(0, count($catalog) - count($sample)) ?> autres variétés à découvrir dans votre groupe.</p>
  </div>
</section>
<?php endif; ?>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Comment avance un groupe</p><h2>Trois étapes, affichées en direct</h2><p>Chaque groupe passe par ces trois statuts. Vous voyez à tout moment où en est le vôtre.</p></div>
      <a class="btn btn--ghost" href="/comment-ca-marche">Questions fréquentes</a>
    </div>
    <?php partial('partials/phases') ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Près de chez vous</p><h2>Les groupes déjà inscrits</h2><p><?= count($groups) ?> <?= plural(count($groups), 'groupe local', 'groupes locaux') ?> autour de Toulouse<?= $open ? ', dont ' . $open . ' ' . plural($open, 'avec les ventes ouvertes', 'avec les ventes ouvertes') : '' ?>. Le vôtre n'y est pas ? Lancez-le.</p></div>
      <a class="btn btn--ghost" href="/groupes">Voir tous les groupes</a>
    </div>
    <?php if ($groups): ?>
      <div class="groups-grid"><?php foreach (array_slice($groups, 0, 6) as $k => $g) partial('partials/group_card', ['g' => $g, 'i' => $k]); ?></div>
    <?php else: ?>
      <div class="empty"><h3>Le premier groupe reste à créer</h3><p>Proposez-en un dans votre commune.</p><a class="btn" href="/lancer-un-groupe">Lancer un groupe</a></div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--sky">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Au fil des saisons</p><h2>Quatre univers, un seul principe</h2><p>Les graines ouvrent la saison. Viendront ensuite les plants potagers, les arbres fruitiers et les aromatiques, avec le même vote et les mêmes lots de 10.</p></div></div>
    <div class="cats">
      <?php $k = 0; foreach (Cat::CATEGORIES as $key => $c): ?>
        <div class="cat on-photo" data-reveal style="--i:<?= $k++ ?>"><?= ph($c['photo'], $c['label'], '', 700) ?><div class="cat-body"><?php if ($key === 'seeds'): ?><span class="chip" style="background:var(--sun);color:var(--navy)">En ce moment</span><?php endif; ?><h3><?= e($c['label']) ?></h3><p><?= e($c['intro']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band on-photo" data-reveal="zoom">
      <?= ph('commons:Vegetable_garden_detail.jpg|commons:Vegetable_garden_1.JPG', '', '', 1600) ?>
      <p class="eyebrow eyebrow--start eyebrow--light mb-0">Bénévoles</p>
      <h2>Il manque peut-être deux bénévoles dans votre commune.</h2>
      <p>Une demi-journée pour préparer les packs, une matinée pour les distribuer. C'est tout ce qu'il faut pour que le groupe ouvre ses ventes.</p>
      <div class="row"><a class="btn btn--light" href="/groupes?statut=recruiting">Voir les groupes qui cherchent <span class="arr" aria-hidden="true">→</span></a></div>
    </div>
  </div>
</section>
