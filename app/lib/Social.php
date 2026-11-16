<?php
declare(strict_types=1);

/**
 * Publications sur les réseaux : l'agent prépare le texte et le visuel, le président clique « Publier ».
 * Publication directe sur la page Facebook et le compte Instagram professionnel par l'API Graph de Meta,
 * dès que l'identifiant de page, l'identifiant Instagram et le jeton de page sont saisis dans Paramètres.
 */
final class Social
{
    private const DIR = '/uploads/social';

    public static function facebookReady(): bool
    {
        return setting('fb_page_id', '') !== '' && setting('fb_page_token', '') !== '';
    }

    public static function instagramReady(): bool
    {
        return setting('ig_user_id', '') !== '' && setting('fb_page_token', '') !== '';
    }

    /** Visuel proposé par un agent : chemin du site (photo du catalogue) ou image https, copiée sur le site. */
    public static function adoptImage(string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '') return null;
        $host = (string)parse_url(base_url(), PHP_URL_HOST);
        if (preg_match('#^https?://' . preg_quote($host, '#') . '(/.*)$#i', $ref, $m)) $ref = $m[1];
        if ($ref[0] === '/') {
            $path = strtok($ref, '?');
            return preg_match('#^/(assets/varietes|uploads/(photos|social))/[A-Za-z0-9._-]+\.(jpe?g|png|webp)$#i', $path) && is_file(PUBLIC_DIR . $path) ? $path : null;
        }
        if (!preg_match('#^https://#i', $ref) || !function_exists('curl_init')) return null;
        $ch = curl_init($ref);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 25,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_USERAGENT => 'LesJardinsDaCote/1.0']);
        $data = curl_exec($ch);
        $ok = $data !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        if (!$ok || strlen($data) < 2000 || strlen($data) > 8 * 1024 * 1024) return null;
        return self::store($data);
    }

    /** Image choisie par le président depuis son téléphone ou son ordinateur. */
    public static function adoptUpload(string $field): ?string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name']) || $_FILES[$field]['size'] > 8 * 1024 * 1024) return null;
        return self::store((string)file_get_contents($_FILES[$field]['tmp_name']));
    }

    private static function store(string $data): ?string
    {
        $info = @getimagesizefromstring($data);
        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'][$info[2] ?? 0] ?? null;
        if (!$ext) return null;
        $dir = PUBLIC_DIR . self::DIR;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $name = date('Ymd') . '-' . random_token(6) . '.' . $ext;
        return @file_put_contents($dir . '/' . $name, $data) ? self::DIR . '/' . $name : null;
    }

    /** Publie le texte (et le visuel) sur les réseaux demandés. Retourne ['ok' => [...], 'errors' => [...]]. */
    public static function publish(string $text, ?string $image, array $networks): array
    {
        $ok = []; $errors = [];
        $token = (string)setting('fb_page_token', '');
        $imageUrl = $image ? rtrim(base_url(), '/') . $image : null;
        foreach (array_unique($networks) as $n) {
            try {
                if ($n === 'facebook') {
                    if (!self::facebookReady()) throw new RuntimeException('Facebook n\'est pas encore relié (Paramètres).');
                    $page = rawurlencode((string)setting('fb_page_id'));
                    $imageUrl
                        ? self::graph("$page/photos", ['url' => $imageUrl, 'caption' => $text, 'access_token' => $token])
                        : self::graph("$page/feed", ['message' => $text, 'access_token' => $token]);
                    $ok[] = 'Facebook';
                } elseif ($n === 'instagram') {
                    if (!self::instagramReady()) throw new RuntimeException('Instagram n\'est pas encore relié (Paramètres).');
                    if (!$imageUrl) throw new RuntimeException('Instagram exige une image.');
                    $ig = rawurlencode((string)setting('ig_user_id'));
                    $c = self::graph("$ig/media", ['image_url' => $imageUrl, 'caption' => $text, 'access_token' => $token]);
                    self::graph("$ig/media_publish", ['creation_id' => (string)($c['id'] ?? ''), 'access_token' => $token]);
                    $ok[] = 'Instagram';
                }
            } catch (Throwable $e) {
                $errors[] = ucfirst($n) . ' : ' . $e->getMessage();
            }
        }
        return ['ok' => $ok, 'errors' => $errors];
    }

    private static function graph(string $path, array $fields): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('extension curl absente');
        $ch = curl_init('https://graph.facebook.com/' . $path);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('réseau injoignable (' . $err . ')');
        $d = json_decode((string)$raw, true);
        if (!is_array($d) || isset($d['error'])) throw new RuntimeException(mb_substr((string)($d['error']['message'] ?? 'réponse inattendue'), 0, 300));
        return $d;
    }
}
