<?php $asso = e(setting('association_name')); ?>
<div class="wrap page prose">
  <h1>Conditions générales et mandat d'achat groupé</h1>
  <p class="muted">Version du <?= date_fr(date('Y-m-d')) ?>. <?= e(setting('association_legal')) ?>, <?= e(setting('association_address')) ?>.</p>
  <div class="notice">Modèle fourni avec la plateforme : faites-le relire par un juriste ou votre fédération avant la mise en ligne.</div>

  <h2>1. Objet</h2>
  <p>Les présentes conditions encadrent la participation des membres et utilisateurs aux campagnes d'achat groupé organisées par <?= $asso ?> (« l'association ») sur le site <?= e(parse_url(base_url(), PHP_URL_HOST)) ?>. L'association est à but non lucratif : elle mutualise les commandes de ses participants pour acheter des produits de jardinage en conditionnement professionnel, puis les reconditionne et les distribue avec l'aide de bénévoles.</p>

  <h2>2. Mandat d'achat groupé</h2>
  <p>En validant son engagement, le participant donne mandat à l'association d'acheter en son nom et pour son compte, auprès du fournisseur indiqué sur la campagne, la quantité de produit réservée, dans le cadre d'un groupement d'achat. L'association répartit ensuite le conditionnement professionnel entre les participants. Ce mandat prend fin à la remise des produits.</p>

  <h2>3. Campagnes et paliers</h2>
  <p>Chaque campagne indique une date de clôture et un ou plusieurs paliers de quantité. Le palier 1 est le seuil de validation. À la clôture :</p>
  <ul>
    <li>si le palier 1 n'est pas atteint (ou, pour un panier commun, si le franco de port fournisseur n'est pas atteint), la campagne est annulée, aucun montant n'est prélevé et le participant est libéré de son engagement ;</li>
    <li>si un palier est atteint, <strong>tous les participants paient le prix du dernier palier atteint</strong>, quelle que soit la date de leur engagement. Ce prix n'est jamais supérieur au montant affiché lors de l'engagement.</li>
  </ul>
  <p>L'association peut, de façon exceptionnelle, prolonger une campagne de 48 heures ou la valider en rachetant elle-même le reliquat manquant pour atteindre le palier 1. Dans ce cas le prix du palier 1 s'applique.</p>

  <h2>4. Prix</h2>
  <p>Les prix sont indiqués en euros, toutes taxes comprises le cas échéant (<?= e(setting('vat_mention')) ?>). Ils comprennent le coût du produit, la quote-part de transport, le sachet et une contribution aux frais de fonctionnement de l'association. Leur décomposition est affichée sur chaque campagne.</p>

  <h2>5. Paiement</h2>
  <p>Lors de l'engagement, le participant enregistre sa carte bancaire auprès du prestataire de paiement Stripe, avec authentification forte. Il autorise l'association à débiter cette carte, à la clôture, du montant final calculé selon l'article 3. Aucune donnée de carte n'est conservée par l'association. Si la banque exige une nouvelle authentification ou refuse le débit, le participant est invité par email à régler sous 48 heures ; à défaut, sa réservation peut être annulée.</p>
  <p>Lorsque le paiement en ligne n'est pas proposé, le règlement s'effectue au retrait.</p>

  <h2>6. Annulation et rétractation</h2>
  <p>Le participant peut annuler son engagement depuis son compte, sans frais, jusqu'à la clôture de la campagne. Après la clôture, la commande est passée au fournisseur pour le compte du groupement et les produits sont fractionnés à la demande : conformément à l'article L221-28 du Code de la consommation, le droit de rétractation ne s'applique pas aux biens confectionnés selon les spécifications du consommateur ni aux biens susceptibles de se détériorer rapidement (plantes vivantes).</p>

  <h2>7. Semences et traçabilité</h2>
  <p>Les semences sont reconditionnées par les bénévoles à partir de lots professionnels. Chaque sachet porte l'espèce, la variété, le numéro du lot d'origine, l'année de conditionnement, le poids net ou le nombre de graines et la mention « Semences destinées aux jardiniers amateurs ». Elles sont destinées à un usage non commercial.</p>

  <h2>8. Retrait</h2>
  <p>Les produits sont remis lors des permanences de distribution, sur présentation du Pass Retrait (QR code ou code à six caractères). Les plantes vivantes doivent être retirées dans les 72 heures suivant leur arrivée. Les produits non retirés trente jours après la mise à disposition, malgré relance, sont réintégrés aux stocks de l'association sans remboursement.</p>

  <h2>9. Réclamations</h2>
  <p>Toute réclamation (produit manquant, sachet endommagé) est à adresser à <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a> dans les 8 jours suivant le retrait. En cas de litige, le participant peut recourir gratuitement à un médiateur de la consommation (coordonnées à compléter) ou à la plateforme européenne de règlement en ligne des litiges.</p>

  <h2>10. Données personnelles</h2>
  <p>Voir la page <a href="/confidentialite">Données personnelles</a>.</p>

  <h2>11. Droit applicable</h2>
  <p>Les présentes conditions sont soumises au droit français.</p>
</div>
