<?php
declare(strict_types=1);

function admin_view(string $tpl, array $data = []): void
{
    $data += ['head' => '<meta name="robots" content="noindex">', 'bodyClass' => 'admin'];
    $inner = render('admin/' . $tpl, $data);
    $content = render('admin/_shell', $data + ['inner' => $inner]);
    echo render('layouts/main', $data + ['content' => $content]);
}

/* ------------------------------------------------------------------ */
/* Tableau de bord                                                    */
/* ------------------------------------------------------------------ */
function admin_dashboard(): void
{
    Auth::requireAdmin();
    $active = Campaigns::hydrate(db()->all(Campaigns::SELECT . " WHERE c.status = 'active' ORDER BY c.end_at"));
    $committed = 0.0;
    foreach ($active as $c) $committed += $c['state']['reserved'] * $c['state']['price'];
    $alerts = array_filter($active, fn($c) => !$c['state']['validated'] && $c['state']['to_t1_pct'] >= 85 && strtotime($c['end_at']) - time() < 72 * 3600);
    $k = [
        'active' => count($active),
        'committed' => $committed,
        'participants' => (int)db()->val("SELECT COUNT(DISTINCT o.user_id) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.status = 'reserved'"),
        'to_pack' => (int)db()->val("SELECT COUNT(*) FROM campaigns WHERE status IN ('validated','receiving','packaging')"),
        'ready' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE status = 'ready'"),
        'pay_issues' => (int)db()->val("SELECT COUNT(*) FROM orders WHERE payment_status IN ('failed','action_required')"),
        'to_collect' => (float)db()->val("SELECT COALESCE(SUM(amount_final),0) FROM orders WHERE payment_status = 'to_collect'"),
        'paid' => (float)db()->val("SELECT COALESCE(SUM(amount_final),0) FROM orders WHERE payment_status = 'paid'"),
        'candidates' => (int)db()->val("SELECT COUNT(*) FROM users WHERE wants_volunteer = 1 AND role = 'client'"),
        'shifts_missing' => (int)db()->val('SELECT COUNT(*) FROM shifts s WHERE s.starts_at > NOW() AND (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) < s.volunteers_needed'),
        'mail_queue' => (int)db()->val("SELECT COUNT(*) FROM email_queue WHERE status = 'queued'"),
        'mail_failed' => (int)db()->val("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'"),
    ];
    $lastCron = setting('last_cron_at');
    admin_view('dashboard', ['title' => 'Tableau de bord', 'k' => $k, 'active' => $active, 'alerts' => $alerts, 'lastCron' => $lastCron, 'section' => 'dashboard']);
}

/* ------------------------------------------------------------------ */
/* Campagnes                                                          */
/* ------------------------------------------------------------------ */
function admin_campaigns(): void
{
    Auth::requireAdmin();
    $status = (string)input('statut', '');
    $where = $status !== '' && isset(Units::STATUSES[$status]) ? ' WHERE c.status = ' . db()->pdo->quote($status) : '';
    $camps = Campaigns::hydrate(db()->all(Campaigns::SELECT . $where . ' ORDER BY FIELD(c.status,"active","draft","validated","receiving","packaging","ready_pickup","failed","closed"), c.end_at DESC LIMIT 300'));
    $counts = [];
    foreach (db()->all('SELECT status, COUNT(*) n FROM campaigns GROUP BY status') as $r) $counts[$r['status']] = (int)$r['n'];
    admin_view('campaigns', ['title' => 'Campagnes', 'camps' => $camps, 'status' => $status, 'counts' => $counts, 'section' => 'campaigns']);
}

function admin_campaign_form(?string $id = null): void
{
    Auth::requireAdmin();
    $c = $id ? Campaigns::one((int)$id) : null;
    if ($id && !$c) abort(404);
    $productId = $c ? (int)$c['product_id'] : input_int('produit');
    $products = db()->all('SELECT p.id, p.name, p.variety, p.unit, p.category, s.name AS supplier FROM products p LEFT JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.name, p.variety');
    $product = $productId ? db()->one('SELECT * FROM products WHERE id = ?', [$productId]) : null;
    $packs = $productId ? db()->all('SELECT * FROM product_packs WHERE product_id = ? ORDER BY qty', [$productId]) : [];
    if (!$c) {
        $unit = $product['unit'] ?? 'g';
        $ref = ['g' => 10, 'seeds' => 50, 'l' => 10, 'kg' => 1, 'pot' => 1, 'piece' => 1][$unit];
        $c = [
            'id' => null, 'product_id' => $productId, 'title' => $product ? trim($product['name'] . ' ' . $product['variety']) : '', 'slug' => '', 'pitch' => '',
            'trigger_mode' => 'volume', 'status' => 'draft', 'supplier_batch_id' => null, 'pickup_window_id' => db()->val('SELECT id FROM pickup_windows WHERE starts_at > NOW() ORDER BY starts_at LIMIT 1'),
            'start_at' => date('Y-m-d H:i:s'), 'end_at' => date('Y-m-d 20:00:00', strtotime('+21 days')),
            'ref_portion' => $ref, 'portion_options' => ['g' => '5,10,25', 'seeds' => '25,50,100', 'l' => '5,10,20,50', 'kg' => '1,2,5', 'pot' => '1', 'piece' => '1'][$unit],
            'max_packets_per_line' => 20, 'max_qty_cap' => null, 'public_unit_price' => 0,
            'shipping_share_ht' => 0, 'waste_coeff' => setting('default_waste_coeff', '0.04'), 'packet_cost' => setting('default_packet_cost', '0.08'),
            'margin_rate' => setting('default_margin_rate', '0.15'), 'vat_rate' => setting('default_vat_rate', '0'), 'is_featured' => 0,
            'tiers' => [], 'unit' => $unit, 'current_reserved_qty' => 0, 'shop_buyback_qty' => 0,
        ];
    }
    $batches = db()->all("SELECT b.*, s.name AS supplier FROM supplier_batches b JOIN suppliers s ON s.id = b.supplier_id WHERE b.status IN ('open') OR b.id = ? ORDER BY b.created_at DESC", [$c['supplier_batch_id'] ?? 0]);
    $windows = db()->all('SELECT * FROM pickup_windows WHERE ends_at > NOW() OR id = ? ORDER BY starts_at', [$c['pickup_window_id'] ?? 0]);
    $orders = $c['id'] ? (int)db()->val("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE campaign_id = ? AND status <> 'cancelled'", [$c['id']]) : 0;
    admin_view('campaign_form', ['title' => $c['id'] ? 'Campagne : ' . $c['title'] : 'Nouvelle campagne', 'c' => $c, 'products' => $products, 'product' => $product,
        'packs' => $packs, 'batches' => $batches, 'windows' => $windows, 'orders' => $orders, 'section' => 'campaigns',
        'scripts' => '<script src="' . asset('js/admin.js') . '" defer></script>']);
}

