<?php
/** @var array $u  @var array $others  @var array $groups */
$ex = $u['example'];
$list = $u['varieties'];
$fams = Varieties::families($list);
$inPack = $ex ? array_flip(array_map(fn($it) => (int)$it['variety_id'], $ex['items'])) : [];
?>
<section class="page-hero">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="crumbs"><a href="/univers">Univers</a> / <?= e($u['label']) ?></p>
      <p class="eyebrow eyebrow--start"><?= e($u['season']) ?></p>
      <h1><?= e($u['label']) ?></h1>
      <p class="lede"><?= e($u['intro']) ?></p>
      <?php if ($ex && $ex['save']): ?>
        <div class="stat-grid stat-grid--3 mt-3">
          <div class="stat"><b><?= eur($ex['price']) ?></b><span>le pack</span></div>
          <div class="stat"><b class="muted" style="text-decoration:line-through;text-decoration-color:var(--tomato)"><?= eur($ex['market']) ?></b><span>en jardinerie</span></div>
          <div class="stat"><b data-count="<?= (int)$ex['save_pct'] ?>" data-prefix="−" data-suffix=" %">−<?= (int)$ex['save_pct'] ?> %</b><span>d'économie</span></div>
        </div>
      <?php endif; ?>
      <div class="row mt-3"><a class="btn" href="<?= $u['sale'] ? '/groupes/' . e($u['sale']['group_slug']) : '/groupes' ?>"><?= $u['sale'] ? 'Voir la vente en cours' : 'Trouver mon groupe' ?> <span class="arr" aria-hidden="true">→</span></a><a class="btn btn--ghost" href="/lancer-un-groupe">Lancer un groupe</a></div>
    </div>
    <div class="photo-stack reveal-img" data-reveal="right"><?= ph($u['photo'], $u['label'], '', 1400, true) ?></div>
  </div>
</section>

<?php if ($list): ?>
<section class="section" style="padding-top:1rem">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <div><p class="eyebrow eyebrow--start">Le catalogue</p><h2><?= count($list) ?> <?= $u['key'] === 'fruit_trees' ? 'arbres' : 'variétés' ?> proposées au vote</h2>
        <p>Les membres d'un groupe cochent leurs préférées ; les plus demandées entrent dans le pack jusqu'à son prix<?= $ex ? ' (' . eur($ex['price']) . ')' : '' ?>. Ci-dessous, en vert, un exemple de composition si chacun avait voté de la même façon.</p></div>
    </div>
    <?php if (count($fams) > 1): ?>
      <div class="vfilter" role="group" aria-label="Filtrer" data-vfilter>
        <button type="button" class="chip is-on" data-fam="">Toutes (<?= count($list) ?>)</button>
        <?php foreach ($fams as $f => $n): ?><button type="button" class="chip" data-fam="<?= e($f) ?>"><?= e($f) ?> (<?= $n ?>)</button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="vgrid" data-vgrid>
      <?php foreach ($list as $k => $v): $inside = isset($inPack[(int)$v['id']]); ?>
        <div class="vcard<?= $inside ? ' is-on' : '' ?>" data-family="<?= e($v['family']) ?>" data-reveal style="--i:<?= $k % 4 ?>;cursor:default">
          <span class="vcard-photo"><?= ph($v['photo'], $v['name'] . ' ' . $v['variety'], '', 500) ?></span>
          <span class="vcard-body">
            <b><?= e($v['variety'] ?: $v['name']) ?></b>
            <small><?= e($v['name']) ?> · <?= e($v['portion']) ?></small>
            <?php if ($v['note']): ?><span class="vnote"><?= e($v['note']) ?></span><?php endif; ?>
            <span class="vfoot"><span class="vin<?= $inside ? ' is-in' : '' ?>"><?= $inside ? 'Dans l\'exemple' : 'Au choix' ?></span><?php if ($v['market_price'] !== null): ?><span class="muted">jardinerie : <?= eur($v['market_price']) ?></span><?php endif; ?></span>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="source-note mt-3">Prix en jardinerie et prix d'achat relevés en novembre 2026 : <?= e(implode(' ; ', array_unique(array_filter(array_map(fn($v) => $v['supplier'], $list))))) ?>. Prix indicatifs, mis à jour par l'association avant chaque vente.</p>
  </div>
</section>
<?php else: ?>
<section class="section" style="padding-top:1rem"><div class="wrap"><div class="empty"><h3>Catalogue en préparation</h3><p>L'association sélectionne en ce moment les producteurs de cet univers. Rejoignez un groupe pour être prévenu·e de la première vente.</p><a class="btn" href="/groupes">Trouver mon groupe</a></div></div></section>
<?php endif; ?>

<?php if ($groups): ?>
<section class="section section--sky">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">En ce moment</p><h2>Les groupes qui achètent des <?= e(mb_strtolower($u['label'])) ?></h2></div></div>
    <div class="groups-grid"><?php foreach ($groups as $k => $g) partial('partials/group_card', ['g' => $g, 'i' => $k]); ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Les autres univers</p><h2>Et aux autres saisons</h2></div><a class="btn btn--ghost" href="/univers">Tous les univers</a></div>
    <div class="ugrid"><?php $k = 0; foreach (array_slice($others, 0, 3) as $o) partial('partials/ucard', ['u' => $o, 'i' => $k++]); ?></div>
  </div>
</section>
