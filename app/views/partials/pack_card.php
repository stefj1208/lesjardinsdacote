<?php
/** @var array $p  @var string $mode 'show'|'vote'|'result'  @var ?int $myVote  @var ?array $sale */
$mode = $mode ?? 'show';
$votedThis = isset($myVote) && $myVote === (int)$p['id'];
$isWinner = !empty($sale['winning_pack_id']) && (int)$sale['winning_pack_id'] === (int)$p['id'];
$maxItems = $maxItems ?? 5;
?>
<article class="pack<?= !empty($wide) ? ' pack--wide' : '' ?><?= $votedThis ? ' is-voted' : '' ?><?= $isWinner ? ' is-winner' : '' ?>" id="pack-<?= (int)$p['id'] ?>" data-reveal style="--i:<?= (int)($i ?? 0) ?>">
  <div class="pack-photo">
    <?= ph($p['photo'], $p['name'], '', 900) ?>
    <span class="chip"><?= e(Cat::label($p['category'])) ?></span>
    <?php partial('partials/save_badge', ['p' => $p]) ?>
    <span class="pack-count"><?= e($p['summary']) ?></span>
  </div>
  <div class="pack-body">
    <div>
      <?php if ($isWinner): ?><span class="status status--tomato" style="margin-bottom:.6rem">Pack choisi par le groupe</span><?php endif; ?>
      <h3><a href="/packs/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
      <?php if ($p['tagline']): ?><p class="tagline mt-1"><?= e($p['tagline']) ?></p><?php endif; ?>
    </div>
    <div class="pack-price"><?php partial('partials/price', ['p' => $p]) ?></div>
    <ul class="items">
      <?php foreach (array_slice($p['items'], 0, $maxItems) as $it): ?>
        <li class="item"><?= ph($it['photo'], $it['name'], '', 200) ?><span><b><?= e($it['variety'] ?: $it['name']) ?></b><small><?= e($it['name']) ?></small></span><span class="qty"><?= e(Cat::qty((int)$it['quantity'], $it['unit'])) ?></span></li>
      <?php endforeach; ?>
      <?php if (count($p['items']) > $maxItems): ?><li class="items-more"><a href="/packs/<?= e($p['slug']) ?>">+ <?= count($p['items']) - $maxItems ?> autres variétés : voir tout le pack <span aria-hidden="true">→</span></a></li><?php endif; ?>
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
