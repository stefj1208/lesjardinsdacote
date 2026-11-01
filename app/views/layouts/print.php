<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Impression') ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body style="background:#E8ECE6">
<div class="print-toolbar no-print">
  <strong><?= e($title ?? '') ?></strong>
  <button class="btn btn--light btn--sm" type="button" onclick="window.print()">Imprimer / enregistrer en PDF</button>
  <?= $toolbar ?? '' ?>
  <a class="btn btn--outline-light btn--sm" href="javascript:history.back()">Retour</a>
</div>
<?= $content ?>
</body>
</html>
