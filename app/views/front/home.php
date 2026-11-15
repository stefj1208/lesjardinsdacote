<?php
/** @var array $groups  @var ?array $heroPack  @var ?array $heroSale  @var array $varieties  @var array $univers  @var int $bestPct */
$open = count(array_filter($groups, fn($g) => $g['public_status'] === 'open'));
$words = function (string $text, int $start = 0): string {
    $out = []; $i = $start;
    foreach (preg_split('/\s+/u', trim($text)) as $w) $out[] = '<span class="w"><span style="--i:' . $i++ . '">' . $w . '</span></span>';
    return implode(' ', $out);
};
$byRef = [];
foreach ($catalog as $v) if ($v['supplier_ref']) $byRef[$v['supplier_ref']] = $v;
$show = array_values(array_filter(array_map(fn($r) => $byRef[$r] ?? null, Season::SHOWCASE)));
if (count($show) < 4) $show = array_slice($catalog, 0, 12);
$tiles = array_slice($show, 0, 4);
$soon = array_filter($univers, fn($u) => !$u['live']);
?>
<section class="h5">
  <div class="wrap h5-grid">
    <div class="h5-text">
      <p class="eyebrow eyebrow--start rise" style="--i:0">Association loi 1901 · graines bio achetées entre voisins</p>
      <h1 class="h5-title"><?= $words('Des graines bio, choisies et achetées') ?> <em><?= $words('avec vos voisins.', 5) ?></em></h1>
      <p class="lede rise" style="--i:6">Cette saison, <?= count($catalog) ?> variétés anciennes et reproductibles sont au vote : tomates de collection, courges, fleurs pour les abeilles. Votre commune choisit, l'association achète en votre nom <strong>au prix coûtant</strong>, des bénévoles préparent les sachets. Seuls <?= eur(Asso::fee()) ?> de frais par commande, dont <?= eur(Asso::fundShare()) ?> pour planter des arbres.</p>
      <form class="hero-search rise" style="--i:7" action="/groupes" method="get" role="search">
        <label class="sr-only" for="hq">Votre commune</label>
        <input id="hq" name="q" placeholder="Votre commune ou code postal" autocomplete="address-level2">
        <button class="btn" type="submit">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></button>
      </form>
      <p class="neighbours rise" style="--i:8"><span class="nb-faces" aria-hidden="true"><i></i><i></i><i></i></span><span>Déjà <b data-count="<?= (int)$neighbours ?>"><?= (int)$neighbours ?></b> <?= plural((int)$neighbours, 'voisin inscrit', 'voisins inscrits') ?> dans <?= count($groups) ?> <?= plural(count($groups), 'commune', 'communes') ?></span></p>
      <ol class="steps3 rise" style="--i:9" aria-label="Comment ça marche, en trois étapes">
        <li><b>1</b> Je rejoins le groupe de ma commune</li>
        <li><b>2</b> On vote les variétés, l'association achète au prix coûtant</li>
        <li><b>3</b> Je retire mon pack près de chez moi</li>
      </ol>
      <p class="eco-badges rise" style="--i:10"><span>100 % bio</span><span>Variétés reproductibles</span><span>Zéro livraison à domicile</span><span>1 € = des arbres plantés</span></p>
    </div>
    <div class="h5-bento" aria-hidden="true">
      <?php $n = 0; foreach ($tiles as $v): $n++; ?>
        <a class="tile tile--<?= $n ?> reveal-img" href="/univers/graines" tabindex="-1" style="--i:<?= $n ?>" data-speed="<?= [1 => '-0.04', 2 => '0.05', 3 => '-0.02', 4 => '0.06'][$n] ?>">
          <?= ph($v['photo'], '', '', 900, $n <= 2) ?>
          <span class="tile-label"><?= e($v['variety']) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if ($heroPack): ?>
        <div class="float-card rise" style="--i:9">
          <span class="status status--<?= $heroSale && $heroSale['status'] === 'voting' ? 'leaf' : 'tomato' ?>"><?= $heroSale && $heroSale['status'] === 'voting' ? 'Vote en direct' : 'En vente' ?></span>
          <strong><?= e($heroPack['name']) ?></strong>
          <span class="muted small"><?= e($heroPack['summary']) ?></span>
          <span class="fc-price"><?= eur($heroPack['price']) ?><?php if ($heroPack['save']): ?> <s><?= eur($heroPack['market']) ?></s><?php endif; ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($varieties): ?>
