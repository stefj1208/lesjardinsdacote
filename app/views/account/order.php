<?php
$b = $sale['batch'];
$steps = [
  ['Achat enregistré', true],
  ['Lot de ' . (int)$o['batch_size'] . ' complet', in_array($o['status'], ['confirmed', 'ready', 'collected'], true)],
  ['Préparation', in_array($o['status'], ['ready', 'collected'], true)],
  ['Prêt au retrait', in_array($o['status'], ['ready', 'collected'], true)],
  ['Retiré', $o['status'] === 'collected'],
];
?>
<div class="wrap page">
  <p class="crumbs"><a href="/compte">Mon compte</a> / <?= e($o['reference']) ?></p>
  <?php if ($thanks): ?>
    <div class="callout callout--leaf mb-4" style="margin-bottom:1.5rem"><h2 style="font-size:1.6rem">Merci, votre achat est enregistré !</h2><p class="mb-0">Parlez-en à vos voisins : plus le lot se remplit vite, plus vite les packs sont commandés.</p></div>
  <?php endif; ?>
  <div class="admin-head"><h1><?= e($o['pack_name']) ?></h1><span class="chip"><?= e(Orders::STATUS_LABELS[$o['status']]) ?></span></div>
  <?php if ($o['status'] !== 'cancelled'): ?>
    <ol class="timeline" aria-label="Avancement"><?php foreach ($steps as [$l, $d]): ?><li class="<?= $d ? 'done' : '' ?>"><?= e($l) ?></li><?php endforeach; ?></ol>
  <?php endif; ?>

  <div class="layout-aside">
    <div>
      <?php if ($o['status'] === 'pending'): $fill = (int)db()->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE sale_id = ? AND batch_no = ? AND status = 'pending'", [$o['sale_id'], $o['batch_no']]); ?>
        <div class="panel">
          <h2>Lot n° <?= (int)$o['batch_no'] ?> : <?= $fill ?> / <?= (int)$o['batch_size'] ?></h2>
          <div class="slots"><?php for ($i = 0; $i < (int)$o['batch_size']; $i++): ?><span class="slot slot--big<?= $i < $fill ? ' is-on' : '' ?>"></span><?php endfor; ?></div>
          <p class="mt-2 mb-0 muted">Encore <?= max(0, (int)$o['batch_size'] - $fill) ?> achats avant la commande. La vente se termine le <?= e(date_fr($o['sale_ends_at'])) ?> ; si le lot n'est pas complet d'ici là, votre achat est annulé sans aucun débit.</p>
          <div class="row mt-2"><a class="btn btn--sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode('Il manque ' . max(0, (int)$o['batch_size'] - $fill) . ' achats pour commander le pack « ' . $o['pack_name'] . ' » de ' . $o['group_name'] . ' : ' . abs_url('/groupes/' . $o['group_slug'])) ?>">Partager sur WhatsApp</a></div>
        </div>
      <?php endif; ?>
      <section class="panel">
        <h2>Contenu du pack</h2>
        <ul class="items">
          <?php foreach ($items as $it): ?><li class="item"><?= ph($it['photo'], $it['name'], '', 200) ?><span><b><?= e($it['name']) ?></b><small><?= e($it['variety']) ?></small></span><span class="qty"><?= e(Cat::qty((float)$it['quantity'] * (int)$o['quantity'], $it['unit'])) ?></span></li><?php endforeach; ?>
        </ul>
      </section>
    </div>
    <aside class="stack">
      <div class="panel summary">
        <h2>Paiement</h2>
        <?php partial('partials/order_lines', ['o' => $o]) ?>
        <p class="small mt-2"><strong><?= e(Orders::PAYMENT_LABELS[$o['payment_status']]) ?></strong></p>
        <?php if (in_array($o['payment_status'], ['action_required', 'failed'], true) && stripe_enabled()): ?><a class="btn btn--accent btn--block" href="/compte/paiement/<?= e($o['reference']) ?>">Régler maintenant</a><?php endif; ?>
        <?php if ($o['invoice_number']): ?><a class="btn btn--ghost btn--block mt-2" href="/compte/commande/<?= e($o['reference']) ?>/facture" target="_blank">Facture <?= e($o['invoice_number']) ?></a><?php endif; ?>
      </div>
      <div class="panel">
        <h2>Retrait</h2>
        <p class="mb-0"><strong><?= e($o['pickup_place'] ?: 'À préciser') ?></strong><br><?= e($o['pickup_address']) ?><?= $o['pickup_at'] ? '<br>' . e(date_fr($o['pickup_at'], true, true)) : '' ?></p>
        <?php if ($o['status'] === 'ready'): ?><a class="btn btn--block mt-2" href="/retrait/<?= e($o['pickup_token']) ?>">Ouvrir mon Pass Retrait</a><?php elseif ($o['status'] !== 'cancelled' && $o['status'] !== 'collected'): ?><p class="small muted mt-2 mb-0">Votre Pass Retrait (QR code) arrive par email quand votre pack est prêt.</p><?php endif; ?>
      </div>
      <?php if ($o['status'] === 'pending'): ?>
        <form method="post" action="/compte/commande/<?= e($o['reference']) ?>/annuler" data-confirm="Annuler cet achat ?"><?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit">Annuler mon achat</button><p class="small muted mt-1">Possible tant que le lot n'est pas complet.</p></form>
      <?php endif; ?>
    </aside>
  </div>
</div>
