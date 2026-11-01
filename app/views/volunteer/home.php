<div class="wrap page">
  <h1>Merci <?= e($u['first_name']) ?>, voici ce qui vous attend</h1>
  <div class="vol-tiles mt-3">
    <a class="vol-tile" href="/benevoles/distribution"><b><?= $ready ?></b><span>Distribution — <?= plural($ready, 'pack prêt', 'packs prêts') ?> à remettre</span></a>
    <a class="vol-tile" href="/benevoles/planning"><b><?= count($mine) ?></b><span>Planning — mes prochains créneaux</span></a>
  </div>
  <h2 class="mt-6">Préparation des packs</h2>
  <?php if (!$sales): ?><div class="empty"><h3>Rien à préparer pour l'instant</h3><p>Les packs apparaissent ici dès qu'un lot de 10 achats est complet dans l'un de vos groupes.</p></div><?php endif; ?>
  <?php foreach ($sales as $s): ?>
    <a class="order-card" href="/benevoles/preparation/<?= (int)$s['id'] ?>" style="grid-template-columns:72px 1fr auto">
      <?= ph($s['winner']['photo'] ?? null, '', '', 300) ?>
      <div>
        <div class="ref"><?= e($s['group_name']) ?> <span class="chip"><?= e(Cat::SALE_STATUS[$s['status']]) ?></span></div>
        <div class="small muted"><?= e($s['winner']['name'] ?? $s['title']) ?> · <?= $s['batch']['complete'] ?> <?= plural($s['batch']['complete'], 'lot complet', 'lots complets') ?> · lot en cours <?= $s['batch']['fill'] ?>/<?= $s['batch']['size'] ?></div>
      </div>
      <div style="text-align:right"><strong><?= $s['to_prepare'] ?></strong> à préparer<br><span class="small muted"><?= $s['ready'] ?> prêts · <?= $s['collected'] ?> remis</span></div>
    </a>
  <?php endforeach; ?>
</div>
