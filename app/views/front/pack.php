<div class="wrap page">
  <p class="crumbs"><a href="/groupes">Groupes</a> / <?= e(Cat::label($p['category'])) ?> / <?= e($p['name']) ?></p>
  <div class="split">
    <div><?= ph($p['photo'], $p['name'], '', 1400, true) ?></div>
    <div>
      <span class="chip"><?= e(Cat::label($p['category'])) ?></span>
      <h1 class="mt-2" style="font-size:clamp(2.2rem,4vw,3.4rem)"><?= e($p['name']) ?></h1>
      <p class="lede"><?= e($p['tagline']) ?></p>
      <div class="row mt-3"><span class="big-num"><?= money($p['price']) ?></span><span class="muted"><?= e($p['summary']) ?></span></div>
      <?php if ($groupsWithPack): ?>
        <div class="panel mt-4"><h3>Proposé dans</h3>
          <?php foreach ($groupsWithPack as $gp): ?><p class="mb-0"><a href="/groupes/<?= e($gp['slug']) ?>"><?= e($gp['name']) ?></a> <span class="muted">· <?= e(Cat::SALE_STATUS[$gp['sale_status']]) ?></span></p><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <h2 class="mt-6">Ce que contient le pack</h2>
  <div class="groups-grid">
    <?php foreach ($p['items'] as $it): ?>
      <div class="group-card" style="grid-template-rows:220px auto">
        <?= ph($it['photo'], $it['name'] . ' ' . $it['variety'], '', 800) ?>
        <div class="group-card-body">
          <div class="row row--between"><h3><?= e($it['name']) ?></h3><span class="chip chip--ok"><?= e(Cat::qty((int)$it['quantity'], $it['unit'])) ?></span></div>
          <div class="meta"><?= e($it['variety']) ?><?= $it['latin_name'] ? ' · <em>' . e($it['latin_name']) . '</em>' : '' ?></div>
          <?php if ($it['note']): ?><p class="mb-0 small"><?= e($it['note']) ?></p><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($p['description']): ?><div class="prose mt-6"><?= nl2p($p['description']) ?></div><?php endif; ?>
</div>
