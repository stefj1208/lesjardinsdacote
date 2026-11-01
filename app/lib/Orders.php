<?php
declare(strict_types=1);

/** Engagements, débit à la clôture, préparation et retrait. */
final class Orders
{
    public static function find(int $id): ?array
    {
        return db()->one('SELECT o.*, u.email, u.first_name, u.last_name, u.phone, u.stripe_customer_id,
                w.label AS window_label, w.location AS window_location, w.address AS window_address, w.starts_at AS window_starts, w.ends_at AS window_ends
            FROM orders o JOIN users u ON u.id = o.user_id LEFT JOIN pickup_windows w ON w.id = o.pickup_window_id WHERE o.id = ?', [$id]);
    }

    public static function findByRef(string $ref): ?array
    {
        $id = db()->val('SELECT id FROM orders WHERE reference = ?', [$ref]);
        return $id ? self::find((int)$id) : null;
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) return null;
        $id = db()->val('SELECT id FROM orders WHERE pickup_token = ?', [$token]);
        return $id ? self::find((int)$id) : null;
    }

    public static function items(int $orderId): array
    {
        return db()->all('SELECT oi.*, c.title, c.slug, c.status AS campaign_status, c.end_at, c.current_unit_price, c.supplier_lot,
                p.unit, p.name AS product_name, p.variety, p.latin_name, p.category, p.image_url
            FROM order_items oi JOIN campaigns c ON c.id = oi.campaign_id JOIN products p ON p.id = c.product_id
            WHERE oi.order_id = ? ORDER BY c.title, oi.portion_qty', [$orderId]);
    }

    /** Montant estimé d'une commande au prix courant des paliers. */
    public static function liveTotal(array $items): float
    {
        $t = 0.0;
        foreach ($items as $it) {
            if (in_array($it['status'], ['cancelled', 'failed'], true)) continue;
            $price = $it['unit_price_final'] !== null ? (float)$it['unit_price_final'] : (float)$it['current_unit_price'];
            $t += Pricing::packetPrice($price, (float)$it['portion_qty']) * (int)$it['packets'];
        }
        return round($t, 2);
    }

    private static function newPickupCode(): string
    {
        $alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $c = '';
            for ($i = 0; $i < 6; $i++) $c .= $alpha[random_int(0, strlen($alpha) - 1)];
        } while (db()->val('SELECT id FROM orders WHERE pickup_code = ?', [$c]));
        return $c;
    }

    /**
     * Crée une commande par fenêtre de retrait à partir du panier.
     * Réservation atomique : verrou SELECT … FOR UPDATE sur chaque campagne.
     */
    public static function createFromCart(array $user, array $cart, array $opts): array
    {
        $created = [];
        $tierChanged = [];
        db()->tx(function (DB $db) use ($user, $cart, $opts, &$created, &$tierChanged) {
            foreach ($cart['groups'] as $windowId => $group) {
                // Verrous et contrôles
                foreach ($group['lines'] as $line) {
                    $c = $db->one('SELECT * FROM campaigns WHERE id = ? FOR UPDATE', [$line['campaign']['id']]);
                    if (!$c || $c['status'] !== 'active' || strtotime($c['end_at']) <= time()) {
                        throw new DomainException('La campagne « ' . $line['campaign']['title'] . ' » est terminée.');
                    }
                    if ($c['max_qty_cap'] !== null && (float)$c['current_reserved_qty'] + $line['quantity'] > (float)$c['max_qty_cap'] + 1e-9) {
                        $left = max(0, (float)$c['max_qty_cap'] - (float)$c['current_reserved_qty']);
                        throw new DomainException('Il ne reste que ' . Units::qty($left, $line['campaign']['unit']) . ' disponibles pour « ' . $line['campaign']['title'] . ' ».');
                    }
                }
                $seq = $db->nextSequence('order');
                $ref = 'JDC-' . date('y') . '-' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
                $initial = mb_strtoupper(mb_substr($user['last_name'] ?: $user['first_name'] ?: $user['email'], 0, 1));
                $orderId = $db->insert('orders', [
                    'reference' => $ref,
                    'user_id' => $user['id'],
                    'pickup_window_id' => $windowId ?: null,
                    'payment_mode' => $opts['payment_mode'],
                    'payment_status' => 'pending',
                    'stripe_setup_intent_id' => $opts['setup_intent_id'] ?? null,
                    'stripe_payment_method_id' => $opts['payment_method_id'] ?? null,
                    'container_choice' => $opts['container_choice'] ?? 'kraft',
                    'notes' => $opts['notes'] ?? null,
                    'pickup_token' => random_token(16),
                    'pickup_code' => self::newPickupCode(),
                    'bin_code' => preg_match('/^[A-Z]$/u', $initial) ? $initial : '#',
                    'cgv_accepted_at' => now(),
                ]);
                $amount = 0.0;
                foreach ($group['lines'] as $line) {
                    $c = $line['campaign'];
                    $price = (float)$db->val('SELECT current_unit_price FROM campaigns WHERE id = ?', [$c['id']]);
                    if ($price <= 0) $price = $c['state']['price'];
                    $db->insert('order_items', [
                        'order_id' => $orderId, 'campaign_id' => $c['id'], 'portion_qty' => $line['portion'],
                        'packets' => $line['packets'], 'quantity' => $line['quantity'], 'unit_price_commit' => $price,
                    ]);
                    $amount += Pricing::packetPrice($price, $line['portion']) * $line['packets'];
                    $r = Campaigns::refresh((int)$c['id']);
                    if ($r['new_tier'] > $r['old_tier']) $tierChanged[(int)$c['id']] = true;
                }
                $db->exec('UPDATE orders SET amount_committed = ? WHERE id = ?', [round($amount, 2), $orderId]);
                $created[] = $orderId;
            }
        });
        foreach (array_keys($tierChanged) as $cid) Campaigns::notifyTierReached($cid);
        foreach ($created as $id) self::mailCommitted($id);
        audit('order.created', implode(',', $created));
        return $created;
    }