function admin_campaign_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $productId = input_int('product_id');
    $product = db()->one('SELECT * FROM products WHERE id = ?', [$productId]);
    if (!$product) { flash('error', 'Choisissez un produit.'); back('/admin/campagnes'); }
    $ref = max(0.001, (float)input_dec('ref_portion', 10));
    $portions = Units::portions((string)input('portion_options', ''));
    if (!$portions) $portions = [$ref];
    $title = trim((string)input('title', '')) ?: trim($product['name'] . ' ' . $product['variety']);
    $data = [
        'product_id' => $productId, 'title' => mb_substr($title, 0, 190), 'pitch' => mb_substr((string)input('pitch', ''), 0, 255) ?: null,
        'trigger_mode' => in_array(input('trigger_mode'), array_keys(Units::MODES), true) ? input('trigger_mode') : 'volume',
        'supplier_batch_id' => input_int('supplier_batch_id') ?: null, 'pickup_window_id' => input_int('pickup_window_id') ?: null,
        'start_at' => date('Y-m-d H:i:s', strtotime((string)input('start_at')) ?: time()),
        'end_at' => date('Y-m-d H:i:s', strtotime((string)input('end_at')) ?: strtotime('+21 days')),
        'ref_portion' => $ref, 'portion_options' => implode(',', array_map(fn($p) => rtrim(rtrim(number_format($p, 3, '.', ''), '0'), '.'), $portions)),
        'max_packets_per_line' => max(1, min(999, input_int('max_packets_per_line', 20))),
        'max_qty_cap' => input('max_qty_cap') !== '' && input('max_qty_cap') !== null ? input_dec('max_qty_cap') : null,
        'public_unit_price' => round((float)input_dec('public_price_part', 0) / $ref, 5),
        'shipping_share_ht' => (float)input_dec('shipping_share_ht', 0), 'waste_coeff' => (float)input_dec('waste_pct', 4) / 100,
        'packet_cost' => (float)input_dec('packet_cost', 0.08), 'margin_rate' => (float)input_dec('margin_pct', 15) / 100,
        'vat_rate' => (float)input_dec('vat_pct', 0) / 100, 'is_featured' => input('is_featured') ? 1 : 0,
    ];
    if ($data['trigger_mode'] === 'franco' && !$data['supplier_batch_id']) { flash('error', 'Le mode « franco de port » nécessite un panier fournisseur.'); back('/admin/campagnes'); }
    if (strtotime($data['end_at']) <= strtotime($data['start_at'])) { flash('error', 'La date de fin doit suivre la date de début.'); back('/admin/campagnes'); }

    // Paliers saisis en "parts" et "prix par part"
    $tiers = [];
    $parts = (array)($_POST['tier_parts'] ?? []);
    $prices = (array)($_POST['tier_price'] ?? []);
    $labels = (array)($_POST['tier_label'] ?? []);
    foreach ($parts as $i => $p) {
        $p = (float)str_replace(',', '.', (string)$p);
        $pr = (float)str_replace(',', '.', (string)($prices[$i] ?? 0));
        if ($p <= 0 || $pr <= 0) continue;
        $tiers[] = ['min_qty' => round($p * $ref, 3), 'unit_price_ttc' => round($pr / $ref, 5), 'label' => mb_substr(trim((string)($labels[$i] ?? '')), 0, 120) ?: null];
    }
    usort($tiers, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);
    if (!$tiers) { flash('error', 'Ajoutez au moins un palier (le premier est le seuil de validation).'); back('/admin/campagnes'); }
    for ($i = 1; $i < count($tiers); $i++) {
        if ($tiers[$i]['unit_price_ttc'] >= $tiers[$i - 1]['unit_price_ttc']) { flash('error', 'Chaque palier doit avoir un prix inférieur au précédent.'); back('/admin/campagnes'); }
    }
    if ($data['public_unit_price'] <= 0) $data['public_unit_price'] = round($tiers[0]['unit_price_ttc'] * 1.3, 5);

    db()->tx(function (DB $db) use (&$id, $data, $tiers, $title) {
        if ($id) {
            $cur = $db->one('SELECT * FROM campaigns WHERE id = ? FOR UPDATE', [$id]);
            if (!$cur) abort(404);
            $db->update('campaigns', $data, 'id = ?', [$id]);
        } else {
            $data['slug'] = Campaigns::uniqueSlug($title);
            $data['status'] = 'draft';
            $data['packed_year'] = (int)date('Y');
            $id = $db->insert('campaigns', $data);
        }
        $db->exec('DELETE FROM campaign_tiers WHERE campaign_id = ?', [$id]);
        foreach ($tiers as $i => $t) $db->insert('campaign_tiers', ['campaign_id' => $id, 'tier_level' => $i + 1] + $t);
        Campaigns::refresh($id);
    });
    audit('campaign.saved', '#' . $id . ' ' . $title);
    if (input('publish')) {
        db()->exec("UPDATE campaigns SET status = 'active' WHERE id = ? AND status = 'draft'", [$id]);
        flash('success', 'Campagne publiée.');
    } else {
        flash('success', 'Campagne enregistrée.');
    }
    redirect('/admin/campagnes/' . $id);
}

