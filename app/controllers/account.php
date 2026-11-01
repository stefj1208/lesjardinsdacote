<?php
declare(strict_types=1);

const NOINDEX = '<meta name="robots" content="noindex">';

function account_login_form(): void
{
    if (Auth::check()) redirect('/compte');
    view('account/login', ['title' => 'Connexion', 'head' => NOINDEX]);
}

function account_login(): void
{
    if (Auth::tooManyAttempts()) {
        flash('error', 'Trop de tentatives. Patientez quelques minutes avant de réessayer.');
        redirect('/connexion');
    }
    if (!Auth::attempt((string)input('email', ''), (string)($_POST['password'] ?? ''))) {
        keep_old();
        flash('error', 'Email ou mot de passe incorrect.');
        redirect('/connexion');
    }
    $to = $_SESSION['intended'] ?? (Auth::isAdmin() ? '/admin' : '/compte');
    unset($_SESSION['intended']);
    apply_pending_join();
    Cart::applyPending((int)Auth::id());
    if (!is_string($to) || !str_starts_with($to, '/') || str_starts_with($to, '//')) $to = '/compte';
    redirect($to);
}

function account_register_form(): void
{
    if (Auth::check()) redirect('/compte');
    view('account/register', ['title' => 'Créer un compte', 'head' => NOINDEX]);
}

