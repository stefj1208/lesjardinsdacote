<?php
declare(strict_types=1);

/** Panier enregistré en base (le pack composé par le vote y est déposé pour chaque votant). */
final class Cart
{
    public static function items(int $userId): array
    {
        $rows = db()->all("SELECT ci.*, s.title AS sale_title, s.status AS sale_status, s.sale_ends_at, s.batch_size, s.max_per_order, s.winning_pack_id,
                g.name AS group_name, g.slug AS group_slug, p.name AS pack_name, p.price, p.photo, p.slug AS pack_slug
            FROM cart_items ci JOIN sales s ON s.id = ci.sale_id JOIN `groups` g ON g.id = s.group_id JOIN packs p ON p.id = ci.pack_id
            WHERE ci.user_id = ? ORDER BY ci.created_at", [$userId]);
        foreach ($rows as &$r) {
            $r['valid'] = $r['sale_status'] === 'open' && strtotime((string)$r['sale_ends_at']) > time() && (int)$r['pack_id'] === (int)$r['winning_pack_id'];
            $r['total'] = round((float)$r['price'] * (int)$r['quantity'], 2);
            $market = (float)db()->val('SELECT COALESCE(SUM(market_price), 0) FROM pack_items WHERE pack_id = ?', [$r['pack_id']]);
            $r['saved'] = $market > (float)$r['price'] ? round(($market - (float)$r['price']) * (int)$r['quantity'], 2) : 0.0;
            $sale = ['id' => $r['sale_id'], 'batch_size' => $r['batch_size']];
            $r['batch'] = Sales::batchState($sale);
        }
        return $rows;
    }

    public static function count(?int $userId): int
    {
        if (!$userId) return isset($_SESSION['pending_cart']) ? 1 : 0;
        return (int)db()->val("SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN sales s ON s.id = ci.sale_id WHERE ci.user_id = ? AND s.status = 'open'", [$userId]);
    }

    public static function add(int $userId, int $saleId, int $packId, int $qty, string $source = 'manual'): void
    {
        $s = Sales::find($saleId);
        if (!$s || $s['status'] !== 'open') throw new DomainException('Cette vente n\'est pas ouverte.');
        if ((int)$s['winning_pack_id'] !== $packId) throw new DomainException('Ce pack n\'est pas en vente.');
        $qty = max(1, min((int)$s['max_per_order'], $qty));
        db()->exec('INSERT INTO cart_items (user_id, sale_id, pack_id, quantity, source) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)',
            [$userId, $saleId, $packId, $qty, $source]);
    }

    public static function remove(int $userId, int $saleId): void
    {
        db()->exec('DELETE FROM cart_items WHERE user_id = ? AND sale_id = ?', [$userId, $saleId]);
    }

    /** Ajout mémorisé avant connexion, appliqué juste après. */
    public static function applyPending(int $userId): void
    {
        $p = $_SESSION['pending_cart'] ?? null;
        unset($_SESSION['pending_cart']);
        if (!$p) return;
        try { self::add($userId, (int)$p['sale'], (int)$p['pack'], (int)$p['qty']); } catch (DomainException) {}
    }
}