function admin_campaign_action(string $id): void
{
    Auth::requireAdmin();
    $id = (int)$id;
    $c = Campaigns::find($id);
    if (!$c) abort(404);
    $a = (string)input('action');
    switch ($a) {
        case 'publish':
            db()->exec("UPDATE campaigns SET status = 'active' WHERE id = ? AND status = 'draft'", [$id]);
            flash('success', 'Campagne publiée.'); break;
        case 'unpublish':
            if ((int)db()->val("SELECT COUNT(*) FROM order_items WHERE campaign_id = ? AND status = 'reserved'", [$id]) > 0) { flash('error', 'Des engagements existent : clôturez plutôt la campagne.'); break; }
            db()->exec("UPDATE campaigns SET status = 'draft' WHERE id = ? AND status = 'active'", [$id]);
            flash('success', 'Campagne repassée en brouillon.'); break;
        case 'extend':
            Campaigns::extend($id, 48);
            flash('success', 'Campagne prolongée de 48 h.'); break;
        case 'force':
            $r = Campaigns::close($id, true);
            flash('success', $r['result'] === 'validated' ? 'Campagne validée : la boutique rachète le reliquat. Les paiements partent au prochain passage du cron.' : 'Action impossible.'); break;
        case 'close':
            $r = Campaigns::close($id);
            flash($r['result'] === 'failed' ? 'info' : 'success', $r['result'] === 'validated' ? 'Campagne clôturée et validée au prix du palier atteint.' : ($r['result'] === 'failed' ? 'Campagne clôturée sans atteindre le palier 1 : participants libérés.' : 'Rien à faire.'));
            Orders::processPending(40);
            break;
        case 'status':
            $to = (string)input('to');
            if (in_array($to, ['receiving', 'packaging', 'ready_pickup', 'closed'], true) && !in_array($c['status'], ['draft', 'active', 'failed'], true)) {
                db()->exec('UPDATE campaigns SET status = ? WHERE id = ?', [$to, $id]);
                if ($to === 'ready_pickup') foreach (db()->col('SELECT DISTINCT order_id FROM order_items WHERE campaign_id = ?', [$id]) as $oid) Orders::checkReady((int)$oid);
                flash('success', 'Statut mis à jour : ' . Units::STATUSES[$to] . '.');
            }
            break;
        case 'duplicate':
            $new = db()->tx(function (DB $db) use ($c, $id) {
                $row = $db->one('SELECT * FROM campaigns WHERE id = ?', [$id]);
                unset($row['id'], $row['created_at'], $row['updated_at']);
                $row = array_merge($row, ['slug' => Campaigns::uniqueSlug($c['title']), 'status' => 'draft', 'current_reserved_qty' => 0, 'current_tier' => 0,
                    'notified_tier' => 0, 'closed_at' => null, 'shop_buyback_qty' => 0, 'supplier_lot' => null,
                    'start_at' => date('Y-m-d H:i:s'), 'end_at' => date('Y-m-d 20:00:00', strtotime('+21 days'))]);
                $nid = $db->insert('campaigns', $row);
                foreach (Campaigns::tiers($id) as $t) { unset($t['id']); $t['campaign_id'] = $nid; $db->insert('campaign_tiers', $t); }
                Campaigns::refresh($nid);
                return $nid;
            });
            flash('success', 'Campagne dupliquée en brouillon.');
            redirect('/admin/campagnes/' . $new);
        case 'delete':
            if ((int)db()->val('SELECT COUNT(*) FROM order_items WHERE campaign_id = ?', [$id]) > 0) { flash('error', 'Impossible : des commandes sont liées à cette campagne.'); break; }
            db()->exec('DELETE FROM campaigns WHERE id = ?', [$id]);
            flash('success', 'Campagne supprimée.');
            redirect('/admin/campagnes');
    }
    audit('campaign.' . $a, '#' . $id);
    redirect('/admin/campagnes/' . $id);
}

