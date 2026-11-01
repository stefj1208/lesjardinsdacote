<div class="wrap page prose">
  <h1>Mentions légales</h1>
  <h2>Éditeur du site</h2>
  <p><?= e(setting('association_name')) ?>, <?= e(setting('association_legal')) ?><br>
  Siège : <?= e(setting('association_address')) ?><br>
  Contact : <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a><br>
  Directeur·rice de la publication : <?= e(setting('publication_director', 'le ou la président·e de l\'association')) ?></p>
  <h2>Hébergement</h2>
  <p><?= nl2br(e(setting('host_info', "Hostinger International Ltd.\n61 Lordou Vironos Street, 6023 Larnaca, Chypre\nhttps://www.hostinger.fr"))) ?></p>
  <h2>Paiement</h2>
  <p>Les paiements en ligne sont traités par Stripe Payments Europe Ltd., 1 Grand Canal Street Lower, Dublin 2, Irlande.</p>
  <h2>Propriété intellectuelle</h2>
  <p>Les textes, illustrations et le logo du site appartiennent à l'association. Les photographies et descriptions de produits peuvent appartenir aux fournisseurs, qui les mettent à disposition de leurs revendeurs. Les polices Fraunces, Caveat et Plus Jakarta Sans sont distribuées sous licence SIL Open Font License et servies depuis ce site.</p>
</div>
