<?php
declare(strict_types=1);

function front_home(): void
{
    $groups = Groups::listActive();
    usort($groups, fn($a, $b) => array_search($a['public_status'], ['open', 'preopening', 'recruiting'], true) <=> array_search($b['public_status'], ['open', 'preopening', 'recruiting'], true));
    // Les trois packs graines du moment : ceux de la vente la plus avancée, sinon le catalogue.
    $packs = [];
    $sid = db()->val("SELECT s.id FROM sales s JOIN `groups` g ON g.id = s.group_id WHERE g.status = 'active' AND s.category = 'seeds' AND s.status IN ('waiting','voting','open') ORDER BY FIELD(s.status,'voting','open','waiting'), s.id LIMIT 1");
    if ($sid) $packs = Sales::packs((int)$sid);
    if (!$packs) $packs = array_slice(Packs::all('seeds', true), 0, 3);
    $catalog = Packs::all(null, true);
    // Le pack mis en avant dans le héros : la plus forte économie.
    $hero = null;
    foreach ($packs ?: $catalog as $p) if (!$hero || $p['save'] > $hero['save']) $hero = $p;
    $varieties = [];
    foreach ($catalog as $p) foreach ($p['items'] as $it) $varieties[$it['name'] . ' ' . $it['variety']] = [$it['name'], (string)$it['variety']];
    $others = array_values(array_filter($catalog, fn($p) => $p['category'] !== 'seeds'));
    view('front/home', ['groups' => $groups, 'packs' => $packs, 'heroPack' => $hero, 'varieties' => array_values($varieties),
        'bestPct' => Packs::bestSavingPct($catalog), 'others' => $others, 'bodyClass' => 'has-hero']);
}

function front_groups(): void
{
    $q = trim((string)input('q', ''));
    $st = (string)input('statut', '');
    $all = Groups::listActive($q);
    $counts = ['all' => count($all)] + array_fill_keys(array_keys(Cat::GROUP_STATUS), 0);
    foreach ($all as $g) $counts[$g['public_status']]++;
    $groups = isset(Cat::GROUP_STATUS[$st]) ? array_values(array_filter($all, fn($g) => $g['public_status'] === $st)) : $all;
    view('front/groups', ['groups' => $groups, 'q' => $q, 'st' => isset(Cat::GROUP_STATUS[$st]) ? $st : '', 'counts' => $counts,
        'title' => 'Les groupes inscrits', 'description' => 'Liste des groupes d\'achat « Les Jardins d\'à Côté » par commune, avec leur statut : bénévoles recherchés, pré-ouverture ou ventes ouvertes.']);
}

function front_group(string $slug): void
{
    $g = Groups::findBySlug($slug);
    if (!$g || ($g['status'] !== 'active' && !Auth::isAdmin())) abort(404, 'Ce groupe n\'existe pas ou n\'est pas encore publié.');
    $g = Groups::hydrate([$g])[0];
    $uid = Auth::id();
    $s = $g['sale'];
    view('front/group', [
        'g' => $g, 'title' => $g['name'], 'myRole' => Groups::role((int)$g['id'], $uid),
        'myVote' => $s ? Sales::userVote((int)$s['id'], $uid) : null,
        'inCart' => $s && $uid ? db()->one('SELECT * FROM cart_items WHERE user_id = ? AND sale_id = ?', [$uid, $s['id']]) : null,
        'myOrder' => $s && $uid ? db()->one("SELECT * FROM orders WHERE user_id = ? AND sale_id = ? AND status <> 'cancelled' ORDER BY id DESC LIMIT 1", [$uid, $s['id']]) : null,
        'description' => $g['name'] . ' : ' . Cat::GROUP_STATUS[$g['public_status']]['label'] . '. Groupe d\'achat de packs de graines et de plants à ' . $g['commune'] . '.',
        'og' => ['image' => $g['photo'] ? (str_starts_with((string)Photo::url($g['photo']), '/') ? abs_url((string)Photo::url($g['photo'])) : Photo::url($g['photo'])) : null],
        'scripts' => '', 'bodyClass' => 'has-hero',
        'groupSaved' => $s ? Sales::groupSaved($s) : 0.0,
    ]);
}