function admin_campaign_participants_csv(string $id): void
{
    Auth::requireAdmin();
    $c = Campaigns::find((int)$id);
    if (!$c) abort(404);
    $rows = db()->all("SELECT o.reference, u.last_name, u.first_name, u.email, u.phone, oi.portion_qty, oi.packets, oi.quantity, oi.status, oi.unit_price_final, oi.line_total_final, o.payment_status, o.bin_code
        FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN users u ON u.id = o.user_id WHERE oi.campaign_id = ? AND oi.status <> 'cancelled' ORDER BY u.last_name", [$id]);
    csv_out('participants-' . $c['slug'] . '.csv', ['Référence', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Contenance', 'Sachets', 'Quantité', 'Statut', 'Prix unitaire final', 'Total final', 'Paiement', 'Casier'],
        array_map(fn($r) => [$r['reference'], $r['last_name'], $r['first_name'], $r['email'], $r['phone'], num($r['portion_qty'], 3), $r['packets'], num($r['quantity'], 3),
            $r['status'], $r['unit_price_final'] !== null ? num($r['unit_price_final'], 5) : '', $r['line_total_final'] !== null ? num($r['line_total_final'], 2) : '', $r['payment_status'], $r['bin_code']], $rows));
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

/** Générateur de paliers + simulateur de rentabilité (JSON, appelé en direct par le formulaire). */
function admin_api_tiers(): void
{
    Auth::requireAdmin();
    $body = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
    $productId = (int)($body['product_id'] ?? 0);
    $packs = db()->all('SELECT label, qty, cost_ht FROM product_packs WHERE product_id = ? ORDER BY qty', [$productId]);
    $ref = max(0.001, (float)($body['ref_portion'] ?? 10));
    $p = [
        'ref_portion' => $ref, 'vat_rate' => (float)($body['vat_pct'] ?? 0) / 100, 'margin_rate' => (float)($body['margin_pct'] ?? 15) / 100,
        'waste_coeff' => (float)($body['waste_pct'] ?? 4) / 100, 'packet_cost' => (float)($body['packet_cost'] ?? 0.08),
        'shipping_share_ht' => (float)($body['shipping_share_ht'] ?? 0), 'include_smallest' => !empty($body['include_smallest']),
    ];
    $out = ['packs' => $packs];
    if (($body['mode'] ?? '') === 'generate') {
        $g = Pricing::generateTiers($packs, $p);
        $out['tiers'] = array_map(fn($t) => ['parts' => round($t['min_qty'] / $ref, 2), 'price' => round($t['unit_price_ttc'] * $ref, 2), 'label' => $t['label']], $g['tiers']);
        $out['public_price_part'] = round($g['public_unit_price'] * $ref, 2);
        $tiers = $g['tiers'];
    } else {
        $tiers = [];
        foreach ((array)($body['tiers'] ?? []) as $i => $t) {
            if ((float)($t['parts'] ?? 0) > 0 && (float)($t['price'] ?? 0) > 0) {
                $tiers[] = ['tier_level' => $i + 1, 'min_qty' => (float)$t['parts'] * $ref, 'unit_price_ttc' => (float)$t['price'] / $ref, 'label' => $t['label'] ?? ''];
            }
        }
        usort($tiers, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);
        foreach ($tiers as $i => &$t) $t['tier_level'] = $i + 1;
    }
    $c = $p + ['public_unit_price' => (float)($body['public_price_part'] ?? 0) / $ref, 'current_reserved_qty' => 0];
    $reserved = isset($body['reserved']) ? (float)$body['reserved'] : null;
    $out['simulation'] = $packs ? Pricing::simulate($c, $tiers, $packs, $reserved) : [];
    json_out($out);
}

function admin_purchase_order(): void
{
    Auth::requireAdmin();
    $batchId = input_int('panier');
    $ids = array_filter(array_map('intval', (array)($_GET['c'] ?? [])));
    if ($batchId) $ids = db()->col("SELECT id FROM campaigns WHERE supplier_batch_id = ? AND status NOT IN ('draft','failed')", [$batchId]);
    if (!$ids) $ids = db()->col("SELECT id FROM campaigns WHERE status IN ('validated','receiving') ORDER BY closed_at");
    $po = Campaigns::purchaseOrder($ids);
    if (input('format') === 'csv') {
        $rows = [];
        foreach ($po['lines'] as $l) foreach ($l['opt']['lines'] as $p) {
            $rows[] = [$l['campaign']['supplier_name'], $l['campaign']['sku_supplier'], $l['campaign']['product_name'] . ' ' . $l['campaign']['variety'], $p['label'], $p['count'], num($p['unit_cost_ht'], 2), num($p['total_ht'], 2), num($l['need'], 3) . ' ' . Units::short($l['campaign']['unit'])];
        }
        csv_out('bon-de-commande-' . date('Y-m-d') . '.csv', ['Fournisseur', 'Référence', 'Produit', 'Conditionnement', 'Quantité', 'PU HT', 'Total HT', 'Besoin net'], $rows);
    }
    $batches = db()->all('SELECT b.*, s.name supplier FROM supplier_batches b JOIN suppliers s ON s.id = b.supplier_id ORDER BY b.created_at DESC');
    $batch = $batchId ? Campaigns::batchTotals($batchId) : null;
    admin_view('purchase_order', ['title' => 'Bon de commande fournisseur', 'po' => $po, 'batches' => $batches, 'batchId' => $batchId, 'batch' => $batch, 'ids' => $ids, 'section' => 'po']);
}

/* ------------------------------------------------------------------ */
/* Produits & import                                                  */
/* ------------------------------------------------------------------ */
function admin_products(): void
{
    Auth::requireAdmin();
    $q = trim((string)input('q', ''));
    $sql = 'SELECT p.*, s.name supplier, (SELECT COUNT(*) FROM product_packs pp WHERE pp.product_id = p.id) packs,
        (SELECT COUNT(*) FROM campaigns c WHERE c.product_id = p.id) camps FROM products p LEFT JOIN suppliers s ON s.id = p.supplier_id';
    $params = [];
    if ($q !== '') { $sql .= ' WHERE p.name LIKE ? OR p.variety LIKE ? OR p.sku_supplier LIKE ? OR p.latin_name LIKE ?'; $params = array_fill(0, 4, '%' . $q . '%'); }
    $products = db()->all($sql . ' ORDER BY p.name, p.variety LIMIT 500', $params);
    admin_view('products', ['title' => 'Catalogue produits', 'products' => $products, 'q' => $q, 'section' => 'products']);
}

function admin_product_form(?string $id = null): void
{
    Auth::requireAdmin();
    $p = $id ? db()->one('SELECT * FROM products WHERE id = ?', [(int)$id]) : null;
    if ($id && !$p) abort(404);
    $p ??= ['id' => null, 'supplier_id' => db()->val('SELECT id FROM suppliers ORDER BY id LIMIT 1'), 'category' => 'seeds', 'unit' => 'g', 'is_organic' => 1];
    $packs = $p['id'] ? db()->all('SELECT * FROM product_packs WHERE product_id = ? ORDER BY qty', [$p['id']]) : [];
    admin_view('product_form', ['title' => $p['id'] ? 'Produit : ' . $p['name'] : 'Nouveau produit', 'p' => $p, 'packs' => $packs,
        'suppliers' => db()->all('SELECT id, name FROM suppliers ORDER BY name'), 'section' => 'products',
        'scripts' => '<script src="' . asset('js/admin.js') . '" defer></script>']);
}

function admin_product_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    $enum = fn($v, $list) => in_array($v, $list, true) ? $v : null;
    $data = [
        'supplier_id' => input_int('supplier_id') ?: null, 'sku_supplier' => mb_substr((string)input('sku_supplier', ''), 0, 64) ?: null,
        'category' => $enum(input('category'), array_keys(Units::CATEGORIES)) ?? 'seeds',
        'name' => mb_substr((string)input('name', ''), 0, 160), 'variety' => mb_substr((string)input('variety', ''), 0, 160) ?: null,
        'latin_name' => mb_substr((string)input('latin_name', ''), 0, 160) ?: null, 'family' => mb_substr((string)input('family', ''), 0, 120) ?: null,
        'description' => (string)input('description', '') ?: null, 'sowing_advice' => (string)input('sowing_advice', '') ?: null,
        'sowing_months' => implode(',', array_filter(array_map('intval', (array)($_POST['sowing_months'] ?? [])))) ?: null,
        'sowing_depth' => mb_substr((string)input('sowing_depth', ''), 0, 40) ?: null,
        'exposure' => $enum(input('exposure'), array_keys(Units::EXPOSURE)), 'water' => $enum(input('water'), array_keys(Units::WATER)),
        'difficulty' => $enum(input('difficulty'), array_keys(Units::DIFFICULTY)), 'is_organic' => input('is_organic') ? 1 : 0,
        'unit' => $enum(input('unit'), array_keys(Units::ALL)) ?? 'g', 'pot_size' => mb_substr((string)input('pot_size', ''), 0, 40) ?: null,
        'pmg' => input('pmg') !== '' && input('pmg') !== null ? input_dec('pmg') : null,
        'image_url' => mb_substr((string)input('image_url', ''), 0, 255) ?: null, 'source_url' => mb_substr((string)input('source_url', ''), 0, 255) ?: null,
    ];
    if ($data['name'] === '') { flash('error', 'Le nom est obligatoire.'); back('/admin/produits'); }
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $info = @getimagesize($_FILES['image']['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if ($ext && $_FILES['image']['size'] < 5 * 1024 * 1024) {
            $dir = PUBLIC_DIR . '/uploads/produits';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $name = slugify($data['name'] . ' ' . $data['variety']) . '-' . substr(random_token(4), 0, 6) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $name)) $data['image_url'] = '/uploads/produits/' . $name;
        } else {
            flash('error', 'Image refusée (JPG, PNG ou WebP, 5 Mo maximum).');
        }
    }
    db()->tx(function (DB $db) use (&$id, $data) {
        if ($id) $db->update('products', $data, 'id = ?', [$id]);
        else $id = $db->insert('products', $data);
        $db->exec('DELETE FROM product_packs WHERE product_id = ?', [$id]);
        $labels = (array)($_POST['pack_label'] ?? []);
        foreach ($labels as $i => $l) {
            $q = (float)str_replace(',', '.', (string)($_POST['pack_qty'][$i] ?? 0));
            $cost = (float)str_replace(',', '.', (string)($_POST['pack_cost'][$i] ?? 0));
            if (trim((string)$l) === '' || $q <= 0 || $cost <= 0) continue;
            $db->insert('product_packs', ['product_id' => $id, 'label' => mb_substr(trim((string)$l), 0, 80), 'qty' => $q, 'cost_ht' => $cost]);
        }
    });
    flash('success', 'Produit enregistré.');
    redirect(input('then_campaign') ? '/admin/campagnes/nouvelle?produit=' . $id : '/admin/produits/' . $id);
}

