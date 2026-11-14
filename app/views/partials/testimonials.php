<?php
/** Témoignages publiés (jamais inventés : section masquée tant qu'il n'y en a pas). */
try { $tm = db()->all("SELECT name, commune, body FROM testimonials WHERE status = 'published' AND consent = 1 ORDER BY id DESC LIMIT 6"); } catch (Throwable) { $tm = []; }
if (!$tm) return;
?>
<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Ils en parlent</p><h2>La parole aux voisins</h2></div></div>
    <div class="testis">
      <?php foreach ($tm as $k => $t): ?>
        <figure class="testi" data-reveal style="--i:<?= $k % 3 ?>">
          <blockquote>« <?= e($t['body']) ?> »</blockquote>
          <figcaption><b><?= e($t['name']) ?></b><?= $t['commune'] ? ' · ' . e($t['commune']) : '' ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
