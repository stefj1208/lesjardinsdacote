<?php $s = $g['sale']; $ps = $g['public_status']; ?>
<a class="group-card" href="/groupes/<?= e($g['slug']) ?>">
  <?= ph($g['photo'], 'Jardin à ' . $g['commune'], '', 800) ?>
  <div class="group-card-body">
    <div class="row row--between"><?php partial('partials/status', ['s' => $ps]) ?><span class="meta"><?= e($g['postal_code']) ?></span></div>
    <h3><?= e($g['name']) ?></h3>
    <div class="meta"><?= e(Cat::ORGANIZERS[$g['organizer_type']] ?? '') ?> · <?= $g['members'] ?> <?= plural($g['members'], 'membre', 'membres') ?></div>
    <?php if ($ps === 'recruiting'): ?>
      <div class="progress-line"><span>Bénévoles</span><strong><?= $g['volunteers'] ?> / <?= (int)$g['volunteers_needed'] ?></strong></div>
      <div class="slots"><?php for ($i = 0; $i < (int)$g['volunteers_needed']; $i++): ?><span class="slot<?= $i < $g['volunteers'] ? ' is-person' : '' ?>"></span><?php endfor; ?></div>
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
  </div>
</a>