    private static function mailCommitted(int $orderId): void
    {
        $o = self::find($orderId);
        $items = self::items($orderId);
        $rows = '';
        foreach ($items as $it) {
            $rows .= '<tr><td style="padding:6px 0;border-bottom:1px solid #eee">' . e($it['title']) . '<br><small style="color:#777">' . (int)$it['packets'] . ' × ' . e(Units::qty($it['portion_qty'], $it['unit'])) . '</small></td>'
                . '<td style="padding:6px 0;border-bottom:1px solid #eee;text-align:right">' . money(Pricing::packetPrice((float)$it['unit_price_commit'], (float)$it['portion_qty']) * (int)$it['packets']) . '</td></tr>';
        }
        $pay = $o['payment_mode'] === 'stripe'
            ? '<p>Votre carte est enregistrée de façon sécurisée par Stripe. <strong>Aucun montant n\'a été débité.</strong> Le prélèvement aura lieu à la clôture, au prix du dernier palier atteint — jamais plus que le montant ci-dessus.</p>'
            : '<p><strong>Aucun paiement maintenant.</strong> Vous réglerez au retrait, au prix du dernier palier atteint — jamais plus que le montant ci-dessus.</p>';
        $body = '<p>Bonjour ' . e($o['first_name']) . ',</p><p>Merci ! Votre engagement <strong>' . e($o['reference']) . '</strong> est bien enregistré.</p>'
            . '<table width="100%" style="border-collapse:collapse;font-size:14px">' . $rows
            . '<tr><td style="padding:10px 0"><strong>Montant maximum</strong></td><td style="text-align:right"><strong>' . money($o['amount_committed']) . '</strong></td></tr></table>'
            . $pay . '<p>Plus vos voisins rejoignent la campagne, plus le prix baisse pour tout le monde (vous compris). Partagez-la !</p>';
        Mailer::queue($o['email'], 'Engagement ' . $o['reference'] . ' enregistré 🌱', Mailer::layout('Engagement enregistré', $body, 'Suivre ma commande', abs_url('/compte/commande/' . $o['reference'])));
    }

