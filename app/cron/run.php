<?php
/**
 * Tâche planifiée — à exécuter toutes les 5 minutes.
 * Hostinger (hPanel → Avancé → Tâches Cron) :
 *   /usr/bin/php /home/VOTRE_UTILISATEUR/domains/lesjardinsdacote.fr/app/cron/run.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
if ($GLOBALS['CONFIG'] === null) { fwrite(STDERR, "Configuration absente.\n"); exit(1); }
$res = Tasks::run();
echo date('c') . ' ' . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
