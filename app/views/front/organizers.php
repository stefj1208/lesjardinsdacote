<section class="g-hero" style="min-height:460px">
  <?= ph('commons:Raised_vegetable_bed_at_Boreham,_Essex,_England.jpg|commons:Raised_bed.jpg', 'Potager partagé', '', 1800, true) ?>
  <div class="wrap">
    <h1 style="max-width:18ch">Collectivités, entreprises, associations : offrez un potager à votre communauté.</h1>
    <p class="meta" style="max-width:60ch;font-size:1.15rem">Vous lancez « Les Jardins d'à Côté » de votre commune ou de votre entreprise. Nous fournissons les packs, le site, les paiements et l'organisation. Vous offrez un lieu et un relais.</p>
    <div class="row mt-3"><a class="btn btn--light btn--lg" href="#proposer">Proposer un groupe</a></div>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <h2>Pour qui ?</h2>
      <div class="feature-list mt-3">
        <div class="feature"><span class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/></svg></span><div><h4>Mairies, CCAS, intercommunalités</h4><p>Une action concrète pour le pouvoir d'achat, le lien social et la biodiversité, sans budget ni marché public : chaque habitant paie son pack.</p></div></div>
        <div class="feature"><span class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span><div><h4>Entreprises et comités d'entreprise</h4><p>Une animation RSE simple : les salariés votent pour leur pack et le retirent sur le lieu de travail.</p></div></div>
        <div class="feature"><span class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21V11M12 14c-4 0-7-3-7-7 4 0 7 3 7 7zM12 11c0-4 3-7 7-7 0 4-3 7-7 7z"/></svg></span><div><h4>Jardins partagés et familiaux</h4><p>Des semences et des plants de qualité pour toutes les parcelles, commandés en une fois.</p></div></div>
        <div class="feature"><span class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2.5 1.2-4.5 3-5"/></svg></span><div><h4>Associations et groupes d'habitants</h4><p>Quelques voisins motivés suffisent : il faut simplement deux bénévoles et un lieu de retrait.</p></div></div>
      </div>
    </div>
    <div class="photo-pair">
      <?= ph('commons:Seedling_planting.jpg|commons:Dar_cucumber_seedlings.jpg', 'Plantation de jeunes plants', '', 900) ?>
      <?= ph('commons:Beans_in_hand,_gene_bank.jpg|commons:Pumpkin_seeds_in_hand.jpg', 'Graines dans la main', '', 900) ?>
    </div>
  </div>
</section>

<section class="section section--wash">
  <div class="wrap">
    <div class="section-head"><div><h2>Qui fait quoi</h2><p>Votre engagement est volontairement léger. Nous gérons tout ce qui coûte du temps.</p></div></div>
    <div class="doors">
      <div class="panel"><h3>Vous</h3>
        <ul class="items" style="gap:.8rem">
          <li>Proposez le groupe et donnez-lui son nom : « Les Jardins d'à Côté de votre commune ».</li>
          <li>Mettez à disposition un lieu de retrait (salle, hall, local) pour une matinée de distribution.</li>
          <li>Relayez auprès de vos habitants, salariés ou adhérents : bulletin municipal, affichage, intranet.</li>
          <li>Aidez à trouver deux bénévoles (élus, agents, salariés volontaires, habitants).</li>
        </ul></div>
      <div class="panel"><h3>L'association Les Jardins d'à Côté</h3>
        <ul class="items" style="gap:.8rem">
          <li>Sélectionne trois packs de saison à 30 € (graines bio, plants, fruitiers, aromatiques).</li>
          <li>Fournit la page du groupe, les votes, les paiements sécurisés et les emails.</li>
          <li>Commande auprès des fournisseurs par lots de 10 : aucun stock, aucun risque d'invendus.</li>
          <li>Forme les bénévoles : fiche de préparation, étiquettes, QR codes de retrait.</li>
        </ul></div>
    </div>
    <div class="mt-6"><?php partial('partials/phases') ?></div>
  </div>
</section>

<section class="section" id="proposer">
  <div class="wrap split">
    <div>
      <h2>Proposer un groupe</h2>
      <p class="lede">Nous vous recontactons sous quelques jours pour valider le lieu de retrait et le calendrier. Le groupe apparaît ensuite dans la liste avec le statut « À la recherche de bénévoles ».</p>
      <?php if ($existing): ?><div class="notice mt-3">Un groupe existe déjà à <?= e($existing['commune']) ?> : <a href="/groupes/<?= e($existing['slug']) ?>"><?= e($existing['name']) ?></a>.</div><?php endif; ?>
    </div>
    <form class="panel" method="post" action="/lancer-un-groupe">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="field"><label for="commune">Commune</label><input id="commune" name="commune" required value="<?= e(old('commune', $commune)) ?>"></div>
        <div class="field"><label for="postal_code">Code postal</label><input id="postal_code" name="postal_code" inputmode="numeric" required value="<?= e(old('postal_code')) ?>"></div>
      </div>
      <div class="field"><label for="organizer_type">Vous êtes</label>
        <select id="organizer_type" name="organizer_type"><?php foreach (Cat::ORGANIZERS as $k => $l): ?><option value="<?= $k ?>"<?= old('organizer_type') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="organizer_name">Nom de la structure <span class="muted">(facultatif)</span></label><input id="organizer_name" name="organizer_name" value="<?= e(old('organizer_name')) ?>" placeholder="Mairie de…, Société…, Association…"></div>
      <div class="grid-2">
        <div class="field"><label for="contact_name">Votre nom</label><input id="contact_name" name="contact_name" required autocomplete="name" value="<?= e(old('contact_name', $u ? trim($u['first_name'] . ' ' . $u['last_name']) : '')) ?>"></div>
        <div class="field"><label for="contact_phone">Téléphone</label><input id="contact_phone" name="contact_phone" type="tel" autocomplete="tel" value="<?= e(old('contact_phone', $u['phone'] ?? '')) ?>"></div>
      </div>
      <div class="field"><label for="contact_email">Email</label><input id="contact_email" name="contact_email" type="email" required autocomplete="email" value="<?= e(old('contact_email', $u['email'] ?? '')) ?>"></div>
      <div class="field"><label for="pickup_place">Lieu de retrait envisagé <span class="muted">(facultatif)</span></label><input id="pickup_place" name="pickup_place" value="<?= e(old('pickup_place')) ?>" placeholder="Salle des fêtes, hall d'accueil, local associatif…"></div>
      <div class="field"><label for="description">Un mot sur votre projet <span class="muted">(facultatif)</span></label><textarea id="description" name="description" style="min-height:90px"><?= e(old('description')) ?></textarea></div>
      <label class="check"><input type="checkbox" name="volunteer" value="1"<?= old('volunteer') ? ' checked' : '' ?>> Je serai l'un des deux bénévoles du groupe</label>
      <button class="btn btn--lg btn--block mt-3" type="submit">Envoyer la proposition</button>
    </form>
  </div>
</section>
