<div class="wrap page prose">
  <h1>Données personnelles</h1>
  <p>Responsable du traitement : <?= e(setting('association_name')) ?>, <?= e(setting('association_address')) ?> — <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>.</p>

  <h2>Ce que nous collectons et pourquoi</h2>
  <ul>
    <li><strong>Compte</strong> (nom, prénom, email, téléphone et code postal facultatifs) : gérer vos engagements et vous prévenir des paliers et du retrait. Base légale : exécution du contrat.</li>
    <li><strong>Commandes et factures</strong> : exécuter les achats groupés, tenir la comptabilité. Base légale : contrat et obligation légale (conservation 10 ans).</li>
    <li><strong>Paiement</strong> : vos données de carte sont saisies directement chez Stripe et ne transitent jamais par nos serveurs. Nous ne conservons qu'un identifiant technique.</li>
    <li><strong>Bénévolat</strong> : vos inscriptions aux créneaux, pour organiser les ateliers et vous envoyer un rappel.</li>
  </ul>

  <h2>Durées de conservation</h2>
  <p>Compte : jusqu'à sa suppression, ou 3 ans après votre dernière activité. Factures : 10 ans. Journaux de connexion : 1 jour (tentatives de connexion) à 1 an.</p>

  <h2>Destinataires</h2>
  <p>Les bénévoles habilités (pour la préparation et la remise des commandes), notre hébergeur Hostinger (serveurs situés dans l'Union européenne) et Stripe pour les paiements. Vos données ne sont jamais vendues ni cédées.</p>

  <h2>Cookies</h2>
  <p>Le site n'utilise qu'un cookie de session, indispensable à la connexion et au panier. Aucun outil de mesure d'audience ni de publicité. Les polices de caractères sont hébergées sur nos serveurs. Lors du paiement, Stripe dépose ses propres cookies nécessaires à la prévention de la fraude.</p>

  <h2>Vos droits</h2>
  <p>Vous pouvez accéder à vos données, les rectifier, les supprimer, vous opposer à un traitement ou en demander la portabilité en écrivant à <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>. Vous pouvez aussi introduire une réclamation auprès de la CNIL (cnil.fr).</p>
</div>
