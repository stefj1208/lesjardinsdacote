<?php /** @var array $rows */ ?>
<h1>Témoignages</h1>
<p class="muted">Les membres laissent un avis depuis leur compte après un retrait. Rien n'est publié sur le site sans votre accord, et seuls les avis dont l'auteur a accepté la publication peuvent l'être.</p>
<?php if (!$rows): ?><div class="panel"><p class="mb-0">Aucun témoignage pour l'instant. La section « Ils en parlent » de la page d'accueil reste masquée tant qu'aucun avis n'est publié.</p></div><?php endif; ?>
<?php foreach ($rows as $t): ?>
  <div class="panel mt-2 testi-admin">
    <div class="vitem-head"><span class="chip"><?= ['pending' => 'À décider', 'published' => 'Publié', 'hidden' => 'Masqué'][$t['status']] ?></span><b><?= e($t['name']) ?></b><small class="muted"><?= e((string)$t['commune']) ?> · <?= e(date_fr($t['created_at'])) ?></small></div>
    <p class="mt-2">« <?= e($t['body']) ?> »</p>
    <div class="row">
      <?php if ($t['status'] !== 'published' && $t['consent']): ?><form method="post" action="/admin/temoignages/<?= (int)$t['id'] ?>"><?= csrf_field() ?><button class="btn btn--sm" name="status" value="published">Publier</button></form><?php endif; ?>
      <?php if (!$t['consent']): ?><span class="small muted">L'auteur n'a pas accepté la publication.</span><?php endif; ?>
      <?php if ($t['status'] !== 'hidden'): ?><form method="post" action="/admin/temoignages/<?= (int)$t['id'] ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="status" value="hidden">Masquer</button></form><?php endif; ?>
      <form method="post" action="/admin/temoignages/<?= (int)$t['id'] ?>" data-confirm="Supprimer définitivement ce témoignage ?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="delete" value="1">Supprimer</button></form>
    </div>
  </div>
<?php endforeach; ?>
