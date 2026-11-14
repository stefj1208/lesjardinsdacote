<?php
declare(strict_types=1);

function admin_view(string $tpl, array $data = []): void
{
    $data += ['head' => '<meta name="robots" content="noindex">', 'bodyClass' => 'admin'];
    $inner = render('admin/' . $tpl, $data);
    echo render('layouts/main', $data + ['content' => render('admin/_shell', $data + ['inner' => $inner])]);
}

/** Photo envoyée depuis un formulaire : renvoie le chemin public ou null. */
function admin_upload(string $field, string $prefix): ?string
{
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return null;
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
    if (!$ext || $_FILES[$field]['size'] > 8 * 1024 * 1024) { flash('error', 'Photo refusée : JPG, PNG ou WebP, 8 Mo maximum.'); return null; }
    $dir = PUBLIC_DIR . '/uploads/photos';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = slugify($prefix) . '-' . substr(random_token(4), 0, 6) . '.' . $ext;
    return move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name) ? '/uploads/photos/' . $name : null;
}

/** Fichier d'un tableau de champs (photos des variétés). */
function admin_upload_multi(string $field, int $i, string $prefix): ?string
{
    if (empty($_FILES[$field]['tmp_name'][$i]) || !is_uploaded_file($_FILES[$field]['tmp_name'][$i])) return null;
    $_FILES['__one'] = ['tmp_name' => $_FILES[$field]['tmp_name'][$i], 'size' => $_FILES[$field]['size'][$i], 'name' => $_FILES[$field]['name'][$i]];
    return admin_upload('__one', $prefix);
}

function csv_out(string $filename, array $head, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $head, ';');
    foreach ($rows as $r) fputcsv($out, $r, ';');
    fclose($out);
    exit;
}

/* ------------------------------------------------------------------ */
function admin_dashboard(): void
{
    Auth::requireAdmin();
    $groups = Groups::hydrate(db()->all("SELECT * FROM `groups` WHERE status = 'active' ORDER BY commune"));
    $k = [
        'groups' => count($groups),
        'proposed' => (int)db()->val("SELECT COUNT(*) FROM `groups` WHERE status = 'proposed'"),
        'members' => (int)db()->val("SELECT COUNT(DISTINCT user_id) FROM group_members"),
        'voting' => (int)db()->val("SELECT COUNT(*) FROM sales WHERE status = 'voting'"),
        'open' => (int)db()->val("SELECT COUNT(*) FROM sales WHERE status = 'open'"),
        'pending' => (int)db()->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE status = 'pending'"),
        'sold' => (int)db()->val("SELECT COALESCE(SUM(quantity),0) FROM orders WHERE status IN ('confirmed','ready','collected')"),
        'to_prepare' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'"),
        'paid' => (float)db()->val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status = 'paid'"),
        'to_collect' => (float)db()->val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status = 'to_collect'"),
        'pay_issues' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE payment_status IN ('failed','action_required')"),
        'candidates' => (int)db()->val("SELECT COUNT(*) FROM users WHERE wants_volunteer = 1 AND role = 'client'"),
        'mail_failed' => (int)db()->val("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'"),
    ];
    admin_view('dashboard', ['title' => 'Tableau de bord', 'k' => $k, 'groups' => $groups, 'lastCron' => setting('last_cron_at'), 'section' => 'dashboard']);
}

/* ---------------------------- Groupes ----------------------------- */
function admin_groups(): void
{
    Auth::requireAdmin();
    $st = in_array(input('statut'), ['proposed', 'active', 'archived'], true) ? (string)input('statut') : '';
    $rows = db()->all('SELECT * FROM `groups`' . ($st ? ' WHERE status = ' . db()->pdo->quote($st) : '') . " ORDER BY FIELD(status,'proposed','active','archived'), commune");
    $counts = [];
    foreach (db()->all('SELECT status, COUNT(*) n FROM `groups` GROUP BY status') as $r) $counts[$r['status']] = (int)$r['n'];
    admin_view('groups', ['title' => 'Groupes', 'groups' => Groups::hydrate($rows), 'st' => $st, 'counts' => $counts, 'section' => 'groups']);
}

function admin_group_form(?string $id = null): void
{
    Auth::requireAdmin();
    $g = $id ? Groups::one((int)$id) : null;
    if ($id && !$g) abort(404);
    $g ??= ['id' => null, 'name' => '', 'commune' => '', 'postal_code' => '', 'organizer_type' => 'collectivite', 'organizer_name' => '', 'contact_name' => '', 'contact_email' => '',
        'contact_phone' => '', 'pickup_place' => '', 'pickup_address' => '', 'photo' => '', 'description' => '', 'volunteers_needed' => 2, 'status' => 'active', 'slug' => ''];
    $members = $g['id'] ? db()->all('SELECT u.*, gm.role AS gm_role, gm.created_at AS joined FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY gm.role DESC, u.last_name', [$g['id']]) : [];
    $sales = $g['id'] ? db()->all('SELECT * FROM sales WHERE group_id = ? ORDER BY id DESC', [$g['id']]) : [];
    admin_view('group_form', ['title' => $g['id'] ? $g['name'] : 'Nouveau groupe', 'g' => $g, 'members' => $members, 'sales' => $sales, 'section' => 'groups']);
}