<div class="ticker" aria-label="Quelques plantes proposées">
  <div class="ticker-track">
    <?php for ($k = 0; $k < 2; $k++): ?><div style="display:flex"<?= $k ? ' aria-hidden="true"' : '' ?>><?php foreach ($varieties as $v): ?><span><em><?= e($v['name']) ?></em> <?= e($v['variety']) ?></span><?php endforeach; ?></div><?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<section class="section" id="univers">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">La saison des graines</p><h2><?= count($catalog) ?> variétés au vote, toutes en photo</h2>
        <p>Des variétés anciennes, bio et reproductibles : vous pourrez récolter vos propres graines et les ressemer l'an prochain. Dans chaque groupe, les plus demandées entrent dans le pack.</p></div>
      <a class="btn btn--ghost" href="/univers/graines">Voir tout le catalogue <span class="arr" aria-hidden="true">→</span></a>
    </div>
    <div class="showcase">
      <?php foreach ($show as $k => $v): ?>
        <a class="show-card" href="/univers/graines" data-reveal style="--i:<?= $k % 4 ?>">
          <?= ph($v['photo'], $v['name'] . ' ' . $v['variety'], '', 600) ?>
          <span><b><?= e($v['variety']) ?></b><small><?= e($v['name']) ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if ($soon): ?>
      <p class="soon-line" data-reveal><b>Et ensuite ?</b> <?php foreach ($soon as $u): ?><span class="chip"><?= e($u['label']) ?></span><?php endforeach; ?> <span class="muted">arriveront aux saisons suivantes, quand les premiers groupes seront lancés.</span></p>
    <?php endif; ?>
  </div>
</section>

<section class="section eco">
  <div class="wrap">
    <div class="section-head section-head--center" data-reveal>
      <div><p class="eyebrow">Un potager qui fait du bien au vivant</p><h2>Six raisons écologiques de semer avec ses voisins</h2></div>
    </div>
    <div class="eco-grid">
      <article class="eco-card" data-reveal style="--i:0"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 22V11"/><path d="M12 14c-4.5 0-7-2.6-7-6.5 4 0 7 2.6 7 6.5z"/><path d="M12 11c0-4.6 2.8-7.5 7.5-7.5 0 4.6-2.8 7.5-7.5 7.5z"/></svg></span>
        <h3>Des semences 100 % bio</h3><p>Toutes les graines sont certifiées agriculture biologique : produites sans pesticides ni engrais de synthèse, et jamais enrobées.</p></article>
      <article class="eco-card" data-reveal style="--i:1"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 0113.7-5.7L20 8"/><path d="M20 4v4h-4"/><path d="M20 12a8 8 0 01-13.7 5.7L4 16"/><path d="M4 20v-4h4"/></svg></span>
        <h3>Des variétés que l'on ressème</h3><p>Aucun hybride F1 : ces variétés sont reproductibles. Gardez quelques graines de vos plus belles récoltes, vous n'aurez plus à en racheter.</p></article>
      <article class="eco-card" data-reveal style="--i:2"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 3c2 2 2 4 0 6M12 21c-2-2-2-4 0-6M3 12c2-2 4-2 6 0M21 12c-2 2-4 2-6 0M5.6 5.6c2.8 0 4.2 1.400 4.2 4.200M18.400 18.400c-2.800 0-4.200-1.400-4.200-4.200M18.400 5.600c0 2.800-1.400 4.200-4.200 4.200M5.600 18.400c0-2.800 1.400-4.200 4.200-4.200"/></svg></span>
        <h3>La biodiversité cultivée</h3><p>Noire de Crimée, Petit gris de Rennes, Musquée de Provence : chaque variété ancienne semée dans un jardin est une variété qui ne disparaît pas.</p></article>
      <article class="eco-card" data-reveal style="--i:3"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><ellipse cx="12" cy="14" rx="4" ry="5.500"/><path d="M8.300 12h7.400M8.300 15.500h7.400M9 9.500C7 6 4.500 6 3.500 8s1 4 4.800 3.500M15 9.500C17 6 19.500 6 20.500 8s-1 4-4.800 3.500M10.500 8.700L9.500 6M13.500 8.700l1-2.700"/></svg></span>
        <h3>Des fleurs pour les pollinisateurs</h3><p>Bourrache, souci, cosmos, capucine : chaque pack peut embarquer des fleurs mellifères qui nourrissent abeilles, papillons et coccinelles.</p></article>
      <article class="eco-card" data-reveal style="--i:4"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg></span>
        <h3>Moins d'emballages, moins de trajets</h3><p>Un grand conditionnement partagé remplace des dizaines de sachets individuels. Une seule livraison par groupe, et chacun retire son pack à pied.</p></article>
      <article class="eco-card" data-reveal style="--i:5"><span class="eco-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 22v-7"/><path d="M12 15a6 6 0 006-6c0-3.300-2.700-6-6-6S6 5.700 6 9a6 6 0 006 6z"/><path d="M12 12l3-3M12 9.500L10 7.500"/></svg></span>
        <h3>Des arbres plantés à chaque commande</h3><p><?= eur(Asso::fundShare()) ?> par commande alimente le fonds plantations de l'association. Les comptes sont publics, à l'euro près.</p></article>
    </div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Comment ça marche</p><h2>Trois étapes, affichées en direct</h2><p>Chaque groupe local passe par ces trois statuts. Vous voyez à tout moment où en est le vôtre.</p></div>
      <a class="btn btn--ghost" href="/comment-ca-marche">Questions fréquentes</a>
    </div>
    <?php partial('partials/phases') ?>
  </div>
