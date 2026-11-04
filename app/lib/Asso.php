<?php
declare(strict_types=1);

/**
 * Règles « association » : frais de fonctionnement et de logistique par commande (dont une part pour
 * le fonds plantations), adhésion annuelle, transparence des montants.
 * Les produits sont refacturés à prix coûtant dans le cadre d'un mandat d'achat : seuls ces frais
 * reviennent à l'association.
 */
final class Asso
{
    public const DEFAULT_MARKET_NOTE = "Prix publics constatés en magasin de jardinage (relevés en novembre 2026) : un sachet ou un plant de la même variété, à l'unité.";

    private static function num(string $key, string $default): float
    {
        return max(0, round((float)str_replace(',', '.', (string)setting($key, $default)), 2));
    }

    /** Frais de fonctionnement et de logistique, par commande. */
    public static function fee(): float { return self::num('order_fee', '5'); }

    /** Part de ces frais versée au fonds plantations. */
    public static function fundShare(): float { return min(self::fee(), self::num('order_fee_fund', '1')); }

    /** Cotisation annuelle (0 = adhésion gratuite, mais toujours explicite). */
    public static function membershipFee(): float { return self::num('membership_fee', '0'); }

    public static function year(): int { return (int)date('Y'); }

    public static function isMember(int $userId, ?int $year = null): bool
    {
        return (bool)db()->val("SELECT COUNT(*) FROM memberships m LEFT JOIN orders o ON o.id = m.order_id
            WHERE m.user_id = ? AND m.year = ? AND (m.order_id IS NULL OR m.amount = 0 OR o.status <> 'cancelled')", [$userId, $year ?? self::year()]);
    }

    /** Enregistre l'adhésion de l'année (rattachée à la commande qui l'a payée, le cas échéant). */
    public static function join(int $userId, float $amount, ?int $orderId = null): void
    {
        db()->exec('INSERT INTO memberships (user_id, year, amount, order_id) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), order_id = VALUES(order_id)', [$userId, self::year(), $amount, $orderId]);
    }

    /**
     * Ventilation lisible d'un panier : produits à prix coûtant, frais par commande (une commande par groupe),
     * part plantations, adhésion éventuelle.
     */
    public static function breakdown(array $items, ?int $userId): array
    {
        $goods = round(array_sum(array_map(fn($i) => (float)$i['total'], $items)), 2);
        $n = count($items);
        $fee = round(self::fee() * $n, 2);
        $fund = round(self::fundShare() * $n, 2);
        $needsMembership = $userId ? !self::isMember($userId) : true;
        $membership = $needsMembership ? self::membershipFee() : 0.0;
        $market = round(array_sum(array_map(fn($i) => (float)($i['market'] ?? 0), $items)), 2);
        return ['market' => $market, 'saved' => max(0, round($market - $goods - $fee, 2)), 'goods' => $goods, 'orders' => $n, 'fee' => $fee, 'fee_unit' => self::fee(), 'fund' => $fund, 'fund_unit' => self::fundShare(),
            'needs_membership' => $needsMembership, 'membership' => $membership, 'total' => round($goods + $fee + $membership, 2)];
    }

    /** Montant réuni pour les plantations (commandes dont le lot est complet). */
    public static function fundTotal(): float
    {
        return (float)db()->val("SELECT COALESCE(SUM(fund_amount), 0) FROM orders WHERE status IN ('confirmed','ready','collected')");
    }

    public static function stats(): array
    {
        return [
            'fund' => self::fundTotal(),
            'members' => (int)db()->val('SELECT COUNT(DISTINCT user_id) FROM memberships WHERE year = ?', [self::year()]),
            'saved' => (float)db()->val("SELECT COALESCE(SUM(GREATEST((SELECT COALESCE(SUM(pi.market_price),0) FROM pack_items pi WHERE pi.pack_id = o.pack_id) - o.unit_price, 0) * o.quantity), 0)
                FROM orders o WHERE o.status IN ('confirmed','ready','collected')"),
        ];
    }

    public static function president(): string { return (string)setting('president_name', ''); }
    public static function treasurer(): string { return (string)setting('treasurer_name', ''); }
}
