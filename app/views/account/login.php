<div class="wrap auth">
  <div class="panel">
    <h1 style="font-size:2rem">Connexion</h1>
    <p class="muted">Pour suivre vos engagements et retirer vos sachets.</p>
    <form method="post" action="/connexion">
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"></div>
      <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <button class="btn btn--block btn--lg" type="submit">Se connecter</button>
    </form>
    <p class="mt-6 small"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
    <p class="small mb-0">Pas encore de compte ? <a href="/inscription">Créer un compte en 30 secondes</a></p>
  </div>
</div>
