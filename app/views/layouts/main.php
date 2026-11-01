<?php
$siteName = setting('association_name', "Les Jardins d'à Côté");
$pageTitle = isset($title) && $title ? $title . ' · ' . $siteName : $siteName . ' — Achats groupés de graines et plantes à Toulouse';
$desc = $description ?? 'Achats groupés solidaires de semences bio, plantes et terreau vrac à Toulouse. Plus nous sommes nombreux, plus le prix baisse pour tout le monde.';
$og = $og ?? [];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$cur = fn(string $p) => ($p === '/' ? $path === '/' : str_starts_with($path, $p)) ? ' aria-current="page"' : '';
$user = Auth::user();
$cartCount = Cart::count();
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#232B2D">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preload" href="/assets/fonts/fraunces-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/caveat-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="<?= e($og['type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($og['title'] ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($og['description'] ?? $desc) ?>">
<meta property="og:url" content="<?= e($og['url'] ?? abs_url($path)) ?>">
<meta property="og:image" content="<?= e($og['image'] ?? abs_url('/assets/img/og-default.png')) ?>">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<?= $head ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="sr-only" href="#main">Aller au contenu</a>
<header class="site-head">
  <div class="wrap">
    <a class="brand" href="/" aria-label="<?= e($siteName) ?> — accueil">
      <?php partial('partials/logo') ?>
      <span class="brand-name">Les Jardins <em>d'à Côté</em></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav" data-nav-toggle>
      <svg width="18" height="14" viewBox="0 0 18 14" aria-hidden="true"><path d="M1 1h16M1 7h16M1 13h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Menu
    </button>
    <nav class="nav" id="nav" aria-label="Navigation principale">
      <a href="/campagnes"<?= $cur('/campagne') ?>>Campagnes</a>
      <a href="/comment-ca-marche"<?= $cur('/comment-ca-marche') ?>>Comment ça marche</a>
      <?php if (Auth::isVolunteer()): ?><a href="/benevoles"<?= $cur('/benevoles') ?>>Espace bénévoles</a><?php else: ?><a href="/devenir-benevole"<?= $cur('/devenir-benevole') ?>>Devenir bénévole</a><?php endif; ?>
      <?php if (Auth::isAdmin()): ?><a href="/admin"<?= $cur('/admin') ?>>Back-office</a><?php endif; ?>
      <?php if ($user): ?><a href="/compte"<?= $cur('/compte') ?>>Mon compte</a><?php else: ?><a href="/connexion"<?= $cur('/connexion') ?>>Connexion</a><?php endif; ?>
      <a class="cart-link" href="/panier"<?= $cur('/panier') ?>>Panier<span class="cart-count" data-cart-count<?= $cartCount ? '' : ' hidden' ?>><?= $cartCount ?></span></a>
    </nav>
  </div>
</header>
<?php $fl = flashes(); if ($fl): ?>
<div class="wrap flash-zone" role="status">
  <?php foreach ($fl as $f): ?><div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<main id="main">
<?= $content ?>
</main>
<footer class="site-foot">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <h4><?= e($siteName) ?></h4>
        <p>Une association de voisins qui achète en gros, pèse, ensache et distribue ensemble. Les économies restent dans le quartier.</p>
        <p class="small"><?= e(setting('association_address')) ?><br><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p>
      </div>
      <div>
        <h4>Participer</h4>
        <ul>
          <li><a href="/campagnes">Campagnes en cours</a></li>
          <li><a href="/comment-ca-marche">Les paliers expliqués</a></li>
          <li><a href="/devenir-benevole">Devenir bénévole</a></li>
        </ul>
      </div>
      <div>
        <h4>Infos</h4>
        <ul>
          <li><a href="/cgv">Conditions générales &amp; mandat d'achat groupé</a></li>
          <li><a href="/mentions-legales">Mentions légales</a></li>
          <li><a href="/confidentialite">Données personnelles</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-base">
      <span>© <?= date('Y') ?> <?= e($siteName) ?> — <?= e(setting('association_legal')) ?></span>
      <span>Hébergé en Europe</span>
    </div>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?= $scripts ?? '' ?>
</body>
</html>
