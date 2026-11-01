<?php
/** @var array $p  @var string $mode 'show'|'vote'|'result'  @var ?int $myVote  @var ?array $sale */
$mode = $mode ?? 'show';
$votedThis = isset($myVote) && $myVote === (int)$p['id'];
$isWinner = !empty($sale['winning_pack_id']) && (int)$sale['winning_pack_id'] === (int)$p['id'];
$maxItems = $maxItems ?? 6;
?>
<article class="pack<?= !empty($wide) ? ' pack--wide' : '' ?><?= $votedThis ? ' is-voted' : '' ?><?= $isWinner ? ' is-winner' : '' ?>" id="pack-<?= (int)$p['id'] ?>">
  <?= ph($p['photo'], $p['name'], '', 900) ?>
  <span class="pack-price"><?= money($p['price']) ?></span>
  <div class="pack-body">
    <div>
      <?php if ($isWinner): ?><span class="status status--tomato mb-2" style="margin-bottom:.6rem">Pack choisi par le groupe</span><?php endif; ?>
      <h3><a href="/packs/<?= e($p['slug']) ?>" style="color:inherit;text-decoration:none"><?= e($p['name']) ?></a></h3>
      <p class="small muted mt-1 mb-0"><?= e($p['summary']) ?></p>
    </div>
    <?php if ($p['tagline']): ?><p class="tagline"><?= e($p['tagline']) ?></p><?php endif; ?>
    <ul class="items">
      <?php foreach (array_slice($p['items'], 0, $maxItems) as $it): ?>
        <li class="item"><?= ph($it['photo'], $it['name'], '', 200) ?><span><b><?= e($it['name']) ?></b><small><?= e($it['variety']) ?></small></span><span class="qty"><?= e(Cat::qty((int)$it['quantity'], $it['unit'])) ?></span></li>
      <?php endforeach; ?>
      <?php if (count($p['items']) > $maxItems): ?><li class="small muted">+ <?= count($p['items']) - $maxItems ?> autres variétés</li><?php endif; ?>
    </ul>
    <?php if ($mode === 'vote' || $mode === 'result'): $tot = max(1, (int)($sale['votes_total'] ?? 0)); $pct = (int)round((int)$p['votes'] / $tot * 100); ?>
      <div class="vote-bar">
        <div class="row row--between"><span><?= (int)$p['votes'] ?> <?= plural((int)$p['votes'], 'voix', 'voix') ?></span><span><?= $pct ?> %</span></div>
        <div class="meter"><span style="--p:<?= $pct ?>"></span></div>
      </div>
    <?php endif; ?>
    <?php if ($mode === 'vote'): ?>
      <form method="post" action="/ventes/<?= (int)$sale['id'] ?>/voter"><?= csrf_field() ?><input type="hidden" name="pack_id" value="<?= (int)$p['id'] ?>">
        <?php if ($votedThis): ?><button class="btn btn--ghost btn--block" type="button" disabled>✓ Votre choix</button>
        <?php else: ?><button class="btn btn--block" type="submit"><?= isset($myVote) && $myVote ? 'Changer pour ce pack' : 'Voter pour ce pack' ?></button><?php endif; ?>
      </form>
    <?php endif; ?>
  </div>
</article>
