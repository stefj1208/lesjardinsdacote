<?php $vatRates = []; $totalTtc = 0.0; $totalVat = 0.0; ?>
<div class="invoice">
  <div class="row row--between" style="align-items:flex-start">
    <div>
      <div class="brand" style="color:var(--slate)"><?php partial('partials/logo') ?><span class="brand-name" style="color:var(--slate)">Les Jardins <em style="color:var(--wood)">d'à Côté</em></span></div>
      <p class="small mt-4"><?= e(setting('association_legal')) ?><br><?= e(setting('association_address')) ?><br><?= e(setting('contact_email')) ?></p>
    </div>
    <div style="text-align:right">
      <h1 style="font-size:1.8rem;margin:0">Facture</h1>
      <p class="small">N° <strong><?= e($o['invoice_number']) ?></strong><br>Date : <?= e(date_fr($o['invoiced_at'])) ?><br>Commande : <?= e($o['reference']) ?></p>
    </div>
  </div>
  <p class="mt-6"><strong>Client</strong><br><?= e(trim($o['first_name'] . ' ' . $o['last_name'])) ?><br><?= e($o['email']) ?></p>
  <table class="table mt-6">
    <thead><tr><th>Désignation</th><th>Lot</th><th class="num">Qté</th><th class="num">PU TTC</th><th class="num">Total TTC</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it):
        $pu = Pricing::packetPrice((float)$it['unit_price_final'], (float)$it['portion_qty']);
        $line = (float)$it['line_total_final'];
        $totalTtc += $line;
        $rate = (float)db()->val('SELECT vat_rate FROM campaigns WHERE id = ?', [$it['campaign_id']]);
        $totalVat += $line - $line / (1 + $rate);
        $vatRates[(string)$rate] = true;
    ?>
      <tr><td><?= e($it['title']) ?> — sachet de <?= e(Units::qty($it['portion_qty'], $it['unit'])) ?><br><small class="muted"><?= e($it['latin_name']) ?> · Semences destinées aux jardiniers amateurs</small></td>
        <td><?= e($it['supplier_lot'] ?: '—') ?></td><td class="num"><?= (int)$it['packets'] ?></td><td class="num"><?= money($pu) ?></td><td class="num"><?= money($line) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div style="margin-left:auto;max-width:320px" class="mt-6 summary">
    <?php if ($totalVat > 0.004): ?>
      <div class="line"><span>Total HT</span><span><?= money($totalTtc - $totalVat) ?></span></div>
      <div class="line"><span>TVA</span><span><?= money($totalVat) ?></span></div>
    <?php endif; ?>
    <div class="line total"><span>Total TTC</span><span><?= money($totalTtc) ?></span></div>
    <p class="small muted">Réglé <?= $o['payment_mode'] === 'stripe' ? 'par carte bancaire' : 'au retrait' ?>.</p>
  </div>
  <?php if ($totalVat <= 0.004): ?><p class="small mt-6"><?= e(setting('vat_mention')) ?>.</p><?php endif; ?>
  <p class="small muted mt-6">Achat réalisé dans le cadre d'un groupement d'achat : l'association agit en qualité de mandataire de ses membres.</p>
</div>
