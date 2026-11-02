<?php $hasMarket = $p['market'] !== null; ?>
<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/groupes">Groupes</a> / <?= e(Cat::label($p['category'])) ?> / <?= e($p['name']) ?></p>
    <div class="split split--center">
      <div class="pack-photo" style="border-radius:var(--r-photo);overflow:hidden;box-shadow:var(--shadow-lg)" data-reveal="left">
        <?= ph($p['photo'], $p['name'], '', 1400, true) ?>
        <span class="chip"><?= e(Cat::label($p['category'])) ?></span>
        <?php partial('partials/save_badge', ['p' => $p]) ?>
      </div>
      <div data-reveal="right">
        <p class="eyebrow eyebrow--start"><?= e($p['summary']) ?></p>
        <h1 style="font-size:clamp(2.2rem,4vw,3.4rem)"><?= e($p['name']) ?></h1>
        <p class="lede"><?= e($p['tagline']) ?></p>
        <div class="panel mt-3"><?php partial('partials/price', ['p' => $p, 'note' => true]) ?></div>
        <?php if ($groupsWithPack): ?>
          <div class="panel mt-2"><h3>Proposé dans</h3>
            <?php foreach ($groupsWithPack as $gp): ?><p class="mb-0"><a href="/groupes/<?= e($gp['slug']) ?>"><?= e($gp['name']) ?></a> <span class="muted">· <?= e(Cat::SALE_STATUS[$gp['sale_status']]) ?></span></p><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="row mt-3"><a class="btn" href="/groupes">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></a></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:1rem">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Dans le pack</p><h2><?= count($p['items']) ?> <?= plural(count($p['items']), 'variété', 'variétés') ?>, détaillées une par une</h2></div></div>
    <div class="variety-grid">
      <?php foreach ($p['items'] as $k => $it): ?>
        <article class="variety" data-reveal style="--i:<?= $k % 4 ?>">
          <?= ph($it['photo'], $it['name'] . ' ' . $it['variety'], '', 700) ?>
          <div class="variety-body">
            <h3><?= e($it['variety'] ?: $it['name']) ?></h3>
            <div class="meta"><?= e($it['name']) ?><?= $it['latin_name'] ? ' · <em>' . e($it['latin_name']) . '</em>' : '' ?></div>
            <?php if ($it['note']): ?><p><?= e($it['note']) ?></p><?php endif; ?>
            <div class="variety-foot"><span class="chip chip--ok"><?= e(Cat::qty((int)$it['quantity'], $it['unit'])) ?></span><?php if ($it['market_price'] !== null): ?><span class="muted">en jardinerie : <?= eur($it['market_price']) ?></span><?php endif; ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($hasMarket): ?>
    <div class="split mt-6">
      <div data-reveal="left">
        <h2>Combien vous économisez</h2>
        <p>Pour semer ces <?= count($p['items']) ?> variétés, il faudrait acheter un sachet de chacune en jardinerie. Le pack les réunit pour <?= eur($p['price']) ?>.</p>
        <?php if ($p['save'] > 0): ?><div class="stat-grid" style="grid-template-columns:1fr 1fr">
          <div class="stat"><b data-count="<?= e(number_format($p['save'], 2, '.', '')) ?>" data-decimals="2" data-suffix=" €"><?= eur($p['save']) ?></b><span>d'économie</span></div>
          <div class="stat"><b data-count="<?= (int)$p['save_pct'] ?>" data-prefix="−" data-suffix=" %">−<?= (int)$p['save_pct'] ?> %</b><span>par rapport aux sachets du commerce</span></div>
        </div><?php endif; ?>
        <p class="source-note mt-2"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p>
      </div>
      <div class="panel" data-reveal="right">
        <div class="table-wrap"><table class="table value-table">
          <thead><tr><th>Variété</th><th class="num">Dans le pack</th><th class="num">En jardinerie</th></tr></thead>
          <tbody><?php foreach ($p['items'] as $it): ?><tr><td><?= e($it['name']) ?> <span class="muted"><?= e($it['variety']) ?></span></td><td class="num"><?= e(Cat::qty((int)$it['quantity'], $it['unit'])) ?></td><td class="num"><?= $it['market_price'] !== null ? eur($it['market_price']) : '—' ?></td></tr><?php endforeach; ?></tbody>
          <tfoot>
            <tr><td colspan="2">Total en jardinerie</td><td class="num"><?= eur($p['market']) ?></td></tr>
            <tr><td colspan="2">Le pack, avec votre groupe</td><td class="num" style="color:var(--green-dd)"><?= eur($p['price']) ?></td></tr>
          </tfoot>
        </table></div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($p['description']): ?><div class="prose mt-6" data-reveal><h2>À propos de ce pack</h2><?= nl2p($p['description']) ?></div><?php endif; ?>
  </div>
</section>
