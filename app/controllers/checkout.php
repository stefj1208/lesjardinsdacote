<?php
declare(strict_types=1);

function cart_show(): void
{
    view('front/cart', ['cart' => Cart::lines(), 'title' => 'Mon panier', 'head' => '<meta name="robots" content="noindex">']);
}

function cart_add(): void
{
    $cid = input_int('campaign_id');
    $portion = input_dec('portion', 0);
    $packets = max(1, min(99, input_int('packets', 1)));
    $c = Campaigns::find($cid);
    $err = null;
    if (!$c || !Campaigns::isOpen($c)) $err = 'Cette campagne n\'accepte plus d\'engagements.';
    elseif (!in_array($portion, Units::portions($c['portion_options']), false)) $err = 'Contenance non disponible.';
    elseif ($packets > (int)$c['max_packets_per_line']) $err = 'Maximum ' . (int)$c['max_packets_per_line'] . ' sachets par contenance.';
    if ($err) {
        if (wants_json()) json_out(['error' => $err], 422);
        flash('error', $err); back('/campagnes');
    }
    Cart::add($cid, $portion, $packets);
    $msg = $packets . ' × ' . Units::qty($portion, $c['unit']) . ' de ' . $c['title'] . ' ' . plural($packets, 'ajouté', 'ajoutés') . ' au panier.';
    if (wants_json()) json_out(['ok' => true, 'count' => Cart::count(), 'message' => $msg]);
    flash('success', $msg);
    redirect('/panier');
}

function cart_update(): void
{
    $key = (string)input('key', '');
    if (preg_match('/^\d+:[\d.]+$/', $key)) Cart::set($key, input('remove') ? 0 : max(0, min(99, input_int('packets', 1))));
    redirect('/panier');
}

function checkout_show(): void
{
    $user = Auth::requireLogin();
    $cart = Cart::lines();
    if (!$cart['lines']) { flash('info', 'Votre panier est vide.'); redirect('/campagnes'); }
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
                $si = Stripe::createSetupIntent($customer, 'Engagement achat groupé — ' . setting('association_name', "Les Jardins d'à Côté"));
                $_SESSION['setup_intent'] = $si['id'];
            }
            $clientSecret = $si['client_secret'];
        } catch (Throwable $e) {
            error_log('Stripe checkout: ' . $e->getMessage());
            $stripeError = 'Le module de paiement est momentanément indisponible. Vous pouvez vous engager avec un règlement au retrait.';
        }
    }
    $returning = input('setup_intent') && input('redirect_status') === 'succeeded' ? (string)input('setup_intent') : null;
    view('front/checkout', [
        'cart' => $cart, 'user' => $user, 'clientSecret' => $clientSecret, 'stripeError' => $stripeError, 'returning' => $returning,
        'title' => 'Mon engagement', 'head' => '<meta name="robots" content="noindex">',
        'scripts' => $clientSecret ? '<script src="https://js.stripe.com/v3/"></script><script src="' . asset('js/checkout.js') . '" defer></script>' : '',
    ]);
}

function checkout_submit(): void
{
    $user = Auth::requireLogin();
    $cart = Cart::lines();
    if (!$cart['lines']) { flash('info', 'Votre panier est vide.'); redirect('/campagnes'); }
    if (!input('cgv')) { flash('error', 'Merci d\'accepter les conditions générales et le mandat d\'achat groupé.'); redirect('/engagement'); }
    $opts = [
        'container_choice' => in_array(input('container'), ['kraft', 'own', 'deposit'], true) ? input('container') : 'kraft',
        'notes' => mb_substr((string)input('notes', ''), 0, 500) ?: null,
        'payment_mode' => 'on_pickup',
    ];
    if ($user['phone'] === null && input('phone')) {
        db()->exec('UPDATE users SET phone = ? WHERE id = ?', [mb_substr((string)input('phone'), 0, 30), $user['id']]);
    }
    if (stripe_enabled() && input('pay_mode') !== 'on_pickup_fallback') {
        $siId = (string)input('setup_intent_id', '');
        try {
            $si = Stripe::retrieveSetupIntent($siId);
            $customer = Stripe::ensureCustomer(Auth::user());
            if ($si['status'] !== 'succeeded' || ($si['customer'] ?? '') !== $customer) throw new RuntimeException('Carte non validée');
            $opts['payment_mode'] = 'stripe';
            $opts['setup_intent_id'] = $si['id'];
            $opts['payment_method_id'] = is_array($si['payment_method']) ? $si['payment_method']['id'] : $si['payment_method'];
        } catch (Throwable $e) {
            flash('error', 'La validation de votre carte n\'a pas abouti. Merci de réessayer.');
            redirect('/engagement');
        }
    }
    try {
        $ids = Orders::createFromCart($user, $cart, $opts);
    } catch (DomainException $e) {
        flash('error', $e->getMessage());
        redirect('/panier');
    }
    Cart::clear();
    unset($_SESSION['setup_intent']);
    $first = Orders::find($ids[0]);
    flash('success', 'Merci ! Votre engagement est enregistré. Un email de confirmation vient de partir.');
    redirect('/compte/commande/' . $first['reference'] . '?merci=1');
}

function stripe_webhook(): void
{
    $payload = (string)file_get_contents('php://input');
    $secret = (string)config('stripe.webhook_secret', '');
    $event = $secret !== '' ? Stripe::verifyWebhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret) : null;
    if (!$event) json_out(['error' => 'signature'], 400);
    try {
        db()->insert('stripe_events', ['id' => $event['id'], 'type' => $event['type']]);
    } catch (PDOException) {
        json_out(['ok' => true, 'duplicate' => true]);
    }
    $obj = $event['data']['object'] ?? [];
    if (in_array($event['type'], ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
        $oid = (int)($obj['metadata']['order_id'] ?? 0);
        if (!$oid) $oid = (int)db()->val('SELECT id FROM orders WHERE stripe_payment_intent_id = ?', [$obj['id'] ?? '']);
        if ($oid) {
            if ($event['type'] === 'payment_intent.succeeded') {
                db()->exec('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?', [$obj['id'], $oid]);
                Orders::markPaid($oid);
            } else {
                db()->exec("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status <> 'paid'", [$oid]);
            }
        }
    }
    json_out(['ok' => true]);
}
