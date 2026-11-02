<?php
declare(strict_types=1);

/**
 * Données de démonstration : un catalogue de variétés rares (Agrosemens), 4 groupes à
 * différents stades (bénévoles recherchés, vote des variétés, ventes ouvertes) et une
 * proposition de groupe à valider. Groupes, membres et achats fictifs se suppriment en
 * un clic ; le catalogue de variétés reste, comme base de travail.
 */
final class Demo
{
    /** Prix public d'un sachet de graines bio en jardinerie (La Semence Bio, marque grand public d'Agrosemens, relevé novembre 2026). */
    public const MARKET = 3.30;
    public const MARKET_NOTE = "Prix marché : 3,30 € le sachet, prix public des sachets de graines bio du même semencier (La Semence Bio, marque grand public d'Agrosemens) chez Truffaut et Nature & Découvertes, relevé en novembre 2026. Une variété = un sachet.";

    /** TOMATE Belmonte : 3,20 € HT les 10 graines, 9,40 € HT les 100 graines (catalogue Agrosemens, novembre 2026). */
    private const RATIO_100 = 9.40 / 3.20;
    private const VAT = 1.055;

    /** Anciens packs de démonstration, supprimés lors des mises à jour. */
    public const OLD_PACKS = ["Potager d'été gourmand", 'Potager facile pour débuter', 'Balcon & petits espaces', 'Herbes du Sud', "Plants d'été", 'Verger familial',
        'Tomates de collection', 'Ratatouille du monde', 'Courges & curiosités', 'Herbes rares du monde'];

