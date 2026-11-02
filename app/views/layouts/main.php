<?php
$siteName = setting('association_name', "Les Jardins d'à Côté");
$pageTitle = isset($title) && $title ? $title . ' · ' . $siteName : $siteName . ' — Plants, arbres fruitiers et graines en achat groupé entre voisins';
$desc = $description ?? 'Dans votre commune, un groupe de voisins achète en gros plants potagers, arbres fruitiers, aromatiques et graines, et les prépare avec des bénévoles.';
$og = $og ?? [];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$cur = fn(string $p) => ($p === '/' ? $path === '/' : str_starts_with($path, $p)) ? ' aria-current="page"' : '';
$user = Auth::user();
$cartCount = Cart::count(Auth::id());
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#0F172B">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preload" href="/assets/fonts/nunito-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/heebo-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/pacifico-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($og['title'] ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($og['description'] ?? $desc) ?>">
<meta property="og:url" content="<?= e(abs_url($path)) ?>">
<meta property="og:image" content="<?= e($og['image'] ?? abs_url('/assets/img/og-default.png')) ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $head ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="sr-only" href="#main">Aller au contenu</a>
<header class="site-head">
  <?php if (setting('demo_data') === '1'): ?><div class="demo-bar">Site de démonstration<span class="hide-sm"> : les groupes, votes et achats affichés sont fictifs</span>.</div><?php endif; ?>
  <div class="wrap">
    <a class="brand" href="/" aria-label="<?= e($siteName) ?> — accueil"><?php partial('partials/logo') ?><span class="brand-name">Les jardins d'à côté</span></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav" data-nav-toggle>
      <svg width="18" height="14" viewBox="0 0 18 14" aria-hidden="true"><path d="M1 1h16M1 7h16M1 13h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Menu
    </button>
    <nav class="nav" id="nav" aria-label="Navigation principale">
      <a href="/univers"<?= $cur('/univers') ?>>Ce qu'on achète</a>
      <a href="/groupes"<?= $cur('/groupes') ?>>Les groupes</a>
      <a href="/comment-ca-marche"<?= $cur('/comment-ca-marche') ?>>Comment ça marche</a>
      <a href="/lancer-un-groupe"<?= $cur('/lancer-un-groupe') ?>>Lancer un groupe</a>
      <?php if (Auth::isVolunteer()): ?><a href="/benevoles"<?= $cur('/benevoles') ?>>Bénévoles</a><?php endif; ?>
      <?php if (Auth::isAdmin()): ?><a href="/admin"<?= $cur('/admin') ?>>Back-office</a><?php endif; ?>
      <?php if ($user): ?><a href="/compte"<?= $cur('/compte') ?>>Mon compte</a><?php else: ?><a href="/connexion"<?= $cur('/connexion') ?>>Connexion</a><?php endif; ?>
      <a href="/panier"<?= $cur('/panier') ?>>Panier<?php if ($cartCount): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?></a>
      <?php if (!$user): ?><a class="nav-cta" href="/inscription">Rejoindre</a><?php endif; ?>
    </nav>
  </div>
</header>
<?php $fl = flashes(); if ($fl): ?>
<div class="wrap flash-zone" role="status"><?php foreach ($fl as $f): ?><div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<main id="main">
<?= $content ?>
</main>
<footer class="site-foot">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <span class="foot-brand">Les jardins d'à côté</span>
        <p>Des groupes d'achat de voisins, commune par commune : plants potagers, arbres fruitiers, aromatiques, graines. On choisit ensemble, on achète en gros, on paie bien moins cher qu'en jardinerie.</p>
        <p class="small"><?= e(setting('association_address')) ?><br><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p>
      </div>
      <div>
        <h4>Participer</h4>
        <ul>
          <li><a href="/groupes">Trouver mon groupe</a></li>
          <li><a href="/comment-ca-marche">Comment ça marche</a></li>
          <li><a href="/lancer-un-groupe">Lancer un groupe dans ma commune</a></li>
        </ul>
      </div>
      <div>
        <h4>Informations</h4>
        <ul>
          <li><a href="/cgv">Conditions générales</a></li>
          <li><a href="/mentions-legales">Mentions légales</a></li>
          <li><a href="/confidentialite">Données personnelles</a></li>
          <li><a href="/credits-photos">Crédits photos</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-base"><span>© <?= date('Y') ?> <?= e($siteName) ?> — <?= e(setting('association_legal')) ?></span><span><a href="https://www.lesjardinsdacote.fr/">lesjardinsdacote.fr</a></span></div>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?= $scripts ?? '' ?>
</body>
</html>
