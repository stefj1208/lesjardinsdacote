<div class="wrap page pass">
  <div class="slate">
    <h1 class="chalk" style="font-size:2.4rem;color:var(--chalk)">Pass Retrait</h1>
    <p class="mb-0"><?= e(trim($o['first_name'] . ' ' . mb_substr((string)$o['last_name'], 0, 1)) . '.') ?> · <?= e($o['reference']) ?></p>
    <div class="qr"><?= QR::svg(abs_url('/r/' . $o['pickup_token']), 220) ?></div>
    <div class="code"><?= e($o['pickup_code']) ?></div>
    <p class="small" style="color:var(--chalk-dim)">Code à donner si le QR ne passe pas</p>
    <?php if ($o['status'] === 'collected'): ?>
      <p class="chalk" style="font-size:1.6rem;color:var(--green)">✓ Retirée le <?= e(date_fr($o['collected_at'])) ?></p>
    <?php elseif ($o['status'] !== 'ready'): ?>
      <p class="chalk" style="font-size:1.4rem;color:var(--chalk-yellow)">Pas encore prête : vous recevrez un email.</p>
    <?php endif; ?>
  </div>
  <div class="panel mt-6" style="text-align:left">
    <?php if ($o['window_label']): ?>
      <p><strong><?= e($o['window_location']) ?></strong><br><?= e($o['window_address']) ?><br><?= e(time_range_fr($o['window_starts'], $o['window_ends'])) ?></p>
    <?php else: ?><p><strong><?= e(setting('association_address')) ?></strong></p><?php endif; ?>
    <ul class="mb-0">
      <?php foreach ($items as $it): if (!in_array($it['status'], ['validated', 'packed', 'collected'], true)) continue; ?>
        <li><?= (int)$it['packets'] ?> × <?= e(Units::qty($it['portion_qty'], $it['unit'])) ?> — <?= e($it['title']) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($o['payment_status'] === 'to_collect'): ?><p class="mt-4 mb-0"><strong>À régler sur place : <?= money($o['amount_final']) ?></strong></p><?php endif; ?>
  </div>
</div>