    /**
     * Graines potagères : [plante, variété, nom latin, réf. Agrosemens, prix HT des 10 graines, conseil, photo].
     * Le prix des 100 graines est connu pour Belmonte ; pour les autres il est estimé avec le même rapport.
     */
    public const SEEDS = [
        ['Tomate noire', 'Black from Tula', 'Solanum lycopersicum', 'TOM87', 1.85, 'Originaire de Tula, en Russie : 200 à 400 g, chair douce et très charnue', 'commons:Black_Beauty_tomat.jpg|commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
        ['Tomate noire', 'Bedouin', 'Solanum lycopersicum', 'TO152', 1.55, 'Fruits en forme de poire, rouge-noir, 120 à 180 g', 'commons:Seattle-Heirloom-tomato-2156.jpg|commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
        ['Tomate noire', 'Noire russe charbonneuse', 'Solanum lycopersicum', 'TOM41', 1.85, 'Précoce (70 jours), chocolat foncé, sucrée et sans acidité', 'commons:Black_krim_tomato.jpg|commons:Tomates_anciennes.jpg'],
        ['Tomate bicolore', 'Ananas noire', 'Solanum lycopersicum', 'TOM88', 2.15, 'Jusqu\'à 500 g, chair verte et rouge, saveur fruitée et épicée', 'commons:Seattle-Heirloom-tomato-2155.jpg|commons:Heirloom_tomatoes_2.jpg'],
        ['Tomate violette', 'Indigo blue', 'Solanum lycopersicum', 'TO176', 2.15, 'Épaules violet-noir riches en anthocyanes', 'commons:Indigo_Rose_Tomatoes.jpg|commons:Heirloom_tomatoes_2.jpg'],
        ['Tomate blanche', 'Beauté blanche', 'Solanum lycopersicum', 'TOM97', 1.55, 'Ivoire rosé, 100 à 250 g, très douce', 'commons:Capay_heirloom_tomatoes_at_Slow_Food_Nation.jpg|commons:Heirloom_tomatoes_2.jpg'],
        ['Tomate orange', 'Verna', 'Solanum lycopersicum', 'TO163', 1.70, 'Cœur de bœuf orange de 200 à 500 g, peu de graines, saveur exceptionnelle', 'commons:Tomatoes_for_sale_in_France.jpg|commons:Heirloom_tomatoes_2.jpg'],
        ['Tomate orange', 'La Carotina', 'Solanum lycopersicum', 'TO100', 3.20, 'Jaune-orangé, grappes de 5 à 6 fruits parfumés', 'commons:Capay_heirloom_tomatoes_at_Slow_Food_Nation.jpg|commons:Tomates_anciennes.jpg'],
        ['Tomate zébrée', 'Red zébra', 'Solanum lycopersicum', 'TO154', 2.15, 'Rouge striée d\'or, aussi belle que bonne', 'commons:Heirloom_tomatoes_2.jpg'],
        ['Tomate rouge', 'Russe rouge', 'Solanum lycopersicum', 'TO123', 1.70, 'Géante de 400 g à 1,5 kg, parfaite pour les sauces et les farcis', 'commons:Tomates_anciennes.jpg'],
        ['Tomate rose', 'Belmonte', 'Solanum lycopersicum', 'TO134', 3.20, 'Grosse tomate rose originaire de Belmonte (Italie), 300 à 500 g, douce et parfumée', 'commons:Tomato.jpg|commons:Tomates_anciennes.jpg'],
        ['Tomate cerise', 'Black cherry', 'Solanum lycopersicum var. cerasiforme', 'TOM42', 1.70, 'Cerises rouge-noir de 20 g, sucrées, précoces', 'commons:Tomate_Black_Cherry_15671.jpg|commons:Cherry_tomatoes.jpg'],
        ['Tomate cerise', 'Gold nugget', 'Solanum lycopersicum var. cerasiforme', 'TOM19', 1.55, 'Pépites dorées de 10 g, récolte en 60 jours', 'commons:Cherry_tomatoes.jpg|commons:Cherry_Tomato_on_Vine.JPG'],
        ['Tomate cerise', 'Green envy', 'Solanum lycopersicum var. cerasiforme', 'TO160', 1.55, 'Verte virant au jaune pâle, juteuse, ne se fend pas', 'commons:Green_cherry_tomatoes.JPG|commons:Green_cherry_tomatoes_on_vine.JPG|commons:Cherry_tomatoes.jpg'],
        ['Aubergine', 'Violette de Toulouse', 'Solanum melongena', 'AUB32', 1.85, 'La variété de chez nous, demi-longue et violet profond', 'commons:Purple_Eggplant.JPG|commons:Aubergine.jpg|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine', 'Rosa Bianca', 'Solanum melongena', 'AUB02', 1.45, 'Ronde, rose lavande et blanc, chair crémeuse', 'commons:Aubergine.jpg|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine', 'Slim Jim', 'Solanum melongena', 'AUB29', 1.45, 'Petits fruits fins et violets, idéale en pot', 'commons:Solanum-melongena-024.JPG|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine zébrée', 'Tsakoniki', 'Solanum melongena', 'AUB37', 2.25, 'Ancienne variété grecque, violette striée de blanc, sans amertume', 'commons:Aubergines_Three_Types.jpg|commons:Solanum_melongena_ja001.jpg'],
        ['Aubergine zébrée', 'Fantazia', 'Solanum melongena', 'AUB43', 2.25, 'Striée de violet et de blanc', 'commons:Solanum_melongena_ja001.jpg|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine zébrée', 'Zébrina', 'Solanum melongena', 'AUB15', 2.80, 'Petits fruits striés, très productive', 'commons:Solanum_melongena_24_08_2012_(1).JPG|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine blanche', 'Dourga', 'Solanum melongena', 'AUB13', 1.85, 'Longue et blanche, chair fine', 'commons:Aubergines_Three_Types.jpg'],
        ['Aubergine blanche', 'Blanche ronde à œuf', 'Solanum melongena', 'AUB30', 1.45, 'Des fruits blancs de la taille d\'un œuf', 'commons:Solanum_ovigerum.JPG|commons:Aubergines_Three_Types.jpg'],
        ['Aubergine verte', 'Louisiana long green', 'Solanum melongena', 'AUB08', 2.80, 'Longue, vert pâle, originaire de Louisiane', 'commons:Solanum-melongena-024.JPG|commons:Aubergines_Three_Types.jpg'],
        ['Poivron', 'di Capriglio', 'Capsicum annuum', 'POV31', 1.65, 'Petit poivron ancien du Piémont', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
        ['Poivron', 'Mandarine', 'Capsicum annuum', 'POV26', 2.90, 'Petits fruits orange vif, très sucrés', 'commons:Capsicum_annuum_fruits_IMGP0049.jpg|commons:Green_capsicum,_sweet_bell_pepper.jpg'],
        ['Poivron', 'Tomate', 'Capsicum annuum', 'POV15', 1.95, 'Fruits plats et côtelés en forme de tomate', 'commons:Green_capsicum,_sweet_bell_pepper.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
        ['Poivron', 'Doux long des Landes', 'Capsicum annuum', 'POV12', 1.95, 'Long et fin, à faire frire', 'commons:Green_capsicum,_sweet_bell_pepper.jpg'],
        ['Piment doux', 'Biquinho', 'Capsicum chinense', 'PIM19', 3.45, 'Du Brésil : petites gouttes rouges sucrées, à peine piquantes (1/10)', 'commons:Habanero_chile_-_flower_with_fruit_(aka).jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg'],
        ['Piment', 'Basque Gorria', 'Capsicum annuum', 'PIM14', 2.00, 'Le piment d\'Espelette : conique, rouge, force 4/10', "commons:France-Piment_d'Espelette-2005-08-05.jpg|commons:France-AOC_Piment_d'Espelette-2005-08-05.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg"],
        ['Piment', 'Sucette de Provence', 'Capsicum annuum', 'POV33', 2.05, 'Effilé, vert puis rouge vif, force 4 à 7/10', "commons:France-AOC_Piment_d'Espelette-2005-08-05.jpg|commons:Capsicum_annuum_fruits_IMGP0049.jpg"],
        ['Piment fort', 'Habanero golden', 'Capsicum chinense', 'PIM11', 6.70, 'Jaune vif, fruité et très piquant (10/10)', 'commons:Habanero_pepper.jpg|commons:Piment_habanero.jpg|commons:Habanero_pepers.jpg'],
        ['Courge', 'Melonette jaspée de Vendée', 'Cucurbita pepo', 'POT47', 2.60, 'Ancienne variété française, chair orange très sucrée', 'commons:Cucurbita_pepo_12zz.jpg|commons:Pumpkin_seeds_in_hand.jpg'],
        ['Courge', 'Sweet Dumpling (patidou)', 'Cucurbita pepo', 'POT10', 2.40, 'Petite courge striée au goût de châtaigne', "commons:Pumpkin_'Sweet_Dumpling'_01.jpg|commons:Chinese_Sweet_Dumpling.JPG|commons:Pumpkin_seeds_in_hand.jpg"],
        ['Courgette', 'Ronde de Nice', 'Cucurbita pepo', 'COU01', 2.35, 'Ronde et vert pâle, idéale à farcir', 'commons:Courgette_J1.JPG|commons:Courgette.jpg'],
        ['Courgette', 'Jaune', 'Cucurbita pepo', 'COU38', 4.60, 'Allongée, jaune d\'or, très décorative', 'commons:Cucurbita_pepo_var_cylindrica,_Yellow_Zucchini_Squash_(crop).jpg|commons:Courgette.jpg'],
        ['Courgette', 'Yellow Straight Neck', 'Cucurbita pepo', 'COU09', 2.05, 'Jaune à col droit, variété américaine ancienne', 'commons:Cucurbita_pepo_Summer_Squash_Yellow_and_Green_Zucchini_zucchine.jpg|commons:Courgette.jpg'],
        ['Courgette', "Verte petite d'Alger", 'Cucurbita pepo', 'COU36', 2.65, 'Vert clair, très productive', 'commons:Courgette.jpg|commons:Courgette_J1.JPG'],
        ['Concombre', 'Arménien', 'Cucumis melo var. flexuosus', 'CON14', 3.40, 'Jusqu\'à 1 m de long, goût de melon, jamais amer', 'commons:Armenian_cucumber_cross-section.JPG|commons:Dar_cucumber_seedlings.jpg'],
        ['Concombre', 'Lemon', 'Cucumis sativus', 'CON26', 2.65, 'Ancienne variété d\'Inde, ronde et jaune comme un citron', 'commons:Lemon_cucumber_J1.JPG|commons:Dar_cucumber_seedlings.jpg'],
        ['Concombre', "Rollison's Telegraph", 'Cucumis sativus', 'CON22', 3.40, 'Long concombre anglais du XIXe siècle', 'commons:Dar_cucumber_seedlings.jpg'],
        ['Melon', 'Vieille France', 'Cucumis melo', 'MEL30', 2.10, 'Charentais brodé, chair orange très sucrée', 'commons:Charentais-Melone.jpg|commons:Cantaloupe.jpg|commons:Pumpkin_seeds_in_hand.jpg'],
    ];

    /** Aromatiques au gramme : [plante, variété, latin, réf., prix HT du plus petit sachet, grammes de ce sachet, conseil, photo]. */
    public const AROMATICS = [
        ['Basilic sacré', 'Tulsi', 'Ocimum tenuiflorum', 'BAS25', 7.05, 2, 'Plante sacrée en Inde, parfum de clou de girofle', 'commons:Ocimum_tenuiflorum.jpg|commons:Tulsi_(2070558022).jpg|commons:Basil_leaves.jpg'],
        ['Basilic', 'Cannelle', 'Ocimum basilicum', 'BAS03', 4.65, 2, 'Tiges pourpres et parfum de cannelle', 'commons:Thai_basil_with_flowers.jpg|commons:Basil_leaves.jpg'],
        ['Basilic', 'Citron', 'Ocimum × citriodorum', 'BAS04', 5.70, 2, 'Arôme de citron, parfait avec le poisson', 'commons:Lemon_Basil.jpg|commons:Basil_leaves.jpg'],
        ['Basilic', 'Thaï', 'Ocimum basilicum var. thyrsiflora', 'BAS09', 5.70, 2, 'Anisé, indispensable de la cuisine asiatique', 'commons:Thai_basil.jpg|commons:Basil_leaves.jpg'],
        ['Basilic', 'Réglisse', 'Ocimum basilicum', 'BAS18', 4.95, 2, 'Notes douces de réglisse', 'commons:Thai_basil_flower.jpg|commons:Basil_leaves.jpg'],
        ['Basilic', 'Rouge Osmin', 'Ocimum basilicum', 'BAS06', 2.90, 2, 'Feuillage pourpre presque noir', 'commons:Ocimum_basilicum_001.JPG|commons:Basil_leaves.jpg'],
        ['Basilic', 'Grec', 'Ocimum basilicum var. minimum', 'BAS10', 2.75, 2, 'Boule compacte à petites feuilles, idéal en pot', 'commons:Pot_of_basil_sprouts_(Ocimum_basilicum)_-_20050422.jpg|commons:Basil_leaves.jpg'],
        ['Thym', "d'hiver", 'Thymus vulgaris', 'THY02', 3.85, 1, 'Fleurs rose pâle, en cuisine ou en tisane', 'commons:Fresh_thyme.jpg|commons:Thyme.jpg'],
        ['Agastache', 'anisée', 'Agastache foeniculum', 'AGA01', 4.15, 1, 'Feuilles au goût d\'anis, fleurs adorées des abeilles', 'commons:Agastache_foeniculum.jpg|commons:Fresh_thyme.jpg'],
        ['Hysope', 'officinale', 'Hyssopus officinalis', 'HYS01', 5.05, 1, 'Vivace mellifère à la saveur mentholée', 'commons:Hyssopus_officinalis.jpg|commons:Hyssopus_officinalis_004.JPG|commons:Rosemary_Plant.jpg'],
        ['Sarriette', 'vivace', 'Satureja montana', 'SAR02', 1.80, 1, 'L\'herbe des haricots, résiste à la sécheresse', 'commons:Satureja_montana_1.JPG|commons:Satureja_montana_3c.JPG|commons:Thyme.jpg'],
    ];

    /** Crée le catalogue de variétés s'il est vide. */
    public static function seedCatalog(): void
    {
        $db = db();
        if ((int)$db->val('SELECT COUNT(*) FROM varieties')) return;
        foreach (self::SEEDS as $i => [$n, $v, $l, $ref, $p10, $note, $ph]) {
            $known = $ref === 'TO134';
            $p100 = $known ? 9.40 : round($p10 * self::RATIO_100, 2);
            $db->insert('varieties', ['category' => 'seeds', 'name' => $n, 'variety' => $v, 'latin_name' => $l, 'note' => $note, 'photo' => $ph,
                'portion_qty' => 10, 'unit' => 'graines', 'purchase_price' => round($p100 * self::VAT, 2), 'purchase_qty' => 100, 'market_price' => self::MARKET,
                'supplier' => $known ? 'Agrosemens' : 'Agrosemens (prix des 100 graines estimé)', 'supplier_ref' => $ref, 'position' => $i]);
        }
        foreach (self::AROMATICS as $i => [$n, $v, $l, $ref, $p, $grams, $note, $ph]) {
            $db->insert('varieties', ['category' => 'aromatics', 'name' => $n, 'variety' => $v, 'latin_name' => $l, 'note' => $note, 'photo' => $ph,
                'portion_qty' => 0.5, 'unit' => 'g', 'purchase_price' => round($p * self::VAT, 2), 'purchase_qty' => $grams, 'market_price' => self::MARKET,
                'supplier' => 'Agrosemens (plus petit sachet)', 'supplier_ref' => $ref, 'position' => $i]);
        }
    }

    public static function seed(): void
    {
        $db = db();
        self::seedCatalog();
        $seedIds = array_map('intval', $db->col("SELECT id FROM varieties WHERE category = 'seeds' AND is_active = 1 ORDER BY position"));

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
        mt_srand(2027);
        foreach ($groups as [$commune, $cp, $type, $org, $place, $addr, $photo, $stage, $vol]) {
            $name = Groups::defaultName($commune);
            $gid = $db->insert('groups', ['slug' => Groups::uniqueSlug($name), 'name' => $name, 'commune' => $commune, 'postal_code' => $cp,
                'organizer_type' => $type, 'organizer_name' => $org, 'pickup_place' => $place, 'pickup_address' => $addr, 'photo' => $photo,
                'status' => 'active', 'validated_at' => now(),
                'description' => "Un groupe d'achat de voisins à $commune : on choisit ensemble les variétés du pack de saison, on les achète en gros, et les bénévoles préparent les sachets."]);
            $members = array_slice($users, ($u * 4) % 10, 8);
            $u++;
            foreach ($members as $k => $mid) {
                $db->exec('INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)', [$gid, $mid, $k < $vol ? 'volunteer' : 'member']);
            }
            $sid = $db->insert('sales', ['group_id' => $gid, 'category' => 'seeds', 'title' => 'Graines potagères — printemps ' . (date('Y') + 1),
                'vote_days' => 10, 'sale_days' => 21, 'batch_size' => 10, 'price' => 30, 'max_choices' => 10, 'pickup_at' => date('Y-m-d 10:00:00', strtotime('+40 days'))]);
            Varieties::setForSale($sid, $seedIds);
            if ($stage === 'voting' || $stage === 'open') {
                $db->exec("UPDATE sales SET status = 'voting', vote_starts_at = NOW() - INTERVAL 6 DAY, vote_ends_at = NOW() + INTERVAL 4 DAY WHERE id = ?", [$sid]);
                // Chaque membre coche 6 à 10 variétés ; les tomates et les aubergines sont un peu plus demandées.
                $weighted = [];
                foreach ($seedIds as $k => $vid) for ($w = 0; $w < ($k < 14 ? 3 : ($k < 23 ? 2 : 1)); $w++) $weighted[] = $vid;
                foreach ($members as $mid) {
                    $pick = [];
                    $want = mt_rand(6, 10);
                    while (count($pick) < $want) $pick[$weighted[mt_rand(0, count($weighted) - 1)]] = true;
                    foreach (array_keys($pick) as $vid) $db->exec('INSERT IGNORE INTO variety_votes (sale_id, user_id, variety_id) VALUES (?, ?, ?)', [$sid, $mid, $vid]);
                }
            }
            if ($stage === 'open') {
                $s = Sales::one($sid);
                $comp = Varieties::compose($s['varieties'], (float)$s['price']);
                $packId = Varieties::createPack($comp, $s);
                $db->exec("UPDATE sales SET status = 'open', winning_pack_id = ?, vote_starts_at = NOW() - INTERVAL 16 DAY, vote_ends_at = NOW() - INTERVAL 6 DAY,
                    opened_at = NOW() - INTERVAL 6 DAY, sale_ends_at = NOW() + INTERVAL 15 DAY WHERE id = ?", [$packId, $sid]);
                $db->insert('sale_batches', ['sale_id' => $sid, 'batch_no' => 1, 'status' => 'complete', 'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]);
                $db->insert('sale_batches', ['sale_id' => $sid, 'batch_no' => 2, 'status' => 'open']);
                foreach (array_merge(array_fill(0, 10, 1), array_fill(0, 6, 2)) as $k => $batch) {
                    $buyer = $users[$k % count($users)];
                    $bu = $db->one('SELECT * FROM users WHERE id = ?', [$buyer]);
                    $db->insert('orders', ['reference' => 'DEMO-' . $sid . '-' . $k, 'user_id' => $buyer, 'sale_id' => $sid, 'pack_id' => $packId, 'quantity' => 1,
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
        foreach ([['packing', 'Préparation des sachets de graines', '+20 days 10:00', 3, 4], ['distribution', 'Distribution des packs', '+40 days 10:00', 3, 3]] as [$type, $title, $when, $h, $need]) {
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
            $packs = $db->col("SELECT s.winning_pack_id FROM sales s JOIN `groups` g ON g.id = s.group_id WHERE g.organizer_name LIKE 'Exemple —%' AND s.winning_pack_id IS NOT NULL");
            $db->exec("DELETE o FROM orders o JOIN sales s ON s.id = o.sale_id JOIN `groups` g ON g.id = s.group_id WHERE g.organizer_name LIKE 'Exemple —%'");
            $db->exec("DELETE o FROM orders o JOIN users u ON u.id = o.user_id WHERE u.email LIKE '%@exemple.test'");
            $db->exec("DELETE FROM `groups` WHERE organizer_name LIKE 'Exemple —%'");
            $db->exec("DELETE FROM shifts WHERE group_id IS NULL AND location = 'Salle des fêtes, Pins-Justaret'");
            $db->exec("DELETE FROM users WHERE email LIKE '%@exemple.test'");
            foreach ($packs as $pid) $db->exec('DELETE FROM packs WHERE id = ? AND id NOT IN (SELECT pack_id FROM orders) AND id NOT IN (SELECT winning_pack_id FROM sales WHERE winning_pack_id IS NOT NULL)', [$pid]);
            $in = implode(',', array_fill(0, count(self::OLD_PACKS), '?'));
            $db->exec("DELETE FROM packs WHERE name IN ($in) AND id NOT IN (SELECT pack_id FROM orders) AND id NOT IN (SELECT winning_pack_id FROM sales WHERE winning_pack_id IS NOT NULL)", self::OLD_PACKS);
        });
        set_setting('demo_data', '0');
    }
}
