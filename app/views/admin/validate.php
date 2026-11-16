<?php
/** File « À valider ». @var array $pending @var array $recent @var array $stats */
$catLabel = fn(string $c) => Agents::CATEGORIES[$c]['label'] ?? $c;
?>
<h1>À valider</h1>
<p class="muted">Tout ce que les agents proposent passe ici avant d'exister pour de bon. Valider envoie le message tel quel ; vous pouvez le retoucher avant.</p>

<?php if (!$stats['inbox']['imap_ready']): ?>
  <div class="callout callout--warn"><strong>Boîte mail non relevée.</strong>
    <?php if (!extension_loaded('imap')): ?>Activez l'extension <code>imap</code> dans hPanel → Avancé → Configuration PHP → Extensions PHP, puis<?php endif; ?>
    renseignez le mot de passe de <?= e(Inbox::user() ?: 'la boîte contact') ?> dans <a href="/admin/parametres">Paramètres</a> pour que les messages reçus arrivent ici automatiquement.
  </div>
<?php endif; ?>

<?php if ($stats['categories']): ?>
  <div class="vstats">
    <?php foreach ($stats['categories'] as $c => $s): ?>
      <span class="chip" title="<?= $s['decided'] ?> décisions"><?= e($s['label']) ?> :
        <?= $s['pending'] ?> en attente<?= $s['clean_rate'] !== null ? ' · ' . $s['clean_rate'] . ' % validés tels quels' : '' ?></span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!Social::facebookReady() && !Social::instagramReady() && array_filter($pending, fn($i) => $i['category'] === 'social')): ?>
  <div class="callout"><strong>Publication en un clic :</strong> reliez votre page Facebook et votre compte Instagram dans <a href="/admin/parametres#reseaux">Paramètres → Réseaux sociaux</a>. En attendant, copiez le texte, téléchargez l'image et publiez depuis votre téléphone.</div>
<?php endif; ?>

<?php if (!$pending): ?>
  <div class="panel mt-3"><p class="mb-0">Rien à valider pour l'instant. Les agents déposeront leurs prochaines propositions ici.</p></div>
<?php endif; ?>

