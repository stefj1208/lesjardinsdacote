<?php
declare(strict_types=1);

/**
 * Données de démonstration : packs avec photos (Wikimedia Commons), 4 groupes à
 * différents stades (bénévoles recherchés, pré-ouverture, ventes ouvertes) et
 * une proposition de groupe à valider. Tout est supprimable en un clic.
 */
final class Demo
{
    /** Prix public d'un sachet de graines bio en jardinerie (La Semence Bio, marque grand public d'Agrosemens, relevé novembre 2026). */
    public const MARKET = 3.30;
    public const MARKET_NOTE = "Prix marché : 3,30 € le sachet, prix public des sachets de graines bio du même semencier (La Semence Bio, marque grand public d'Agrosemens) chez Truffaut et Nature & Découvertes, relevé en novembre 2026. Une variété = un sachet.";

    /** Anciens packs de démonstration (v2), supprimés lors de la mise à jour. */
    public const OLD_PACKS = ["Potager d'été gourmand", 'Potager facile pour débuter', 'Balcon & petits espaces', 'Herbes du Sud', "Plants d'été", 'Verger familial'];

    /**
     * Packs : [univers, nom, accroche, prix, photo, variétés].
     * Variété : [plante, variété, nom latin, quantité, unité, conseil, photo]. Variétés rares du catalogue Agrosemens (semences bio).
     */
    public const PACKS = [
        ['seeds', 'Tomates de collection', "14 tomates anciennes introuvables en jardinerie : noires, blanches, orange, zébrées et cerises de couleur. 10 graines de chaque.", 30,
            'commons:Heirloom_tomatoes_2.jpg|commons:Tomates_anciennes.jpg', [
            ['Tomate noire', 'Black from Tula', 'Solanum lycopersicum', 10, 'graines', 'Originaire de Tula, en Russie : 200 à 400 g, chair douce et très charnue', 'commons:Black_Beauty_tomat.jpg|commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
            ['Tomate noire', 'Bedouin', 'Solanum lycopersicum', 10, 'graines', 'Fruits en forme de poire, rouge-noir, 120 à 180 g', 'commons:Seattle-Heirloom-tomato-2156.jpg|commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
            ['Tomate noire', 'Noire russe charbonneuse', 'Solanum lycopersicum', 10, 'graines', 'Précoce (70 jours), chocolat foncé, sucrée et sans acidité', 'commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
            ['Tomate bicolore', 'Ananas noire', 'Solanum lycopersicum', 10, 'graines', 'Jusqu\'à 500 g, chair verte et rouge, saveur fruitée et épicée', 'commons:Seattle-Heirloom-tomato-2155.jpg|commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate violette', 'Indigo blue', 'Solanum lycopersicum', 10, 'graines', 'Épaules violet-noir riches en anthocyanes', 'commons:Indigo_Rose_Tomatoes.jpg|commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate blanche', 'Beauté blanche', 'Solanum lycopersicum', 10, 'graines', 'Ivoire rosé, 100 à 250 g, très douce', 'commons:Capay_heirloom_tomatoes_at_Slow_Food_Nation.jpg|commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate orange', 'Verna', 'Solanum lycopersicum', 10, 'graines', 'Cœur de bœuf orange de 200 à 500 g, peu de graines, saveur exceptionnelle', 'commons:Tomatoes_for_sale_in_France.jpg|commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate orange', 'La Carotina', 'Solanum lycopersicum', 10, 'graines', 'Jaune-orangé, grappes de 5 à 6 fruits parfumés', 'commons:Capay_heirloom_tomatoes_at_Slow_Food_Nation.jpg|commons:Tomates_anciennes.jpg'],
            ['Tomate zébrée', 'Red zébra', 'Solanum lycopersicum', 10, 'graines', 'Rouge striée d\'or, aussi belle que bonne', 'commons:Heirloom_tomatoes_2.jpg'],
            ['Tomate rouge', 'Russe rouge', 'Solanum lycopersicum', 10, 'graines', 'Géante de 400 g à 1,5 kg, parfaite pour les sauces et les farcis', 'commons:Tomates_anciennes.jpg'],
            ['Tomate rouge', 'Belmonte', 'Solanum lycopersicum', 10, 'graines', 'Grosse tomate côtelée d\'origine italienne, très charnue', 'commons:Tomato.jpg|commons:Tomates_anciennes.jpg'],
            ['Tomate cerise', 'Black cherry', 'Solanum lycopersicum var. cerasiforme', 10, 'graines', 'Cerises rouge-noir de 20 g, sucrées, précoces', 'commons:Tomate_Black_Cherry_15671.jpg|commons:Cherry_tomatoes.jpg'],
            ['Tomate cerise', 'Gold nugget', 'Solanum lycopersicum var. cerasiforme', 10, 'graines', 'Pépites dorées de 10 g, récolte en 60 jours', 'commons:Cherry_tomatoes.jpg|commons:Cherry_Tomato_on_Vine.JPG'],
            ['Tomate cerise', 'Green envy', 'Solanum lycopersicum var. cerasiforme', 10, 'graines', 'Verte virant au jaune pâle, juteuse, ne se fend pas', 'commons:Green_cherry_tomatoes.JPG|commons:Green_cherry_tomatoes_on_vine.JPG|commons:Cherry_tomatoes.jpg'],
        ]],
        ['seeds', 'Ratatouille du monde', "Aubergines zébrées et blanches, poivrons et piments de Grèce, du Brésil ou du Pays basque : 15 variétés pour un potager du Sud.", 30,
            'commons:Aubergines_Three_Types.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg', [
            ['Aubergine', 'Violette de Toulouse', 'Solanum melongena', 10, 'graines', 'La variété de chez nous, demi-longue et violet profond', 'commons:Purple_Eggplant.JPG|commons:Aubergine.jpg|commons:Aubergines_Three_Types.jpg'],
            ['Aubergine zébrée', 'Tsakoniki', 'Solanum melongena', 10, 'graines', 'Ancienne variété grecque, violette striée de blanc, sans amertume', 'commons:Aubergines_Three_Types.jpg|commons:Solanum_melongena_ja001.jpg'],
            ['Aubergine zébrée', 'Fantazia', 'Solanum melongena', 10, 'graines', 'Striée de violet et de blanc', 'commons:Solanum_melongena_ja001.jpg|commons:Aubergines_Three_Types.jpg'],
            ['Aubergine zébrée', 'Zébrina', 'Solanum melongena', 10, 'graines', 'Petits fruits striés, très productive', 'commons:Solanum_melongena_24_08_2012_(1).JPG|commons:Aubergines_Three_Types.jpg'],
            ['Aubergine blanche', 'Dourga', 'Solanum melongena', 10, 'graines', 'Longue et blanche, chair fine', 'commons:Aubergines_Three_Types.jpg'],
            ['Aubergine blanche', 'Blanche ronde à œuf', 'Solanum melongena', 10, 'graines', 'Des fruits blancs de la taille d\'un œuf', 'commons:Solanum_ovigerum.JPG|commons:Aubergines_Three_Types.jpg'],
            ['Aubergine verte', 'Louisiana long green', 'Solanum melongena', 10, 'graines', 'Longue, vert pâle, originaire de Louisiane', 'commons:Solanum-melongena-024.JPG|commons:Aubergines_Three_Types.jpg'],
            ['Poivron', 'di Capriglio', 'Capsicum annuum', 10, 'graines', 'Petit poivron ancien du Piémont', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
            ['Poivron', 'Mandarine', 'Capsicum annuum', 10, 'graines', 'Petits fruits orange vif, très sucrés', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg|commons:Green_capsicum,_sweet_bell_pepper.jpg'],
            ['Poivron', 'Tomate', 'Capsicum annuum', 10, 'graines', 'Fruits plats et côtelés en forme de tomate', 'commons:Green_capsicum,_sweet_bell_pepper.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
            ['Poivron', 'Doux long des Landes', 'Capsicum annuum', 10, 'graines', 'Long et fin, à faire frire', 'commons:Green_capsicum,_sweet_bell_pepper.jpg'],
            ['Piment doux', 'Biquinho', 'Capsicum chinense', 10, 'graines', 'Du Brésil : petites gouttes rouges sucrées, à peine piquantes (1/10)', 'commons:Habanero_chile_-_flower_with_fruit_(aka).jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
            ['Piment', 'Basque Gorria', 'Capsicum annuum', 10, 'graines', 'Le piment d\'Espelette : conique, rouge, force 4/10', "commons:France-Piment_d'Espelette-2005-08-05.jpg|commons:France-AOC_Piment_d'Espelette-2005-08-05.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg"],
            ['Piment', 'Sucette de Provence', 'Capsicum annuum', 10, 'graines', 'Effilé, vert puis rouge vif, force 4 à 7/10', "commons:France-AOC_Piment_d'Espelette-2005-08-05.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg"],
            ['Piment fort', 'Habanero golden', 'Capsicum chinense', 10, 'graines', 'Jaune vif, fruité et très piquant (10/10)', 'commons:Habanero_pepper.jpg|commons:Piment_habanero.jpg|commons:Habanero_pepers.jpg'],
        ]],
        ['seeds', 'Courges & curiosités', "Courges anciennes, courgettes de couleur, concombres citron ou arménien, melon et haricots violets : 13 variétés qui changent du potager classique.", 30,
            "commons:Pumpkin_'Sweet_Dumpling'_01.jpg|commons:Pumpkin_seeds_in_hand.jpg", [
            ['Courge', 'Melonette jaspée de Vendée', 'Cucurbita pepo', 10, 'graines', 'Ancienne variété française, chair orange très sucrée', 'commons:Cucurbita_pepo_12zz.jpg|commons:Pumpkin_seeds_in_hand.jpg'],
            ['Courge', 'Sweet Dumpling (patidou)', 'Cucurbita pepo', 10, 'graines', 'Petite courge striée au goût de châtaigne', "commons:Pumpkin_'Sweet_Dumpling'_01.jpg|commons:Chinese_Sweet_Dumpling.JPG|commons:Pumpkin_seeds_in_hand.jpg"],
            ['Courge', 'Musquée de Provence', 'Cucurbita moschata', 10, 'graines', 'Grosse courge côtelée à chair orange, de longue conservation', 'commons:Musquee_de_Provence.jpg|commons:Cucurbita_moschata_Musquée_de_Provence_-_Une_belle_musquee.jpg|commons:Pumpkin_seeds_in_hand.jpg'],
            ['Courgette', 'Ronde de Nice', 'Cucurbita pepo', 10, 'graines', 'Ronde et vert pâle, idéale à farcir', 'commons:Courgette_J1.JPG|commons:Courgette.jpg'],
            ['Courgette', 'Jaune', 'Cucurbita pepo', 10, 'graines', 'Allongée, jaune d\'or, très décorative', 'commons:Cucurbita_pepo_var_cylindrica,_Yellow_Zucchini_Squash_(crop).jpg|commons:Courgette.jpg'],
            ['Courgette', 'Yellow Straight Neck', 'Cucurbita pepo', 10, 'graines', 'Jaune à col droit, variété américaine ancienne', 'commons:Cucurbita_pepo_Summer_Squash_Yellow_and_Green_Zucchini_zucchine.jpg|commons:Courgette.jpg'],
            ['Courgette', "Verte petite d'Alger", 'Cucurbita pepo', 10, 'graines', 'Vert clair, très productive', 'commons:Courgette.jpg|commons:Courgette_J1.JPG'],
            ['Concombre', 'Arménien', 'Cucumis melo var. flexuosus', 10, 'graines', 'Jusqu\'à 1 m de long, goût de melon, jamais amer', 'commons:Armenian_cucumber_cross-section.JPG|commons:Dar_cucumber_seedlings.jpg'],
            ['Concombre', 'Lemon', 'Cucumis sativus', 10, 'graines', 'Ancienne variété d\'Inde, ronde et jaune comme un citron', 'commons:Lemon_cucumber_J1.JPG|commons:Dar_cucumber_seedlings.jpg'],
            ['Concombre', "Rollison's Telegraph", 'Cucumis sativus', 10, 'graines', 'Long concombre anglais du XIXe siècle', 'commons:Dar_cucumber_seedlings.jpg'],
            ['Melon', 'Vieille France', 'Cucumis melo', 10, 'graines', 'Charentais brodé, chair orange très sucrée', 'commons:Charentais-Melone.jpg|commons:Cantaloupe.jpg|commons:Pumpkin_seeds_in_hand.jpg'],
            ['Haricot à rames', 'Blauhilde', 'Phaseolus vulgaris', 10, 'graines', 'Gousses violettes qui verdissent à la cuisson', 'commons:Four_Different_Varieties_of_Green_Beans_(Phaseolus_vulgaris).jpg|commons:Green_beans.jpg'],
            ['Haricot nain', 'Purple Queen', 'Phaseolus vulgaris', 10, 'graines', 'Mangetout violet, sans fil', 'commons:Four_Different_Varieties_of_Green_Beans_(Phaseolus_vulgaris).jpg|commons:Gruene_Bohnen.jpg'],
        ]],
        ['aromatics', 'Herbes rares du monde', "Basilics sacré, cannelle, citron et thaï, agastache, hysope, sarriette : 12 aromatiques rares, au gramme, pour des années de semis.", 30,
            'commons:Thai_basil.jpg|commons:Basil_leaves.jpg', [
            ['Basilic sacré', 'Tulsi', 'Ocimum tenuiflorum', 1, 'g', 'Plante sacrée en Inde, parfum de clou de girofle', 'commons:Ocimum_tenuiflorum.jpg|commons:Tulsi_(2070558022).jpg|commons:Basil_leaves.jpg'],
            ['Basilic', 'Cannelle', 'Ocimum basilicum', 1, 'g', 'Tiges pourpres et parfum de cannelle', 'commons:Thai_basil_with_flowers.jpg|commons:Basil_leaves.jpg'],
            ['Basilic', 'Citron', 'Ocimum × citriodorum', 1, 'g', 'Arôme de citron, parfait avec le poisson', 'commons:Lemon_Basil.jpg|commons:Basil_leaves.jpg'],
            ['Basilic', 'Thaï', 'Ocimum basilicum var. thyrsiflora', 1, 'g', 'Anisé, indispensable de la cuisine asiatique', 'commons:Thai_basil.jpg|commons:Basil_leaves.jpg'],
            ['Basilic', 'Réglisse', 'Ocimum basilicum', 1, 'g', 'Notes douces de réglisse', 'commons:Thai_basil_flower.jpg|commons:Basil_leaves.jpg'],
            ['Basilic', 'Rouge Osmin', 'Ocimum basilicum', 1, 'g', 'Feuillage pourpre presque noir', 'commons:Ocimum_basilicum_001.JPG|commons:Basil_leaves.jpg'],
            ['Basilic', 'Grec', 'Ocimum basilicum var. minimum', 1, 'g', 'Boule compacte à petites feuilles, idéal en pot', 'commons:Pot_of_basil_sprouts_(Ocimum_basilicum)_-_20050422.jpg|commons:Basil_leaves.jpg'],
            ['Thym', "d'hiver", 'Thymus vulgaris', 1, 'g', 'Fleurs rose pâle, en cuisine ou en tisane', 'commons:Fresh_thyme.jpg|commons:Thyme.jpg'],
            ['Agastache', 'anisée', 'Agastache foeniculum', 1, 'g', 'Feuilles au goût d\'anis, fleurs adorées des abeilles', 'commons:Agastache_foeniculum.jpg|commons:Fresh_thyme.jpg'],
            ['Hysope', 'officinale', 'Hyssopus officinalis', 1, 'g', 'Vivace mellifère à la saveur mentholée', 'commons:Hyssopus_officinalis.jpg|commons:Hyssopus_officinalis_004.JPG|commons:Rosemary_Plant.jpg'],
            ['Sarriette', 'vivace', 'Satureja montana', 1, 'g', 'L\'herbe des haricots, résiste à la sécheresse', 'commons:Satureja_montana_1.JPG|commons:Satureja_montana_3c.JPG|commons:Thyme.jpg'],
            ['Coriandre', 'à petites graines', 'Coriandrum sativum', 3, 'g', 'Pour les feuilles comme pour les graines', 'commons:Coriandrum_sativum_003.JPG|commons:A_scene_of_Coriander_leaves.JPG|commons:Basil_leaves.jpg'],
        ]],
    ];

    public static function seed(): void
    {
        $db = db();
        $packIds = [];
        foreach (self::PACKS as [$cat, $name, $tag, $price, $photo, $items]) {
            $id = $db->insert('packs', ['slug' => Packs::uniqueSlug($name), 'category' => $cat, 'name' => $name, 'tagline' => $tag, 'price' => $price,
                'photo' => $photo, 'description' => $tag . "\n\nSemences biologiques et reproductibles de variétés rares, achetées en gros conditionnement chez Agrosemens, semencier bio installé à Rousset (Bouches-du-Rhône). Les bénévoles les répartissent en sachets étiquetés avec la variété et le numéro de lot d'origine."]);
            foreach ($items as $i => [$n, $v, $l, $q, $u, $note, $ph]) {
                $db->insert('pack_items', ['pack_id' => $id, 'position' => $i, 'name' => $n, 'variety' => $v, 'latin_name' => $l, 'quantity' => $q, 'unit' => $u,
                    'market_price' => self::MARKET, 'note' => $note ?: null, 'photo' => $ph]);
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
        set_setting('market_price_note', self::MARKET_NOTE);
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
            $names = array_merge(array_map(fn($p) => $p[1], self::PACKS), self::OLD_PACKS);
            $in = implode(',', array_fill(0, count($names), '?'));
            $db->exec("DELETE FROM packs WHERE name IN ($in) AND id NOT IN (SELECT pack_id FROM sale_packs) AND id NOT IN (SELECT pack_id FROM orders)", $names);
        });
        set_setting('demo_data', '0');
    }
}