function account_register(): void
{
    $email = mb_strtolower((string)input('email', ''));
    $pass = (string)($_POST['password'] ?? '');
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';
    if (mb_strlen($pass) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if (trim((string)input('first_name')) === '') $errors[] = 'Le prénom est obligatoire.';
    if (!input('consent')) $errors[] = 'Merci d\'accepter la politique de confidentialité.';
    if (!$errors && db()->val('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = 'Un compte existe déjà avec cet email. Connectez-vous ou réinitialisez votre mot de passe.';
    if ($errors) {
        keep_old();
        foreach ($errors as $er) flash('error', $er);
        redirect('/inscription');
    }
    $id = db()->insert('users', [
        'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
        'first_name' => mb_substr((string)input('first_name'), 0, 80), 'last_name' => mb_substr((string)input('last_name', ''), 0, 80),
        'phone' => input('phone') ? mb_substr((string)input('phone'), 0, 30) : null,
        'postal_code' => input('postal_code') ? mb_substr((string)input('postal_code'), 0, 10) : null,
        'wants_volunteer' => input('volunteer') ? 1 : 0,
    ]);
    Auth::login($id);
    $u = Auth::user();
    Mailer::queue($email, 'Bienvenue aux Jardins d\'à Côté 🌱', Mailer::layout('Bienvenue !',
        '<p>Bonjour ' . e($u['first_name']) . ',</p><p>Votre compte est créé. Vous pouvez maintenant rejoindre les campagnes d\'achat groupé : plus nous sommes nombreux, plus les prix baissent pour tout le monde.</p>'
        . ($u['wants_volunteer'] ? '<p>Merci aussi de proposer votre aide comme bénévole ! Un membre de l\'association valide votre accès très vite.</p>' : ''),
        'Voir les campagnes', abs_url('/campagnes')));
    flash('success', 'Bienvenue ' . $u['first_name'] . ' ! Votre compte est créé.');
    $to = $_SESSION['intended'] ?? '/groupes';
    unset($_SESSION['intended']);
    if (!is_string($to) || !str_starts_with($to, '/') || str_starts_with($to, '//')) $to = '/groupes';
    apply_pending_join();
    Cart::applyPending((int)$id);
    redirect($to);
}

function account_logout(): void
{
    Auth::logout();
    flash('info', 'Vous êtes déconnecté·e. À bientôt au jardin !');
    redirect('/');
}

function account_forgot_form(): void
{
    view('account/forgot', ['title' => 'Mot de passe oublié', 'head' => NOINDEX]);
}

function account_forgot(): void
{
    $email = mb_strtolower((string)input('email', ''));
    $u = db()->one('SELECT * FROM users WHERE email = ?', [$email]);
    if ($u && !Auth::tooManyAttempts()) {
        $token = random_token(24);
        db()->exec('UPDATE users SET reset_token_hash = ?, reset_expires_at = (NOW() + INTERVAL 1 HOUR) WHERE id = ?', [hash('sha256', $token), $u['id']]);
        Mailer::sendNow($u['email'], 'Réinitialisation de votre mot de passe', Mailer::layout('Nouveau mot de passe',
            '<p>Bonjour ' . e($u['first_name']) . ',</p><p>Vous avez demandé à réinitialiser votre mot de passe. Ce lien est valable une heure.</p><p class="small">Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement cet email.</p>',
            'Choisir un nouveau mot de passe', abs_url('/reinitialiser/' . $token)));
    }
    db()->exec('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [client_ip(), 'reset:' . mb_substr($email, 0, 180)]);
    flash('success', 'Si un compte existe avec cette adresse, un email de réinitialisation vient d\'être envoyé.');
    redirect('/connexion');
}

function account_reset_user(string $token): ?array
{
    return db()->one('SELECT * FROM users WHERE reset_token_hash = ? AND reset_expires_at > NOW()', [hash('sha256', $token)]);
}

function account_reset_form(string $token): void
{
    if (!account_reset_user($token)) { flash('error', 'Ce lien a expiré. Faites une nouvelle demande.'); redirect('/mot-de-passe-oublie'); }
    view('account/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'head' => NOINDEX]);
}

function account_reset(string $token): void
{
    $u = account_reset_user($token);
    if (!$u) { flash('error', 'Ce lien a expiré. Faites une nouvelle demande.'); redirect('/mot-de-passe-oublie'); }
    $pass = (string)($_POST['password'] ?? '');
    if (mb_strlen($pass) < 8) { flash('error', 'Le mot de passe doit contenir au moins 8 caractères.'); redirect('/reinitialiser/' . $token); }
    db()->exec('UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_expires_at = NULL WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
    Auth::login((int)$u['id']);
    flash('success', 'Mot de passe mis à jour.');
    redirect('/compte');
}

function account_home(): void
{
    $u = Auth::requireLogin();
    $orders = array_map(fn($id) => Orders::find((int)$id), db()->col('SELECT id FROM orders WHERE user_id = ? ORDER BY created_at DESC', [$u['id']]));
    $groups = Groups::hydrate(db()->all("SELECT g.*, gm.role AS my_role FROM `groups` g JOIN group_members gm ON gm.group_id = g.id WHERE gm.user_id = ? AND g.status = 'active' ORDER BY g.commune", [$u['id']]));
    $cart = Cart::items((int)$u['id']);
    $shifts = Auth::isVolunteer() ? db()->all('SELECT s.* FROM shifts s JOIN shift_signups ss ON ss.shift_id = s.id WHERE ss.user_id = ? AND s.ends_at > NOW() ORDER BY s.starts_at', [$u['id']]) : [];
    view('account/home', ['u' => $u, 'orders' => $orders, 'groups' => $groups, 'cart' => $cart, 'shifts' => $shifts, 'title' => 'Mon compte', 'head' => NOINDEX]);
}

function account_profile_form(): void
{
    $u = Auth::requireLogin();
    view('account/profile', ['u' => $u, 'title' => 'Mon profil', 'head' => NOINDEX]);
}

function account_profile(): void
{
    $u = Auth::requireLogin();
    $data = [
        'first_name' => mb_substr((string)input('first_name', ''), 0, 80), 'last_name' => mb_substr((string)input('last_name', ''), 0, 80),
        'phone' => input('phone') ? mb_substr((string)input('phone'), 0, 30) : null, 'postal_code' => input('postal_code') ? mb_substr((string)input('postal_code'), 0, 10) : null,
        'notify_tiers' => input('notify_tiers') ? 1 : 0,
    ];
    $new = (string)($_POST['new_password'] ?? '');
    if ($new !== '') {
        if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password_hash'])) { flash('error', 'Mot de passe actuel incorrect.'); redirect('/compte/profil'); }
        if (mb_strlen($new) < 8) { flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.'); redirect('/compte/profil'); }
        $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
    }
    db()->update('users', $data, 'id = ?', [$u['id']]);
    flash('success', 'Profil enregistré.');
    redirect('/compte/profil');
}

function account_order_for_user(string $ref): array
{
    $u = Auth::requireLogin();
    $o = Orders::findByRef($ref);
    if (!$o || ((int)$o['user_id'] !== (int)$u['id'] && !Auth::isAdmin())) abort(404, 'Commande introuvable.');
    return $o;
}

function account_order(string $ref): void
{
    $o = account_order_for_user($ref);
    if (input('payment_intent') && input('payment_intent') === $o['stripe_payment_intent_id'] && stripe_enabled() && $o['payment_status'] !== 'paid') {
        try {
            if ((Stripe::retrievePaymentIntent($o['stripe_payment_intent_id'])['status'] ?? '') === 'succeeded') {
                Orders::markPaid((int)$o['id']);
                $o = Orders::find((int)$o['id']);
                flash('success', 'Paiement confirmé, merci !');
            }
        } catch (Throwable) { /* le webhook prendra le relais */ }
    }
    $s = Sales::one((int)$o['sale_id']);
    view('account/order', ['o' => $o, 'items' => Orders::items($o), 'sale' => $s, 'thanks' => (bool)input('merci'),
        'title' => 'Commande ' . $o['reference'], 'head' => NOINDEX]);
}

function account_order_cancel(string $ref): void
{
    $o = account_order_for_user($ref);
    try {
        Orders::cancel($o);
        flash('success', 'Votre achat est annulé. Aucun montant ne sera prélevé.');
    } catch (DomainException $e) {
        flash('error', $e->getMessage());
    }
    redirect('/compte/commande/' . $ref);
}

function account_invoice(string $ref): void
{
    $o = account_order_for_user($ref);
    if (!$o['invoice_number']) abort(404, 'La facture sera disponible après le paiement.');
    view('account/invoice', ['o' => $o, 'items' => Orders::items($o), 'title' => 'Facture ' . $o['invoice_number'], 'head' => NOINDEX], 'layouts/print');
}

function account_pay_form(string $ref): void
{
    $o = account_order_for_user($ref);
    if (!in_array($o['payment_status'], ['action_required', 'failed'], true) || !stripe_enabled()) redirect('/compte/commande/' . $ref);
    try {
        $pi = $o['stripe_payment_intent_id'] ? Stripe::retrievePaymentIntent($o['stripe_payment_intent_id']) : null;
        if ($pi && $pi['status'] === 'succeeded') { Orders::markPaid((int)$o['id']); redirect('/compte/commande/' . $ref); }
        if (!$pi || in_array($pi['status'], ['canceled'], true) || (int)$pi['amount'] !== (int)round((float)$o['amount'] * 100)) {
            $pi = Stripe::request('POST', 'payment_intents', [
                'amount' => (int)round((float)$o['amount'] * 100), 'currency' => 'eur', 'customer' => $o['stripe_customer_id'] ?: Stripe::ensureCustomer(Auth::user()),
                'payment_method_types' => ['card'], 'setup_future_usage' => null,
                'description' => 'Commande ' . $o['reference'], 'metadata' => ['order_ref' => $o['reference'], 'order_id' => $o['id']],
            ]);
            db()->exec('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?', [$pi['id'], $o['id']]);
        }
    } catch (Throwable $e) {
        error_log('Stripe pay: ' . $e->getMessage());
        flash('error', 'Le paiement en ligne est momentanément indisponible. Vous pourrez régler au retrait.');
        redirect('/compte/commande/' . $ref);
    }
    view('account/pay', ['o' => $o, 'clientSecret' => $pi['client_secret'], 'title' => 'Paiement ' . $o['reference'], 'head' => NOINDEX,
        'scripts' => '<script src="https://js.stripe.com/v3/"></script><script src="' . asset('js/pay.js') . '" defer></script>']);
}

function account_pay_confirm(string $ref): void
{
    $o = account_order_for_user($ref);
    try {
        $pi = Stripe::retrievePaymentIntent((string)$o['stripe_payment_intent_id']);
        if ($pi['status'] === 'succeeded') {
            Orders::markPaid((int)$o['id']);
            flash('success', 'Paiement confirmé, merci !');
        } elseif ($pi['status'] === 'processing') {
            flash('info', 'Paiement en cours de traitement par votre banque.');
        } else {
            flash('error', 'Le paiement n\'a pas abouti. Merci de réessayer.');
            redirect('/compte/paiement/' . $ref);
        }
    } catch (Throwable) {
        flash('error', 'Vérification du paiement impossible pour le moment.');
    }
    redirect('/compte/commande/' . $ref);
}
