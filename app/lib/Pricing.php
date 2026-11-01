<?php
declare(strict_types=1);

/**
 * Moteur de prix à paliers dégressifs (§5 et §6 du cahier des charges).
 *
 * Toutes les quantités sont exprimées dans l'unité de base du produit
 * (g, graines, L, kg, pot, pièce) et tous les prix unitaires sont TTC par
 * unité de base. Le prix d'un sachet = prix unitaire × contenu du sachet.
 */
final class Pricing
{
    /**
     * Formule du cahier des charges (§6), appliquée par unité de base :
     *   prix = [ (coût_sac + quote-part_port) ÷ (qté_sac × (1 − freinte)) + coût_sachet ÷ part_réf ]
     *          × (1 + marge_asso) × (1 + TVA)
     * Le coût du sachet kraft est réparti sur la part de référence (ex. 10 g),
     * ce qui revient à le compter une fois par sachet vendu.
     */
    public static function unitPrice(float $packCostHt, float $packQty, float $shippingShareHt, float $waste,
                                     float $packetCost, float $refPortion, float $margin, float $vat): float
    {
        $sellable = max(0.000001, $packQty * (1 - $waste));
        $base = ($packCostHt + $shippingShareHt) / $sellable;
        if ($refPortion > 0) $base += $packetCost / $refPortion;
        return round($base * (1 + $margin) * (1 + $vat), 5);
    }

    /** Prix d'un sachet / d'une unité vendue, arrondi au centime. */
    public static function packetPrice(float $unitPrice, float $portion): float
    {
        return round($unitPrice * $portion, 2);
    }

    /** Génère automatiquement les paliers à partir de la grille fournisseur. */
    public static function generateTiers(array $packs, array $p): array
    {
        usort($packs, fn($a, $b) => $a['qty'] <=> $b['qty']);
        $packs = array_values(array_filter($packs, fn($x) => (float)$x['qty'] > 0 && (float)$x['cost_ht'] > 0));
        if (!$packs) return ['tiers' => [], 'public_unit_price' => 0];

        $ref = max(0.001, (float)$p['ref_portion']);
        $vat = (float)$p['vat_rate'];
        // Prix public de référence = ce que paierait un particulier seul en achetant le plus petit conditionnement.
        $smallest = $packs[0];
        $public = round(((float)$smallest['cost_ht'] / (float)$smallest['qty']) * (1 + $vat), 5);

        $useFromIndex = count($packs) >= 3 ? 1 : 0; // le plus petit conditionnement sert de prix public
        if (!empty($p['include_smallest'])) $useFromIndex = 0;

        $tiers = [];
        $lastPrice = PHP_FLOAT_MAX;
        foreach (array_slice($packs, $useFromIndex) as $pack) {
            $price = self::unitPrice((float)$pack['cost_ht'], (float)$pack['qty'], (float)$p['shipping_share_ht'],
                (float)$p['waste_coeff'], (float)$p['packet_cost'], $ref, (float)$p['margin_rate'], $vat);
            if ($price >= $lastPrice) continue; // un palier doit toujours faire baisser le prix
            $sellable = (float)$pack['qty'] * (1 - (float)$p['waste_coeff']);
            $minQty = floor($sellable / $ref) * $ref;
            if ($minQty <= 0) continue;
            $tiers[] = [
                'tier_level' => count($tiers) + 1,
                'min_qty' => $minQty,
                'unit_price_ttc' => $price,
                'label' => $pack['label'] . ' débloqué',
            ];
            $lastPrice = $price;
            if (count($tiers) >= 5) break;
        }
        if ($public <= 0 || ($tiers && $public < $tiers[0]['unit_price_ttc'])) {
            $public = $tiers ? round($tiers[0]['unit_price_ttc'] * 1.35, 5) : $public;
        }
        return ['tiers' => $tiers, 'public_unit_price' => $public];
    }

