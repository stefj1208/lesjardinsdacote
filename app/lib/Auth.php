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
        $u = self::requireLogin();
        if (!self::isAdmin()) abort(403, 'Cet espace est réservé aux administrateurs.');
        return $u;
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
