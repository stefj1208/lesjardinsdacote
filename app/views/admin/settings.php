<?php $cronCmd = '/usr/bin/php ' . APP_DIR . '/cron/run.php'; ?>
<div class="admin-head"><h1>Paramètres</h1></div>
<div class="layout-aside">
  <form class="panel" method="post" action="/admin/parametres">
    <?= csrf_field() ?>
    <h2>Association</h2>
    <div class="field"><label for="association_name">Nom</label><input id="association_name" name="association_name" value="<?= e($s['association_name']) ?>"></div>
    <div class="field"><label for="association_legal">Forme et SIRET / RNA</label><input id="association_legal" name="association_legal" value="<?= e($s['association_legal']) ?>"></div>
    <div class="field"><label for="association_address">Adresse (boutique / retraits)</label><input id="association_address" name="association_address" value="<?= e($s['association_address']) ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="contact_email">Email de contact</label><input id="contact_email" name="contact_email" type="email" value="<?= e($s['contact_email']) ?>"></div>
      <div class="field"><label for="publication_director">Directeur·rice de publication</label><input id="publication_director" name="publication_director" value="<?= e($s['publication_director']) ?>"></div>
    </div>
    <div class="field"><label for="host_info">Hébergeur (mentions légales)</label><textarea id="host_info" name="host_info" style="min-height:70px"><?= e($s['host_info']) ?></textarea></div>
    <h2 class="mt-6">Valeurs par défaut des campagnes</h2>
    <div class="grid-2">
      <div class="field"><label for="m">Contribution asso (%)</label><input id="m" name="default_margin_rate" inputmode="decimal" value="<?= e(dec_in((float)$s['default_margin_rate'] * 100, 2)) ?>"></div>
      <div class="field"><label for="w">Freinte (%)</label><input id="w" name="default_waste_coeff" inputmode="decimal" value="<?= e(dec_in((float)$s['default_waste_coeff'] * 100, 2)) ?>"></div>
      <div class="field"><label for="pc">Coût sachet + étiquette (€)</label><input id="pc" name="default_packet_cost" inputmode="decimal" value="<?= e(dec_in((float)$s['default_packet_cost'], 4)) ?>"></div>
      <div class="field"><label for="v">TVA (%)</label><input id="v" name="default_vat_rate" inputmode="decimal" value="<?= e(dec_in((float)$s['default_vat_rate'] * 100, 2)) ?>"></div>
    </div>
    <div class="field"><label for="vm">Mention TVA (si 0 %)</label><input id="vm" name="vat_mention" value="<?= e($s['vat_mention']) ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="prh">Relance retardataires après (heures)</label><input id="prh" name="pickup_reminder_hours" type="number" value="<?= e($s['pickup_reminder_hours']) ?>"></div>
      <div class="field"><label for="df">Consigne sac (€)</label><input id="df" name="deposit_fee" inputmode="decimal" value="<?= e(dec_in((float)$s['deposit_fee'], 2)) ?>"></div>
    </div>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
  <aside class="stack">
    <div class="panel">
      <h2>État technique</h2>
      <dl class="dl small">
        <dt>Paiement Stripe</dt><dd><?= stripe_enabled() ? (str_starts_with((string)config('stripe.secret_key'), 'sk_test') ? 'Mode test' : 'Production') : 'Désactivé' ?></dd>
        <dt>Webhook Stripe</dt><dd><?= config('stripe.webhook_secret') ? 'Configuré' : 'Non configuré' ?></dd>
        <dt>Envoi d'emails</dt><dd><?= config('mail.smtp_host') ? 'SMTP ' . e(config('mail.smtp_host')) : 'mail() PHP' ?></dd>
        <dt>Tâche planifiée</dt><dd><?= $lastCron ? 'dernier passage ' . e(date_fr($lastCron, true)) : 'jamais exécutée' ?></dd>
      </dl>
      <p class="small mt-4"><strong>Cron (toutes les 5 min)</strong><br><code style="word-break:break-all"><?= e($cronCmd) ?></code></p>
      <p class="small"><strong>Ou URL à appeler</strong><br><code style="word-break:break-all"><?= e(abs_url('/cron/' . config('cron_key'))) ?></code></p>
      <p class="small"><strong>Webhook Stripe</strong><br><code><?= e(abs_url('/stripe/webhook')) ?></code></p>
      <form method="post" action="/admin/parametres"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="test_email" value="1" type="submit">M'envoyer un email de test</button></form>
    </div>
    <?php if ($demo): ?>
    <form class="panel" method="post" action="/admin/parametres" data-confirm="Supprimer toutes les données de démonstration (fournisseur, produits, campagnes et voisins fictifs) ?">
      <?= csrf_field() ?>
      <h2>Démonstration</h2>
      <p class="small">Supprime le fournisseur « (démo) », ses produits, ses campagnes et les 12 voisins fictifs.</p>
      <button class="btn btn--danger btn--sm" name="purge_demo" value="1" type="submit">Supprimer les données de démo</button>
    </form>
    <?php endif; ?>
  </aside>
</div>
