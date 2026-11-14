<?php
$siteName = setting('association_name', "Les Jardins d'à Côté");
$pageTitle = isset($title) && $title ? $title . ' · ' . $siteName : $siteName . ' — Plants, arbres fruitiers et graines en achat groupé entre voisins';
$desc = $description ?? 'Dans votre commune, un groupe de voisins achète en gros plants potagers, arbres fruitiers, aromatiques et graines, et les prépare avec des bénévoles.';
$og = $og ?? [];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$cur = fn(string $p) => ($p === '/' ? $path === '/' : str_starts_with($path, $p)) ? ' aria-current="page"' : '';
$user = Auth::user();
$cartCount = Cart::count(Auth::id());
// Back-office : présentation « application » (installable sur téléphone), sans en-tête ni pied de page publics.
$adminUi = ($bodyClass ?? '') === 'admin' || !empty($adminApp);
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
<script>document.documentElement.classList.add('js');try{if(sessionStorage.getItem('jdc-canopy')&&!matchMedia('(prefers-reduced-motion: reduce)').matches){document.documentElement.classList.add('canopy-arrive');}sessionStorage.removeItem('jdc-canopy');}catch(e){}</script>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($og['title'] ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($og['description'] ?? $desc) ?>">
<meta property="og:url" content="<?= e(abs_url($path)) ?>">
<meta property="og:image" content="<?= e($og['image'] ?? abs_url('/assets/img/og-default.png')) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php if ($adminUi): ?>
<link rel="manifest" href="/admin/app.webmanifest">
<link rel="apple-touch-icon" href="/assets/img/app-180.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Jardins Pilotage">
<?php endif; ?>
<?= $head ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="sr-only" href="#main">Aller au contenu</a>
<?php if ($adminUi): $toVal = Auth::isAdmin() ? Agents::pendingCount() : 0; ?>
<header class="app-bar">
  <a class="app-brand" href="/admin"><?php partial('partials/logo') ?><span>Pilotage</span></a>
  <?php if (Auth::isAdmin()): ?>
  <div class="app-actions">
    <a href="/" class="app-link">Voir le site</a>
    <form method="post" action="/deconnexion"><?= csrf_field() ?><input type="hidden" name="to" value="/admin/connexion"><button class="app-link" type="submit">Déconnexion</button></form>
  </div>
  <?php endif; ?>
</header>
<?php else: ?>
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
      <a href="/association"<?= $cur('/association') ?>>L'association</a>
      <a href="/lancer-un-groupe"<?= $cur('/lancer-un-groupe') ?>>Lancer un groupe</a>
      <?php if (Auth::isVolunteer()): ?><a href="/benevoles"<?= $cur('/benevoles') ?>>Bénévoles</a><?php endif; ?>
      <?php if (Auth::isAdmin()): ?><a href="/admin"<?= $cur('/admin') ?>>Back-office</a><?php endif; ?>
      <?php if ($user): ?><a href="/compte"<?= $cur('/compte') ?>>Mon compte</a><?php else: ?><a href="/connexion"<?= $cur('/connexion') ?>>Connexion</a><?php endif; ?>
      <a href="/panier"<?= $cur('/panier') ?>>Panier<?php if ($cartCount): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?></a>
      <?php if (!$user): ?><a class="nav-cta" href="/inscription">Rejoindre</a><?php endif; ?>
    </nav>
  </div>
</header>
<?php endif; ?>
<?php $fl = flashes(); if ($fl): ?>
<div class="wrap flash-zone" role="status"><?php foreach ($fl as $f): ?><div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<main id="main">
<?= $content ?>
</main>
<?php if ($adminUi): ?>
<?php if (Auth::isAdmin()): ?>
<nav class="app-tabs" aria-label="Raccourcis">
  <a href="/admin"<?= $path === '/admin' ? ' aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4zm10 7h6v-9h-6zM4 20h6v-5H4zm10-11h6V4h-6z"/></svg>Pilotage</a>
  <a href="/admin/a-valider"<?= $cur('/admin/a-valider') ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7"/></svg>À valider<?php if ($toVal): ?><b class="app-badge"><?= $toVal ?></b><?php endif; ?></a>
  <a href="/admin/commandes"<?= $cur('/admin/commandes') ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 13H7zM9 7a3 3 0 016 0"/></svg>Commandes</a>
  <a href="/admin/parametres"<?= $cur('/admin/parametres') ?>><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/></svg>Réglages</a>
</nav>
<?php endif; ?>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/admin/sw.js',{scope:'/admin'}).catch(function(){});}</script>
<?php else: ?>
<footer class="site-foot">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <span class="foot-brand">Les jardins d'à côté</span>
        <p>Des groupes d'achat de voisins, commune par commune : plants potagers, arbres fruitiers, aromatiques, graines. Une association loi 1901 : on choisit ensemble, l'association achète en notre nom au prix coûtant, et une part des frais plante des arbres.</p>
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
          <li><a href="/association">L'association et ses engagements</a></li>
          <li><a href="/cgv">Conditions du mandat d'achat</a></li>
          <li><a href="/mentions-legales">Mentions légales</a></li>
          <li><a href="/confidentialite">Données personnelles</a></li>
          <li><a href="/credits-photos">Crédits photos</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-base"><span>© <?= date('Y') ?> <?= e($siteName) ?> — <?= e(setting('association_legal')) ?></span><span><a href="https://www.lesjardinsdacote.fr/">lesjardinsdacote.fr</a></span></div>
  </div>
</footer>
<?php endif; ?>
<?php if (!$adminUi) partial('partials/canopy'); ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/garden-fx.js') ?>" defer></script>
<?= $scripts ?? '' ?>
</body>
</html>
