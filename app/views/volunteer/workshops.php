<div class="wrap page" style="max-width:900px">
  <p class="crumbs"><a href="/benevoles">Espace bénévoles</a> / Atelier</p>
  <h1>Atelier ensachage</h1>
  <?php if (!$camps): ?><div class="empty"><h3>Rien à ensacher pour l'instant</h3><p>Les campagnes validées apparaîtront ici à leur clôture.</p></div><?php endif; ?>
  <?php foreach ($camps as $c): $pct = $c['total_packets'] ? (int)floor($c['packed_packets'] / $c['total_packets'] * 100) : 0; ?>
    <a class="order-card" href="/benevoles/atelier/<?= (int)$c['id'] ?>">
      <div>
        <div class="ref"><?= e($c['title']) ?> <span class="chip <?= $c['status'] === 'ready_pickup' ? 'chip--ok' : 'chip--warn' ?>"><?= e(Units::STATUSES[$c['status']]) ?></span></div>
        <div class="small muted"><?= $c['total_packets'] ?> sachets · <?= e(implode(', ', array_map(fn($r) => $r['packets'] . ' × ' . Units::qty($r['portion_qty'], $c['unit']), $c['sheet']))) ?></div>
        <div class="gauge gauge--light mt-4" style="--p:<?= $pct ?>;height:8px;max-width:320px"><span></span></div>
      </div>
      <strong><?= $pct ?> %</strong>
    </a>
  <?php endforeach; ?>
</div>
