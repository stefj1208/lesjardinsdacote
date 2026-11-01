<?php
declare(strict_types=1);

const NOINDEX_CART = '<meta name="robots" content="noindex">';

function cart_show(): void
{
    $uid = Auth::id();
    view('front/cart', ['items' => $uid ? Cart::items($uid) : [], 'title' => 'Mon panier', 'head' => NOINDEX_CART]);
}

function cart_add(): void
{
    $saleId = input_int('sale_id');
    $packId = input_int('pack_id');
    $qty = max(1, input_int('qty', 1));
    $s = Sales::find($saleId);
    if (!$s) abort(404);
    if (!Auth::check()) {
        $_SESSION['pending_cart'] = ['sale' => $saleId, 'pack' => $packId, 'qty' => $qty];
        $_SESSION['intended'] = '/panier';
        flash('info', 'Créez votre compte ou connectez-vous pour finaliser votre achat.');
        redirect('/inscription');
    }
    try {
        Cart::add((int)Auth::id(), $saleId, $packId, $qty);
        Groups::join((int)$s['group_id'], (int)Auth::id(), 'member');
    } catch (DomainException $e) {
        flash('error', $e->getMessage());
        redirect('/groupes/' . $s['group_slug']);
    }
    redirect('/panier');
}

function cart_update(): void
{
    $u = Auth::requireLogin();
    $saleId = input_int('sale_id');
    if (input('remove')) {
        Cart::remove((int)$u['id'], $saleId);
    } else {
        $row = db()->one('SELECT * FROM cart_items WHERE user_id = ? AND sale_id = ?', [$u['id'], $saleId]);
        if ($row) {
            try { Cart::add((int)$u['id'], $saleId, (int)$row['pack_id'], input_int('qty', 1), $row['source']); } catch (DomainException $e) { flash('error', $e->getMessage()); }
        }
    }
    redirect('/panier');
}

function checkout_items(int $uid): array
{
    return array_values(array_filter(Cart::items($uid), fn($i) => $i['valid']));
}

function checkout_show(): void
{
    $user = Auth::requireLogin();
    $items = checkout_items((int)$user['id']);
    if (!$items) { flash('info', 'Votre panier est vide.'); redirect('/panier'); }
    $clientSecret = null; $stripeError = null;
    if (stripe_enabled()) {
        try {
            $customer = Stripe::ensureCustomer($user);
            $si = null;
            if (!empty($_SESSION['setup_intent'])) {
                try { $si = Stripe::retrieveSetupIntent($_SESSION['setup_intent']); } catch (Throwable) { $si = null; }
                if ($si && (($si['customer'] ?? '') !== $customer || !in_array($si['status'], ['requires_payment_method', 'requires_confirmation', 'requires_action'], true))) $si = null;
            }
            if (!$si) {
                $si = Stripe::createSetupIntent($customer, 'Achat groupé — ' . setting('association_name', "Les Jardins d'à Côté"));
                $_SESSION['setup_intent'] = $si['id'];
            }
            $clientSecret = $si['client_secret'];
        } catch (Throwable $e) {
            error_log('Stripe checkout: ' . $e->getMessage());
            $stripeError = 'Le paiement en ligne est momentanément indisponible : vous pouvez confirmer avec un règlement au retrait.';
        }
    }
    $returning = input('setup_intent') && input('redirect_status') === 'succeeded' ? (string)input('setup_intent') : null;
    view('front/checkout', [
        'items' => $items, 'total' => array_sum(array_column($items, 'total')), 'user' => $user, 'clientSecret' => $clientSecret,
        'stripeError' => $stripeError, 'returning' => $returning, 'title' => 'Confirmer mon achat', 'head' => NOINDEX_CART,
        'scripts' => $clientSecret ? '<script src="https://js.stripe.com/v3/"></script><script src="' . asset('js/checkout.js') . '" defer></script>' : '',
    ]);
}

function checkout_submit(): void
{
    $user = Auth::requireLogin();
    $items = checkout_items((int)$user['id']);
    if (!$items) { flash('info', 'Votre panier est vide.'); redirect('/panier'); }
    if (!input('cgv')) { flash('error', 'Merci d\'accepter les conditions générales.'); redirect('/commande'); }
    $opts = ['payment_mode' => 'on_pickup', 'notes' => mb_substr((string)input('notes', ''), 0, 500) ?: null];
    if (!$user['phone'] && input('phone')) db()->exec('UPDATE users SET phone = ? WHERE id = ?', [mb_substr((string)input('phone'), 0, 30), $user['id']]);
    if (stripe_enabled() && input('pay_mode') !== 'on_pickup_fallback') {
        try {
            $si = Stripe::retrieveSetupIntent((string)input('setup_intent_id', ''));
            $customer = Stripe::ensureCustomer(Auth::user());
            if ($si['status'] !== 'succeeded' || ($si['customer'] ?? '') !== $customer) throw new RuntimeException('carte non validée');
            $opts['payment_mode'] = 'stripe';
            $opts['setup_intent_id'] = $si['id'];
            $opts['payment_method_id'] = is_array($si['payment_method']) ? $si['payment_method']['id'] : $si['payment_method'];
        } catch (Throwable) {
            flash('error', 'La validation de votre carte n\'a pas abouti. Merci de réessayer.');
            redirect('/commande');
        }
    }
    $refs = [];
    foreach ($items as $it) {
        try {
            $id = Orders::create($user, $it, $opts);
            $refs[] = (string)db()->val('SELECT reference FROM orders WHERE id = ?', [$id]);
        } catch (DomainException $e) {
            flash('error', $it['pack_name'] . ' : ' . $e->getMessage());
        }
    }
    unset($_SESSION['setup_intent']);
    if (!$refs) redirect('/panier');
    flash('success', 'Merci ! Votre achat est enregistré. Rien n\'est débité tant que votre lot n\'est pas complet.');
    redirect('/compte/commande/' . $refs[0] . '?merci=1');
}

function stripe_webhook(): void
{
    $payload = (string)file_get_contents('php://input');
    $secret = (string)config('stripe.webhook_secret', '');
    $event = $secret !== '' ? Stripe::verifyWebhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret) : null;
    if (!$event) json_out(['error' => 'signature'], 400);
    try { db()->insert('stripe_events', ['id' => $event['id'], 'type' => $event['type']]); } catch (PDOException) { json_out(['ok' => true, 'duplicate' => true]); }
    $obj = $event['data']['object'] ?? [];
    $oid = (int)($obj['metadata']['order_id'] ?? 0) ?: (int)db()->val('SELECT id FROM orders WHERE stripe_payment_intent_id = ?', [$obj['id'] ?? '']);
    if ($oid && $event['type'] === 'payment_intent.succeeded') {
        db()->exec('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?', [$obj['id'], $oid]);
        Orders::markPaid($oid);
    } elseif ($oid && $event['type'] === 'payment_intent.payment_failed') {
        db()->exec("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status <> 'paid'", [$oid]);
    }
    json_out(['ok' => true]);
}
