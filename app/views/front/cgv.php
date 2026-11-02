<?php $asso = e(setting('association_name')); ?>
<div class="wrap page prose">
  <h1>Conditions générales et mandat d'achat groupé</h1>
  <p class="muted">Version du <?= date_fr(date('Y-m-d')) ?>. <?= e(setting('association_legal')) ?>, <?= e(setting('association_address')) ?>.</p>
  <div class="notice">Modèle fourni avec la plateforme : faites-le relire par un juriste ou votre fédération avant la mise en ligne.</div>

  <h2>1. Objet</h2>
  <p>Les présentes conditions encadrent la participation aux groupes d'achat « Les Jardins d'à Côté » organisés par <?= $asso ?> (« l'association ») sur ce site. L'association est à but non lucratif : elle mutualise les achats de packs (graines, plants, arbres fruitiers, aromatiques) des membres de chaque groupe local, puis les fait préparer et distribuer par des bénévoles.</p>

  <h2>2. Groupes, votes et mandat d'achat groupé</h2>
  <p>Chaque groupe local passe par trois étapes : recherche de bénévoles, pré-ouverture (chaque membre choisit les variétés qu'il souhaite) puis ouverture des ventes du pack composé des variétés les plus demandées, dans la limite de son prix. Voter n'engage pas à acheter : le pack composé est déposé dans le panier du votant, qui reste libre de confirmer ou non.</p>
  <p>En confirmant un achat, le membre donne mandat à l'association d'acheter en son nom et pour son compte le pack choisi, dans le cadre d'un groupement d'achat. Ce mandat prend fin à la remise du pack.</p>

  <h2>3. Lots et déclenchement de la commande</h2>
  <p>Les achats sont regroupés par lots (10 packs par défaut, nombre affiché sur la page du groupe). Lorsqu'un lot est complet, la commande de chacun de ses participants devient ferme et le paiement est déclenché ; un nouveau lot s'ouvre. Si la vente se termine alors que le dernier lot est incomplet, les achats de ce lot sont annulés et <strong>aucun montant n'est prélevé</strong>.</p>

  <h2>4. Prix</h2>
  <p>Le prix de chaque pack est indiqué en euros, toutes taxes comprises le cas échéant (<?= e(setting('vat_mention')) ?>), avant le vote. Il est identique pour tous les participants.</p>

  <h2>5. Paiement</h2>
  <p>Lors de l'achat, le membre enregistre sa carte bancaire auprès du prestataire de paiement Stripe, avec authentification forte, et autorise l'association à la débiter du prix du pack lorsque son lot est complet. Aucune donnée de carte n'est conservée par l'association. Si la banque exige une nouvelle authentification ou refuse le débit, le membre est invité à régler sous 48 heures ; à défaut, sa commande peut être annulée. Lorsque le paiement en ligne n'est pas proposé, le règlement s'effectue au retrait.</p>

  <h2>6. Annulation et rétractation</h2>
  <p>Le membre peut annuler son achat depuis son compte, sans frais, tant que son lot n'est pas complet. Une fois le lot complet, la commande est passée au fournisseur pour le compte du groupement et les produits sont conditionnés à la demande : conformément à l'article L221-28 du Code de la consommation, le droit de rétractation ne s'applique pas aux biens confectionnés selon les spécifications du consommateur ni aux biens susceptibles de se détériorer rapidement (plantes vivantes).</p>

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