function admin_group_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $commune = mb_substr(trim((string)input('commune', '')), 0, 120);
    if ($commune === '') { flash('error', 'La commune est obligatoire.'); back('/admin/groupes'); }
    $name = trim((string)input('name', '')) ?: Groups::defaultName($commune);
    $data = [
        'name' => mb_substr($name, 0, 160), 'commune' => $commune, 'postal_code' => mb_substr((string)input('postal_code', ''), 0, 10),
        'organizer_type' => array_key_exists((string)input('organizer_type'), Cat::ORGANIZERS) ? (string)input('organizer_type') : 'autre',
        'organizer_name' => mb_substr((string)input('organizer_name', ''), 0, 160) ?: null, 'contact_name' => mb_substr((string)input('contact_name', ''), 0, 120) ?: null,
        'contact_email' => mb_substr((string)input('contact_email', ''), 0, 190) ?: null, 'contact_phone' => mb_substr((string)input('contact_phone', ''), 0, 30) ?: null,
        'pickup_place' => mb_substr((string)input('pickup_place', ''), 0, 160) ?: null, 'pickup_address' => mb_substr((string)input('pickup_address', ''), 0, 255) ?: null,
        'description' => (string)input('description', '') ?: null, 'volunteers_needed' => max(1, min(10, input_int('volunteers_needed', 2))),
        'photo' => trim((string)input('photo', '')) ?: null,
    ];
    if ($up = admin_upload('photo_file', $commune)) $data['photo'] = $up;
    if ($id) {
        db()->update('groups', $data, 'id = ?', [$id]);
    } else {
        $data += ['slug' => Groups::uniqueSlug($name), 'status' => 'active', 'validated_at' => now()];
        $id = db()->insert('groups', $data);
    }
    Sales::checkGroup($id);
    flash('success', 'Groupe enregistré.');
    redirect('/admin/groupes/' . $id);
}

