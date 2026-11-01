<?php
declare(strict_types=1);

function vol_view(string $tpl, array $data = []): void
{
    view('volunteer/' . $tpl, $data + ['head' => '<meta name="robots" content="noindex">']);
}

/** Ventes visibles par ce bénévole : celles de ses groupes (toutes pour un admin). */
function vol_sales(array $u, array $statuses = ['open', 'closed']): array
{
    $in = implode(',', array_map(fn($s) => db()->pdo->quote($s), $statuses));
    $sql = "SELECT s.id FROM sales s WHERE s.status IN ($in)" . (Auth::isAdmin() ? '' : " AND s.group_id IN (SELECT group_id FROM group_members WHERE user_id = " . (int)$u['id'] . " AND role = 'volunteer')");
    $out = [];
    foreach (db()->col($sql . ' ORDER BY s.status, s.id DESC') as $id) {
        $s = Sales::one((int)$id);
        $s['to_prepare'] = (int)db()->val("SELECT COUNT(*) FROM orders WHERE sale_id = ? AND status = 'confirmed'", [$id]);
        $s['ready'] = (int)db()->val("SELECT COUNT(*) FROM orders WHERE sale_id = ? AND status = 'ready'", [$id]);
        $s['collected'] = (int)db()->val("SELECT COUNT(*) FROM orders WHERE sale_id = ? AND status = 'collected'", [$id]);
        if ($s['to_prepare'] + $s['ready'] + $s['collected'] > 0 || $s['status'] === 'open') $out[] = $s;
    }
    return $out;
}

function vol_can_access_sale(array $u, array $s): bool
{
    return Auth::isAdmin() || Groups::role((int)$s['group_id'], (int)$u['id']) === 'volunteer';
}

function vol_home(): void
{
    $u = Auth::requireVolunteer();
    $sales = vol_sales($u);
    $mine = db()->all('SELECT s.* FROM shifts s JOIN shift_signups ss ON ss.shift_id = s.id WHERE ss.user_id = ? AND s.ends_at > NOW() ORDER BY s.starts_at LIMIT 3', [$u['id']]);
    $ready = (int)db()->val("SELECT COUNT(*) FROM orders WHERE status = 'ready'");
    vol_view('home', ['u' => $u, 'sales' => $sales, 'mine' => $mine, 'ready' => $ready, 'title' => 'Espace bénévoles']);
}

