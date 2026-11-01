<div class="wrap page prose">
  <h1>Crédits photos</h1>
  <p>Les photos de démonstration proviennent de <a href="https://commons.wikimedia.org" rel="noopener">Wikimedia Commons</a> et sont utilisées selon leur licence (Creative Commons ou domaine public). Elles peuvent être remplacées à tout moment par vos propres photos depuis le back-office.</p>
  <?php if (!$photos): ?><p class="muted">La liste se remplit automatiquement au fur et à mesure que les photos sont copiées sur le serveur.</p><?php endif; ?>
  <ul>
    <?php foreach ($photos as $p): ?>
      <li><a href="<?= e($p['source_page'] ?: Photo::remote($p['ref'])) ?>" rel="noopener"><?= e(str_replace('_', ' ', substr($p['ref'], 8))) ?></a> — <?= e($p['author'] ?: 'auteur sur la page source') ?><?= $p['license'] ? ', ' . e($p['license']) : '' ?></li>
    <?php endforeach; ?>
  </ul>
</div>
