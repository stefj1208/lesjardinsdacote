<?php $sachet = Varieties::sachetCost(); ?>
<div class="admin-head"><h1>Variétés</h1><a class="btn btn--sm" href="/admin/varietes/nouvelle<?= $cat ? '?univers=' . $cat : '' ?>">Nouvelle variété</a></div>
<nav class="tabs"><a href="/admin/varietes"<?= !$cat ? ' aria-current="page"' : '' ?>>Toutes</a><?php foreach (Cat::CATEGORIES as $k => $c): ?><a href="?univers=<?= $k ?>"<?= $cat === $k ? ' aria-current="page"' : '' ?>><?= e($c['label']) ?></a><?php endforeach; ?></nav>
<p class="muted small">Le coût d'une portion découle du prix d'achat en gros (ex. 9,92 € TTC les 100 graines → 0,99 € les 10). À la clôture d'un vote, les variétés les plus demandées entrent dans le pack tant que la somme de leurs coûts, plus <?= money($sachet) ?> de sachet par variété<?= Varieties::fixedCosts() ? ' et ' . money(Varieties::fixedCosts()) . ' de frais fixes' : '' ?>, reste sous le prix du pack (<a href="/admin/parametres">Paramètres</a>).</p>
<div class="panel table-wrap"><table class="table">
  <thead><tr><th></th><th>Variété</th><th>Univers</th><th class="num">Portion</th><th class="num">Achat</th><th class="num">Coût portion</th><th class="num">Jardinerie</th><th>Fournisseur</th></tr></thead>
  <tbody>
  <?php foreach ($varieties as $v): ?>
    <tr<?= $v['is_active'] ? '' : ' style="opacity:.5"' ?>>
      <td style="width:58px"><?= ph($v['photo'], '', '', 120) ?></td>
      <td><a href="/admin/varietes/<?= (int)$v['id'] ?>"><strong><?= e($v['name']) ?></strong> <?= e($v['variety']) ?></a><?= $v['is_active'] ? '' : ' <span class="chip">désactivée</span>' ?></td>
      <td class="small"><?= e(Cat::label($v['category'])) ?></td>
      <td class="num"><?= e($v['portion']) ?></td>
      <td class="num small"><?= $v['purchase_price'] !== null ? money($v['purchase_price']) . ' / ' . e(Cat::qty($v['purchase_qty'], $v['unit'])) : '—' ?></td>
      <td class="num"><strong><?= $v['unit_cost'] !== null ? money($v['unit_cost']) : '—' ?></strong></td>
      <td class="num"><?= $v['market_price'] !== null ? money($v['market_price']) : '—' ?></td>
      <td class="small"><?= e($v['supplier']) ?><?= $v['supplier_ref'] ? ' · ' . e($v['supplier_ref']) : '' ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$varieties): ?><tr><td colspan="8" class="muted">Aucune variété.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<style>.table .ph{width:46px;height:46px;border-radius:10px}</style>