    /** Annulation possible tant que toutes les campagnes concernées sont encore ouvertes. */
    public static function cancel(array $order): void
    {
        $items = self::items((int)$order['id']);
        foreach ($items as $it) {
            if ($it['status'] !== 'reserved' || $it['campaign_status'] !== 'active' || strtotime($it['end_at']) <= time()) {
                throw new DomainException('Cette commande ne peut plus être annulée (une campagne est déjà clôturée).');
            }
        }
        db()->tx(function (DB $db) use ($order, $items) {
            $db->exec("UPDATE order_items SET status = 'cancelled' WHERE order_id = ?", [$order['id']]);
            $db->exec("UPDATE orders SET status = 'cancelled', payment_status = 'not_required' WHERE id = ?", [$order['id']]);
            foreach (array_unique(array_column($items, 'campaign_id')) as $cid) Campaigns::refresh((int)$cid);
        });
        audit('order.cancelled', $order['reference']);
    }

    /** Commandes dont toutes les campagnes sont clôturées et pas encore traitées. */
    public static function processPending(int $limit = 25): array
    {
        $ids = db()->col("SELECT o.id FROM orders o WHERE o.status = 'committed' AND o.payment_status = 'pending'
            AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status = 'reserved')
            ORDER BY o.id LIMIT " . (int)$limit);
        $res = [];
        foreach ($ids as $id) $res[$id] = self::finalize((int)$id);
        return $res;
    }

    public static function finalize(int $orderId): string
    {
        $o = self::find($orderId);
        if (!$o || $o['payment_status'] !== 'pending') return 'skipped';
        $total = (float)db()->val("SELECT COALESCE(SUM(line_total_final),0) FROM order_items WHERE order_id = ? AND status IN ('validated','packed','collected')", [$orderId]);
        if ($total <= 0) {
            db()->exec("UPDATE orders SET status = 'failed', payment_status = 'not_required', amount_final = 0 WHERE id = ?", [$orderId]);
            return 'nothing_to_pay';
        }
        db()->exec("UPDATE orders SET status = 'confirmed', amount_final = ? WHERE id = ?", [$total, $orderId]);
        $o['amount_final'] = $total;

        if ($o['payment_mode'] === 'on_pickup' || !stripe_enabled() || !$o['stripe_payment_method_id']) {
            db()->exec("UPDATE orders SET payment_status = 'to_collect' WHERE id = ?", [$orderId]);
            self::mailConfirmed($o, 'to_collect');
            return 'to_collect';
        }
        return self::charge($o);
    }

    public static function charge(array $o): string
    {
        db()->exec("UPDATE orders SET payment_status = 'processing' WHERE id = ?", [$o['id']]);
        $cents = (int)round((float)$o['amount_final'] * 100);
        try {
            $pi = Stripe::chargeOffSession($o['stripe_customer_id'], $o['stripe_payment_method_id'], $cents,
                'Commande ' . $o['reference'] . ' — ' . setting('association_name', "Les Jardins d'à Côté"),
                ['order_ref' => $o['reference'], 'order_id' => $o['id']], 'charge-' . $o['reference'] . '-' . $cents);
            db()->exec('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?', [$pi['id'], $o['id']]);
            if ($pi['status'] === 'succeeded') {
                self::markPaid((int)$o['id']);
                return 'paid';
            }
            db()->exec("UPDATE orders SET payment_status = 'action_required' WHERE id = ?", [$o['id']]);
            self::mailConfirmed($o, 'action_required');
            return 'action_required';
        } catch (StripeError $e) {
            $piId = $e->payload['payment_intent']['id'] ?? null;
            $code = $e->payload['code'] ?? '';
            db()->exec('UPDATE orders SET payment_status = ?, stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id) WHERE id = ?',
                [$code === 'authentication_required' ? 'action_required' : 'failed', $piId, $o['id']]);
            self::mailConfirmed($o, $code === 'authentication_required' ? 'action_required' : 'failed', $e->getMessage());
            audit('payment.error', $o['reference'] . ' : ' . $e->getMessage());
            return $code === 'authentication_required' ? 'action_required' : 'failed';
        }
    }

    public static function markPaid(int $orderId): void
    {
        $o = self::find($orderId);
        if (!$o || $o['payment_status'] === 'paid') return;
        db()->exec("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$orderId]);
        self::assignInvoice($orderId);
        self::mailConfirmed(self::find($orderId), 'paid');
        audit('payment.paid', $o['reference'] . ' ' . $o['amount_final']);
    }

    public static function assignInvoice(int $orderId): void
    {
        if (db()->val('SELECT invoice_number FROM orders WHERE id = ?', [$orderId])) return;
        $n = db()->nextSequence('invoice');
        db()->exec('UPDATE orders SET invoice_number = ?, invoiced_at = NOW() WHERE id = ? AND invoice_number IS NULL',
            ['F' . date('Y') . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT), $orderId]);
    }

