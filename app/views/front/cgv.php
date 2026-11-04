<?php $asso = e(setting('association_name')); $fee = eur(Asso::fee()); $fund = eur(Asso::fundShare()); $mem = Asso::membershipFee(); ?>
<div class="wrap page prose">
  <p class="eyebrow eyebrow--start">Association loi 1901</p>
  <h1>Conditions du mandat d'achat groupé</h1>
  <p class="muted">Version du <?= date_fr(date('Y-m-d')) ?>. <?= $asso ?>, <?= e(setting('association_legal')) ?>, <?= e(setting('association_address')) ?>.</p>

  <div class="keypoints">
    <div><b>Prix coûtant</b><span>Les produits sont refacturés au centime près, sans marge.</span></div>
    <div><b><?= $fee ?> par commande</b><span>Frais de fonctionnement et de logistique, dont <?= $fund ?> pour planter des arbres.</span></div>
    <div><b>Rien n'est débité</b><span>avant que votre lot de 10 achats soit complet. Sinon, tout est annulé.</span></div>
  </div>

  <h2>1. Parties et objet</h2>
  <p>Le membre (le « mandant ») donne à l'association <?= $asso ?> (le « mandataire ») mandat d'acheter <strong>en son nom et pour son compte</strong> les produits de la vente groupée à laquelle il participe. L'association n'est pas le vendeur : le vendeur est le producteur ou le fournisseur indiqué sur la page de la vente. Le mandat prend fin à la remise des produits.</p>

  <h2>2. Adhésion</h2>
  <p>Le mandat est réservé aux membres de l'association. L'adhésion se fait au moment de la commande, une fois par année civile. Elle est <?= $mem > 0 ? 'de ' . eur($mem) . ' par an' : 'offerte' ?> et donne une voix à l'assemblée générale.</p>

  <h2>3. Déroulement d'une vente groupée</h2>
  <ol>
    <li><strong>Vote</strong> : les membres du groupe local votent pour les variétés qu'ils souhaitent.</li>
    <li><strong>Composition</strong> : le pack réunit les variétés les plus demandées, dans l'ordre des voix, dans la limite du prix annoncé.</li>
    <li><strong>Commande</strong> : le membre valide et enregistre son moyen de paiement. Les achats sont regroupés par lots (10 par défaut).</li>
    <li><strong>Lot complet</strong> : la commande devient ferme, le paiement est déclenché et l'association passe la commande groupée au fournisseur.</li>
    <li><strong>Distribution</strong> : les bénévoles préparent les packs et les remettent lors d'une permanence, sur présentation du Pass Retrait.</li>
  </ol>

  <h2>4. Prix</h2>
  <p>Le montant payé comprend, affichés séparément avant la validation :</p>
  <ul>
    <li><strong>le coût réel des produits</strong> (prix du pack), refacturé sans marge, transport et conditionnement compris ;</li>
    <li><strong><?= $fee ?> de frais de fonctionnement et de logistique par commande</strong>, quel que soit le nombre de packs, dont <?= $fund ?> affectés au fonds plantations de l'association ;</li>
    <li>le cas échéant, la cotisation annuelle.</li>
  </ul>
  <p>Si le coût réel s'avère inférieur au prix du pack, l'écart est ajouté au pack en variétés ou remboursé. Le montant ne peut pas augmenter après la validation. <?= e(setting('vat_mention')) ?>.</p>
  <p>Les comparaisons de prix affichées sur le site portent sur les mêmes variétés vendues à l'unité, au prix public constaté et daté, sans citer d'enseigne ; elles incluent les frais de commande.</p>

  <h2>5. Paiement</h2>
  <p>Le membre enregistre sa carte auprès du prestataire Stripe, avec authentification forte, et autorise l'association à la débiter lorsque son lot est complet. L'association ne conserve aucune donnée de carte. Si la banque refuse le débit, le membre est invité à régler sous 48 heures. Lorsque le paiement en ligne n'est pas proposé, le règlement se fait au retrait.</p>

  <h2>6. Annulation</h2>
  <ul>
    <li><strong>Avant que le lot soit complet</strong> : le membre annule sans frais depuis son compte.</li>
    <li><strong>Lot incomplet à la fin de la vente</strong> : la commande est annulée, rien n'est prélevé, frais compris.</li>
    <li><strong>Après que le lot est complet</strong> : la commande est passée au fournisseur et ne peut plus être annulée. Les plantes vivantes se détériorent rapidement et les sachets sont préparés à la demande (article L221-28 du Code de la consommation).</li>
  </ul>

  <h2>7. Semences et végétaux</h2>
  <p>Les semences sont reconditionnées par les bénévoles à partir de lots professionnels ; chaque sachet indique l'espèce, la variété, le lot d'origine, l'année et la quantité, avec la mention « Semences destinées aux jardiniers amateurs ». Les plants et arbres sont remis avec leur passeport phytosanitaire lorsqu'il est requis. La reprise d'un végétal dépend aussi de sa plantation et de son entretien : elle n'est pas garantie.</p>

  <h2>8. Retrait</h2>
  <p>Les produits sont remis lors des permanences, sur présentation du Pass Retrait (QR code ou code à six caractères), au membre ou à la personne qu'il désigne. Les produits non retirés 7 jours après la distribution, malgré une relance, sont donnés à un jardin partagé ou à une école, sans remboursement.</p>

  <h2>9. Réclamations et responsabilité</h2>
  <p>Le fournisseur répond de la conformité des produits. Signalez tout défaut à la distribution ou dans les 7 jours, avec une photo, à <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a> : l'association transmet la réclamation et suit sa résolution (remplacement, avoir ou remboursement). Elle s'engage à exécuter le mandat avec soin et à informer les membres sans délai de tout retard.</p>

  <h2>10. Données personnelles</h2>
  <p>Voir la page <a href="/confidentialite">Données personnelles</a>. Seuls le nom et la commande sont transmis au fournisseur, si nécessaire.</p>

  <h2>11. Litiges</h2>
  <p>En cas de désaccord, contactez d'abord l'association, qui répond sous 15 jours. À défaut d'accord, le litige relève du droit français et des tribunaux compétents, sous réserve des règles protectrices du consommateur.</p>
</div>
