<div class="wrap page">
  <h1>Bonjour <?= e($u['first_name']) ?>, merci d'être là</h1>
  <p class="muted mb-6">Tout ce qu'il faut pour réceptionner, ensacher et distribuer.</p>
  <div class="vol-tiles">
    <a class="vol-tile" href="/benevoles/planning"><b><?= $needs ?></b><span>Planning — <?= $needs ? plural($needs, 'créneau cherche', 'créneaux cherchent') . ' du monde' : 'tous les créneaux sont pourvus' ?></span></a>
    <a class="vol-tile" href="/benevoles/atelier"><b><?= $toPack ?></b><span>Atelier ensachage — <?= plural($toPack, 'campagne à préparer', 'campagnes à préparer') ?></span></a>
    <a class="vol-tile" href="/benevoles/distribution"><b><?= $ready ?></b><span>Distribution — <?= plural($ready, 'commande prête', 'commandes prêtes') ?> à remettre</span></a>
  </div>
  <?php if ($mine): ?>
  <section class="panel mt-6">
    <h2>Mes prochains créneaux</h2>
    <?php foreach ($mine as $s): ?><p><strong><?= e($s['title']) ?></strong><br><?= e(time_range_fr($s['starts_at'], $s['ends_at'])) ?> — <?= e($s['location']) ?></p><?php endforeach; ?>
  </section>
  <?php endif; ?>
</div>
