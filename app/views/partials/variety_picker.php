<?php
/**
 * Grille des variétés d'une vente.
 * @var array $s  vente hydratée   @var array $myChoices   @var bool $readonly
 */
$readonly = $readonly ?? false;
$proj = $s['projection'];
$inPack = $proj ? array_flip(array_map(fn($it) => (int)$it['variety_id'], $proj['items'])) : [];
$fams = Varieties::families($s['varieties']);
$max = (int)$s['max_choices'];
// Ordre d'affichage : les plus demandées d'abord pendant le vote.
$list = $s['varieties'];
if (!$readonly) usort($list, fn($a, $b) => [-(int)$a['votes'], (int)$a['position']] <=> [-(int)$b['votes'], (int)$b['position']]);
?>
<form class="vpick" id="voter" method="post" action="/ventes/<?= (int)$s['id'] ?>/voter" data-vpick data-max="<?= $max ?>">
  <?= csrf_field() ?>
  <?php if (count($fams) > 1): ?>
    <div class="vfilter" role="group" aria-label="Filtrer par plante">
      <button type="button" class="chip is-on" data-fam="">Toutes (<?= count($s['varieties']) ?>)</button>
      <?php foreach ($fams as $f => $n): ?><button type="button" class="chip" data-fam="<?= e($f) ?>"><?= e($f) ?> (<?= $n ?>)</button><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="vgrid">
    <?php foreach ($list as $k => $v): $on = in_array((int)$v['id'], $myChoices ?? [], true); $inside = isset($inPack[(int)$v['id']]); ?>
      <label class="vcard<?= $on ? ' is-on' : '' ?>" data-family="<?= e($v['family']) ?>" data-reveal style="--i:<?= $k % 4 ?>">
        <?php if (!$readonly): ?><input type="checkbox" name="varieties[]" value="<?= (int)$v['id'] ?>"<?= $on ? ' checked' : '' ?>><?php endif; ?>
        <span class="vcard-photo">
          <?= ph($v['photo'], $v['name'] . ' ' . $v['variety'], '', 500) ?>
          <?php if (!$readonly): ?><span class="vcheck" aria-hidden="true"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M3.5 8.5l3 3 6-7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><?php endif; ?>
          <?php if ($s['status'] === 'voting'): ?><span class="vvotes"><?= (int)$v['votes'] ?> <?= plural((int)$v['votes'], 'voix', 'voix') ?></span><?php endif; ?>
        </span>
        <span class="vcard-body">
          <b><?= e($v['variety'] ?: $v['name']) ?></b>
          <small><?= e($v['name']) ?> · <?= e($v['portion']) ?></small>
          <?php if ($v['note']): ?><span class="vnote"><?= e($v['note']) ?></span><?php endif; ?>
          <span class="vfoot">
            <?php if ($s['status'] === 'voting'): ?><span class="vin<?= $inside ? ' is-in' : '' ?>"><?= $inside ? 'Dans le pack' : 'Hors budget pour l\'instant' ?></span><?php endif; ?>
            <?php if ($v['market_price'] !== null): ?><span class="muted">prix public : <?= eur($v['market_price']) ?></span><?php endif; ?>
          </span>
        </span>
      </label>
    <?php endforeach; ?>
  </div>
  <?php if (!$readonly): ?>
    <div class="vbar">
      <span><strong data-vcount><?= count($myChoices ?? []) ?></strong> / <?= $max ?> variétés choisies</span>
      <button class="btn btn--accent" type="submit"><?= ($myChoices ?? []) ? 'Mettre à jour mes choix' : 'Enregistrer mes choix' ?></button>
    </div>
  <?php endif; ?>
</form>
