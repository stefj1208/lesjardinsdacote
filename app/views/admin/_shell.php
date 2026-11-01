<?php
$s = $section ?? '';
$link = fn(string $href, string $key, string $label) => '<a href="' . $href . '"' . ($s === $key ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
$issues = (int)db()->val("SELECT COUNT(*) FROM orders WHERE payment_status IN ('failed','action_required')");
?>
<div class="admin-shell">
  <nav class="admin-side" aria-label="Back-office">
    <?= $link('/admin', 'dashboard', 'Tableau de bord') ?>
    <div class="grp">Achats groupés</div>
    <?= $link('/admin/campagnes', 'campaigns', 'Campagnes') ?>
    <?= $link('/admin/bon-de-commande', 'po', 'Bon de commande') ?>
    <?= $link('/admin/paniers-fournisseur', 'batches', 'Paniers franco') ?>
    <?= $link('/admin/commandes', 'orders', 'Commandes' . ($issues ? ' (' . $issues . ' ⚠)' : '')) ?>
    <div class="grp">Catalogue</div>
    <?= $link('/admin/produits', 'products', 'Produits') ?>
    <?= $link('/admin/import', 'import', 'Import CSV') ?>
    <?= $link('/admin/fournisseurs', 'suppliers', 'Fournisseurs') ?>
    <div class="grp">Logistique</div>
    <?= $link('/admin/retraits', 'windows', 'Distributions') ?>
    <?= $link('/admin/creneaux', 'shifts', 'Créneaux bénévoles') ?>
    <a href="/benevoles">Espace bénévoles</a>
    <div class="grp">Association</div>
    <?= $link('/admin/utilisateurs', 'users', 'Membres') ?>
    <?= $link('/admin/parametres', 'settings', 'Paramètres') ?>
  </nav>
  <div class="admin-main"><?= $inner ?></div>
</div>
