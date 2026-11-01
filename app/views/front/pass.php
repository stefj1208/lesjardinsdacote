<div class="wrap page pass">
  <div class="pass-card">
    <h1>Pass Retrait</h1>
    <p class="mb-0"><?= e(trim($o['first_name'] . ' ' . mb_substr((string)$o['last_name'], 0, 1)) . '.') ?> · <?= e($o['reference']) ?></p>
    <div class="qr"><?= QR::svg(abs_url('/r/' . $o['pickup_token']), 220) ?></div>
    <div class="code"><?= e($o['pickup_code']) ?></div>
    <p class="small" style="color:#B9C7BE">Code à donner si le QR ne passe pas</p>
    <?php if ($o['status'] === 'collected'): ?><p style="color:#BFE0C7;font-weight:700">✓ Retiré le <?= e(date_fr($o['collected_at'])) ?></p>
    <?php elseif ($o['status'] !== 'ready'): ?><p style="color:var(--sun);font-weight:700">Pas encore prêt : vous recevrez un email.</p><?php endif; ?>
  </div>
  <div class="panel mt-3" style="text-align:left">
    <p><strong><?= (int)$o['quantity'] ?> × <?= e($o['pack_name']) ?></strong><br><span class="muted"><?= e($o['group_name']) ?></span></p>
    <p class="mb-0"><strong><?= e($o['pickup_place'] ?: 'Lieu de retrait') ?></strong><br><?= e($o['pickup_address']) ?><?= $o['pickup_at'] ? '<br>' . e(date_fr($o['pickup_at'], true, true)) : '' ?></p>
    <?php if ($o['payment_status'] === 'to_collect'): ?><p class="mt-2 mb-0"><strong>À régler sur place : <?= money($o['amount']) ?></strong></p><?php endif; ?>
  </div>
</div>
