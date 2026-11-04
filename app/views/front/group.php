<?php
$ps = $g['public_status'];
$s = $g['sale'];
$needed = (int)$g['volunteers_needed'];
$steps = [
  'recruiting' => ['Recherche de bénévoles', $g['volunteers'] >= $needed ? $g['volunteers'] . ' bénévoles engagés' : $g['volunteers'] . ' / ' . $needed . ' bénévoles'],
  'preopening' => ['Pré-ouverture', $s && $s['status'] === 'voting' ? 'Vote jusqu\'au ' . date_fr($s['vote_ends_at']) : 'Vote des variétés'],
  'open'       => ['Ventes ouvertes', $s && $s['status'] === 'open' ? 'Jusqu\'au ' . date_fr($s['sale_ends_at']) : 'Achats par lots de 10'],
];
$order = array_keys($steps);
$curIdx = array_search($ps, $order, true);
?>
<section class="g-banner reveal-img"><?= ph($g['photo'], 'Jardin à ' . $g['commune'], '', 1800, true) ?></section>

<div class="wrap">
  <header class="g-head" data-reveal>
    <div class="row row--between" style="align-items:flex-start">
      <div>
        <div class="row"><?php partial('partials/status', ['s' => $ps, 'long' => true]) ?><?php if ($s): ?><span class="chip"><?= e(Cat::label($s['category'])) ?></span><?php endif; ?></div>
        <h1 class="mt-2"><?= e($g['name']) ?></h1>
        <p class="muted mb-0"><?= e($g['commune']) ?> (<?= e($g['postal_code']) ?>) · <?= e($g['organizer_name'] ?: Cat::ORGANIZERS[$g['organizer_type']]) ?> · <?= $g['members'] ?> <?= plural($g['members'], 'membre', 'membres') ?></p>
      </div>
    </div>
    <ol class="stepper" aria-label="Étapes du groupe">
      <?php foreach ($steps as $k => [$label, $sub]): $i = array_search($k, $order, true); ?>
        <li class="step<?= $i < $curIdx ? ' is-done' : '' ?><?= $i === $curIdx ? ' is-current' : '' ?>" style="list-style:none"<?= $i === $curIdx ? ' aria-current="step"' : '' ?>><b><?= $i < $curIdx ? '✓ ' : '' ?><?= e($label) ?></b><small><?= e($sub) ?></small></li>
      <?php endforeach; ?>
    </ol>
  </header>

  <div class="g-layout" style="padding-bottom:5rem">
    <div>
      <?php if ($ps === 'recruiting'): ?>
        <div class="callout" data-reveal>
          <h2 style="font-size:1.6rem">Il manque <?= $needed - $g['volunteers'] ?> <?= plural($needed - $g['volunteers'], 'bénévole', 'bénévoles') ?> pour ouvrir le groupe</h2>
          <p>Le rôle : aider à préparer les packs (une demi-journée) et à les distribuer (une matinée). Dès que <?= $needed ?> bénévoles sont inscrits, le vote des variétés commence automatiquement.</p>
          <div class="slots mb-2"><?php for ($i = 0; $i < $needed; $i++): ?><span class="slot slot--big<?= $i < $g['volunteers'] ? ' is-person' : '' ?>" style="--i:<?= $i ?>"></span><?php endfor; ?></div>
          <?php if ($myRole === 'volunteer'): ?><p class="mb-0"><strong>Merci, vous êtes bénévole de ce groupe.</strong></p>
          <?php else: ?><form method="post" action="/groupes/<?= e($g['slug']) ?>/rejoindre"><?= csrf_field() ?><input type="hidden" name="role" value="volunteer"><button class="btn" type="submit">Je deviens bénévole</button></form><?php endif; ?>
        </div>
        <?php if ($s && $s['varieties']): $pj = $s['projection']; ?>
          <h2 class="mt-6">Les <?= count($s['varieties']) ?> variétés qui seront proposées</h2>
          <p class="muted">Dès l'ouverture du vote, chaque membre choisit jusqu'à <?= (int)$s['max_choices'] ?> variétés. Les plus demandées composent le pack à <?= eur($s['price']) ?><?= $pj && $pj['save'] ? ' : environ ' . count($pj['items']) . ' variétés, d\'une valeur de ' . eur($pj['market']) . ' au prix public' : '' ?>.</p>
          <?php partial('partials/variety_picker', ['s' => $s, 'myChoices' => [], 'readonly' => true]); ?>
        <?php endif; ?>

      <?php elseif ($s && $s['status'] === 'voting'): $pj = $s['projection']; ?>
        <p class="eyebrow eyebrow--start">Pré-ouverture</p>
        <h2 class="mb-0">Composez le pack de votre groupe</h2>
        <p class="lede mt-1" style="font-size:1.08rem">Choisissez jusqu'à <strong><?= (int)$s['max_choices'] ?> variétés</strong> parmi les <?= count($s['varieties']) ?> proposées. Les plus demandées entrent dans le pack, dans l'ordre des voix, jusqu'à atteindre son prix de <?= eur($s['price']) ?>. Vote ouvert jusqu'au <?= e(date_fr($s['vote_ends_at'], true, true)) ?>.</p>
        <?php if (!Auth::check()): ?><div class="notice mb-2" style="margin-bottom:1rem"><a href="/connexion">Connectez-vous</a> ou <a href="/inscription">créez un compte</a> pour enregistrer vos choix : le pack composé arrivera directement dans votre panier.</div><?php endif; ?>
        <?php if ($myChoices): ?><div class="callout callout--leaf mb-2" style="margin-bottom:1rem"><strong>Vos <?= count($myChoices) ?> choix sont enregistrés.</strong> Vous pouvez les modifier jusqu'à la fin du vote. Le pack composé arrivera dans votre panier à l'ouverture des ventes.</div><?php endif; ?>
        <?php if ($pj): ?>
          <div class="projection" data-reveal>
            <div class="projection-head">
              <div><span class="status status--leaf">En direct · <?= $s['voters'] ?> <?= plural($s['voters'], 'votant', 'votants') ?></span><h3 class="mt-1 mb-0">Si le vote s'arrêtait maintenant</h3></div>
              <?php partial('partials/save_badge', ['p' => $pj]) ?>
            </div>
            <div class="projection-stats">
              <div><b data-count="<?= count($pj['items']) ?>"><?= count($pj['items']) ?></b><span>variétés dans le pack</span></div>
              <div><b><?= eur($pj['price']) ?></b><span>le pack, + <?= eur(Asso::fee()) ?> de frais par commande</span></div>
              <?php if ($pj['save']): ?><div><b class="strike-soft"><?= eur($pj['market']) ?></b><span>au prix public</span></div><div><b class="hl" data-count="<?= e(number_format($pj['save'], 2, '.', '')) ?>" data-decimals="2" data-suffix=" €"><?= eur($pj['save']) ?></b><span>d'économie</span></div><?php endif; ?>
            </div>
            <div class="projection-items"><?php foreach ($pj['items'] as $it): ?><span class="pi"><?= ph($it['photo'], '', '', 120) ?><?= e($it['variety'] ?: $it['name']) ?></span><?php endforeach; ?></div>
            <?php if ($pj['out']): ?><p class="small muted mb-0 mt-2">Hors budget pour l'instant : <?= e(implode(', ', array_map(fn($v) => $v['variety'] ?: $v['name'], array_slice($pj['out'], 0, 8)))) ?><?= count($pj['out']) > 8 ? '…' : '' ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
        <?php partial('partials/variety_picker', ['s' => $s, 'myChoices' => $myChoices]); ?>
        <p class="source-note mt-2"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p>

      <?php elseif ($s && $s['status'] === 'open'): $b = $s['batch']; $w = $s['winner']; ?>
        <h2>En vente : <?= e($w['name']) ?></h2>
        <p class="muted"><?= count($w['items']) ?> variétés choisies par <?= $s['voters'] ?> <?= plural($s['voters'], 'membre', 'membres') ?> du groupe. Vente ouverte jusqu'au <?= e(date_fr($s['sale_ends_at'], true, true)) ?>.</p>
        <div class="panel mb-4" style="margin-bottom:1.5rem" data-reveal>
          <div class="row row--between"><h3 class="mb-0">Lot n° <?= $b['no'] ?> : <?= $b['fill'] ?> / <?= $b['size'] ?> achats</h3><?php if ($b['complete']): ?><span class="chip chip--ok"><?= $b['complete'] ?> <?= plural($b['complete'], 'lot complet', 'lots complets') ?> · <?= $b['sold'] ?> packs commandés</span><?php endif; ?></div>
          <div class="slots mt-2"><?php for ($i = 0; $i < $b['size']; $i++): ?><span class="slot slot--big<?= $i < $b['fill'] ? ' is-on' : '' ?>" style="--i:<?= $i ?>"></span><?php endfor; ?></div>
          <p class="mt-2 mb-0 muted">Encore <strong><?= $b['missing'] ?></strong> <?= plural($b['missing'], 'achat', 'achats') ?> pour compléter ce lot. Quand il est complet, les packs sont commandés et débités, puis un nouveau lot de <?= $b['size'] ?> s'ouvre. Si la vente se termine avant, le lot incomplet est annulé et rien n'est débité.</p>
        </div>
        <?php partial('partials/pack_card', ['p' => $w, 'mode' => 'show', 'sale' => $s, 'maxItems' => 8, 'wide' => true]); ?>
        <p class="source-note mt-2"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p>

      <?php else: ?>
        <div class="callout callout--leaf"><h2 style="font-size:1.6rem">Bénévoles au complet !</h2><p class="mb-0">Le prochain vote des variétés sera annoncé aux membres du groupe par email. Rejoignez le groupe pour ne pas le manquer.</p></div>
      <?php endif; ?>
    </div>

    <aside class="side">
      <?php if ($s && $s['status'] === 'open'): $w = $s['winner']; ?>
        <div class="panel buy-box" id="acheter">
          <?php partial('partials/price', ['p' => $w]) ?>
          <?php if ($inCart): ?>
            <div class="callout callout--leaf" style="padding:1rem"><strong>Ce pack est dans votre panier<?= $inCart['source'] === 'vote' ? ' (grâce à votre vote)' : '' ?>.</strong></div>
            <a class="btn btn--accent btn--lg btn--block" href="/panier">Confirmer mon achat</a>
          <?php elseif ($myOrder): ?>
            <div class="callout callout--leaf" style="padding:1rem"><strong>Vous avez commandé ce pack.</strong> <a href="/compte/commande/<?= e($myOrder['reference']) ?>">Suivre ma commande</a></div>
          <?php else: ?>
            <form method="post" action="/panier/ajouter" class="buy-box"><?= csrf_field() ?>
              <input type="hidden" name="sale_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="pack_id" value="<?= (int)$w['id'] ?>">
              <div class="row row--between"><label class="label" for="qty">Nombre de packs</label>
                <div class="qty-pick"><button type="button" data-step="-1" aria-label="Un de moins">−</button><input id="qty" name="qty" type="number" min="1" max="<?= (int)$s['max_per_order'] ?>" value="1"><button type="button" data-step="1" aria-label="Un de plus">+</button></div></div>
              <button class="btn btn--accent btn--lg btn--block" type="submit">Acheter ce pack</button>
            </form>
          <?php endif; ?>
          <p class="small muted mb-0">Rien n'est débité tant que votre lot de <?= (int)$s['batch_size'] ?> achats n'est pas complet. Annulable jusque-là.</p>
        </div>
      <?php endif; ?>
      <?php if (!empty($groupSaved) && $groupSaved > 0): ?>
        <div class="panel panel--navy group-savings" data-reveal>
          <b data-count="<?= e(number_format($groupSaved, 0, '.', '')) ?>" data-suffix=" €"><?= eur(round($groupSaved)) ?></b>
          <span>déjà économisés par les membres du groupe par rapport aux prix publics</span>
        </div>
      <?php endif; ?>

      <div class="panel">
        <h3>Le groupe</h3>
        <dl class="dl">
          <dt>Organisé par</dt><dd><?= e($g['organizer_name'] ?: Cat::ORGANIZERS[$g['organizer_type']]) ?></dd>
          <dt>Bénévoles</dt><dd><?= $g['volunteers'] >= $needed ? $g['volunteers'] : $g['volunteers'] . ' / ' . $needed ?></dd>
          <dt>Membres</dt><dd><?= $g['members'] ?></dd>
          <?php if ($g['pickup_place']): ?><dt>Retrait</dt><dd><?= e($g['pickup_place']) ?><br><span class="muted" style="font-weight:400"><?= e($g['pickup_address']) ?></span></dd><?php endif; ?>
          <?php if ($s && $s['pickup_at']): ?><dt>Distribution</dt><dd><?= e(date_fr($s['pickup_at'], true, true)) ?></dd><?php endif; ?>
        </dl>
        <div class="stack mt-3">
          <?php if (!$myRole): ?>
            <form method="post" action="/groupes/<?= e($g['slug']) ?>/rejoindre"><?= csrf_field() ?><input type="hidden" name="role" value="member"><button class="btn btn--block" type="submit">Rejoindre le groupe</button></form>
            <p class="small muted mb-0">Gratuit. Vous êtes prévenu·e du vote et de l'ouverture des ventes.</p>
          <?php else: ?>
            <p class="mb-0"><span class="chip chip--ok">✓ <?= $myRole === 'volunteer' ? 'Bénévole du groupe' : 'Membre du groupe' ?></span></p>
            <?php if ($myRole === 'member'): ?><form method="post" action="/groupes/<?= e($g['slug']) ?>/rejoindre"><?= csrf_field() ?><input type="hidden" name="role" value="volunteer"><button class="btn btn--ghost btn--block" type="submit">Devenir aussi bénévole</button></form><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($g['description']): ?><div class="panel"><h3>À propos</h3><div class="muted"><?= nl2p($g['description']) ?></div></div><?php endif; ?>
      <div class="panel">
        <h3>Faire grandir le groupe</h3>
        <?php $shareText = $g['name'] . ' : on achète ensemble des packs de graines rares, bien moins cher qu\'au prix public. Rejoignez-nous !'; $shareUrl = abs_url('/groupes/' . $g['slug']); ?>
        <div class="row"><a class="btn btn--sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>">WhatsApp</a><a class="btn btn--ghost btn--sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>">Facebook</a><button class="btn btn--ghost btn--sm" type="button" data-share data-share-text="<?= e($shareText) ?>" data-share-url="<?= e($shareUrl) ?>">Copier le lien</button></div>
      </div>
    </aside>
  </div>
</div>
<?php if ($s && $s['status'] === 'open' && !$myOrder): ?>
<a class="mobile-cta" href="<?= $inCart ? '/panier' : '#acheter' ?>"><span><strong><?= eur($s['winner']['price']) ?></strong><?= $s['winner']['save'] ? ' <s>' . eur($s['winner']['market']) . '</s>' : '' ?> · lot <?= $s['batch']['fill'] ?>/<?= $s['batch']['size'] ?></span><span class="btn btn--accent btn--sm"><?= $inCart ? 'Confirmer' : 'Acheter' ?></span></a>
<?php endif; ?>
