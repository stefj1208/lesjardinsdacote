<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ */
/* Configuration & base de données                                     */
/* ------------------------------------------------------------------ */

function config(string $path, $default = null)
{
    $node = $GLOBALS['CONFIG'] ?? [];
    foreach (explode('.', $path) as $k) {
        if (!is_array($node) || !array_key_exists($k, $node)) return $default;
        $node = $node[$k];
    }
    return $node;
}

function db(): DB
{
    static $db = null;
    if ($db === null) {
        $db = new DB(config('db.host'), config('db.name'), config('db.user'), (string)config('db.pass'));
    }
    return $db;
}

function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null || $key === '__reset__') {
        $cache = [];
        foreach (db()->all('SELECT k, v FROM settings') as $r) $cache[$r['k']] = $r['v'];
        if ($key === '__reset__') return null;
    }
    return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}

function set_setting(string $key, ?string $value): void
{
    db()->exec('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
    setting('__reset__');
}

function stripe_enabled(): bool
{
    return config('stripe.secret_key') !== '' && config('stripe.secret_key') !== null
        && config('stripe.public_key') !== '' && config('stripe.public_key') !== null;
}

/* ------------------------------------------------------------------ */
/* HTTP                                                                */
/* ------------------------------------------------------------------ */

function base_url(): string
{
    $u = (string)config('base_url', '');
    if ($u !== '') return rtrim($u, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function url(string $path = '/', array $query = []): string
{
    $u = '/' . ltrim($path, '/');
    if ($query) $u .= '?' . http_build_query($query);
    return $u;
}

function abs_url(string $path = '/', array $query = []): string
{
    return base_url() . url($path, $query);
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string)filemtime($file), -6) : APP_VERSION;
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    if ($ref && $host === ($_SERVER['HTTP_HOST'] ?? $host)) redirect($ref);
    redirect($fallback);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = input($key);
    return is_numeric($v) ? (int)$v : $default;
}

function input_dec(string $key, ?float $default = 0.0): ?float
{
    $v = input($key);
    if ($v === null || $v === '') return $default;
    $v = str_replace([' ', "\u{202F}", "\u{00A0}", ','], ['', '', '', '.'], (string)$v);
    return is_numeric($v) ? (float)$v : $default;
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function json_out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code = 404, string $message = ''): never
{
    http_response_code($code);
    if (wants_json()) json_out(['error' => $message ?: 'Erreur ' . $code], $code);
    $file = APP_DIR . '/views/errors/' . ($code === 403 ? '403' : '404') . '.php';
    view('errors/' . ($code === 403 ? '403' : '404'), ['message' => $message, 'title' => 'Page introuvable']);
    exit;
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/* ------------------------------------------------------------------ */
/* Sessions, flash, CSRF                                               */
/* ------------------------------------------------------------------ */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = str_starts_with(base_url(), 'https://');
    session_name('jdc_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function keep_old(): void
{
    $_SESSION['_old'] = array_diff_key($_POST, array_flip(['password', 'password_confirm', '_csrf']));
}

function old(string $key, $default = '')
{
    return $_SESSION['_old_now'][$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        abort(403, 'Session expirée, merci de recharger la page et de réessayer.');
    }
}

/* ------------------------------------------------------------------ */
/* Vues                                                                */
/* ------------------------------------------------------------------ */

function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render(string $template, array $data = []): string
{
    $file = APP_DIR . '/views/' . $template . '.php';
    if (!is_file($file)) throw new RuntimeException('Vue introuvable : ' . $template);
    extract($data, EXTR_SKIP);
    ob_start();
    include $file;
    return (string)ob_get_clean();
}

function view(string $template, array $data = [], string $layout = 'layouts/main'): void
{
    $content = render($template, $data);
    if ($layout === '') { echo $content; return; }
    echo render($layout, $data + ['content' => $content]);
}

function partial(string $template, array $data = []): void
{
    echo render($template, $data);
}

/* ------------------------------------------------------------------ */
/* Formatage                                                           */
/* ------------------------------------------------------------------ */

function money($amount, bool $symbol = true): string
{
    $s = number_format((float)$amount, 2, ',', "\u{202F}");
    return $symbol ? $s . "\u{00A0}€" : $s;
}

function num($v, int $maxDecimals = 2): string
{
    $v = round((float)$v, $maxDecimals);
    $dec = ($v == floor($v)) ? 0 : $maxDecimals;
    $s = number_format($v, $dec, ',', "\u{202F}");
    if ($dec > 0) $s = rtrim(rtrim($s, '0'), ',');
    return $s;
}

/** Nombre pour un champ de saisie : virgule décimale, sans séparateur de milliers. */
function dec_in($v, int $maxDecimals = 3): string
{
    if ($v === null || $v === '') return '';
    $s = number_format((float)$v, $maxDecimals, ',', '');
    return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

const FR_MONTHS = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const FR_MONTHS_SHORT = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const FR_DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

function date_fr(?string $dt, bool $withTime = false, bool $withDay = false): string
{
    if (!$dt) return '—';
    $t = strtotime($dt);
    $s = ($withDay ? FR_DAYS[(int)date('w', $t)] . ' ' : '') . date('j', $t) . ' ' . FR_MONTHS[(int)date('n', $t)] . ' ' . date('Y', $t);
    if ($withTime) $s .= ' à ' . date('G\hi', $t);
    return $s;
}

function time_range_fr(string $start, string $end): string
{
    $a = strtotime($start); $b = strtotime($end);
    return ucfirst(FR_DAYS[(int)date('w', $a)]) . ' ' . date('j', $a) . ' ' . FR_MONTHS[(int)date('n', $a)]
        . ', ' . date('G\hi', $a) . ' – ' . date('G\hi', $b);
}

function remaining_fr(string $end): string
{
    $s = strtotime($end) - time();
    if ($s <= 0) return 'terminée';
    $d = intdiv($s, 86400); $h = intdiv($s % 86400, 3600); $m = intdiv($s % 3600, 60);
    if ($d > 0) return $d . ' j ' . $h . ' h';
    if ($h > 0) return $h . ' h ' . $m . ' min';
    return max(1, $m) . ' min';
}

function plural(int|float $n, string $one, string $many): string
{
    return ($n >= 2 ? $many : $one);
}

function slugify(string $s): string
{
    $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s) ?: strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim((string)$s, '-') ?: 'campagne';
}

function random_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function audit(string $action, string $details = ''): void
{
    try {
        db()->exec('INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)', [Auth::id(), $action, mb_substr($details, 0, 1000)]);
    } catch (Throwable) { /* ne jamais bloquer pour un log */ }
}

function nl2p(string $text): string
{
    $parts = preg_split('/\n{2,}/', trim($text)) ?: [];
    return implode('', array_map(fn($p) => '<p>' . nl2br(e($p)) . '</p>', $parts));
}
