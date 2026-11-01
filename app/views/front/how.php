<div class="wrap page">
  <div class="split" style="align-items:start">
    <div class="prose">
      <h1>Comment ça marche</h1>
      <p style="font-size:1.15rem">Un semencier professionnel vend ses graines beaucoup moins cher au kilo qu'en sachet de jardinerie. Seul, personne n'a besoin d'un kilo de basilic. À cinquante voisins, si. L'association achète le gros sac, les bénévoles le divisent en sachets, et chacun paie sa part au prix de gros.</p>

      <h2>Les paliers</h2>
      <p>Chaque campagne affiche plusieurs paliers. Le premier est le seuil de validation : en dessous, l'association ne peut pas acheter et la campagne est annulée sans frais. Au-delà, chaque palier correspond à un conditionnement plus gros chez le fournisseur, donc à un prix plus bas.</p>

      <h2>Le prix le plus bas pour tout le monde</h2>
      <p>Si vous vous engagez au début, quand le prix affiché est celui du palier 1, et que la communauté atteint le palier 3 avant la clôture, <strong>vous payez le prix du palier 3</strong>. Le prix final est le même pour tous les participants, quelle que soit la date de leur engagement. C'est pour ça que partager une campagne à ses voisins fait baisser son propre prix.</p>

      <h2>Le paiement</h2>
      <p>À l'engagement, votre carte est enregistrée et vérifiée par notre prestataire de paiement Stripe, avec l'authentification de votre banque. <strong>Rien n'est débité.</strong> Le prélèvement a lieu automatiquement à la clôture, au prix final. Si la campagne n'atteint pas son premier palier, vous êtes libéré·e de votre engagement et rien n'est prélevé.</p>
      <p>Vous pouvez annuler votre engagement depuis votre compte tant que la campagne est ouverte.</p>

      <h2>Après la clôture</h2>
      <ol>
        <li>L'association passe la commande groupée au fournisseur.</li>
        <li>À la réception, les bénévoles pèsent et ensachent lors d'ateliers, et collent sur chaque sachet une étiquette avec l'espèce, la variété, le numéro de lot d'origine et le poids.</li>
        <li>Quand vos sachets sont prêts, vous recevez par email un Pass Retrait avec un QR code.</li>
        <li>Vous passez à la permanence de distribution, un bénévole scanne le QR code et vous remet votre commande.</li>
      </ol>

      <h2>Pourquoi des bénévoles ?</h2>
      <p>Le reconditionnement est le seul coût qui ne baisse pas avec le volume. En le faisant ensemble, on garde la contribution de l'association au strict minimum : sachets kraft, balance de précision, local, assurance.</p>
      <p><a class="btn" href="/devenir-benevole">Rejoindre les bénévoles</a></p>
    </div>

    <div class="sticky-col">
      <div class="slate">
        <h2 class="chalk" style="font-size:2rem">Exemple : basilic Grand Vert</h2>
        <ol class="tiers">
          <li class="tier"><span class="t-name">Seul</span><span class="t-cond">sachet de jardinerie</span><span class="t-price"><span class="strike">4,50 €</span></span></li>
          <li class="tier is-reached"><span class="t-name">Palier 1</span><span class="t-cond">20 parts · sac 250 g</span><span class="t-price">3,20 €</span></li>
          <li class="tier is-reached is-active"><span class="t-name">Palier 2</span><span class="t-cond">50 parts · sac 500 g</span><span class="t-price">2,40 €</span></li>
          <li class="tier"><span class="t-name">Palier 3</span><span class="t-cond">100 parts · sac 1 kg</span><span class="t-price">1,75 €</span></li>
        </ol>
        <p class="tier-note">64 parts réservées : tout le monde paie 2,40 €, et plus que 36 parts pour 1,75 €.</p>
      </div>
      <div class="panel">
        <h3>La plante vous montre où on en est</h3>
        <div class="row" style="justify-content:space-between">
          <?php foreach ([1 => 'Graine semée', 2 => 'Jeune pousse', 3 => 'Validée', 4 => 'En fleurs', 5 => 'Récolte'] as $s => $l): ?>
            <div class="center" style="width:18%"><?php partial('partials/plant', ['state' => $s, 'growth' => $s / 5, 'toT1' => $s === 2 ? .6 : 1, 'size' => 'sm']) ?><small><?= e($l) ?></small></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
