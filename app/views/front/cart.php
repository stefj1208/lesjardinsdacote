<div class="wrap page">
  <h1 class="page-title">Mon panier</h1>
  <?php if (!$cart['lines']): ?>
    <div class="empty"><h3>Votre panier est vide</h3><p>Choisissez un bac, une contenance, et la plante commencera à pousser.</p><a class="btn" href="/campagnes">Voir les campagnes</a></div>
  <?php else: ?>
  <div class="layout-aside">
    <div>
      <?php foreach ($cart['groups'] as $g): ?>
      <section class="panel">
        <h2><?= e($g['label']) ?></h2>
        <?php foreach ($g['lines'] as $l): $c = $l['campaign']; ?>
        <div class="cart-line">
          <div class="thumb"><?php if ($c['image_url']): ?><img src="<?= e($c['image_url']) ?>" alt=""><?php else: partial('partials/plant', ['state' => $c['state']['plant_state'], 'growth' => $c['state']['growth'], 'toT1' => $c['state']['to_t1_pct'] / 100, 'size' => 'sm']); endif; ?></div>
          <div>
            <h3><a href="/campagne/<?= e($c['slug']) ?>"><?= e($c['title']) ?></a></h3>
            <div class="meta">Sachet de <?= e(Units::qty($l['portion'], $c['unit'])) ?> · <?= money($l['packet_price']) ?> au palier actuel · clôture <?= e(date_fr($c['end_at'])) ?></div>
            <form class="qty-form" method="post" action="/panier/modifier">
              <?= csrf_field() ?><input type="hidden" name="key" value="<?= e($l['key']) ?>">
              <label class="sr-only" for="q-<?= e($l['key']) ?>">Nombre de sachets</label>
              <input id="q-<?= e($l['key']) ?>" type="number" name="packets" min="1" max="<?= (int)$c['max_packets_per_line'] ?>" value="<?= $l['packets'] ?>">
              <button class="btn btn--ghost btn--sm" type="submit">Mettre à jour</button>
              <button class="linkish small" type="submit" name="remove" value="1">Retirer</button>
            </form>
          </div>
          <strong><?= money($l['total']) ?></strong>
        </div>
        <?php endforeach; ?>
      </section>
      <?php endforeach; ?>
    </div>
    <aside class="panel summary">
      <h2>Récapitulatif</h2>
      <div class="line"><span>Montant maximum</span><span><?= money($cart['total']) ?></span></div>
      <p class="small muted">C'est le prix au palier actuel. Si d'autres voisins rejoignent les campagnes, il baisse pour vous aussi.</p>
      <div class="line total"><span>Débité aujourd'hui</span><span><?= money(0) ?></span></div>
      <a class="btn btn--go btn--block btn--lg mt-4" href="/engagement">M'engager</a>
      <a class="btn btn--ghost btn--block mt-4" href="/campagnes">Continuer mes achats</a>
    </aside>
  </div>
  <?php endif; ?>
</div>
