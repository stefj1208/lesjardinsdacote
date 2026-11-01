<div class="admin-head">
  <h1>Tableau de bord</h1>
  <form method="post" action="/admin/taches"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Lancer les tâches maintenant</button></form>
</div>
<?php if (setting('demo_data') === '1'): ?><div class="notice mb-2" style="margin-bottom:1rem">Les données de démonstration sont chargées (bandeau visible sur le site). Supprimez-les dans <a href="/admin/parametres">Paramètres</a> avant d'ouvrir au public.</div><?php endif; ?>
<?php if (!$lastCron || strtotime($lastCron) < time() - 3600): ?><div class="flash flash--error">La tâche planifiée ne tourne pas : sans elle, les votes et les ventes ne s'ouvrent ni ne se clôturent automatiquement.</div><?php endif; ?>
<?php if ($k['proposed']): ?><div class="callout mb-2" style="margin-bottom:1rem"><div class="row row--between"><strong><?= $k['proposed'] ?> <?= plural($k['proposed'], 'proposition de groupe à valider', 'propositions de groupe à valider') ?></strong><a class="btn btn--sm" href="/admin/groupes?statut=proposed">Voir</a></div></div><?php endif; ?>
<div class="kpis">
  <div class="kpi"><b><?= $k['groups'] ?></b><span>groupes publiés</span></div>
  <div class="kpi"><b><?= $k['members'] ?></b><span>membres inscrits</span></div>
  <div class="kpi"><b><?= $k['voting'] ?></b><span>votes en cours</span></div>
  <div class="kpi"><b><?= $k['open'] ?></b><span>ventes ouvertes</span></div>
  <div class="kpi"><b><?= $k['pending'] ?></b><span>packs en attente de lot complet</span></div>
  <div class="kpi"><b><?= $k['sold'] ?></b><span>packs commandés</span></div>
  <div class="kpi"><b><?= $k['to_prepare'] ?></b><span>packs à préparer</span></div>
  <div class="kpi"><b><?= money($k['paid'] + $k['to_collect']) ?></b><span>chiffre d'affaires (<?= money($k['to_collect']) ?> à encaisser)</span></div>
</div>
<div class="layout-aside">
  <section class="panel">
    <div class="row row--between"><h2 class="mb-0">Groupes</h2><a class="btn btn--sm" href="/admin/groupes/nouveau">Nouveau groupe</a></div>
    <div class="table-wrap mt-2"><table class="table">
      <thead><tr><th>Groupe</th><th>Statut</th><th>Bénévoles</th><th>Avancement</th></tr></thead>
      <tbody><?php foreach ($groups as $g): $s = $g['sale']; ?>
        <tr><td><a href="/admin/groupes/<?= (int)$g['id'] ?>"><?= e($g['name']) ?></a><br><small class="muted"><?= $g['members'] ?> membres</small></td>
          <td><?php partial('partials/status', ['s' => $g['public_status']]) ?></td>
          <td><?= $g['volunteers'] ?> / <?= (int)$g['volunteers_needed'] ?></td>
          <td class="small"><?php if (!$s): ?><a href="/admin/ventes/nouvelle?groupe=<?= (int)$g['id'] ?>">Créer une vente</a><?php elseif ($s['status'] === 'voting'): ?><?= $s['votes_total'] ?> voix · fin <?= e(date_fr($s['vote_ends_at'])) ?><?php elseif ($s['status'] === 'open'): ?>Lot <?= $s['batch']['no'] ?> : <?= $s['batch']['fill'] ?>/<?= $s['batch']['size'] ?> · <?= $s['batch']['sold'] ?> vendus<?php else: ?>En attente des bénévoles<?php endif; ?></td></tr>
      <?php endforeach; ?><?php if (!$groups): ?><tr><td colspan="4" class="muted">Aucun groupe publié.</td></tr><?php endif; ?></tbody>
    </table></div>
  </section>
  <aside class="stack">
    <div class="panel"><h3>À traiter</h3>
      <p><?= $k['candidates'] ?> <a href="/admin/utilisateurs?filtre=candidats"><?= plural($k['candidates'], 'bénévole à valider', 'bénévoles à valider') ?></a></p>
      <p><?= $k['pay_issues'] ?> <a href="/admin/commandes?paiement=failed"><?= plural($k['pay_issues'], 'paiement à régulariser', 'paiements à régulariser') ?></a></p>
      <p class="mb-0"><?= $k['mail_failed'] ? '<span style="color:var(--red)">' . $k['mail_failed'] . ' emails en échec</span>' : 'Aucun email en échec' ?></p>
    </div>
    <?php if (!stripe_enabled()): ?><div class="panel small">Paiement en ligne désactivé : les achats se règlent au retrait. Ajoutez les clés Stripe dans <code>app/config.php</code> pour l'activer.</div><?php endif; ?>
  </aside>
</div>
