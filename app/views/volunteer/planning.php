<?php $types = ['' => 'Tous', 'packing' => 'Pesée & ensachage', 'unloading' => 'Réception', 'distribution' => 'Distribution', 'other' => 'Autre']; ?>
<div class="wrap page" style="max-width:860px">
  <p class="crumbs"><a href="/benevoles">Espace bénévoles</a> / Planning</p>
  <h1>Planning des chantiers participatifs</h1>
  <div class="filter-cats mb-6" style="margin-bottom:var(--s-5)">
    <?php foreach ($types as $k => $l): ?><a class="btn btn--sm <?= $type === $k ? '' : 'btn--ghost' ?>" href="?type=<?= e($k) ?>"><?= e($l) ?></a><?php endforeach; ?>
  </div>
  <?php if (!$shifts): ?><div class="empty"><h3>Aucun créneau prévu</h3><p>Les ateliers sont créés à la clôture des campagnes.</p></div><?php endif; ?>
  <?php foreach ($shifts as $s): $full = (int)$s['signed'] >= (int)$s['volunteers_needed']; $t = strtotime($s['starts_at']); ?>
  <div class="shift<?= $s['mine'] ? ' is-mine' : '' ?>">
    <div class="shift-date"><small><?= e(FR_DAYS[(int)date('w', $t)]) ?></small><b><?= date('j', $t) ?></b><small><?= e(FR_MONTHS_SHORT[(int)date('n', $t)]) ?></small></div>
    <div>
      <strong style="font-size:1.1rem"><?= e($s['title']) ?></strong>
      <div><?= date('G\hi', $t) ?> – <?= date('G\hi', strtotime($s['ends_at'])) ?> · <?= e($s['location']) ?></div>
      <div class="small muted"><?= (int)$s['signed'] ?>/<?= (int)$s['volunteers_needed'] ?> inscrits<?= $s['names'] ? ' : ' . e($s['names']) : '' ?></div>
      <div class="gauge gauge--light mt-4" style="--p:<?= min(100, (int)$s['signed'] / max(1, (int)$s['volunteers_needed']) * 100) ?>;height:8px;max-width:220px"><span></span></div>
      <?php if ($s['description']): ?><p class="small mt-4 mb-0"><?= e($s['description']) ?></p><?php endif; ?>
    </div>
    <div class="shift-actions" style="display:grid;gap:6px">
      <form method="post" action="/benevoles/planning/<?= (int)$s['id'] ?>"><?= csrf_field() ?>
        <?php if ($s['mine']): ?><button class="btn btn--ghost" type="submit">Me désinscrire</button>
        <?php else: ?><button class="btn <?= $full ? 'btn--ghost' : 'btn--go' ?>" type="submit"><?= $full ? 'Venir en renfort' : 'Je m\'inscris' ?></button><?php endif; ?>
      </form>
      <?php if ($s['mine']): ?><a class="small" href="/benevoles/planning/<?= (int)$s['id'] ?>/ics">Ajouter à mon agenda</a><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
