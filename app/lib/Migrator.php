<?php
declare(strict_types=1);

/** Mise à jour automatique de la base au premier passage après un déploiement. */
final class Migrator
{
    public const VERSION = 12;
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
            if ($v < 8) self::to8();
            if ($v < 9) self::to9();
            if ($v < 10) self::to10();
            if ($v < 11) self::to11();
            if ($v < 12) self::to12();
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

    /** v8 → v9 : connexion mémorisée (application du back-office), témoignages, coordonnées des groupes. */
    private static function to9(): void
    {
        self::execFile(APP_DIR . '/sql/schema.sql');
        if (!self::hasColumn('groups', 'lat')) db()->pdo->exec('ALTER TABLE `groups` ADD COLUMN lat DECIMAL(9,6) NULL, ADD COLUMN lng DECIMAL(9,6) NULL');
        Geo::fillGroups();
        set_setting('schema_version', '9');
        audit('migrate', 'schéma v9');
    }

    /** v7 → v8 : poste de pilotage (passages des agents, liste « à faire », pause et autonomie). */
    private static function to8(): void
    {
        self::execFile(APP_DIR . '/sql/schema.sql');
        Pilot::seedTodos();
        set_setting('schema_version', '8');
        audit('migrate', 'schéma v8');
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

    /** v9 → v10 : saison centrée sur les graines — nouveau catalogue (photos de chaque variété), démonstration recomposée. */
    private static function to10(): void
    {
        set_setting('season_focus', 'seeds');
        $n = Season::install();
        if (setting('demo_data') === '1') { Demo::purge(); Demo::seed(); }
        set_setting('schema_version', '10');
        audit('migrate', 'schéma v10 : ' . $n . ' variétés de graines au catalogue de la saison');
    }

    /** v10 → v11 : tous les univers rouverts (graines mises en avant), catalogue de graines étendu, démonstration d'origine. */
    private static function to11(): void
    {
        set_setting('season_focus', 'seeds');
        $n = Season::install();
        if (setting('demo_data') === '1') { Demo::purge(); Demo::seed(); }
        if (!db()->val("SELECT id FROM todos WHERE title LIKE 'Confirmer les prix des graines%'")) {
            Pilot::addTodo('Confirmer les prix des graines avant la première commande',
                'Le site du semencier était hors ligne lors de la mise à jour : 87 variétés ont un prix estimé d\'après la gamme (mention « prix estimé » dans Admin → Variétés). Les vérifier sur le tarif pro avant de passer commande.',
                'plan', '/admin/varietes?univers=seeds');
        }
        set_setting('schema_version', '11');
        audit('migrate', 'schéma v11 : ' . $n . ' variétés de graines au catalogue');
    }

    /**
     * v11 → v12 : site en réel. Catalogue de graines aligné sur le catalogue professionnel 2027 (références et prix réels),
     * pack de graines à 19 €, exemples fictifs conservés mais signalés un par un et fermés aux inscriptions.
     */
    private static function to12(): void
    {
        if (setting('seed_pack_price', '') === '') set_setting('seed_pack_price', '19');
        $n = Season::install();
        if (setting('demo_data') === '1') { Demo::purge(); Demo::seed(); }
        db()->exec("UPDATE todos SET done_at = NOW() WHERE done_at IS NULL AND title LIKE 'Confirmer les prix des graines%'");
        set_setting('schema_version', '12');
        audit('migrate', 'schéma v12 : ' . $n . ' variétés de graines, prix du catalogue 2027, site en réel');
    }
}
