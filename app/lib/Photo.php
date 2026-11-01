<?php
declare(strict_types=1);

/**
 * Photos : référence Wikimedia Commons (« commons:Fichier.jpg »), URL ou fichier local.
 * Plusieurs candidates possibles, séparées par « | » : la première disponible est utilisée.
 * Les photos distantes s'affichent tout de suite, puis la tâche planifiée les copie sur le
 * serveur (plus rapide, aucune requête vers un tiers) et récupère auteur et licence.
 */
final class Photo
{
    private static ?array $map = null;
    private const DIR = '/uploads/photos';

    private static function load(): void
    {
        if (self::$map !== null) return;
        self::$map = [];
        try {
            foreach (db()->all('SELECT ref_hash, status, local_path, attempts FROM photos') as $r) self::$map[$r['ref_hash']] = $r;
        } catch (Throwable) { /* table absente pendant l'installation */ }
    }

    public static function url(?string $ref, int $width = 1200): ?string
    {
        $ref = trim((string)$ref);
        if ($ref === '') return null;
        self::load();
        foreach (array_filter(array_map('trim', explode('|', $ref))) as $c) {
            if (str_starts_with($c, '/')) return $c;
            $h = md5($c);
            $row = self::$map[$h] ?? null;
            if ($row && $row['status'] === 'cached' && $row['local_path'] && is_file(PUBLIC_DIR . $row['local_path'])) return $row['local_path'];
            if ($row && ($row['status'] === 'missing' || ($row['status'] === 'failed' && (int)$row['attempts'] >= 3))) continue;
            if (!$row) {
                try {
                    db()->exec('INSERT IGNORE INTO photos (ref, ref_hash) VALUES (?, ?)', [$c, $h]);
                    self::$map[$h] = ['ref_hash' => $h, 'status' => 'pending', 'local_path' => null, 'attempts' => 0];
                } catch (Throwable) {}
            }
            return self::remote($c, $width);
        }
        return null;
    }

    public static function remote(string $c, int $width = 1200): string
    {
        if (str_starts_with($c, 'commons:')) {
            $name = str_replace(' ', '_', substr($c, 8));
            return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($name) . '?width=' . $width;
        }
        return $c;
    }

    /** Copie locale + crédits (appelé par la tâche planifiée). */
    public static function cacheBatch(int $limit = 12): array
    {
        $done = ['cached' => 0, 'missing' => 0, 'failed' => 0];
        $dir = PUBLIC_DIR . self::DIR;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return $done;
        $rows = db()->all("SELECT * FROM photos WHERE status = 'pending' OR (status = 'failed' AND attempts < 3) ORDER BY attempts, ref LIMIT " . (int)$limit);
        foreach ($rows as $r) {
            try {
                $res = self::fetchOne($r['ref']);
                if ($res === null) {
                    db()->exec("UPDATE photos SET status = 'missing', attempts = attempts + 1 WHERE ref_hash = ?", [$r['ref_hash']]);
                    $done['missing']++;
                    continue;
                }
                $ext = $res['ext'];
                $path = self::DIR . '/' . $r['ref_hash'] . '.' . $ext;
                file_put_contents(PUBLIC_DIR . $path, $res['bytes']);
                db()->exec("UPDATE photos SET status = 'cached', local_path = ?, source_page = ?, author = ?, license = ?, attempts = attempts + 1 WHERE ref_hash = ?",
                    [$path, $res['page'], $res['author'], $res['license'], $r['ref_hash']]);
                $done['cached']++;
            } catch (Throwable $e) {
                db()->exec("UPDATE photos SET status = 'failed', attempts = attempts + 1 WHERE ref_hash = ?", [$r['ref_hash']]);
                error_log('Photo ' . $r['ref'] . ' : ' . $e->getMessage());
                $done['failed']++;
            }
        }
        return $done;
    }

    private static function http(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 40,
            CURLOPT_USERAGENT => 'LesJardinsDaCote/2.0 (' . base_url() . '; ' . setting('contact_email', 'contact@example.org') . ')',
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('réseau');
        return [$code, (string)$body, $type];
    }

    private static function fetchOne(string $ref): ?array
    {
        $author = null; $license = null; $page = null;
        if (str_starts_with($ref, 'commons:')) {
            $title = 'File:' . str_replace(' ', '_', substr($ref, 8));
            [$code, $json] = self::http('https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url|mime|extmetadata&iiurlwidth=1600&iiextmetadatafilter=Artist|LicenseShortName&titles=' . rawurlencode($title));
            if ($code !== 200) throw new RuntimeException('API ' . $code);
            $data = json_decode($json, true);
            $p = $data['query']['pages'] ?? [];
            $p = $p ? reset($p) : null;
            if (!$p || isset($p['missing']) || empty($p['imageinfo'][0])) return null;
            $ii = $p['imageinfo'][0];
            $src = $ii['thumburl'] ?? $ii['url'];
            $page = $ii['descriptionurl'] ?? null;
            $author = trim(html_entity_decode(strip_tags((string)($ii['extmetadata']['Artist']['value'] ?? '')), ENT_QUOTES, 'UTF-8')) ?: null;
            $license = trim(strip_tags((string)($ii['extmetadata']['LicenseShortName']['value'] ?? ''))) ?: null;
        } elseif (preg_match('#^https?://#', $ref)) {
            $src = $ref;
            $page = $ref;
        } else {
            return null;
        }
        [$code, $bytes, $type] = self::http($src);
        if ($code === 404) return null;
        if ($code !== 200 || strlen($bytes) < 2000) throw new RuntimeException('téléchargement ' . $code);
        $info = @getimagesizefromstring($bytes);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if (!$ext) throw new RuntimeException('format ' . $type);
        return ['bytes' => $bytes, 'ext' => $ext, 'author' => $author ? mb_substr($author, 0, 250) : null, 'license' => $license, 'page' => $page];
    }

    public static function credits(): array
    {
        return db()->all("SELECT * FROM photos WHERE status = 'cached' AND ref LIKE 'commons:%' ORDER BY ref");
    }
}

/** Balise image avec repli élégant si la photo est indisponible. */
function ph(?string $ref, string $alt = '', string $class = '', int $w = 1200, bool $eager = false): string
{
    $src = Photo::url($ref, $w);
    $img = $src
        ? '<img src="' . e($src) . '" alt="' . e($alt) . '"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async" onerror="this.parentNode.classList.add(\'ph--empty\');this.remove()">'
        : '';
    return '<div class="ph ' . e($class) . ($src ? '' : ' ph--empty') . '">' . $img . '</div>';
}
