<?php
declare(strict_types=1);

final class Packs
{
    public const DEFAULT_MARKET_NOTE = "Prix marché : prix public d'un sachet de la même variété en jardinerie.";

    public static function find(int $id): ?array
    {
        $p = db()->one('SELECT * FROM packs WHERE id = ?', [$id]);
        return $p ? self::withItems([$p])[0] : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $p = db()->one('SELECT * FROM packs WHERE slug = ?', [$slug]);
        return $p ? self::withItems([$p])[0] : null;
    }

    public static function withItems(array $packs): array
    {
        if (!$packs) return [];
        $ids = implode(',', array_map(fn($p) => (int)$p['id'], $packs));
        $by = [];
        foreach (db()->all("SELECT * FROM pack_items WHERE pack_id IN ($ids) ORDER BY position, id") as $it) $by[(int)$it['pack_id']][] = $it;
        foreach ($packs as &$p) {
            $p['items'] = $by[(int)$p['id']] ?? [];
            $p['summary'] = self::summary($p['items']);
            $p += self::savings($p);
        }
        return $packs;
    }

    /**
     * Valeur du pack au prix du marché (somme des prix de détail des variétés) et économie réalisée.
     * Retourne market = null si aucun prix marché n'est renseigné.
     */
    public static function savings(array $p): array
    {
        $market = 0.0; $known = 0;
        foreach ($p['items'] ?? [] as $it) {
            if ($it['market_price'] !== null && $it['market_price'] !== '') { $market += (float)$it['market_price']; $known++; }
        }
        $price = (float)$p['price'];
        if (!$known || $market <= $price) return ['market' => $known ? round($market, 2) : null, 'save' => 0.0, 'save_pct' => 0];
        $save = round($market - $price, 2);
        return ['market' => round($market, 2), 'save' => $save, 'save_pct' => (int)floor($save / $market * 100)];
    }

    /** Meilleure économie (en %) parmi des packs. */
    public static function bestSavingPct(array $packs): int
    {
        return $packs ? max(array_map(fn($p) => (int)($p['save_pct'] ?? 0), $packs)) : 0;
    }

    /** « 6 variétés · 230 graines » */
    public static function summary(array $items): string
    {
        if (!$items) return '';
        $byUnit = [];
        foreach ($items as $it) $byUnit[$it['unit']] = ($byUnit[$it['unit']] ?? 0) + (float)$it['quantity'];
        $parts = [count($items) . ' ' . plural(count($items), 'variété', 'variétés')];
        foreach ($byUnit as $u => $q) $parts[] = Cat::qty($q, $u);
        return implode(' · ', $parts);
    }

    public static function all(?string $category = null, bool $activeOnly = false): array
    {
        $w = []; $p = [];
        if ($category) { $w[] = 'category = ?'; $p[] = $category; }
        if ($activeOnly) $w[] = 'is_active = 1';
        return self::withItems(db()->all('SELECT * FROM packs' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY category, name', $p));
    }

    public static function uniqueSlug(string $name, ?int $except = null): string
    {
        $base = slugify($name);
        $slug = $base; $i = 2;
        while (db()->val('SELECT id FROM packs WHERE slug = ?' . ($except ? ' AND id <> ' . (int)$except : ''), [$slug])) $slug = $base . '-' . $i++;
        return $slug;
    }
}
