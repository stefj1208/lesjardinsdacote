<div class="wrap page" style="max-width:760px">
  <p class="crumbs"><a href="/compte">Mon compte</a> / Profil</p>
  <h1 class="page-title">Mon profil</h1>
  <form method="post" action="/compte/profil" class="panel">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="field"><label for="first_name">Prénom</label><input id="first_name" name="first_name" required value="<?= e($u['first_name']) ?>"></div>
      <div class="field"><label for="last_name">Nom</label><input id="last_name" name="last_name" value="<?= e($u['last_name']) ?>"></div>
      <div class="field"><label for="phone">Téléphone</label><input id="phone" name="phone" type="tel" value="<?= e($u['phone']) ?>"></div>
      <div class="field"><label for="postal_code">Code postal</label><input id="postal_code" name="postal_code" value="<?= e($u['postal_code']) ?>"></div>
    </div>
    <p class="muted small">Email : <?= e($u['email']) ?></p>
    <label class="check"><input type="checkbox" name="notify_tiers" value="1"<?= $u['notify_tiers'] ? ' checked' : '' ?>> Me prévenir quand un palier est débloqué sur mes campagnes</label>
    <h2 class="mt-6">Changer de mot de passe</h2>
    <div class="grid-2">
      <div class="field"><label for="current_password">Mot de passe actuel</label><input id="current_password" name="current_password" type="password" autocomplete="current-password"></div>
      <div class="field"><label for="new_password">Nouveau mot de passe</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password"></div>
    </div>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
  <p class="small muted mt-6">Pour supprimer votre compte et vos données, écrivez à <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a> (les factures sont conservées 10 ans, obligation légale).</p>
</div>