function admin_product_delete(string $id): void
{
    Auth::requireAdmin();
    if ((int)db()->val('SELECT COUNT(*) FROM campaigns WHERE product_id = ?', [(int)$id]) > 0) { flash('error', 'Ce produit est utilisé par des campagnes.'); redirect('/admin/produits/' . (int)$id); }
    db()->exec('DELETE FROM products WHERE id = ?', [(int)$id]);
    flash('success', 'Produit supprimé.');
    redirect('/admin/produits');
}

const IMPORT_COLUMNS = ['sku', 'nom', 'variete', 'nom_latin', 'famille', 'categorie', 'unite', 'pmg', 'bio', 'exposition', 'eau', 'difficulte',
    'mois_semis', 'profondeur', 'description', 'conseils_semis', 'image_url', 'url_source', 'conditionnements'];

function admin_import_form(): void
{
    Auth::requireAdmin();
    admin_view('import', ['title' => 'Import catalogue', 'suppliers' => db()->all('SELECT id, name FROM suppliers ORDER BY name'), 'rows' => null, 'section' => 'import']);
}

function admin_import_template(): void
{
    Auth::requireAdmin();
    csv_out('modele-import-catalogue.csv', IMPORT_COLUMNS, [[
        'AG-TOM-001', 'Tomate', 'Cœur de Bœuf', 'Solanum lycopersicum', 'Solanacées', 'semences', 'graines', '3,2', 'oui', 'soleil', 'beaucoup', 'facile',
        '2,3,4', '0,5 cm', 'Grosse tomate charnue.', 'Semer au chaud en godets.', '', 'https://www.agrosemens.com/catalogue-2027.html',
        'Sachet 250 graines=250=6,10|Sac 1000 graines=1000=17,90|Sac 5000 graines=5000=66,00',
    ], [
        'AG-BAS-002', 'Basilic', 'Grand Vert', 'Ocimum basilicum', 'Lamiacées', 'semences', 'g', '1,7', 'oui', 'soleil', 'moyen', 'facile',
        '3,4,5,6', '0,2 cm', '', '', '', '', 'Sachet 10 g=10=4,20|Sac 50 g=50=13,50|Sac 250 g=250=49|Sac 1 kg=1000=160',
    ]]);
}

/** Lit un CSV d'import (séparateur ; ou , détecté) et normalise chaque ligne. */
function import_parse(string $file): array
{
    $raw = (string)file_get_contents($file);
    if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $firstLine = strtok($raw, "\n");
    $sep = substr_count((string)$firstLine, ';') >= substr_count((string)$firstLine, ',') ? ';' : ',';
    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $raw); rewind($fh);
    $head = array_map(fn($h) => slugify_col((string)$h), fgetcsv($fh, 0, $sep) ?: []);
    $rows = [];
    $map = [
        'unite' => ['g' => 'g', 'gramme' => 'g', 'grammes' => 'g', 'graines' => 'seeds', 'graine' => 'seeds', 'nombre' => 'seeds', 'l' => 'l', 'litre' => 'l', 'litres' => 'l', 'kg' => 'kg', 'pot' => 'pot', 'unite' => 'pot', 'piece' => 'piece', 'pièce' => 'piece'],
        'categorie' => ['semences' => 'seeds', 'graines' => 'seeds', 'seeds' => 'seeds', 'plantes' => 'indoor', 'interieur' => 'indoor', 'arbustes' => 'shrubs', 'fruitiers' => 'shrubs', 'terreau' => 'substrate', 'substrats' => 'substrate', 'pots' => 'pots', 'seconde_main' => 'second_hand'],
        'exposition' => ['ombre' => 'shade', 'mi-ombre' => 'partial', 'mi_ombre' => 'partial', 'miombre' => 'partial', 'soleil' => 'sun'],
        'eau' => ['peu' => 'low', 'faible' => 'low', 'moyen' => 'medium', 'modere' => 'medium', 'beaucoup' => 'high', 'eleve' => 'high'],
        'difficulte' => ['facile' => 'easy', 'debutant' => 'easy', 'increvable' => 'easy', 'moyen' => 'medium', 'intermediaire' => 'medium', 'difficile' => 'expert', 'expert' => 'expert'],
    ];
    $norm = fn($v) => mb_strtolower(trim(transliterator_transliterate('Any-Latin; Latin-ASCII', (string)$v) ?: (string)$v));
    $line = 1;
    while (($r = fgetcsv($fh, 0, $sep)) !== false) {
        $line++;
        if (count(array_filter($r, fn($x) => trim((string)$x) !== '')) === 0) continue;
        $row = [];
        foreach ($head as $i => $h) $row[$h] = trim((string)($r[$i] ?? ''));
        $errors = [];
        $packs = [];
        foreach (array_filter(explode('|', $row['conditionnements'] ?? '')) as $chunk) {
            $parts = array_map('trim', explode('=', $chunk));
            if (count($parts) !== 3) { $errors[] = 'conditionnement illisible « ' . $chunk . ' »'; continue; }
            $q = (float)str_replace([',', ' '], ['.', ''], $parts[1]); $c = (float)str_replace([',', ' ', '€'], ['.', '', ''], $parts[2]);
            if ($q <= 0 || $c <= 0) { $errors[] = 'quantité ou prix invalide « ' . $chunk . ' »'; continue; }
            $packs[] = ['label' => $parts[0], 'qty' => $q, 'cost_ht' => $c];
        }
        $unit = $map['unite'][$norm($row['unite'] ?? 'g')] ?? null;
        if (!$unit) $errors[] = 'unité inconnue';
        if (($row['nom'] ?? '') === '') $errors[] = 'nom manquant';
        $rows[] = [
            'line' => $line, 'errors' => $errors,
            'data' => [
                'sku_supplier' => $row['sku'] ?? null ?: null, 'name' => $row['nom'] ?? '', 'variety' => $row['variete'] ?? null ?: null,
                'latin_name' => $row['nom_latin'] ?? null ?: null, 'family' => $row['famille'] ?? null ?: null,
                'category' => $map['categorie'][$norm($row['categorie'] ?? 'semences')] ?? 'seeds', 'unit' => $unit ?? 'g',
                'pmg' => isset($row['pmg']) && $row['pmg'] !== '' ? (float)str_replace(',', '.', $row['pmg']) : null,
                'is_organic' => in_array($norm($row['bio'] ?? 'oui'), ['oui', '1', 'yes', 'bio', 'x'], true) ? 1 : 0,
                'exposure' => $map['exposition'][$norm($row['exposition'] ?? '')] ?? null, 'water' => $map['eau'][$norm($row['eau'] ?? '')] ?? null,
                'difficulty' => $map['difficulte'][$norm($row['difficulte'] ?? '')] ?? null,
                'sowing_months' => implode(',', array_filter(array_map('intval', preg_split('/[^0-9]+/', $row['mois_semis'] ?? '') ?: []), fn($m) => $m >= 1 && $m <= 12)) ?: null,
                'sowing_depth' => $row['profondeur'] ?? null ?: null, 'description' => $row['description'] ?? null ?: null,
                'sowing_advice' => $row['conseils_semis'] ?? null ?: null, 'image_url' => $row['image_url'] ?? null ?: null, 'source_url' => $row['url_source'] ?? null ?: null,
            ],
            'packs' => $packs,
        ];
    }
    fclose($fh);
    return ['head' => $head, 'rows' => $rows];
}