    /**
     * Combinaison de conditionnements fournisseur la moins chère couvrant
     * le besoin brut (besoin net ÷ (1 − freinte)). Programmation dynamique.
     */
    public static function optimalPacks(array $packs, float $needNet, float $waste = 0.0): array
    {
        $packs = array_values(array_filter($packs, fn($x) => (float)$x['qty'] > 0));
        $needRaw = $needNet / max(0.0001, 1 - $waste);
        if (!$packs || $needNet <= 0) return ['lines' => [], 'qty' => 0.0, 'cost_ht' => 0.0, 'need_raw' => $needRaw];

        $sizes = array_map(fn($x) => (int)round((float)$x['qty'] * 1000), $packs);
        $g = 0;
        foreach ($sizes as $s) $g = $g === 0 ? $s : self::gcd($g, $s);
        $g = max(1, $g);
        $units = array_map(fn($s) => intdiv($s, $g), $sizes);
        $target = (int)ceil(round($needRaw * 1000) / $g);
        $maxU = max($units);
        $limit = $target + $maxU;
        $counts = array_fill(0, count($packs), 0);

        if ($limit <= 250000) {
            $INF = PHP_FLOAT_MAX;
            $best = array_fill(0, $limit + 1, $INF);
            $from = array_fill(0, $limit + 1, -1);
            $best[0] = 0.0;
            for ($q = 1; $q <= $limit; $q++) {
                foreach ($units as $i => $u) {
                    if ($u <= $q && $best[$q - $u] < $INF) {
                        $c = $best[$q - $u] + (float)$packs[$i]['cost_ht'];
                        if ($c < $best[$q] - 1e-9) { $best[$q] = $c; $from[$q] = $i; }
                    }
                }
            }
            $bestQ = -1; $bestC = $INF;
            for ($q = $target; $q <= $limit; $q++) {
                if ($best[$q] < $bestC - 1e-9) { $bestC = $best[$q]; $bestQ = $q; }
            }
            for ($q = $bestQ; $q > 0; $q -= $units[$from[$q]]) $counts[$from[$q]]++;
        } else {
            // Repli glouton pour les très gros volumes : le meilleur prix unitaire d'abord.
            $order = array_keys($packs);
            usort($order, fn($a, $b) => ($packs[$a]['cost_ht'] / $packs[$a]['qty']) <=> ($packs[$b]['cost_ht'] / $packs[$b]['qty']));
            $rest = $target;
            foreach ($order as $i) { $n = intdiv($rest, $units[$i]); $counts[$i] += $n; $rest -= $n * $units[$i]; }
            if ($rest > 0) {
                $small = array_search(min($units), $units, true);
                $counts[$small] += (int)ceil($rest / $units[$small]);
            }
        }

        $lines = []; $qty = 0.0; $cost = 0.0;
        foreach ($packs as $i => $p) {
            if ($counts[$i] === 0) continue;
            $lines[] = ['label' => $p['label'], 'pack_qty' => (float)$p['qty'], 'count' => $counts[$i],
                'unit_cost_ht' => (float)$p['cost_ht'], 'total_ht' => round($counts[$i] * (float)$p['cost_ht'], 2)];
            $qty += $counts[$i] * (float)$p['qty'];
            $cost += $counts[$i] * (float)$p['cost_ht'];
        }
        usort($lines, fn($a, $b) => $b['pack_qty'] <=> $a['pack_qty']);
        return ['lines' => $lines, 'qty' => $qty, 'cost_ht' => round($cost, 2), 'need_raw' => $needRaw];
    }

