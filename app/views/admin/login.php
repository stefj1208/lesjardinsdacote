<div class="wrap auth auth--admin">
  <div class="panel">
    <p class="eyebrow eyebrow--start">Espace réservé</p>
    <h1 style="font-size:2rem">Back-office</h1>
    <p class="muted">Accès réservé à l'administration de l'association.</p>
    <form method="post" action="/admin/connexion">
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="username" required value="<?= e(old('email')) ?>"></div>
      <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <label class="check mb-3"><input type="checkbox" name="remember" value="1" checked><span>Rester connecté sur cet appareil (60 jours)</span></label>
      <button class="btn btn--block btn--lg" type="submit">Se connecter</button>
    </form>
    <p class="mt-4 small mb-0"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
  </div>
</div>