function front_group_join(string $slug): void
{
    $g = Groups::findBySlug($slug);
    if (!$g || $g['status'] !== 'active') abort(404);
    if (!Auth::check()) {
        $_SESSION['intended'] = '/groupes/' . $slug;
        $_SESSION['pending_join'] = ['group' => (int)$g['id'], 'role' => input('role') === 'volunteer' ? 'volunteer' : 'member'];
        flash('info', 'Créez votre compte (30 secondes) ou connectez-vous pour rejoindre ' . $g['name'] . '.');
        redirect('/inscription');
    }
    $role = input('role') === 'volunteer' ? 'volunteer' : 'member';
    Groups::join((int)$g['id'], (int)Auth::id(), $role);
    flash('success', $role === 'volunteer'
        ? 'Merci ! Vous êtes bénévole de ' . $g['name'] . '. Un membre de l\'association vous contactera pour la préparation des packs.'
        : 'Bienvenue dans ' . $g['name'] . ' ! Vous serez prévenu·e du vote et de l\'ouverture des ventes.');
    redirect('/groupes/' . $slug);
}

/** Rejoindre automatiquement le groupe demandé avant la connexion. */
function apply_pending_join(): void
{
    $p = $_SESSION['pending_join'] ?? null;
    unset($_SESSION['pending_join']);
    if ($p && Auth::id()) Groups::join((int)$p['group'], (int)Auth::id(), $p['role']);
}

function front_vote(string $saleId): void
{
    $s = Sales::find((int)$saleId);
    if (!$s) abort(404);
    if (!Auth::check()) {
        $_SESSION['intended'] = '/groupes/' . $s['group_slug'];
        flash('info', 'Connectez-vous ou créez un compte pour voter : le pack gagnant arrivera directement dans votre panier.');
        redirect('/inscription');
    }
    try {
        Sales::vote((int)$saleId, (int)Auth::id(), input_int('pack_id'));
        $name = db()->val('SELECT name FROM packs WHERE id = ?', [input_int('pack_id')]);
        flash('success', 'Vote enregistré pour « ' . $name . ' ». Résultat le ' . date_fr($s['vote_ends_at']) . '.');
    } catch (DomainException $e) {
        flash('error', $e->getMessage());
    }
    redirect('/groupes/' . $s['group_slug'] . '#pack-' . input_int('pack_id'));
}

