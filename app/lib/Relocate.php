<?php
declare(strict_types=1);

/**
 * Déménagement ponctuel vers le domaine lesjardinsdacote.fr (même compte Hostinger, même base).
 * L'ancienne installation, qui possède config.php, le recopie dans la nouvelle (avec la nouvelle adresse),
 * ainsi que le verrou d'installation et les photos, puis adopte elle-même la nouvelle adresse :
 * toutes ses pages redirigent alors vers le nouveau domaine.
 */
final class Relocate
{
    public const TARGET_URL = 'https://lesjardinsdacote.fr';
    public const TARGET_DIR = '/home/u183108258/domains/lesjardinsdacote.fr/public_html/site';

    public static function tick(): void
    {
        if (setting('relocated_to', '') === self::TARGET_URL) return;
        $target = self::TARGET_DIR;
        if (!is_dir($target . '/app') || !is_file(APP_DIR . '/config.php')) return;
        if (realpath($target . '/app') === realpath(APP_DIR)) return; // on est déjà la nouvelle installation
        try {
            $cfg = (string)file_get_contents(APP_DIR . '/config.php');
            $new = preg_replace("/('base_url'\s*=>\s*)'[^']*'/", "\$1'" . self::TARGET_URL . "'", $cfg, 1, $n);
            if (!$n || $new === null) throw new RuntimeException('base_url introuvable dans config.php');
            if (!is_file($target . '/app/config.php')) {
                if (file_put_contents($target . '/app/config.php', $new, LOCK_EX) === false) throw new RuntimeException('écriture de la configuration impossible');
                @chmod($target . '/app/config.php', 0640);
            }
            @mkdir($target . '/app/storage', 0750, true);
            if (is_file(APP_DIR . '/storage/installed.lock')) @copy(APP_DIR . '/storage/installed.lock', $target . '/app/storage/installed.lock');
            // Racine du nouveau site : tout est servi depuis site/public_html (le reste du dépôt reste privé).
            $root = dirname($target);
            if (is_file(ROOT_DIR . '/deploy/racine.htaccess') && !is_file($root . '/.htaccess')) @copy(ROOT_DIR . '/deploy/racine.htaccess', $root . '/.htaccess');
            foreach (['uploads/photos', 'uploads/produits'] as $d) self::copyDir(PUBLIC_DIR . '/' . $d, $target . '/public_html/' . $d);
            // L'ancienne adresse redirige désormais vers la nouvelle.
            file_put_contents(APP_DIR . '/config.php', $new, LOCK_EX);
            set_setting('relocated_to', self::TARGET_URL);
            audit('relocate', 'installation recopiée vers ' . self::TARGET_URL);
        } catch (Throwable $e) {
            error_log('[relocate] ' . $e->getMessage());
        }
    }

    private static function copyDir(string $from, string $to): void
    {
        if (!is_dir($from)) return;
        @mkdir($to, 0755, true);
        foreach (scandir($from) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $s = $from . '/' . $f; $d = $to . '/' . $f;
            if (is_dir($s)) self::copyDir($s, $d);
            elseif (!is_file($d)) @copy($s, $d);
        }
    }
}