function slugify_col(string $h): string
{
    return str_replace('-', '_', slugify(trim($h)));
}

function admin_import_preview(): void
{
    Auth::requireAdmin();
    if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) { flash('error', 'Choisissez un fichier CSV.'); redirect('/admin/import'); }
    $parsed = import_parse($_FILES['csv']['tmp_name']);
    $missing = array_diff(['nom', 'unite', 'conditionnements'], $parsed['head']);
    if ($missing) { flash('error', 'Colonnes manquantes : ' . implode(', ', $missing) . '. Utilisez le modèle.'); redirect('/admin/import'); }
    $supplierId = input_int('supplier_id');
    foreach ($parsed['rows'] as &$r) {
        $r['exists'] = $r['data']['sku_supplier'] ? (bool)db()->val('SELECT id FROM products WHERE supplier_id = ? AND sku_supplier = ?', [$supplierId, $r['data']['sku_supplier']]) : false;
    }
    $_SESSION['import'] = ['supplier_id' => $supplierId, 'rows' => $parsed['rows']];
    admin_view('import', ['title' => 'Import catalogue — vérification', 'suppliers' => db()->all('SELECT id, name FROM suppliers ORDER BY name'),
        'rows' => $parsed['rows'], 'supplierId' => $supplierId, 'section' => 'import']);
}

function admin_import_confirm(): void
{
    Auth::requireAdmin();
    $imp = $_SESSION['import'] ?? null;
    if (!$imp) redirect('/admin/import');
    $createCampaigns = (bool)input('create_campaigns');
    $created = 0; $updated = 0; $camps = 0;
    db()->tx(function (DB $db) use ($imp, $createCampaigns, &$created, &$updated, &$camps) {
        foreach ($imp['rows'] as $r) {
            if ($r['errors']) continue;
            $data = $r['data'] + ['supplier_id' => $imp['supplier_id'] ?: null];
            $id = $data['sku_supplier'] ? (int)$db->val('SELECT id FROM products WHERE supplier_id <=> ? AND sku_supplier = ?', [$data['supplier_id'], $data['sku_supplier']]) : 0;
            if ($id) { $db->update('products', $data, 'id = ?', [$id]); $updated++; }
            else { $id = $db->insert('products', $data); $created++; }
            if ($r['packs']) {
                $db->exec('DELETE FROM product_packs WHERE product_id = ?', [$id]);
                foreach ($r['packs'] as $p) $db->insert('product_packs', ['product_id' => $id] + $p);
            }
            if ($createCampaigns && $r['packs'] && !$db->val("SELECT id FROM campaigns WHERE product_id = ? AND status IN ('draft','active')", [$id])) {
                $unit = $data['unit'];
                $ref = ['g' => 10, 'seeds' => 50, 'l' => 10, 'kg' => 1, 'pot' => 1, 'piece' => 1][$unit];
                $params = ['ref_portion' => $ref, 'vat_rate' => (float)setting('default_vat_rate', 0), 'margin_rate' => (float)setting('default_margin_rate', 0.15),
                    'waste_coeff' => (float)setting('default_waste_coeff', 0.04), 'packet_cost' => (float)setting('default_packet_cost', 0.08), 'shipping_share_ht' => 0];
                $g = Pricing::generateTiers($r['packs'], $params);
                if (!$g['tiers']) continue;
                $title = trim($data['name'] . ' ' . $data['variety']);
                $cid = $db->insert('campaigns', [
                    'product_id' => $id, 'slug' => Campaigns::uniqueSlug($title), 'title' => $title, 'status' => 'draft', 'trigger_mode' => 'volume',
                    'start_at' => now(), 'end_at' => date('Y-m-d 20:00:00', strtotime('+21 days')), 'ref_portion' => $ref,
                    'portion_options' => ['g' => '5,10,25', 'seeds' => '25,50,100', 'l' => '5,10,20', 'kg' => '1,2,5', 'pot' => '1', 'piece' => '1'][$unit],
                    'public_unit_price' => $g['public_unit_price'], 'waste_coeff' => $params['waste_coeff'], 'packet_cost' => $params['packet_cost'],
                    'margin_rate' => $params['margin_rate'], 'vat_rate' => $params['vat_rate'], 'packed_year' => (int)date('Y'),
                ]);
                foreach ($g['tiers'] as $t) $db->insert('campaign_tiers', ['campaign_id' => $cid] + $t);
                Campaigns::refresh($cid);
                $camps++;
            }
        }
    });
    unset($_SESSION['import']);
    audit('import', "créés $created, mis à jour $updated, campagnes $camps");
    flash('success', "Import terminé : $created " . plural($created, 'produit créé', 'produits créés') . ", $updated " . plural($updated, 'mis à jour', 'mis à jour')
        . ($camps ? ", $camps " . plural($camps, 'campagne brouillon générée', 'campagnes brouillons générées') . ' avec leurs paliers' : '') . '.');
    redirect($camps ? '/admin/campagnes?statut=draft' : '/admin/produits');
}

/* ------------------------------------------------------------------ */
/* Fournisseurs, paniers fournisseur, retraits, créneaux              */
/* ------------------------------------------------------------------ */
function admin_suppliers(): void
{
    Auth::requireAdmin();
    $edit = input_int('edit');
    admin_view('suppliers', ['title' => 'Fournisseurs', 'section' => 'suppliers',
        'suppliers' => db()->all('SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id) products FROM suppliers s ORDER BY s.name'),
        'edit' => $edit ? db()->one('SELECT * FROM suppliers WHERE id = ?', [$edit]) : null]);
}

function admin_supplier_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    if (input('delete') && $id) {
        if ((int)db()->val('SELECT COUNT(*) FROM products WHERE supplier_id = ?', [$id])) { flash('error', 'Ce fournisseur a des produits.'); redirect('/admin/fournisseurs'); }
        db()->exec('DELETE FROM suppliers WHERE id = ?', [$id]);
        flash('success', 'Fournisseur supprimé.'); redirect('/admin/fournisseurs');
    }
    $data = ['name' => mb_substr((string)input('name', ''), 0, 120), 'catalog_url' => (string)input('catalog_url', '') ?: null,
        'min_order_franco_ht' => (float)input_dec('min_order_franco_ht', 0), 'shipping_cost_ht' => (float)input_dec('shipping_cost_ht', 0),
        'lead_time_days' => input_int('lead_time_days', 7), 'contact_email' => (string)input('contact_email', '') ?: null,
        'contact_phone' => (string)input('contact_phone', '') ?: null, 'notes' => (string)input('notes', '') ?: null];
    if ($data['name'] === '') { flash('error', 'Nom obligatoire.'); redirect('/admin/fournisseurs'); }
    $id ? db()->update('suppliers', $data, 'id = ?', [$id]) : db()->insert('suppliers', $data);
    flash('success', 'Fournisseur enregistré.');
    redirect('/admin/fournisseurs');
}

