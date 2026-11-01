<?php
declare(strict_types=1);

// Le dossier "app" se place à côté de public_html (recommandé) ou à l'intérieur.
$appDir = is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/app';
define('PUBLIC_DIR', __DIR__);
require $appDir . '/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(self), geolocation=()');

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

// Assistant d'installation tant que la configuration n'existe pas.
if ($GLOBALS['CONFIG'] === null || !is_file(APP_DIR . '/storage/installed.lock')) {
    start_session();
    require APP_DIR . '/controllers/install.php';
    exit;
}

start_session();
Migrator::run();
$_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);

$router = new Router();
require APP_DIR . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
