<div class="wrap page center" style="max-width:640px">
  <?php partial('partials/plant', ['state' => 1, 'growth' => 0, 'size' => 'md']) ?>
  <h1 class="mt-6">Rien n'a encore poussé ici</h1>
  <p class="muted"><?= e($message ?: 'Cette page n\'existe pas ou a été déplacée.') ?></p>
  <a class="btn" href="/campagnes">Voir les campagnes</a>
</div>
