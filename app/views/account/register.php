<div class="wrap auth" style="max-width:560px">
  <div class="panel">
    <h1 style="font-size:2rem">Créer un compte</h1>
    <p class="muted">Gratuit, sans engagement. Il vous permettra de rejoindre les achats groupés.</p>
    <form method="post" action="/inscription">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="field"><label for="first_name">Prénom</label><input id="first_name" name="first_name" autocomplete="given-name" required value="<?= e(old('first_name')) ?>"></div>
        <div class="field"><label for="last_name">Nom</label><input id="last_name" name="last_name" autocomplete="family-name" value="<?= e(old('last_name')) ?>"></div>
      </div>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"></div>
      <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><span class="hint">8 caractères minimum.</span></div>
      <div class="grid-2">
        <div class="field"><label for="phone">Téléphone <span class="muted">(facultatif)</span></label><input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone')) ?>"></div>
        <div class="field"><label for="postal_code">Code postal <span class="muted">(facultatif)</span></label><input id="postal_code" name="postal_code" inputmode="numeric" autocomplete="postal-code" value="<?= e(old('postal_code')) ?>"></div>
      </div>
      <label class="check"><input type="checkbox" name="volunteer" value="1"<?= old('volunteer') ? ' checked' : '' ?>> Je veux aussi donner un coup de main comme bénévole</label>
      <label class="check mt-4"><input type="checkbox" name="consent" value="1" required> J'accepte la <a href="/confidentialite" target="_blank">politique de confidentialité</a>.</label>
      <button class="btn btn--block btn--lg mt-6" type="submit">Créer mon compte</button>
    </form>
    <p class="mt-6 small mb-0">Déjà inscrit·e ? <a href="/connexion">Se connecter</a></p>
  </div>
</div>
