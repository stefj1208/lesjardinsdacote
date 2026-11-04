<section class="page-hero">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start">Côté jardinier</p>
      <h1>Comment ça marche</h1>
      <p class="lede">Vous rejoignez le groupe de votre commune, vous choisissez les variétés de votre pack, et vous ne payez que lorsque le groupe a réuni assez d'achats pour commander. Pas d'abonnement, pas de frais d'inscription.</p>
      <div class="row mt-3"><a class="btn btn--lg" href="/groupes">Trouver mon groupe <span class="arr" aria-hidden="true">→</span></a></div>
    </div>
    <div data-reveal="right"><?= ph('commons:Carrot_harvest.jpg|commons:Bunches_of_carrots.jpg', 'Récolte de carottes', 'photo-round', 1200) ?></div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head" data-reveal><div><p class="eyebrow eyebrow--start">Pas à pas</p><h2>Les trois étapes d'un groupe</h2></div></div>
    <?php partial('partials/phases') ?>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start">Sans risque</p>
      <h2>Le paiement par lots de 10</h2>
      <p>Pour commander au meilleur prix, l'association regroupe les achats par lots de 10 packs. Tant que votre lot n'est pas complet, <strong>rien n'est débité</strong> et vous pouvez annuler depuis votre compte.</p>
      <p>Dès que le dixième achat arrive, le lot est commandé : chaque participant est débité de son pack (ou paie au retrait si le paiement en ligne n'est pas activé), et un nouveau lot de 10 s'ouvre pour les suivants.</p>
      <p>Si la vente se termine alors que le dernier lot n'est pas complet, ce lot est annulé : <strong>0 € débité</strong>, et vous êtes prévenu·e par email.</p>
    </div>
    <div class="panel" data-reveal="right">
      <h3>Exemple : vente de Pins-Justaret</h3>
      <dl class="dl mt-2">
        <dt>Lot n° 1</dt><dd>10 / 10 · commandé et débité</dd>
        <dt>Lot n° 2</dt><dd>6 / 10 · en attente, rien de débité</dd>
      </dl>
      <div class="slots mt-3"><?php for ($i = 0; $i < 10; $i++): ?><span class="slot slot--big is-on" style="--i:<?= $i ?>"></span><?php endfor; ?></div>
      <div class="slots mt-2"><?php for ($i = 0; $i < 10; $i++): ?><span class="slot slot--big<?= $i < 6 ? ' is-on' : '' ?>" style="--i:<?= $i ?>"></span><?php endfor; ?></div>
      <p class="small muted mt-2 mb-0">Encore 4 achats et le lot n° 2 part en commande.</p>
    </div>
  </div>
</section>

<section class="section section--ink">
  <div class="wrap split split--center">
    <div data-reveal="left">
      <p class="eyebrow eyebrow--start eyebrow--light">Le prix marché, expliqué</p>
      <h2>Chaque pack affiche ce qu'il vous coûterait à l'unité</h2>
      <p class="lede">Pour chaque variété, nous relevons le prix public d'un sachet ou d'un plant de la même variété, sans citer d'enseigne. Additionnés, ils donnent la valeur du pack à l'unité. On en retire le prix du pack <strong>et les frais de la commande</strong> : ce qui reste, c'est votre économie réelle.</p>
      <p class="source-note" style="color:#93A1B5"><?= e(setting('market_price_note', Packs::DEFAULT_MARKET_NOTE)) ?></p>
    </div>
    <ul class="ticks" data-reveal="right">
      <li>L'association achète directement aux producteurs, au tarif professionnel : plants chez les horticulteurs, arbres chez les pépiniéristes, graines en grand conditionnement chez le semencier bio.</li>
      <li>Les bénévoles préparent les packs : plants étiquetés, arbres en racines nues, sachets d'environ 10 graines par variété.</li>
      <li>Le pack est refacturé <strong>au prix coûtant</strong>, sans marge. L'association se finance par des frais fixes de <?= eur(Asso::fee()) ?> par commande, dont <?= eur(Asso::fundShare()) ?> pour planter des arbres.</li>
    </ul>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <p class="eyebrow eyebrow--start">Vos questions</p>
    <h2>Questions fréquentes</h2>
    <div class="faq mt-3">
      <details open><summary>Que contient un pack ?</summary><div>Chaque pack détaille ses variétés, avec photo, et la quantité exacte : nombre de graines, de plants ou d'arbres. Les graines sont bio et reproductibles, conditionnées par les bénévoles avec le numéro de lot d'origine.</div></details>
      <details><summary>Combien ça coûte ?</summary><div>Le prix du pack (30 € en général) correspond au <strong>coût réel des produits</strong>, payé au producteur sans marge. S'ajoutent <strong><?= eur(Asso::fee()) ?> de frais de fonctionnement et de logistique par commande</strong>, quel que soit le nombre de packs, dont <?= eur(Asso::fundShare()) ?> versés au fonds plantations. L'adhésion annuelle à l'association est <?= Asso::membershipFee() > 0 ? 'de ' . eur(Asso::membershipFee()) : 'offerte' ?>. Tout est détaillé au panier avant de valider.</div></details>
      <details><summary>Pourquoi faut-il adhérer ?</summary><div>Parce que l'association achète <strong>en votre nom</strong> : c'est un mandat d'achat, réservé à ses membres. L'adhésion se coche au moment de la commande, une fois par an. Elle vous donne aussi une voix à l'assemblée générale.</div></details>
      <details><summary>Pourquoi est-ce moins cher ?</summary><div>Un commerce vend à l'unité et doit financer un magasin, du personnel et des invendus : c'est normal. Le groupe, lui, achète en gros directement aux producteurs, ne garde aucun stock et confie la préparation aux bénévoles : le prix paie les plantes.</div></details>
      <details><summary>Comment se compose le pack ?</summary><div>Pendant la pré-ouverture, chaque membre coche jusqu'à 10 variétés. À la fin du vote, les variétés sont classées par nombre de voix et ajoutées au pack une à une, tant que leur coût d'achat (plus le sachet et l'étiquette) tient dans le prix du pack. Un aperçu « si le vote s'arrêtait maintenant » est affiché en direct sur la page du groupe.</div></details>
      <details><summary>Dois-je voter pour acheter ?</summary><div>Non, mais c'est plus simple : à l'ouverture des ventes, le pack composé est déposé automatiquement dans le panier de chaque votant. Il suffit de confirmer. Les membres qui n'ont pas voté peuvent aussi l'acheter.</div></details>
      <details><summary>Et si une variété que j'ai choisie n'entre pas dans le pack ?</summary><div>Le pack réunit les variétés les plus demandées par l'ensemble du groupe. Si une de vos variétés n'a pas assez de voix, elle n'y sera pas ; le pack arrive quand même dans votre panier et vous restez libre de ne pas confirmer l'achat.</div></details>
      <details><summary>Quand suis-je débité·e ?</summary><div>Uniquement quand votre lot de 10 achats est complet. Votre carte est enregistrée de façon sécurisée par Stripe au moment de l'achat, sans débit. Si votre lot n'est jamais complété, rien n'est prélevé.</div></details>
      <details><summary>Où et quand je récupère mon pack ?</summary><div>Au lieu de retrait du groupe (salle municipale, local associatif, hall d'entreprise), lors de la distribution annoncée sur sa page. Vous recevez un QR code par email quand votre pack est prêt.</div></details>
      <details><summary>Comment devenir bénévole ?</summary><div>Sur la page de votre groupe, cliquez sur « Je deviens bénévole ». Il faut deux bénévoles par groupe pour ouvrir le vote. Le rôle : préparer les packs une demi-journée et aider à la distribution.</div></details>
    </div>
  </div>
</section>
