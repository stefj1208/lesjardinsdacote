<?php
declare(strict_types=1);

/**
 * Catalogue de variétés et composition d'un pack par le vote.
 *
 * Chaque variété a un prix d'achat en gros (ex. 9,90 € les 100 graines) et une portion
 * (ex. 10 graines) : le coût d'une portion en découle (0,99 €). Le pack d'un groupe réunit
 * les variétés les plus demandées, dans l'ordre des voix, tant que leur coût (plus le sachet)
 * tient dans le prix du pack. Son « prix marché » est la somme des prix publics des sachets
 * équivalents en jardinerie.
 */
final class Varieties
{
    public static function find(int $id): ?array
    {
        $v = db()->one('SELECT * FROM varieties WHERE id = ?', [$id]);
        return $v ? self::decorate($v) : null;
    }

    public static function all(?string $category = null, bool $activeOnly = false): array
    {
        $w = []; $p = [];
        if ($category) { $w[] = 'category = ?'; $p[] = $category; }
        if ($activeOnly) $w[] = 'is_active = 1';
        return array_map([self::class, 'decorate'], db()->all('SELECT * FROM varieties' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY category, position, name, variety', $p));
    }

    /** Ajoute coût de la portion, famille (premier mot) et libellé de portion. */
    public static function decorate(array $v): array
    {
        $v['unit_cost'] = self::unitCost($v);
        $v['family'] = self::family((string)$v['name']);
        $v['portion'] = Cat::qty((float)$v['portion_qty'], $v['unit']);
        return $v;
    }

    public static function unitCost(array $v): ?float
    {
        if ($v['purchase_price'] === null || $v['purchase_price'] === '' || (float)$v['purchase_qty'] <= 0) return null;
        return round((float)$v['purchase_price'] / (float)$v['purchase_qty'] * (float)$v['portion_qty'], 3);
    }

    public static function family(string $name): string
    {
        $w = preg_split('/\s+/u', trim($name))[0] ?? $name;
        return mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1);
    }

    /** Variétés soumises au vote d'une vente, avec leur nombre de voix. */
    public static function forSale(int $saleId): array
    {
        return array_map([self::class, 'decorate'], db()->all('SELECT v.*, (SELECT COUNT(*) FROM variety_votes vv WHERE vv.sale_id = sv.sale_id AND vv.variety_id = v.id) AS votes
            FROM sale_varieties sv JOIN varieties v ON v.id = sv.variety_id WHERE sv.sale_id = ? ORDER BY v.position, v.name, v.variety', [$saleId]));
    }

    public static function setForSale(int $saleId, array $ids): void
    {
        db()->exec('DELETE FROM sale_varieties WHERE sale_id = ?', [$saleId]);
        foreach (array_unique(array_filter(array_map('intval', $ids))) as $vid) db()->exec('INSERT IGNORE INTO sale_varieties (sale_id, variety_id) VALUES (?, ?)', [$saleId, $vid]);
        db()->exec('DELETE vv FROM variety_votes vv LEFT JOIN sale_varieties sv ON sv.sale_id = vv.sale_id AND sv.variety_id = vv.variety_id WHERE vv.sale_id = ? AND sv.sale_id IS NULL', [$saleId]);
    }

    public static function sachetCost(): float { return max(0, (float)str_replace(',', '.', (string)setting('sachet_cost', '0.10'))); }
    public static function fixedCosts(): float { return max(0, (float)str_replace(',', '.', (string)setting('pack_fixed_costs', '0'))); }

    /**
     * Compose le pack : variétés triées par voix (puis ordre du catalogue), ajoutées tant que le budget le permet.
     * Une variété trop chère pour le reste du budget est sautée au profit des suivantes.
     */
    public static function compose(array $candidates, float $price, ?int $maxVarieties = null): array
    {
        usort($candidates, fn($a, $b) => [-(int)($a['votes'] ?? 0), (int)$a['position'], (string)$a['name'], (string)$a['variety']]
            <=> [-(int)($b['votes'] ?? 0), (int)$b['position'], (string)$b['name'], (string)$b['variety']]);
        $sachet = self::sachetCost();
        $budget = $price - self::fixedCosts();
        $spent = 0.0; $in = []; $out = [];
        foreach ($candidates as $v) {
            $c = ($v['unit_cost'] ?? 0) + $sachet;
            if (($maxVarieties && count($in) >= $maxVarieties) || $spent + $c > $budget + 0.0001) { $out[] = $v; continue; }
            $spent += $c; $in[] = $v;
        }
        $market = array_sum(array_map(fn($v) => (float)($v['market_price'] ?? 0), $in));
        return ['in' => $in, 'out' => $out, 'cost' => round($spent + self::fixedCosts(), 2), 'budget' => round($budget, 2), 'market' => round($market, 2)];
    }

    /** Pack « virtuel » (même forme que Packs::withItems) pour l'affichage d'une composition. */
    public static function toPack(array $comp, float $price, string $name, string $category = 'seeds'): array
    {
        $items = array_map(fn($v) => ['name' => $v['name'], 'variety' => $v['variety'], 'latin_name' => $v['latin_name'], 'quantity' => $v['portion_qty'],
            'unit' => $v['unit'], 'note' => $v['note'], 'photo' => $v['photo'], 'market_price' => $v['market_price'], 'unit_cost' => $v['unit_cost'],
            'variety_id' => $v['id'], 'votes' => (int)($v['votes'] ?? 0)], $comp['in']);
        $p = ['id' => null, 'slug' => null, 'category' => $category, 'name' => $name, 'price' => $price, 'items' => $items,
            'photo' => $items[0]['photo'] ?? Cat::CATEGORIES[$category]['photo'] ?? null, 'tagline' => null, 'cost' => $comp['cost']];
        $p['summary'] = Packs::summary($items);
        return $p + Packs::savings($p);
    }

    /** Aperçu de chaque univers : nombre de variétés et exemple de pack composé au prix conseillé. */
    public static function overview(): array
    {
        $out = [];
        foreach (Cat::CATEGORIES as $cat => $c) {
            $list = self::all($cat, true);
            $price = Cat::packPrice($cat);
            $ex = $list ? self::toPack(self::compose($list, $price), $price, 'Exemple de pack ' . mb_strtolower($c['label']), $cat) : null;
            $sale = db()->one("SELECT s.id, s.status, g.name AS group_name, g.slug AS group_slug FROM sales s JOIN `groups` g ON g.id = s.group_id
                WHERE s.category = ? AND g.status = 'active' AND s.status IN ('voting','open','waiting') ORDER BY FIELD(s.status,'open','voting','waiting'), s.id LIMIT 1", [$cat]);
            $out[$cat] = $c + ['key' => $cat, 'count' => count($list), 'varieties' => $list, 'example' => $ex, 'price' => $price, 'sale' => $sale];
        }
        return $out;
    }

    /** Enregistre la composition comme pack définitif (utilisé à la clôture du vote). */
    public static function createPack(array $comp, array $sale): int
    {
        $db = db();
        $name = 'Le pack ' . self::groupShort((string)$sale['group_name']) . ' · ' . Cat::label($sale['category']);
        $n = count($comp['in']);
        $id = $db->insert('packs', ['slug' => Packs::uniqueSlug($name . ' ' . date('Y')), 'category' => $sale['category'], 'name' => $name,
            'tagline' => $n . ' variétés choisies par le vote des membres de ' . $sale['group_name'] . '.',
            'description' => 'Ce pack a été composé par le vote des membres du groupe : les variétés les plus demandées ont été retenues, dans l\'ordre des voix, jusqu\'à atteindre le prix du pack.',
            'price' => $sale['price'], 'photo' => $comp['in'][0]['photo'] ?? null, 'is_active' => 1]);
        foreach ($comp['in'] as $i => $v) {
            $db->insert('pack_items', ['pack_id' => $id, 'position' => $i, 'name' => $v['name'], 'variety' => $v['variety'], 'latin_name' => $v['latin_name'],
                'quantity' => $v['portion_qty'], 'unit' => $v['unit'], 'market_price' => $v['market_price'], 'unit_cost' => $v['unit_cost'],
                'variety_id' => $v['id'], 'note' => $v['note'], 'photo' => $v['photo']]);
        }
        return $id;
    }

    /** « Les Jardins d'à Côté de Pins-Justaret » → « de Pins-Justaret ». */
    public static function groupShort(string $groupName): string
    {
        return preg_match("/(d(?:e |')\\S.*)$/u", preg_replace("/^Les Jardins d'à Côté\\s*/ui", '', $groupName) ?? $groupName, $m) ? $m[1] : $groupName;
    }

    public static function families(array $varieties): array
    {
        $f = [];
        foreach ($varieties as $v) $f[$v['family']] = ($f[$v['family']] ?? 0) + 1;
        return $f;
    }
}
