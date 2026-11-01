<div class="wrap page">
  <div class="split" style="align-items:start">
    <div class="prose">
      <h1>Devenir bénévole</h1>
      <p style="font-size:1.15rem">Sans bénévoles, pas d'achat en gros : ce sont eux qui transforment un sac de 1 kg en cinquante sachets étiquetés. Une matinée de temps en temps suffit, et on apprend plein de choses sur les graines.</p>
      <h2>Les missions</h2>
      <ul>
        <li><strong>Atelier pesée & ensachage</strong> — balance de précision, sachets kraft, étiquettes. L'application vous dit exactement combien de sachets préparer.</li>
        <li><strong>Réception</strong> — décharger les colis ou les palettes, vérifier les quantités et noter les numéros de lot.</li>
        <li><strong>Permanence distribution</strong> — scanner le QR code des voisins avec votre téléphone et leur remettre leurs sachets.</li>
      </ul>
      <?php if (Auth::isVolunteer()): ?>
        <p><a class="btn btn--lg" href="/benevoles">Aller à l'espace bénévoles</a></p>
      <?php elseif (Auth::check() && Auth::user()['wants_volunteer']): ?>
        <div class="notice">Votre candidature est enregistrée : un membre de l'association valide votre accès très vite.</div>
      <?php elseif (Auth::check()): ?>
        <form method="post" action="/devenir-benevole"><?= csrf_field() ?><button class="btn btn--lg" type="submit">Je propose mon aide</button></form>
      <?php else: ?>
        <p><a class="btn btn--lg" href="/inscription">Créer mon compte bénévole</a> <a class="btn btn--ghost btn--lg" href="/connexion">J'ai déjà un compte</a></p>
        <p class="small muted">Cochez « Je veux aussi donner un coup de main » à l'inscription.</p>
      <?php endif; ?>
    </div>
    <div>
      <div class="slate">
        <h2 class="chalk" style="font-size:2rem">Prochains créneaux</h2>
        <?php if (!$shifts): ?><p>Les prochains ateliers seront annoncés à la clôture des campagnes.</p><?php endif; ?>
        <?php foreach ($shifts as $s): ?>
          <div style="padding:10px 0;border-bottom:1px dashed rgba(255,255,255,.2)">
            <div class="chalk" style="font-size:1.5rem;color:var(--chalk-yellow)"><?= e($s['title']) ?></div>
            <div class="small"><?= e(time_range_fr($s['starts_at'], $s['ends_at'])) ?> · <?= (int)$s['signed'] ?>/<?= (int)$s['volunteers_needed'] ?> inscrits</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
