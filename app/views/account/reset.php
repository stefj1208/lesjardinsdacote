<div class="wrap auth">
  <div class="panel">
    <h1 style="font-size:2rem">Nouveau mot de passe</h1>
    <form method="post" action="/reinitialiser/<?= e($token) ?>">
      <?= csrf_field() ?>
      <div class="field"><label for="password">Nouveau mot de passe</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div>
      <button class="btn btn--block btn--lg" type="submit">Enregistrer</button>
    </form>
  </div>
</div>
