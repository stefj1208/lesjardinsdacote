<div class="admin-head">
  <h1>Tableau de bord</h1>
  <form method="post" action="/admin/taches"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Lancer les tâches maintenant</button></form>
</div>
<?php if (setting('demo_data') === '1'): ?><div class="notice mb-6">Les données de démonstration sont chargées. Supprimez-les depuis <a href="/admin/parametres">Paramètres</a> avant d'ouvrir le site au public.</div><?php endif; ?>
<?php if (!$lastCron || strtotime($lastCron) < time() - 3600): ?><div class="flash flash--error mb-6">La tâche planifiée ne tourne pas<?= $lastCron ? ' (dernier passage : ' . e(date_fr($lastCron, true)) . ')' : '' ?> : sans elle, les campagnes ne se clôturent pas et les emails ne partent pas. Voir Paramètres.</div><?php endif; ?>
<?php if (!stripe_enabled()): ?><div class="notice mb-6">Paiement en ligne désactivé : les engagements se font avec règlement au retrait. Ajoutez vos clés Stripe dans <code>app/config.php</code> pour l'activer.</div><?php endif; ?>

<div class="kpis">
  <div class="kpi"><b><?= $k['active'] ?></b><span>campagnes ouvertes</span></div>
  <div class="kpi"><b><?= money($k['committed']) ?></b><span>engagés au prix actuel</span></div>
  <div class="kpi"><b><?= $k['participants'] ?></b><span>participants en cours</span></div>
  <div class="kpi"><b><?= $k['to_pack'] ?></b><span>campagnes à préparer</span></div>
  <div class="kpi"><b><?= $k['ready'] ?></b><span>commandes prêtes au retrait</span></div>
  <div class="kpi"><b><?= money($k['paid']) ?></b><span>encaissés en ligne</span></div>
  <div class="kpi"><b><?= money($k['to_collect']) ?></b><span>à encaisser au retrait</span></div>
  <div class="kpi"><b style="color:<?= $k['pay_issues'] ? 'var(--red)' : 'inherit' ?>"><?= $k['pay_issues'] ?></b><span><a href="/admin/commandes?paiement=failed">paiements à régulariser</a></span></div>
</div>

<?php if ($alerts): ?>
<section class="panel mb-6" style="border:2px solid var(--amber)">
  <h2>À sauver avant la clôture</h2>
  <p class="muted small">Campagnes à plus de 85 % du palier 1 qui se terminent dans moins de 72 h. Vous pouvez les prolonger de 48 h ou les valider en rachetant le reliquat pour le stock de la boutique.</p>
  <?php foreach ($alerts as $c): ?>
    <div class="row row--between" style="padding:8px 0;border-top:1px solid var(--line)">
      <div><strong><?= e($c['title']) ?></strong> — <?= $c['state']['to_t1_pct'] ?> % · manque <?= e(Units::qty($c['state']['missing'], $c['unit'])) ?> · fin dans <?= e(remaining_fr($c['end_at'])) ?></div>
      <div class="row">
        <form method="post" action="/admin/campagnes/<?= (int)$c['id'] ?>/action"><?= csrf_field() ?><input type="hidden" name="action" value="extend"><button class="btn btn--ghost btn--sm">Prolonger 48 h</button></form>
        <form method="post" action="/admin/campagnes/<?= (int)$c['id'] ?>/action" data-confirm="Valider la campagne et racheter <?= e(Units::qty($c['state']['missing'], $c['unit'])) ?> pour le stock de la boutique ?"><?= csrf_field() ?><input type="hidden" name="action" value="force"><button class="btn btn--wood btn--sm">Racheter le reliquat</button></form>
      </div>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<div class="layout-aside">
  <section class="panel">
    <div class="row row--between"><h2>Campagnes ouvertes</h2><a class="btn btn--sm" href="/admin/campagnes/nouvelle">Nouvelle campagne</a></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Campagne</th><th>Progression</th><th>Palier</th><th class="num">Fin</th></tr></thead>
      <tbody><?php foreach ($active as $c): $st = $c['state']; ?>
        <tr><td><a href="/admin/campagnes/<?= (int)$c['id'] ?>"><?= e($c['title']) ?></a><br><small class="muted"><?= $c['participants'] ?> participants</small></td>
          <td style="min-width:140px"><div class="gauge gauge--light" style="--p:<?= $st['validated'] ? $st['to_next_pct'] : $st['to_t1_pct'] ?>;height:8px"><span></span></div><small class="muted"><?= e(Units::qty($st['reserved'], $c['unit'])) ?></small></td>
          <td><?= $st['tier'] ? '<span class="chip chip--ok">' . $st['tier'] . '/' . $st['max_tier'] . '</span>' : '<span class="chip">0/' . $st['max_tier'] . '</span>' ?></td>
          <td class="num"><?= e(remaining_fr($c['end_at'])) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$active): ?><tr><td colspan="4" class="muted">Aucune campagne ouverte. <a href="/admin/import">Importez le catalogue</a> ou créez une campagne.</td></tr><?php endif; ?></tbody>
    </table></div>
  </section>
  <aside class="stack">
    <div class="panel">
      <h2>Bénévoles</h2>
      <p><?= $k['candidates'] ?> <a href="/admin/utilisateurs?filtre=candidats"><?= plural($k['candidates'], 'candidature', 'candidatures') ?></a> à valider</p>
      <p class="mb-0"><?= $k['shifts_missing'] ?> <a href="/admin/creneaux"><?= plural($k['shifts_missing'], 'créneau incomplet', 'créneaux incomplets') ?></a></p>
    </div>
    <div class="panel">
      <h2>Emails</h2>
      <p><?= $k['mail_queue'] ?> en file d'attente</p>
      <p class="mb-0"><?= $k['mail_failed'] ? '<span style="color:var(--red)">' . $k['mail_failed'] . ' en échec</span> : vérifiez le SMTP.' : 'Aucun échec' ?></p>
    </div>
  </aside>
</div>
