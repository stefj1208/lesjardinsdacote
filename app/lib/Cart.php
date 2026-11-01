<?php
declare(strict_types=1);

/** Panier mixte en session : plusieurs campagnes, plusieurs contenances. */
final class Cart
{
    public static function raw(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    public static function add(int $campaignId, float $portion, int $packets): void
    {
        $key = $campaignId . ':' . rtrim(rtrim(number_format($portion, 3, '.', ''), '0'), '.');
        $cur = $_SESSION['cart'][$key] ?? 0;
        $_SESSION['cart'][$key] = max(0, min(999, $cur + $packets));
        if ($_SESSION['cart'][$key] === 0) unset($_SESSION['cart'][$key]);
    }

    public static function set(string $key, int $packets): void
    {
        if ($packets <= 0) unset($_SESSION['cart'][$key]);
        else $_SESSION['cart'][$key] = min(999, $packets);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart']);
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /** Lignes détaillées et vérifiées (campagnes ouvertes, contenances autorisées). */
    public static function lines(): array
    {
        $raw = self::raw();
        if (!$raw) return ['lines' => [], 'total' => 0.0, 'groups' => [], 'invalid' => []];
        $ids = array_unique(array_map(fn($k) => (int)explode(':', $k)[0], array_keys($raw)));
        $camps = [];
        foreach (Campaigns::hydrate(db()->all(Campaigns::SELECT . ' WHERE c.id IN (' . implode(',', $ids) . ')')) as $c) $camps[(int)$c['id']] = $c;
        $lines = []; $invalid = []; $total = 0.0; $groups = [];
        foreach ($raw as $key => $packets) {
            [$cid, $portion] = explode(':', $key);
            $c = $camps[(int)$cid] ?? null;
            $portion = (float)$portion;
            if (!$c || !Campaigns::isOpen($c) || !in_array($portion, Units::portions($c['portion_options']), false)) {
                $invalid[] = $key; unset($_SESSION['cart'][$key]); continue;
            }
            $packetPrice = Pricing::packetPrice($c['state']['price'], $portion);
            $line = [
                'key' => $key, 'campaign' => $c, 'portion' => $portion, 'packets' => (int)$packets,
                'quantity' => $portion * (int)$packets, 'packet_price' => $packetPrice,
                'total' => round($packetPrice * (int)$packets, 2),
            ];
            $lines[] = $line;
            $total += $line['total'];
            $gk = $c['pickup_window_id'] ?: 0;
            $groups[$gk]['label'] = $c['window_label'] ?: 'Retrait : date communiquée à la validation';
            $groups[$gk]['lines'][] = $line;
        }
        return ['lines' => $lines, 'total' => round($total, 2), 'groups' => $groups, 'invalid' => $invalid];
    }
}