function admin_group_action(string $id): void
{
    Auth::requireAdmin();
    $g = Groups::find((int)$id);
    if (!$g) abort(404);
    switch ((string)input('action')) {
        case 'validate':
            db()->exec("UPDATE `groups` SET status = 'active', validated_at = NOW() WHERE id = ?", [$g['id']]);
            if ($g['contact_email']) Mailer::queue($g['contact_email'], 'Votre groupe est en ligne : ' . $g['name'], Mailer::layout('Votre groupe est en ligne',
                '<p>Bonjour ' . e($g['contact_name']) . ',</p><p><strong>' . e($g['name']) . '</strong> est publié. Prochaine étape : trouver ' . (int)$g['volunteers_needed'] . ' bénévoles. Partagez la page du groupe autour de vous !</p>',
                'Voir la page du groupe', abs_url('/groupes/' . $g['slug'])));
            Sales::checkGroup((int)$g['id']);
            flash('success', 'Groupe validé et publié.'); break;
        case 'archive':
            db()->exec("UPDATE `groups` SET status = 'archived' WHERE id = ?", [$g['id']]); flash('success', 'Groupe archivé.'); break;
        case 'reactivate':
            db()->exec("UPDATE `groups` SET status = 'active' WHERE id = ?", [$g['id']]); flash('success', 'Groupe réactivé.'); break;
        case 'delete':
            if (db()->val('SELECT COUNT(*) FROM orders o JOIN sales s ON s.id = o.sale_id WHERE s.group_id = ?', [$g['id']])) { flash('error', 'Des commandes existent : archivez plutôt le groupe.'); break; }
            db()->exec('DELETE FROM `groups` WHERE id = ?', [$g['id']]); flash('success', 'Groupe supprimé.'); redirect('/admin/groupes');
        case 'member_role':
            $uid = input_int('user_id');
            if (input('role') === 'remove') db()->exec('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$g['id'], $uid]);
            else db()->exec('UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?', [input('role') === 'volunteer' ? 'volunteer' : 'member', $g['id'], $uid]);
            Sales::checkGroup((int)$g['id']);
            flash('success', 'Membre mis à jour.'); break;
    }
    audit('group.' . input('action'), '#' . $g['id']);
    redirect('/admin/groupes/' . $g['id']);
}

/* ----------------------------- Ventes ----------------------------- */
function admin_sales(): void
{
    Auth::requireAdmin();
    $rows = array_map(fn($s) => Sales::hydrate($s), db()->all("SELECT s.*, g.name AS group_name, g.slug AS group_slug, g.pickup_place AS group_pickup_place, g.pickup_address AS group_pickup_address
        FROM sales s JOIN `groups` g ON g.id = s.group_id ORDER BY FIELD(s.status,'open','voting','waiting','closed','cancelled'), s.id DESC"));
    admin_view('sales', ['title' => 'Ventes', 'sales' => $rows, 'section' => 'sales']);
}

function admin_sale_form(?string $id = null): void
{
    Auth::requireAdmin();
    $s = $id ? Sales::one((int)$id) : null;
    if ($id && !$s) abort(404);
    $s ??= ['id' => null, 'group_id' => input_int('groupe'), 'category' => 'seeds', 'title' => 'Graines potagères — printemps ' . (date('n') > 6 ? date('Y') + 1 : date('Y')), 'status' => 'waiting',
        'vote_days' => (int)setting('default_vote_days', 10), 'sale_days' => (int)setting('default_sale_days', 21), 'batch_size' => (int)setting('default_batch_size', 10),
        'max_per_order' => 3, 'pickup_at' => null, 'pickup_place' => '', 'pickup_address' => '', 'notes' => '', 'packs' => [], 'winner' => null,
        'price' => (float)setting('default_pack_price', 30), 'max_choices' => 10, 'max_varieties' => null, 'varieties' => [], 'projection' => null, 'voters' => 0, 'votes_total' => 0];
    admin_view('sale_form', ['title' => $s['id'] ? 'Vente : ' . $s['title'] : 'Nouvelle vente', 's' => $s, 'groups' => db()->all("SELECT id, name FROM `groups` WHERE status <> 'archived' ORDER BY commune"),
        'catalog' => Varieties::all(null, true), 'section' => 'sales']);
}

function admin_sale_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $vids = array_values(array_filter(array_map('intval', (array)($_POST['varieties'] ?? []))));
    $data = [
        'group_id' => input_int('group_id'), 'category' => array_key_exists((string)input('category'), Cat::CATEGORIES) ? (string)input('category') : 'seeds',
        'title' => mb_substr(trim((string)input('title', '')), 0, 160), 'vote_days' => max(1, input_int('vote_days', 10)), 'sale_days' => max(1, input_int('sale_days', 21)),
        'batch_size' => max(1, min(100, input_int('batch_size', 10))), 'max_per_order' => max(1, min(10, input_int('max_per_order', 3))),
        'pickup_at' => input('pickup_at') ? date('Y-m-d H:i:s', strtotime((string)input('pickup_at'))) : null,
        'pickup_place' => mb_substr((string)input('pickup_place', ''), 0, 160) ?: null, 'pickup_address' => mb_substr((string)input('pickup_address', ''), 0, 255) ?: null,
        'notes' => (string)input('notes', '') ?: null,
        'price' => max(1, (float)input_dec('price', 30)), 'max_choices' => max(1, min(50, input_int('max_choices', 10))),
        'max_varieties' => input_int('max_varieties') ?: null,
    ];
    if (!$data['group_id'] || $data['title'] === '') { flash('error', 'Groupe et titre obligatoires.'); back('/admin/ventes'); }
    if (count($vids) < 2) { flash('error', 'Cochez au moins 2 variétés à soumettre au vote.'); back('/admin/ventes'); }
    if ($id) {
        $cur = Sales::find($id);
        if (!in_array($cur['status'], ['waiting', 'voting'], true)) unset($data['price'], $data['max_varieties']);
        db()->update('sales', $data, 'id = ?', [$id]);
        if (in_array($cur['status'], ['waiting', 'voting'], true)) Varieties::setForSale($id, $vids);
    } else {
        $id = db()->insert('sales', $data);
        Varieties::setForSale($id, $vids);
    }
    Sales::checkGroup((int)$data['group_id']);
    flash('success', 'Vente enregistrée.' . (Sales::find($id)['status'] === 'voting' ? ' Le groupe a ses bénévoles : le vote est ouvert.' : ''));
    redirect('/admin/ventes/' . $id);
}

function admin_sale_action(string $id): void
{
    Auth::requireAdmin();
    $s = Sales::find((int)$id);
    if (!$s) abort(404);
    switch ((string)input('action')) {
        case 'start_vote':
            flash(Sales::startVoting((int)$id) ? 'success' : 'error', Sales::find((int)$id)['status'] === 'voting' ? 'Vote ouvert, les membres sont prévenus.' : 'Impossible : il faut au moins 2 variétés.'); break;
        case 'open':
            if ($s['status'] === 'waiting') Sales::startVoting((int)$id);
            $ok = Sales::open((int)$id);
            flash($ok ? 'success' : 'error', $ok ? 'Ventes ouvertes : le pack composé par le vote est dans le panier des votants.' : 'Impossible de composer le pack : vérifiez les variétés et leurs prix d\'achat.'); break;
        case 'close':
            flash(Sales::close((int)$id) ? 'success' : 'error', 'Vente clôturée. Le lot incomplet éventuel est annulé sans débit.'); break;
        case 'extend':
            db()->exec("UPDATE sales SET vote_ends_at = IF(status = 'voting', DATE_ADD(vote_ends_at, INTERVAL 3 DAY), vote_ends_at), sale_ends_at = IF(status = 'open', DATE_ADD(sale_ends_at, INTERVAL 7 DAY), sale_ends_at) WHERE id = ?", [$id]);
            flash('success', $s['status'] === 'voting' ? 'Vote prolongé de 3 jours.' : 'Vente prolongée de 7 jours.'); break;
        case 'cancel':
            if (db()->val("SELECT COUNT(*) FROM orders WHERE sale_id = ? AND status IN ('confirmed','ready','collected')", [$id])) { flash('error', 'Des lots sont déjà commandés : clôturez plutôt la vente.'); break; }
            db()->exec("UPDATE orders SET status = 'cancelled', payment_status = 'not_required' WHERE sale_id = ? AND status = 'pending'", [$id]);
            db()->exec("UPDATE sales SET status = 'cancelled', closed_at = NOW() WHERE id = ?", [$id]);
            db()->exec('DELETE FROM cart_items WHERE sale_id = ?', [$id]);
            flash('success', 'Vente annulée.'); break;
        case 'delete':
            if (db()->val('SELECT COUNT(*) FROM orders WHERE sale_id = ?', [$id])) { flash('error', 'Des commandes existent.'); break; }
            db()->exec('DELETE FROM sales WHERE id = ?', [$id]);
            flash('success', 'Vente supprimée.'); redirect('/admin/ventes');
    }
    audit('sale.' . input('action'), '#' . $id);
    redirect('/admin/ventes/' . $id);
}

function admin_sale_csv(string $id): void
{
    Auth::requireAdmin();
    $s = Sales::find((int)$id);
    if (!$s) abort(404);
    $rows = db()->all('SELECT o.*, u.last_name, u.first_name, u.email, u.phone, p.name AS pack FROM orders o JOIN users u ON u.id = o.user_id JOIN packs p ON p.id = o.pack_id WHERE o.sale_id = ? ORDER BY o.batch_no, u.last_name', [$id]);
    csv_out('acheteurs-' . slugify($s['group_name']) . '.csv', ['Référence', 'Lot', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Pack', 'Quantité', 'Montant', 'Statut', 'Paiement', 'Casier'],
        array_map(fn($o) => [$o['reference'], $o['batch_no'], $o['last_name'], $o['first_name'], $o['email'], $o['phone'], $o['pack'], $o['quantity'], num($o['amount'], 2), $o['status'], $o['payment_status'], $o['bin_code']], $rows));
}

/* ------------------------------ Packs ------------------------------ */
/* ----------------------------- Variétés ----------------------------- */
function admin_varieties(): void
{
    Auth::requireAdmin();
    $cat = array_key_exists((string)input('univers'), Cat::CATEGORIES) ? (string)input('univers') : null;
    admin_view('varieties', ['title' => 'Variétés', 'varieties' => Varieties::all($cat), 'cat' => $cat, 'section' => 'varieties']);
}

function admin_variety_form(?string $id = null): void
{
    Auth::requireAdmin();
    $v = $id ? Varieties::find((int)$id) : null;
    if ($id && !$v) abort(404);
    $cat = array_key_exists((string)input('univers'), Cat::CATEGORIES) ? (string)input('univers') : 'seeds';
    $v ??= Varieties::decorate(['id' => null, 'category' => $cat, 'name' => '', 'variety' => '', 'latin_name' => '', 'note' => '', 'photo' => '', 'portion_qty' => $cat === 'aromatics' ? 0.5 : 10,
        'unit' => $cat === 'aromatics' ? 'g' : 'graines', 'purchase_price' => null, 'purchase_qty' => 100, 'market_price' => 3.30, 'supplier' => 'Agrosemens', 'supplier_ref' => '', 'position' => 0, 'is_active' => 1]);
    admin_view('variety_form', ['title' => $v['id'] ? 'Variété : ' . $v['name'] . ' ' . $v['variety'] : 'Nouvelle variété', 'v' => $v, 'section' => 'varieties']);
}

function admin_variety_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $name = mb_substr(trim((string)input('name', '')), 0, 120);
    if ($name === '') { flash('error', 'Le nom de la plante est obligatoire.'); back('/admin/varietes'); }
    $data = ['category' => array_key_exists((string)input('category'), Cat::CATEGORIES) ? (string)input('category') : 'seeds', 'name' => $name,
        'variety' => mb_substr(trim((string)input('variety', '')), 0, 120) ?: null, 'latin_name' => mb_substr(trim((string)input('latin_name', '')), 0, 160) ?: null,
        'note' => mb_substr(trim((string)input('note', '')), 0, 255) ?: null, 'photo' => trim((string)input('photo', '')) ?: null,
        'portion_qty' => admin_dec(input('portion_qty')) ?: 10, 'unit' => array_key_exists((string)input('unit'), Cat::UNITS) ? (string)input('unit') : 'graines',
        'purchase_price' => admin_dec(input('purchase_price')), 'purchase_qty' => admin_dec(input('purchase_qty')), 'market_price' => admin_dec(input('market_price')),
        'supplier' => mb_substr(trim((string)input('supplier', '')), 0, 120) ?: null, 'supplier_ref' => mb_substr(trim((string)input('supplier_ref', '')), 0, 60) ?: null,
        'position' => input_int('position'), 'is_active' => input('is_active') ? 1 : 0];
    if ($up = admin_upload('photo_file', $name . ' ' . $data['variety'])) $data['photo'] = $up;
    if ($id) db()->update('varieties', $data, 'id = ?', [$id]);
    else $id = db()->insert('varieties', $data);
    flash('success', 'Variété enregistrée.');
    redirect(input('next') === 'new' ? '/admin/varietes/nouvelle?univers=' . $data['category'] : '/admin/varietes?univers=' . $data['category']);
}

function admin_variety_delete(string $id): void
{
    Auth::requireAdmin();
    if (db()->val('SELECT COUNT(*) FROM sale_varieties sv JOIN sales s ON s.id = sv.sale_id WHERE sv.variety_id = ? AND s.status IN (\'waiting\',\'voting\')', [(int)$id])) {
        db()->exec('UPDATE varieties SET is_active = 0 WHERE id = ?', [(int)$id]);
        flash('info', 'Cette variété est proposée dans un vote en cours : elle a été désactivée pour les prochaines ventes.');
    } else {
        db()->exec('DELETE FROM varieties WHERE id = ?', [(int)$id]);
        flash('success', 'Variété supprimée.');
    }
    redirect('/admin/varietes');
}

function admin_packs(): void
{
    Auth::requireAdmin();
    $cat = array_key_exists((string)input('univers'), Cat::CATEGORIES) ? (string)input('univers') : null;
    admin_view('packs', ['title' => 'Packs', 'packs' => Packs::all($cat), 'cat' => $cat, 'section' => 'packs']);
}

function admin_pack_form(?string $id = null): void
{
    Auth::requireAdmin();
    $p = $id ? Packs::find((int)$id) : null;
    if ($id && !$p) abort(404);
    $p ??= ['id' => null, 'category' => (string)input('univers', 'seeds'), 'name' => '', 'tagline' => '', 'description' => '', 'price' => (float)setting('default_pack_price', 30), 'photo' => '', 'is_active' => 1, 'items' => []];
    admin_view('pack_form', ['title' => $p['id'] ? $p['name'] : 'Nouveau pack', 'p' => $p, 'section' => 'packs']);
}

function admin_pack_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $name = mb_substr(trim((string)input('name', '')), 0, 160);
    if ($name === '') { flash('error', 'Le nom du pack est obligatoire.'); back('/admin/packs'); }
    $data = ['category' => array_key_exists((string)input('category'), Cat::CATEGORIES) ? (string)input('category') : 'seeds', 'name' => $name,
        'tagline' => mb_substr((string)input('tagline', ''), 0, 255) ?: null, 'description' => (string)input('description', '') ?: null,
        'price' => max(0, (float)input_dec('price', 30)), 'photo' => trim((string)input('photo', '')) ?: null, 'is_active' => input('is_active') ? 1 : 0];
    if ($up = admin_upload('photo_file', $name)) $data['photo'] = $up;
    db()->tx(function (DB $db) use (&$id, $data, $name) {
        if ($id) $db->update('packs', $data, 'id = ?', [$id]);
        else $id = $db->insert('packs', $data + ['slug' => Packs::uniqueSlug($name)]);
        $db->exec('DELETE FROM pack_items WHERE pack_id = ?', [$id]);
        $names = (array)($_POST['item_name'] ?? []);
        foreach ($names as $i => $n) {
            $n = trim((string)$n);
            if ($n === '') continue;
            $photo = trim((string)($_POST['item_photo'][$i] ?? '')) ?: null;
            if ($up = admin_upload_multi('item_file', (int)$i, $n)) $photo = $up;
            $db->insert('pack_items', ['pack_id' => $id, 'position' => (int)$i, 'name' => mb_substr($n, 0, 120),
                'variety' => mb_substr(trim((string)($_POST['item_variety'][$i] ?? '')), 0, 120) ?: null,
                'latin_name' => mb_substr(trim((string)($_POST['item_latin'][$i] ?? '')), 0, 160) ?: null,
                'quantity' => max(0.01, admin_dec($_POST['item_qty'][$i] ?? null) ?? 1),
                'unit' => array_key_exists((string)($_POST['item_unit'][$i] ?? ''), Cat::UNITS) ? (string)$_POST['item_unit'][$i] : 'graines',
                'market_price' => admin_dec($_POST['item_market'][$i] ?? null),
                'note' => mb_substr(trim((string)($_POST['item_note'][$i] ?? '')), 0, 255) ?: null, 'photo' => $photo]);
        }
    });
    flash('success', 'Pack enregistré.');
    redirect('/admin/packs/' . $id);
}

/** Montant saisi (« 3,30 ») ou null si vide. */
function admin_dec($v): ?float
{
    $v = str_replace([' ', "\u{202F}", "\u{00A0}", ',', '€'], ['', '', '', '.', ''], trim((string)$v));
    return $v !== '' && is_numeric($v) && (float)$v >= 0 ? round((float)$v, 2) : null;
}

function admin_pack_delete(string $id): void
{
    Auth::requireAdmin();
    if (db()->val('SELECT COUNT(*) FROM sales WHERE winning_pack_id = ?', [(int)$id]) || db()->val('SELECT COUNT(*) FROM orders WHERE pack_id = ?', [(int)$id])) {
        db()->exec('UPDATE packs SET is_active = 0 WHERE id = ?', [(int)$id]);
        flash('info', 'Ce pack est utilisé par une vente : il a été désactivé plutôt que supprimé.');
    } else {
        db()->exec('DELETE FROM packs WHERE id = ?', [(int)$id]);
        flash('success', 'Pack supprimé.');
    }
    redirect('/admin/packs');
}

/* ---------------------------- Commandes ---------------------------- */
function admin_orders(): void
{
    Auth::requireAdmin();
    $q = trim((string)input('q', ''));
    $st = (string)input('statut', '');
    $pay = (string)input('paiement', '');
    $where = ['1=1']; $params = [];
    if ($q !== '') { $where[] = '(o.reference LIKE ? OR o.pickup_code = ? OR u.email LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ?)'; array_push($params, "%$q%", strtoupper($q), "%$q%", "%$q%", "%$q%"); }
    if (isset(Orders::STATUS_LABELS[$st])) { $where[] = 'o.status = ?'; $params[] = $st; }
    if (isset(Orders::PAYMENT_LABELS[$pay])) { $where[] = 'o.payment_status = ?'; $params[] = $pay; }
    $orders = db()->all('SELECT o.*, u.first_name, u.last_name, u.email, g.name AS group_name, p.name AS pack_name FROM orders o JOIN users u ON u.id = o.user_id JOIN sales s ON s.id = o.sale_id JOIN `groups` g ON g.id = s.group_id JOIN packs p ON p.id = o.pack_id WHERE '
        . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 400', $params);
    if (input('format') === 'csv') {
        csv_out('commandes-' . date('Y-m-d') . '.csv', ['Référence', 'Date', 'Groupe', 'Pack', 'Qté', 'Lot', 'Nom', 'Prénom', 'Email', 'Statut', 'Paiement', 'Montant', 'Facture'],
            array_map(fn($o) => [$o['reference'], $o['created_at'], $o['group_name'], $o['pack_name'], $o['quantity'], $o['batch_no'], $o['last_name'], $o['first_name'], $o['email'], $o['status'], $o['payment_status'], num($o['amount'], 2), $o['invoice_number']], $orders));
    }
    admin_view('orders', ['title' => 'Commandes', 'orders' => $orders, 'q' => $q, 'st' => $st, 'pay' => $pay, 'section' => 'orders']);
}

function admin_order(string $id): void
{
    Auth::requireAdmin();
    $o = Orders::find((int)$id);
    if (!$o) abort(404);
    admin_view('order', ['title' => 'Commande ' . $o['reference'], 'o' => $o, 'items' => Orders::items($o), 'section' => 'orders']);
}

function admin_order_action(string $id): void
{
    $u = Auth::requireAdmin();
    $o = Orders::find((int)$id);
    if (!$o) abort(404);
    switch ((string)input('action')) {
        case 'mark_paid': Orders::markPaid((int)$o['id']); flash('success', 'Commande marquée payée.'); break;
        case 'retry':
            if ($o['payment_mode'] === 'stripe' && stripe_enabled()) { $r = Orders::charge($o); flash($r === 'paid' ? 'success' : 'error', $r === 'paid' ? 'Paiement réussi.' : 'Le paiement a de nouveau échoué.'); }
            break;
        case 'to_pickup': db()->exec("UPDATE orders SET payment_status = 'to_collect' WHERE id = ? AND payment_status IN ('failed','action_required')", [$o['id']]); flash('success', 'Paiement basculé au retrait.'); break;
        case 'resend_pass': if ($o['status'] === 'ready') { Orders::mailReady($o); flash('success', 'Pass Retrait renvoyé.'); } break;
        case 'cancel': try { Orders::cancel($o); flash('success', 'Achat annulé.'); } catch (DomainException $e) { flash('error', $e->getMessage()); } break;
        case 'refund':
            if ($o['payment_status'] === 'paid' && $o['stripe_payment_intent_id'] && stripe_enabled()) {
                try { Stripe::refund($o['stripe_payment_intent_id']); db()->exec("UPDATE orders SET payment_status = 'refunded' WHERE id = ?", [$o['id']]); flash('success', 'Remboursement effectué.'); }
                catch (Throwable $e) { flash('error', 'Remboursement impossible : ' . $e->getMessage()); }
            }
            break;
    }
    audit('order.' . input('action'), $o['reference'] . ' par ' . $u['email']);
    redirect('/admin/commandes/' . (int)$o['id']);
}

/* ---------------------------- Créneaux ----------------------------- */
function admin_shifts(): void
{
    Auth::requireAdmin();
    $edit = input_int('edit');
    admin_view('shifts', ['title' => 'Créneaux bénévoles', 'section' => 'shifts', 'groups' => db()->all("SELECT id, name FROM `groups` WHERE status = 'active' ORDER BY commune"),
        'shifts' => db()->all('SELECT s.*, g.name AS group_name, (SELECT GROUP_CONCAT(CONCAT(u.first_name, " ", u.last_name) SEPARATOR ", ") FROM shift_signups ss JOIN users u ON u.id = ss.user_id WHERE ss.shift_id = s.id) names,
            (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) signed FROM shifts s LEFT JOIN `groups` g ON g.id = s.group_id WHERE s.ends_at > (NOW() - INTERVAL 30 DAY) ORDER BY s.starts_at'),
        'edit' => $edit ? db()->one('SELECT * FROM shifts WHERE id = ?', [$edit]) : null]);
}

function admin_shift_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    if (input('delete') && $id) { db()->exec('DELETE FROM shifts WHERE id = ?', [$id]); flash('success', 'Créneau supprimé.'); redirect('/admin/creneaux'); }
    $data = ['type' => in_array(input('type'), ['packing', 'unloading', 'distribution', 'other'], true) ? input('type') : 'other', 'group_id' => input_int('group_id') ?: null,
        'title' => mb_substr((string)input('title', ''), 0, 160), 'description' => (string)input('description', '') ?: null,
        'location' => mb_substr((string)input('location', ''), 0, 255) ?: (string)setting('association_address'),
        'starts_at' => date('Y-m-d H:i:s', strtotime((string)input('starts_at')) ?: time()), 'ends_at' => date('Y-m-d H:i:s', strtotime((string)input('ends_at')) ?: time() + 10800),
        'volunteers_needed' => max(1, input_int('volunteers_needed', 3))];
    if ($data['title'] === '') { flash('error', 'Intitulé obligatoire.'); redirect('/admin/creneaux'); }
    $id ? db()->update('shifts', $data, 'id = ?', [$id]) : db()->insert('shifts', $data);
    flash('success', 'Créneau enregistré.');
    redirect('/admin/creneaux');
}

/* ----------------------- Membres & paramètres ---------------------- */
function admin_users(): void
{
    Auth::requireAdmin();
    $f = (string)input('filtre', '');
    $q = trim((string)input('q', ''));
    $where = ['1=1']; $params = [];
    if ($f === 'candidats') $where[] = "u.wants_volunteer = 1 AND u.role = 'client'";
    elseif (in_array($f, ['client', 'volunteer', 'admin'], true)) { $where[] = 'u.role = ?'; $params[] = $f; }
    if ($q !== '') { $where[] = '(u.email LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    $users = db()->all('SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) orders,
            (SELECT GROUP_CONCAT(CONCAT(g.commune, IF(gm.role = "volunteer", " (bénévole)", "")) SEPARATOR ", ") FROM group_members gm JOIN `groups` g ON g.id = gm.group_id WHERE gm.user_id = u.id) grps
        FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC LIMIT 400', $params);
    admin_view('users', ['title' => 'Membres', 'users' => $users, 'f' => $f, 'q' => $q, 'section' => 'users']);
}

function admin_user_role(string $id): void
{
    $me = Auth::requireAdmin();
    $role = (string)input('role');
    if (!in_array($role, ['client', 'volunteer', 'admin'], true)) back('/admin/utilisateurs');
    if ((int)$id === (int)$me['id'] && $role !== 'admin') { flash('error', 'Vous ne pouvez pas retirer votre propre accès administrateur.'); back('/admin/utilisateurs'); }
    $u = db()->one('SELECT * FROM users WHERE id = ?', [(int)$id]);
    if (!$u) abort(404);
    db()->exec('UPDATE users SET role = ?, wants_volunteer = 0 WHERE id = ?', [$role, $u['id']]);
    if ($role === 'volunteer' && $u['role'] === 'client') {
        Mailer::queue($u['email'], 'Votre accès bénévole est activé', Mailer::layout('Bienvenue dans l\'équipe',
            '<p>Bonjour ' . e($u['first_name']) . ',</p><p>Votre accès à l\'espace bénévoles est activé : préparation des packs, étiquettes et remise aux membres avec le scan des QR codes.</p>',
            'Ouvrir l\'espace bénévoles', abs_url('/benevoles')));
    }
    audit('user.role', $u['email'] . ' → ' . $role);
    flash('success', 'Rôle mis à jour pour ' . Auth::displayName($u) . '.');
    back('/admin/utilisateurs');
}

function admin_pilot(): void
{
    Auth::requireAdmin();
    Pilot::seedTodos();
    $stats = Agents::stats();
    admin_view('pilot', ['title' => 'Pilotage', 'section' => 'pilot', 'stats' => $stats,
        'todos' => Pilot::todos(), 'runs' => Pilot::lastRuns(), 'journal' => Pilot::journal(),
        'pipes' => ['fournisseur' => Pilot::pipeline('fournisseur'), 'collectivite' => Pilot::pipeline('collectivite')],
        'k' => [
            'proposed' => (int)db()->val("SELECT COUNT(*) FROM `groups` WHERE status = 'proposed'"),
            'pay_issues' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE payment_status IN ('failed','action_required')"),
            'to_prepare' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'"),
            'mail_failed' => (int)db()->val("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'"),
            'sales_open' => (int)db()->val("SELECT COUNT(*) FROM sales WHERE status IN ('voting','open')"),
            'posts_ready' => (int)db()->val("SELECT COUNT(*) FROM agent_items WHERE category = 'social' AND status = 'approved'"),
        ],
        'lastCron' => setting('last_cron_at')]);
}

function admin_pilot_action(): void
{
    Auth::requireAdmin();
    $do = (string)input('do');
    if ($do === 'pause' || $do === 'resume') Pilot::setPaused((string)input('agent'), $do === 'pause');
    elseif ($do === 'auto_on' || $do === 'auto_off') {
        if (!Pilot::setAuto((string)input('category'), $do === 'auto_on', Agents::stats())) flash('error', 'Pas encore possible : il faut 20 décisions dont 90 % validées sans retouche dans cette catégorie.');
    }
    elseif ($do === 'todo_done' || $do === 'todo_undo') Pilot::toggleTodo(input_int('id'), $do === 'todo_done');
    elseif ($do === 'todo_add' && trim((string)input('title')) !== '') Pilot::addTodo((string)input('title'));
    back('/admin');
}

function admin_validate(): void
{
    Auth::requireAdmin();
    $all = Agents::items(null, null, 200);
    $isPending = fn($i) => in_array($i['status'], ['pending', 'draft_needed'], true);
    admin_view('validate', ['title' => 'À valider', 'section' => 'validate',
        'pending' => array_reverse(array_values(array_filter($all, $isPending))),
        'recent' => array_slice(array_values(array_filter($all, fn($i) => !$isPending($i))), 0, 25),
        'stats' => Agents::stats()]);
}

function admin_validate_decide(string $id): void
{
    Auth::requireAdmin();
    $action = input('action') === 'reject' ? 'reject' : 'approve';
    $r = Agents::decide((int)$id, $action, ['subject' => input('subject'), 'body' => input('body')], (int)Auth::user()['id'], (string)input('note', ''));
    if (!($r['ok'] ?? false)) flash('error', $r['error'] ?? 'Impossible de traiter cet élément.');
    else flash('success', ['approved' => 'Validé.', 'sent' => 'Validé — le message part dans les minutes qui viennent.', 'rejected' => 'Refusé.'][$r['status']] ?? 'Fait.');
    back('/admin/a-valider');
}

function admin_agents_regenerate(): void
{
    Auth::requireAdmin();
    $_SESSION['agent_token_once'] = Agents::regenerate();
    flash('success', 'Nouveau jeton généré. Copiez-le maintenant : il ne sera plus affiché. Pensez à le mettre à jour dans les tâches planifiées Claude.');
    back('/admin/parametres');
}

const SETTING_KEYS = ['sachet_cost', 'pack_fixed_costs', 'market_price_note', 'logo_path', 'association_name', 'association_legal', 'association_address', 'contact_email', 'publication_director', 'host_info',
    'vat_mention', 'pickup_reminder_hours', 'default_vote_days', 'default_sale_days', 'default_batch_size', 'default_pack_price', 'hero_photo',
    'order_fee', 'order_fee_fund', 'membership_fee', 'president_name', 'treasurer_name', 'imap_host', 'imap_user'];

function admin_settings(): void
{
    Auth::requireAdmin();
    $s = [];
    foreach (SETTING_KEYS as $k) $s[$k] = setting($k, '');
    $photos = ['total' => (int)db()->val('SELECT COUNT(*) FROM photos'), 'cached' => (int)db()->val("SELECT COUNT(*) FROM photos WHERE status = 'cached'"),
        'missing' => db()->col("SELECT ref FROM photos WHERE status IN ('missing','failed')")];
    admin_view('settings', ['title' => 'Paramètres', 's' => $s, 'section' => 'settings', 'lastCron' => setting('last_cron_at'), 'demo' => setting('demo_data') === '1', 'photos' => $photos]);
}

function admin_settings_save(): void
{
    Auth::requireAdmin();
    if (input('test_email')) {
        $ok = Mailer::sendNow(Auth::user()['email'], 'Test d\'envoi — Les Jardins d\'à Côté', Mailer::layout('Test réussi', '<p>Si vous lisez ce message, l\'envoi des emails fonctionne.</p>'));
        flash($ok ? 'success' : 'error', $ok ? 'Email de test envoyé.' : 'Échec de l\'envoi : vérifiez les réglages SMTP dans app/config.php.');
        redirect('/admin/parametres');
    }
    if (input('purge_demo')) { Demo::purge(); flash('success', 'Données de démonstration supprimées.'); redirect('/admin'); }
    if (input('load_demo')) { if (!db()->val("SELECT 1 FROM `groups` WHERE organizer_name LIKE 'Exemple —%' LIMIT 1")) Demo::seed(); flash('success', 'Données de démonstration chargées.'); redirect('/admin'); }
    if (input('retry_photos')) { db()->exec("UPDATE photos SET status = 'pending', attempts = 0 WHERE status IN ('missing','failed')"); flash('success', 'Les photos seront retentées au prochain passage de la tâche planifiée.'); redirect('/admin/parametres'); }
    foreach (SETTING_KEYS as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        set_setting($k, mb_substr(trim((string)$_POST[$k]), 0, 2000));
    }
    // Mot de passe de la boîte mail : enregistré seulement s'il est saisi (jamais réaffiché).
    if (trim((string)input('imap_password', '')) !== '') set_setting('imap_password', trim((string)input('imap_password')));
    if (input('imap_clear')) set_setting('imap_password', '');
    if ($up = admin_upload('hero_file', 'accueil')) set_setting('hero_photo', $up);
    if ($up = admin_upload('logo_file', 'logo')) set_setting('logo_path', $up);
    flash('success', 'Paramètres enregistrés.');
    redirect('/admin/parametres');
}

function admin_run_tasks(): void
{
    Auth::requireAdmin();
    $r = Tasks::run();
    $sa = $r['sales'] ?? [];
    flash('success', 'Tâches exécutées : ' . ($sa['voting'] ?? 0) . ' vote(s) ouvert(s), ' . ($sa['opened'] ?? 0) . ' vente(s) ouverte(s), ' . ($sa['closed'] ?? 0) . ' clôturée(s), '
        . ($r['photos']['cached'] ?? 0) . ' photo(s) copiée(s), ' . ($r['emails']['sent'] ?? 0) . ' email(s) envoyé(s).');
    back('/admin');
}
