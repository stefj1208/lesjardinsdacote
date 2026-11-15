<?php
/** Poste de pilotage. @var array $stats @var array $todos @var array $runs @var array $journal @var array $pipes @var array $k @var array $launch */
$open = array_values(array_filter($todos, fn($t) => !$t['done_at']));
$done = array_values(array_filter($todos, fn($t) => $t['done_at']));
$cronOk = $lastCron && strtotime($lastCron) > time() - 3600;
$act = function (string $do, array $fields, string $label, string $class = 'btn btn--ghost btn--sm') {
    $h = '<form method="post" action="/admin/pilotage" class="inline">' . csrf_field() . '<input type="hidden" name="do" value="' . e($do) . '">';
    foreach ($fields as $n => $v) $h .= '<input type="hidden" name="' . e($n) . '" value="' . e((string)$v) . '">';
    return $h . '<button class="' . $class . '" type="submit"' . ($label === '' ? ' aria-label="Marquer comme fait"' : '') . '>' . e($label) . '</button></form>';
};
$pendT = (int)db()->val("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'");
// Une seule liste, dans l'ordre d'urgence : [nombre, phrase, lien, bouton]
$urgent = array_values(array_filter([
    [$k['pay_issues'], plural($k['pay_issues'], 'paiement a échoué', 'paiements ont échoué'), '/admin/commandes', 'Relancer'],
    [$stats['pending'], plural($stats['pending'], 'message préparé par vos assistants attend votre accord', 'messages préparés par vos assistants attendent votre accord'), '/admin/a-valider', 'Relire'],
    [$k['proposed'], plural($k['proposed'], 'nouveau groupe demande à être accepté', 'nouveaux groupes demandent à être acceptés'), '/admin/groupes', 'Voir'],
    [$k['to_prepare'], plural($k['to_prepare'], 'commande confirmée à préparer', 'commandes confirmées à préparer'), '/admin/commandes', 'Préparer'],
    [$pendT, plural($pendT, 'témoignage à publier ou masquer', 'témoignages à publier ou masquer'), '/admin/temoignages', 'Lire'],
    [$k['posts_ready'], plural($k['posts_ready'], 'publication validée, à poster sur les réseaux', 'publications validées, à poster sur les réseaux'), '/admin/a-valider', 'Voir'],
    [$k['mail_failed'], plural($k['mail_failed'], 'e-mail n\'est pas parti', 'e-mails ne sont pas partis'), '/admin/parametres', 'Vérifier'],
], fn($r) => $r[0] > 0));
$nTodo = count($urgent) + count($open);
$stepsDone = count(array_filter($launch, fn($l) => $l[1]));
$pct = (int)round($stepsDone / max(1, count($launch)) * 100);
$first = trim(explode(' ', (string)(Auth::user()['first_name'] ?? ''))[0]);
$when = function (?string $at): string {
    if (!$at) return 'jamais';
    $d = (int)floor((strtotime(date('Y-m-d')) - strtotime(substr($at, 0, 10))) / 86400);
    return ($d === 0 ? 'aujourd\'hui' : ($d === 1 ? 'hier' : 'il y a ' . $d . ' jours')) . ' à ' . date('G\hi', strtotime($at));
};
?>
<div class="pilot p2">
  <header class="p2-hello">
    <h1>Bonjour<?= $first ? ' ' . e($first) : '' ?></h1>
    <p class="p2-sum <?= $urgent ? 'is-hot' : 'is-calm' ?>">
      <?php if ($urgent): ?><b><?= count($urgent) ?></b> <?= plural(count($urgent), 'point demande', 'points demandent') ?> votre attention aujourd'hui.
      <?php elseif ($open): ?>Rien d'urgent. Il reste <b><?= count($open) ?></b> <?= plural(count($open), 'étape', 'étapes') ?> pour finir le lancement.
      <?php else: ?>Tout est à jour. Rien à faire aujourd'hui.<?php endif; ?>
    </p>
    <?php if (!$cronOk): ?><p class="callout callout--warn">Le site n'exécute plus ses tâches automatiques (e-mails, relève de la boîte, clôture des votes). Vérifiez la tâche Cron dans hPanel.</p><?php endif; ?>
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
    <h2><span class="p2-n">1</span> À faire aujourd'hui</h2>
    <?php if (!$urgent): ?>
      <div class="p2-empty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22V11"/><path d="M12 14c-4.5 0-7-2.600-7-6.500 4 0 7 2.600 7 6.500z"/><path d="M12 11c0-4.600 2.800-7.500 7.500-7.500 0 4.600-2.800 7.500-7.500 7.500z"/></svg><span>Aucun message, paiement ou groupe en attente.</span></div>
    <?php else: ?>
      <div class="p2-list">
        <?php foreach ($urgent as [$n, $text, $href, $cta]): ?>
          <a class="p2-item" href="<?= $href ?>"><b class="p2-count"><?= (int)$n ?></b><span><?= e($text) ?></span><em><?= e($cta) ?> →</em></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section id="liste">
    <h2><span class="p2-n">2</span> Le lancement, étape par étape</h2>
    <div class="panel p2-launch">
      <div class="p2-progress"><span><b><?= $stepsDone ?></b> sur <?= count($launch) ?> étapes clés</span><span class="pbar"><i style="width:<?= $pct ?>%"></i></span></div>
      <ul class="p2-steps">
        <?php foreach ($launch as [$label, $ok, $hint, $href, $cta]): ?>
          <li class="<?= $ok ? 'is-ok' : '' ?>"><i aria-hidden="true"><?= $ok ? '✓' : '' ?></i><div><b><?= e($label) ?></b><?php if (!$ok && $hint): ?><small><?= e($hint) ?></small><?php endif; ?></div>
            <?php if (!$ok && $href): ?><a class="btn btn--sm" href="<?= e($href) ?>"><?= e($cta) ?></a><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <h3 class="p2-sub">Ce que vous seul pouvez faire <span class="muted">(<?= count($open) ?>)</span></h3>
    <div class="panel ptodos">
      <?php if (!$open): ?><p class="mb-0">Tout est fait. Vos assistants ajouteront ici ce qui dépend de vous.</p><?php endif; ?>
      <?php foreach ($open as $i => $t): ?>
        <?php if ($i === 4): ?><details class="p2-more"><summary>Voir les <?= count($open) - 4 ?> suivantes</summary><?php endif; ?>
        <div class="ptodo">
          <?= $act('todo_done', ['id' => $t['id']], '', 'pcheck') ?>
          <div><b><?= e($t['title']) ?></b>
            <?php if ($t['detail']): ?><small><?= e($t['detail']) ?></small><?php endif; ?>
            <?php if ($t['link']): ?><a class="small" href="<?= e($t['link']) ?>">Ouvrir →</a><?php endif; ?>
            <?php if ($t['source'] !== 'plan' && $t['source'] !== 'moi'): ?><span class="chip">ajouté par l'assistant <?= e($t['source']) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (count($open) > 4): ?></details><?php endif; ?>
      <form method="post" action="/admin/pilotage" class="ptodo-add"><?= csrf_field() ?><input type="hidden" name="do" value="todo_add">
        <label class="sr-only" for="todo-new">Ajouter une action</label><input id="todo-new" name="title" placeholder="Ajouter une action…" maxlength="200" required><button class="btn btn--sm" type="submit">Ajouter</button></form>
      <?php if ($done): ?>
        <details class="pdone"><summary><?= count($done) ?> action<?= count($done) > 1 ? 's' : '' ?> terminée<?= count($done) > 1 ? 's' : '' ?></summary>
          <?php foreach ($done as $t): ?><div class="ptodo is-done"><?= $act('todo_undo', ['id' => $t['id']], '✓', 'pcheck is-on') ?><div><b><?= e($t['title']) ?></b></div></div><?php endforeach; ?>
        </details>
      <?php endif; ?>
    </div>
  </section>

  <section>
    <h2><span class="p2-n">3</span> Vos assistants</h2>
    <p class="muted small">Ils préparent, vous décidez : aucun message ne part et aucun euro ne bouge sans votre accord.</p>
    <div class="panel p2-agents">
      <?php foreach (Pilot::AGENTS as $key => $a): if ($a['status'] === 'planned') continue; $run = $runs[$key] ?? null; $paused = Pilot::paused($key);
        $state = $paused ? ['En pause', 'is-pause'] : ($a['status'] === 'site' ? [$cronOk ? 'Actif' : 'Arrêté', $cronOk ? 'is-ok' : 'is-ko'] : ($run ? ['Actif', 'is-ok'] : ['Pas encore passé', 'is-wait'])); ?>
        <details class="p2-agent">
          <summary><span class="pstatus <?= $state[1] ?>"><?= e($state[0]) ?></span><b><?= e($a['label']) ?></b>
            <small><?= $run ? 'Dernier passage ' . e($when($run['created_at'])) : ($a['status'] === 'site' ? 'Dernier passage ' . e($when($lastCron)) : e($a['when'])) ?></small></summary>
          <div class="p2-agent-body">
            <p class="small mb-0"><?= e($a['does']) ?></p>
            <p class="small muted mb-0">Rythme : <?= e($a['when']) ?></p>
            <?php if ($run): ?><p class="prun"><?= e($run['summary']) ?></p><?php endif; ?>
            <?php if ($a['status'] === 'live'): ?><?= $paused ? $act('resume', ['agent' => $key], 'Relancer cet assistant', 'btn btn--sm') : $act('pause', ['agent' => $key], 'Mettre en pause') ?><?php endif; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
    <?php if (!array_filter($runs)): ?><p class="callout mt-2">Aucun assistant n'a encore pu écrire ici. Il manque une autorisation : voir « Autoriser lesjardinsdacote.fr pour les agents » dans votre liste.</p><?php endif; ?>
  </section>

  <section>
    <h2><span class="p2-n">4</span> En chiffres</h2>
    <div class="p2-kpis">
      <div><b><?= (int)$stats['kpi']['members'] ?></b><span>adhérents <?= Asso::year() ?></span></div>
      <div><b><?= (int)$stats['kpi']['groups_active'] ?></b><span><?= plural((int)$stats['kpi']['groups_active'], 'groupe actif', 'groupes actifs') ?></span></div>
      <div><b><?= (int)$stats['kpi']['orders_30d'] ?></b><span>commandes sur 30 jours</span></div>
      <div><b><?= eur($stats['kpi']['fund']) ?></b><span>pour planter des arbres</span></div>
    </div>
    <p class="small mt-2"><a href="/admin/activite">Détail des ventes et de l'activité →</a></p>
  </section>

  <section>
    <details class="p2-adv">
      <summary>Réglages avancés et historique</summary>

      <h3>Démarchage en cours</h3>
      <div class="ppipes">
        <?php foreach (['fournisseur' => 'Fournisseurs', 'collectivite' => 'Mairies & collectivités'] as $c => $label): $p = $pipes[$c]; ?>
          <div class="panel"><b><?= e($label) ?></b>
            <div class="ppipe"><div><b><?= $p['waiting'] ?></b><span>à valider</span></div><div><b><?= $p['contacted'] ?></b><span>contactés</span></div><div><b><?= $p['replied'] ?></b><span>ont répondu</span></div><div><b><?= $p['rejected'] ?></b><span>écartés</span></div></div>
          </div>
        <?php endforeach; ?>
      </div>

      <h3>Envoi sans relecture</h3>
      <p class="muted small">Quand vous aurez validé <?= Pilot::AUTO_MIN_DECIDED ?> messages d'une catégorie, dont <?= Pilot::AUTO_MIN_RATE ?> % sans rien changer, vous pourrez laisser l'assistant envoyer seul. Les premiers contacts avec une mairie restent toujours relus par vous.</p>
      <div class="pauto">
        <?php foreach (Agents::CATEGORIES as $c => $meta): if ($c === 'autre' || $c === 'catalogue') continue;
          $s = $stats['categories'][$c] ?? ['decided' => 0, 'clean_rate' => null, 'pending' => 0];
          $allowed = in_array($c, Pilot::AUTO_ALLOWED, true); $on = Pilot::auto($c); $elig = $allowed && Pilot::eligible($s);
          $progress = min(100, (int)round(($s['decided'] / Pilot::AUTO_MIN_DECIDED) * 100)); ?>
          <div class="panel prow">
            <div class="prow-main"><b><?= e($meta['label']) ?></b>
              <small><?= (int)$s['decided'] ?> sur <?= Pilot::AUTO_MIN_DECIDED ?> décisions<?= $s['clean_rate'] !== null ? ' · ' . (int)$s['clean_rate'] . ' % validées telles quelles' : '' ?></small>
              <span class="pbar"><i style="width:<?= $progress ?>%"></i></span>
            </div>
            <div class="prow-side">
              <?php if (!$allowed): ?><span class="pstatus is-plan">Toujours relu par vous</span>
              <?php elseif ($on): ?><span class="pstatus is-ok">Envoi automatique</span><?= $act('auto_off', ['category' => $c], 'Repasser en manuel') ?>
              <?php elseif ($elig): ?><?= $act('auto_on', ['category' => $c], 'Passer en automatique', 'btn btn--sm') ?>
              <?php else: ?><span class="pstatus is-wait">Relu par vous</span><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <h3>Journal</h3>
      <?php if (!$journal): ?><p class="small muted">Aucune action enregistrée pour l'instant.</p>
      <?php else: ?>
        <div class="panel pjournal">
          <?php foreach ($journal as $j): ?><div class="pj<?= $j['ok'] ? '' : ' is-ko' ?>"><time><?= e(date_fr($j['at'], true)) ?></time><b><?= e($j['who']) ?></b><span><?= e($j['what']) ?></span></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </details>
  </section>
</div>
