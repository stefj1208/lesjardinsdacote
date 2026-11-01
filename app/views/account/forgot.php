<div class="wrap auth">
  <div class="panel">
    <h1 style="font-size:2rem">Mot de passe oublié</h1>
    <p class="muted">Indiquez votre email, nous vous envoyons un lien pour en choisir un nouveau.</p>
    <form method="post" action="/mot-de-passe-oublie">
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required></div>
      <button class="btn btn--block btn--lg" type="submit">Recevoir le lien</button>
    </form>
  </div>
</div>
