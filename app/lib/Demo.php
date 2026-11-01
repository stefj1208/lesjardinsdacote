<?php
declare(strict_types=1);

/**
 * Données de démonstration : packs avec photos (Wikimedia Commons), 4 groupes à
 * différents stades (bénévoles recherchés, pré-ouverture, ventes ouvertes) et
 * une proposition de groupe à valider. Tout est supprimable en un clic.
 */
final class Demo
{
    public const PACKS = [
        ['seeds', "Potager d'été gourmand", 'Tomates, courgettes, haricots et basilic : de quoi remplir les paniers de juillet à septembre.', 30,
            'commons:Heirloom_tomatoes_2.jpg|commons:Tomates_anciennes.jpg', [
            ['Tomate', 'Cœur de bœuf', 'Solanum lycopersicum', 30, 'graines', 'Grosse tomate charnue, semis au chaud en mars', 'commons:Tomates_anciennes.jpg|commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate cerise', 'Rouge', 'Solanum lycopersicum var. cerasiforme', 30, 'graines', 'Très productive, idéale à grignoter', 'commons:Cherry_Tomato_on_Vine.JPG|commons:Cherry_tomatoes.jpg'],
            ['Courgette', 'Noire maraîchère', 'Cucurbita pepo', 15, 'graines', 'Deux pieds suffisent pour une famille', 'commons:Courgette.jpg|commons:Courgette_J1.JPG'],
            ['Haricot nain', 'Contender', 'Phaseolus vulgaris', 120, 'graines', 'Sans fil, semis en place en mai', 'commons:Green_beans.jpg|commons:Gruene_Bohnen.jpg'],
            ['Poivron', "Doux d'Espagne", 'Capsicum annuum', 25, 'graines', 'Aime la chaleur toulousaine', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg|commons:Green_capsicum,_sweet_bell_pepper.jpg'],
            ['Basilic', 'Grand vert', 'Ocimum basilicum', 300, 'graines', 'Au pied des tomates, il les protège', 'commons:Basil_leaves.jpg|commons:Ocimum_basilicum_001.JPG'],
        ]],
        ['seeds', 'Potager facile pour débuter', 'Des légumes qui lèvent vite et pardonnent tout : parfait pour un premier potager ou avec des enfants.', 30,
            'commons:Radishes_fresh_from_the_garden.jpg|commons:Raphanus_sativus,_red_radish_variant.jpg', [
            ['Radis', '18 jours', 'Raphanus sativus', 500, 'graines', 'Récolte trois semaines après le semis', 'commons:Radishes_fresh_from_the_garden.jpg|commons:Raphanus_sativus,_red_radish_variant.jpg'],
            ['Laitue', 'Merveille des 4 saisons', 'Lactuca sativa', 400, 'graines', 'Se sème presque toute l\'année', 'commons:Buttercrunch_butterhead_lettuce.jpg|commons:Grand_Rapids_Lettuce.jpg'],
            ['Carotte', 'de Nantes', 'Daucus carota', 800, 'graines', 'Sucrée, sans cœur', 'commons:Carrot_harvest.jpg|commons:Bunches_of_carrots.jpg'],
            ['Haricot nain', 'Contender', 'Phaseolus vulgaris', 120, 'graines', 'Les enfants adorent les récolter', 'commons:Green_beans.jpg|commons:Gruene_Bohnen.jpg'],
            ['Courgette', 'Noire maraîchère', 'Cucurbita pepo', 15, 'graines', 'Productive et sans souci', 'commons:Courgette.jpg|commons:Courgette_J1.JPG'],
        ]],
        ['seeds', 'Balcon & petits espaces', 'Des variétés compactes pour jardinières et pots : un vrai potager sur 2 m² de balcon.', 30,
            'commons:Pot_of_basil_sprouts_(Ocimum_basilicum)_-_20050422.jpg|commons:Basil_leaves.jpg', [
            ['Tomate cerise', 'Rouge', 'Solanum lycopersicum var. cerasiforme', 30, 'graines', 'En pot de 20 litres, avec un tuteur', 'commons:Cherry_Tomato_on_Vine.JPG|commons:Cherry_tomatoes.jpg'],
            ['Basilic', 'Grand vert', 'Ocimum basilicum', 300, 'graines', 'En jardinière, au soleil', 'commons:Basil_leaves.jpg|commons:Ocimum_basilicum_001.JPG'],
            ['Laitue à couper', 'Feuille de chêne', 'Lactuca sativa', 400, 'graines', 'Se recoupe plusieurs fois', 'commons:Grand_Rapids_Lettuce.jpg|commons:Buttercrunch_butterhead_lettuce.jpg'],
            ['Radis', '18 jours', 'Raphanus sativus', 300, 'graines', 'Parfait en bac peu profond', 'commons:Radishes_fresh_from_the_garden.jpg'],
            ['Poivron', "Doux d'Espagne", 'Capsicum annuum', 20, 'graines', 'Un pied par pot de 10 litres', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
        ]],
        ['aromatics', 'Herbes du Sud', 'Thym, romarin, basilic et ciboulette en godets : un coin d\'aromatiques prêt à planter.', 30,
            'commons:Rosemary_Plant.jpg|commons:Fresh_thyme.jpg', [
            ['Thym', 'commun', 'Thymus vulgaris', 2, 'godets', 'Plein soleil, sol sec', 'commons:Fresh_thyme.jpg|commons:Thyme.jpg'],
            ['Romarin', 'officinal', 'Salvia rosmarinus', 2, 'godets', 'Résiste à la sécheresse', 'commons:Rosemary_Plant.jpg|commons:Rosemary.jpg'],
            ['Basilic', 'Grand vert', 'Ocimum basilicum', 2, 'godets', 'À rentrer avant les premiers froids', 'commons:Basil_leaves.jpg'],
        ]],
        ['vegetables', "Plants d'été", 'Des plants vigoureux à repiquer mi-mai : tomates, courgettes et concombres.', 30,
            'commons:Dar_cucumber_seedlings.jpg|commons:Seedling_planting.jpg', [
            ['Tomate', 'Cœur de bœuf', 'Solanum lycopersicum', 4, 'plants', 'Plants greffés', 'commons:Tomates_anciennes.jpg'],
            ['Courgette', 'Noire maraîchère', 'Cucurbita pepo', 2, 'plants', '', 'commons:Courgette.jpg'],
            ['Concombre', 'Marketer', 'Cucumis sativus', 2, 'plants', '', 'commons:Dar_cucumber_seedlings.jpg'],
        ]],
        ['fruit_trees', 'Verger familial', 'Un pommier et un poirier de variétés anciennes, en racines nues, pour planter cet hiver.', 30,
            'commons:Apple_orchard.jpg|commons:Piper-Orchard-Old-apple-tree-3369.jpg', [
            ['Pommier', 'Reinette grise du Canada', 'Malus domestica', 1, 'arbre', 'Racines nues, 2 ans', 'commons:Piper-Orchard-Old-apple-tree-3369.jpg|commons:Apple_orchard.jpg'],
            ['Poirier', 'Williams', 'Pyrus communis', 1, 'arbre', 'Racines nues, 2 ans', 'commons:Apple_orchard.jpg'],
        ]],
    ];

    public static function seed(): void
    {
        $db = db();
        $packIds = [];
        foreach (self::PACKS as [$cat, $name, $tag, $price, $photo, $items]) {
            $id = $db->insert('packs', ['slug' => Packs::uniqueSlug($name), 'category' => $cat, 'name' => $name, 'tagline' => $tag, 'price' => $price,
                'photo' => $photo, 'description' => $tag . "\n\nGraines bio et reproductibles, conditionnées par les bénévoles avec le numéro de lot d'origine."]);
            foreach ($items as $i => [$n, $v, $l, $q, $u, $note, $ph]) {
                $db->insert('pack_items', ['pack_id' => $id, 'position' => $i, 'name' => $n, 'variety' => $v, 'latin_name' => $l, 'quantity' => $q, 'unit' => $u, 'note' => $note ?: null, 'photo' => $ph]);
            }
            $packIds[] = $id;
        }
        [$p1, $p2, $p3] = $packIds;

        $names = [['Camille', 'Arnaud'], ['Yanis', 'Benoît'], ['Léa', 'Cazes'], ['Hugo', 'Delmas'], ['Inès', 'Esteve'], ['Malik', 'Fabre'],
            ['Chloé', 'Garcia'], ['Nathan', 'Huc'], ['Sarah', 'Izard'], ['Théo', 'Jourdan'], ['Manon', 'Lacoste'], ['Rayan', 'Moreau'],
            ['Julie', 'Nadal'], ['Lucas', 'Olivier'], ['Emma', 'Pujol'], ['Adam', 'Roux'], ['Zoé', 'Sabatier'], ['Noah', 'Teulier']];
        $users = [];
        foreach ($names as $i => [$f, $l]) {
            $email = 'voisin' . ($i + 1) . '@exemple.test';
            $users[] = (int)($db->val('SELECT id FROM users WHERE email = ?', [$email])
                ?: $db->insert('users', ['email' => $email, 'password_hash' => password_hash(random_token(), PASSWORD_DEFAULT), 'first_name' => $f, 'last_name' => $l]));
        }

        $groups = [
            ['Pins-Justaret', '31860', 'collectivite', 'Exemple — commune partenaire', 'Salle des fêtes', 'Place de la mairie, 31860 Pins-Justaret',
                'commons:Vegetable_garden_1.JPG|commons:Raised_bed.jpg', 'open', 3],
            ['Pinsaguel', '31120', 'jardin_partage', 'Exemple — jardin partagé du quartier', 'Jardin partagé', 'Chemin des jardins, 31120 Pinsaguel',
                'commons:Raised_vegetable_bed_at_Boreham,_Essex,_England.jpg|commons:Raised_bed.jpg', 'voting', 2],
            ['Roques', '31120', 'entreprise', 'Exemple — comité d\'entreprise', 'Hall d\'accueil', 'Zone d\'activités, 31120 Roques',
                'commons:Vegetable_garden_detail.jpg|commons:Vegetable_garden_1.JPG', 'waiting', 1],
            ['Portet-sur-Garonne', '31120', 'association', 'Exemple — association de quartier', 'Local associatif', '31120 Portet-sur-Garonne',
                'commons:George_Washington_University_Community_Garden.jpg|commons:Raised_bed.jpg', 'waiting', 0],
        ];
        $u = 0;
        foreach ($groups as [$commune, $cp, $type, $org, $place, $addr, $photo, $stage, $vol]) {
            $name = Groups::defaultName($commune);
            $gid = $db->insert('groups', ['slug' => Groups::uniqueSlug($name), 'name' => $name, 'commune' => $commune, 'postal_code' => $cp,
                'organizer_type' => $type, 'organizer_name' => $org, 'pickup_place' => $place, 'pickup_address' => $addr, 'photo' => $photo,
                'status' => 'active', 'validated_at' => now(),
                'description' => "Un groupe d'achat de voisins à $commune : on choisit ensemble un pack de saison, on commande en gros, et les bénévoles préparent les packs."]);
            $members = array_slice($users, ($u * 4) % 10, 8);
            $u++;
            foreach ($members as $k => $mid) {
                $db->exec('INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)', [$gid, $mid, $k < $vol ? 'volunteer' : 'member']);
            }
            $sid = $db->insert('sales', ['group_id' => $gid, 'category' => 'seeds', 'title' => 'Graines potagères — printemps ' . (date('Y') + 1),
                'vote_days' => 10, 'sale_days' => 21, 'batch_size' => 10, 'pickup_at' => date('Y-m-d 10:00:00', strtotime('+40 days'))]);
            Sales::setPacks($sid, [$p1, $p2, $p3]);
            if ($stage === 'voting' || $stage === 'open') {
                $db->exec("UPDATE sales SET status = 'voting', vote_starts_at = NOW() - INTERVAL 6 DAY, vote_ends_at = NOW() + INTERVAL 4 DAY WHERE id = ?", [$sid]);
                $ballot = [$p1, $p1, $p2, $p1, $p3, $p2, $p1, $p3];
                foreach ($members as $k => $mid) $db->exec('INSERT IGNORE INTO votes (sale_id, user_id, pack_id) VALUES (?, ?, ?)', [$sid, $mid, $ballot[$k % count($ballot)]]);
            }
            if ($stage === 'open') {
                $db->exec("UPDATE sales SET status = 'open', winning_pack_id = ?, vote_starts_at = NOW() - INTERVAL 16 DAY, vote_ends_at = NOW() - INTERVAL 6 DAY,
                    opened_at = NOW() - INTERVAL 6 DAY, sale_ends_at = NOW() + INTERVAL 15 DAY WHERE id = ?", [$p1, $sid]);
                $db->insert('sale_batches', ['sale_id' => $sid, 'batch_no' => 1, 'status' => 'complete', 'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]);
                $db->insert('sale_batches', ['sale_id' => $sid, 'batch_no' => 2, 'status' => 'open']);
                foreach (array_merge(array_fill(0, 10, 1), array_fill(0, 6, 2)) as $k => $batch) {
                    $buyer = $users[$k % count($users)];
                    $bu = $db->one('SELECT * FROM users WHERE id = ?', [$buyer]);
                    $db->insert('orders', ['reference' => 'DEMO-' . $sid . '-' . $k, 'user_id' => $buyer, 'sale_id' => $sid, 'pack_id' => $p1, 'quantity' => 1,
                        'unit_price' => 30, 'amount' => 30, 'batch_no' => $batch, 'status' => $batch === 1 ? 'confirmed' : 'pending',
                        'payment_mode' => 'on_pickup', 'payment_status' => $batch === 1 ? 'to_collect' : 'pending', 'from_vote' => $k < 6 ? 1 : 0,
                        'pickup_token' => random_token(16), 'pickup_code' => strtoupper(substr(random_token(4), 0, 6)),
                        'bin_code' => mb_strtoupper(mb_substr($bu['last_name'], 0, 1)), 'cgv_accepted_at' => now()]);
                }
            }
        }
        $db->insert('groups', ['slug' => Groups::uniqueSlug(Groups::defaultName('Villate')), 'name' => Groups::defaultName('Villate'), 'commune' => 'Villate',
            'postal_code' => '31860', 'organizer_type' => 'habitants', 'organizer_name' => 'Exemple — proposition d\'habitants', 'contact_name' => 'Exemple',
            'contact_email' => 'proposition@exemple.test', 'status' => 'proposed', 'description' => 'Proposition reçue depuis le formulaire « Lancer un groupe ».']);

        $first = (int)$db->val("SELECT id FROM `groups` WHERE status = 'active' ORDER BY id LIMIT 1");
        foreach ([['packing', 'Préparation des packs de graines', '+20 days 10:00', 3, 4], ['distribution', 'Distribution des packs', '+40 days 10:00', 3, 3]] as [$type, $title, $when, $h, $need]) {
            $t = strtotime($when);
            $db->insert('shifts', ['group_id' => $first, 'type' => $type, 'title' => $title, 'location' => 'Salle des fêtes, Pins-Justaret',
                'starts_at' => date('Y-m-d H:i:s', $t), 'ends_at' => date('Y-m-d H:i:s', $t + $h * 3600), 'volunteers_needed' => $need]);
        }
        set_setting('demo_data', '1');
    }

    public static function purge(): void
    {
        db()->tx(function (DB $db) {
            $db->exec("DELETE o FROM orders o JOIN sales s ON s.id = o.sale_id JOIN `groups` g ON g.id = s.group_id WHERE g.organizer_name LIKE 'Exemple —%'");
            $db->exec("DELETE o FROM orders o JOIN users u ON u.id = o.user_id WHERE u.email LIKE '%@exemple.test'");
            $db->exec("DELETE FROM `groups` WHERE organizer_name LIKE 'Exemple —%'");
            $db->exec("DELETE FROM shifts WHERE group_id IS NULL AND location = 'Salle des fêtes, Pins-Justaret'");
            $db->exec("DELETE FROM users WHERE email LIKE '%@exemple.test'");
            $names = array_map(fn($p) => $p[1], self::PACKS);
            $in = implode(',', array_fill(0, count($names), '?'));
            $db->exec("DELETE FROM packs WHERE name IN ($in) AND id NOT IN (SELECT pack_id FROM sale_packs) AND id NOT IN (SELECT pack_id FROM orders)", $names);
        });
        set_setting('demo_data', '0');
    }
}