<?php foreach ($pending as $it): ?>
  <?php $meta = Agents::meta($it); $isSocial = $it['category'] === 'social'; ?>
  <form class="panel vitem mt-3" method="post" action="/admin/a-valider/<?= (int)$it['id'] ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="vitem-head">
      <span class="chip"><?= e($catLabel($it['category'])) ?></span>
      <b><?= e($it['title']) ?></b>
      <small class="muted"><?= e(date_fr($it['created_at'], true)) ?> · <?= e($it['source']) ?></small>
    </div>
    <?php if ($it['summary']): ?><p class="small"><?= e($it['summary']) ?></p><?php endif; ?>

    <?php if ($it['inbox_id']): ?>
      <details class="vmail"><summary>Message reçu de <?= e($it['inbox_from_name'] ?: $it['inbox_from']) ?> — <?= e($it['inbox_subject']) ?></summary>
        <pre class="vpre"><?= e(mb_substr((string)$it['inbox_body'], 0, 4000)) ?></pre>
      </details>
    <?php endif; ?>

    <?php if ($it['status'] === 'draft_needed'): ?>
      <p class="small muted">L'agent n'a pas encore rédigé de brouillon (prochain passage) ; vous pouvez répondre à la main ci-dessous.</p>
    <?php endif; ?>

    <?php if ($it['to_email']): ?>
      <div class="field"><label>À</label><input type="text" value="<?= e($it['to_email']) ?>" disabled></div>
      <div class="field"><label for="subj-<?= (int)$it['id'] ?>">Objet</label><input id="subj-<?= (int)$it['id'] ?>" name="subject" value="<?= e((string)$it['subject']) ?>"></div>
      <div class="field"><label for="body-<?= (int)$it['id'] ?>">Message (envoyé après validation)</label>
        <textarea id="body-<?= (int)$it['id'] ?>" name="body" rows="8"><?= e((string)$it['body']) ?></textarea></div>
    <?php elseif ($it['body']): ?>
      <?php if ($isSocial): ?>
        <div class="vsocial">
          <?php if (!empty($meta['image'])): ?>
            <a class="vsocial-img" href="<?= e($meta['image']) ?>" download><img src="<?= e($meta['image']) ?>" alt="Visuel du post" loading="lazy"></a>
          <?php else: ?>
            <div class="vsocial-img vsocial-img--none">Pas encore de visuel</div>
          <?php endif; ?>
          <div class="vsocial-side">
            <label class="small" for="img-<?= (int)$it['id'] ?>"><?= empty($meta['image']) ? 'Ajouter une image' : 'Remplacer l\'image' ?> (JPG ou PNG, par exemple créée avec Gemini ou Canva)</label>
            <input id="img-<?= (int)$it['id'] ?>" type="file" name="image" accept="image/jpeg,image/png">
            <button class="btn btn--ghost btn--sm" name="action" value="image" type="submit">Enregistrer l'image</button>
            <?php if (!empty($meta['design_url'])): ?><a class="small" href="<?= e($meta['design_url']) ?>" target="_blank" rel="noopener">Ouvrir le visuel dans Canva →</a><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="field"><label for="body-<?= (int)$it['id'] ?>"><?= $isSocial ? 'Texte du post' : 'Contenu' ?></label>
        <textarea id="body-<?= (int)$it['id'] ?>" name="body" rows="6"><?= e((string)$it['body']) ?></textarea></div>
    <?php endif; ?>

    <?php if ($it['to_email'] && $it['category'] === 'collectivite'): $sel = $meta['attachments'] ?? []; ?>
      <input type="hidden" name="has_attachments" value="1">
      <fieldset class="vattach"><legend>Pièces jointes</legend>
        <?php foreach (Mailer::ATTACHABLE as $k => $a): ?>
          <label><input type="checkbox" name="attachments[]" value="<?= e($k) ?>"<?= in_array($k, $sel, true) ? ' checked' : '' ?>> <?= e($a[2]) ?> (PDF) <a href="<?= e($a[0]) ?>" target="_blank" rel="noopener">voir</a></label>
        <?php endforeach; ?>
      </fieldset>
    <?php endif; ?>

    <div class="row vitem-actions">
      <?php if ($isSocial): ?>
        <?php if (Social::facebookReady() && Social::instagramReady() && !empty($meta['image'])): ?><button class="btn btn--accent" name="action" value="publish_all" type="submit">Publier sur Facebook et Instagram</button><?php endif; ?>
        <?php if (Social::facebookReady()): ?><button class="btn" name="action" value="publish_fb" type="submit">Publier sur Facebook</button><?php endif; ?>
        <?php if (Social::instagramReady() && !empty($meta['image'])): ?><button class="btn" name="action" value="publish_ig" type="submit">Publier sur Instagram</button><?php endif; ?>
        <?php if (!Social::facebookReady() && !Social::instagramReady()): ?>
          <button class="btn btn--accent" type="button" data-copy="#body-<?= (int)$it['id'] ?>">Copier le texte</button>
          <?php if (!empty($meta['image'])): ?><a class="btn btn--ghost" href="<?= e($meta['image']) ?>" download>Télécharger l'image</a><?php endif; ?>
          <button class="btn btn--ghost" name="action" value="approve" type="submit">Marquer comme publié</button>
        <?php endif; ?>
      <?php else: ?>
      <button class="btn btn--accent" name="action" value="approve" type="submit"><?= $it['to_email'] ? 'Valider et envoyer' : 'Valider' ?></button>
      <?php endif; ?>
      <button class="btn btn--ghost" name="action" value="reject" type="submit">Refuser</button>
      <input class="vnote" name="note" placeholder="Motif (facultatif, aide les agents à s'améliorer)">
    </div>
  </form>
<?php endforeach; ?>

<?php if ($recent): ?>
  <h2 class="mt-5">Dernières décisions</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Quand</th><th>Catégorie</th><th>Titre</th><th>Décision</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
      <tr><td><?= e(date_fr($r['decided_at'] ?? $r['created_at'], true)) ?></td><td><?= e($catLabel($r['category'])) ?></td>
        <td><?= e($r['title']) ?><?= $r['to_email'] ? ' <span class="muted">→ ' . e($r['to_email']) . '</span>' : '' ?></td>
        <td><?= ['approved' => 'Validé', 'sent' => 'Envoyé', 'rejected' => 'Refusé', 'failed' => 'Échec d\'envoi'][$r['status']] ?? e($r['status']) ?><?= $r['edited'] ? ' (retouché)' : '' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