function vol_planning(): void
{
    $u = Auth::requireVolunteer();
    $shifts = db()->all('SELECT s.*, g.name AS group_name, (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) AS signed,
            EXISTS(SELECT 1 FROM shift_signups ss WHERE ss.shift_id = s.id AND ss.user_id = ?) AS mine,
            (SELECT GROUP_CONCAT(u.first_name SEPARATOR ", ") FROM shift_signups ss JOIN users u ON u.id = ss.user_id WHERE ss.shift_id = s.id) AS names
        FROM shifts s LEFT JOIN `groups` g ON g.id = s.group_id WHERE s.ends_at > NOW() ORDER BY s.starts_at', [$u['id']]);
    vol_view('planning', ['shifts' => $shifts, 'title' => 'Planning']);
}

function vol_signup(string $id): void
{
    $u = Auth::requireVolunteer();
    $s = db()->one('SELECT * FROM shifts WHERE id = ?', [(int)$id]);
    if (!$s) abort(404);
    if (db()->val('SELECT 1 FROM shift_signups WHERE shift_id = ? AND user_id = ?', [$s['id'], $u['id']])) {
        db()->exec('DELETE FROM shift_signups WHERE shift_id = ? AND user_id = ?', [$s['id'], $u['id']]);
        flash('info', 'Désinscription enregistrée.');
    } else {
        db()->exec('INSERT INTO shift_signups (shift_id, user_id) VALUES (?, ?)', [$s['id'], $u['id']]);
        flash('success', 'Merci ! Vous êtes inscrit·e. Un rappel vous sera envoyé la veille.');
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
        . 'UID:shift-' . (int)$s['id'] . '@' . parse_url(base_url(), PHP_URL_HOST) . "\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . $fmt($s['starts_at']) . "\r\nDTEND:" . $fmt($s['ends_at'])
        . "\r\nSUMMARY:" . $esc($s['title']) . "\r\nLOCATION:" . $esc($s['location']) . "\r\nDESCRIPTION:" . $esc($s['description']) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    exit;
}

function vol_prep(string $saleId): void
{
    $u = Auth::requireVolunteer();
    $s = Sales::one((int)$saleId);
    if (!$s || !vol_can_access_sale($u, $s)) abort(404);
    $orders = db()->all("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.id = o.user_id
        WHERE o.sale_id = ? AND o.status IN ('confirmed','ready','collected') ORDER BY o.batch_no, o.bin_code, u.last_name", [$s['id']]);
    vol_view('prep', ['s' => $s, 'orders' => $orders, 'needs' => Sales::needs((int)$s['id']), 'title' => 'Préparation — ' . $s['group_name']]);
}

function vol_prep_toggle(string $orderId): void
{
    $u = Auth::requireVolunteer();
    $o = Orders::find((int)$orderId);
    if (!$o) abort(404);
    $s = Sales::find((int)$o['sale_id']);
    if (!vol_can_access_sale($u, $s)) abort(403);
    if (input('ready') === '1') Orders::markReady((int)$o['id'], (int)$u['id']); else Orders::unmarkReady((int)$o['id']);
    if (wants_json()) json_out(['ok' => true, 'status' => db()->val('SELECT status FROM orders WHERE id = ?', [$o['id']])]);
    redirect('/benevoles/preparation/' . $o['sale_id']);
}

function vol_labels(string $saleId): void
{
    $u = Auth::requireVolunteer();
    $s = Sales::one((int)$saleId);
    if (!$s || !vol_can_access_sale($u, $s)) abort(404);
    $orders = db()->all("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.id = o.user_id
        WHERE o.sale_id = ? AND o.status IN ('confirmed','ready') ORDER BY o.bin_code, u.last_name", [$s['id']]);
    $labels = [];
    foreach ($orders as $o) {
        $items = db()->all('SELECT * FROM pack_items WHERE pack_id = ? ORDER BY position', [$o['pack_id']]);
        for ($k = 0; $k < (int)$o['quantity']; $k++) foreach ($items as $it) {
            $labels[] = ['it' => $it, 'buyer' => $o['bin_code'] . ' · ' . $o['first_name'] . ' ' . mb_substr((string)$o['last_name'], 0, 1) . '.', 'token' => $o['pickup_token']];
        }
    }
    view('volunteer/labels', ['s' => $s, 'labels' => $labels, 'lot' => trim((string)input('lot', '')), 'title' => 'Étiquettes — ' . $s['group_name']], 'layouts/print');
}

function vol_distribution(): void
{
    Auth::requireVolunteer();
    $q = trim((string)input('q', ''));
    $results = [];
    if ($q !== '') {
        if (preg_match('#/r/([a-f0-9]{32})#', $q, $m) || preg_match('/^[a-f0-9]{32}$/', $q)) redirect('/benevoles/distribution/' . ($m[1] ?? $q));
        $like = '%' . $q . '%';
        $results = db()->all("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON u.id = o.user_id
            WHERE o.status IN ('ready','confirmed','collected') AND (o.pickup_code = ? OR o.reference LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ? OR u.email LIKE ?)
            ORDER BY FIELD(o.status,'ready','confirmed','collected'), u.last_name LIMIT 30", [strtoupper($q), $like, $like, $like, $like]);
        if (count($results) === 1 && strtoupper($q) === $results[0]['pickup_code']) redirect('/benevoles/distribution/' . $results[0]['pickup_token']);
    }
    $today = db()->all("SELECT o.*, u.first_name, u.last_name, g.name AS group_name FROM orders o JOIN users u ON u.id = o.user_id JOIN sales s ON s.id = o.sale_id JOIN `groups` g ON g.id = s.group_id
        WHERE o.status = 'ready' ORDER BY g.name, o.bin_code, u.last_name LIMIT 300");
    vol_view('distribution', ['q' => $q, 'results' => $results, 'today' => $today, 'collectedToday' => (int)db()->val('SELECT COUNT(*) FROM orders WHERE DATE(collected_at) = CURDATE()'),
        'title' => 'Distribution', 'scripts' => '<script src="' . asset('vendor/html5-qrcode.min.js') . '" defer></script><script src="' . asset('js/scan.js') . '" defer></script>']);
}

function vol_handover(string $token): void
{
    Auth::requireVolunteer();
    $o = Orders::findByToken($token);
    if (!$o) { flash('error', 'QR code inconnu. Essayez avec le code à 6 caractères ou le nom.'); redirect('/benevoles/distribution'); }
    vol_view('handover', ['o' => $o, 'items' => Orders::items($o), 'title' => 'Remise ' . $o['reference']]);
}

function vol_handover_confirm(string $token): void
{
    $u = Auth::requireVolunteer();
    $o = Orders::findByToken($token);
    if (!$o) abort(404);
    if ($o['status'] === 'collected') { flash('info', 'Ce pack avait déjà été remis.'); redirect('/benevoles/distribution'); }
    if (!in_array($o['status'], ['ready', 'confirmed'], true)) { flash('error', 'Cette commande ne peut pas être remise : ' . Orders::STATUS_LABELS[$o['status']] . '.'); redirect('/benevoles/distribution/' . $token); }
    if (in_array($o['payment_status'], ['to_collect', 'failed', 'action_required'], true) && !input('paid_on_site')) {
        flash('error', 'Confirmez l\'encaissement de ' . money($o['amount']) . ' avant la remise.');
        redirect('/benevoles/distribution/' . $token);
    }
    Orders::collect((int)$o['id'], (int)$u['id'], (bool)input('paid_on_site'));
    flash('success', 'Remise validée pour ' . $o['first_name'] . ' (' . $o['reference'] . '). Bonne saison !');
    redirect('/benevoles/distribution');
}
