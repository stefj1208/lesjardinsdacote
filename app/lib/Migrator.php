<?php
declare(strict_types=1);

/** Mise à jour automatique de la base au premier passage après un déploiement. */
final class Migrator
{
    public const VERSION = 7;
    private static bool $reseed = false;

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
            if ($v < 3) self::to3();
            if ($v < 4) self::to4();
            if ($v < 5) self::to5();
            if ($v < 6) self::to6();
            if ($v < 7) self::to7();
            if (self::$reseed) Demo::seedCatalog();
            if (self::$reseed && setting('demo_data') === '1') { Demo::purge(); Demo::seed(); }
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
        if ($hasV1) self::$reseed = true;
        audit('migrate', 'schéma v2');
    }

    /** v2 → v3 : prix marché par variété (affichage des économies), nouveaux packs de démonstration. */
    private static function to3(): void
    {
        $has = (bool)db()->val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pack_items' AND column_name = 'market_price'");
        if (!$has) db()->pdo->exec('ALTER TABLE pack_items ADD COLUMN market_price DECIMAL(8,2) NULL AFTER unit');
        db()->exec("INSERT IGNORE INTO settings (k, v) VALUES ('market_price_note', ?)", [Packs::DEFAULT_MARKET_NOTE]);
        set_setting('schema_version', '3');
        self::$reseed = true;
        audit('migrate', 'schéma v3');
    }

    private static function hasColumn(string $table, string $col): bool
    {
        return (bool)db()->val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $col]);
    }

    /** v4 → v5 : univers « Fleurs & vivaces », aromatiques en pot (les graines aromatiques rejoignent les graines). */
    private static function to5(): void
    {
        $enum = "ENUM('seeds','vegetables','fruit_trees','aromatics','plants') NOT NULL DEFAULT 'seeds'";
        foreach (['packs', 'varieties', 'sales'] as $t) db()->pdo->exec("ALTER TABLE `$t` MODIFY category $enum");
        db()->exec("UPDATE varieties SET category = 'seeds' WHERE category = 'aromatics' AND unit = 'g'");
        set_setting('schema_version', '5');
        self::$reseed = true;
        audit('migrate', 'schéma v5');
    }

    /**
     * v6 → v7 : file « À valider » des agents (tables inbox_messages et agent_items),
     * boîte mail entrante en IMAP, jeton d'API des agents.
     */
    private static function to7(): void
    {
        self::execFile(APP_DIR . '/sql/schema.sql');
        if ((string)setting('imap_user', '') === '') set_setting('imap_user', (string)setting('contact_email', ''));
        set_setting('schema_version', '7');
        audit('migrate', 'schéma v7');
    }

    /**
     * v5 → v6 : modèle associatif — frais de fonctionnement et de logistique par commande (dont une part
     * pour le fonds plantations), adhésion annuelle, comparaisons de prix sans nom d'enseigne.
     */
    private static function to6(): void
    {
        $pdo = db()->pdo;
        if (!self::hasColumn('orders', 'fee_amount')) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN goods_amount DECIMAL(10,2) NULL AFTER amount, ADD COLUMN fee_amount DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER goods_amount,
                ADD COLUMN fund_amount DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER fee_amount, ADD COLUMN membership_amount DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER fund_amount');
            $pdo->exec('UPDATE orders SET goods_amount = amount');
            // Commandes de démonstration : mêmes frais que les vraies, pour illustrer le fonds plantations.
            db()->exec("UPDATE orders SET fee_amount = ?, fund_amount = ?, amount = goods_amount + ? WHERE reference LIKE 'DEMO-%'", [Asso::fee(), Asso::fundShare(), Asso::fee()]);
        }
        self::execFile(APP_DIR . '/sql/schema.sql'); // table memberships et réglages par défaut
        set_setting('market_price_note', Asso::DEFAULT_MARKET_NOTE);
        set_setting('pack_fixed_costs', '0');
        if ((string)setting('president_name', '') === '') set_setting('president_name', 'François Stephan Jaune');
        if ((string)setting('treasurer_name', '') === '') set_setting('treasurer_name', 'Claudine Aurélie Tourail');
        if ((string)setting('publication_director', '') === '') set_setting('publication_director', 'François Stephan Jaune, président');
        set_setting('schema_version', '6');
        audit('migrate', 'schéma v6');
    }

    /** v3 → v4 : catalogue de variétés, vote des variétés (le pack est composé par le vote dans la limite du prix). */
    private static function to4(): void
    {
        $pdo = db()->pdo;
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DROP TABLE IF EXISTS votes');
        $pdo->exec('DROP TABLE IF EXISTS sale_packs');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        self::execFile(APP_DIR . '/sql/schema.sql'); // tables nouvelles (CREATE IF NOT EXISTS) et réglages par défaut
        if (!self::hasColumn('sales', 'price')) $pdo->exec('ALTER TABLE sales ADD COLUMN price DECIMAL(8,2) NOT NULL DEFAULT 30.00 AFTER max_per_order, ADD COLUMN max_choices TINYINT UNSIGNED NOT NULL DEFAULT 10 AFTER price, ADD COLUMN max_varieties SMALLINT UNSIGNED NULL AFTER max_choices');
        if (!self::hasColumn('pack_items', 'unit_cost')) $pdo->exec('ALTER TABLE pack_items MODIFY quantity DECIMAL(10,2) NOT NULL DEFAULT 1, ADD COLUMN unit_cost DECIMAL(8,3) NULL AFTER market_price, ADD COLUMN variety_id INT UNSIGNED NULL AFTER unit_cost');
        set_setting('schema_version', '4');
        self::$reseed = true;
        audit('migrate', 'schéma v4');
    }
}
