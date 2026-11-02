<?php $s = $g['sale']; $ps = $g['public_status']; $best = $s && $s['packs'] ? ($s['winner'] ?: array_reduce($s['packs'], fn($c, $p) => !$c || $p['save'] > $c['save'] ? $p : $c)) : null; ?>
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
        <div class="progress-line"><span>Vote : <?= $s['votes_total'] ?> <?= plural($s['votes_total'], 'voix', 'voix') ?></span><span>jusqu'au <?= e(date_fr($s['vote_ends_at'])) ?></span></div>
        <div class="meter"><span style="--p:<?= $s['vote_ends_at'] ? max(5, min(100, (int)((time() - strtotime($s['vote_starts_at'])) / max(1, strtotime($s['vote_ends_at']) - strtotime($s['vote_starts_at'])) * 100))) : 0 ?>"></span></div>
      <?php else: ?>
        <div class="progress-line"><span>Bénévoles au complet</span><span>Prochain vote en préparation</span></div>
      <?php endif; ?>
    <?php else: $b = $s['batch']; ?>
      <div class="progress-line"><span>Lot n° <?= $b['no'] ?> : <?= $b['fill'] ?> / <?= $b['size'] ?></span><span><?= $b['sold'] ?> <?= plural($b['sold'], 'pack commandé', 'packs commandés') ?></span></div>
      <div class="meter meter--tomato"><span style="--p:<?= $b['pct'] ?>"></span></div>
    <?php endif; ?>
    <?php if ($best && $best['save'] > 0): ?><div class="card-save"><?= $ps === 'open'
      ? e($best['name']) . ' : ' . eur($best['price']) . ' au lieu de ' . eur($best['market']) . ' (−' . (int)$best['save_pct'] . '&nbsp;%)'
      : 'Packs à ' . eur($best['price']) . ', jusqu\'à −' . (int)$best['save_pct'] . '&nbsp;% vs jardinerie' ?></div><?php endif; ?>
  </div>
</a>
