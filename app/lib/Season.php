<?php
declare(strict_types=1);

/**
 * Campagne mise en avant : les graines. Catalogue de variétés bio et reproductibles,
 * chacune avec la photo de la variété elle-même. Les autres univers restent ouverts.
 *
 * Le fournisseur et ses références restent internes (back-office) : rien de ce fichier
 * ne l'affiche au public. Prix relevés au catalogue professionnel en novembre 2026.
 */
final class Season
{
    public const SUPPLIER = 'Agrosemens';
    private const VAT = 1.055;

    /** Univers mis en avant cette saison (tous les univers restent ouverts). */
    public static function featured(string $cat): bool
    {
        return in_array($cat, array_map('trim', explode(',', (string)setting('season_focus', 'seeds'))), true);
    }

    /** Ordre d'affichage des familles dans le catalogue. */
    public const FAMILY_ORDER = ['Tomate', 'Aubergine', 'Poivron', 'Piment doux', 'Piment', 'Courgette', 'Courge', 'Concombre', 'Melon', 'Racine',
        'Salade', 'Feuille', 'Chou', 'Légumineuse', 'Maïs', 'Oignon', 'Fruit', 'Fleur', 'Aromatique'];

    /** Variétés mises en avant sur l'accueil (références internes). */
    public const SHOWCASE = ['TOM31', 'POT10', 'BET04', 'SOU01', 'TOM04', 'POT04', 'PIM14', 'CON26', 'RAD19', 'BLT14', 'BOU01', 'MEL01'];

    /**
     * Catalogue de la saison, tenu dans app/data/seeds_catalog.php :
     * [famille affichée, variété, nom latin, réf., prix HT du conditionnement, quantité achetée, unité, portion par pack, texte, photo Commons ou null]
     * Références, conditionnements et prix : catalogue professionnel 2027 du semencier. Photos : uniquement des images libres
     * (Wikimedia Commons) vérifiées une à une ; une variété sans photo libre fidèle est proposée sans photo.
     */
    public static function catalog(): array
    {
        static $c = null;
        return $c ??= require APP_DIR . '/data/seeds_catalog.php';
    }

    /**
     * Installe le catalogue de la saison : met à jour ou crée chaque variété (clé : référence),
     * désactive les autres graines, et remet la liste complète au vote des ventes pas encore ouvertes.
     */
    public static function install(): int
    {
        $db = db();
        $market = (float)$db->val("SELECT market_price FROM varieties WHERE category = 'seeds' AND market_price IS NOT NULL LIMIT 1") ?: Demo::MARKET;
        $keep = [];
        $order = array_flip(self::FAMILY_ORDER);
        foreach (self::catalog() as $i => $r) {
            [$fam, $var, $latin, $ref, $ht, $qty, $unit, $portion, $note, $photo] = $r;
            // Dans chaque famille, les variétés illustrées passent d'abord.
            $pos = ($order[$fam] ?? 50) * 1000 + ($photo ? 0 : 500) + $i % 500;
            $row = ['category' => 'seeds', 'name' => $fam, 'variety' => $var, 'latin_name' => $latin, 'note' => mb_substr($note, 0, 255),
                'photo' => $photo ? 'commons:' . $photo : null, 'portion_qty' => $portion, 'unit' => $unit,
                'purchase_price' => round($ht * self::VAT, 2), 'purchase_qty' => $qty, 'market_price' => $market,
                'supplier' => self::SUPPLIER . ' (catalogue pro 2027)', 'supplier_ref' => $ref, 'position' => $pos, 'is_active' => 1];
            $id = $ref
                ? (int)$db->val("SELECT id FROM varieties WHERE category = 'seeds' AND supplier_ref = ? ORDER BY id LIMIT 1", [$ref])
                : (int)$db->val("SELECT id FROM varieties WHERE category = 'seeds' AND name = ? AND variety = ? ORDER BY id LIMIT 1", [$fam, $var]);
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($row)));
                $db->exec("UPDATE varieties SET $set WHERE id = ?", [...array_values($row), $id]);
            } else {
                $id = $db->insert('varieties', $row);
            }
            $keep[] = $id;
        }
        $in = implode(',', $keep);
        $db->exec("UPDATE varieties SET is_active = 0 WHERE category = 'seeds' AND id NOT IN ($in)");
        foreach ($db->col("SELECT id FROM sales WHERE category = 'seeds' AND status IN ('waiting','voting')") as $sid) {
            Varieties::setForSale((int)$sid, $keep);
        }
        return count($keep);
    }
}
