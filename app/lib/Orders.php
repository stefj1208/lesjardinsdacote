<?php
declare(strict_types=1);

/**
 * Achats de packs, regroupés par lots (10 par défaut).
 * pending   : dans un lot en cours, rien n'est débité
 * confirmed : le lot est complet, le paiement est déclenché, préparation par les bénévoles
 * ready     : pack préparé, Pass Retrait envoyé
 * collected : remis au membre
 * cancelled : annulé par le membre, ou lot incomplet à la fin de la vente (0 €)
 */
final class Orders
{
    public const STATUS_LABELS = [
        'pending' => 'En attente du lot complet', 'confirmed' => 'Lot complet — en préparation', 'cancelled' => 'Annulée',
        'ready' => 'Prête au retrait', 'collected' => 'Retirée',
    ];
    public const PAYMENT_LABELS = [
        'pending' => 'Aucun débit avant le lot complet', 'not_required' => 'Rien à payer', 'processing' => 'Paiement en cours',
        'paid' => 'Payée', 'action_required' => 'Confirmation bancaire requise', 'failed' => 'Paiement refusé',
        'to_collect' => 'À régler au retrait', 'refunded' => 'Remboursée',
    ];

    public static function find(int $id): ?array
    {
        return db()->one('SELECT o.*, u.email, u.first_name, u.last_name, u.phone, u.stripe_customer_id,
                s.title AS sale_title, s.status AS sale_status, s.batch_size, s.pickup_at, s.sale_ends_at,
                COALESCE(s.pickup_place, g.pickup_place) AS pickup_place, COALESCE(s.pickup_address, g.pickup_address) AS pickup_address,
                g.name AS group_name, g.slug AS group_slug, p.name AS pack_name, p.slug AS pack_slug, p.photo AS pack_photo
            FROM orders o JOIN users u ON u.id = o.user_id JOIN sales s ON s.id = o.sale_id JOIN `groups` g ON g.id = s.group_id JOIN packs p ON p.id = o.pack_id
            WHERE o.id = ?', [$id]);
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

    public static function items(array $o): array
    {
        return db()->all('SELECT * FROM pack_items WHERE pack_id = ? ORDER BY position, id', [$o['pack_id']]);
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

    /** Crée la commande dans le lot en cours ; si le lot atteint sa taille, il est validé. */
    public static function create(array $user, array $cartItem, array $opts): int
    {
        $completed = null;
        $orderId = db()->tx(function (DB $db) use ($user, $cartItem, $opts, &$completed) {
            $s = $db->one('SELECT * FROM sales WHERE id = ? FOR UPDATE', [$cartItem['sale_id']]);
            if (!$s || $s['status'] !== 'open' || strtotime((string)$s['sale_ends_at']) <= time()) throw new DomainException('Cette vente est terminée.');
            if ((int)$s['winning_pack_id'] !== (int)$cartItem['pack_id']) throw new DomainException('Ce pack n\'est plus en vente.');
            $pack = $db->one('SELECT * FROM packs WHERE id = ?', [$cartItem['pack_id']]);
            $qty = max(1, min((int)$s['max_per_order'], (int)$cartItem['quantity']));
            $batchNo = (int)$db->val("SELECT batch_no FROM sale_batches WHERE sale_id = ? AND status = 'open' ORDER BY batch_no DESC LIMIT 1", [$s['id']]);
            if (!$batchNo) {
                $batchNo = (int)$db->val('SELECT COALESCE(MAX(batch_no),0) FROM sale_batches WHERE sale_id = ?', [$s['id']]) + 1;
                $db->insert('sale_batches', ['sale_id' => $s['id'], 'batch_no' => $batchNo]);
            }
            $seq = $db->nextSequence('order');
            $initial = mb_strtoupper(mb_substr($user['last_name'] ?: $user['first_name'] ?: $user['email'], 0, 1));
            $id = $db->insert('orders', [
                'reference' => 'JDC-' . date('y') . '-' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT),
                'user_id' => $user['id'], 'sale_id' => $s['id'], 'pack_id' => $pack['id'], 'quantity' => $qty,
                'unit_price' => $pack['price'], 'goods_amount' => $goods = round((float)$pack['price'] * $qty, 2),
                'fee_amount' => $fee = (float)($opts['fee'] ?? 0), 'fund_amount' => (float)($opts['fund'] ?? 0), 'membership_amount' => $mem = (float)($opts['membership'] ?? 0),
                'amount' => round($goods + $fee + $mem, 2), 'batch_no' => $batchNo,
                'payment_mode' => $opts['payment_mode'], 'payment_status' => 'pending',
                'stripe_setup_intent_id' => $opts['setup_intent_id'] ?? null, 'stripe_payment_method_id' => $opts['payment_method_id'] ?? null,
                'from_vote' => $cartItem['source'] === 'vote' ? 1 : 0, 'notes' => $opts['notes'] ?? null,
                'pickup_token' => random_token(16), 'pickup_code' => self::newPickupCode(),
                'bin_code' => preg_match('/^[A-Z]$/u', $initial) ? $initial : '#', 'cgv_accepted_at' => now(),
            ]);
            if (!empty($opts['join'])) Asso::join((int)$user['id'], (float)($opts['membership'] ?? 0), $id);
            $db->exec('DELETE FROM cart_items WHERE user_id = ? AND sale_id = ?', [$user['id'], $s['id']]);
            $fill = (int)$db->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE sale_id = ? AND batch_no = ? AND status = 'pending'", [$s['id'], $batchNo]);
            if ($fill >= (int)$s['batch_size']) {
                $db->exec("UPDATE sale_batches SET status = 'complete', completed_at = NOW() WHERE sale_id = ? AND batch_no = ?", [$s['id'], $batchNo]);
                $db->exec("UPDATE orders SET status = 'confirmed' WHERE sale_id = ? AND batch_no = ? AND status = 'pending'", [$s['id'], $batchNo]);
                $db->insert('sale_batches', ['sale_id' => $s['id'], 'batch_no' => $batchNo + 1]);
                $completed = [(int)$s['id'], $batchNo];
            }
            return $id;
        });
        self::mailCreated($orderId);
        if ($completed) self::finalizeBatch(...$completed);
        audit('order.created', '#' . $orderId);
        return $orderId;
    }

    private static function mailCreated(int $id): void
    {
        $o = self::find($id);
        if ($o['status'] === 'confirmed') return; // le mail « lot complet » suit immédiatement
        $s = Sales::one((int)$o['sale_id']);
        $b = $s['batch'];
        $pay = $o['payment_mode'] === 'stripe'
            ? 'Votre carte est enregistrée par Stripe. <strong>Rien n\'est débité tant que le lot n\'est pas complet.</strong>'
            : '<strong>Rien à payer maintenant</strong> : vous réglerez au retrait, une fois le lot complet.';
        Mailer::queue($o['email'], 'Achat enregistré — lot ' . $o['batch_no'] . ' : ' . $b['fill'] . '/' . $b['size'], Mailer::layout('Achat enregistré',
            '<p>Bonjour ' . e($o['first_name']) . ',</p><p>Votre achat <strong>' . e($o['reference']) . '</strong> (' . (int)$o['quantity'] . ' × « ' . e($o['pack_name']) . ' », ' . money($o['amount']) . ') est enregistré dans le <strong>lot n° ' . (int)$o['batch_no'] . '</strong>.</p>'
            . '<p style="font-size:18px">Lot en cours : <strong>' . $b['fill'] . ' / ' . $b['size'] . '</strong> — encore ' . $b['missing'] . ' ' . plural($b['missing'], 'achat', 'achats') . '.</p>'
            . '<p>' . $pay . '</p><p>Parlez-en à vos voisins : le lot se complète plus vite !</p>',
            'Suivre ma commande', abs_url('/compte/commande/' . $o['reference'])));
    }

    /** Lot complet : débit de chaque commande (ou paiement au retrait) et annonce. */
    public static function finalizeBatch(int $saleId, int $batchNo): void
    {
        foreach (db()->col("SELECT id FROM orders WHERE sale_id = ? AND batch_no = ? AND status = 'confirmed' AND payment_status = 'pending'", [$saleId, $batchNo]) as $id) {
            $o = self::find((int)$id);
            if ($o['payment_mode'] === 'stripe' && stripe_enabled() && $o['stripe_payment_method_id']) {
                self::charge($o);
            } else {
                db()->exec("UPDATE orders SET payment_status = 'to_collect' WHERE id = ?", [$id]);
                self::mailBatchComplete(self::find((int)$id));
            }
        }
        audit('batch.complete', 'vente #' . $saleId . ' lot ' . $batchNo);
    }

    private static function mailBatchComplete(array $o, string $extra = ''): void
    {
        $pay = match ($o['payment_status']) {
            'paid' => '<p>Le paiement de <strong>' . money($o['amount']) . '</strong> a bien été effectué. Votre facture est disponible dans votre espace.</p>',
            'to_collect' => '<p>Vous réglerez <strong>' . money($o['amount']) . '</strong> au retrait (espèces, chèque ou carte selon la permanence).</p>',
            default => $extra,
        };
        Mailer::queue($o['email'], 'Lot complet ! Votre pack est commandé — ' . $o['reference'], Mailer::layout('Lot complet !',
            '<p>Bonjour ' . e($o['first_name']) . ',</p><p>Le lot n° ' . (int)$o['batch_no'] . ' de <strong>' . e($o['group_name']) . '</strong> a atteint ' . (int)$o['batch_size'] . ' achats : votre pack « ' . e($o['pack_name']) . ' » est commandé.</p>'
            . $pay . '<p>Les bénévoles vont préparer les packs. Vous recevrez votre <strong>Pass Retrait</strong> (QR code) dès que le vôtre sera prêt.</p>',
            'Voir ma commande', abs_url('/compte/commande/' . $o['reference'])));
    }

    public static function charge(array $o): string
    {
        db()->exec("UPDATE orders SET payment_status = 'processing' WHERE id = ?", [$o['id']]);
        $cents = (int)round((float)$o['amount'] * 100);
        try {
            $pi = Stripe::chargeOffSession($o['stripe_customer_id'], $o['stripe_payment_method_id'], $cents,
                'Commande ' . $o['reference'] . ' — ' . $o['pack_name'], ['order_ref' => $o['reference'], 'order_id' => $o['id']], 'charge-' . $o['reference'] . '-' . $cents);
            db()->exec('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?', [$pi['id'], $o['id']]);
            if ($pi['status'] === 'succeeded') {
                db()->exec("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$o['id']]);
                self::assignInvoice((int)$o['id']);
                self::mailBatchComplete(self::find((int)$o['id']));
                return 'paid';
            }
            db()->exec("UPDATE orders SET payment_status = 'action_required' WHERE id = ?", [$o['id']]);
        } catch (StripeError $e) {
            $code = $e->payload['code'] ?? '';
            db()->exec('UPDATE orders SET payment_status = ?, stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id) WHERE id = ?',
                [$code === 'authentication_required' ? 'action_required' : 'failed', $e->payload['payment_intent']['id'] ?? null, $o['id']]);
            audit('payment.error', $o['reference'] . ' : ' . $e->getMessage());
        }
        $o = self::find((int)$o['id']);
        self::mailBatchComplete($o, '<p style="color:#B45309"><strong>Votre banque demande une confirmation, ou le débit a été refusé.</strong> Merci de régler en ligne sous 48 h pour garder votre pack.</p>'
            . '<p><a href="' . e(abs_url('/compte/paiement/' . $o['reference'])) . '">Régler ma commande</a></p>');
        return $o['payment_status'];
    }

    public static function markPaid(int $orderId): void
    {
        $o = self::find($orderId);
        if (!$o || $o['payment_status'] === 'paid') return;
        db()->exec("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$orderId]);
        self::assignInvoice($orderId);
        audit('payment.paid', $o['reference']);
    }

    public static function assignInvoice(int $orderId): void
    {
        if (db()->val('SELECT invoice_number FROM orders WHERE id = ?', [$orderId])) return;
        $n = db()->nextSequence('invoice');
        db()->exec('UPDATE orders SET invoice_number = ?, invoiced_at = NOW() WHERE id = ? AND invoice_number IS NULL',
            ['F' . date('Y') . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT), $orderId]);
    }

    /** Annulation par le membre : possible tant que son lot n'est pas complet. */
    public static function cancel(array $o): void
    {
        if ($o['status'] !== 'pending') throw new DomainException('Le lot de cette commande est déjà complet : elle ne peut plus être annulée.');
        db()->exec("UPDATE orders SET status = 'cancelled', payment_status = 'not_required' WHERE id = ? AND status = 'pending'", [$o['id']]);
        audit('order.cancelled', $o['reference']);
    }

    public static function markReady(int $orderId, int $volunteerId): void
    {
        $o = self::find($orderId);
        if (!$o || $o['status'] !== 'confirmed') return;
        db()->exec("UPDATE orders SET status = 'ready', ready_at = NOW(), prepared_by = ? WHERE id = ?", [$volunteerId, $orderId]);
        self::mailReady(self::find($orderId));
    }

    public static function unmarkReady(int $orderId): void
    {
        db()->exec("UPDATE orders SET status = 'confirmed', ready_at = NULL WHERE id = ? AND status = 'ready'", [$orderId]);
    }

    public static function mailReady(array $o, bool $reminder = false): void
    {
        $where = '<p><strong>' . e($o['pickup_place'] ?: 'Lieu de retrait') . '</strong><br>' . e($o['pickup_address'] ?: '')
            . ($o['pickup_at'] ? '<br>' . e(date_fr($o['pickup_at'], true, true)) : '') . '</p>';
        $pay = $o['payment_status'] === 'to_collect' ? '<p>Montant à régler sur place : <strong>' . money($o['amount']) . '</strong>.</p>'
            : (in_array($o['payment_status'], ['action_required', 'failed'], true) ? '<p style="color:#B45309"><strong>Paiement en attente</strong> : merci de le régulariser avant le retrait.</p>' : '');
        $body = '<p>Bonjour ' . e($o['first_name']) . ',</p>'
            . ($reminder ? '<p>Petit rappel : <strong>votre pack vous attend</strong> !</p>' : '<p>Les bénévoles ont préparé votre pack « ' . e($o['pack_name']) . ' ». <strong>Il est prêt !</strong></p>')
            . '<p style="text-align:center;margin:20px 0"><img src="' . e(abs_url('/qr/' . $o['pickup_token'] . '.png')) . '" width="180" height="180" alt="QR code de retrait" style="border-radius:12px"><br>'
            . '<span style="font-family:Courier,monospace;font-size:22px;letter-spacing:4px"><strong>' . e($o['pickup_code']) . '</strong></span></p>'
            . '<p>Présentez ce QR code (ou ce code) au retrait :</p>' . $where . $pay;
        Mailer::queue($o['email'], ($reminder ? 'Rappel : ' : '') . 'Votre pack est prêt — ' . $o['reference'],
            Mailer::layout($reminder ? 'Votre pack vous attend' : 'Votre pack est prêt', $body, 'Ouvrir mon Pass Retrait', abs_url('/retrait/' . $o['pickup_token'])));
    }

    public static function collect(int $orderId, int $volunteerId, bool $paidOnSite = false): void
    {
        db()->exec("UPDATE orders SET status = 'collected', collected_at = NOW(), collected_by = ? WHERE id = ?", [$volunteerId, $orderId]);
        if ($paidOnSite) db()->exec("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_status IN ('to_collect','failed','action_required')", [$orderId]);
        $o = self::find($orderId);
        if ($o['payment_status'] === 'paid') self::assignInvoice($orderId);
        audit('order.collected', $o['reference']);
    }

    public static function remindLate(int $hours): int
    {
        $rows = db()->col("SELECT id FROM orders WHERE status = 'ready' AND reminded_at IS NULL AND ready_at <= (NOW() - INTERVAL ? HOUR)", [$hours]);
        foreach ($rows as $id) {
            self::mailReady(self::find((int)$id), true);
            db()->exec('UPDATE orders SET reminded_at = NOW() WHERE id = ?', [$id]);
        }
        return count($rows);
    }
}
