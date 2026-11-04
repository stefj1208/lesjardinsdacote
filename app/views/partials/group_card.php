<?php $s = $g['sale']; $ps = $g['public_status']; $best = $s['showcase'] ?? null; ?>
<a class="group-card on-photo" href="/groupes/<?= e($g['slug']) ?>" data-reveal style="--i:<?= (int)($i ?? 0) % 3 ?>">
  <span class="card-flag"><?php partial('partials/status', ['s' => $ps]) ?></span>
  <?= ph($g['photo'], 'Jardin à ' . $g['commune'], '', 800) ?>
  <div class="group-card-body">
    <h3><?= e($g['name']) ?></h3>
    <div class="meta"><?= e($g['commune']) ?> (<?= e($g['postal_code']) ?>) · <?= $g['members'] ?> <?= plural($g['members'], 'membre', 'membres') ?></div>
    <?php if ($ps === 'recruiting'): ?>
      <div class="progress-line"><span>Bénévoles</span><strong><?= $g['volunteers'] ?> / <?= (int)$g['volunteers_needed'] ?></strong></div>
      <div class="slots"><?php for ($k = 0; $k < (int)$g['volunteers_needed']; $k++): ?><span class="slot<?= $k < $g['volunteers'] ? ' is-person' : '' ?>" style="--i:<?= $k ?>"></span><?php endfor; ?></div>
    <?php elseif ($ps === 'preopening'): ?>
      <?php if ($s && $s['status'] === 'voting'): ?>
        <div class="progress-line"><span>Vote : <?= $s['voters'] ?> <?= plural($s['voters'], 'votant', 'votants') ?></span><span>jusqu'au <?= e(date_fr($s['vote_ends_at'])) ?></span></div>
        <div class="meter"><span style="--p:<?= $s['vote_ends_at'] ? max(5, min(100, (int)((time() - strtotime($s['vote_starts_at'])) / max(1, strtotime($s['vote_ends_at']) - strtotime($s['vote_starts_at'])) * 100))) : 0 ?>"></span></div>
      <?php else: ?>
        <div class="progress-line"><span>Bénévoles au complet</span><span>Prochain vote en préparation</span></div>
      <?php endif; ?>
    <?php else: $b = $s['batch']; ?>
      <div class="progress-line"><span>Lot n° <?= $b['no'] ?> : <?= $b['fill'] ?> / <?= $b['size'] ?></span><span><?= $b['sold'] ?> <?= plural($b['sold'], 'pack commandé', 'packs commandés') ?></span></div>
      <div class="meter meter--tomato"><span style="--p:<?= $b['pct'] ?>"></span></div>
    <?php endif; ?>
    <?php if ($best && $best['save'] > 0): ?><div class="card-save"><?= $ps === 'open'
      ? count($best['items']) . ' variétés : ' . eur($best['all_in'] ?? $best['price']) . ' frais compris, au lieu de ' . eur($best['market']) . ' (−' . (int)$best['save_pct'] . '&nbsp;%)'
      : 'Pack à ' . eur($best['price']) . ' : environ ' . count($best['items']) . ' variétés, −' . (int)$best['save_pct'] . '&nbsp;% vs prix publics' ?></div><?php endif; ?>
  </div>
</a>
