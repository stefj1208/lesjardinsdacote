<?php
/** Poste de pilotage. @var array $stats @var array $todos @var array $runs @var array $journal @var array $pipes @var array $k */
$open = array_values(array_filter($todos, fn($t) => !$t['done_at']));
$done = array_values(array_filter($todos, fn($t) => $t['done_at']));
$cronOk = $lastCron && strtotime($lastCron) > time() - 3600;
$act = function (string $do, array $fields, string $label, string $class = 'btn btn--ghost btn--sm') {
    $h = '<form method="post" action="/admin/pilotage" class="inline">' . csrf_field() . '<input type="hidden" name="do" value="' . e($do) . '">';
    foreach ($fields as $n => $v) $h .= '<input type="hidden" name="' . e($n) . '" value="' . e((string)$v) . '">';
    return $h . '<button class="' . $class . '" type="submit">' . e($label) . '</button></form>';
};
$tiles = [
    ['À valider', $stats['pending'], '/admin/a-valider', 'Propositions des agents', true],
    ['Groupes proposés', $k['proposed'], '/admin/groupes', 'À accepter ou refuser', false],
    ['Paiements en échec', $k['pay_issues'], '/admin/commandes', 'À relancer', false],
    ['Packs à préparer', $k['to_prepare'], '/admin/commandes', 'Lots complets', false],
    ['Posts à publier', $k['posts_ready'], '/admin/a-valider', 'Validés, prêts à poster', false],
    ['E-mails en échec', $k['mail_failed'], '/admin/parametres', 'Envoi à vérifier', false],
];
?>
<div class="pilot">
  <header class="pilot-head">
    <div><h1>Pilotage</h1><p class="muted mb-0"><?= e(date_fr(date('Y-m-d'), false, true)) ?> · tout ce qui attend une décision, et ce que font les agents.</p></div>
    <span class="pstatus <?= $cronOk ? 'is-ok' : 'is-ko' ?>"><?= $cronOk ? 'Site et tâches : OK' : 'Tâche du site arrêtée' ?></span>
  </header>

  <div class="panel install-card" data-install hidden>
    <div class="install-icon"><img src="/assets/img/app-192.png" alt="" width="56" height="56"></div>
    <div>
      <b>Installer le pilotage sur votre téléphone</b>
      <p class="small mb-0" data-install-ios hidden>Sur iPhone : touchez <strong>Partager</strong> <span aria-hidden="true">⬆︎</span> dans Safari, puis <strong>Sur l'écran d'accueil</strong>.</p>
      <p class="small mb-0" data-install-android hidden>Une icône « Jardins Pilotage » s'ajoute à votre écran d'accueil, comme une application.</p>
      <p class="small mb-0" data-install-other hidden>Ouvrez cette page sur votre téléphone (Safari sur iPhone, Chrome sur Android) pour l'installer comme une application.</p>
    </div>
    <div class="install-actions">
      <button class="btn btn--sm" type="button" data-install-btn hidden>Installer</button>
      <button class="btn btn--ghost btn--sm" type="button" data-install-close>Plus tard</button>
    </div>
  </div>

  <section>
    <h2>À traiter maintenant</h2>
    <div class="ptiles">
      <?php foreach ($tiles as [$label, $n, $href, $hint, $always]): if (!$n && !$always) continue; ?>
        <a class="ptile<?= $n ? ' is-hot' : '' ?>" href="<?= $href ?>"><b><?= (int)$n ?></b><span><?= e($label) ?></span><small><?= e($n ? $hint : 'Rien en attente') ?></small></a>
      <?php endforeach; ?>
      <?php $pendT = (int)db()->val("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'"); if ($pendT): ?>
        <a class="ptile is-hot" href="/admin/temoignages"><b><?= $pendT ?></b><span>Témoignages</span><small>À publier ou masquer</small></a>
      <?php endif; ?>
      <a class="ptile<?= $open ? ' is-hot' : '' ?>" href="#liste"><b><?= count($open) ?></b><span>Dans ma liste</span><small>Actions que vous seul pouvez faire</small></a>
    </div>
  </section>

  <section id="liste">
    <h2>Ma liste</h2>
    <div class="panel ptodos">
      <?php if (!$open): ?><p class="mb-0">Tout est fait. Les agents ajouteront ici ce qui dépend de vous.</p><?php endif; ?>
      <?php foreach ($open as $t): ?>
        <div class="ptodo">
          <?= $act('todo_done', ['id' => $t['id']], '', 'pcheck') ?>
          <div><b><?= e($t['title']) ?></b>
            <?php if ($t['detail']): ?><small><?= e($t['detail']) ?></small><?php endif; ?>
            <?php if ($t['link']): ?><a class="small" href="<?= e($t['link']) ?>">Ouvrir →</a><?php endif; ?>
            <?php if ($t['source'] !== 'plan' && $t['source'] !== 'moi'): ?><span class="chip">ajouté par l'agent <?= e($t['source']) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <form method="post" action="/admin/pilotage" class="ptodo-add"><?= csrf_field() ?><input type="hidden" name="do" value="todo_add">
        <input name="title" placeholder="Ajouter une action…" maxlength="200" required><button class="btn btn--sm" type="submit">Ajouter</button></form>
      <?php if ($done): ?>
        <details class="pdone"><summary><?= count($done) ?> action<?= count($done) > 1 ? 's' : '' ?> terminée<?= count($done) > 1 ? 's' : '' ?></summary>
          <?php foreach ($done as $t): ?><div class="ptodo is-done"><?= $act('todo_undo', ['id' => $t['id']], '✓', 'pcheck is-on') ?><div><b><?= e($t['title']) ?></b></div></div><?php endforeach; ?>
        </details>
      <?php endif; ?>
    </div>
  </section>

  <section>
    <h2>Les agents</h2>
    <div class="pagents">
      <?php foreach (Pilot::AGENTS as $key => $a): $run = $runs[$key] ?? null; $paused = Pilot::paused($key);
        $state = $a['status'] === 'planned' ? ['À venir', 'is-plan'] : ($paused ? ['En pause', 'is-pause'] : ($a['status'] === 'site' ? [$cronOk ? 'Actif' : 'Arrêté', $cronOk ? 'is-ok' : 'is-ko'] : ($run ? ['Actif', 'is-ok'] : ['En attente du 1er passage', 'is-wait']))); ?>
        <div class="panel pagent">
          <div class="pagent-top"><b><?= e($a['label']) ?></b><span class="pstatus <?= $state[1] ?>"><?= e($state[0]) ?></span></div>
          <p class="small mb-0"><?= e($a['does']) ?></p>
          <p class="small muted"><?= e($a['when']) ?></p>
          <?php if ($run): ?><p class="prun"><strong><?= e(date_fr($run['created_at'], true)) ?></strong> — <?= e($run['summary']) ?></p>
          <?php elseif ($a['status'] === 'site'): ?><p class="prun">Dernier passage : <?= $lastCron ? e(date_fr($lastCron, true)) : 'jamais' ?></p><?php endif; ?>
          <?php if ($a['status'] === 'live'): ?><?= $paused ? $act('resume', ['agent' => $key], 'Relancer', 'btn btn--sm') : $act('pause', ['agent' => $key], 'Mettre en pause') ?><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Niveau d'autonomie</h2>
    <p class="muted small">Une catégorie peut passer en envoi automatique après <?= Pilot::AUTO_MIN_DECIDED ?> décisions dont <?= Pilot::AUTO_MIN_RATE ?> % validées sans retouche. Vous pouvez revenir en arrière à tout moment.</p>
    <div class="pauto">
      <?php foreach (Agents::CATEGORIES as $c => $meta): if ($c === 'autre' || $c === 'catalogue') continue;
        $s = $stats['categories'][$c] ?? ['decided' => 0, 'clean_rate' => null, 'pending' => 0];
        $allowed = in_array($c, Pilot::AUTO_ALLOWED, true); $on = Pilot::auto($c); $elig = $allowed && Pilot::eligible($s);
        $progress = min(100, (int)round(($s['decided'] / Pilot::AUTO_MIN_DECIDED) * 100)); ?>
        <div class="panel prow">
          <div class="prow-main"><b><?= e($meta['label']) ?></b>
            <small><?= (int)$s['decided'] ?> décision<?= $s['decided'] > 1 ? 's' : '' ?><?= $s['clean_rate'] !== null ? ' · ' . (int)$s['clean_rate'] . ' % validées telles quelles' : '' ?></small>
            <span class="pbar"><i style="width:<?= $progress ?>%"></i></span>
          </div>
          <div class="prow-side">
            <?php if (!$allowed): ?><span class="pstatus is-plan">Toujours validé par vous</span>
            <?php elseif ($on): ?><span class="pstatus is-ok">Envoi automatique</span><?= $act('auto_off', ['category' => $c], 'Repasser en manuel') ?>
            <?php elseif ($elig): ?><?= $act('auto_on', ['category' => $c], 'Passer en automatique', 'btn btn--sm') ?>
            <?php else: ?><span class="pstatus is-wait">Validation manuelle</span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Pipelines</h2>
    <div class="ppipes">
      <?php foreach (['fournisseur' => 'Fournisseurs', 'collectivite' => 'Mairies & collectivités'] as $c => $label): $p = $pipes[$c]; ?>
        <div class="panel"><b><?= e($label) ?></b>
          <div class="ppipe"><div><b><?= $p['waiting'] ?></b><span>à valider</span></div><div><b><?= $p['contacted'] ?></b><span>contactés</span></div><div><b><?= $p['replied'] ?></b><span>ont répondu</span></div><div><b><?= $p['rejected'] ?></b><span>écartés</span></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Chiffres</h2>
    <div class="ptiles ptiles--flat">
      <div class="ptile"><b><?= eur($stats['kpi']['fund']) ?></b><span>Fonds plantations</span></div>
      <div class="ptile"><b><?= (int)$stats['kpi']['members'] ?></b><span>Membres <?= Asso::year() ?></span></div>
      <div class="ptile"><b><?= (int)$stats['kpi']['groups_active'] ?></b><span>Groupes actifs</span></div>
      <div class="ptile"><b><?= (int)$k['sales_open'] ?></b><span>Ventes en cours</span></div>
      <div class="ptile"><b><?= (int)$stats['kpi']['orders_30d'] ?></b><span>Commandes (30 j)</span></div>
      <div class="ptile"><b><?= (int)$stats['inbox']['new'] ?></b><span>Mails sans réponse</span></div>
    </div>
    <p class="small"><a href="/admin/activite">Voir le détail des ventes et de l'activité →</a></p>
  </section>

  <section>
    <h2>Journal</h2>
    <?php if (!$journal): ?><div class="panel"><p class="mb-0">Aucune action enregistrée pour l'instant. Chaque passage d'agent et chacune de vos décisions s'inscrira ici.</p></div>
    <?php else: ?>
      <div class="panel pjournal">
        <?php foreach ($journal as $j): ?><div class="pj<?= $j['ok'] ? '' : ' is-ko' ?>"><time><?= e(date_fr($j['at'], true)) ?></time><b><?= e($j['who']) ?></b><span><?= e($j['what']) ?></span></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
