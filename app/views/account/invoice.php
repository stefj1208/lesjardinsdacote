<div class="invoice">
  <div class="row row--between" style="align-items:flex-start">
    <div>
      <div class="brand"><?php partial('partials/logo') ?><span class="brand-name">Les Jardins d'à Côté</span></div>
      <p class="small mt-2"><?= e(setting('association_legal')) ?><br><?= e(setting('association_address')) ?><br><?= e(setting('contact_email')) ?></p>
    </div>
    <div style="text-align:right"><h1 style="font-size:1.8rem;margin:0">Facture</h1><p class="small">N° <strong><?= e($o['invoice_number']) ?></strong><br>Date : <?= e(date_fr($o['invoiced_at'])) ?><br>Commande : <?= e($o['reference']) ?></p></div>
  </div>
  <p class="mt-4"><strong>Client</strong><br><?= e(trim($o['first_name'] . ' ' . $o['last_name'])) ?><br><?= e($o['email']) ?></p>
  <table class="table mt-3">
    <thead><tr><th>Désignation</th><th class="num">Qté</th><th class="num">PU TTC</th><th class="num">Total TTC</th></tr></thead>
    <tbody><tr><td><strong><?= e($o['pack_name']) ?></strong> — <?= e($o['group_name']) ?><br><small class="muted"><?= e(implode(', ', array_map(fn($i) => $i['name'] . ' ' . $i['variety'] . ' (' . Cat::qty((int)$i['quantity'], $i['unit']) . ')', $items))) ?></small></td>
      <td class="num"><?= (int)$o['quantity'] ?></td><td class="num"><?= money($o['unit_price']) ?></td><td class="num"><?= money($o['amount']) ?></td></tr></tbody>
  </table>
  <div style="margin-left:auto;max-width:320px" class="mt-3 summary"><div class="line total"><span>Total TTC</span><span><?= money($o['amount']) ?></span></div><p class="small muted">Réglé <?= $o['payment_mode'] === 'stripe' ? 'par carte bancaire' : 'au retrait' ?>.</p></div>
  <p class="small mt-4"><?= e(setting('vat_mention')) ?>. Achat réalisé dans le cadre d'un groupement d'achat : l'association agit en qualité de mandataire de ses membres.</p>
</div>
