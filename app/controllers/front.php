<?php
declare(strict_types=1);

function front_active_campaigns(): array
{
    return Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status = 'active' AND c.start_at <= NOW() ORDER BY c.end_at"));
}

function front_home(): void
{
    $camps = front_active_campaigns();
    $featured = null;
    foreach ($camps as $c) if ($c['is_featured']) { $featured = $c; break; }
    if (!$featured && $camps) {
        $sorted = $camps;
        usort($sorted, fn($a, $b) => $b['state']['to_next_pct'] <=> $a['state']['to_next_pct']);
        $featured = $sorted[0];
    }
    $camps = array_values(array_filter($camps, fn($c) => !$featured || $c['id'] !== $featured['id']));
    $stats = [
        'neighbours' => (int)db()->val("SELECT COUNT(DISTINCT o.user_id) FROM orders o WHERE o.status <> 'cancelled'"),
        'campaigns' => count($camps),
        'saved' => (float)db()->val("SELECT COALESCE(SUM(GREATEST(0, ROUND(c.public_unit_price * oi.portion_qty, 2) * oi.packets - COALESCE(oi.line_total_final, ROUND(c.current_unit_price * oi.portion_qty, 2) * oi.packets))),0)
            FROM order_items oi JOIN campaigns c ON c.id = oi.campaign_id WHERE oi.status NOT IN ('cancelled','failed')"),
    ];
    view('front/home', ['featured' => $featured, 'campaigns' => array_slice($camps, 0, 6), 'stats' => $stats, 'bodyClass' => 'home']);
}

function front_catalogue(): void
{
    $camps = front_active_campaigns();
    $upcoming = Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status = 'active' AND c.start_at > NOW() ORDER BY c.start_at LIMIT 6"));
    $recent = Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status IN ('validated','receiving','packaging','ready_pickup') ORDER BY c.closed_at DESC LIMIT 6"));
    view('front/catalogue', ['campaigns' => $camps, 'upcoming' => $upcoming, 'recent' => $recent, 'title' => 'Campagnes d\'achat groupé',
        'description' => 'Toutes les campagnes en cours : semences bio Agrosemens, plantes, terreau vrac. Rejoignez un achat groupé et faites baisser le prix.']);
}

function front_campaign_payload(array $c): array
{
    return [
        'id' => (int)$c['id'], 'unit' => $c['unit'], 'unitShort' => Units::short($c['unit']), 'ref' => (float)$c['ref_portion'],
        'refUnit' => Units::ALL[$c['unit']]['ref'] ?? 1, 'refLabel' => Units::ALL[$c['unit']]['ref_label'] ?? '',
        'public' => (float)$c['public_unit_price'], 'reserved' => $c['state']['reserved'],
        'tiers' => array_map(fn($t) => ['level' => (int)$t['tier_level'], 'min' => (float)$t['min_qty'], 'price' => (float)$t['unit_price_ttc']], $c['tiers']),
        'pmg' => $c['pmg'] !== null ? (float)$c['pmg'] : null, 'open' => Campaigns::isOpen($c),
    ];
}

function front_campaign(string $slug): void
{
    $c = Campaigns::findBySlug($slug);
    if (!$c || ($c['status'] === 'draft' && !Auth::isAdmin())) abort(404, 'Cette campagne n\'existe pas ou n\'est plus en ligne.');
    $c = Campaigns::hydrate([$c])[0];
    $batch = $c['supplier_batch_id'] ? Campaigns::batchTotals((int)$c['supplier_batch_id']) : null;
    $similar = Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status = 'active' AND c.id <> ? AND p.category = ? ORDER BY c.end_at LIMIT 3", [$c['id'], $c['category']]));
    $st = $c['state'];
    $priceStr = Units::refPrice($st['price'], $c['unit']);
    $og = [
        'type' => 'product',
        'title' => $c['title'] . ' — ' . money(Pricing::packetPrice($st['price'], (float)$c['ref_portion'])) . ' les ' . Units::qty($c['ref_portion'], $c['unit']),
        'description' => $st['next_tier']
            ? 'Plus que ' . $st['parts_missing'] . ' ' . plural($st['parts_missing'], 'part', 'parts') . ' pour débloquer le palier ' . $st['next_tier'] . ' et faire baisser le prix pour tout le monde. Achat groupé solidaire à Toulouse.'
            : 'Prix plancher débloqué ! Achat groupé solidaire à Toulouse.',
        'image' => abs_url('/og/' . $c['slug'] . '-' . $st['tier'] . '-' . $st['parts_missing'] . '.png'),
        'url' => abs_url('/campagne/' . $c['slug']),
    ];
    view('front/campaign', [
        'c' => $c, 'batch' => $batch, 'similar' => $similar, 'title' => $c['title'], 'og' => $og, 'canonical' => abs_url('/campagne/' . $c['slug']),
        'description' => $og['description'], 'priceStr' => $priceStr, 'payload' => front_campaign_payload($c),
    ]);
}

function front_api_campaign(string $id): void
{
    $c = Campaigns::one((int)$id);
    if (!$c || $c['status'] === 'draft') json_out(['error' => 'introuvable'], 404);
    $st = $c['state'];
    json_out([
        'id' => (int)$c['id'], 'state' => $st, 'participants' => $c['participants'], 'status' => $c['status'],
        'remaining' => remaining_fr($c['end_at']), 'open' => Campaigns::isOpen($c),
        'priceRef' => Units::refPrice($st['price'], $c['unit']),
        'pricePortion' => money(Pricing::packetPrice($st['price'], (float)$c['ref_portion'])),
        'missingLabel' => Units::qty($st['missing'], $c['unit']),
    ]);
}

function front_api_campaigns(): void
{
    $out = [];
    foreach (front_active_campaigns() as $c) {
        $out[] = ['id' => (int)$c['id'], 'state' => $c['state'], 'participants' => $c['participants'],
            'pricePortion' => money(Pricing::packetPrice($c['state']['price'], (float)$c['ref_portion']))];
    }
    json_out($out);
}

function front_short_campaign(string $id): void
{
    $slug = db()->val('SELECT slug FROM campaigns WHERE id = ?', [(int)$id]);
    if (!$slug) abort(404);
    redirect('/campagne/' . $slug, 301);
}

/** Visuel de partage dynamique (OpenGraph) : ardoise avec le prix actuel. */
function front_og_image(string $file): void
{
    if (!preg_match('/^(.+?)(?:-\d+-\d+)?\.png$/', $file, $m)) abort(404);
    $c = Campaigns::findBySlug($m[1]);
    if (!$c) abort(404);
    $c = Campaigns::hydrate([$c])[0];
    $st = $c['state'];
    $dir = APP_DIR . '/storage/og';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $cache = $dir . '/' . $c['id'] . '-' . $st['tier'] . '-' . $st['parts_missing'] . '-' . substr(md5($c['title'] . $c['updated_at']), 0, 6) . '.png';
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=600');
    if (is_file($cache)) { readfile($cache); exit; }

    $w = 1200; $h = 630;
    $im = imagecreatetruecolor($w, $h);
    $col = fn($hex) => imagecolorallocate($im, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
    $wood = $col('#8B5A2B'); $woodL = $col('#A9774A'); $slate = $col('#232B2D'); $chalk = $col('#F8FAFC'); $dim = $col('#AAB5B8');
    $yellow = $col('#FDE047'); $green = $col('#22C55E'); $kraft = $col('#E6D5B8');
    imagefilledrectangle($im, 0, 0, $w, $h, $wood);
    for ($y = 0; $y < $h; $y += 7) imageline($im, 0, $y, $w, $y + 3, $y % 21 === 0 ? $woodL : $wood);
    imagefilledrectangle($im, 28, 28, $w - 28, $h - 28, $slate);
    $caveat = APP_DIR . '/fonts/caveat-bold.ttf';
    $sans = APP_DIR . '/fonts/jakarta-bold.ttf';
    $sansM = APP_DIR . '/fonts/jakarta-medium.ttf';
    $text = function (string $t, float $size, int $x, int $y, $color, string $font) use ($im) { imagettftext($im, $size, 0, $x, $y, $color, $font, $t); };
    $title = mb_strimwidth($c['title'], 0, 34, '…');
    $text($title, 64, 80, 150, $chalk, $caveat);
    if ($c['latin_name']) $text($c['latin_name'], 22, 84, 195, $dim, $sansM);
    $portion = Units::qty($c['ref_portion'], $c['unit']);
    $now = money(Pricing::packetPrice($st['price'], (float)$c['ref_portion']));
    $text($now, 110, 80, 360, $yellow, $caveat);
    $text('les ' . $portion, 28, 84, 410, $chalk, $sansM);
    if ($st['discount_pct'] > 0) {
        $pub = money(Pricing::packetPrice($st['public_price'], (float)$c['ref_portion']));
        $text($pub, 46, 560, 330, $dim, $caveat);
        imagesetthickness($im, 5);
        imageline($im, 552, 322, 560 + 150, 300, $chalk);
        $text('−' . $st['discount_pct'] . ' %', 46, 560, 390, $green, $caveat);
    }
    // Jauge
    imagefilledrectangle($im, 80, 460, 1120, 484, $col('#3A4649'));
    $p = $st['next_tier'] ? max(0.02, $st['to_next_pct'] / 100) : 1;
    imagefilledrectangle($im, 80, 460, 80 + (int)(1040 * $p), 484, $green);
    $msg = $st['next_tier']
        ? 'Plus que ' . $st['parts_missing'] . ' ' . plural($st['parts_missing'], 'part', 'parts') . ' pour débloquer le palier ' . $st['next_tier'] . ' !'
        : 'Prix plancher débloqué — récolte abondante !';
    $text($msg, 40, 80, 545, $yellow, $caveat);
    $text("Les Jardins d'à Côté · achat groupé solidaire · Toulouse", 18, 80, 585, $kraft, $sans);
    // Petite pousse
    imagefilledellipse($im, 1040, 250, 150, 150, $col('#2E383B'));
    imagesetthickness($im, 8);
    imageline($im, 1040, 310, 1040, 230, $green);
    imagefilledellipse($im, 1010, 225, 60, 30, $green);
    imagefilledellipse($im, 1072, 210, 66, 32, $col('#4ADE80'));
    imagefilledrectangle($im, 990, 305, 1090, 320, $kraft);
    imagepng($im, $cache);
    imagedestroy($im);
    readfile($cache);
    exit;
}

function front_static(string $page): void
{
    $titles = ['how' => 'Comment ça marche', 'cgv' => 'Conditions générales & mandat d\'achat groupé', 'legal' => 'Mentions légales', 'privacy' => 'Données personnelles'];
    view('front/' . $page, ['title' => $titles[$page] ?? '']);
}

function front_volunteer_info(): void
{
    $shifts = db()->all("SELECT s.*, (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) AS signed FROM shifts s WHERE s.starts_at > NOW() ORDER BY s.starts_at LIMIT 4");
    view('front/volunteer', ['title' => 'Devenir bénévole', 'shifts' => $shifts]);
}

function front_volunteer_apply(): void
{
    $u = Auth::requireLogin();
    if ($u['role'] === 'client') {
        db()->exec('UPDATE users SET wants_volunteer = 1 WHERE id = ?', [$u['id']]);
        $admins = db()->col("SELECT email FROM users WHERE role = 'admin'");
        foreach ($admins as $a) {
            Mailer::queue($a, 'Nouvelle candidature bénévole : ' . Auth::displayName($u),
                Mailer::layout('Nouveau bénévole', '<p>' . e(Auth::displayName($u)) . ' (' . e($u['email']) . ') souhaite rejoindre l\'équipe des bénévoles.</p>', 'Valider dans le back-office', abs_url('/admin/utilisateurs?filtre=candidats')));
        }
    }
    flash('success', 'Merci ! Un membre de l\'association va valider votre accès à l\'espace bénévoles très vite.');
    redirect('/devenir-benevole');
}

function front_qr_png(string $file): void
{
    $token = preg_replace('/\.png$/', '', $file);
    $o = Orders::findByToken($token);
    if (!$o) abort(404);
    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=86400');
    echo QR::png(abs_url('/r/' . $o['pickup_token']), 8);
    exit;
}

function front_pass_redirect(string $token): void
{
    if (Auth::isVolunteer()) redirect('/benevoles/distribution/' . $token);
    redirect('/retrait/' . $token);
}

function front_pass(string $token): void
{
    $o = Orders::findByToken($token);
    if (!$o) abort(404, 'Ce Pass Retrait est introuvable.');
    view('front/pass', ['o' => $o, 'items' => Orders::items((int)$o['id']), 'title' => 'Pass Retrait ' . $o['reference'],
        'head' => '<meta name="robots" content="noindex">']);
}

function front_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /benevoles\nDisallow: /compte\nDisallow: /retrait\nDisallow: /panier\nDisallow: /engagement\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    exit;
}

function front_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = ['/', '/campagnes', '/comment-ca-marche', '/devenir-benevole'];
    foreach (db()->col("SELECT slug FROM campaigns WHERE status <> 'draft'") as $s) $urls[] = '/campagne/' . $s;
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) echo '<url><loc>' . e(abs_url($u)) . '</loc></url>';
    echo '</urlset>';
    exit;
}
