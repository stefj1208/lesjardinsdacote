<?php
declare(strict_types=1);

final class Packs
{
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
        }
        return $packs;
    }

    /** « 6 variétés · 230 graines » */
    public static function summary(array $items): string
    {
        if (!$items) return '';
        $byUnit = [];
        foreach ($items as $it) $byUnit[$it['unit']] = ($byUnit[$it['unit']] ?? 0) + (int)$it['quantity'];
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