function admin_batches(): void
{
    Auth::requireAdmin();
    $rows = db()->all('SELECT b.*, s.name supplier FROM supplier_batches b JOIN suppliers s ON s.id = b.supplier_id ORDER BY b.created_at DESC');
    foreach ($rows as &$b) $b['totals'] = Campaigns::batchTotals((int)$b['id']);
    $edit = input_int('edit');
    admin_view('batches', ['title' => 'Paniers fournisseur (franco)', 'batches' => $rows, 'section' => 'batches',
        'suppliers' => db()->all('SELECT id, name, min_order_franco_ht FROM suppliers ORDER BY name'),
        'edit' => $edit ? db()->one('SELECT * FROM supplier_batches WHERE id = ?', [$edit]) : null]);
}

function admin_batch_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    if (input('ordered') && $id) {
        db()->exec("UPDATE supplier_batches SET status = 'ordered', ordered_at = NOW() WHERE id = ?", [$id]);
        db()->exec("UPDATE campaigns SET status = 'receiving' WHERE supplier_batch_id = ? AND status = 'validated'", [$id]);
        flash('success', 'Commande fournisseur marquée comme passée : les campagnes passent en « Réception ».');
        redirect('/admin/paniers-fournisseur');
    }
    $data = ['supplier_id' => input_int('supplier_id'), 'name' => mb_substr((string)input('name', ''), 0, 160), 'franco_ht' => (float)input_dec('franco_ht', 0)];
    if (!$data['supplier_id'] || $data['name'] === '') { flash('error', 'Fournisseur et nom obligatoires.'); redirect('/admin/paniers-fournisseur'); }
    $id ? db()->update('supplier_batches', $data, 'id = ?', [$id]) : db()->insert('supplier_batches', $data);
    flash('success', 'Panier fournisseur enregistré.');
    redirect('/admin/paniers-fournisseur');
}

function admin_windows(): void
{
    Auth::requireAdmin();
    $edit = input_int('edit');
    admin_view('windows', ['title' => 'Distributions & retraits', 'section' => 'windows',
        'windows' => db()->all('SELECT w.*, (SELECT COUNT(*) FROM campaigns c WHERE c.pickup_window_id = w.id) camps FROM pickup_windows w ORDER BY w.starts_at DESC'),
        'edit' => $edit ? db()->one('SELECT * FROM pickup_windows WHERE id = ?', [$edit]) : null]);
}

function admin_window_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    if (input('delete') && $id) { db()->exec('DELETE FROM pickup_windows WHERE id = ?', [$id]); flash('success', 'Distribution supprimée.'); redirect('/admin/retraits'); }
    $data = ['label' => mb_substr((string)input('label', ''), 0, 160), 'location' => mb_substr((string)input('location', ''), 0, 160),
        'address' => mb_substr((string)input('address', ''), 0, 255), 'starts_at' => date('Y-m-d H:i:s', strtotime((string)input('starts_at')) ?: time()),
        'ends_at' => date('Y-m-d H:i:s', strtotime((string)input('ends_at')) ?: time() + 10800), 'notes' => (string)input('notes', '') ?: null];
    if ($data['label'] === '' || $data['address'] === '') { flash('error', 'Intitulé et adresse obligatoires.'); redirect('/admin/retraits'); }
    $id ? db()->update('pickup_windows', $data, 'id = ?', [$id]) : db()->insert('pickup_windows', $data);
    flash('success', 'Distribution enregistrée.');
    redirect('/admin/retraits');
}

function admin_shifts(): void
{
    Auth::requireAdmin();
    $edit = input_int('edit');
    $shifts = db()->all('SELECT s.*, (SELECT GROUP_CONCAT(CONCAT(u.first_name, " ", u.last_name) SEPARATOR ", ") FROM shift_signups ss JOIN users u ON u.id = ss.user_id WHERE ss.shift_id = s.id) names,
        (SELECT COUNT(*) FROM shift_signups ss WHERE ss.shift_id = s.id) signed FROM shifts s WHERE s.ends_at > (NOW() - INTERVAL 30 DAY) ORDER BY s.starts_at');
    admin_view('shifts', ['title' => 'Créneaux bénévoles', 'shifts' => $shifts, 'section' => 'shifts',
        'campaigns' => db()->all("SELECT id, title FROM campaigns WHERE status NOT IN ('closed','failed') ORDER BY title"),
        'edit' => $edit ? db()->one('SELECT * FROM shifts WHERE id = ?', [$edit]) : null]);
}

function admin_shift_save(): void
{
    Auth::requireAdmin();
    $id = input_int('id');
    if (input('delete') && $id) { db()->exec('DELETE FROM shifts WHERE id = ?', [$id]); flash('success', 'Créneau supprimé.'); redirect('/admin/creneaux'); }
    $data = ['type' => in_array(input('type'), ['packing', 'unloading', 'distribution', 'other'], true) ? input('type') : 'other',
        'title' => mb_substr((string)input('title', ''), 0, 160), 'description' => (string)input('description', '') ?: null,
        'location' => mb_substr((string)input('location', '') ?: (string)setting('association_address'), 0, 255),
        'starts_at' => date('Y-m-d H:i:s', strtotime((string)input('starts_at')) ?: time()), 'ends_at' => date('Y-m-d H:i:s', strtotime((string)input('ends_at')) ?: time() + 10800),
        'volunteers_needed' => max(1, input_int('volunteers_needed', 3)), 'campaign_id' => input_int('campaign_id') ?: null];
    if ($data['title'] === '') { flash('error', 'Intitulé obligatoire.'); redirect('/admin/creneaux'); }
    $id ? db()->update('shifts', $data, 'id = ?', [$id]) : db()->insert('shifts', $data);
    flash('success', 'Créneau enregistré.');
    redirect('/admin/creneaux');
}

/* ------------------------------------------------------------------ */
/* Commandes                                                          */
/* ------------------------------------------------------------------ */
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
    $orders = db()->all('SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 300', $params);
    if (input('format') === 'csv') {
        csv_out('commandes-' . date('Y-m-d') . '.csv', ['Référence', 'Date', 'Nom', 'Prénom', 'Email', 'Statut', 'Paiement', 'Engagé', 'Final', 'Facture'],
            array_map(fn($o) => [$o['reference'], $o['created_at'], $o['last_name'], $o['first_name'], $o['email'], $o['status'], $o['payment_status'], num($o['amount_committed'], 2), $o['amount_final'] !== null ? num($o['amount_final'], 2) : '', $o['invoice_number']], $orders));
    }
    admin_view('orders', ['title' => 'Commandes', 'orders' => $orders, 'q' => $q, 'st' => $st, 'pay' => $pay, 'section' => 'orders']);
}

