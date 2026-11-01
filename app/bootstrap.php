<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));
define('APP_VERSION', '2.0.0');

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fra');

spl_autoload_register(function (string $class): void {
    $f = APP_DIR . '/lib/' . $class . '.php';
    if (is_file($f)) require $f;
});
require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/Photo.php';

$configFile = APP_DIR . '/config.php';
$GLOBALS['CONFIG'] = is_file($configFile) ? require $configFile : null;

if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', is_dir(ROOT_DIR . '/public_html') ? ROOT_DIR . '/public_html' : ROOT_DIR);
}

$isDev = ($GLOBALS['CONFIG']['env'] ?? 'prod') === 'dev';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(APP_DIR . '/storage/logs')) @mkdir(APP_DIR . '/storage/logs', 0750, true);
ini_set('error_log', APP_DIR . '/storage/logs/php-error.log');

set_exception_handler(function (Throwable $e) use ($isDev): void {
    error_log('[' . date('c') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
        exit(1);
    }
    if (!headers_sent()) http_response_code(500);
    if ($isDev) {
        echo '<pre style="padding:20px;white-space:pre-wrap">' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        $f = APP_DIR . '/views/errors/500.php';
        if (is_file($f)) { include $f; } else { echo 'Une erreur est survenue.'; }
    }
    exit;
});
