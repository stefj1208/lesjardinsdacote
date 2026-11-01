<?php
declare(strict_types=1);

function vol_view(string $tpl, array $data = []): void
{
    view('volunteer/' . $tpl, $data + ['bodyClass' => 'vol', 'head' => '<meta name="robots" content="noindex">']);
}

function vol_home(): void
{
    $u = Auth::requireVolunteer();
    $mine = db()->all('SELECT s.* FROM shifts s JOIN shift_signups ss ON ss.shift_id = s.id WHERE ss.user_id = ? AND s.ends_at > NOW() ORDER BY s.starts_at LIMIT 3', [$u['id']]);
    $needs = (int)db()->val('SELECT COUNT(*) FROM shifts s WHERE s.starts_at > NOW() AND (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) < s.volunteers_needed');
    $toPack = (int)db()->val("SELECT COUNT(*) FROM campaigns WHERE status IN ('validated','receiving','packaging')");
    $ready = (int)db()->val("SELECT COUNT(*) FROM orders WHERE status = 'ready'");
    vol_view('home', ['u' => $u, 'mine' => $mine, 'needs' => $needs, 'toPack' => $toPack, 'ready' => $ready, 'title' => 'Espace bénévoles']);
}

function vol_planning(): void
{
    $u = Auth::requireVolunteer();
    $shifts = db()->all('SELECT s.*, (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) AS signed,
            EXISTS(SELECT 1 FROM shift_signups ss WHERE ss.shift_id = s.id AND ss.user_id = ?) AS mine,
            (SELECT GROUP_CONCAT(u.first_name SEPARATOR ", ") FROM shift_signups ss JOIN users u ON u.id = ss.user_id WHERE ss.shift_id = s.id) AS names
        FROM shifts s WHERE s.ends_at > NOW() ORDER BY s.starts_at', [$u['id']]);
    $type = (string)input('type', '');
    if ($type !== '') $shifts = array_values(array_filter($shifts, fn($s) => $s['type'] === $type));
    vol_view('planning', ['shifts' => $shifts, 'type' => $type, 'title' => 'Planning des chantiers']);
}

function vol_signup(string $id): void
{
    $u = Auth::requireVolunteer();
    $s = db()->one('SELECT * FROM shifts WHERE id = ?', [(int)$id]);
    if (!$s) abort(404);
    $already = db()->val('SELECT 1 FROM shift_signups WHERE shift_id = ? AND user_id = ?', [$s['id'], $u['id']]);
    if ($already) {
        db()->exec('DELETE FROM shift_signups WHERE shift_id = ? AND user_id = ?', [$s['id'], $u['id']]);
        flash('info', 'Désinscription enregistrée pour « ' . $s['title'] . ' ».');
    } else {
        $n = (int)db()->val('SELECT COUNT(*) FROM shift_signups WHERE shift_id = ?', [$s['id']]);
        db()->exec('INSERT INTO shift_signups (shift_id, user_id) VALUES (?, ?)', [$s['id'], $u['id']]);
        flash('success', 'Merci ! Vous êtes inscrit·e. Un rappel vous sera envoyé la veille.' . ($n >= (int)$s['volunteers_needed'] ? ' (Le créneau était déjà complet : vous êtes en renfort.)' : ''));
    }
    redirect('/benevoles/planning');
}

function vol_shift_ics(string $id): void
{
    Auth::requireVolunteer();
    $s = db()->one('SELECT * FROM shifts WHERE id = ?', [(int)$id]);
    if (!$s) abort(404);
    $fmt = fn($d) => gmdate('Ymd\THis\Z', strtotime($d));
    $esc = fn($t) => str_replace(["\\", ";", ",", "\n"], ["\\\\", "\\;", "\\,", "\\n"], (string)$t);
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="creneau-' . (int)$s['id'] . '.ics"');
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Les Jardins d'a Cote//Benevoles//FR\r\nBEGIN:VEVENT\r\n"
        . 'UID:shift-' . (int)$s['id'] . '@' . parse_url(base_url(), PHP_URL_HOST) . "\r\n"
        . 'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . $fmt($s['starts_at']) . "\r\nDTEND:" . $fmt($s['ends_at']) . "\r\n"
        . 'SUMMARY:' . $esc($s['title']) . "\r\nLOCATION:" . $esc($s['location']) . "\r\nDESCRIPTION:" . $esc($s['description']) . "\r\n"
        . "BEGIN:VALARM\r\nTRIGGER:-PT2H\r\nACTION:DISPLAY\r\nDESCRIPTION:" . $esc($s['title']) . "\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    exit;
}

function vol_workshops(): void
{
    Auth::requireVolunteer();
    $camps = Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status IN ('validated','receiving','packaging','ready_pickup') ORDER BY FIELD(c.status,'packaging','receiving','validated','ready_pickup'), c.closed_at"));
    foreach ($camps as &$c) {
        $c['sheet'] = Campaigns::packingSheet((int)$c['id']);
        $c['total_packets'] = array_sum(array_column($c['sheet'], 'packets'));
        $c['packed_packets'] = array_sum(array_column($c['sheet'], 'packed'));
    }
    vol_view('workshops', ['camps' => $camps, 'title' => 'Atelier ensachage']);
}

function vol_workshop(string $id): void
{
    Auth::requireVolunteer();
    $c = Campaigns::one((int)$id);
    if (!$c || !in_array($c['status'], ['validated', 'receiving', 'packaging', 'ready_pickup', 'closed'], true)) abort(404, 'Cette campagne n\'est pas à préparer.');
    $sheet = Campaigns::packingSheet((int)$id);
    $items = db()->all("SELECT oi.*, o.reference, o.bin_code, o.container_choice, o.notes, o.payment_status, u.first_name, u.last_name
        FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id
        WHERE oi.campaign_id = ? AND oi.status IN ('validated','packed','collected') ORDER BY oi.portion_qty, o.bin_code, u.last_name", [$id]);
    $packs = db()->all('SELECT * FROM product_packs WHERE product_id = ? ORDER BY qty', [$c['product_id']]);
    $po = Campaigns::purchaseOrder([(int)$id]);
    vol_view('workshop', ['c' => $c, 'sheet' => $sheet, 'items' => $items, 'po' => $po['lines'][0] ?? null, 'packs' => $packs, 'title' => 'Atelier : ' . $c['title']]);
}

function vol_workshop_lot(string $id): void
{
    Auth::requireVolunteer();
    $lot = mb_substr(trim((string)input('supplier_lot', '')), 0, 80);
    $year = input_int('packed_year', (int)date('Y'));
    if ($lot === '') { flash('error', 'Le numéro de lot d\'origine est obligatoire (traçabilité des semences).'); redirect('/benevoles/atelier/' . (int)$id); }
    db()->exec("UPDATE campaigns SET supplier_lot = ?, packed_year = ?, status = IF(status IN ('validated','receiving'), 'packaging', status) WHERE id = ?", [$lot, $year, (int)$id]);
    audit('campaign.received', '#' . $id . ' lot ' . $lot . ' — ' . trim((string)input('reception_note', '')));
    flash('success', 'Réception enregistrée. Place à l\'ensachage !');
    redirect('/benevoles/atelier/' . (int)$id);
}

function vol_pack_item(string $itemId): void
{
    $u = Auth::requireVolunteer();
    $it = db()->one('SELECT oi.*, c.supplier_lot FROM order_items oi JOIN campaigns c ON c.id = oi.campaign_id WHERE oi.id = ?', [(int)$itemId]);
    if (!$it) abort(404);
    if (!$it['supplier_lot']) {
        $msg = 'Renseignez d\'abord le numéro de lot d\'origine (réception).';
        if (wants_json()) json_out(['error' => $msg], 422);
        flash('error', $msg); back();
    }
    $packed = input('packed', '1') === '1';
    Orders::markItemPacked((int)$itemId, (int)$u['id'], $packed);
    $ready = $packed && db()->val("SELECT status FROM orders WHERE id = ?", [$it['order_id']]) === 'ready';
    if (!$packed) db()->exec("UPDATE orders SET status = 'confirmed', ready_at = NULL WHERE id = ? AND status = 'ready'", [$it['order_id']]);
    $count = (int)db()->val("SELECT COALESCE(SUM(packets),0) FROM order_items WHERE campaign_id = ? AND status IN ('packed','collected')", [$it['campaign_id']]);
    if (wants_json()) json_out(['ok' => true, 'ready' => $ready, 'packedCount' => $count]);
    back('/benevoles/atelier/' . (int)$it['campaign_id']);
}

function vol_workshop_done(string $id): void
{
    $u = Auth::requireVolunteer();
    $left = (int)db()->val("SELECT COUNT(*) FROM order_items WHERE campaign_id = ? AND status = 'validated'", [(int)$id]);
    if ($left > 0) { flash('error', "Il reste $left " . plural($left, 'ligne', 'lignes') . ' à ensacher.'); redirect('/benevoles/atelier/' . (int)$id); }
    db()->exec("UPDATE campaigns SET status = 'ready_pickup' WHERE id = ? AND status IN ('validated','receiving','packaging')", [(int)$id]);
    foreach (db()->col("SELECT DISTINCT order_id FROM order_items WHERE campaign_id = ?", [(int)$id]) as $oid) Orders::checkReady((int)$oid);
    audit('campaign.packed', '#' . $id);
    flash('success', 'Bravo ! La campagne est prête : les clients dont la commande est complète ont reçu leur Pass Retrait.');
    redirect('/benevoles/atelier');
}

function vol_labels(string $id): void
{
    Auth::requireVolunteer();
    $c = Campaigns::find((int)$id);
    if (!$c) abort(404);
    $mode = input('mode') === 'stock' ? 'stock' : 'buyer';
    $style = input('style') === 'kraft' ? 'kraft' : 'slate';
    $labels = [];
    if ($mode === 'buyer') {
        $rows = db()->all("SELECT oi.portion_qty, oi.packets, o.bin_code, u.first_name, u.last_name FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id
            WHERE oi.campaign_id = ? AND oi.status IN ('validated','packed') ORDER BY o.bin_code, u.last_name, oi.portion_qty", [$id]);
        foreach ($rows as $r) for ($i = 0; $i < (int)$r['packets']; $i++) {
            $labels[] = ['portion' => (float)$r['portion_qty'], 'buyer' => $r['bin_code'] . ' · ' . $r['first_name'] . ' ' . mb_substr((string)$r['last_name'], 0, 1) . '.'];
        }
    } else {
        $n = max(1, min(240, input_int('n', 24)));
        $portion = input_dec('portion', (float)$c['ref_portion']);
        for ($i = 0; $i < $n; $i++) $labels[] = ['portion' => $portion, 'buyer' => ''];
    }
    $qr = QR::svg(abs_url('/c/' . $c['id']), 60, $style === 'kraft' ? '#232B2D' : '#F8FAFC', $style === 'kraft' ? '#E6D5B8' : '#232B2D');
    view('volunteer/labels', ['c' => $c, 'labels' => $labels, 'qr' => $qr, 'style' => $style, 'mode' => $mode, 'title' => 'Étiquettes — ' . $c['title'],
        'toolbar' => '<a class="btn btn--chalk btn--sm" href="?mode=' . $mode . '&style=' . ($style === 'kraft' ? 'slate' : 'kraft') . '">Style ' . ($style === 'kraft' ? 'ardoise' : 'kraft') . '</a>'], 'layouts/print');
}

function vol_distribution(): void
{
    Auth::requireVolunteer();
    $q = trim((string)input('q', ''));
    $results = [];
    if ($q !== '') {
        if (preg_match('#/r/([a-f0-9]{32})#', $q, $m) || preg_match('/^[a-f0-9]{32}$/', $q)) {
            redirect('/benevoles/distribution/' . ($m[1] ?? $q));
        }
        $like = '%' . $q . '%';
        $results = db()->all("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.id = o.user_id
            WHERE o.status IN ('ready','confirmed','collected') AND (o.pickup_code = ? OR o.reference LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ? OR u.email LIKE ?)
            ORDER BY FIELD(o.status,'ready','confirmed','collected'), u.last_name LIMIT 30", [strtoupper($q), $like, $like, $like, $like]);
        if (count($results) === 1 && strtoupper($q) === $results[0]['pickup_code']) redirect('/benevoles/distribution/' . $results[0]['pickup_token']);
    }
    $today = db()->all("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.id = o.user_id WHERE o.status = 'ready' ORDER BY o.bin_code, u.last_name LIMIT 200");
    $collectedToday = (int)db()->val('SELECT COUNT(*) FROM orders WHERE DATE(collected_at) = CURDATE()');
    vol_view('distribution', ['q' => $q, 'results' => $results, 'today' => $today, 'collectedToday' => $collectedToday, 'title' => 'Distribution',
        'scripts' => '<script src="' . asset('vendor/html5-qrcode.min.js') . '" defer></script><script src="' . asset('js/scan.js') . '" defer></script>']);
}

function vol_handover(string $token): void
{
    Auth::requireVolunteer();
    $o = Orders::findByToken($token);
    if (!$o) { flash('error', 'QR code inconnu. Essayez avec le code à 6 caractères ou le nom.'); redirect('/benevoles/distribution'); }
    $items = Orders::items((int)$o['id']);
    vol_view('handover', ['o' => $o, 'items' => $items, 'title' => 'Remise ' . $o['reference']]);
}

function vol_handover_confirm(string $token): void
{
    $u = Auth::requireVolunteer();
    $o = Orders::findByToken($token);
    if (!$o) abort(404);
    if ($o['status'] === 'collected') { flash('info', 'Cette commande avait déjà été remise.'); redirect('/benevoles/distribution'); }
    if (!in_array($o['status'], ['ready', 'confirmed'], true)) { flash('error', 'Cette commande n\'est pas remettable (statut : ' . Orders::STATUS_LABELS[$o['status']] . ').'); redirect('/benevoles/distribution/' . $token); }
    if (in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true) && !input('paid_on_site')) {
        flash('error', 'Confirmez l\'encaissement de ' . money($o['amount_final']) . ' avant la remise.');
        redirect('/benevoles/distribution/' . $token);
    }
    Orders::collect((int)$o['id'], (int)$u['id'], (bool)input('paid_on_site'));
    flash('success', 'Remise validée pour ' . $o['first_name'] . ' (' . $o['reference'] . '). Bonne récolte !');
    redirect('/benevoles/distribution');
}
