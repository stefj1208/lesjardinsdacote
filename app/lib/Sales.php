<?php
declare(strict_types=1);

/**
 * Cycle de vie d'une vente :
 *  waiting (moins de 2 bénévoles) → voting (pré-ouverture : chacun choisit ses variétés)
 *  → open (le pack composé par le vote part dans le panier des votants, débit par lots de 10)
 *  → closed (le dernier lot incomplet est annulé, 0 € débité).
 */
final class Sales
{
    public static function find(int $id): ?array
    {
        return db()->one('SELECT s.*, g.name AS group_name, g.slug AS group_slug, g.commune, g.volunteers_needed,
                g.pickup_place AS group_pickup_place, g.pickup_address AS group_pickup_address
            FROM sales s JOIN `groups` g ON g.id = s.group_id WHERE s.id = ?', [$id]);
    }

    public static function one(int $id): ?array
    {
        $s = self::find($id);
        return $s ? self::hydrate($s) : null;
    }

    /** Le pack en vente (composé à la clôture du vote). */
    public static function packs(int $saleId): array
    {
        $id = db()->val('SELECT winning_pack_id FROM sales WHERE id = ?', [$saleId]);
        return $id ? Packs::withItems(db()->all('SELECT * FROM packs WHERE id = ?', [$id])) : [];
    }

    /** Économie totale des membres du groupe sur cette vente (packs commandés × économie du pack). */
    public static function groupSaved(array $s): float
    {
        $total = 0.0;
        $byPack = [];
        foreach ($s['packs'] ?? [] as $p) $byPack[(int)$p['id']] = (float)$p['save'];
        foreach (db()->all("SELECT pack_id, SUM(quantity) AS q FROM orders WHERE sale_id = ? AND status <> 'cancelled' GROUP BY pack_id", [$s['id']]) as $r) {
            $total += ($byPack[(int)$r['pack_id']] ?? 0) * (int)$r['q'];
        }
        return round($total, 2);
    }

    public static function hydrate(array $s): array
    {
        $s['packs'] = self::packs((int)$s['id']);
        $s['winner'] = $s['packs'][0] ?? null;
        $s['varieties'] = Varieties::forSale((int)$s['id']);
        $s['voters'] = (int)db()->val('SELECT COUNT(DISTINCT user_id) FROM variety_votes WHERE sale_id = ?', [$s['id']]);
        $s['votes_total'] = $s['voters'];
        $s['projection'] = null;
        if (!$s['winner'] && $s['varieties']) {
            $comp = Varieties::compose($s['varieties'], (float)$s['price'], $s['max_varieties'] ? (int)$s['max_varieties'] : null);
            $s['projection'] = Varieties::toPack($comp, (float)$s['price'], 'Le pack ' . Varieties::groupShort((string)($s['group_name'] ?? '')), $s['category']) + ['out' => $comp['out']];
        }
        $s['showcase'] = $s['winner'] ?: $s['projection'];
        $s['batch'] = self::batchState($s);
        $s['pickup_place'] = $s['pickup_place'] ?: ($s['group_pickup_place'] ?? null);
        $s['pickup_address'] = $s['pickup_address'] ?: ($s['group_pickup_address'] ?? null);
        return $s;
    }

    /** Progression des lots : lot courant, remplissage, lots complets, packs vendus. */
    public static function batchState(array $s): array
    {
        $size = max(1, (int)$s['batch_size']);
        $cur = db()->one("SELECT batch_no FROM sale_batches WHERE sale_id = ? AND status = 'open' ORDER BY batch_no DESC LIMIT 1", [$s['id']]);
        $no = $cur ? (int)$cur['batch_no'] : (int)db()->val('SELECT COALESCE(MAX(batch_no),0) FROM sale_batches WHERE sale_id = ?', [$s['id']]) + 1;
        $fill = (int)db()->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE sale_id = ? AND batch_no = ? AND status = 'pending'", [$s['id'], $no]);
        $complete = (int)db()->val("SELECT COUNT(*) FROM sale_batches WHERE sale_id = ? AND status = 'complete'", [$s['id']]);
        $sold = (int)db()->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE sale_id = ? AND status IN ('confirmed','ready','collected')", [$s['id']]);
        $buyers = (int)db()->val("SELECT COUNT(DISTINCT user_id) FROM orders WHERE sale_id = ? AND status <> 'cancelled'", [$s['id']]);
        return ['no' => $no, 'fill' => $fill, 'size' => $size, 'missing' => max(0, $size - $fill), 'pct' => (int)min(100, floor($fill / $size * 100)),
            'complete' => $complete, 'sold' => $sold, 'buyers' => $buyers];
    }

    /** Variétés choisies par un membre. */
    public static function userChoices(int $saleId, ?int $userId): array
    {
        if (!$userId) return [];
        return array_map('intval', db()->col('SELECT variety_id FROM variety_votes WHERE sale_id = ? AND user_id = ?', [$saleId, $userId]));
    }

    /** Remplace les choix d'un membre (au plus max_choices variétés). */
    public static function vote(int $saleId, int $userId, array $varietyIds): int
    {
        $s = self::find($saleId);
        if (!$s || $s['status'] !== 'voting') throw new DomainException('Le vote n\'est pas ouvert pour cette vente.');
        $allowed = array_map('intval', db()->col('SELECT variety_id FROM sale_varieties WHERE sale_id = ?', [$saleId]));
        $ids = array_values(array_intersect(array_unique(array_map('intval', $varietyIds)), $allowed));
        if (!$ids) throw new DomainException('Choisissez au moins une variété.');
        if (count($ids) > (int)$s['max_choices']) throw new DomainException('Vous pouvez choisir ' . (int)$s['max_choices'] . ' variétés au maximum.');
        db()->tx(function (DB $db) use ($saleId, $userId, $ids) {
            $db->exec('DELETE FROM variety_votes WHERE sale_id = ? AND user_id = ?', [$saleId, $userId]);
            foreach ($ids as $vid) $db->exec('INSERT INTO variety_votes (sale_id, user_id, variety_id) VALUES (?, ?, ?)', [$saleId, $userId, $vid]);
        });
        Groups::join((int)$s['group_id'], $userId, 'member');
        return count($ids);
    }

    /** Dès que le groupe a ses bénévoles, ses ventes en attente passent au vote. */
    public static function checkGroup(int $groupId): void
    {
        $g = Groups::find($groupId);
        if (!$g || $g['status'] !== 'active') return;
        $vol = (int)db()->val("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND role = 'volunteer'", [$groupId]);
        if ($vol < (int)$g['volunteers_needed']) return;
        foreach (db()->col("SELECT id FROM sales WHERE group_id = ? AND status = 'waiting'", [$groupId]) as $id) self::startVoting((int)$id);
    }

    public static function startVoting(int $id): bool
    {
        $s = self::find($id);
        if (!$s || $s['status'] !== 'waiting') return false;
        if ((int)db()->val('SELECT COUNT(*) FROM sale_varieties WHERE sale_id = ?', [$id]) < 2) return false;
        db()->exec("UPDATE sales SET status = 'voting', vote_starts_at = NOW(), vote_ends_at = DATE_ADD(NOW(), INTERVAL vote_days DAY) WHERE id = ?", [$id]);
        $s = self::one($id);
        $link = abs_url('/groupes/' . $s['group_slug']);
        foreach (Groups::memberEmails((int)$s['group_id']) as $u) {
            Mailer::queue($u['email'], 'Choisissez vos variétés — ' . $s['group_name'],
                Mailer::layout('À vous de composer le pack !',
                    '<p>Bonjour ' . e($u['first_name']) . ',</p><p><strong>' . e($s['group_name']) . '</strong> a trouvé ses bénévoles : la pré-ouverture commence.</p>'
                    . '<p>' . count($s['varieties']) . ' variétés rares sont proposées. Choisissez jusqu\'à ' . (int)$s['max_choices'] . ' variétés d\'ici le <strong>' . e(date_fr($s['vote_ends_at'], false, true)) . '</strong>. '
                    . 'Les plus demandées composent le pack à ' . eur($s['price']) . ', qui arrivera directement dans votre panier.</p>',
                    'Choisir mes variétés', $link));
        }
        audit('sale.voting', '#' . $id);
        return true;
    }

    public static function open(int $id): bool
    {
        $s = self::one($id);
        if (!$s || $s['status'] !== 'voting' || !$s['varieties']) return false;
        $comp = Varieties::compose($s['varieties'], (float)$s['price'], $s['max_varieties'] ? (int)$s['max_varieties'] : null);
        if (!$comp['in']) return false;
        $packId = 0;
        db()->tx(function (DB $db) use ($s, $comp, &$packId) {
            $packId = Varieties::createPack($comp, $s);
            $db->exec("UPDATE sales SET status = 'open', winning_pack_id = ?, opened_at = NOW(), sale_ends_at = DATE_ADD(NOW(), INTERVAL sale_days DAY) WHERE id = ?", [$packId, $s['id']]);
            $db->exec("INSERT IGNORE INTO sale_batches (sale_id, batch_no, status) VALUES (?, 1, 'open')", [$s['id']]);
            // Le pack composé bascule dans le panier de tous les votants.
            $db->exec("INSERT INTO cart_items (user_id, sale_id, pack_id, quantity, source)
                       SELECT DISTINCT user_id, sale_id, ?, 1, 'vote' FROM variety_votes WHERE sale_id = ?
                       ON DUPLICATE KEY UPDATE pack_id = VALUES(pack_id)", [$packId, $s['id']]);
        });
        $s = self::one($id);
        $w = $s['winner'];
        $voters = array_flip(array_map('intval', db()->col('SELECT DISTINCT user_id FROM variety_votes WHERE sale_id = ?', [$id])));
        $saving = $w['save'] > 0 ? ' Acheté variété par variété en jardinerie, il coûterait ' . eur($w['market']) . '.' : '';
        foreach (Groups::memberEmails((int)$s['group_id']) as $u) {
            $voted = isset($voters[(int)$u['id']]);
            $body = '<p>Bonjour ' . e($u['first_name']) . ',</p>'
                . '<p>Les votes sont clos pour <strong>' . e($s['group_name']) . '</strong> : le pack est composé de <strong>' . count($w['items']) . ' variétés</strong> choisies par les membres, pour ' . eur($w['price']) . '.' . e($saving) . '</p>'
                . ($voted ? '<p><strong>Il vous attend déjà dans votre panier.</strong> Confirmez votre achat : vous ne serez débité·e que lorsque votre lot de ' . (int)$s['batch_size'] . ' achats sera complet.</p>'
                          : '<p>La vente est ouverte jusqu\'au ' . e(date_fr($s['sale_ends_at'])) . '. Les achats sont regroupés par lots de ' . (int)$s['batch_size'] . ' : le débit n\'a lieu que lorsqu\'un lot est complet.</p>');
            Mailer::queue($u['email'], ($voted ? 'Votre pack vous attend — ' : 'Ventes ouvertes — ') . $s['group_name'],
                Mailer::layout('Les ventes sont ouvertes', $body, $voted ? 'Confirmer mon achat' : 'Voir la vente', abs_url($voted ? '/panier' : '/groupes/' . $s['group_slug'])));
        }
        audit('sale.open', '#' . $id . ' pack ' . $packId . ' (' . count($comp['in']) . ' variétés)');
        return true;
    }

    /** Clôture : le lot incomplet est annulé, sans aucun débit. */
    public static function close(int $id): bool
    {
        $s = self::find($id);
        if (!$s || $s['status'] !== 'open') return false;
        $cancelled = [];
        db()->tx(function (DB $db) use ($id, &$cancelled) {
            $db->exec("UPDATE sales SET status = 'closed', closed_at = NOW() WHERE id = ?", [$id]);
            $cancelled = $db->col("SELECT id FROM orders WHERE sale_id = ? AND status = 'pending'", [$id]);
            $db->exec("UPDATE orders SET status = 'cancelled', payment_status = 'not_required' WHERE sale_id = ? AND status = 'pending'", [$id]);
            $db->exec("UPDATE sale_batches SET status = 'cancelled' WHERE sale_id = ? AND status = 'open'", [$id]);
            $db->exec('DELETE FROM cart_items WHERE sale_id = ?', [$id]);
        });
        foreach ($cancelled as $oid) {
            $o = Orders::find((int)$oid);
            Mailer::queue($o['email'], 'Lot non complété — 0 € débité (' . $o['reference'] . ')', Mailer::layout('Lot non complété',
                '<p>Bonjour ' . e($o['first_name']) . ',</p><p>La vente « ' . e($s['title']) . ' » de ' . e($s['group_name']) . ' est terminée. Votre achat faisait partie d\'un lot qui n\'a pas atteint ' . (int)$s['batch_size'] . ' achats.</p>'
                . '<p><strong>Votre commande est annulée et aucun montant n\'a été débité.</strong> Merci d\'avoir participé ! Restez membre du groupe pour être prévenu·e de la prochaine vente.</p>',
                'Voir le groupe', abs_url('/groupes/' . $s['group_slug'])));
        }
        audit('sale.closed', '#' . $id . ' annulées ' . count($cancelled));
        return true;
    }

    /** Appelé par la tâche planifiée. */
    public static function tick(): array
    {
        $r = ['voting' => 0, 'opened' => 0, 'closed' => 0];
        foreach (db()->col("SELECT DISTINCT group_id FROM sales WHERE status = 'waiting'") as $gid) {
            $before = (int)db()->val("SELECT COUNT(*) FROM sales WHERE group_id = ? AND status = 'voting'", [$gid]);
            self::checkGroup((int)$gid);
            $r['voting'] += (int)db()->val("SELECT COUNT(*) FROM sales WHERE group_id = ? AND status = 'voting'", [$gid]) - $before;
        }
        foreach (db()->col("SELECT id FROM sales WHERE status = 'voting' AND vote_ends_at <= NOW()") as $id) if (self::open((int)$id)) $r['opened']++;
        foreach (db()->col("SELECT id FROM sales WHERE status = 'open' AND sale_ends_at <= NOW()") as $id) if (self::close((int)$id)) $r['closed']++;
        return $r;
    }

    /** Liste de préparation : quantités totales par variété pour les lots complets. */
    public static function needs(int $saleId): array
    {
        return db()->all("SELECT pi.name, pi.variety, pi.latin_name, pi.unit, SUM(pi.quantity * o.quantity) total
            FROM orders o JOIN pack_items pi ON pi.pack_id = o.pack_id
            WHERE o.sale_id = ? AND o.status IN ('confirmed','ready','collected')
            GROUP BY pi.name, pi.variety, pi.latin_name, pi.unit ORDER BY pi.name, pi.variety", [$saleId]);
    }
}