    private static function mailConfirmed(array $o, string $situation, string $err = ''): void
    {
        $link = abs_url('/compte/commande/' . $o['reference']);
        $saved = (float)$o['amount_committed'] - (float)$o['amount_final'];
        $intro = '<p>Bonjour ' . e($o['first_name']) . ',</p><p>Les campagnes de votre commande <strong>' . e($o['reference']) . '</strong> sont clôturées. '
            . 'Montant final : <strong style="font-size:18px">' . money($o['amount_final']) . '</strong>'
            . ($saved > 0.009 ? ' — soit <strong>' . money($saved) . ' de moins</strong> que votre engagement grâce aux paliers débloqués 🎉' : '') . '.</p>';
        switch ($situation) {
            case 'paid':
                $body = $intro . '<p>Le paiement a bien été effectué. Votre facture est disponible dans votre espace.</p><p>Nos bénévoles vont maintenant commander auprès du fournisseur puis préparer vos sachets. Vous recevrez votre <strong>Pass Retrait (QR code)</strong> dès que tout sera prêt.</p>';
                Mailer::queue($o['email'], 'Paiement confirmé — ' . $o['reference'], Mailer::layout('Commande confirmée', $body, 'Voir ma commande', $link));
                break;
            case 'to_collect':
                $body = $intro . '<p>Vous réglerez ce montant <strong>au moment du retrait</strong> (espèces, chèque ou carte selon la permanence).</p><p>Vous recevrez votre <strong>Pass Retrait (QR code)</strong> dès que vos sachets seront prêts.</p>';
                Mailer::queue($o['email'], 'Commande confirmée — ' . $o['reference'], Mailer::layout('Commande confirmée', $body, 'Voir ma commande', $link));
                break;
            case 'action_required':
                $body = $intro . '<p>Votre banque demande une <strong>confirmation de paiement</strong> (3-D Secure). Cliquez ci-dessous pour valider en quelques secondes.</p>';
                Mailer::queue($o['email'], 'Action requise : confirmez votre paiement — ' . $o['reference'], Mailer::layout('Confirmez votre paiement', $body, 'Confirmer le paiement', abs_url('/compte/paiement/' . $o['reference'])));
                break;
            default:
                $body = $intro . '<p>Le prélèvement sur la carte enregistrée a été refusé' . ($err ? ' (' . e($err) . ')' : '') . '. Pour conserver votre commande, merci de régler avec une autre carte sous 48 h.</p>';
                Mailer::queue($o['email'], 'Paiement refusé — ' . $o['reference'], Mailer::layout('Paiement à régulariser', $body, 'Payer ma commande', abs_url('/compte/paiement/' . $o['reference'])));
        }
    }

    /** Un article est ensaché ; si toute la commande l'est, elle passe "prête" et le Pass Retrait part. */
    public static function markItemPacked(int $itemId, int $userId, bool $packed = true): void
    {
        $it = db()->one('SELECT * FROM order_items WHERE id = ?', [$itemId]);
        if (!$it || !in_array($it['status'], ['validated', 'packed'], true)) return;
        db()->exec('UPDATE order_items SET status = ?, packed_at = ?, packed_by = ? WHERE id = ?',
            [$packed ? 'packed' : 'validated', $packed ? now() : null, $packed ? $userId : null, $itemId]);
        if ($packed) self::checkReady((int)$it['order_id']);
    }

    public static function checkReady(int $orderId): bool
    {
        $o = self::find($orderId);
        if (!$o || !in_array($o['status'], ['confirmed'], true)) return false;
        $waiting = (int)db()->val("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND status IN ('reserved','validated')", [$orderId]);
        if ($waiting > 0) return false;
        db()->exec("UPDATE orders SET status = 'ready', ready_at = NOW() WHERE id = ?", [$orderId]);
        self::mailReady(self::find($orderId));
        return true;
    }

