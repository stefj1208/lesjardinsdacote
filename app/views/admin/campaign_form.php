<?php
$ref = (float)$c['ref_portion'];
$unit = $product['unit'] ?? $c['unit'] ?? 'g';
$dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
$isNew = !$c['id'];
$st = $c['state'] ?? null;
?>
<div class="admin-head">
  <h1><?= $isNew ? 'Nouvelle campagne' : e($c['title']) ?></h1>
  <?php if (!$isNew): ?>
    <div class="row">
      <span class="chip chip--dark"><?= e(Units::STATUSES[$c['status']]) ?></span>
      <a class="btn btn--ghost btn--sm" href="/campagne/<?= e($c['slug']) ?>" target="_blank">Voir la page</a>
    </div>
  <?php endif; ?>
</div>

<?php if (!$isNew): ?>
<section class="panel mb-6">
  <div class="row row--between">
    <div>
      <?php if ($st): ?><strong><?= e(Units::qty($st['reserved'], $c['unit'])) ?></strong> réservés par <?= (int)$c['participants'] ?> participants ·
        palier <?= $st['tier'] ?>/<?= $st['max_tier'] ?> · prix actuel <?= money(Pricing::packetPrice($st['price'], $ref)) ?> la part<?= (float)$c['shop_buyback_qty'] > 0 ? ' · reliquat boutique ' . e(Units::qty($c['shop_buyback_qty'], $c['unit'])) : '' ?><?php endif; ?>
    </div>
  </div>
  <div class="row mt-4">
    <?php $act = function (string $action, string $label, string $cls = 'btn--ghost', string $confirm = '', array $extra = []) use ($c) {
        echo '<form method="post" action="/admin/campagnes/' . (int)$c['id'] . '/action"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field()
            . '<input type="hidden" name="action" value="' . e($action) . '">';
        foreach ($extra as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
        echo '<button class="btn btn--sm ' . $cls . '" type="submit">' . e($label) . '</button></form>';
    }; ?>
    <?php if ($c['status'] === 'draft') $act('publish', 'Publier', 'btn--go'); ?>
    <?php if ($c['status'] === 'active'): ?>
      <?php $act('unpublish', 'Repasser en brouillon'); ?>
      <?php $act('extend', 'Prolonger de 48 h'); ?>
      <?php $act('close', 'Clôturer maintenant', 'btn--wood', 'Clôturer maintenant ? Les paiements seront déclenchés au prix du palier atteint.'); ?>
      <?php if (!$st['validated']) $act('force', 'Forcer la validation (rachat du reliquat)', 'btn--wood', 'La boutique rachète ' . Units::qty($st['missing'], $c['unit']) . ' pour son stock et la campagne est validée au prix du palier 1. Continuer ?'); ?>
    <?php endif; ?>
    <?php if (in_array($c['status'], ['validated', 'receiving', 'packaging', 'ready_pickup'], true)): ?>
      <a class="btn btn--sm" href="/admin/bon-de-commande?c[]=<?= (int)$c['id'] ?>">Bon de commande</a>
      <a class="btn btn--sm btn--ghost" href="/benevoles/atelier/<?= (int)$c['id'] ?>">Atelier</a>
      <?php foreach (['receiving' => 'Réception', 'packaging' => 'Ensachage', 'ready_pickup' => 'Prête au retrait', 'closed' => 'Terminer'] as $to => $l) if ($to !== $c['status']) $act('status', '→ ' . $l, 'btn--ghost', '', ['to' => $to]); ?>
    <?php endif; ?>
    <a class="btn btn--sm btn--ghost" href="/admin/campagnes/<?= (int)$c['id'] ?>/participants.csv">Participants (CSV)</a>
    <?php $act('duplicate', 'Dupliquer'); ?>
    <?php if (!$orders) $act('delete', 'Supprimer', 'btn--danger', 'Supprimer définitivement cette campagne ?'); ?>
  </div>
</section>
<?php endif; ?>

<form method="post" action="/admin/campagnes/enregistrer" id="campaign-form" data-unit="<?= e($unit) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">

  <section class="panel">
    <h2>Produit</h2>
    <div class="grid-2">
      <div class="field">
        <label for="product_id">Produit du catalogue</label>
        <select id="product_id" name="product_id" required <?= $orders ? 'disabled' : '' ?> <?= $isNew ? 'data-reload-product' : '' ?>>
          <option value="">— Choisir —</option>
          <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"<?= (int)$p['id'] === (int)$c['product_id'] ? ' selected' : '' ?>><?= e($p['name'] . ' ' . $p['variety']) ?> (<?= e(Units::short($p['unit'])) ?><?= $p['supplier'] ? ' · ' . e($p['supplier']) : '' ?>)</option><?php endforeach; ?>
        </select>
        <?php if ($orders): ?><input type="hidden" name="product_id" value="<?= (int)$c['product_id'] ?>"><span class="hint">Non modifiable : des commandes existent.</span><?php endif; ?>
      </div>
      <div class="field"><label for="title">Titre affiché</label><input id="title" name="title" required value="<?= e($c['title']) ?>"></div>
    </div>
    <div class="field"><label for="pitch">Accroche <span class="muted">(facultatif)</span></label><input id="pitch" name="pitch" maxlength="255" value="<?= e($c['pitch']) ?>"></div>
    <?php if ($product): ?>
      <p class="label">Grille du fournisseur <a class="small" href="/admin/produits/<?= (int)$product['id'] ?>">modifier</a></p>
      <?php if ($packs): ?>
        <div class="chips"><?php foreach ($packs as $p): ?><span class="chip"><?= e($p['label']) ?> : <?= money($p['cost_ht']) ?> HT (<?= e(Units::refPrice((float)$p['cost_ht'] / (float)$p['qty'], $unit)) ?>)</span><?php endforeach; ?></div>
      <?php else: ?><p class="notice">Aucun conditionnement fournisseur : le générateur de paliers et le simulateur en ont besoin.</p><?php endif; ?>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Déroulement</h2>
    <div class="grid-3">
      <div class="field"><label for="start_at">Ouverture</label><input id="start_at" name="start_at" type="datetime-local" required value="<?= $dt($c['start_at']) ?>"></div>
      <div class="field"><label for="end_at">Clôture</label><input id="end_at" name="end_at" type="datetime-local" required value="<?= $dt($c['end_at']) ?>"><span class="hint">Au-delà de 7 jours, l'enregistrement de carte (SetupIntent) est utilisé automatiquement.</span></div>
      <div class="field"><label for="pickup_window_id">Distribution</label>
        <select id="pickup_window_id" name="pickup_window_id"><option value="">À définir</option><?php foreach ($windows as $w): ?><option value="<?= (int)$w['id'] ?>"<?= (int)$w['id'] === (int)$c['pickup_window_id'] ? ' selected' : '' ?>><?= e($w['label'] . ' — ' . date_fr($w['starts_at'])) ?></option><?php endforeach; ?></select>
        <span class="hint"><a href="/admin/retraits">Gérer les distributions</a></span></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="trigger_mode">Déclenchement des paliers</label>
        <select id="trigger_mode" name="trigger_mode"><?php foreach (Units::MODES as $k => $l): ?><option value="<?= $k ?>"<?= $c['trigger_mode'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="supplier_batch_id">Panier fournisseur (franco)</label>
        <select id="supplier_batch_id" name="supplier_batch_id"><option value="">Aucun</option><?php foreach ($batches as $b): ?><option value="<?= (int)$b['id'] ?>"<?= (int)$b['id'] === (int)$c['supplier_batch_id'] ? ' selected' : '' ?>><?= e($b['name']) ?> (<?= money($b['franco_ht']) ?> HT)</option><?php endforeach; ?></select>
        <span class="hint">Obligatoire en mode franco. <a href="/admin/paniers-fournisseur">Gérer</a></span></div>
      <div class="field"><label class="check" style="margin-top:30px"><input type="checkbox" name="is_featured" value="1"<?= $c['is_featured'] ? ' checked' : '' ?>> Mettre en avant sur l'accueil</label></div>
    </div>
  </section>

  <section class="panel">
    <h2>Vente au détail</h2>
    <div class="grid-4">
      <div class="field"><label for="ref_portion">Part de référence (<?= e(Units::short($unit)) ?>)</label><input id="ref_portion" name="ref_portion" inputmode="decimal" required value="<?= e(dec_in($ref, 3)) ?>" data-sim><span class="hint">Les paliers et prix se saisissent par part.</span></div>
      <div class="field"><label for="portion_options">Contenances proposées</label><input id="portion_options" name="portion_options" required value="<?= e($c['portion_options']) ?>"><span class="hint">Séparées par des virgules, ex. 5,10,25</span></div>
      <div class="field"><label for="max_packets_per_line">Sachets max. par contenance</label><input id="max_packets_per_line" name="max_packets_per_line" type="number" min="1" value="<?= (int)$c['max_packets_per_line'] ?>"></div>
      <div class="field"><label for="max_qty_cap">Plafond total (<?= e(Units::short($unit)) ?>)</label><input id="max_qty_cap" name="max_qty_cap" inputmode="decimal" value="<?= $c['max_qty_cap'] !== null ? e(dec_in($c['max_qty_cap'], 3)) : '' ?>" placeholder="illimité"></div>
    </div>
  </section>

  <section class="panel">
    <h2>Moteur de prix</h2>
    <p class="muted small">Prix d'une part = [ (coût du sac + port) ÷ (quantité × (1 − freinte)) + sachet ÷ part ] × (1 + marge) × (1 + TVA)</p>
    <div class="grid-4">
      <div class="field"><label for="margin_pct">Contribution asso (%)</label><input id="margin_pct" name="margin_pct" inputmode="decimal" value="<?= e(dec_in((float)$c['margin_rate'] * 100, 2)) ?>" data-sim></div>
      <div class="field"><label for="waste_pct">Freinte / perte de pesée (%)</label><input id="waste_pct" name="waste_pct" inputmode="decimal" value="<?= e(dec_in((float)$c['waste_coeff'] * 100, 2)) ?>" data-sim></div>
      <div class="field"><label for="packet_cost">Coût sachet + étiquette (€)</label><input id="packet_cost" name="packet_cost" inputmode="decimal" value="<?= e(dec_in((float)$c['packet_cost'], 4)) ?>" data-sim></div>
      <div class="field"><label for="vat_pct">TVA (%)</label><input id="vat_pct" name="vat_pct" inputmode="decimal" value="<?= e(dec_in((float)$c['vat_rate'] * 100, 2)) ?>" data-sim><span class="hint">0 si l'association n'est pas assujettie.</span></div>
    </div>
    <div class="grid-4">
      <div class="field"><label for="shipping_share_ht">Quote-part de port (€ HT)</label><input id="shipping_share_ht" name="shipping_share_ht" inputmode="decimal" value="<?= e(dec_in((float)$c['shipping_share_ht'], 2)) ?>" data-sim></div>
      <div class="field"><label for="public_price_part">Prix public de référence (€ / part)</label><input id="public_price_part" name="public_price_part" inputmode="decimal" value="<?= e(dec_in((float)$c['public_unit_price'] * $ref, 2)) ?>" data-sim><span class="hint">Affiché barré.</span></div>
    </div>

    <h3 class="mt-6">Paliers</h3>
    <div class="row mb-6" style="margin-bottom:var(--s-4)">
      <button class="btn btn--wood btn--sm" type="button" data-generate <?= $packs ? '' : 'disabled' ?>>Générer depuis la grille fournisseur</button>
      <label class="check small"><input type="checkbox" id="include_smallest"> Inclure le plus petit conditionnement</label>
    </div>
    <div class="table-wrap"><table class="table tier-editor" id="tiers">
      <thead><tr><th>Palier</th><th>Seuil (parts)</th><th>Prix par part (€ TTC)</th><th>Mention</th><th></th></tr></thead>
      <tbody>
      <?php $rows = $c['tiers'] ?: [['min_qty' => '', 'unit_price_ttc' => '', 'label' => '']]; foreach ($rows as $i => $t): ?>
        <tr>
          <td class="t-num"><?= $i + 1 ?></td>
          <td><input name="tier_parts[]" inputmode="decimal" value="<?= $t['min_qty'] !== '' ? e(dec_in((float)$t['min_qty'] / $ref, 2)) : '' ?>" data-sim></td>
          <td><input name="tier_price[]" inputmode="decimal" value="<?= $t['unit_price_ttc'] !== '' ? e(dec_in((float)$t['unit_price_ttc'] * $ref, 2)) : '' ?>" data-sim></td>
          <td><input name="tier_label[]" value="<?= e($t['label'] ?? '') ?>" placeholder="Ex. Sac 500 g débloqué"></td>
          <td><button class="btn btn--danger btn--sm" type="button" data-remove-tier aria-label="Supprimer">×</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <button class="btn btn--ghost btn--sm mt-4" type="button" data-add-tier>Ajouter un palier</button>

    <h3 class="mt-6">Simulateur de rentabilité</h3>
    <p class="muted small">Pour chaque seuil : chiffre d'affaires, achat fournisseur optimal (combinaison de sacs la moins chère), port, sachets et marge nette de l'association.</p>
    <div class="table-wrap"><table class="table sim-table" id="simulation"><thead><tr><th>Seuil</th><th class="num">Quantité</th><th class="num">Recettes HT</th><th>Achat fournisseur</th><th class="num">Port + sachets</th><th class="num">Marge nette</th></tr></thead>
      <tbody><tr><td colspan="6" class="muted">Renseignez les paliers pour lancer la simulation.</td></tr></tbody></table></div>
    <input type="hidden" id="product_id_sim" value="<?= (int)$c['product_id'] ?>">
    <input type="hidden" id="reserved_sim" value="<?= (float)($st['reserved'] ?? 0) ?>">
  </section>

  <div class="row mt-6">
    <button class="btn btn--lg" type="submit">Enregistrer</button>
    <?php if ($isNew || $c['status'] === 'draft'): ?><button class="btn btn--go btn--lg" type="submit" name="publish" value="1">Enregistrer et publier</button><?php endif; ?>
  </div>
</form>
