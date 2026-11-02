<?php $cronCmd = '/usr/bin/php ' . APP_DIR . '/cron/run.php'; ?>
<div class="admin-head"><h1>Paramètres</h1></div>
<div class="layout-aside">
  <form class="panel" method="post" action="/admin/parametres" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h2>Association</h2>
    <div class="field"><label for="association_name">Nom</label><input id="association_name" name="association_name" value="<?= e($s['association_name']) ?>"></div>
    <div class="field"><label for="association_legal">Forme et SIRET / RNA</label><input id="association_legal" name="association_legal" value="<?= e($s['association_legal']) ?>"></div>
    <div class="field"><label for="association_address">Adresse</label><input id="association_address" name="association_address" value="<?= e($s['association_address']) ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="contact_email">Email de contact</label><input id="contact_email" name="contact_email" type="email" value="<?= e($s['contact_email']) ?>"></div>
      <div class="field"><label for="publication_director">Directeur·rice de publication</label><input id="publication_director" name="publication_director" value="<?= e($s['publication_director']) ?>"></div>
    </div>
    <div class="field"><label for="host_info">Hébergeur (mentions légales)</label><textarea id="host_info" name="host_info" style="min-height:70px"><?= e($s['host_info']) ?></textarea></div>
    <div class="field"><label for="vat_mention">Mention TVA</label><input id="vat_mention" name="vat_mention" value="<?= e($s['vat_mention']) ?>"></div>
    <h2 class="mt-4">Valeurs par défaut des ventes</h2>
    <div class="grid-2">
      <div class="field"><label for="dv">Durée du vote (jours)</label><input id="dv" name="default_vote_days" type="number" min="1" value="<?= e($s['default_vote_days']) ?>"></div>
      <div class="field"><label for="ds">Durée de la vente (jours)</label><input id="ds" name="default_sale_days" type="number" min="1" value="<?= e($s['default_sale_days']) ?>"></div>
      <div class="field"><label for="db">Taille d'un lot</label><input id="db" name="default_batch_size" type="number" min="1" value="<?= e($s['default_batch_size']) ?>"></div>
      <div class="field"><label for="dp">Prix d'un pack (€)</label><input id="dp" name="default_pack_price" inputmode="decimal" value="<?= e($s['default_pack_price']) ?>"></div>
      <div class="field"><label for="prh">Relance des retardataires (heures)</label><input id="prh" name="pickup_reminder_hours" type="number" value="<?= e($s['pickup_reminder_hours']) ?>"></div>
    </div>
    <h2 class="mt-4">Composition des packs</h2>
    <div class="grid-2">
      <div class="field"><label for="sachet_cost">Coût d'un sachet + étiquette (€ par variété)</label><input id="sachet_cost" name="sachet_cost" inputmode="decimal" value="<?= e($s['sachet_cost']) ?>"></div>
      <div class="field"><label for="pack_fixed_costs">Frais fixes par pack (€ : port, fonctionnement)</label><input id="pack_fixed_costs" name="pack_fixed_costs" inputmode="decimal" value="<?= e($s['pack_fixed_costs']) ?>"></div>
    </div>
    <p class="hint">À la clôture du vote, les variétés les plus demandées entrent dans le pack tant que leurs coûts, plus ces frais, restent sous le prix du pack.</p>
    <h2 class="mt-4">Communication</h2>
    <div class="field"><label for="market_price_note">Source du « prix marché » (affichée sous les économies)</label><textarea id="market_price_note" name="market_price_note" style="min-height:80px"><?= e($s['market_price_note']) ?></textarea>
      <span class="hint">Les prix marché se saisissent variété par variété dans chaque pack. Le site affiche alors la valeur du pack en jardinerie et l'économie réalisée.</span></div>
    <h2 class="mt-4">Logo</h2>
    <div class="row" style="align-items:center">
      <div class="logo-preview"><?php partial('partials/logo') ?></div>
      <div class="grow"><div class="field"><label for="logo_file">Remplacer le logo (PNG ou WebP transparent, 400 px de large)</label><input id="logo_file" name="logo_file" type="file" accept="image/png,image/webp,image/jpeg"></div>
        <div class="field"><label for="logo_path">ou adresse de l'image</label><input id="logo_path" name="logo_path" value="<?= e($s['logo_path']) ?>" placeholder="Vide = logo dessiné par défaut"></div></div>
    </div>
    <h2 class="mt-4">Photo d'accueil</h2>
    <div class="row" style="align-items:flex-start">
      <?= ph($s['hero_photo'] ?: Cat::HERO, '', 'thumb-preview', 400) ?>
      <div class="grow"><div class="field"><label for="hero_file">Envoyer une photo (paysage, 2000 px de large idéalement)</label><input id="hero_file" name="hero_file" type="file" accept="image/jpeg,image/png,image/webp"></div>
        <div class="field"><label for="hero_photo">ou référence / adresse</label><input id="hero_photo" name="hero_photo" value="<?= e($s['hero_photo']) ?>" placeholder="Vide = photo par défaut"></div></div>
    </div>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
  <aside class="stack">
    <div class="panel"><h3>État technique</h3>
      <dl class="dl small">
        <dt>Paiement Stripe</dt><dd><?= stripe_enabled() ? (str_starts_with((string)config('stripe.secret_key'), 'sk_test') ? 'Mode test' : 'Production') : 'Désactivé' ?></dd>
        <dt>Emails</dt><dd><?= config('mail.smtp_host') ? 'SMTP ' . e(config('mail.smtp_host')) : 'mail() PHP' ?></dd>
        <dt>Tâche planifiée</dt><dd><?= $lastCron ? e(date_fr($lastCron, true)) : 'jamais exécutée' ?></dd>
        <dt>Photos</dt><dd><?= $photos['cached'] ?> / <?= $photos['total'] ?> copiées sur le serveur</dd>
      </dl>
      <?php if ($photos['missing']): ?><p class="small mt-2"><strong>Introuvables :</strong> <?= e(implode(', ', array_map(fn($r) => str_replace('commons:', '', $r), $photos['missing']))) ?></p>
        <form method="post" action="/admin/parametres"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="retry_photos" value="1">Réessayer</button></form><?php endif; ?>
      <p class="small mt-2"><strong>Cron (toutes les 5 min)</strong><br><code style="word-break:break-all"><?= e($cronCmd) ?></code></p>
      <form method="post" action="/admin/parametres"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="test_email" value="1">M'envoyer un email de test</button></form>
    </div>
    <form class="panel" method="post" action="/admin/parametres" data-confirm="<?= $demo ? 'Supprimer les groupes, votes, achats et membres fictifs ?' : 'Charger les données de démonstration ?' ?>">
      <?= csrf_field() ?><h3>Démonstration</h3>
      <?php if ($demo): ?><p class="small">Supprime les 5 groupes « Exemple », leurs votes et achats, et les membres fictifs. Les packs de démo non utilisés sont aussi retirés.</p><button class="btn btn--danger btn--sm" name="purge_demo" value="1">Supprimer les données de démo</button>
      <?php else: ?><p class="small">Ajoute des packs avec photos et 4 groupes fictifs à différents stades.</p><button class="btn btn--ghost btn--sm" name="load_demo" value="1">Charger la démo</button><?php endif; ?>
    </form>
  </aside>
</div>