function admin_order(string $id): void
{
    Auth::requireAdmin();
    $o = Orders::find((int)$id);
    if (!$o) abort(404);
    admin_view('order', ['title' => 'Commande ' . $o['reference'], 'o' => $o, 'items' => Orders::items((int)$o['id']), 'section' => 'orders']);
}

function admin_order_action(string $id): void
{
    $u = Auth::requireAdmin();
    $o = Orders::find((int)$id);
    if (!$o) abort(404);
    switch ((string)input('action')) {
        case 'mark_paid':
            db()->exec("UPDATE orders SET payment_status = 'to_collect' WHERE id = ? AND payment_status IN ('failed','action_required')", [$o['id']]);
            Orders::markPaid((int)$o['id']);
            flash('success', 'Commande marquée payée.'); break;
        case 'retry':
            if ($o['payment_mode'] === 'stripe' && in_array($o['payment_status'], ['failed', 'action_required'], true) && stripe_enabled()) {
                $r = Orders::charge($o);
                flash($r === 'paid' ? 'success' : 'error', $r === 'paid' ? 'Paiement réussi.' : 'Le paiement a de nouveau échoué : le client a été prévenu.');
            }
            break;
        case 'to_pickup':
            db()->exec("UPDATE orders SET payment_status = 'to_collect' WHERE id = ? AND payment_status IN ('failed','action_required','pending')", [$o['id']]);
            flash('success', 'Paiement basculé en « à régler au retrait ».'); break;
        case 'resend_pass':
            if (in_array($o['status'], ['ready', 'confirmed'], true)) { Orders::mailReady($o); flash('success', 'Pass Retrait renvoyé.'); }
            break;
        case 'cancel':
            try { Orders::cancel($o); flash('success', 'Engagement annulé.'); } catch (DomainException $e) { flash('error', $e->getMessage()); }
            break;
        case 'refund':
            if ($o['payment_status'] === 'paid' && $o['stripe_payment_intent_id'] && stripe_enabled()) {
                try {
                    Stripe::refund($o['stripe_payment_intent_id']);
                    db()->exec("UPDATE orders SET payment_status = 'refunded' WHERE id = ?", [$o['id']]);
                    flash('success', 'Remboursement effectué.');
                } catch (Throwable $e) { flash('error', 'Remboursement impossible : ' . $e->getMessage()); }
            }
            break;
    }
    audit('order.' . input('action'), $o['reference'] . ' par ' . $u['email']);
    redirect('/admin/commandes/' . (int)$o['id']);
}

/* ------------------------------------------------------------------ */
/* Utilisateurs & paramètres                                          */
/* ------------------------------------------------------------------ */
function admin_users(): void
{
    Auth::requireAdmin();
    $f = (string)input('filtre', '');
    $q = trim((string)input('q', ''));
    $where = ['1=1']; $params = [];
    if ($f === 'candidats') $where[] = "u.wants_volunteer = 1 AND u.role = 'client'";
    elseif (in_array($f, ['client', 'volunteer', 'admin'], true)) { $where[] = 'u.role = ?'; $params[] = $f; }
    if ($q !== '') { $where[] = '(u.email LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    $users = db()->all('SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) orders FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC LIMIT 300', $params);
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
        Mailer::queue($u['email'], 'Bienvenue dans l\'équipe des bénévoles !', Mailer::layout('Vous êtes bénévole',
            '<p>Bonjour ' . e($u['first_name']) . ',</p><p>Votre accès à l\'espace bénévoles est activé. Vous pouvez dès maintenant vous inscrire aux ateliers d\'ensachage et aux permanences.</p>',
            'Voir le planning', abs_url('/benevoles/planning')));
    }
    audit('user.role', $u['email'] . ' → ' . $role);
    flash('success', 'Rôle mis à jour pour ' . Auth::displayName($u) . '.');
    back('/admin/utilisateurs');
}

const SETTING_KEYS = ['association_name', 'association_legal', 'association_address', 'contact_email', 'publication_director', 'host_info',
    'default_margin_rate', 'default_waste_coeff', 'default_packet_cost', 'default_vat_rate', 'vat_mention', 'pickup_reminder_hours', 'deposit_fee'];

function admin_settings(): void
{
    Auth::requireAdmin();
    $s = [];
    foreach (SETTING_KEYS as $k) $s[$k] = setting($k, '');
    admin_view('settings', ['title' => 'Paramètres', 's' => $s, 'section' => 'settings', 'lastCron' => setting('last_cron_at'), 'demo' => setting('demo_data') === '1']);
}

function admin_settings_save(): void
{
    Auth::requireAdmin();
    if (input('test_email')) {
        $ok = Mailer::sendNow(Auth::user()['email'], 'Test d\'envoi — Les Jardins d\'à Côté', Mailer::layout('Test réussi', '<p>Si vous lisez ce message, l\'envoi des emails fonctionne.</p>'));
        flash($ok ? 'success' : 'error', $ok ? 'Email de test envoyé à ' . Auth::user()['email'] . '.' : 'Échec de l\'envoi : vérifiez les réglages SMTP dans app/config.php (détail dans app/storage/logs/php-error.log).');
        redirect('/admin/parametres');
    }
    if (input('purge_demo')) {
        Demo::purge();
        flash('success', 'Données de démonstration supprimées. La plateforme est prête pour vos vraies campagnes.');
        redirect('/admin');
    }
    foreach (SETTING_KEYS as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        $v = trim((string)$_POST[$k]);
        if (in_array($k, ['default_margin_rate', 'default_waste_coeff', 'default_vat_rate'], true)) $v = (string)((float)str_replace(',', '.', $v) / 100);
        if (in_array($k, ['default_packet_cost', 'deposit_fee'], true)) $v = (string)(float)str_replace(',', '.', $v);
        set_setting($k, mb_substr($v, 0, 2000));
    }
    flash('success', 'Paramètres enregistrés.');
    redirect('/admin/parametres');
}

function admin_run_tasks(): void
{
    Auth::requireAdmin();
    $r = Tasks::run();
    $closed = count($r['campaigns_closed'] ?? []);
    flash('success', 'Tâches exécutées : ' . $closed . ' ' . plural($closed, 'campagne clôturée', 'campagnes clôturées') . ', ' . count($r['orders_processed'] ?? []) . ' commande(s) traitée(s), ' . ($r['emails']['sent'] ?? 0) . ' email(s) envoyé(s).');
    back('/admin');
}
