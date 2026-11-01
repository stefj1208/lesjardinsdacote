<div class="wrap page">
  <h1>Mon panier</h1>
  <?php $valid = array_filter($items, fn($i) => $i['valid']); ?>
  <?php if (!$items): ?>
    <div class="empty mt-3"><h3>Votre panier est vide</h3><p>Quand un groupe dont vous êtes membre ouvre ses ventes, le pack gagnant arrive ici automatiquement si vous avez voté.</p><a class="btn" href="/groupes">Voir les groupes</a></div>
  <?php else: ?>
  <div class="layout-aside mt-3">
    <section class="panel">
      <?php foreach ($items as $it): ?>
        <div class="cart-line">
          <?= ph($it['photo'], $it['pack_name'], '', 300) ?>
          <div>
            <?php if ($it['source'] === 'vote'): ?><span class="chip chip--ok mb-2" style="margin-bottom:.4rem">Ajouté grâce à votre vote</span><?php endif; ?>
            <h3 class="mb-0" style="font-size:1.15rem"><a href="/packs/<?= e($it['pack_slug']) ?>" style="color:inherit"><?= e($it['pack_name']) ?></a></h3>
            <div class="small muted"><?= e($it['group_name']) ?> · <?= e($it['sale_title']) ?></div>
            <?php if ($it['valid']): ?>
              <div class="small mt-1">Lot n° <?= $it['batch']['no'] ?> : <?= $it['batch']['fill'] ?> / <?= $it['batch']['size'] ?> · vente ouverte jusqu'au <?= e(date_fr($it['sale_ends_at'])) ?></div>
              <form class="row mt-1" method="post" action="/panier/modifier"><?= csrf_field() ?><input type="hidden" name="sale_id" value="<?= (int)$it['sale_id'] ?>">
                <label class="sr-only" for="q<?= (int)$it['sale_id'] ?>">Quantité</label>
                <select id="q<?= (int)$it['sale_id'] ?>" name="qty" style="width:auto" data-autosubmit><?php for ($q = 1; $q <= (int)$it['max_per_order']; $q++): ?><option value="<?= $q ?>"<?= $q === (int)$it['quantity'] ? ' selected' : '' ?>><?= $q ?> pack<?= $q > 1 ? 's' : '' ?></option><?php endfor; ?></select>
                <button class="linkish small" type="submit" name="remove" value="1">Retirer</button>
              </form>
            <?php else: ?>
              <p class="small error-text mt-1 mb-0">Cette vente n'est plus ouverte.</p>
              <form method="post" action="/panier/modifier"><?= csrf_field() ?><input type="hidden" name="sale_id" value="<?= (int)$it['sale_id'] ?>"><button class="linkish small" name="remove" value="1">Retirer</button></form>
            <?php endif; ?>
          </div>
          <strong><?= money($it['total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </section>
    <aside class="panel summary">
      <h2>Récapitulatif</h2>
      <div class="line"><span>Total</span><span><?= money(array_sum(array_column($valid, 'total'))) ?></span></div>
      <div class="line total"><span>Débité aujourd'hui</span><span><?= money(0) ?></span></div>
      <p class="small muted">Chaque pack n'est débité que lorsque son lot de 10 achats est complet. Si le lot n'est pas complété avant la fin de la vente, rien n'est prélevé.</p>
      <?php if ($valid): ?><a class="btn btn--accent btn--lg btn--block" href="/commande">Confirmer mon achat</a><?php endif; ?>
    </aside>
  </div>
  <?php endif; ?>
</div>
