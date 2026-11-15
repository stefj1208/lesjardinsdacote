<?php /** @var int $count @var ?string $pdf */ ?>
<section class="page-hero">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start">Mairies · CCAS · entreprises · bailleurs · jardins partagés</p>
      <h1>Faites jardiner votre commune, sans budget ni logistique.</h1>
      <p class="lede">« Les Jardins d'à Côté » est une association loi 1901 qui organise des achats groupés entre voisins : graines bio, plants potagers, arbres fruitiers, aromatiques. Les habitants achètent au prix coûtant, des bénévoles préparent les packs. Vous prêtez un lieu et relayez l'information : nous faisons tout le reste.</p>
      <div class="row mt-3">
        <a class="btn" href="/lancer-un-groupe">Proposer un groupe dans ma commune <span class="arr" aria-hidden="true">→</span></a>
        <?php if ($pdf): ?><a class="btn btn--ghost" href="<?= e($pdf) ?>" download>Télécharger la plaquette (PDF)</a><?php endif; ?>
      </div>
    </div>
    <div class="photo-stack reveal-img" data-reveal="right"><?= ph('commons:Raised_bed.jpg|commons:Vegetable_garden_detail.jpg', 'Potager partagé', '', 1400, true) ?></div>
  </div>
</section>

<section class="section eco" style="padding-top:1rem">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Ce que votre territoire y gagne</p><h2>Une action concrète pour la biodiversité et le lien social</h2></div></div>
    <div class="eco-grid">
      <article class="eco-card" data-reveal><h3>Biodiversité cultivée</h3><p>Pour la campagne de graines en cours : <?= (int)$count ?> variétés bio et reproductibles, dont des fleurs mellifères. Une réponse simple aux objectifs de votre plan climat, de votre atlas de la biodiversité ou de votre démarche « Territoire engagé pour la nature ».</p></article>
      <article class="eco-card" data-reveal style="--i:1"><h3>Pouvoir d'achat</h3><p>Graines, plants et arbres sont achetés en gros et revendus au prix coûtant. L'association ne prend aucune marge : seulement <?= eur(Asso::fee()) ?> de frais par commande.</p></article>
      <article class="eco-card" data-reveal style="--i:2"><h3>Lien social</h3><p>Le vote des variétés, l'atelier de mise en petits sachets et la distribution réunissent voisins, bénévoles et jardiniers débutants autour d'un projet commun.</p></article>
      <article class="eco-card" data-reveal style="--i:3"><h3>Des arbres plantés</h3><p><?= eur(Asso::fundShare()) ?> par commande alimente un fonds de plantation d'arbres, dont les comptes sont publics. Une plantation peut être organisée sur votre commune.</p></article>
      <article class="eco-card" data-reveal style="--i:4"><h3>Zéro coût, zéro stock</h3><p>Pas de subvention demandée, pas de régie, pas de caisse : les habitants paient en ligne, l'association commande uniquement ce qui est acheté.</p></article>
      <article class="eco-card" data-reveal style="--i:5"><h3>Transparence</h3><p>Prix d'achat, frais et fonds plantations sont affichés à l'euro près. Vos habitants savent exactement où va leur argent.</p></article>
    </div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Votre rôle</p><h2>Trois gestes, et c'est lancé</h2></div></div>
    <ol class="steps3 steps3--wide">
      <li><b>1</b> Vous proposez un groupe : cinq minutes sur le formulaire, sans engagement.</li>
      <li><b>2</b> Vous mettez à disposition un lieu deux à trois demi-journées par campagne : l'atelier de mise en sachets et la distribution (salle, hall, jardin partagé).</li>
      <li><b>3</b> Vous relayez dans vos canaux : bulletin municipal, panneau lumineux, intranet, affichage. Nous fournissons textes et visuels.</li>
    </ol>
    <div class="panel mt-3"><h3>Bénévoles recherchés dans chaque commune</h3>
      <p class="muted">Quelques heures par saison, sans compétence particulière : <b>mise en petits sachets</b> des graines reçues en grands conditionnements, <b>réception</b> des livraisons, <b>permanence de distribution</b>, <b>référent de groupe</b>, relais de quartier, jardinier conseil.</p></div>
    <div class="row mt-3"><a class="btn btn--navy" href="/lancer-un-groupe">Proposer un groupe <span class="arr" aria-hidden="true">→</span></a><a class="btn btn--ghost" href="/univers/graines">Voir les <?= (int)$count ?> variétés de la saison</a><a class="btn btn--ghost" href="/association">L'association et ses comptes</a></div>
  </div>
</section>
