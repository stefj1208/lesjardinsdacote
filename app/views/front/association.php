<?php /** @var array $stats */ $mem = Asso::membershipFee(); ?>
<section class="page-hero asso-hero">
  <div class="wrap split split--center">
    <div>
      <p class="eyebrow eyebrow--start rise" style="--i:0">Association loi 1901</p>
      <h1 class="rise" style="--i:1">Une association de voisins, <em>pas un magasin.</em></h1>
      <p class="lede rise" style="--i:2">Les Jardins d'à Côté fait jardiner plus de monde, pour moins cher, avec des variétés qui font du bien à la biodiversité. Nous achetons <strong>en votre nom</strong>, au prix coûtant. Nos frais fixes financent la logistique et la plantation d'arbres.</p>
      <div class="row rise" style="--i:3"><a class="btn" href="/groupes" data-sprout>Trouver mon groupe <span class="arr" aria-hidden="true">→</span></a><a class="btn btn--ghost" href="/cgv">Lire les conditions</a></div>
    </div>
    <div class="fund-card rise" style="--i:2">
      <span class="fund-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M24 44V26"/><path class="grow-leaf" d="M24 30c-7 0-11-4-11-10 6 0 11 4 11 10z"/><path class="grow-leaf grow-leaf--2" d="M24 26c0-8 5-13 13-13 0 8-5 13-13 13z"/></svg></span>
      <b data-count="<?= e(number_format($stats['fund'], 2, '.', '')) ?>" data-decimals="0" data-suffix=" €"><?= eur($stats['fund']) ?></b>
      <span>réunis pour planter des arbres, grâce aux commandes des membres</span>
      <div class="fund-mini"><span><b><?= (int)$stats['members'] ?></b> membres en <?= Asso::year() ?></span><span><b><?= eur($stats['saved']) ?></b> économisés</span></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Notre objet</p><h2>Ce pour quoi l'association existe</h2></div></div>
    <div class="pillars">
      <div class="pillar" data-reveal style="--i:0"><span class="pillar-n">01</span><h3>Le jardin pour tous</h3><p>Rendre le potager, le verger et les aromatiques accessibles à tous, quels que soient l'âge, les revenus et le lieu de vie.</p></div>
      <div class="pillar" data-reveal style="--i:1"><span class="pillar-n">02</span><h3>La biodiversité</h3><p>Diffuser des variétés locales, anciennes et rares, et des pratiques de jardinage qui respectent le vivant.</p></div>
      <div class="pillar" data-reveal style="--i:2"><span class="pillar-n">03</span><h3>Le lien entre voisins</h3><p>Des groupes par commune, des bénévoles, des distributions où l'on se rencontre et où l'on échange des conseils.</p></div>
      <div class="pillar" data-reveal style="--i:3"><span class="pillar-n">04</span><h3>Planter des arbres</h3><p>Financer et réaliser des plantations d'arbres, de haies et de vergers, en France et à l'étranger, et du matériel agricole à usage collectif.</p></div>
      <div class="pillar" data-reveal style="--i:4"><span class="pillar-n">05</span><h3>Essaimer</h3><p>Partager librement nos outils et nos méthodes, et accompagner d'autres collectifs qui veulent reproduire le modèle.</p></div>
    </div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start">Où va votre argent</p>
      <h2>Chaque euro a une destination, affichée avant de payer</h2>
      <p>Les produits sont refacturés au centime près : l'association ne prend <strong>aucune marge</strong> dessus. Elle se finance par des frais fixes de <strong><?= eur(Asso::fee()) ?> par commande</strong>, quel que soit le nombre de packs, dont <strong><?= eur(Asso::fundShare()) ?></strong> pour les plantations. L'adhésion annuelle est <?= $mem > 0 ? 'de ' . eur($mem) : 'offerte' ?>.</p>
    </div>
    <?php partial('partials/money_split') ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Nos engagements</p><h2>Ce que vous pouvez vérifier</h2></div></div>
    <div class="promise-grid">
      <?php foreach ([
        ['Prix coûtant', 'Le prix du pack est le coût réel des produits. S\'il est plus bas que prévu, l\'écart revient aux membres.'],
        ['Rien avant le lot complet', 'Votre carte est enregistrée, pas débitée. Si le lot de 10 n\'est pas atteint, tout est annulé, frais compris.'],
        ['Des comparaisons honnêtes', 'Mêmes variétés, à l\'unité, prix publics datés, frais compris, sans citer aucune enseigne.'],
        ['Des comptes publics', 'Chaque année : montant du fonds plantations, projets financés, arbres plantés et taux de reprise.'],
        ['Des bénévoles, pas de salaires cachés', 'Les dirigeants sont bénévoles. Les frais paient la logistique, pas des marges.'],
        ['Un modèle ouvert', 'Notre outil et nos méthodes sont partagés librement avec les collectifs qui veulent essaimer.'],
      ] as $k => [$t, $d]): ?>
        <div class="promise" data-reveal style="--i:<?= $k % 3 ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg><div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--ink" id="statuts">
  <div class="wrap split">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start eyebrow--light">Gouvernance</p>
      <h2>Qui décide ?</h2>
      <p class="lede">Les membres, réunis chaque année en assemblée générale : une personne, une voix. Ils élisent le conseil d'administration, approuvent les comptes et votent l'usage du fonds plantations.</p>
      <p class="small" style="color:#B8C3D3">Les statuts, le règlement intérieur et les comptes sont communiqués à tout membre sur simple demande à <a href="mailto:<?= e(setting('contact_email')) ?>" style="color:#fff"><?= e(setting('contact_email')) ?></a>.</p>
    </div>
    <dl class="gov" data-reveal="right">
      <?php if (Asso::president()): ?><div><dt>Président</dt><dd><?= e(Asso::president()) ?></dd></div><?php endif; ?>
      <?php if (Asso::treasurer()): ?><div><dt>Trésorière</dt><dd><?= e(Asso::treasurer()) ?></dd></div><?php endif; ?>
      <div><dt>Statut</dt><dd><?= e(setting('association_legal')) ?></dd></div>
      <div><dt>Siège</dt><dd><?= e(setting('association_address')) ?></dd></div>
    </dl>
  </div>
</section>

<section class="section">
  <div class="wrap asso-cta" data-reveal>
    <div><h2>Envie d'en être ?</h2><p class="lede mb-0">Rejoignez le groupe de votre commune, ou lancez-le avec un voisin : il suffit de deux bénévoles.</p></div>
    <div class="row"><a class="btn btn--accent btn--lg" href="/groupes" data-sprout>Trouver mon groupe</a><a class="btn btn--ghost btn--lg" href="/lancer-un-groupe">Lancer un groupe</a></div>
  </div>
</section>
