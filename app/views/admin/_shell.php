<?php
$sec = $section ?? '';
$link = fn(string $href, string $key, string $label) => '<a href="' . $href . '"' . ($sec === $key ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
$proposed = (int)db()->val("SELECT COUNT(*) FROM `groups` WHERE status = 'proposed'");
$issues = (int)db()->val("SELECT COUNT(*) FROM orders WHERE payment_status IN ('failed','action_required')");
$toValidate = Agents::pendingCount();
?>
<div class="admin-shell">
  <nav class="admin-side" aria-label="Back-office">
    <?= $link('/admin', 'dashboard', 'Tableau de bord') ?>
    <?= $link('/admin/a-valider', 'validate', 'À valider' . ($toValidate ? ' (' . $toValidate . ')' : '')) ?>
    <div class="grp">Réseau</div>
    <?= $link('/admin/groupes', 'groups', 'Groupes' . ($proposed ? ' (' . $proposed . ' à valider)' : '')) ?>
    <?= $link('/admin/ventes', 'sales', 'Ventes & votes') ?>
    <?= $link('/admin/varietes', 'varieties', 'Variétés') ?>
    <?= $link('/admin/packs', 'packs', 'Packs composés') ?>
    <div class="grp">Achats</div>
    <?= $link('/admin/commandes', 'orders', 'Commandes' . ($issues ? ' (' . $issues . ' ⚠)' : '')) ?>
    <div class="grp">Bénévoles</div>
    <?= $link('/admin/creneaux', 'shifts', 'Créneaux') ?>
    <a href="/benevoles">Espace bénévoles</a>
    <div class="grp">Association</div>
    <?= $link('/admin/utilisateurs', 'users', 'Membres') ?>
    <?= $link('/admin/parametres', 'settings', 'Paramètres') ?>
  </nav>
  <div class="admin-main"><?= $inner ?></div>
</div>