function front_pack(string $slug): void
{
    $p = Packs::findBySlug($slug);
    if (!$p) abort(404, 'Ce pack n\'existe pas.');
    $groups = db()->all("SELECT g.name, g.slug, s.status AS sale_status FROM sale_packs sp JOIN sales s ON s.id = sp.sale_id JOIN `groups` g ON g.id = s.group_id
        WHERE sp.pack_id = ? AND g.status = 'active' AND s.status IN ('waiting','voting','open') ORDER BY g.commune", [$p['id']]);
    view('front/pack', ['p' => $p, 'groupsWithPack' => $groups, 'title' => $p['name'], 'description' => $p['tagline'] . ' — ' . $p['summary']]);
}

function front_organizers(): void
{
    $commune = trim((string)input('commune', ''));
    $existing = $commune !== '' ? db()->one("SELECT name, slug, commune FROM `groups` WHERE status = 'active' AND commune = ?", [$commune]) : null;
    view('front/organizers', ['commune' => $commune, 'existing' => $existing, 'u' => Auth::user(), 'title' => 'Collectivités, entreprises, associations',
        'bodyClass' => 'has-hero', 'bestPct' => Packs::bestSavingPct(Packs::all(null, true)),
        'description' => 'Lancez « Les Jardins d\'à Côté » de votre commune ou de votre entreprise : des packs de graines et de plants à 30 € pour vos habitants ou salariés, sans stock ni budget.']);
}

function front_organizers_submit(): void
{
    $commune = mb_substr(trim((string)input('commune', '')), 0, 120);
    $email = mb_strtolower(trim((string)input('contact_email', '')));
    if ($commune === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || trim((string)input('contact_name')) === '') {
        keep_old();
        flash('error', 'Merci d\'indiquer la commune, votre nom et un email valide.');
        redirect('/lancer-un-groupe#proposer');
    }
    $type = array_key_exists((string)input('organizer_type'), Cat::ORGANIZERS) ? (string)input('organizer_type') : 'habitants';
    $name = Groups::defaultName($commune);
    $id = db()->insert('groups', [
        'slug' => Groups::uniqueSlug($name), 'name' => $name, 'commune' => $commune, 'postal_code' => mb_substr((string)input('postal_code', ''), 0, 10),
        'organizer_type' => $type, 'organizer_name' => mb_substr((string)input('organizer_name', ''), 0, 160) ?: null,
        'contact_name' => mb_substr((string)input('contact_name'), 0, 120), 'contact_email' => $email, 'contact_phone' => mb_substr((string)input('contact_phone', ''), 0, 30) ?: null,
        'pickup_place' => mb_substr((string)input('pickup_place', ''), 0, 160) ?: null,
        'description' => mb_substr((string)input('description', ''), 0, 2000) ?: null,
        'status' => 'proposed', 'proposed_by' => Auth::id(),
    ]);
    if (input('volunteer') && Auth::id()) db()->exec("INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES (?, ?, 'volunteer')", [$id, Auth::id()]);
    foreach (db()->col("SELECT email FROM users WHERE role = 'admin'") as $a) {
        Mailer::queue($a, 'Nouvelle proposition de groupe : ' . $commune, Mailer::layout('Proposition de groupe',
            '<p><strong>' . e($name) . '</strong> proposé par ' . e((string)input('contact_name')) . ' (' . e(Cat::ORGANIZERS[$type]) . ($type && input('organizer_name') ? ' — ' . e((string)input('organizer_name')) : '') . ').</p>'
            . '<p>' . e($email) . ' · ' . e((string)input('contact_phone')) . '</p><p>' . nl2br(e((string)input('description'))) . '</p>',
            'Valider dans le back-office', abs_url('/admin/groupes/' . $id)));
    }
    Mailer::queue($email, 'Votre proposition de groupe à ' . $commune, Mailer::layout('Merci pour votre proposition',
        '<p>Bonjour ' . e((string)input('contact_name')) . ',</p><p>Nous avons bien reçu votre proposition pour <strong>' . e($name) . '</strong>. Nous vous recontactons très vite pour caler le lieu de retrait et le calendrier.</p>'
        . '<p>Une fois validé, le groupe apparaîtra sur le site avec le statut « À la recherche de bénévoles ».</p>', 'Voir les groupes', abs_url('/groupes')));
    audit('group.proposed', $name);
    flash('success', 'Merci ! Votre proposition est envoyée. Nous vous recontactons sous quelques jours.');
    redirect('/lancer-un-groupe');
}

function front_static(string $page): void
{
    $titles = ['how' => 'Comment ça marche', 'cgv' => 'Conditions générales', 'legal' => 'Mentions légales', 'privacy' => 'Données personnelles'];
    view('front/' . $page, ['title' => $titles[$page] ?? '']);
}

function front_credits(): void
{
    view('front/credits', ['photos' => Photo::credits(), 'title' => 'Crédits photos']);
}

function front_qr_png(string $file): void
{
    $o = Orders::findByToken(preg_replace('/\.png$/', '', $file));
    if (!$o) abort(404);
    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=86400');
    echo QR::png(abs_url('/r/' . $o['pickup_token']), 8);
    exit;
}

function front_pass_redirect(string $token): void
{
    redirect(Auth::isVolunteer() ? '/benevoles/distribution/' . $token : '/retrait/' . $token);
}

function front_pass(string $token): void
{
    $o = Orders::findByToken($token);
    if (!$o) abort(404, 'Ce Pass Retrait est introuvable.');
    view('front/pass', ['o' => $o, 'title' => 'Pass Retrait ' . $o['reference'], 'head' => '<meta name="robots" content="noindex">']);
}

function front_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /benevoles\nDisallow: /compte\nDisallow: /retrait\nDisallow: /panier\nDisallow: /commande\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    exit;
}

function front_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = ['/', '/groupes', '/comment-ca-marche', '/lancer-un-groupe'];
    foreach (db()->col("SELECT slug FROM `groups` WHERE status = 'active'") as $s) $urls[] = '/groupes/' . $s;
    foreach (db()->col('SELECT slug FROM packs WHERE is_active = 1') as $s) $urls[] = '/packs/' . $s;
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) echo '<url><loc>' . e(abs_url($u)) . '</loc></url>';
    echo '</urlset>';
    exit;
}
