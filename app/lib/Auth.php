<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['uid'] ?? null;
            if (!$id) $id = self::fromRemember();
            if ($id) {
                self::$user = db()->one('SELECT * FROM users WHERE id = ?', [(int)$id]);
                if (!self::$user) unset($_SESSION['uid']);
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isVolunteer(): bool
    {
        $u = self::user();
        return $u && in_array($u['role'], ['volunteer', 'admin'], true);
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u && $u['role'] === 'admin';
    }

    public static function requireLogin(): array
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('info', 'Connectez-vous pour continuer.');
            redirect(url('/connexion'));
        }
        return self::user();
    }

    public static function requireVolunteer(): array
    {
        $u = self::requireLogin();
        if (!self::isVolunteer()) abort(403, "Cet espace est réservé aux bénévoles de l'association.");
        return $u;
    }

    public static function requireAdmin(): array
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/admin';
            redirect(url('/admin/connexion'));
        }
        if (!self::isAdmin()) abort(403, 'Cet espace est réservé aux administrateurs.');
        return self::user();
    }

    /* ---- « Rester connecté sur cet appareil » (application du back-office) ---- */

    private const REMEMBER_COOKIE = 'jdc_keep';
    private const REMEMBER_DAYS = 60;

    public static function remember(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        db()->exec('DELETE FROM auth_tokens WHERE expires_at < NOW()');
        db()->insert('auth_tokens', ['user_id' => $userId, 'selector' => $selector, 'validator_hash' => hash('sha256', $validator),
            'expires_at' => date('Y-m-d H:i:s', time() + self::REMEMBER_DAYS * 86400), 'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        self::setRememberCookie($selector . ':' . $validator, time() + self::REMEMBER_DAYS * 86400);
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, ['expires' => $expires, 'path' => '/', 'secure' => str_starts_with(base_url(), 'https://'), 'httponly' => true, 'samesite' => 'Lax']);
    }

    private static function fromRemember(): ?int
    {
        $c = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (!preg_match('/^([a-f0-9]{18}):([a-f0-9]{64})$/', $c, $m)) return null;
        try {
            $t = db()->one('SELECT * FROM auth_tokens WHERE selector = ? AND expires_at > NOW()', [$m[1]]);
        } catch (Throwable) { return null; }
        if (!$t || !hash_equals($t['validator_hash'], hash('sha256', $m[2]))) { self::setRememberCookie('', time() - 3600); return null; }
        // Rotation du jeton à chaque reprise de session.
        db()->exec('DELETE FROM auth_tokens WHERE id = ?', [$t['id']]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$t['user_id'];
        self::remember((int)$t['user_id']);
        return (int)$t['user_id'];
    }

    public static function forgetDevice(): void
    {
        $c = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (preg_match('/^([a-f0-9]{18}):/', $c, $m)) { try { db()->exec('DELETE FROM auth_tokens WHERE selector = ?', [$m[1]]); } catch (Throwable) {} }
        self::setRememberCookie('', time() - 3600);
    }

    public static function tooManyAttempts(): bool
    {
        $n = (int)db()->val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [client_ip()]);
        return $n >= 8;
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $u = db()->one('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            db()->exec('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [client_ip(), mb_substr($email, 0, 190)]);
            return false;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            db()->exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        db()->exec('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
        self::login((int)$u['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        self::$loaded = false;
        db()->exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$userId]);
    }

    public static function logout(): void
    {
        self::forgetDevice();
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
        self::$loaded = true;
    }

    public static function displayName(?array $u = null): string
    {
        $u ??= self::user();
        if (!$u) return '';
        $n = trim($u['first_name'] . ' ' . $u['last_name']);
        return $n !== '' ? $n : $u['email'];
    }
}
