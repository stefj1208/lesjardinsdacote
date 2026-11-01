<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <h1>Plus on est de voisins, moins les graines coûtent cher.</h1>
      <p class="lede">On achète ensemble les sacs professionnels de semences bio, nos bénévoles pèsent et ensachent, et chaque palier débloqué fait baisser le prix pour tout le monde — y compris pour ceux qui se sont engagés les premiers.</p>
      <div class="row mt-6">
        <a class="btn btn--lg" href="/campagnes">Voir les campagnes en cours</a>
        <a class="btn btn--ghost btn--lg" href="/comment-ca-marche">Comment ça marche</a>
      </div>
      <?php if ($stats['neighbours'] > 0): ?>
      <p class="muted mt-6 small"><?= $stats['neighbours'] ?> voisins engagés · <?= $stats['campaigns'] ?> campagnes ouvertes<?= $stats['saved'] > 1 ? ' · ' . money($stats['saved']) . ' économisés ensemble' : '' ?></p>
      <?php endif; ?>
    </div>

    <?php if ($featured): $st = $featured['state']; ?>
    <a href="/campagne/<?= e($featured['slug']) ?>" style="text-decoration:none;display:block" aria-label="Campagne <?= e($featured['title']) ?>">
      <div class="slate" data-camp-live="<?= (int)$featured['id'] ?>">
        <span class="pin" aria-hidden="true"></span>
        <div class="hero-slate">
          <div>
            <h3><?= e($featured['title']) ?></h3>
            <div class="chalk now" style="font-size:3.4rem;color:var(--chalk-yellow)" data-live="pricePortion"><?= money(Pricing::packetPrice($st['price'], (float)$featured['ref_portion'])) ?></div>
            <div class="small" style="color:var(--chalk-dim)">les <?= e(Units::qty($featured['ref_portion'], $featured['unit'])) ?><?= $st['discount_pct'] > 0 ? ' · −' . $st['discount_pct'] . ' % sur le prix public' : '' ?></div>
          </div>
          <?php partial('partials/plant', ['state' => $st['plant_state'], 'growth' => $st['growth'], 'toT1' => $st['to_t1_pct'] / 100, 'size' => 'md']) ?>
        </div>
        <div class="gauge mt-4" style="--p:<?= $st['validated'] ? $st['to_next_pct'] : $st['to_t1_pct'] ?>"><span></span></div>
        <div class="gauge-legend">
          <span class="chalk" style="font-size:1.3rem;color:var(--chalk-yellow)" data-live="noteText"><?= $st['next_tier'] ? 'Plus que ' . $st['parts_missing'] . ' ' . plural($st['parts_missing'], 'part', 'parts') . ($st['tier'] === 0 ? ' pour valider !' : ' pour le palier ' . $st['next_tier'] . ' !') : 'Prix plancher débloqué !' ?></span>
          <span>Reste <?= remaining_fr($featured['end_at']) ?></span>
        </div>
      </div>
    </a>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <div>
        <h2>Les bacs du moment</h2>
        <p>Chaque bac est une commande groupée. Quand il atteint son premier palier, la commande est garantie.</p>
      </div>
      <a class="btn btn--ghost" href="/campagnes">Toutes les campagnes</a>
    </div>
    <?php if ($campaigns): ?>
      <div class="crates"><?php foreach ($campaigns as $c) partial('partials/crate', ['c' => $c]); ?></div>
    <?php else: ?>
      <div class="empty"><h3>La prochaine campagne se prépare</h3><p>Les bénévoles finalisent la commande auprès des fournisseurs. Créez un compte pour être prévenu de l'ouverture.</p><a class="btn" href="/inscription">Créer mon compte</a></div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="background:var(--white);border-block:1px solid var(--line)">
  <div class="wrap">
    <h2 class="mb-6">Du sac de 1 kg à votre sachet de 10 g</h2>
    <div class="steps">
      <div class="step"><h3>Vous vous engagez</h3><p>Choisissez vos sachets et enregistrez votre carte. Rien n'est débité tant que la campagne n'est pas clôturée.</p></div>
      <div class="step"><h3>La plante pousse</h3><p>Chaque voisin qui rejoint fait grandir la plante. À chaque palier, l'association peut acheter un plus gros sac : le prix baisse pour tous.</p></div>
      <div class="step"><h3>On pèse, on ensache, vous retirez</h3><p>Au prix le plus bas atteint. Les bénévoles préparent vos sachets et vous recevez un QR code pour le retrait au 17 boulevard d'Arcole.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <h2>Où va chaque euro</h2>
      <p>Pas de marge cachée. Sur un sachet, la plus grosse part revient au semencier. La petite contribution de l'association finance les sachets kraft, la balance de précision, le local et l'assurance.</p>
      <div class="transparency" aria-label="Exemple de décomposition du prix">
        <div class="bar">
          <span style="flex:66;background:var(--forest)">Semencier</span>
          <span style="flex:8;background:var(--wood)">Port</span>
          <span style="flex:11;background:#6B7C80">Sachet</span>
          <span style="flex:15;background:var(--slate)">Asso</span>
        </div>
        <p class="small muted">Exemple pour une campagne au palier 2. Le détail exact figure sur chaque fiche.</p>
      </div>
    </div>
    <div class="slate">
      <h3 class="chalk" style="font-size:2rem;color:var(--chalk-yellow)">On cherche des mains vertes</h3>
      <p>Une matinée par mois suffit : pesée et ensachage, déchargement de palettes, permanences de distribution. C'est ce qui permet d'acheter en gros sans exploser les coûts.</p>
      <a class="btn btn--chalk" href="/devenir-benevole">Rejoindre les bénévoles</a>
    </div>
  </div>
</section>
