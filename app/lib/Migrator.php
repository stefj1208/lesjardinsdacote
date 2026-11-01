<?php
declare(strict_types=1);

/** Mise à jour automatique de la base au premier passage après un déploiement. */
final class Migrator
{
    public const VERSION = 2;

    public static function execFile(string $file): void
    {
        $sql = (string)file_get_contents($file);
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim((string)preg_replace('/^--.*$/m', '', $stmt));
            if ($stmt !== '') db()->pdo->exec($stmt);
        }
    }

    public static function run(): void
    {
        $v = (int)(db()->val("SELECT v FROM settings WHERE k = 'schema_version'") ?? 0);
        if ($v >= self::VERSION) return;
        if ((int)db()->val("SELECT GET_LOCK('jdc_migrate', 10)") !== 1) return;
        try {
            $v = (int)(db()->val("SELECT v FROM settings WHERE k = 'schema_version'") ?? 0);
            if ($v < 2) self::to2();
        } finally {
            db()->val("SELECT RELEASE_LOCK('jdc_migrate')");
        }
    }

    /** v1 (campagnes à paliers) → v2 (groupes, votes, packs, lots). */
    private static function to2(): void
    {
        $pdo = db()->pdo;
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $hasV1 = (bool)db()->val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'campaigns'");
        if ($hasV1) {
            foreach (['order_items', 'orders', 'campaign_tiers', 'campaigns', 'product_packs', 'products', 'supplier_batches', 'suppliers', 'pickup_windows', 'shift_signups', 'shifts'] as $t) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $t . '`');
            }
            foreach (['default_margin_rate', 'default_waste_coeff', 'default_packet_cost', 'default_vat_rate', 'deposit_fee', 'demo_supplier_id'] as $k) {
                db()->exec('DELETE FROM settings WHERE k = ?', [$k]);
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        self::execFile(APP_DIR . '/sql/schema.sql');
        set_setting('schema_version', '2');
        if ($hasV1 && setting('demo_data') === '1') Demo::seed();
        audit('migrate', 'schéma v2');
    }
}
