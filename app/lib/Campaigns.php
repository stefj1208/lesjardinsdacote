<?php
declare(strict_types=1);

/** Cycle de vie des campagnes (§15) : réservation, paliers, clôture, relances. */
final class Campaigns
{
    public const SELECT = 'SELECT c.*, p.name AS product_name, p.variety, p.latin_name, p.family, p.category, p.unit, p.pmg,
            p.description, p.sowing_advice, p.sowing_months, p.sowing_depth, p.exposure, p.water, p.difficulty,
            p.is_organic, p.image_url, p.pot_size, p.sku_supplier, p.supplier_id, p.source_url,
            s.name AS supplier_name, w.label AS window_label, w.location AS window_location, w.address AS window_address,
            w.starts_at AS window_starts, w.ends_at AS window_ends
        FROM campaigns c
        JOIN products p ON p.id = c.product_id
        LEFT JOIN suppliers s ON s.id = p.supplier_id
        LEFT JOIN pickup_windows w ON w.id = c.pickup_window_id';

    public static function find(int $id): ?array
    {
        return db()->one(self::SELECT . ' WHERE c.id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return db()->one(self::SELECT . ' WHERE c.slug = ?', [$slug]);
    }

    public static function tiers(int $campaignId): array
    {
        return db()->all('SELECT * FROM campaign_tiers WHERE campaign_id = ? ORDER BY tier_level', [$campaignId]);
    }

    /** Ajoute 'tiers' et 'state' à chaque campagne (une requête pour tous les paliers). */
    public static function hydrate(array $campaigns): array
    {
        if (!$campaigns) return [];
        $ids = array_map(fn($c) => (int)$c['id'], $campaigns);
        $all = db()->all('SELECT * FROM campaign_tiers WHERE campaign_id IN (' . implode(',', $ids) . ') ORDER BY tier_level');
        $by = [];
        foreach ($all as $t) $by[(int)$t['campaign_id']][] = $t;
        $participants = [];
        foreach (db()->all("SELECT oi.campaign_id, COUNT(DISTINCT o.user_id) n FROM order_items oi JOIN orders o ON o.id = oi.order_id
                WHERE oi.campaign_id IN (" . implode(',', $ids) . ") AND oi.status <> 'cancelled' GROUP BY oi.campaign_id") as $r) {
            $participants[(int)$r['campaign_id']] = (int)$r['n'];
        }
        foreach ($campaigns as &$c) {
            $c['tiers'] = $by[(int)$c['id']] ?? [];
            $c['state'] = Pricing::state($c, $c['tiers']);
            $c['participants'] = $participants[(int)$c['id']] ?? 0;
        }
        return $campaigns;
    }

    public static function one(int $id): ?array
    {
        $c = self::find($id);
        return $c ? self::hydrate([$c])[0] : null;
    }

    public static function isOpen(array $c): bool
    {
        return $c['status'] === 'active' && strtotime($c['start_at']) <= time() && strtotime($c['end_at']) > time();
    }

    /** Recalcule quantité réservée, palier et prix courant (à appeler dans une transaction). */
    public static function refresh(int $campaignId): array
    {
        $c = db()->one('SELECT * FROM campaigns WHERE id = ? FOR UPDATE', [$campaignId]);
        $qty = (float)db()->val("SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE campaign_id = ? AND status <> 'cancelled' AND status <> 'failed'", [$campaignId]);
        $c['current_reserved_qty'] = $qty;
        $st = Pricing::state($c, self::tiers($campaignId));
        db()->exec('UPDATE campaigns SET current_reserved_qty = ?, current_tier = ?, current_unit_price = ? WHERE id = ?',
            [$qty, $st['tier'], $st['price'], $campaignId]);
        return ['old_tier' => (int)$c['current_tier'], 'new_tier' => $st['tier'], 'state' => $st];
    }

    /** Notification de franchissement de palier à tous les participants (§9.4). */
    public static function notifyTierReached(int $campaignId): void
    {
        $c = self::one($campaignId);
        if (!$c || $c['state']['tier'] <= (int)$c['notified_tier']) return;
        $tier = $c['state']['tier'];
        db()->exec('UPDATE campaigns SET notified_tier = ? WHERE id = ?', [$tier, $campaignId]);
        $users = db()->all("SELECT DISTINCT u.email, u.first_name FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id
                WHERE oi.campaign_id = ? AND oi.status = 'reserved' AND u.notify_tiers = 1", [$campaignId]);
        $link = abs_url('/campagne/' . $c['slug']);
        $priceLine = Units::refPrice($c['state']['price'], $c['unit']);
        foreach ($users as $u) {
            $isFirst = $tier === 1;
            $body = '<p>Bonjour ' . e($u['first_name']) . ',</p>'
                . '<p>' . ($isFirst
                    ? 'Bonne nouvelle : la campagne <strong>' . e($c['title']) . '</strong> a atteint son <strong>palier 1</strong>. La commande est garantie !'
                    : 'La communauté a débloqué le <strong>palier ' . $tier . '</strong> de la campagne <strong>' . e($c['title']) . '</strong>.') . '</p>'
                . '<p style="font-size:18px">Nouveau prix : <strong style="color:#1F4E3D">' . e($priceLine) . '</strong>'
                . ($c['state']['discount_pct'] > 0 ? ' (−' . $c['state']['discount_pct'] . ' % par rapport au prix public)' : '') . '</p>'
                . '<p>Grâce à la règle d\'alignement sur le prix le plus bas, <strong>votre commande sera facturée à ce prix</strong>, même si vous vous êtes engagé·e plus tôt.</p>'
                . ($c['state']['next_tier'] ? '<p>Encore ' . e(Units::qty($c['state']['missing'], $c['unit'])) . ' à réserver pour débloquer le palier ' . $c['state']['next_tier'] . '. Partagez la campagne à vos voisins !</p>' : '<p>C\'est le prix plancher : récolte abondante !</p>');
            Mailer::queue($u['email'], ($isFirst ? '🌱 Commande garantie : ' : '🌸 Palier ' . $tier . ' débloqué : ') . $c['title'],
                Mailer::layout($isFirst ? 'Commande garantie !' : 'Nouveau palier débloqué', $body, 'Voir la campagne', $link));
        }
    }

    /** Clôture automatique des campagnes échues (cron). */
    public static function closeDue(): array
    {
        $done = [];
        $due = db()->col("SELECT id FROM campaigns WHERE status = 'active' AND end_at <= NOW() ORDER BY end_at");
        foreach ($due as $id) $done[] = self::close((int)$id);
        return $done;
    }

    /** Clôture une campagne : validée (prix final = dernier palier atteint) ou échouée. */
    public static function close(int $id, bool $forced = false): array
    {
        return db()->tx(function (DB $db) use ($id, $forced) {
            $r = self::refresh($id);
            $c = self::find($id);
            if ($c['status'] !== 'active') return ['id' => $id, 'result' => 'skipped'];
            $tiers = self::tiers($id);
            $st = $r['state'];
            $ok = $st['tier'] >= 1;
            if ($c['trigger_mode'] === 'franco' && $c['supplier_batch_id']) {
                $ok = self::batchReached((int)$c['supplier_batch_id']);
            }
            if (!$ok && !$forced) {
                $db->exec("UPDATE campaigns SET status = 'failed', closed_at = NOW() WHERE id = ?", [$id]);
                $db->exec("UPDATE order_items SET status = 'failed', unit_price_final = 0, line_total_final = 0 WHERE campaign_id = ? AND status = 'reserved'", [$id]);
                self::mailFailed($c);
                audit('campaign.failed', $c['title']);
                return ['id' => $id, 'result' => 'failed'];
            }
            $finalPrice = $st['tier'] >= 1 ? (float)$tiers[$st['tier'] - 1]['unit_price_ttc'] : (float)($tiers[0]['unit_price_ttc'] ?? $c['current_unit_price']);
            if ($forced && $st['tier'] < 1 && isset($tiers[0])) {
                $buyback = max(0, (float)$tiers[0]['min_qty'] - $st['reserved']);
                $db->exec('UPDATE campaigns SET shop_buyback_qty = ? WHERE id = ?', [$buyback, $id]);
            }
            $db->exec("UPDATE campaigns SET status = 'validated', closed_at = NOW(), current_unit_price = ? WHERE id = ?", [$finalPrice, $id]);
            // Règle d'or de rétroactivité : 100 % des participants paient le prix le plus bas atteint.
            $db->exec("UPDATE order_items SET status = 'validated', unit_price_final = ?, line_total_final = ROUND(ROUND(? * portion_qty, 2) * packets, 2)
                       WHERE campaign_id = ? AND status = 'reserved'", [$finalPrice, $finalPrice, $id]);
            audit($forced ? 'campaign.forced' : 'campaign.validated', $c['title'] . ' @ ' . $finalPrice);
            return ['id' => $id, 'result' => 'validated', 'price' => $finalPrice];
        });
    }

    public static function batchTotals(int $batchId): array
    {
        $b = db()->one('SELECT * FROM supplier_batches WHERE id = ?', [$batchId]);
        $rows = db()->all("SELECT c.id, c.vat_rate, c.current_unit_price, c.current_reserved_qty FROM campaigns c WHERE c.supplier_batch_id = ? AND c.status NOT IN ('draft','failed')", [$batchId]);
        $ht = 0.0;
        foreach ($rows as $r) $ht += (float)$r['current_reserved_qty'] * (float)$r['current_unit_price'] / (1 + (float)$r['vat_rate']);
        $franco = (float)($b['franco_ht'] ?? 0);
        return ['batch' => $b, 'total_ht' => round($ht, 2), 'franco_ht' => $franco, 'pct' => $franco > 0 ? min(100, (int)floor($ht / $franco * 100)) : 100, 'campaigns' => count($rows)];
    }

    public static function batchReached(int $batchId): bool
    {
        $t = self::batchTotals($batchId);
        return $t['total_ht'] + 0.001 >= $t['franco_ht'];
    }

    private static function mailFailed(array $c): void
    {
        $users = db()->all("SELECT DISTINCT u.email, u.first_name FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id WHERE oi.campaign_id = ? AND oi.status IN ('reserved','failed')", [$c['id']]);
        foreach ($users as $u) {
            $body = '<p>Bonjour ' . e($u['first_name']) . ',</p><p>La campagne <strong>' . e($c['title']) . '</strong> n\'a malheureusement pas atteint son palier minimum avant la date de fin.</p>'
                . '<p><strong>Vous êtes automatiquement libéré·e de votre engagement : aucun montant ne sera prélevé</strong> pour ce produit. Si votre commande contenait d\'autres produits, ils restent valables.</p>'
                . '<p>Merci d\'avoir participé ! De nouvelles campagnes ouvrent régulièrement.</p>';
            Mailer::queue($u['email'], 'Campagne non atteinte : ' . $c['title'] . ' — 0 € prélevé', Mailer::layout('Campagne non atteinte', $body, 'Voir les campagnes en cours', abs_url('/campagnes')));
        }
    }

    public static function extend(int $id, int $hours = 48): void
    {
        db()->exec("UPDATE campaigns SET end_at = DATE_ADD(GREATEST(end_at, NOW()), INTERVAL ? HOUR), status = IF(status IN ('failed'), 'active', status) WHERE id = ? AND status IN ('active')", [$hours, $id]);
        audit('campaign.extended', '#' . $id . ' +' . $hours . 'h');
    }

    /**
     * Bon de commande fournisseur consolidé (US-A06) : conditionnements optimaux
     * pour couvrir toutes les réservations validées (+ reliquat boutique).
     */
    public static function purchaseOrder(array $campaignIds): array
    {
        $lines = [];
        $total = 0.0;
        foreach ($campaignIds as $id) {
            $c = self::find((int)$id);
            if (!$c) continue;
            $need = (float)db()->val("SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE campaign_id = ? AND status IN ('reserved','validated','packed','collected')", [$id]) + (float)$c['shop_buyback_qty'];
            $packs = db()->all('SELECT * FROM product_packs WHERE product_id = ? ORDER BY qty', [$c['product_id']]);
            $opt = Pricing::optimalPacks($packs, $need, (float)$c['waste_coeff']);
            $lines[] = ['campaign' => $c, 'need' => $need, 'opt' => $opt];
            $total += $opt['cost_ht'];
        }
        return ['lines' => $lines, 'total_ht' => round($total, 2)];
    }

    /** Feuille d'ensachage : nombre de sachets à préparer par contenance (US-B02). */
    public static function packingSheet(int $id): array
    {
        return db()->all("SELECT portion_qty, SUM(packets) packets, SUM(CASE WHEN status IN ('packed','collected') THEN packets ELSE 0 END) packed
            FROM order_items WHERE campaign_id = ? AND status IN ('validated','packed','collected') GROUP BY portion_qty ORDER BY portion_qty", [$id]);
    }

    public static function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = slugify($title);
        $slug = $base; $i = 2;
        while (db()->val('SELECT id FROM campaigns WHERE slug = ?' . ($exceptId ? ' AND id <> ' . (int)$exceptId : ''), [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
