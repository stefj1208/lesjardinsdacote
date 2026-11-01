<?php
$st = $c['state'];
$portions = Units::portions($c['portion_options']);
$defaultPortion = in_array((float)$c['ref_portion'], $portions, false) ? (float)$c['ref_portion'] : $portions[0];
$open = Campaigns::isOpen($c);
$notStarted = $c['status'] === 'active' && strtotime($c['start_at']) > time();
$months = array_filter(array_map('intval', explode(',', (string)$c['sowing_months'])));
$catLabel = Units::CATEGORIES[$c['category']]['label'] ?? '';

// Transparence du prix (par part de référence)
$ref = (float)$c['ref_portion'];
$price = $st['price'] * $ref;
$vatPart = $price - $price / (1 + (float)$c['vat_rate']);
$ht = $price - $vatPart;
$asso = $ht - $ht / (1 + (float)$c['margin_rate']);
$base = $ht - $asso;
$packetPart = (float)$c['packet_cost'];
$tierRow = $st['tier'] > 0 ? $c['tiers'][$st['tier'] - 1] : ($c['tiers'][0] ?? null);
$shipPart = $tierRow && (float)$tierRow['min_qty'] > 0 ? (float)$c['shipping_share_ht'] / (float)$tierRow['min_qty'] * $ref : 0;
$seedPart = max(0, $base - $packetPart - $shipPart);
$split = array_filter([
    ['Semencier', $seedPart, 'var(--forest)'], ['Transport', $shipPart, 'var(--wood)'], ['Sachet kraft', $packetPart, '#6B7C80'],
    ['Association', $asso, 'var(--slate)'], ['TVA', $vatPart, '#9AA5A8'],
], fn($x) => $x[1] > 0.004);
$splitTotal = array_sum(array_column($split, 1)) ?: 1;
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Fil d'Ariane"><a href="/campagnes">Campagnes</a> / <?= e($catLabel) ?> / <?= e($c['title']) ?></nav>

  <?php if ($c['status'] === 'draft'): ?><div class="notice mb-6">Brouillon : cette campagne n'est visible que par les administrateurs.</div><?php endif; ?>

  <div class="camp-grid">
    <div>
      <header class="camp-head">
        <h1><?= e($c['title']) ?></h1>
        <?php if ($c['latin_name']): ?><div class="latin"><?= e($c['latin_name']) ?><?= $c['family'] ? ' — ' . e($c['family']) : '' ?></div><?php endif; ?>
        <div class="chips">
          <?php if ($c['is_organic']): ?><span class="chip chip--bio">Agriculture biologique</span><?php endif; ?>
          <?php if ($c['difficulty']): ?><span class="chip"><?= e(Units::DIFFICULTY[$c['difficulty']]) ?></span><?php endif; ?>
          <?php if ($c['exposure']): ?><span class="chip"><?= e(Units::EXPOSURE[$c['exposure']]) ?></span><?php endif; ?>
          <?php if ($c['supplier_name']): ?><span class="chip">Fournisseur : <?= e(preg_replace('/\s*\(démo\)/', '', $c['supplier_name'])) ?></span><?php endif; ?>
        </div>
      </header>

      <div class="camp-visual">
        <div class="camp-photo<?= $c['image_url'] ? '' : ' camp-photo--placeholder' ?>">
          <?php if ($c['image_url']): ?><img src="<?= e($c['image_url']) ?>" alt="<?= e($c['title']) ?>" loading="lazy">
          <?php else: ?><svg viewBox="0 0 100 120" aria-hidden="true"><rect x="12" y="10" width="76" height="100" rx="4" fill="#E6D5B8" stroke="#8B5A2B" stroke-width="2"/><path d="M12 30 h76" stroke="#8B5A2B" stroke-width="2" stroke-dasharray="4 3"/><circle cx="50" cy="66" r="18" fill="#FAF6F0"/><path d="M50 78 V60 M50 66 c-8 0 -12 -5 -12 -11 c8 0 12 5 12 11z M50 62 c0 -7 5 -11 12 -11 c0 7 -5 11 -12 11z" fill="#22C55E" stroke="#1F4E3D" stroke-width="1.5"/><text x="50" y="100" font-size="9" text-anchor="middle" fill="#5E3B1A" font-family="Georgia">sachet kraft</text></svg><?php endif; ?>
        </div>
        <div>
          <?php partial('partials/plant', ['state' => $st['plant_state'], 'growth' => $st['growth'], 'toT1' => $st['to_t1_pct'] / 100, 'size' => 'lg', 'can' => true]) ?>
        </div>
      </div>

      <?php if ($c['pitch']): ?><p style="font-family:var(--f-title);font-size:1.3rem;font-style:italic"><?= e($c['pitch']) ?></p><?php endif; ?>
      <?php if ($c['description']): ?><div class="prose"><?= nl2p($c['description']) ?></div><?php endif; ?>

      <div class="facts">
        <?php if ($c['sowing_depth']): ?><div class="fact"><b>Profondeur de semis</b><span><?= e($c['sowing_depth']) ?></span></div><?php endif; ?>
        <?php if ($c['water']): ?><div class="fact"><b>Besoin en eau</b><span><?= e(Units::WATER[$c['water']]) ?></span></div><?php endif; ?>
        <?php if ($c['pmg']): ?><div class="fact"><b>Poids de mille graines</b><span><?= e(num($c['pmg'], 2)) ?> g</span></div><?php endif; ?>
        <?php if ($c['unit'] === 'g' && $c['pmg']): ?><div class="fact"><b>Dans un sachet de <?= e(Units::qty($ref, 'g')) ?></b><span>≈ <?= num(Units::seedsFromGrams($ref, (float)$c['pmg']) ?? 0, 0) ?> graines</span></div><?php endif; ?>
        <?php if ($c['unit'] === 'seeds' && $c['pmg']): ?><div class="fact"><b><?= num($ref, 0) ?> graines pèsent</b><span>≈ <?= e(num(Units::gramsFromSeeds($ref, (float)$c['pmg']) ?? 0, 2)) ?> g</span></div><?php endif; ?>
        <?php if ($c['supplier_lot']): ?><div class="fact"><b>Lot d'origine</b><span><?= e($c['supplier_lot']) ?></span></div><?php endif; ?>
      </div>

      <?php if ($months): ?>
      <h2 class="mt-6" style="font-size:1.3rem">Calendrier de semis</h2>
      <div class="sow-cal" role="list" aria-label="Mois de semis">
        <?php for ($m = 1; $m <= 12; $m++): $on = in_array($m, $months, true); ?>
          <span role="listitem" class="<?= $on ? 'on' : '' ?><?= $m === (int)date('n') ? ' now' : '' ?>" title="<?= ucfirst(FR_MONTHS[$m]) . ($on ? ' : période de semis' : '') ?>"><?= mb_substr(ucfirst(FR_MONTHS[$m]), 0, 1) ?></span>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php if ($c['sowing_advice']): ?><div class="prose mt-4"><h3>Conseils de semis</h3><?= nl2p($c['sowing_advice']) ?></div><?php endif; ?>

      <div class="panel mt-6 price-split">
        <h2>Où va votre argent</h2>
        <p class="muted small">Décomposition du prix d'un sachet de <?= e(Units::qty($ref, $c['unit'])) ?> au palier <?= max(1, $st['tier']) ?>.</p>
        <div class="bar" role="img" aria-label="Décomposition du prix">
          <?php foreach ($split as [$l, $v, $col]): ?><span style="flex:<?= round($v / $splitTotal * 100, 2) ?>;background:<?= $col ?>"><?= $v / $splitTotal > .14 ? e($l) : '' ?></span><?php endforeach; ?>
        </div>
        <ul><?php foreach ($split as [$l, $v, $col]): ?><li><span><i style="background:<?= $col ?>"></i><?= e($l) ?></span><span><?= money($v) ?></span></li><?php endforeach; ?></ul>
        <?php if ((float)$c['vat_rate'] == 0.0): ?><p class="small muted mt-4 mb-0"><?= e(setting('vat_mention')) ?>.</p><?php endif; ?>
      </div>

      <?php if ($batch && $c['trigger_mode'] === 'franco'): ?>
      <div class="panel mt-6">
        <h2>Panier commun <?= e(preg_replace('/\s*\(démo\)/', '', (string)$c['supplier_name'])) ?></h2>
        <p class="muted">Cette campagne fait partie d'une commande groupée validée dès que l'ensemble atteint le franco de port de <?= money($batch['franco_ht']) ?> HT.</p>
        <div class="gauge gauge--light" style="--p:<?= $batch['pct'] ?>"><span></span></div>
        <div class="gauge-legend"><span><?= money($batch['total_ht']) ?> HT réservés</span><span><?= $batch['pct'] ?> %</span></div>
      </div>
      <?php endif; ?>

      <div class="panel mt-6">
        <h2>Le calendrier de cette campagne</h2>
        <dl class="dl">
          <dt>Clôture des engagements</dt><dd><?= e(date_fr($c['end_at'], true, true)) ?></dd>
          <dt>Prélèvement</dt><dd>À la clôture, au prix du dernier palier atteint</dd>
          <dt>Retrait</dt><dd><?= $c['window_label'] ? e(time_range_fr($c['window_starts'], $c['window_ends'])) . ' — ' . e($c['window_location']) : 'Date communiquée à la validation' ?></dd>
          <?php if ($c['window_address']): ?><dt>Adresse</dt><dd><?= e($c['window_address']) ?></dd><?php endif; ?>
        </dl>
      </div>
    </div>

    <aside class="sticky-col" id="commander">
      <div class="slate" data-camp-live="<?= (int)$c['id'] ?>">
        <?php partial('partials/tiers_board', ['c' => $c]) ?>
        <div class="gauge mt-4" style="--p:<?= $st['validated'] ? $st['to_next_pct'] : $st['to_t1_pct'] ?>" data-live="gauge"><span></span></div>
        <div class="countdown">
          <span><span data-live="participants"><?= $c['participants'] ?></span> <?= plural($c['participants'], 'voisin engagé', 'voisins engagés') ?></span>
          <span><?= $notStarted ? 'Ouvre le ' . e(date_fr($c['start_at'])) : ($open ? 'Fin dans <strong data-countdown="' . strtotime($c['end_at']) . '">' . e(remaining_fr($c['end_at'])) . '</strong>' : '<strong>' . e(Units::STATUSES[$c['status']]) . '</strong>') ?></span>
        </div>
      </div>

      <?php if ($open): ?>
      <form class="order-box" method="post" action="/panier/ajouter" data-order-form>
        <?= csrf_field() ?>
        <input type="hidden" name="campaign_id" value="<?= (int)$c['id'] ?>">
        <h2>Je participe</h2>
        <fieldset style="border:0;padding:0;margin:0">
          <legend class="label" style="margin-bottom:8px">Contenance du sachet</legend>
          <div class="portion-pick">
            <?php foreach ($portions as $p): $pp = Pricing::packetPrice($st['price'], $p); ?>
              <label><input type="radio" name="portion" value="<?= e((string)$p) ?>"<?= $p == $defaultPortion ? ' checked' : '' ?>>
                <span><?= e(Units::qty($p, $c['unit'])) ?><small data-portion-price="<?= e((string)$p) ?>"><?= money($pp) ?></small></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="row row--between">
          <label class="label" for="packets">Nombre de sachets</label>
          <div class="stepper">
            <button type="button" data-step="-1" aria-label="Un sachet de moins">−</button>
            <input id="packets" name="packets" type="number" inputmode="numeric" min="1" max="<?= (int)$c['max_packets_per_line'] ?>" value="1">
            <button type="button" data-step="1" aria-label="Un sachet de plus">+</button>
          </div>
        </div>
        <div class="order-sum">
          <div class="line"><span>Quantité</span><span data-sum="qty">—</span></div>
          <div class="line total"><span>Au prix actuel</span><span data-sum="total">—</span></div>
          <div class="line small muted"><span>Si le palier suivant est atteint</span><span data-sum="next">—</span></div>
        </div>
        <p class="impact" data-impact aria-live="polite">Ajustez votre quantité : la plante vous montre l'effet de votre commande.</p>
        <button class="btn btn--go btn--block btn--lg" type="submit">Participer à la récolte</button>
        <p class="small muted mt-4 mb-0">Aucun débit aujourd'hui. Vous ne paierez que le prix du dernier palier atteint, et rien du tout si la campagne n'est pas validée.</p>
      </form>
      <?php elseif ($notStarted): ?>
        <div class="panel"><h2>Bientôt ouverte</h2><p>Les engagements ouvrent le <?= e(date_fr($c['start_at'], true)) ?>.</p></div>
      <?php else: ?>
        <div class="panel"><h2>Campagne clôturée</h2><p><?= $c['status'] === 'failed' ? 'Le palier minimum n\'a pas été atteint : aucun participant n\'a été débité.' : 'Merci aux ' . $c['participants'] . ' participants ! Les bénévoles préparent les sachets.' ?></p><a class="btn btn--ghost" href="/campagnes">Voir les campagnes ouvertes</a></div>
      <?php endif; ?>

      <div class="panel">
        <h2 style="font-size:1.15rem">Faites pousser la plante</h2>
        <p class="small muted">Chaque voisin qui rejoint fait baisser votre propre prix.</p>
        <?php
          $shareText = $c['title'] . ' en achat groupé : ' . money(Pricing::packetPrice($st['price'], $ref)) . ' les ' . Units::qty($ref, $c['unit'])
            . ($st['next_tier'] ? '. Plus que ' . $st['parts_missing'] . ' ' . plural($st['parts_missing'], 'part', 'parts') . ' pour faire baisser le prix pour tout le monde !' : ' — prix plancher atteint !');
          $shareUrl = abs_url('/campagne/' . $c['slug']);
        ?>
        <div class="share-row">
          <a class="btn btn--wa btn--sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>">Partager sur WhatsApp</a>
          <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>">Facebook</a>
          <button class="btn btn--ghost btn--sm" type="button" data-share data-share-text="<?= e($shareText) ?>" data-share-url="<?= e($shareUrl) ?>">Copier le lien</button>
        </div>
      </div>
    </aside>
  </div>

  <?php if ($similar): ?>
  <section class="section">
    <h2 class="mb-6">Dans le même rayon</h2>
    <div class="crates"><?php foreach ($similar as $s) partial('partials/crate', ['c' => $s]); ?></div>
  </section>
  <?php endif; ?>
</div>
<?php if ($open): ?>
<a class="mobile-cta" href="#commander"><span><strong data-live="pricePortion"><?= money(Pricing::packetPrice($st['price'], $ref)) ?></strong> les <?= e(Units::qty($ref, $c['unit'])) ?></span><span class="btn btn--go btn--sm">Je participe</span></a>
<?php endif; ?>
<script type="application/json" id="camp-data"><?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