</section>

<section class="section asso-band">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start">Une association, pas un magasin</p>
      <h2>Vous payez les graines au prix coûtant. Et vous plantez des arbres.</h2>
      <p class="lede">L'association achète en votre nom, sans marge. Ses frais fixes de <?= eur(Asso::fee()) ?> par commande paient la logistique et alimentent un fonds qui finance des plantations.</p>
      <div class="fund-inline">
        <span class="fund-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M24 44V26"/><path class="grow-leaf" d="M24 30c-7 0-11-4-11-10 6 0 11 4 11 10z"/><path class="grow-leaf grow-leaf--2" d="M24 26c0-8 5-13 13-13 0 8-5 13-13 13z"/></svg></span>
        <div><b data-count="<?= e(number_format($fund, 2, '.', '')) ?>" data-decimals="0" data-suffix=" €"><?= eur($fund) ?></b><span>déjà réunis pour planter des arbres</span></div>
      </div>
      <a class="btn btn--ghost mt-2" href="/association">Nos engagements <span class="arr" aria-hidden="true">→</span></a>
    </div>
    <?php partial('partials/money_split') ?>
  </div>
</section>

<?php if ($heroPack && $heroPack['save'] > 0): $h = $heroPack; $pctGroup = (int)round($h['all_in'] / $h['market'] * 100); ?>
<section class="section section--ink">
  <div class="wrap savings">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start eyebrow--light">Ce que vous économisez</p>
      <h2>Les mêmes graines bio, <?= $h['save_pct'] ?>&nbsp;% moins cher.</h2>
      <p class="lede">Au prix public, chaque sachet se paie au prix de détail. En groupe, l'association achète les grands conditionnements au tarif professionnel, directement chez un semencier bio français, et les bénévoles préparent les sachets.</p>
      <div class="reasons">
        <div class="reason" data-reveal style="--i:1"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4M3 17l9 4 9-4"/></svg></span><div><h4>Achat en gros, au producteur</h4><p>Les graines sont achetées en grands conditionnements chez un semencier bio français, sans intermédiaire.</p></div></div>
        <div class="reason" data-reveal style="--i:2"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2.5 1.2-4.5 3-5"/></svg></span><div><h4>Préparé par des bénévoles</h4><p>Pas de magasin ni d'emballage marketing : le prix paie les graines, pas la vitrine.</p></div></div>
        <div class="reason" data-reveal style="--i:3"><span class="reason-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.5-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 6.5-7 11-7 11z"/></svg></span><div><h4>Rien n'est perdu</h4><p>On commande par lots de 10 achats : pas de stock, pas d'invendus à rattraper sur le prix.</p></div></div>
      </div>
    </div>
    <div class="compare" data-reveal="right">
      <p class="mb-0" style="color:#fff;font:800 1.15rem var(--f-display)"><?= e($h['name']) ?> · <?= e($h['summary']) ?></p>
      <div class="compare-row">
        <div class="row"><span>Prix publics, à l'unité</span><b><?= eur($h['market']) ?></b></div>
        <div class="compare-bar compare-bar--shop"><span style="--p:100"><?= count($h['items']) ?> articles</span></div>
      </div>
      <div class="compare-row">
        <div class="row"><span>Avec votre groupe, frais compris</span><b><?= eur($h['all_in']) ?></b></div>
        <div class="compare-bar compare-bar--group"><span style="--p:<?= max(18, $pctGroup) ?>">1 pack</span></div>
      </div>
      <div class="compare-total"><b data-count="<?= e(number_format($h['save'], 2, '.', '')) ?>" data-decimals="2" data-suffix=" €"><?= eur($h['save']) ?></b><span>d'économie sur ce pack, soit −<?= $h['save_pct'] ?>&nbsp;%</span></div>
      <p class="source-note" style="color:#93A1B5">Prix publics constatés en novembre 2026 pour les mêmes variétés, à l'unité. Comparaison frais de commande compris ; le détail figure sur chaque page d'univers.</p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="section-head section-head--center" data-reveal>
      <div><p class="eyebrow">Deux façons de participer</p><h2>Vous jardinez, ou vous voulez faire jardiner votre commune ?</h2></div>
    </div>
    <div class="doors">
      <article class="door" data-reveal="left">
        <div class="door-photo reveal-img"><?= ph('commons:Seedling_planting.jpg|commons:Dar_cucumber_seedlings.jpg', 'Plantation de jeunes plants', '', 1100) ?></div>
        <div class="door-body">
          <span class="chip chip--ok">Particuliers</span>
          <h3>Je jardine et je veux acheter</h3>
          <ul class="ticks">
            <li>Je rejoins le groupe de ma commune et j'adhère à l'association<?= Asso::membershipFee() > 0 ? '' : ' (adhésion offerte)' ?>.</li>
            <li>Je coche les variétés de graines que je veux voir dans le pack de mon groupe.</li>
            <li>Le pack composé par le vote arrive dans mon panier. Rien n'est débité tant que le lot de 10 achats n'est pas complet.</li>
            <li>Je paie le prix coûtant + <?= eur(Asso::fee()) ?> de frais par commande, et je retire mon pack près de chez moi.</li>
          </ul>
          <div class="row mt-1"><a class="btn" href="/groupes">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></a><a class="btn btn--ghost" href="/comment-ca-marche">Tout comprendre</a></div>
        </div>
      </article>
      <article class="door" data-reveal="right">
        <div class="door-photo reveal-img"><?= ph('commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg', 'Bacs de potager partagé', '', 1100) ?></div>
        <div class="door-body">
          <span class="chip">Collectivités & entreprises</span>
          <h3>J'organise : mairie, CCAS, entreprise, association, jardin partagé</h3>
          <ul class="ticks">
            <li>Vous proposez « Les jardins d'à côté » de votre commune ou de votre entreprise.</li>
            <li>Vous offrez un lieu de retrait et relayez auprès des habitants ou des salariés.</li>
            <li>Nous fournissons les catalogues, le site, les paiements et l'organisation des bénévoles.</li>
            <li>Aucun stock, aucune caisse, aucun coût pour vous.</li>
          </ul>
          <div class="row mt-1"><a class="btn btn--navy" href="/lancer-un-groupe">Lancer un groupe <span class="arr" aria-hidden="true">→</span></a></div>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section section--sky">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Près de chez vous</p><h2>Les groupes déjà inscrits</h2><p><?= count($groups) ?> <?= plural(count($groups), 'groupe local', 'groupes locaux') ?> autour de Toulouse<?= $open ? ', dont ' . $open . ' avec les ventes ouvertes' : '' ?>. Le vôtre n'y est pas ? Lancez-le.</p></div>
      <a class="btn btn--ghost" href="/groupes">Voir tous les groupes</a>
    </div>
    <?php partial('partials/groups_map', ['points' => $mapPoints, 'height' => '380px']) ?>
    <?php if ($groups): ?>
      <div class="groups-grid"><?php foreach (array_slice($groups, 0, 6) as $k => $g) partial('partials/group_card', ['g' => $g, 'i' => $k]); ?></div>
    <?php else: ?>
      <div class="empty"><h3>Le premier groupe reste à créer</h3><p>Proposez-en un dans votre commune.</p><a class="btn" href="/lancer-un-groupe">Lancer un groupe</a></div>
    <?php endif; ?>
  </div>
</section>

<?php partial('partials/testimonials') ?>

<section class="section">
  <div class="wrap">
    <div class="cta-split" data-reveal="zoom">
      <div class="cta-split-text">
        <p class="eyebrow eyebrow--start eyebrow--light">Bénévoles</p>
        <h2>Il manque peut-être deux bénévoles dans votre commune.</h2>
        <p>Une demi-journée pour préparer les packs, une matinée pour les distribuer. C'est tout ce qu'il faut pour que le groupe ouvre ses ventes.</p>
        <div class="row"><a class="btn btn--light" href="/groupes?statut=recruiting">Voir les groupes qui cherchent <span class="arr" aria-hidden="true">→</span></a></div>
      </div>
      <div class="cta-split-photo reveal-img"><?= ph('commons:Vegetable_garden_detail.jpg|commons:Vegetable_garden_1.JPG', 'Potager', '', 1200) ?></div>
    </div>
  </div>
</section>