    public static function mailReady(array $o, bool $reminder = false): void
    {
        $passUrl = abs_url('/retrait/' . $o['pickup_token']);
        $where = $o['window_label']
            ? '<p><strong>' . e($o['window_location']) . '</strong><br>' . e($o['window_address']) . '<br>' . e(time_range_fr($o['window_starts'], $o['window_ends'])) . '</p>'
            : '<p><strong>' . e(setting('association_address')) . '</strong> — horaires des permanences sur le site.</p>';
        $pay = $o['payment_status'] === 'to_collect' ? '<p>Montant à régler sur place : <strong>' . money($o['amount_final']) . '</strong>.</p>'
            : (in_array($o['payment_status'], ['action_required', 'failed'], true) ? '<p style="color:#b45309"><strong>Paiement en attente</strong> : merci de le régulariser avant le retrait.</p>' : '');
        $body = '<p>Bonjour ' . e($o['first_name']) . ',</p>'
            . ($reminder ? '<p>Petit rappel : <strong>votre commande vous attend</strong> ! Les plantes et graines n\'aiment pas attendre trop longtemps.</p>'
                : '<p>Bonne nouvelle : nos bénévoles ont terminé la pesée et la mise en sachet. <strong>Votre commande ' . e($o['reference']) . ' est prête !</strong></p>')
            . '<p style="text-align:center;margin:20px 0"><img src="' . e(abs_url('/qr/' . $o['pickup_token'] . '.png')) . '" width="180" height="180" alt="QR code de retrait" style="border:8px solid #FAF6F0;border-radius:12px"><br>'
            . '<span style="font-family:Courier,monospace;font-size:22px;letter-spacing:4px;color:#232B2D"><strong>' . e($o['pickup_code']) . '</strong></span></p>'
            . '<p>Présentez ce QR code (ou le code ci-dessus) à la permanence :</p>' . $where . $pay
            . '<p>Pensez à apporter un sac. ' . ($o['container_choice'] === 'own' ? 'Vous avez indiqué apporter vos propres contenants : merci !' : '') . '</p>';
        Mailer::queue($o['email'], ($reminder ? 'Rappel : ' : '') . 'Votre commande est prête à être retirée — ' . $o['reference'],
            Mailer::layout($reminder ? 'Votre commande vous attend' : 'Commande prête au retrait', $body, 'Ouvrir mon Pass Retrait', $passUrl));
    }

    public static function collect(int $orderId, int $volunteerId, bool $paidOnSite = false): void
    {
        db()->tx(function (DB $db) use ($orderId, $volunteerId, $paidOnSite) {
            $db->exec("UPDATE orders SET status = 'collected', collected_at = NOW(), collected_by = ? WHERE id = ?", [$volunteerId, $orderId]);
            $db->exec("UPDATE order_items SET status = 'collected' WHERE order_id = ? AND status IN ('validated','packed')", [$orderId]);
            if ($paidOnSite) {
                $db->exec("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_status IN ('to_collect','failed','action_required')", [$orderId]);
            }
        });
        $o = self::find($orderId);
        if ($o['payment_status'] === 'paid') self::assignInvoice($orderId);
        audit('order.collected', $o['reference']);
    }

    /** Relance des retardataires (US-A08). */
    public static function remindLate(int $hours): int
    {
        $rows = db()->all("SELECT id FROM orders WHERE status = 'ready' AND reminded_at IS NULL AND ready_at <= (NOW() - INTERVAL ? HOUR)", [$hours]);
        foreach ($rows as $r) {
            self::mailReady(self::find((int)$r['id']), true);
            db()->exec('UPDATE orders SET reminded_at = NOW() WHERE id = ?', [$r['id']]);
        }
        return count($rows);
    }

    public const STATUS_LABELS = [
        'committed' => 'Engagée', 'cancelled' => 'Annulée', 'confirmed' => 'Confirmée — en préparation',
        'failed' => 'Campagne non atteinte', 'ready' => 'Prête au retrait', 'collected' => 'Retirée',
    ];
    public const PAYMENT_LABELS = [
        'pending' => 'Aucun débit avant la clôture', 'not_required' => 'Rien à payer', 'processing' => 'Paiement en cours',
        'paid' => 'Payée', 'action_required' => 'Confirmation bancaire requise', 'failed' => 'Paiement refusé',
        'to_collect' => 'À régler au retrait', 'refunded' => 'Remboursée',
    ];
}
