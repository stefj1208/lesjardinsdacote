<?php
declare(strict_types=1);

/**
 * Client Stripe minimal (API REST, sans SDK ni Composer) — compatible hébergement mutualisé.
 *
 * Flux retenu (Pilier A du cahier des charges) :
 *  - à l'engagement : SetupIntent (usage=off_session) → carte enregistrée et validée 3-D Secure, 0 € débité ;
 *  - à la clôture   : PaymentIntent off_session confirmé au prix du dernier palier atteint ;
 *  - si la banque exige une nouvelle authentification : lien de paiement envoyé au client.
 */
final class StripeError extends RuntimeException
{
    public array $payload = [];
}

final class Stripe
{
    private const API = 'https://api.stripe.com/v1/';
    private const VERSION = '2024-06-20';

    public static function request(string $method, string $path, array $params = [], ?string $idempotencyKey = null): array
    {
        $secret = (string)config('stripe.secret_key', '');
        if ($secret === '') throw new StripeError('Stripe n\'est pas configuré.');
        $ch = curl_init();
        $url = rtrim((string)config('stripe.api_base', self::API), '/') . '/' . ltrim($path, '/');
        $headers = ['Stripe-Version: ' . self::VERSION];
        if ($idempotencyKey) $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        $body = self::encode($params);
        if ($method === 'GET' && $body !== '') { $url .= '?' . $body; $body = ''; }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $secret . ':',
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        if ($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new StripeError('Connexion à Stripe impossible : ' . $err);
        $data = json_decode((string)$raw, true) ?: [];
        if ($code >= 400) {
            $e = new StripeError($data['error']['message'] ?? ('Erreur Stripe HTTP ' . $code));
            $e->payload = $data['error'] ?? [];
            throw $e;
        }
        return $data;
    }

    private static function encode(array $params, string $prefix = ''): string
    {
        $out = [];
        foreach ($params as $k => $v) {
            $key = $prefix === '' ? (string)$k : $prefix . '[' . $k . ']';
            if (is_array($v)) { $s = self::encode($v, $key); if ($s !== '') $out[] = $s; }
            elseif (is_bool($v)) $out[] = urlencode($key) . '=' . ($v ? 'true' : 'false');
            elseif ($v !== null) $out[] = urlencode($key) . '=' . urlencode((string)$v);
        }
        return implode('&', $out);
    }

    public static function ensureCustomer(array $user): string
    {
        if (!empty($user['stripe_customer_id'])) return $user['stripe_customer_id'];
        $c = self::request('POST', 'customers', [
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']) ?: null,
            'phone' => $user['phone'] ?: null,
            'metadata' => ['user_id' => $user['id']],
        ], 'cust-' . $user['id']);
        db()->exec('UPDATE users SET stripe_customer_id = ? WHERE id = ?', [$c['id'], $user['id']]);
        return $c['id'];
    }

    public static function createSetupIntent(string $customerId, string $description): array
    {
        return self::request('POST', 'setup_intents', [
            'customer' => $customerId,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
            'description' => $description,
        ]);
    }

    public static function retrieveSetupIntent(string $id): array
    {
        return self::request('GET', 'setup_intents/' . rawurlencode($id));
    }

    /** Débit différé hors session au prix final. */
    public static function chargeOffSession(string $customerId, string $paymentMethodId, int $amountCents, string $description, array $metadata, string $idempotencyKey): array
    {
        return self::request('POST', 'payment_intents', [
            'amount' => $amountCents,
            'currency' => 'eur',
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'off_session' => true,
            'confirm' => true,
            'description' => $description,
            'statement_descriptor_suffix' => 'JARDINS A COTE',
            'metadata' => $metadata,
        ], $idempotencyKey);
    }

    public static function retrievePaymentIntent(string $id): array
    {
        return self::request('GET', 'payment_intents/' . rawurlencode($id));
    }

    public static function refund(string $paymentIntentId, ?int $amountCents = null): array
    {
        return self::request('POST', 'refunds', ['payment_intent' => $paymentIntentId, 'amount' => $amountCents]);
    }

    /** Vérification de la signature des webhooks (en-tête Stripe-Signature). */
    public static function verifyWebhook(string $payload, string $sigHeader, string $secret, int $tolerance = 300): ?array
    {
        $ts = null; $sigs = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') $ts = (int)$v;
            if ($k === 'v1') $sigs[] = $v;
        }
        if (!$ts || !$sigs || abs(time() - $ts) > $tolerance) return null;
        $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
        foreach ($sigs as $s) if (hash_equals($expected, $s)) return json_decode($payload, true);
        return null;
    }
}