    /** Simulateur de rentabilité (US-A03) : marge nette à chaque seuil de palier. */
    public static function simulate(array $c, array $tiers, array $packs, ?float $reserved = null): array
    {
        $rows = [];
        $points = array_map(fn($t) => ['label' => 'Palier ' . $t['tier_level'], 'qty' => (float)$t['min_qty'], 'price' => (float)$t['unit_price_ttc']], $tiers);
        if ($reserved !== null && $reserved > 0) {
            $st = self::state($c, $tiers, $reserved);
            $points[] = ['label' => 'Réservé actuellement', 'qty' => $reserved, 'price' => $st['price_if_closed_now']];
        }
        $vat = (float)$c['vat_rate'];
        $ref = max(0.001, (float)$c['ref_portion']);
        foreach ($points as $pt) {
            $revTtc = $pt['qty'] * $pt['price'];
            $revHt = $revTtc / (1 + $vat);
            $buy = self::optimalPacks($packs, $pt['qty'], (float)$c['waste_coeff']);
            $packets = ceil($pt['qty'] / $ref) * (float)$c['packet_cost'];
            $net = $revHt - $buy['cost_ht'] - (float)$c['shipping_share_ht'] - $packets;
            $rows[] = $pt + [
                'revenue_ttc' => round($revTtc, 2), 'revenue_ht' => round($revHt, 2),
                'purchase_ht' => $buy['cost_ht'], 'purchase_lines' => $buy['lines'],
                'shipping_ht' => (float)$c['shipping_share_ht'], 'packets_cost' => round($packets, 2),
                'net_margin' => round($net, 2), 'net_margin_pct' => $revHt > 0 ? round($net / $revHt * 100, 1) : 0,
            ];
        }
        return $rows;
    }

    /**
     * État complet d'une campagne pour l'affichage (ardoise, jauge, plante).
     * $qty permet de prévisualiser l'effet d'une commande ("arroser la plante").
     */
    public static function state(array $c, array $tiers, ?float $qty = null): array
    {
        usort($tiers, fn($a, $b) => $a['tier_level'] <=> $b['tier_level']);
        $reserved = $qty ?? ((float)$c['current_reserved_qty'] + (float)($c['shop_buyback_qty'] ?? 0));
        $max = count($tiers);
        $tier = 0;
        foreach ($tiers as $i => $t) if ($reserved + 1e-9 >= (float)$t['min_qty']) $tier = $i + 1;

        $t1 = $tiers[0] ?? null;
        $cur = $tier > 0 ? $tiers[$tier - 1] : null;
        $next = $tier < $max ? $tiers[$tier] : null;
        $public = (float)$c['public_unit_price'];
        $priceNow = $cur ? (float)$cur['unit_price_ttc'] : ($t1 ? (float)$t1['unit_price_ttc'] : $public);

        if ($next) {
            $base = $cur ? (float)$cur['min_qty'] : 0.0;
            $span = max(0.0001, (float)$next['min_qty'] - $base);
            $toNext = max(0, min(1, ($reserved - $base) / $span));
            $missing = max(0, (float)$next['min_qty'] - $reserved);
        } else {
            $toNext = 1.0; $missing = 0.0;
        }

        $t1Min = $t1 ? max(0.0001, (float)$t1['min_qty']) : 1;
        $maxMin = $max ? max(0.0001, (float)$tiers[$max - 1]['min_qty']) : 1;
        if ($tier === 0) {
            $plant = ($reserved / $t1Min) < 0.25 ? 1 : 2;
        } elseif ($tier === $max && $max >= 2) {
            $plant = 5;
        } elseif ($tier >= 2) {
            $plant = 4;
        } else {
            $plant = 3;
        }
        $ref = (float)$c['ref_portion'];
        $partsMissing = $ref > 0 ? (int)ceil($missing / $ref - 1e-9) : 0;

        return [
            'reserved' => round($reserved, 3),
            'tier' => $tier,
            'max_tier' => $max,
            'validated' => $tier >= 1,
            'price' => $priceNow,
            'price_if_closed_now' => $priceNow,
            'public_price' => $public,
            'discount_pct' => $public > 0 ? (int)round((1 - $priceNow / $public) * 100) : 0,
            'next_tier' => $next ? (int)$next['tier_level'] : null,
            'next_price' => $next ? (float)$next['unit_price_ttc'] : null,
            'next_min' => $next ? (float)$next['min_qty'] : null,
            'missing' => round($missing, 3),
            'parts_missing' => $partsMissing,
            'to_next_pct' => (int)floor($toNext * 100),
            'to_t1_pct' => (int)min(100, floor($reserved / $t1Min * 100)),
            'growth' => round(min(1, $reserved / $maxMin), 4),
            'plant_state' => $plant,
            'parts_reserved' => $ref > 0 ? (int)floor($reserved / $ref + 1e-9) : 0,
        ];
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) [$a, $b] = [$b, $a % $b];
        return abs($a);
    }
}
